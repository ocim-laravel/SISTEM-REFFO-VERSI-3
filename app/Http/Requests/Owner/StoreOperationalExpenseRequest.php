<?php

namespace App\Http\Requests\Owner;

use App\Models\OperationalExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperationalExpenseRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'kategori_biaya' => ['required', Rule::in(OperationalExpense::KATEGORI_BIAYA)],
            'keterangan' => ['required', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kategori_biaya.in' => 'Kategori biaya tidak dikenal.',
            'nominal.min' => 'Nominal pengeluaran harus lebih dari nol.',
        ];
    }
}
