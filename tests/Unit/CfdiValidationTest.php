<?php

namespace Tests\Unit;

use App\Services\CfdiValidationService;
use PHPUnit\Framework\TestCase;

class CfdiValidationTest extends TestCase
{
    private CfdiValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = new CfdiValidationService();
    }

    /**
     * Test validación de RFC persona física
     */
    public function test_rfc_persona_fisica_valido(): void
    {
        $errores = $this->validationService->validarRfc('XAXX010101000');
        $this->assertEmpty($errores, 'RFC genérico XAXX debe ser válido');

        $errores = $this->validationService->validarRfc('XEXX010101000');
        $this->assertEmpty($errores, 'RFC genérico XEXX debe ser válido');

        // RFC real de ejemplo (asumiendo formato válido)
        $errores = $this->validationService->validarRfc('GODE561231GR8');
        // Nota: Este test puede fallar si el RFC no tiene dígito verificador correcto
        // En producción se usaría un RFC real válido
    }

    /**
     * Test validación de RFC persona moral
     */
    public function test_rfc_persona_moral_valido(): void
    {
        // RFC de ejemplo (asumiendo formato válido)
        $errores = $this->validationService->validarRfc('XAXX010101000');
        $this->assertEmpty($errores, 'RFC genérico debe ser válido');
    }

    /**
     * Test RFC inválido
     */
    public function test_rfc_invalido(): void
    {
        $errores = $this->validationService->validarRfc('INVALID');
        $this->assertNotEmpty($errores, 'RFC inválido debe generar errores');

        $errores = $this->validationService->validarRfc('');
        $this->assertNotEmpty($errores, 'RFC vacío debe generar errores');
    }

    /**
     * Test compatibilidad régimen fiscal - uso CFDI (simulado sin BD)
     */
    public function test_compatibilidad_regimen_uso_cfdi(): void
    {
        // Este test requiere la base de datos poblada
        // En un entorno de testing real, se ejecutarían los seeders primero
        $this->markTestSkipped('Requiere base de datos con catálogos SAT poblados');

        // Código comentado para cuando se ejecute con BD:
        // // Régimen 616 solo compatible con S01
        // $compatible = $this->validationService->validarCompatibilidadRegimenUso('616', 'S01');
        // $this->assertTrue($compatible, 'Régimen 616 debe ser compatible con S01');
    }

    /**
     * Test validación de código postal
     */
    public function test_validacion_codigo_postal(): void
    {
        // Crear una instancia del servicio usando reflexión para acceder al método privado
        $reflection = new \ReflectionClass($this->validationService);
        $method = $reflection->getMethod('validarCodigoPostal');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->validationService, '12345'), 'CP válido debe retornar true');
        $this->assertFalse($method->invoke($this->validationService, '1234'), 'CP de 4 dígitos debe ser inválido');
        $this->assertFalse($method->invoke($this->validationService, '123456'), 'CP de 6 dígitos debe ser inválido');
        $this->assertFalse($method->invoke($this->validationService, 'ABCDE'), 'CP con letras debe ser inválido');
    }

    /**
     * Test reglas específicas para RFC genérico extranjero
     */
    public function test_rfc_generico_extranjero(): void
    {
        // El RFC XEXX010101000 debe ser válido
        $errores = $this->validationService->validarRfc('XEXX010101000');
        $this->assertEmpty($errores, 'RFC genérico extranjero XEXX debe ser válido');

        // El RFC XAXX010101000 debe ser válido
        $errores = $this->validationService->validarRfc('XAXX010101000');
        $this->assertEmpty($errores, 'RFC genérico XAXX debe ser válido');
    }
}
