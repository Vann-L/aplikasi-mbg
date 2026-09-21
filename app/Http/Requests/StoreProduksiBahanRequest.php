<?php

namespace App\Http\Requests;

use App\Models\BahanBaku;
use App\Models\Produksi;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProduksiBahanRequest extends FormRequest
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
            'bahan_baku_id' => ['required', 'integer', Rule::exists('bahan_baku', 'id')->where('status', BahanBaku::STATUS_ACTIVE)],
            'jumlah' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * Ingredients may only be managed while the production has not started.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $produksi = $this->route('produksi');

            if ($produksi instanceof Produksi && $produksi->status !== Produksi::STATUS_BELUM_DIMULAI) {
                $validator->errors()->add('bahan', 'Bahan produksi tidak dapat diubah setelah produksi dimulai.');
            }
        });
    }
}
