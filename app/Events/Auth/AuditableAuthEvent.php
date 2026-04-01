<?php

namespace App\Events\Auth;

use App\Models\User;

interface AuditableAuthEvent
{
    public function getUser(): User;

    public function getAction(): string;

    public function getOldValues(): ?array;

    public function getNewValues(): ?array;

    public function getIpAddress(): ?string;

    public function getUserAgent(): ?string;
}
