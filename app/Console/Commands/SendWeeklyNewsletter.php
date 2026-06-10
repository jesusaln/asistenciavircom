<?php

namespace App\Console\Commands;

use App\Jobs\SendIndividualNewsletter;
use App\Models\BlogPost;
use App\Models\Cliente;
use Illuminate\Console\Command;

class SendWeeklyNewsletter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newsletter:send {post_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía el boletín semanal a todos los clientes suscritos con control de flujo (Throttling)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $postId = $this->argument('post_id');

        // Si no se provee ID, tomar el último post publicado
        $post = $postId
            ? BlogPost::findOrFail($postId)
            : BlogPost::published()->latest('publicado_at')->first();

        if (!$post) {
            $this->error("No se encontró ningún artículo publicado para enviar.");
            return;
        }

        $clientes = Cliente::where('recibe_newsletter', true)
            ->whereNotNull('email')
            ->get();

        $total = $clientes->count();
        $this->info("Iniciando envío de newsletter: '{$post->titulo}' para {$total} clientes.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // LÓGICA DE SEGURIDAD (THROTTLING): 
        // Hostinger limita 10 correos por minuto.
        // Vamos a espaciar el despacho de los jobs.
        $delaySeconds = 0;
        $emailsPerMinute = 8; // Dejamos margen de seguridad

        foreach ($clientes as $index => $cliente) {
            // Calculamos el delay: cada correo se programa con una diferencia de X segundos
            // 60 seg / 8 correos = un correo cada 7.5 segundos
            $delaySeconds = ($index / $emailsPerMinute) * 60;

            SendIndividualNewsletter::dispatch($post, $cliente)
                ->delay(now()->addSeconds($delaySeconds));

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("🚀 ¡Todos los correos han sido programados en la cola!");
        $this->info("Tiempo estimado de finalización: " . round($delaySeconds / 60) . " minutos.");
    }
}
