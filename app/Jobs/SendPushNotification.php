<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPushNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $fcmToken,
        protected string $title,
        protected string $body,
        protected array $data = []
    ) {}

    public function handle(\App\Services\PushNotificationService $service): void
    {
        $service->sendNotification(
            $this->fcmToken,
            $this->title,
            $this->body,
            $this->data
        );
    }
}
