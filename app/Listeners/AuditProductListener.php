<?php

namespace App\Listeners;

use App\Events\Product\AuditableProductEvent;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AuditProductListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'audit';

    public int $tries = 5;

    public array $backoff = [5, 15, 30, 60];

    public function handle(AuditableProductEvent $event): void
    {
        $product = $event->getProduct();

        AuditLog::create([
            'user_id' => $event->getUser()->id,
            'action' => $event->getAction(),
            'model_type' => $product::class,
            'model_id' => $product->id,
            'old_values' => $event->getOldValues(),
            'new_values' => $event->getNewValues(),
            'ip_address' => $event->getIpAddress(),
            'user_agent' => $event->getUserAgent(),
        ]);
    }

    public function failed(AuditableProductEvent $event, \Throwable $exception): void
    {
        Log::error('AuditProductListener failed', [
            'event' => $event::class,
            'product_id' => $event->getProduct()->id,
            'user_id' => $event->getUser()->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
