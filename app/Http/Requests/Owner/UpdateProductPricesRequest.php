<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductPricesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'integer', 'exists:products,id'],
            'products.*.harga_umum' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'products.*.harga_reseller' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'products.*.harga_prioritas' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'products.*.harga_pegawai' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'products.*.harga_modal' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }
}
