<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StorePelunasanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_ADMIN;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bukti_pelunasan' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'catatan_pengecualian' => ['required_without:bukti_pelunasan', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bukti_pelunasan.file' => 'Bukti pelunasan harus berupa berkas.',
            'bukti_pelunasan.mimes' => 'Bukti pelunasan harus berformat JPG, JPEG, PNG, atau PDF.',
            'bukti_pelunasan.max' => 'Ukuran bukti pelunasan maksimal 5 MB.',
            'catatan_pengecualian.required_without' => 'Catatan pengecualian wajib diisi jika bukti tidak diunggah.',
            'catatan_pengecualian.string' => 'Catatan pengecualian harus berupa teks.',
            'catatan_pengecualian.max' => 'Catatan pengecualian maksimal 2000 karakter.',
        ];
    }
}
