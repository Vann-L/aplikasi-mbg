<?php

namespace App\Http\Requests;

use App\Models\Menu;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProduksiRequest extends FormRequest
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
            'menu_id' => ['required', 'integer', Rule::exists('menus', 'id')->where('status', Menu::STATUS_ACTIVE)],
            'tanggal' => ['required', 'date', Rule::unique('produksi', 'menu_id')->where(fn ($query) => $query->where('tanggal', $this->input('tanggal')))],
        ];
    }
}
