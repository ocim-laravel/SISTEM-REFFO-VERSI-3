<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, string>  $targetRoles
     */
    public function __construct(
        public readonly Order $order,
        public readonly ?OrderItem $orderItem,
        public readonly string $statusProduksi,
        public readonly array $targetRoles,
        public readonly string $message,
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
        return 'order.status.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'order_status_changed',
            'order_id' => $this->order->id,
            'no_nota' => $this->order->no_nota,
            'order_item_id' => $this->orderItem?->id,
            'status_produksi' => $this->statusProduksi,
            'status_label' => OrderItem::LABEL_STATUS_PRODUKSI[$this->statusProduksi] ?? $this->statusProduksi,
            'message' => $this->message,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
