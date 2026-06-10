<?php

namespace App\Services;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use App\Models\Cita;
use App\Models\Cliente;

class MirageScraperService
{
    private $client;
    private $baseUrl;

    public function __construct()
    {
        $this->client = new Client([
            'cookies' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'es-ES,es;q=0.9',
            ]
        ]);
        $this->baseUrl = "https://postventa.mirage.mx";
    }

    private function login()
    {
        Log::info("MirageScraper: Iniciando proceso de login...");
        $user = "CSAM0708";
        $pass = "Mirage0708*";

        try {
            $response = $this->client->get($this->baseUrl . '/CS/Account/Login');
            $html = (string) $response->getBody();
            $crawler = new Crawler($html);
            $tokenInput = $crawler->filter('input[name="__RequestVerificationToken"]');
            $token = $tokenInput->count() > 0 ? $tokenInput->attr('value') : null;

            $postData = [
                'Identity.Usuario' => $user,
                'Identity.Password' => $pass,
                'RememberMe' => 'false'
            ];
            if ($token) $postData['__RequestVerificationToken'] = $token;

            $response = $this->client->post($this->baseUrl . '/CS/Account/Login', [
                'form_params' => $postData,
                'allow_redirects' => true
            ]);

            $body = (string) $response->getBody();
            if (str_contains($body, 'name="Identity.Usuario"')) return false;

            Log::info("MirageScraper: Login exitoso.");
            return true;
        } catch (\Exception $e) {
            Log::error("MirageScraper: Excepción en login: " . $e->getMessage());
            return false;
        }
    }

    private function safeExtract(Crawler $crawler, $selector, $type = 'text')
    {
        try {
            $node = $crawler->filter($selector);
            if ($node->count() === 0) return 'N/A';
            
            if ($type === 'text') return trim($node->text());
            if ($type === 'attr') return trim($node->attr('alt') ?? $node->attr('title') ?? 'N/A');
            
            // Caso especial para la estructura DT/DD
            if ($type === 'dd') {
                $dt = $crawler->filter($selector)->closest('dt');
                if ($dt === null || $dt->count() === 0) return 'N/A';
                $dd = $dt->nextAll('dd');
                if ($dd === null || $dd->count() === 0) return 'N/A';
                return trim($dd->first()->text());
            }

            // Caso para estructura DIV col-md-6 (hermanos)
            if ($type === 'sibling-div') {
                $parent = $crawler->filter($selector)->closest('div');
                if ($parent === null || $parent->count() === 0) return 'N/A';
                $sibling = $parent->nextAll('div')->first();
                return $sibling->count() > 0 ? trim($sibling->text()) : 'N/A';
            }

            return 'N/A';
        } catch (\Throwable $e) {
            // Fallback para fecha si falla el selector normal
            if ($selector === 'label[for="Item_FechaSolicitud"]') {
                $hidden = $crawler->filter('input#HFFechaApertura');
                if ($hidden->count() > 0) return trim($hidden->attr('value'));
            }
            return 'N/A';
        }
    }

    public function scrapeAndImport()
    {
        try {
            if (!$this->login()) {
                return ['success' => false, 'message' => 'No se pudo iniciar sesión en Mirage.'];
            }

            Log::info("MirageScraper: Consultando lista parcial de solicitudes...");
            // Intentar cargar la página de contexto primero
            $this->client->get($this->baseUrl . "/CS/Solicitud/Solicitudes/453");
            
            $response = $this->client->get($this->baseUrl . "/CS/CentroServicio/SolicitudesParcial?pagina=1");
            $html = (string) $response->getBody();
            
            $crawler = new Crawler($html);
            $solicitudes = [];
            $rows = $crawler->filter('tr');
            Log::info("MirageScraper: Filas encontradas: " . $rows->count());

            $rows->each(function (Crawler $node, $i) use (&$solicitudes) {
                try {
                    $text = trim($node->text());
                    if (!preg_match('/(MS-(\d+))/', $text, $matches)) return;

                    $folio = $matches[1];
                    $mirageId = $matches[2];
                    
                    // Solo si es "Asignar"
                    if (stripos($text, 'Asignar') === false) return;
                    $status = 'Asignar';

                    // Extraer tipo y fecha buscando en todas las celdas de la fila
                    $tipoServicio = 'Servicio';
                    $fechaSolicitud = 'N/A';
                    
                    $node->filter('td')->each(function(Crawler $td) use (&$tipoServicio, &$fechaSolicitud) {
                        $tdText = trim($td->text());
                        if (preg_match('/(Instalación|Garantía|Mantenimiento|Reparación)/iu', $tdText, $m)) {
                            $tipoServicio = $m[1];
                        }
                        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $tdText, $m)) {
                            $fechaSolicitud = $m[1];
                        }
                    });

                    Log::info("MirageScraper: Procesando detalle para folio $folio | Tipo: $tipoServicio | Fecha: $fechaSolicitud");

                    $detailUrl = $this->baseUrl . "/CS/Solicitud/Solicitud/" . $mirageId;
                    
                    try {
                        $detailResponse = $this->client->get($detailUrl);
                        $detailHtml = (string) $detailResponse->getBody();
                    } catch (\Exception $e) {
                        Log::error("MirageScraper: Error al acceder al detalle de $folio. Saltando...");
                        return;
                    }

                    $detailCrawler = new Crawler($detailHtml);

                    $clienteNombre = $this->safeExtract($detailCrawler, 'label[for="Item_Cliente_NombreCompleto"]', 'dd');
                    $telefono = $this->safeExtract($detailCrawler, 'label[for="Item_Cliente_Telefono"]', 'dd');
                    $direccionCompleta = $this->safeExtract($detailCrawler, 'label[for="Item_Cliente_Domicilio_DomicilioCompleto"]', 'dd');

                    $fechaSolicitudText = $this->safeExtract($detailCrawler, 'label[for="Item_FechaSolicitud"]', 'sibling-div');
                    $responsable = $this->safeExtract($detailCrawler, 'label[for="Item_CentroServicio_Responsable"]', 'sibling-div');
                    $tecnico = $this->safeExtract($detailCrawler, 'label[for="Item_Tecnico_NombreCompleto"]', 'sibling-div');

                    $solicitudes[] = [
                        'mirage_id' => $mirageId,
                        'folio' => $folio,
                        'cliente_nombre' => $clienteNombre,
                        'telefono' => $telefono,
                        'direccion' => $direccionCompleta,
                        'tipo' => $tipoServicio,
                        'fecha' => $fechaSolicitudText !== 'N/A' ? $fechaSolicitudText : $fechaSolicitud,
                        'status' => $status
                    ];
                } catch (\Exception $e) {
                    Log::error("MirageScraper: Error en fila: " . $e->getMessage());
                }
            });

            return [
                'success' => true,
                'data' => $solicitudes,
                'message' => "Se encontraron " . count($solicitudes) . " solicitudes pendientes."
            ];

        } catch (\Exception $e) {
            Log::error('MirageScraper: Error crítico: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
