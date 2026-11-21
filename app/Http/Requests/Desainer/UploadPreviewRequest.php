<?php

namespace App\Http\Requests\Desainer;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UploadPreviewRequest extends FormRequest
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
            'catatan_revisi' => ['required', 'string', 'max:1000'],
            'file_preview' => ['required', 'file', 'image', 'extensions:jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'catatan_revisi.required' => 'Catatan revisi wajib diisi.',
            'file_preview.required' => 'File preview ACC wajib diunggah.',
            'file_preview.image' => 'Preview ACC harus berupa gambar JPG atau PNG.',
            'file_preview.extensions' => 'Preview ACC harus berformat JPG atau PNG.',
            'file_preview.max' => 'Ukuran preview ACC maksimal 10 MB.',
        ];
    }
}
