<?php

namespace App\Http\Requests;

use App\Models\JadwalPegawai;
use App\Services\JadwalPegawaiService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateJadwalRequest extends StoreJadwalRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $jadwal = $this->route('jadwal');

        if ($jadwal instanceof JadwalPegawai && app(JadwalPegawaiService::class)->hasAbsensi($jadwal)) {
            return [
                'pegawai_id' => ['nullable', 'integer'],
                'tanggal' => ['nullable', 'date'],
                'status' => ['nullable', Rule::in(JadwalPegawai::STATUSES)],
                'jam_masuk' => ['nullable', 'date_format:H:i'],
                'jam_pulang' => ['nullable', 'date_format:H:i'],
                'catatan' => ['nullable', 'string', 'max:255'],
            ];
        }

        return parent::rules();
    }

    /**
     * Reject edits that would silently rewrite attendance history and extra
     * checks that must ignore the record being updated.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $jadwal = $this->route('jadwal');
                $service = app(JadwalPegawaiService::class);
                $hasAbsensi = $jadwal instanceof JadwalPegawai && $service->hasAbsensi($jadwal);

                $pegawaiId = $this->input('pegawai_id');
                $tanggal = $this->input('tanggal');

                if (! $hasAbsensi || ($pegawaiId !== null && $tanggal !== null)) {
                    $this->aturanDuplikat($validator, $jadwal instanceof JadwalPegawai ? $jadwal->id : null);
                }

                if (! $hasAbsensi || $this->has('status')) {
                    $this->aturanStatus($validator);
                }

                if ($hasAbsensi && $service->fieldAbsensiBerubah($jadwal, $this->all()) !== []) {
                    $validator->errors()->add('tanggal', 'Jadwal yang sudah memiliki absensi tidak dapat diubah pegawai, tanggal, jam, atau statusnya. Hanya catatan yang boleh diperbarui.');
                }
            },
        ];
    }
}
