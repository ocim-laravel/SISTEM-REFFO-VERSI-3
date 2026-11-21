<?php

namespace App\Http\Requests\Desainer;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class FinalizeDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_DESAINER;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file_cetak_tiff' => ['required', 'file', 'extensions:tif,tiff', 'max:51200'],
            'file_master_arsip' => ['required', 'file', 'extensions:cdr,psd,ai,zip', 'max:102400'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file_cetak_tiff.required' => 'File siap cetak berformat .tif/.tiff wajib diunggah.',
            'file_cetak_tiff.extensions' => 'File siap cetak harus berformat .tif atau .tiff.',
            'file_cetak_tiff.max' => 'Ukuran file siap cetak maksimal 50 MB.',
            'file_master_arsip.required' => 'File master arsip berformat .cdr/.psd/.ai/.zip wajib diunggah.',
            'file_master_arsip.extensions' => 'File master arsip harus berformat .cdr, .psd, .ai, atau .zip.',
            'file_master_arsip.max' => 'Ukuran file master arsip maksimal 100 MB.',
        ];
    }
}
