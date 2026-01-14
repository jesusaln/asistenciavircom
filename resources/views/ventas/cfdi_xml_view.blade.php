<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XML CFDI {{ $uuid }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f8fafc;
            --panel: #ffffff;
            --ink: #0f172a;
            --muted: #64748b;
            --brand: #2563eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--ink);
        }
        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 16px;
        }
        .topbar {
            background: var(--panel);
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }
        .title {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .title h1 {
            margin: 0;
            font-size: 18px;
            letter-spacing: 0.3px;
        }
        .title span {
            font-size: 12px;
            color: var(--muted);
            word-break: break-all;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--brand);
            color: #fff;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.25);
        }
        .btn.secondary {
            background: #0f172a;
        }
        .panel {
            background: var(--panel);
            border-radius: 16px;
            padding: 12px;
            flex: 1;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }
        pre {
            margin: 0;
            padding: 14px;
            background: #0b1220;
            color: #dbeafe;
            border-radius: 12px;
            overflow: auto;
            font-size: 12px;
            line-height: 1.5;
            max-height: calc(100vh - 190px);
        }
        .error {
            padding: 12px;
            color: #b91c1c;
            font-size: 13px;
        }
        @media (max-width: 720px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .actions {
                width: 100%;
                flex-direction: column;
            }
            pre {
                max-height: calc(100vh - 260px);
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="topbar">
            <div class="title">
                <h1>XML CFDI</h1>
                <span>UUID: {{ $uuid }}</span>
            </div>
            <div class="actions">
                <a class="btn" href="{{ $xml_url }}" target="_blank" rel="noopener">Abrir XML</a>
                <a class="btn secondary" href="{{ $download_url }}" target="_blank" rel="noopener">Descargar</a>
            </div>
        </div>

        <div class="panel">
            <pre id="xmlOutput">Cargando XML...</pre>
            <div id="xmlError" class="error" style="display:none;">No se pudo cargar el XML.</div>
        </div>
    </div>
    <script>
        (function () {
            const xmlUrl = @json($xml_url);
            const output = document.getElementById('xmlOutput');
            const errorBox = document.getElementById('xmlError');

            function formatXml(xml) {
                const reg = /(>)(<)(\/*)/g;
                const pad = '  ';
                let formatted = '';
                let indent = 0;
                xml.replace(reg, '$1\n$2$3').split('\n').forEach((node) => {
                    if (node.match(/^<\/\w/)) {
                        indent = Math.max(indent - 1, 0);
                    }
                    formatted += pad.repeat(indent) + node + '\n';
                    if (node.match(/^<\w[^>]*[^\/]>$/)) {
                        indent += 1;
                    }
                });
                return formatted.trim();
            }

            fetch(xmlUrl, { credentials: 'same-origin' })
                .then((response) => response.text())
                .then((xml) => {
                    output.textContent = formatXml(xml);
                })
                .catch(() => {
                    output.style.display = 'none';
                    errorBox.style.display = 'block';
                });
        })();
    </script>
</body>
</html>
