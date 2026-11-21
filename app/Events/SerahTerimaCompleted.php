<?php

namespace App\Events;

use App\Models\Order;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SerahTerimaCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, string>  $targetRoles
     */
    public function __construct(
        public readonly Order $order,
        public readonly array $targetRoles = [
            User::ROLE_ADMIN,
            User::ROLE_DESAINER,
            User::ROLE_OPERATOR_CETAK,
            User::ROLE_FINISHING,
            User::ROLE_ADVERTISING,
            User::ROLE_OWNER,
        ],
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return collect($this->targetRoles)
            ->filter(fn (string $role): bool => in_array($role, User::ROLES, true))
            ->unique()
            ->map(fn (string $role): PrivateChannel => new PrivateChannel('notifications.'.$role))
            ->values()
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'serah-terima.completed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->order->loadMissing('customer');

        return [
            'type' => 'serah_terima_completed',
            'order_id' => $this->order->id,
            'no_nota' => $this->order->no_nota,
            'customer' => $this->order->customer?->nama_pelanggan,
            'nama_pengambil' => $this->order->nama_pengambil,
            'message' => "Barang nota {$this->order->no_nota} telah diserahkan.",
            'created_at' => now()->toIso8601String(),
        ];
    }
}
