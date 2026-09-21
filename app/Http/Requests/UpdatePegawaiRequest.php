<?php

namespace App\Http\Requests;

use App\Models\Pegawai;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePegawaiRequest extends FormRequest
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
            'id_pegawai' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pegawai', 'id_pegawai')->ignore($this->route('pegawai')),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'bagian' => ['nullable', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in([Pegawai::STATUS_ACTIVE, Pegawai::STATUS_INACTIVE])],
        ];
    }
}
