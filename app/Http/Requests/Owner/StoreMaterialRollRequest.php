<?php

namespace App\Http\Requests\Owner;

use App\Models\MaterialRoll;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaterialRollRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kode_roll' => ['required', 'string', 'max:50', 'unique:material_rolls,kode_roll'],
            'jenis_bahan' => ['required', Rule::in(MaterialRoll::JENIS_BAHAN)],
        ];
    }
}
