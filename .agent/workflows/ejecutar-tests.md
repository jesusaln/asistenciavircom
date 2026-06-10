---
description: Ejecutar tests para todos los módulos del sistema
---

# Ejecutar Tests de Todos los Módulos

## Comandos Disponibles

### Ejecutar todos los tests
// turbo
```bash
php artisan test
```

### Ejecutar solo tests de Feature
// turbo
```bash
php artisan test --testsuite=Feature
```

### Ejecutar solo tests de Unit
// turbo
```bash
php artisan test --testsuite=Unit
```

---

## Tests por Módulo

### Clientes
// turbo
```bash
php artisan test tests/Feature/ClienteControllerTest.php tests/Feature/ClienteCrudTest.php
```

### Ventas
// turbo
```bash
php artisan test tests/Feature/VentaControllerTest.php tests/Feature/VentaCrudTest.php tests/Feature/VentaFlowTest.php
```

### Cotizaciones
// turbo
```bash
php artisan test tests/Feature/CotizacionTest.php tests/Feature/CotizacionCrudTest.php
```

### Pedidos
// turbo
```bash
php artisan test tests/Feature/PedidoCrudTest.php
```

### Compras
// turbo
```bash
php artisan test tests/Feature/CompraTest.php
```

### Órdenes de Compra
// turbo
```bash
php artisan test tests/Feature/OrdenCompraTest.php tests/Feature/OrdenCompraControllerTest.php
```

### Citas
// turbo
```bash
php artisan test tests/Feature/CitaCrudTest.php tests/Feature/CitaControllerTest.php tests/Feature/CitaApiControllerTest.php tests/Feature/CitaModelTest.php tests/Feature/CrudCitaTest.php
```

### Productos
// turbo
```bash
php artisan test tests/Feature/ProductoSeriesTest.php tests/Feature/KitTest.php
```

### Facturas y PDFs
// turbo
```bash
php artisan test tests/Feature/FacturaPdfTest.php tests/Feature/PdfGeneratorServiceTest.php
```

### Servicios Financieros
// turbo
```bash
php artisan test tests/Feature/FinancialServiceTest.php
```

### Multi-Tenancy
// turbo
```bash
php artisan test tests/Feature/MultiEmpresaScopingTest.php tests/Feature/MultiTenancyIsolationTest.php
```

### Autenticación
// turbo
```bash
php artisan test tests/Feature/AuthenticationTest.php tests/Feature/RegistrationTest.php tests/Feature/PasswordResetTest.php tests/Feature/PasswordConfirmationTest.php tests/Feature/EmailVerificationTest.php tests/Feature/TwoFactorAuthenticationSettingsTest.php
```

### Perfil y Cuenta
// turbo
```bash
php artisan test tests/Feature/ProfileInformationTest.php tests/Feature/UpdatePasswordTest.php tests/Feature/DeleteAccountTest.php tests/Feature/BrowserSessionsTest.php
```

### Teams
// turbo
```bash
php artisan test tests/Feature/CreateTeamTest.php tests/Feature/DeleteTeamTest.php tests/Feature/UpdateTeamNameTest.php tests/Feature/InviteTeamMemberTest.php tests/Feature/RemoveTeamMemberTest.php tests/Feature/LeaveTeamTest.php tests/Feature/UpdateTeamMemberRoleTest.php
```

### API Tokens
// turbo
```bash
php artisan test tests/Feature/CreateApiTokenTest.php tests/Feature/DeleteApiTokenTest.php tests/Feature/ApiTokenPermissionsTest.php
```

---

## Opciones Útiles

### Ejecutar tests con verbose output
// turbo
```bash
php artisan test --verbose
```

### Ejecutar tests y parar en el primer error
// turbo
```bash
php artisan test --stop-on-failure
```

### Ejecutar tests en paralelo
// turbo
```bash
php artisan test --parallel
```

### Ejecutar tests con coverage
```bash
php artisan test --coverage
```

### Ejecutar un test específico
```bash
php artisan test --filter=nombre_del_test
```

---

## Verificación Rápida (Smoke Tests)

Para verificar que el sistema funciona correctamente después de cambios:

// turbo
```bash
php artisan test tests/Feature/ClienteCrudTest.php tests/Feature/VentaCrudTest.php tests/Feature/PedidoCrudTest.php tests/Feature/CotizacionCrudTest.php tests/Feature/CompraTest.php --stop-on-failure
```

---

## Script de Tests Completos

Para ejecutar todos los tests críticos del negocio:

// turbo
```bash
php artisan test --testsuite=Feature --exclude-group=slow --stop-on-failure
```

---

## Troubleshooting

### Si los tests fallan por base de datos
```bash
php artisan migrate:fresh --env=testing
php artisan db:seed --env=testing
```

### Si necesitas limpiar caché de tests
```bash
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
```

### Ver logs de tests
```bash
tail -f storage/logs/laravel.log
```
