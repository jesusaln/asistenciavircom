<?php

namespace App\Jobs;

use App\Mail\WeeklyNewsletter;
use App\Models\BlogPost;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendIndividualNewsletter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;
    protected $cliente;

    /**
     * Create a new job instance.
     */
    public function __construct(BlogPost $post, Cliente $cliente)
    {
        $this->post = $post;
        $this->cliente = $cliente;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Doble verificación: que el cliente quiera recibir el correo
        if (!$this->cliente->recibe_newsletter || !$this->cliente->email) {
            return;
        }

        try {
            Mail::mailer('newsletter')->to($this->cliente->email)->send(new WeeklyNewsletter($this->post, $this->cliente));

            Log::info("Newsletter enviado exitosamente a: {$this->cliente->email}");
        } catch (\Exception $e) {
            Log::error("Error enviando newsletter a {$this->cliente->email}: " . $e->getMessage());

            // Si falla, podrías reintentar después pero cuidado con los límites del VPS
            $this->release(300); // Reintentar en 5 minutos
        }
    }
}
