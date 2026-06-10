<?php

namespace App\Services;

use App\Models\Producto;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MetaSocialService
{
    private Client $http;
    private string $graphVersion;
    private ?string $pageId;
    private ?string $pageAccessToken;
    private ?string $instagramUserId;

    public function __construct()
    {
        $this->http = new Client([
            'timeout' => 30,
            'http_errors' => false,
        ]);
        $this->graphVersion = config('social.meta.graph_version', 'v21.0');
        $this->pageId = config('social.meta.page_id');
        $this->pageAccessToken = config('social.meta.page_access_token');
        $this->instagramUserId = config('social.meta.instagram_user_id');
    }

    public function ready(): bool
    {
        return !empty($this->pageId) && !empty($this->pageAccessToken);
    }

    public function readyForInstagram(): bool
    {
        return $this->ready() && !empty($this->instagramUserId);
    }

    public function postToFacebook(Producto $producto, string $mensaje, ?string $imageUrl = null): array
    {
        if (!$this->ready()) {
            return $this->error('Meta no configurado. Faltan page_id o page_access_token.');
        }

        $precio = $producto->precio_con_iva > 0
            ? '$' . number_format($producto->precio_con_iva, 0)
            : 'Consultar precio';

        $caption = $mensaje . "\n\n{$producto->nombre}\n💰 {$precio}\n📞 Pide tu cotización por WhatsApp";

        $url = "https://graph.facebook.com/{$this->graphVersion}/{$this->pageId}/photos";

        try {
            $data = [
                'message' => $caption,
                'access_token' => $this->pageAccessToken,
            ];

            if ($imageUrl) {
                $data['url'] = $imageUrl;
            } elseif ($producto->imagen) {
                $data['url'] = url('storage/' . $producto->imagen);
            } else {
                return $this->error('El producto no tiene imagen');
            }

            $response = $this->http->post($url, ['form_params' => $data]);
            $body = json_decode($response->getBody(), true);

            if (isset($body['id'])) {
                Log::info("Publicado a Facebook: {$body['id']}");
                return ['success' => true, 'post_id' => $body['id']];
            }

            Log::error("Error Meta: " . json_encode($body));
            return $this->error($body['error']['message'] ?? 'Error desconocido de Meta');
        } catch (\Throwable $e) {
            Log::error("MetaSocialService::postToFacebook: " . $e->getMessage());
            return $this->error($e->getMessage());
        }
    }

    public function postToInstagram(Producto $producto, string $mensaje, ?string $imageUrl = null): array
    {
        if (!$this->readyForInstagram()) {
            return $this->error('Instagram no configurado');
        }

        if (!$imageUrl && !$producto->imagen) {
            return $this->error('El producto no tiene imagen');
        }

        $imageUrl = $imageUrl ?: url('storage/' . $producto->imagen);
        $precio = $producto->precio_con_iva > 0
            ? '$' . number_format($producto->precio_con_iva, 0)
            : 'Consultar precio';

        $caption = $mensaje . "\n\n{$producto->nombre}\n💰 {$precio}\n📞 Cotiza por WhatsApp";

        try {
            $base = "https://graph.facebook.com/{$this->graphVersion}/{$this->instagramUserId}";

            // Step 1: Create media container
            $create = $this->http->post("{$base}/media", ['form_params' => [
                'image_url' => $imageUrl,
                'caption' => $caption,
                'access_token' => $this->pageAccessToken,
            ]]);
            $createBody = json_decode($create->getBody(), true);

            if (!isset($createBody['id'])) {
                Log::error("Error Meta Instagram create: " . json_encode($createBody));
                return $this->error($createBody['error']['message'] ?? 'Error al crear media');
            }

            $containerId = $createBody['id'];

            // Step 2: Publish the container
            $publish = $this->http->post("{$base}/media_publish", ['form_params' => [
                'creation_id' => $containerId,
                'access_token' => $this->pageAccessToken,
            ]]);
            $publishBody = json_decode($publish->getBody(), true);

            if (isset($publishBody['id'])) {
                Log::info("Publicado a Instagram: {$publishBody['id']}");
                return ['success' => true, 'post_id' => $publishBody['id']];
            }

            Log::error("Error Meta Instagram publish: " . json_encode($publishBody));
            return $this->error($publishBody['error']['message'] ?? 'Error al publicar');
        } catch (\Throwable $e) {
            Log::error("MetaSocialService::postToInstagram: " . $e->getMessage());
            return $this->error($e->getMessage());
        }
    }

    public function deletePost(string $postId, string $plataforma = 'facebook'): array
    {
        if (!$this->ready()) {
            return $this->error('Meta no configurado');
        }

        try {
            $url = "https://graph.facebook.com/{$this->graphVersion}/{$postId}";
            $this->http->delete($url, ['query' => ['access_token' => $this->pageAccessToken]]);
            return ['success' => true];
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    private function error(string $msg): array
    {
        return ['success' => false, 'error' => $msg];
    }
}
