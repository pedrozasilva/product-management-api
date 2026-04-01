<?php

namespace App\Events\Auth;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn implements AuditableAuthEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAction(): string
    {
        return 'login';
    }

    public function getOldValues(): ?array
    {
        return null;
    }

    public function getNewValues(): ?array
    {
        return [
            'email' => $this->user->email,
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
