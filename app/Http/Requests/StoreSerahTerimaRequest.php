<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreSerahTerimaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(
            User::ROLE_ADMIN,
            User::ROLE_DESAINER,
            User::ROLE_OPERATOR_CETAK,
            User::ROLE_FINISHING,
            User::ROLE_ADVERTISING,
        ) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'foto_serah_terima' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'nama_pengambil' => ['required', 'string', 'max:255'],
            'no_wa_pengambil' => ['required', 'string', 'max:30'],
            'catatan_pengecualian' => ['required_without:foto_serah_terima', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'foto_serah_terima.mimes' => 'Foto barang harus berformat JPG, JPEG, atau PNG.',
            'foto_serah_terima.max' => 'Ukuran foto barang maksimal 5 MB.',
            'nama_pengambil.required' => 'Atas nama pengambil wajib diisi.',
            'no_wa_pengambil.required' => 'Nomor WhatsApp pengambil wajib diisi.',
            'catatan_pengecualian.required_without' => 'Catatan pengecualian wajib diisi jika foto tidak diunggah.',
            'catatan_pengecualian.string' => 'Catatan pengecualian harus berupa teks.',
            'catatan_pengecualian.max' => 'Catatan pengecualian maksimal 2000 karakter.',
        ];
    }
}
