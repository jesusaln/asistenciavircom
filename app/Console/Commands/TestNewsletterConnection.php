<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewsletterTestMail;

class TestNewsletterConnection extends Command
{
    protected $signature = 'newsletter:test {email}';
    protected $description = 'Prueba la conexión SMTP con Mailcow para el boletín';

    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Enviando correo de prueba a: {$email}...");

        try {
            Mail::mailer('newsletter')->to($email)->send(new NewsletterTestMail());
            $this->info("✅ ¡Éxito! El correo ha sido enviado desde el nuevo remitente 'blog'.");
        } catch (\Exception $e) {
            $this->error("❌ Error al enviar el correo: " . $e->getMessage());
        }
    }
}
