<?php

namespace App\Http\Requests\Desainer;

use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
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
            // TIFF selalu per item; master arsip cukup sekali per nota.
            'file_cetak_tiff' => ['required', 'file', 'extensions:tif,tiff', 'max:51200'],
            'file_master_arsip' => ['nullable', 'file', 'extensions:cdr,psd,ai,pdf,zip', 'max:102400'],
            'tanda_tangan' => ['nullable', 'string', 'max:255'],
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
            'file_master_arsip.extensions' => 'File master arsip harus berformat .cdr, .ai, .pdf, .psd, atau .zip.',
            'file_master_arsip.max' => 'Ukuran file master arsip maksimal 100 MB.',
        ];
    }

    /**
     * ACC Admin menjadi syarat unggah TIFF, dan tidak boleh ada revisi pending.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $item = $this->route('orderItem');

            if (! $item instanceof OrderItem) {
                return;
            }

            if (! $item->bolehFinalisasi()) {
                $validator->errors()->add(
                    'file_cetak_tiff',
                    'Preview belum disetujui Admin atau masih ada revisi pending.',
                );
            }
        });
    }
}
