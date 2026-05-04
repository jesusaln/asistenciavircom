<?php

namespace App\Listeners;

use App\Events\WhatsAppMessageReceived;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\EmpresaResolver;

class NotifyUsersOfWhatsAppInboxMessage
{
    public function handle(WhatsAppMessageReceived $event): void
    {
        $msg = $event->message;

        if ($msg->direction !== 'inbound' || $msg->is_internal) {
            return;
        }

        EmpresaResolver::setContext($msg->empresa_id);

        $users = User::query()
            ->where('empresa_id', $msg->empresa_id)
            ->where('activo', true)
            ->role(['admin', 'super-admin', 'ventas'])
            ->get(['id']);

        if ($users->isEmpty()) {
            return;
        }

        $preview = (string) ($msg->body ?? '');
        $preview = trim($preview);
        if ($preview === '') {
            $preview = 'Nuevo mensaje de WhatsApp';
        } elseif (mb_strlen($preview) > 160) {
            $preview = mb_substr($preview, 0, 157).'...';
        }

        $title = 'WhatsApp: '.($msg->from_name ?: $msg->wa_id);
        $waParam = rawurlencode((string) $msg->wa_id);
        $url = '/marketing/whatsapp-inbox?wa='.$waParam;

        foreach ($users as $user) {
            UserNotification::createForUser(
                $user->id,
                'whatsapp_inbox',
                $title,
                $preview,
                [
                    'wa_id' => $msg->wa_id,
                    'message_id' => $msg->message_id,
                    'whats_app_chat_id' => $msg->id,
                ],
                $url,
                'fab fa-whatsapp'
            );
        }
    }
}
