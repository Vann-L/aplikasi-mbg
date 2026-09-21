<?php

namespace App\Http\Requests;

use App\Models\Distribusi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePenerimaanRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jumlah_diterima' => ['required', 'integer', 'min:1'],
            'waktu_diterima' => ['required', 'date'],
            'penerima_nama' => ['required', 'string', 'max:255'],
            'foto_bukti' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'catatan' => ['nullable', 'string'],
        ];
    }

    /**
     * Business rules tied to the target distribution.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $distribusi = $this->route('distribusi');

            if (! $distribusi instanceof Distribusi) {
                return;
            }

            if ($distribusi->status !== Distribusi::STATUS_DIKIRIM) {
                $validator->errors()->add('distribusi', 'Hanya distribusi berstatus dikirim yang dapat dicatat penerimaannya.');

                return;
            }

            if ($distribusi->penerimaan()->exists()) {
                $validator->errors()->add('distribusi', 'Penerimaan untuk distribusi ini sudah tercatat.');

                return;
            }

            if ($this->filled('jumlah_diterima') && (int) $this->input('jumlah_diterima') > $distribusi->jumlah_porsi) {
                $validator->errors()->add('jumlah_diterima', 'Jumlah diterima tidak boleh melebihi jumlah dikirim.');
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
            'jumlah_diterima' => 'jumlah diterima',
            'waktu_diterima' => 'waktu diterima',
            'penerima_nama' => 'nama penerima',
            'foto_bukti' => 'foto bukti',
        ];
    }
}
