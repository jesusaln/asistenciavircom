<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Entorno Organizacional Favorable</title>
    <style>
        @page { size: letter landscape; margin: 0; }
        body { font-family: 'Helvetica', sans-serif; margin: 0; padding: 0; background-color: #fff; color: #1a202c; width: 100%; height: 100%; overflow: hidden; }
        .border-outer { border: 20px solid #1a365d; width: 100%; height: 100%; padding: 20px; box-sizing: border-box; position: absolute; top: 0; left: 0; }
        .border-inner { border: 5px solid #d4af37; height: 95%; padding: 40px; box-sizing: border-box; text-align: center; position: relative; }
        
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.05; font-size: 150px; font-weight: bold; color: #1a365d; z-index: -1; }
        
        .header { margin-bottom: 50px; }
        .header h1 { font-size: 48px; color: #1a365d; margin: 0; text-transform: uppercase; letter-spacing: 4px; }
        .header h2 { font-size: 24px; color: #d4af37; margin: 10px 0 0; text-transform: uppercase; letter-spacing: 2px; }
        
        .content { margin-top: 60px; }
        .content p { font-size: 18px; line-height: 1.6; margin: 20px 0; }
        .content .company-name { font-size: 36px; font-weight: bold; color: #1a365d; margin: 30px 0; text-decoration: underline; }
        
        .stats { margin-top: 40px; font-size: 14px; color: #4a5568; }
        
        .signatures { margin-top: 100px; display: table; width: 100%; }
        .signature-box { display: table-cell; width: 50%; text-align: center; }
        .signature-line { width: 250px; border-top: 2px solid #1a365d; margin: 0 auto 10px; }
        .signature-text { font-size: 12px; font-weight: bold; color: #1a365d; text-transform: uppercase; }
        
        .footer { position: absolute; bottom: 40px; left: 0; right: 0; text-align: center; font-size: 10px; color: #718096; }
        .legal-stamp { position: absolute; top: 60px; right: 60px; border: 3px double #d4af37; padding: 10px; font-size: 12px; color: #d4af37; transform: rotate(15deg); font-weight: bold; }
    </style>
</head>
<body>
    <div class="border-outer">
        <div class="border-inner">
            <div class="watermark">NOM-035</div>
            <div class="legal-stamp">CUMPLIMIENTO<br>TOTAL 100%</div>
            
            <div class="header">
                <h2>Reconocimiento a la Excelencia en</h2>
                <h1>Entorno Organizacional</h1>
            </div>
            
            <div class="content">
                <p>Se otorga el presente reconocimiento a:</p>
                <div class="company-name">{{ $recipient }}</div>
                <p>
                    Por haber cumplido satisfactoriamente con el <strong>100% de los requisitos</strong> establecidos en la <br>
                    <strong>Norma Oficial Mexicana NOM-035-STPS-2018</strong>, Factores de riesgo psicosocial en el trabajo - <br>
                    Identificación, análisis y prevención, durante el ciclo de cumplimiento:
                </p>
                <p style="font-size: 24px; font-weight: bold; color: #d4af37;">{{ $period->name }}</p>
                
                <div class="stats">
                    Este reconocimiento avala la participación de <strong>{{ $respondentsCount }} colaboradores</strong> evaluados, <br>
                    garantizando un clima laboral favorable y la prevención de riesgos psicosociales.
                </div>
            </div>
            
            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-text">Dirección de Recursos Humanos</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-text">Comisión de Seguridad e Higiene</div>
                </div>
            </div>
            
            <div class="footer">
                Documento de validez interna | Generado el {{ $fecha }} | ID de Verificación: {{ Str::random(12) }}
            </div>
        </div>
    </div>
</body>
</html>
