<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PolaJadwalRequest;
use App\Http\Requests\StoreJadwalRequest;
use App\Http\Requests\UpdateJadwalRequest;
use App\Models\Absensi;
use App\Models\JadwalPegawai;
use App\Models\KalenderOperasional;
use App\Models\Pegawai;
use App\Models\PolaJadwal;
use App\Services\JadwalEfektifService;
use App\Services\JadwalPegawaiService;
use Carbon\CarbonImmutable;
use Database\Factories\PolaJadwalFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JadwalController extends Controller
{
    public function __construct(
        private readonly JadwalPegawaiService $jadwalService,
        private readonly JadwalEfektifService $jadwalEfektif,
    ) {}

    /**
     * Display the per-date override listing (tangled dates tab).
     */
    public function index(Request $request): View
    {
        $tanggal = $request->string('tanggal')->toString();
        $pegawaiId = $request->integer('pegawai_id');
        $status = $request->string('status')->toString();

        $jadwal = JadwalPegawai::query()
            ->join('pegawai', 'jadwal_pegawai.pegawai_id', '=', 'pegawai.id')
            ->with('pegawai')
            ->select('jadwal_pegawai.*')
            ->when($tanggal !== '', fn ($query) => $query->whereDate('jadwal_pegawai.tanggal', $tanggal))
            ->when($pegawaiId > 0, fn ($query) => $query->where('jadwal_pegawai.pegawai_id', $pegawaiId))
            ->when(in_array($status, JadwalPegawai::STATUSES, true), fn ($query) => $query->where('jadwal_pegawai.status', $status))
            ->orderByDesc('jadwal_pegawai.tanggal')
            ->orderBy('pegawai.nama')
            ->orderByDesc('jadwal_pegawai.id')
            ->paginate(15)
            ->withQueryString();

        return view('jadwal.index', [
            'jadwal' => $jadwal,
            'pegawaiList' => Pegawai::orderBy('nama')->get(),
            'tanggal' => $tanggal,
            'pegawaiId' => $pegawaiId,
            'status' => $status,
            'routePrefix' => 'admin',
            'canManage' => true,
            'active' => 'override',
        ]);
    }

    /**
     * Show the create form for a per-date override.
     */
    public function create(): View
    {
        return view('jadwal.create', [
            'pegawaiList' => $this->pegawaiDapatDijadwalkan(),
            'active' => 'override',
        ]);
    }

    /**
     * Store a new per-date override.
     */
    public function store(StoreJadwalRequest $request): RedirectResponse
    {
        JadwalPegawai::create($request->validated());

        return redirect()->route('admin.jadwal.index')->with('status', 'Jadwal berhasil ditambahkan.');
    }

    /**
     * Display the specified per-date override.
     */
    public function show(JadwalPegawai $jadwal): View
    {
        $jadwal->load('pegawai');

        return view('jadwal.show', [
            'jadwal' => $jadwal,
            'routePrefix' => 'admin',
            'canManage' => true,
            'hasAbsensi' => $this->jadwalService->hasAbsensi($jadwal),
            'active' => 'override',
        ]);
    }

    /**
     * Show the form for editing the specified per-date override.
     */
    public function edit(JadwalPegawai $jadwal): View
    {
        return view('jadwal.edit', [
            'jadwal' => $jadwal,
            'pegawaiList' => $this->pegawaiDapatDijadwalkan(),
            'hasAbsensi' => $this->jadwalService->hasAbsensi($jadwal),
            'active' => 'override',
        ]);
    }

    /**
     * Update the specified per-date override.
     */
    public function update(UpdateJadwalRequest $request, JadwalPegawai $jadwal): RedirectResponse
    {
        $data = $request->validated();

        if ($this->jadwalService->fieldAbsensiBerubah($jadwal, $data) !== []) {
            return back()->withInput()->with('error', 'Jadwal yang sudah memiliki absensi tidak dapat diubah pegawai, tanggal, jam, atau statusnya. Hanya catatan yang boleh diperbarui.');
        }

        $jadwal->update($data);

        return redirect()->route('admin.jadwal.index')->with('status', 'Jadwal berhasil diperbarui.');
    }

    /**
     * Remove the specified per-date override.
     */
    public function destroy(JadwalPegawai $jadwal): RedirectResponse
    {
        if ($this->jadwalService->hasAbsensi($jadwal)) {
            return redirect()->route('admin.jadwal.index')
                ->with('error', 'Jadwal yang sudah memiliki absensi tidak dapat dihapus.');
        }

        $jadwal->delete();

        return redirect()->route('admin.jadwal.index')->with('status', 'Jadwal berhasil dihapus.');
    }

    /**
     * Display the weekly pattern matrix and its history.
     */
    public function pola(): View
    {
        $pegawaiList = $this->pegawaiDapatDijadwalkan();
        $hariIni = CarbonImmutable::today(config('app.timezone'));

        $matriks = $pegawaiList->map(function (Pegawai $pegawai) use ($hariIni): array {
            $polaAktif = $this->jadwalEfektif->polaAktif($pegawai, $hariIni);

            return [
                'pegawai' => $pegawai,
                'pola' => $polaAktif,
                'hari' => $polaAktif?->hari ?? array_fill(0, 7, ['status' => null]),
            ];
        });

        $polas = PolaJadwal::query()
            ->with('pegawai')
            ->orderByDesc('berlaku_mulai')
            ->orderByDesc('id')
            ->take(50)
            ->get();

        return view('jadwal.pola', [
            'matriks' => $matriks,
            'polas' => $polas,
            'routePrefix' => 'admin',
            'canManage' => true,
            'active' => 'pola',
            'namaHari' => PolaJadwal::NAMA_HARI,
        ]);
    }

    /**
     * Show the form for creating a weekly pattern.
     */
    public function polaCreate(): View
    {
        return view('jadwal.pola-create', [
            'pegawaiList' => $this->pegawaiDapatDijadwalkan(),
            'hariForm' => array_combine(range(1, 7), PolaJadwalFactory::polaHari()),
            'routePrefix' => 'admin',
            'canManage' => true,
            'active' => 'pola',
        ]);
    }

    /**
     * Store a new weekly pattern.
     */
    public function polaStore(PolaJadwalRequest $request): RedirectResponse
    {
        PolaJadwal::create([
            ...$request->safe()->only(['pegawai_id', 'berlaku_mulai', 'keterangan']),
            'hari' => $request->hariCanonical(),
        ]);

        return redirect()->route('admin.jadwal.pola')->with('status', 'Pola jadwal mingguan berhasil disimpan.');
    }

    /**
     * Show the form for editing a weekly pattern.
     */
    public function polaEdit(PolaJadwal $pola): View
    {
        $pola->load('pegawai');

        return view('jadwal.pola-edit', [
            'pola' => $pola,
            'pegawaiList' => $this->pegawaiDapatDijadwalkan(),
            'hariForm' => array_combine(range(1, 7), $pola->hari),
            'routePrefix' => 'admin',
            'canManage' => true,
            'active' => 'pola',
        ]);
    }

    /**
     * Update a weekly pattern.
     */
    public function polaUpdate(PolaJadwalRequest $request, PolaJadwal $pola): RedirectResponse
    {
        $pola->update([
            ...$request->safe()->only(['pegawai_id', 'berlaku_mulai', 'keterangan']),
            'hari' => $request->hariCanonical(),
        ]);

        return redirect()->route('admin.jadwal.pola')->with('status', 'Pola jadwal mingguan berhasil diperbarui.');
    }

    /**
     * Remove a weekly pattern, guarded against attendance already recorded
     * under the period it covers.
     */
    public function polaDestroy(PolaJadwal $pola): RedirectResponse
    {
        $mulai = CarbonImmutable::parse($pola->berlaku_mulai->toDateString());

        $next = PolaJadwal::query()
            ->where('pegawai_id', $pola->pegawai_id)
            ->whereDate('berlaku_mulai', '>', $mulai->toDateString())
            ->orderBy('berlaku_mulai')
            ->first();

        $akhir = $next !== null
            ? CarbonImmutable::parse($next->berlaku_mulai->toDateString())->subDay()
            : CarbonImmutable::today(config('app.timezone'));

        $adaAbsensi = Absensi::query()
            ->where('pegawai_id', $pola->pegawai_id)
            ->whereBetween('tanggal', [$mulai->toDateString(), $akhir->toDateString()])
            ->exists();

        if ($adaAbsensi) {
            return redirect()->route('admin.jadwal.pola')
                ->with('error', 'Pola jadwal tidak dapat dihapus karena sudah ada absensi pada periode yang dicakupnya.');
        }

        $pola->delete();

        return redirect()->route('admin.jadwal.pola')->with('status', 'Pola jadwal mingguan berhasil dihapus.');
    }

    /**
     * Display the SPPG-wide operational calendar.
     */
    public function kalender(): View
    {
        $kalender = KalenderOperasional::query()
            ->orderByDesc('tanggal')
            ->paginate(15)
            ->withQueryString();

        return view('jadwal.kalender', [
            'kalender' => $kalender,
            'routePrefix' => 'admin',
            'canManage' => true,
            'active' => 'kalender',
        ]);
    }

    /**
     * Store a new operational calendar entry.
     */
    public function kalenderStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'status' => ['required', Rule::in(KalenderOperasional::STATUSES)],
            'alasan' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        if (KalenderOperasional::whereDate('tanggal', $validated['tanggal'])->exists()) {
            return back()->withInput()->with('error', 'Tanggal '.$validated['tanggal'].' sudah ada di kalender operasional.');
        }

        KalenderOperasional::create($validated);

        return back()->with('status', 'Kalender operasional berhasil diperbarui.');
    }

    /**
     * Update an operational calendar entry.
     */
    public function kalenderUpdate(Request $request, KalenderOperasional $kalender): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(KalenderOperasional::STATUSES)],
            'alasan' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $kalender->update($validated);

        return back()->with('status', 'Kalender operasional berhasil diperbarui.');
    }

    /**
     * Remove an operational calendar entry.
     */
    public function kalenderDestroy(KalenderOperasional $kalender): RedirectResponse
    {
        $kalender->delete();

        return back()->with('status', 'Kalender operasional berhasil dihapus.');
    }

    /**
     * Swap the schedule of two pegawai on a single date using overrides.
     */
    public function tukar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'pegawai_a' => ['required', 'integer', 'exists:pegawai,id'],
            'pegawai_b' => ['required', 'integer', 'different:pegawai_a', 'exists:pegawai,id'],
        ]);

        $tanggal = CarbonImmutable::parse($validated['tanggal']);
        $pegawaiA = Pegawai::findOrFail($validated['pegawai_a']);
        $pegawaiB = Pegawai::findOrFail($validated['pegawai_b']);

        $efektifA = $this->jadwalEfektif->efektif($pegawaiA, $tanggal);
        $efektifB = $this->jadwalEfektif->efektif($pegawaiB, $tanggal);

        $bisaKerja = fn (?object $efektif): bool => $efektif !== null
            && $efektif->status === JadwalPegawai::STATUS_TERJADWAL
            && $efektif->jamMasuk !== null
            && $efektif->jamPulang !== null;

        if (! $bisaKerja($efektifA) || ! $bisaKerja($efektifB)) {
            return back()->withInput()->with('error', 'Tukar jadwal ditolak: kedua pegawai harus memiliki jadwal efektif (terjadwal) pada tanggal tersebut.');
        }

        foreach ([$pegawaiA, $pegawaiB] as $index => $pegawai) {
            $jadwalEfektif = $index === 0 ? $efektifB : $efektifA;
            $pasangan = $index === 0 ? $pegawaiB : $pegawaiA;

            JadwalPegawai::updateOrCreate(
                ['pegawai_id' => $pegawai->id, 'tanggal' => $tanggal->toDateString()],
                [
                    'jam_masuk' => $jadwalEfektif->jamMasuk->format('H:i'),
                    'jam_pulang' => $jadwalEfektif->jamPulang->format('H:i'),
                    'status' => JadwalPegawai::STATUS_TERJADWAL,
                    'catatan' => 'Tukar jadwal tanggal '.$tanggal->format('d/m/Y').' dengan '.$pasangan->nama.'.',
                ],
            );
        }

        return redirect()->route('admin.jadwal.index')->with(
            'status',
            'Jadwal '.$pegawaiA->nama.' dan '.$pegawaiB->nama.' pada '.$tanggal->format('d/m/Y').' berhasil ditukar.',
        );
    }

    /**
     * Display the weekly attendance monitoring matrix.
     */
    public function monitoring(Request $request): View
    {
        $tanggal = $request->string('tanggal')->toString();
        $acuan = $tanggal !== '' ? CarbonImmutable::parse($tanggal) : CarbonImmutable::today(config('app.timezone'));
        $mulai = $acuan->startOfWeek(CarbonImmutable::MONDAY);
        $selesai = $mulai->addDays(6);

        return view('jadwal.monitoring', [
            'rows' => $this->jadwalEfektif->monitoringMingguan($mulai),
            'mulai' => $mulai,
            'selesai' => $selesai,
            'tanggal' => $acuan->toDateString(),
            'routePrefix' => 'admin',
            'canManage' => true,
            'active' => 'monitoring',
            'namaHari' => PolaJadwal::NAMA_HARI,
        ]);
    }

    /**
     * Active pegawai eligible for a new schedule.
     *
     * @return Collection<int, Pegawai>
     */
    private function pegawaiDapatDijadwalkan(): Collection
    {
        return Pegawai::query()
            ->where('status', Pegawai::STATUS_ACTIVE)
            ->orderBy('nama')
            ->get();
    }
}
