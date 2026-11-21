<?php

namespace App\Http\Controllers\Advertising;

use App\Http\Controllers\Controller;
use App\Http\Requests\Advertising\UpdateStatusSpkRequest;
use App\Models\AdvertisingSpk;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SpkController extends Controller
{
    public function dashboard(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $validStatus = in_array($status, AdvertisingSpk::STATUS_LAPANGAN_LIST, true) ? $status : '';

        $spks = AdvertisingSpk::query()
            // Hanya field SPK dan identitas pelanggan yang dimuat.
            // Informasi harga/tagihan sengaja tidak disertakan.
            ->with([
                'order' => fn ($query) => $query->select('id', 'no_nota', 'customer_id'),
                'order.customer' => fn ($query) => $query->select('id', 'instansi', 'nama_pelanggan'),
            ])
            ->when($validStatus !== '', fn ($query) => $query->where('status_lapangan', $validStatus))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('dashboards.advertising', [
            'spks' => $spks,
            'status' => $validStatus,
            'statusOptions' => AdvertisingSpk::LABEL_STATUS_LAPANGAN,
            'nextStatus' => [
                AdvertisingSpk::STATUS_ANTREAN => AdvertisingSpk::STATUS_PERAKITAN,
                AdvertisingSpk::STATUS_PERAKITAN => AdvertisingSpk::STATUS_PEMASANGAN,
                AdvertisingSpk::STATUS_PEMASANGAN => AdvertisingSpk::STATUS_SELESAI,
            ],
            'mediaFolders' => MediaStorage::foldersStatus(),
            'mediaReady' => MediaStorage::isReady(),
        ]);
    }

    public function updateStatus(AdvertisingSpk $spk, UpdateStatusSpkRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($spk, $request): void {
            $spk->update([
                'status_lapangan' => $request->validated()['status_lapangan'],
            ]);
        });

        return back()->with('status', 'Progres lapangan SPK berhasil diperbarui.');
    }
}
