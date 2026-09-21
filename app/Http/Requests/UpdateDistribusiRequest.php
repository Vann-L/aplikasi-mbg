<?php

namespace App\Http\Requests;

use App\Models\Distribusi;
use App\Models\Pegawai;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDistribusiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'petugas_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    $valid = Pegawai::query()
                        ->where('id', $value)
                        ->where('status', Pegawai::STATUS_ACTIVE)
                        ->whereHas('user', fn ($user) => $user->where('role', 'petugas')->where('status', 'active'))
                        ->exists();

                    if (! $valid) {
                        $fail('Petugas yang dipilih bukan pegawai aktif dengan akun operasional.');
                    }
                },
            ],
            'kendaraan' => 'nullable|string|max:255',
            'tanggal' => 'required|date',
            'jam_berangkat' => 'nullable|date_format:H:i',
            'catatan' => 'nullable|string',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $distribusi = $this->route('distribusi');

        if (! $distribusi instanceof Distribusi) {
            return;
        }

        $validator->after(function (Validator $validator) use ($distribusi): void {
            if (! in_array($distribusi->status, Distribusi::STATUS_PERENCANAAN, true)) {
                $validator->errors()->add('status', 'Distribusi yang sudah dikirim tidak dapat dijadwalkan ulang.');
            }
        });
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'petugas_id' => 'petugas',
            'tanggal' => 'tanggal',
            'jam_berangkat' => 'jam berangkat',
        ];
    }
}
