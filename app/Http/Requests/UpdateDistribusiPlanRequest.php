<?php

namespace App\Http\Requests;

use App\Models\Produksi;
use App\Models\Sekolah;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDistribusiPlanRequest extends FormRequest
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
            'sekolah_id' => ['required', 'integer', Rule::exists('sekolah', 'id')->where('status', Sekolah::STATUS_ACTIVE)],
            'jumlah_porsi' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Distribution plans may only be updated while the production has not started.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $produksi = $this->route('produksi');

            if ($produksi instanceof Produksi && $produksi->status !== Produksi::STATUS_BELUM_DIMULAI) {
                $validator->errors()->add('produksi', 'Rencana distribusi tidak dapat diubah setelah produksi dimulai.');
            }
        });
    }
}
