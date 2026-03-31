<?php

namespace App\Listeners;

use App\Events\Auth\AuditableAuthEvent;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AuditAuthListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'audit';

    public int $tries = 5;

    public array $backoff = [5, 15, 30, 60];

    public function handle(AuditableAuthEvent $event): void
    {
        $user = $event->getUser();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => $event->getAction(),
            'model_type' => $user::class,
            'model_id' => $user->id,
            'old_values' => $event->getOldValues(),
            'new_values' => $event->getNewValues(),
        ]);
    }

    public function failed(AuditableAuthEvent $event, \Throwable $exception): void
    {
        Log::error('AuditAuthListener failed', [
            'event' => $event::class,
            'user_id' => $event->getUser()->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
