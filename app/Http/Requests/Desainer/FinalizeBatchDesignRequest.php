<?php

namespace App\Http\Requests\Desainer;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Finalisasi satu nota sekaligus: TIFF per item dan satu master arsip per nota.
 */
class FinalizeBatchDesignRequest extends FormRequest
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
            // Kunci items adalah id item agar urutan mengikuti daftar item.
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['array'],
            'items.*.file_cetak_tiff' => ['required', 'file', 'extensions:tif,tiff', 'max:51200'],
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
            'items.required' => 'Minimal satu item perlu diunggah file cetaknya.',
            'items.min' => 'Minimal satu item perlu diunggah file cetaknya.',
            'items.*.file_cetak_tiff.required' => 'File siap cetak berformat .tif/.tiff wajib diunggah per item.',
            'items.*.file_cetak_tiff.extensions' => 'File siap cetak harus berformat .tif atau .tiff.',
            'items.*.file_cetak_tiff.max' => 'Ukuran file siap cetak maksimal 50 MB.',
            'file_master_arsip.extensions' => 'File master arsip harus berformat .cdr, .ai, .pdf, .psd, atau .zip.',
            'file_master_arsip.max' => 'Ukuran file master arsip maksimal 100 MB.',
        ];
    }

    /**
     * ACC Admin menjadi syarat setiap TIFF; master arsip dibuka terpisah setelah semua TIFF ada.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $order = $this->route('order');

            if (! $order instanceof Order) {
                return;
            }

            $ids = array_map('intval', array_keys((array) $this->input('items', [])));

            if ($ids === []) {
                return;
            }

            $items = OrderItem::query()
                ->with('designRevisions')
                ->where('order_id', $order->id)
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            foreach ($ids as $id) {
                $item = $items->get($id);

                if (! $item) {
                    $validator->errors()->add(
                        "items.{$id}.file_cetak_tiff",
                        'Item tidak ditemukan pada nota ini.',
                    );

                    continue;
                }

                if (! $item->bolehFinalisasi()) {
                    $validator->errors()->add(
                        "items.{$id}.file_cetak_tiff",
                        'Preview belum disetujui Admin atau masih ada revisi pending.',
                    );
                }
            }

        });
    }
}
