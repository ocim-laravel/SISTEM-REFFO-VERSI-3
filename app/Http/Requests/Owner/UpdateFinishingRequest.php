<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinishingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'finishings' => ['required', 'array', 'min:1'],
            'finishings.*.id' => ['required', 'integer', 'exists:finishings,id'],
            'finishings.*.ekstra_kiri_cm' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'finishings.*.ekstra_kanan_cm' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'finishings.*.ekstra_atas_cm' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'finishings.*.ekstra_bawah_cm' => ['required', 'numeric', 'min:0', 'max:999.99'],
        ];
    }
}
