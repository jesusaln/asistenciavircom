---
description: Configuración y pruebas del Bridge de CONTPAQi Comercial para facturación CFDI
---

# Bridge de CONTPAQi Comercial

## Información de Conexión

| Parámetro | Valor |
|-----------|-------|
| **URL del Bridge** | `http://192.168.191.226:5000` |
| **Red ZeroTier** | `b15644912ecffe58` |
| **Ruta Empresa** | `C:\Compac\Empresas\adJESUS_LOPEZ_NORIEGA` |
| **Código Concepto Factura** | `4CLIMAS` |
| **Código Concepto Pago** | `100` |

## Variables de Entorno (.env)

```bash
# CONTPAQi Bridge Configuration
CONTPAQI_ENABLED=true
CONTPAQI_API_URL=http://192.168.191.226:5000
CONTPAQI_RUTA_EMPRESA=C:\Compac\Empresas\adJESUS_LOPEZ_NORIEGA
CONTPAQI_CSD_PASS=<contraseña_del_certificado>
CONTPAQI_CONCEPT_CODE=4CLIMAS
CONTPAQI_CONCEPT_CODE_PAGO=100
CONTPAQI_CONCEPT_CODE_ANTICIPO=4CLIMAS
```

---

## Endpoints Disponibles

### 1️⃣ `POST /api/Clientes` - Crear/Actualizar Cliente

**Request:**
```json
{
  "rutaEmpresa": "C:\\Compac\\Empresas\\adJESUS_LOPEZ_NORIEGA",
  "codigo": "C003",
  "razonSocial": "CAROLINA QUIROZ TRASVINA",
  "rfc": "QUTC900101ABC",
  "email": "carolina@test.com",
  "calle": "Calle Principal",
  "colonia": "Centro",
  "codigoPostal": "23400",
  "ciudad": "San Jose del Cabo",
  "estado": "Baja California Sur",
  "pais": "Mexico",
  "regimenFiscal": "612",
  "usoCFDI": "G03",
  "formaPago": "03"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cliente C003 creado exitosamente",
  "idCliente": 0
}
```

---

### 2️⃣ `POST /api/Productos` - Crear/Actualizar Producto

**Request:**
```json
{
  "rutaEmpresa": "C:\\Compac\\Empresas\\adJESUS_LOPEZ_NORIEGA",
  "codigo": "TEST-TIMB-003",
  "nombre": "Servicio de Prueba",
  "descripcion": "Servicio para pruebas de timbrado",
  "precio": 250.00,
  "tipoProducto": 3,
  "unidadMedida": "H87",
  "claveSAT": "81112200"
}
```

**Tipos de Producto:**
- `1` = Producto
- `2` = Paquete
- `3` = Servicio

---

### 3️⃣ `POST /api/Documentos/factura` - Crear Factura

**Request:**
```json
{
  "rutaEmpresa": "C:\\Compac\\Empresas\\adJESUS_LOPEZ_NORIEGA",
  "codigoConcepto": "4CLIMAS",
  "codigoCliente": "C003",
  "productos": [
    {
      "codigo": "TEST-TIMB-003",
      "cantidad": 1.0,
      "precio": 1.00
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Factura creada exitosamente",
  "idDocumento": 1234
}
```

---

### 4️⃣ `POST /api/Documentos/timbrar` - Timbrar Factura

**Request:**
```json
{
  "rutaEmpresa": "C:\\Compac\\Empresas\\adJESUS_LOPEZ_NORIEGA",
  "codigoConcepto": "4CLIMAS",
  "serie": "",
  "folio": 1234,
  "passCSD": "contraseña_del_certificado"
}
```

**Nota:** El campo `folio` es de tipo `double`, no `int`.

**Response:**
```json
{
  "success": true,
  "message": "Documento timbrado exitosamente"
}
```

---

### 5️⃣ `GET /api/Documentos/xml` - Obtener XML Timbrado

**Query Parameters:**
```
GET /api/Documentos/xml?rutaEmpresa=C:\Compac\Empresas\adJESUS_LOPEZ_NORIEGA&codigoConcepto=4CLIMAS&serie=&folio=1234
```

**Response:**
```json
{
  "success": true,
  "xml": "<?xml version=\"1.0\"?>..."
}
```

---

### 6️⃣ `POST /api/Documentos/cancelar` - Cancelar CFDI

**Request:**
```json
{
  "rutaEmpresa": "C:\\Compac\\Empresas\\adJESUS_LOPEZ_NORIEGA",
  "codigoConcepto": "4CLIMAS",
  "serie": "",
  "folio": 1234,
  "motivoCancelacion": "02",
  "passCSD": "contraseña",
  "uuidSustitucion": null
}
```

**Motivos de Cancelación (SAT):**
- `01` = Comprobante emitido con errores con relación
- `02` = Comprobante emitido con errores sin relación
- `03` = No se llevó a cabo la operación
- `04` = Operación nominativa relacionada

---

## Prueba de Conexión

```bash
# Verificar que ZeroTier esté conectado
sudo zerotier.cli listnetworks

# Verificar ping al servidor
ping 192.168.191.226

# Verificar que el Bridge responda
curl http://192.168.191.226:5000/api/Status
```

## Flujo de Timbrado

1. **Sincronizar cliente** → `POST /api/Clientes`
2. **Sincronizar producto** → `POST /api/Productos`
3. **Crear factura** → `POST /api/Documentos/factura`
4. **Timbrar** → `POST /api/Documentos/timbrar`
5. **Obtener XML** → `GET /api/Documentos/xml`
6. **Guardar CFDI localmente** → `ContpaqiService::procesarXmlEmitido()`

## Notas Importantes

- El Bridge tiene **sanitización de caracteres** implementada (ñ → n, á → a, etc.)
- El campo `folio` es de tipo `double`, no `int`
- Los certificados CSD deben estar instalados en CONTPAQi
- El Bridge corre en Windows Server con CONTPAQi Comercial instalado
- La comunicación es a través de la VPN ZeroTier (puerto 5000)
