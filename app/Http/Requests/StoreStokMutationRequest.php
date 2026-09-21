<?php

namespace App\Http\Requests;

use App\Models\BahanBaku;
use App\Models\StokMutation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStokMutationRequest extends FormRequest
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
        $allowedTipe = $this->user()?->role === 'admin'
            ? [StokMutation::TIPE_MASUK, StokMutation::TIPE_KELUAR, StokMutation::TIPE_PENYESUAIAN]
            : [StokMutation::TIPE_MASUK, StokMutation::TIPE_KELUAR];

        $jumlahRule = $this->input('tipe') === StokMutation::TIPE_PENYESUAIAN
            ? ['required', 'numeric']
            : ['required', 'numeric', 'gt:0'];

        return [
            'bahan_baku_id' => ['required', 'integer', Rule::exists('bahan_baku', 'id')],
            'tipe' => ['required', Rule::in($allowedTipe)],
            'jumlah' => $jumlahRule,
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string'],
        ];
    }

    /**
     * Enforce the business rule that stock must never become negative.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tipe = $this->string('tipe')->toString();
            $jumlah = (float) $this->input('jumlah');
            $bahanBaku = BahanBaku::find($this->input('bahan_baku_id'));

            if ($bahanBaku === null) {
                return;
            }

            $tersedia = $bahanBaku->stokTersedia();

            if ($tipe === StokMutation::TIPE_PENYESUAIAN && $jumlah == 0) {
                $validator->errors()->add('jumlah', 'Jumlah penyesuaian tidak boleh 0.');
            }

            if ($tipe === StokMutation::TIPE_KELUAR && $jumlah > $tersedia) {
                $validator->errors()->add(
                    'jumlah',
                    "Stok tidak mencukupi. Stok tersedia {$tersedia} {$bahanBaku->satuan}.",
                );
            }

            if ($tipe === StokMutation::TIPE_PENYESUAIAN && $tersedia + $jumlah < 0) {
                $validator->errors()->add(
                    'jumlah',
                    "Penyesuaian membuat stok negatif. Stok tersedia {$tersedia} {$bahanBaku->satuan}.",
                );
            }
        });
    }
}
