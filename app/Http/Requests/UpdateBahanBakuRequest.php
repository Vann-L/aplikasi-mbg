<?php

namespace App\Http\Requests;

use App\Models\BahanBaku;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBahanBakuRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:50'],
            'stok_minimum' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in([BahanBaku::STATUS_ACTIVE, BahanBaku::STATUS_INACTIVE])],
        ];
    }
}
