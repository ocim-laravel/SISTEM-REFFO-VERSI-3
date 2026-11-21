<?php

namespace App\Http\Requests\Operator;

use App\Models\MaterialOffcut;
use App\Models\MaterialRoll;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelesaiCetakRequest extends FormRequest
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
        $item = $this->route('orderItem');

        if (! $item instanceof OrderItem || ! $item->isMeteran()) {
            // Produk satuan tidak memotong roll/offcut; hanya perlu konfirmasi.
            return [];
        }

        return [
            'sumber_bahan' => ['required', Rule::in(['roll', 'offcut'])],
            'material_roll_id' => [
                'required_if:sumber_bahan,roll',
                'nullable',
                'integer',
                Rule::exists('material_rolls', 'id')->where('status', MaterialRoll::STATUS_AKTIF),
            ],
            'material_offcut_id' => [
                'required_if:sumber_bahan,offcut',
                'nullable',
                'integer',
                Rule::exists('material_offcuts', 'id')->where('status', MaterialOffcut::STATUS_TERSEDIA),
            ],
            'orientasi_potong' => ['nullable', Rule::in(['panjang', 'lebar'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sumber_bahan.required' => 'Sumber bahan wajib dipilih.',
            'material_roll_id.required_if' => 'Roll utama wajib dipilih.',
            'material_roll_id.exists' => 'Roll tidak ditemukan atau sudah tidak aktif.',
            'material_offcut_id.required_if' => 'Stok bahan potongan wajib dipilih.',
            'material_offcut_id.exists' => 'Stok potongan tidak ditemukan atau sudah terpakai.',
        ];
    }
}
