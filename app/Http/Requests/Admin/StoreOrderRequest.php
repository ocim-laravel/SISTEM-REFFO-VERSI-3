<?php

namespace App\Http\Requests\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'instansi' => ['nullable', 'string', 'max:255'],
            'nama_pelanggan' => ['required', 'string', 'max:255'],
            'no_wa' => ['required', 'string', 'max:30'],
            'kategori' => ['required', Rule::in(Customer::KATEGORIS)],
            'alamat' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.lebar_m' => ['nullable', 'numeric', 'gt:0', 'max:9999'],
            'items.*.tinggi_m' => ['nullable', 'numeric', 'gt:0', 'max:9999'],
            'items.*.finishing_id' => ['nullable', 'integer', 'exists:finishings,id'],

            'status_desain' => ['required', Rule::in(Order::STATUS_DESAIN_LIST)],
            'catatan_desain' => ['nullable', 'string', 'max:5000'],
            'lampiran_briefing' => ['nullable', 'file', 'max:20480', 'mimes:jpg,jpeg,png,webp,pdf,tif,tiff,cdr,psd,ai,zip,rar'],
            'deadline' => ['nullable', 'date'],

            'jumlah_dp' => ['nullable', 'numeric', 'min:0'],
            'status_bayar' => ['required', Rule::in(Order::STATUS_BAYAR_LIST)],

            'spk_enabled' => ['nullable', 'boolean'],
            'spk_jenis_pekerjaan' => ['required_if:spk_enabled,1', 'nullable', 'string', 'max:255'],
            'spk_ukuran_konstruksi' => ['required_if:spk_enabled,1', 'nullable', 'string', 'max:255'],
            'spk_lokasi_pasang' => ['required_if:spk_enabled,1', 'nullable', 'string', 'max:1000'],
            'spk_catatan_teknis' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_pelanggan.required' => 'Nama pelanggan wajib diisi.',
            'no_wa.required' => 'Nomor WhatsApp pelanggan wajib diisi.',
            'kategori.required' => 'Kategori pelanggan wajib dipilih.',
            'items.required' => 'Minimal satu item harus ditambahkan ke nota.',
            'items.*.product_id.required' => 'Produk pada setiap baris wajib dipilih.',
            'items.*.qty.required' => 'Qty pada setiap baris wajib diisi.',
            'items.*.lebar_m.gt' => 'Lebar harus lebih besar dari 0.',
            'items.*.tinggi_m.gt' => 'Tinggi harus lebih besar dari 0.',
            'status_desain.required' => 'Kondisi desain wajib dipilih.',
            'status_bayar.required' => 'Status bayar wajib dipilih.',
            'lampiran_briefing.mimes' => 'Lampiran briefing harus berupa gambar, PDF, TIFF, CDR, PSD, AI, ZIP, atau RAR.',
            'lampiran_briefing.max' => 'Ukuran lampiran briefing maksimal 20 MB.',
            'spk_jenis_pekerjaan.required_if' => 'Jenis pekerjaan SPK wajib diisi.',
            'spk_ukuran_konstruksi.required_if' => 'Ukuran konstruksi SPK wajib diisi.',
            'spk_lokasi_pasang.required_if' => 'Lokasi pasang SPK wajib diisi.',
        ];
    }

    /**
     * Pastikan produk meteran memiliki ukuran dan produk satuan tanpa finishing.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items', []);

            if (! is_array($items)) {
                return;
            }

            $products = Product::query()
                ->whereIn('id', collect($items)->pluck('product_id')->filter())
                ->get()
                ->keyBy('id');

            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $product = $products->get((int) ($item['product_id'] ?? 0));

                if (! $product) {
                    continue;
                }

                // Produk satuan tidak boleh membawa finishing_id, meskipun
                // request dikirim secara manual dari luar form Admin.
                if ($product->kategori_produk === Product::KATEGORI_SATUAN && filled($item['finishing_id'] ?? null)) {
                    $validator->errors()->add(
                        "items.{$index}.finishing_id",
                        'Produk satuan tidak memakai finishing.',
                    );

                    continue;
                }

                if ($product->kategori_produk !== Product::KATEGORI_METERAN) {
                    continue;
                }

                foreach (['lebar_m', 'tinggi_m'] as $field) {
                    if (! isset($item[$field]) || ! is_numeric($item[$field]) || (float) $item[$field] <= 0) {
                        $validator->errors()->add(
                            "items.{$index}.{$field}",
                            'Lebar dan tinggi wajib diisi untuk produk meteran.',
                        );
                    }
                }
            }
        });
    }
}
