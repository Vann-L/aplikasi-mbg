<?php

namespace App\Http\Requests;

use App\Models\JadwalPegawai;
use App\Models\Pegawai;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreJadwalRequest extends FormRequest
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
            'pegawai_id' => [
                'required',
                'integer',
                Rule::exists('pegawai', 'id')->where('status', Pegawai::STATUS_ACTIVE),
            ],
            'tanggal' => ['required', 'date'],
            'status' => ['required', Rule::in(JadwalPegawai::STATUSES)],
            'jam_masuk' => ['nullable', 'date_format:H:i'],
            'jam_pulang' => ['nullable', 'date_format:H:i'],
            'catatan' => ['nullable', 'string', 'max:255'],
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
            'pegawai_id.exists' => 'Pegawai tidak valid atau tidak aktif.',
        ];
    }

    /**
     * Reject a second schedule for the same pegawai on the same date.
     */
    protected function aturanDuplikat(Validator $validator, ?int $ignoreId = null): void
    {
        $query = JadwalPegawai::query()
            ->where('pegawai_id', $this->integer('pegawai_id'))
            ->whereDate('tanggal', $this->date('tanggal'));

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            $validator->errors()->add('tanggal', 'Pegawai sudah memiliki jadwal pada tanggal tersebut.');
        }
    }

    /**
     * Enforce the status-specific jam rules.
     */
    protected function aturanStatus(Validator $validator): void
    {
        $status = $this->string('status')->toString();
        $jamMasuk = $this->input('jam_masuk');
        $jamPulang = $this->input('jam_pulang');

        if ($status === JadwalPegawai::STATUS_TERJADWAL) {
            if ($jamMasuk === null) {
                $validator->errors()->add('jam_masuk', 'Jam masuk wajib diisi untuk jadwal terjadwal.');
            }

            if ($jamPulang === null) {
                $validator->errors()->add('jam_pulang', 'Jam pulang wajib diisi untuk jadwal terjadwal.');
            }

            if ($jamMasuk !== null && $jamPulang !== null && strtotime((string) $jamPulang) <= strtotime((string) $jamMasuk)) {
                $validator->errors()->add('jam_pulang', 'Jam pulang harus lebih besar dari jam masuk.');
            }

            return;
        }

        if ($jamMasuk !== null) {
            $validator->errors()->add('jam_masuk', 'Jam harus kosong untuk status '.$status.'.');
        }

        if ($jamPulang !== null) {
            $validator->errors()->add('jam_pulang', 'Jam harus kosong untuk status '.$status.'.');
        }
    }

    /**
     * Get the "after" validation callables for the request.
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

                $this->aturanDuplikat($validator);
                $this->aturanStatus($validator);
            },
        ];
    }
}
