<?php

namespace App\Events\Auth;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered implements AuditableAuthEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAction(): string
    {
        return 'register';
    }

    public function getOldValues(): ?array
    {
        return null;
    }

    public function getNewValues(): ?array
    {
        return [
            'name' => $this->user->name,
            'email' => $this->user->email,
        ];
    }
}
