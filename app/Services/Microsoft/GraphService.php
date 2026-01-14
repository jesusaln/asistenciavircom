<?php

namespace App\Services\Microsoft;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Exception;
use Carbon\Carbon;

class GraphService
{
    protected $baseUrl = 'https://graph.microsoft.com/v1.0';

    public function __construct(protected User $user)
    {
    }

    public function ensureTokenIsValid()
    {
        if (!$this->user->microsoft_token) {
            throw new Exception('Usuario no conectado a Microsoft.');
        }

        if ($this->user->microsoft_token_expires_at && Carbon::now()->gte($this->user->microsoft_token_expires_at->subMinutes(5))) {
            $this->refreshToken();
        }
    }

    protected function refreshToken()
    {
        $response = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
            'client_id' => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->user->microsoft_refresh_token,
            'scope' => 'User.Read Tasks.ReadWrite Calendars.ReadWrite offline_access', // Add scope if needed or leave empty if relying on previous consent
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->user->microsoft_token = $data['access_token'];
            $this->user->microsoft_refresh_token = $data['refresh_token'] ?? $this->user->microsoft_refresh_token;
            $this->user->microsoft_token_expires_at = now()->addSeconds($data['expires_in']);
            $this->user->save();
        } else {
            // Si falla, quizás invalidar el token o notificar
            \Illuminate\Support\Facades\Log::error('Microsoft Token Refresh Failed: ' . $response->body());
            throw new Exception('Error al refrescar el token de Microsoft. Por favor, vuelve a conectar tu cuenta.');
        }
    }

    public function get($endpoint, $params = [])
    {
        $this->ensureTokenIsValid();
        return Http::withToken($this->user->microsoft_token)
            ->get($this->baseUrl . $endpoint, $params);
    }

    public function post($endpoint, $data = [])
    {
        $this->ensureTokenIsValid();
        return Http::withToken($this->user->microsoft_token)
            ->post($this->baseUrl . $endpoint, $data);
    }

    public function patch($endpoint, $data = [])
    {
        $this->ensureTokenIsValid();
        return Http::withToken($this->user->microsoft_token)
            ->patch($this->baseUrl . $endpoint, $data);
    }

    // To Do Methods

    public function getTaskLists()
    {
        return $this->get('/me/todo/lists');
    }

    public function createTaskList($name)
    {
        return $this->post('/me/todo/lists', ['displayName' => $name]);
    }

    public function getTasks($listId)
    {
        return $this->get("/me/todo/lists/{$listId}/tasks");
    }

    public function createTask($listId, $title, $content = null, $dueDateTime = null)
    {
        $data = [
            'title' => $title,
        ];

        if ($content) {
            $data['body'] = [
                'content' => $content,
                'contentType' => 'text',
            ];
        }

        if ($dueDateTime) {
            // Microsoft expects UTC
            $data['dueDateTime'] = [
                'dateTime' => Carbon::parse($dueDateTime)->setTimezone('UTC')->format('Y-m-d\TH:i:s'),
                'timeZone' => 'UTC',
            ];
        }

        return $this->post("/me/todo/lists/{$listId}/tasks", $data);
    }
}
