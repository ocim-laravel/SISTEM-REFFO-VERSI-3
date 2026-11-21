<?php

namespace App\Http\Requests\Advertising;

use App\Models\AdvertisingSpk;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusSpkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_ADVERTISING;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_lapangan' => ['required', Rule::in(AdvertisingSpk::STATUS_LAPANGAN_LIST)],
        ];
    }

    /**
     * Progres lapangan hanya boleh maju satu tahap:
     * antrean -> perakitan -> pemasangan -> selesai.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $spk = $this->route('spk');
            $status = $this->input('status_lapangan');

            if (! $spk instanceof AdvertisingSpk || ! is_string($status)) {
                return;
            }

            $urutan = array_search($spk->status_lapangan, AdvertisingSpk::STATUS_LAPANGAN_LIST, true);
            $berikutnya = AdvertisingSpk::STATUS_LAPANGAN_LIST[(int) $urutan + 1] ?? null;

            if ($status !== $berikutnya) {
                $validator->errors()->add(
                    'status_lapangan',
                    'Progres hanya dapat dilanjutkan ke tahap berikutnya.',
                );
            }
        });
    }
}
