<?php

namespace App\Http\Requests;

use App\Models\Sekolah;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSekolahRequest extends FormRequest
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
            'npsn' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sekolah', 'npsn')->ignore($this->route('sekolah')),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'kontak' => ['nullable', 'string', 'max:255'],
            'jumlah_penerima' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in([Sekolah::STATUS_ACTIVE, Sekolah::STATUS_INACTIVE])],
        ];
    }
}
