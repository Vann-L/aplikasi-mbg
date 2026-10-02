<?php

namespace App\Http\Requests;

use App\Models\PolaJadwal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as BaseValidator;

class PolaJadwalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pegawai_id' => ['required', 'integer', Rule::exists('pegawai', 'id')],
            'berlaku_mulai' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'hari' => ['required', 'array', 'size:7'],
            'hari.*.status' => ['required', Rule::in([PolaJadwal::STATUS_KERJA, PolaJadwal::STATUS_LIBUR])],
            'hari.*.jam_masuk' => ['nullable', 'date_format:H:i'],
            'hari.*.jam_pulang' => ['nullable', 'date_format:H:i'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hari.size' => 'Pola jadwal harus mencakup 7 hari (Senin sampai Minggu).',
            'hari.required' => 'Data pola hari tidak ditemukan.',
            'hari.*.status.in' => 'Status hari hanya boleh "kerja" atau "libur".',
        ];
    }

    /**
     * Normalize the submitted `hari` array into a canonical list of seven
     * entries indexed 0..6, with null jam values on libur days.
     *
     * @return list<array{status: string, jam_masuk: string|null, jam_pulang: string|null}>
     */
    public function hariCanonical(): array
    {
        $hari = $this->input('hari', []);

        ksort($hari);

        return array_values(array_map(function (array $item): array {
            $status = $item['status'] ?? PolaJadwal::STATUS_LIBUR;

            if ($status === PolaJadwal::STATUS_LIBUR) {
                return ['status' => $status, 'jam_masuk' => null, 'jam_pulang' => null];
            }

            return [
                'status' => $status,
                'jam_masuk' => $item['jam_masuk'] !== '' ? ($item['jam_masuk'] ?? null) : null,
                'jam_pulang' => $item['jam_pulang'] !== '' ? ($item['jam_pulang'] ?? null) : null,
            ];
        }, $hari));
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable(BaseValidator): void>
     */
    public function after(): array
    {
        return [
            function (BaseValidator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->aturanKerja($validator);
                $this->aturanDuplikat($validator);
            },
        ];
    }

    /**
     * Every kerja day must have both jam values set, and pulang after masuk.
     */
    protected function aturanKerja(BaseValidator $validator): void
    {
        foreach ($this->hariCanonical() as $index => $item) {
            $namaHari = PolaJadwal::NAMA_HARI[$index] ?: 'hari ke-'.($index + 1);

            if ($item['status'] !== PolaJadwal::STATUS_KERJA) {
                continue;
            }

            if ($item['jam_masuk'] === null) {
                $validator->errors()->add("hari.{$index}.jam_masuk", "Jam masuk wajib diisi untuk hari kerja ($namaHari).");
            }

            if ($item['jam_pulang'] === null) {
                $validator->errors()->add("hari.{$index}.jam_pulang", "Jam pulang wajib diisi untuk hari kerja ($namaHari).");
            }

            if ($item['jam_masuk'] !== null && $item['jam_pulang'] !== null
                && strtotime($item['jam_pulang']) <= strtotime($item['jam_masuk'])) {
                $validator->errors()->add("hari.{$index}.jam_pulang", "Jam pulang harus lebih besar dari jam masuk ($namaHari).");
            }
        }
    }

    /**
     * A pegawai may only have one pattern starting on a given date.
     */
    protected function aturanDuplikat(BaseValidator $validator): void
    {
        $query = PolaJadwal::query()
            ->where('pegawai_id', $this->integer('pegawai_id'))
            ->whereDate('berlaku_mulai', $this->date('berlaku_mulai'));

        $current = $this->route('pola');

        if ($current !== null) {
            $query->whereKeyNot($current->getKey());
        }

        if ($query->exists()) {
            $validator->errors()->add('berlaku_mulai', 'Pegawai sudah memiliki pola jadwal yang berlaku mulai tanggal tersebut. Gunakan tanggal berbeda untuk merevisi pola.');
        }
    }
}
