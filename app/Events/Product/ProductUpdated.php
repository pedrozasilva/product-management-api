<?php

namespace App\Events\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductUpdated implements AuditableProductEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly User $user,
        public readonly array $oldValues,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getAction(): string
    {
        return 'product_updated';
    }

    public function getOldValues(): ?array
    {
        return $this->oldValues;
    }

    public function getNewValues(): ?array
    {
        return [
            'name' => $this->product->name,
            'description' => $this->product->description,
            'price' => $this->product->price,
            'stock_quantity' => $this->product->stock_quantity,
            'is_active' => $this->product->is_active,
            'category_id' => $this->product->category_id,
        ];
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }
}
