<?php

namespace App\Http\Requests\Operator;

use App\Models\MaterialRoll;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LaporGagalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_OPERATOR_CETAK;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'material_roll_id' => [
                'required',
                'integer',
                Rule::exists('material_rolls', 'id')->where('status', MaterialRoll::STATUS_AKTIF),
            ],
            'panjang_rusak_m' => ['required', 'numeric', 'gt:0', 'max:9999'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'material_roll_id.required' => 'Roll aktif wajib dipilih.',
            'material_roll_id.exists' => 'Roll tidak ditemukan atau sudah tidak aktif.',
            'panjang_rusak_m.required' => 'Panjang bahan rusak wajib diisi.',
            'panjang_rusak_m.gt' => 'Panjang bahan rusak harus lebih besar dari 0.',
        ];
    }
}
