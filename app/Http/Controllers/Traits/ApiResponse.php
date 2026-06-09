<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait ApiResponse
 * 
 * Proporciona métodos estandarizados para respuestas de API.
 * Uso: use ApiResponse; en cualquier controller.
 */
trait ApiResponse
{
    /**
     * Respuesta exitosa estándar.
     *
     * @param mixed $data Datos a retornar
     * @param string $message Mensaje de éxito
     * @param int $code Código HTTP (default: 200)
     * @return JsonResponse
     */
    protected function success($data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Respuesta de error estándar.
     *
     * @param string $message Mensaje de error
     * @param int $code Código HTTP (default: 400)
     * @param mixed $errors Errores adicionales
     * @return JsonResponse
     */
    protected function error(string $message, int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Respuesta de recurso no encontrado.
     *
     * @param string $message Mensaje personalizado
     * @return JsonResponse
     */
    protected function notFound(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Respuesta de no autorizado.
     *
     * @param string $message Mensaje personalizado
     * @return JsonResponse
     */
    protected function unauthorized(string $message = 'No autorizado'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Respuesta de prohibido.
     *
     * @param string $message Mensaje personalizado
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'Acceso denegado'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Respuesta de error de validación.
     *
     * @param mixed $errors Array de errores de validación
     * @param string $message Mensaje general
     * @return JsonResponse
     */
    protected function validationError($errors, string $message = 'Error de validación'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Respuesta de error interno del servidor.
     *
     * @param string $message Mensaje de error
     * @param \Throwable|null $exception Excepción (solo se loggea en desarrollo)
     * @return JsonResponse
     */
    protected function serverError(string $message = 'Error interno del servidor', ?\Throwable $exception = null): JsonResponse
    {
        if ($exception && config('app.debug')) {
            return $this->error($message, 500, [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }

        return $this->error($message, 500);
    }

    /**
     * Respuesta de recurso creado.
     *
     * @param mixed $data Datos del recurso creado
     * @param string $message Mensaje de éxito
     * @return JsonResponse
     */
    protected function created($data = null, string $message = 'Recurso creado exitosamente'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Respuesta sin contenido (para DELETE exitoso).
     *
     * @return JsonResponse
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Respuesta con paginación estándar.
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @param string $message Mensaje de éxito
     * @return JsonResponse
     */
    protected function paginated($paginator, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ], 200);
    }
}
