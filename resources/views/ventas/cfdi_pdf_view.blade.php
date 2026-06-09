<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista CFDI {{ $folio ? '#'.$folio : '' }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f1f5f9;
            --panel: #ffffff;
            --ink: #0f172a;
            --muted: #64748b;
            --brand: #2563eb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e2e8f0 0%, #f8fafc 60%);
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
            letter-spacing: 0.4px;
        }

        .title span {
            font-size: 12px;
            color: var(--muted);
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
            display: flex;
            flex-direction: column;
        }

        iframe,
        object {
            border: none;
            width: 100%;
            height: calc(100vh - 180px);
            border-radius: 10px;
            background: #e2e8f0;
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

            iframe,
            object {
                height: calc(100vh - 260px);
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="topbar">
            <div class="title">
                <h1>Vista de {{ $tipo ?? 'CFDI' }}</h1>
                <span>UUID: {{ $uuid }}</span>
            </div>
            <div class="actions">
                <button class="btn" id="openPdfBtn" type="button">Abrir PDF</button>
                <a class="btn secondary" href="{{ $pdf_url }}?download=1" target="_blank" rel="noopener">Descargar</a>
            </div>
        </div>

        <div class="panel">
            <iframe id="pdfFrame" title="CFDI PDF"></iframe>
            <div id="pdfError" style="display:none; padding: 16px; color: #b91c1c; font-size: 13px;">
                No se pudo cargar el PDF. Intenta descargarlo.
            </div>
        </div>
    </div>
    <script>
        (function () {
            const pdfUrl = @json($pdf_url . '?raw=1');
            const frame = document.getElementById('pdfFrame');
            const errorBox = document.getElementById('pdfError');
            const openBtn = document.getElementById('openPdfBtn');
            let blobUrl = null;

            fetch(pdfUrl, { credentials: 'same-origin' })
                .then(response => response.blob())
                .then(blob => {
                    blobUrl = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }));
                    frame.src = blobUrl;
                })
                .catch(() => {
                    frame.style.display = 'none';
                    errorBox.style.display = 'block';
                });

            openBtn.addEventListener('click', () => {
                if (blobUrl) {
                    window.open(blobUrl, '_blank', 'noopener');
                } else {
                    window.open(pdfUrl, '_blank', 'noopener');
                }
            });
        })();
    </script>
</body>
</html>
