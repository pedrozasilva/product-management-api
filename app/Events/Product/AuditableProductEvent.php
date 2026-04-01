<?php

namespace App\Events\Product;

use App\Models\Product;
use App\Models\User;

interface AuditableProductEvent
{
    public function getUser(): User;

    public function getProduct(): Product;

    public function getAction(): string;

    public function getOldValues(): ?array;

    public function getNewValues(): ?array;

    public function getIpAddress(): ?string;

    public function getUserAgent(): ?string;
}
