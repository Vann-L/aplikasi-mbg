<?php

namespace App\Http\Requests;

use App\Models\Produksi;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateHasilRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'petugas'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'hasil_porsi' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * The production result can only be recorded after production has started.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $produksi = $this->route('produksi');

            if ($produksi instanceof Produksi && $produksi->status === Produksi::STATUS_BELUM_DIMULAI) {
                $validator->errors()->add('hasil_porsi', 'Hasil produksi hanya dapat dicatat setelah produksi dimulai.');
            }
        });
    }
}
