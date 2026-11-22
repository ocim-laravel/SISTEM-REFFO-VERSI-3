<?php

namespace App\Http\Requests\Desainer;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Master desain cukup satu per nota dan hanya dibuka setelah seluruh TIFF per item terupload.
 */
class UploadMasterDesignRequest extends FormRequest
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
            'file_master_arsip.extensions' => 'File master arsip harus berformat .cdr, .ai, .pdf, .psd, atau .zip.',
            'file_master_arsip.max' => 'Ukuran file master arsip maksimal 100 MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $order = $this->route('order');

            if (! $order instanceof Order) {
                return;
            }

            if (! filled($order->file_master_arsip) && ! $this->hasFile('file_master_arsip')) {
                $validator->errors()->add(
                    'file_master_arsip',
                    'File master arsip berformat .cdr/.ai/.pdf/.psd/.zip wajib diunggah satu kali untuk nota ini.',
                );
            }
        });
    }
}
