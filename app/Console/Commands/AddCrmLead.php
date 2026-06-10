<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CrmProspecto;
use App\Models\Empresa;

class AddCrmLead extends Command
{
    protected $signature = 'crm:add-lead 
                            {nombre : Nombre del cliente} 
                            {telefono : Teléfono del cliente} 
                            {interes : Producto en el que está interesado}';

    protected $description = 'Registrar un nuevo lead desde WhatsApp Bot';

    public function handle()
    {
        $nombre = $this->argument('nombre');
        $telefono = $this->argument('telefono');
        $interes = $this->argument('interes');

        // Buscar empresa por default (ID 1)
        $empresaId = Empresa::first()->id ?? 1;

        $prospecto = CrmProspecto::create([
            'empresa_id' => $empresaId,
            'nombre' => $nombre,
            'telefono' => $telefono,
            'origen' => 'whatsapp', // O 'web' si prefieres
            'etapa' => 'interesado',
            'notas' => "Lead capturado por Bot: " . $interes,
            'prioridad' => 'alta',
            'created_by' => 1, // Asignar al admin principal
        ]);

        $this->info("Lead creado con ID: {$prospecto->id}");
        return 0;
    }
}
