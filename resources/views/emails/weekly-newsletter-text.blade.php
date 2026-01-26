ASISTENCIA VIRCOM
BLOG CORPORATIVO

Hola, {{ $cliente->nombre_razon_social }}

Esta semana tenemos un artículo que te interesará especialmente para mantener tu infraestructura funcionando al 100%:

*** {{ $post->titulo }} ***

{{ $post->resumen }}

Leer artículo completo: {{ config('app.url') . '/blog/' . $post->slug }}

---
Esperamos que esta información te sea de gran utilidad. Si tienes dudas o necesitas soporte técnico, nuestro equipo de
ingenieros está listo para ayudarte.

Asistencia Vircom
Expertos en Soluciones Tecnológicas

Darse de baja: {{ config('app.url') }}/newsletter/unsubscribe?email={{ urlencode($cliente->email) }}

(C) {{ date('Y') }} Asistencia Vircom. Todos los derechos reservados.