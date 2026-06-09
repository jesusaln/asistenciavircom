<?php

namespace App\OpenApi;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="CDD App ERP - API",
 *     description="API REST para el sistema de gestión empresarial CDD App ERP. Utiliza autenticación basada en sesión (cookies) para solicitudes desde el frontend, y Bearer tokens para solicitudes desde clientes externos.",
 *     @OA\Contact(
 *         email="soporte@asistenciavircom.com",
 *         name="Soporte Técnico"
 *     ),
 *     @OA\License(
 *         name="Propietario",
 *         url="https://asistenciavircom.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Ingresa el token de autenticación en formato: Bearer token"
 * )
 *
 * @OA\Tag(name="Autenticación", description="Endpoints para autenticación de usuarios")
 * @OA\Tag(name="Clientes", description="Gestión de clientes")
 * @OA\Tag(name="Productos", description="Gestión de productos e inventario")
 * @OA\Tag(name="Ventas", description="Gestión de ventas y facturación")
 * @OA\Tag(name="Compras", description="Gestión de compras")
 * @OA\Tag(name="CFDI", description="Facturación electrónica CFDI")
 * @OA\Tag(name="Cotizaciones", description="Gestión de cotizaciones")
 * @OA\Tag(name="Pedidos", description="Gestión de pedidos")
 * @OA\Tag(name="CuentasPorCobrar", description="Gestión de cuentas por cobrar")
 * @OA\Tag(name="CuentasPorPagar", description="Gestión de cuentas por pagar")
 * @OA\Tag(name="Configuración", description="Configuración de la empresa")
 * @OA\Tag(name="Usuarios", description="Gestión de usuarios y roles")
 * @OA\Tag(name="Reportes", description="Generación de reportes")
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operación exitosa"),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error en la operación")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=10),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=150)
 * )
 */
class OpenApiSpec
{
    /**
     * @OA\Get(
     *     path="/health",
     *     operationId="healthCheck",
     *     tags={"Sistema"},
     *     summary="Verificar estado del sistema",
     *     description="Endpoint para verificar que la API está funcionando correctamente",
     *     @OA\Response(
     *         response=200,
     *         description="Sistema funcionando correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="API funcionando correctamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="version", type="string", example="1.0.0"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function healthCheck()
    {
        // Documentación solamente - endpoint en routes/api.php
    }
}

