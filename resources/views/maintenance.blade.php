<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Mantenimiento</title>
    <style>
        :root{
            color-scheme: dark;
            --bg0:#050814; --bg1:#0a1022; --card:rgba(255,255,255,.06);
            --border:rgba(255,255,255,.10); --text:#e6eaff; --muted:rgba(230,234,255,.65);
            --accent:#34d399; --accent2:#60a5fa;
        }
        *{box-sizing:border-box}
        body{
            margin:0; min-height:100vh; display:grid; place-items:center;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica, Arial;
            background:
                radial-gradient(900px 500px at 20% 10%, rgba(96,165,250,.18), transparent 60%),
                radial-gradient(800px 420px at 80% 20%, rgba(52,211,153,.16), transparent 55%),
                linear-gradient(160deg, var(--bg0), var(--bg1));
            color:var(--text);
        }
        .wrap{width:min(920px, 92vw); padding:32px}
        .card{
            border-radius:28px;
            background:var(--card);
            border:1px solid var(--border);
            box-shadow: 0 30px 70px rgba(0,0,0,.45);
            overflow:hidden;
        }
        .top{
            padding:28px 28px 0;
            display:flex; gap:16px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap;
        }
        .badge{
            display:inline-flex; align-items:center; gap:10px;
            padding:10px 14px;
            border-radius:999px;
            background:rgba(52,211,153,.10);
            border:1px solid rgba(52,211,153,.25);
            color:var(--accent);
            font-weight:800;
            letter-spacing:.12em;
            text-transform:uppercase;
            font-size:11px;
        }
        .dot{width:10px; height:10px; border-radius:50%; background:var(--accent); box-shadow:0 0 18px rgba(52,211,153,.55)}
        h1{
            margin:18px 0 10px;
            font-size: clamp(26px, 3.5vw, 42px);
            letter-spacing:-.02em;
            line-height:1.1;
        }
        p{
            margin:0;
            color:var(--muted);
            font-size:15px;
            line-height:1.6;
        }
        .grid{
            padding:22px 28px 28px;
            display:grid;
            grid-template-columns: 1.2fr .8fr;
            gap:18px;
        }
        .panel{
            border-radius:22px;
            border:1px solid rgba(255,255,255,.10);
            background: rgba(0,0,0,.18);
            padding:18px;
        }
        .mini{
            display:flex; gap:12px; align-items:flex-start;
        }
        .ico{
            width:38px; height:38px; border-radius:14px;
            display:grid; place-items:center;
            border:1px solid rgba(96,165,250,.22);
            background: rgba(96,165,250,.12);
            color:var(--accent2);
            flex:0 0 auto;
        }
        .label{font-size:11px; font-weight:900; letter-spacing:.14em; text-transform:uppercase; color:rgba(230,234,255,.45)}
        .value{margin-top:6px; font-weight:800; color:rgba(230,234,255,.88)}
        .footer{
            padding:16px 28px 26px;
            border-top:1px solid rgba(255,255,255,.08);
            display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;
        }
        a{color:rgba(230,234,255,.85); text-decoration:none}
        a:hover{color:#fff; text-decoration:underline}
        @media (max-width: 820px){
            .grid{grid-template-columns:1fr}
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="top">
                <div>
                    <div class="badge"><span class="dot"></span> Mantenimiento en progreso</div>
                    <h1>Estamos en mantenimiento por el momento</h1>
                    <p>
                        Estamos aplicando mejoras y actualizaciones al sistema. Por favor intenta de nuevo en unos minutos.
                        Si el problema persiste, contacta a soporte.
                    </p>
                </div>
            </div>

            <div class="grid">
                <div class="panel">
                    <div class="mini">
                        <div class="ico" aria-hidden="true">⏱</div>
                        <div>
                            <div class="label">Reintento sugerido</div>
                            <div class="value">En 1–3 minutos</div>
                        </div>
                    </div>
                    <div style="height:14px"></div>
                    <div class="mini">
                        <div class="ico" aria-hidden="true">🔄</div>
                        <div>
                            <div class="label">Tip rápido</div>
                            <div class="value">Cuando vuelva, usa <strong>Ctrl + Shift + R</strong></div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="label">Estado</div>
                    <div class="value">Servicio temporalmente no disponible (503)</div>
                    <div style="height:10px"></div>
                    <p style="margin:0; color:rgba(230,234,255,.55); font-size:13px">
                        Esta ventana aparece durante despliegues o mantenimiento programado.
                    </p>
                </div>
            </div>

            <div class="footer">
                <div class="label">Gracias por tu paciencia</div>
                <a href="/" rel="nofollow">Volver al inicio</a>
            </div>
        </div>
    </div>
</body>
</html>

