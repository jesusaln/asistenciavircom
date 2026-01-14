# Configuración CSD para FacturaLO Plus

## Variables de Entorno (.env)

```env
# API FacturaLO PLUS
FACTURALO_BASE_URL=https://dev.facturaloplus.com/api/rest/servicio
FACTURALO_APIKEY=TU_API_KEY_DE_32_CHARS
# Rutas a archivos (PEM) para timbrar JSON/firmar si usas timbrarConSello
CSD_CER_PEM=/path/a/tu.cer.pem
CSD_KEY_PEM=/path/a/tu.key.pem
CSD_KEY_PASSWORD=tu_password_del_key
```

## Archivos CSD Requeridos

Coloca en este directorio los siguientes archivos:

1. **CSD de prueba vigentes**: Descárgalos desde la guía de FacturaLO Plus
2. **Archivos PEM**: Convierte tus CSD a formato PEM usando OpenSSL

### Convertir CSD a PEM

```bash
# Convertir .cer a .pem
openssl x509 -inform DER -in certificado.cer -out certificado.pem

# Convertir .key a .pem (si es necesario)
openssl pkcs8 -inform DER -in llave.key -out llave.pem
```

## Endpoints de Prueba

- **Timbrado**: `GET /cfdi/timbrar-demo`
- **Cancelación**: `POST /cfdi/cancelar-demo`
- **Estado SAT**: `POST /cfdi/estado-sat`

## Notas Importantes

- **Total exacto**: Para cancelación y consulta SAT, usa el total exacto del CFDI
- **RFCs**: Deben coincidir exactamente con los del CFDI emitido
- **Motivos de cancelación**: 01-04 según Anexo 20
- **CSD vigentes**: Los CSD de prueba tienen vigencia limitada

## CSD de Prueba

Los CSD de prueba están disponibles en:
- https://developers.facturaloplus.com/docs/guias/certificado-prueba
