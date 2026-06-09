<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato Individual de Trabajo</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', Georgia, serif;
            font-size: 11pt;
            line-height: 1.6;
            margin: 1.5cm;
            color: #000;
            text-align: justify;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        .title {
            font-weight: bold;
            font-size: 15pt;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        .section-title {
            text-align: center;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 15px;
            letter-spacing: 3px;
            font-size: 12pt;
            border-bottom: 1px solid #999;
            padding-bottom: 8px;
        }
        p {
            margin-bottom: 12px;
            text-align: justify;
        }
        ul {
            list-style-type: disc;
            margin-left: 25px;
            margin-bottom: 12px;
            margin-top: 8px;
        }
        li {
            margin-bottom: 5px;
            line-height: 1.5;
        }
        .bold {
            font-weight: bold;
        }
        .signatures {
            margin-top: 40px;
            width: 100%;
        }
        .signature-block {
            width: 45%;
            float: left;
            text-align: center;
            margin-top: 30px;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin: 0 auto;
            width: 85%;
            margin-bottom: 8px;
            margin-top: 50px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        /* Marca de agua */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 85pt;
            color: rgba(0, 0, 0, 0.04);
            z-index: -1;
            pointer-events: none;
            white-space: nowrap;
            font-family: Arial, sans-serif;
            font-weight: bold;
        }
        /* ===== ESTILOS DE IMPRESIÓN ===== */
        @media print {
            .no-print { 
                display: none !important; 
            }
            body {
                margin: 0;
                padding: 0.5cm;
                font-size: 10.5pt;
                line-height: 1.5;
            }
            .header {
                margin-bottom: 15px;
            }
            .section-title {
                margin-top: 20px;
                margin-bottom: 12px;
                page-break-after: avoid;
                break-after: avoid;
            }
            p {
                margin-bottom: 10px;
                page-break-inside: avoid;
                break-inside: avoid;
                orphans: 4;
                widows: 4;
            }
            li {
                page-break-inside: avoid;
                break-inside: avoid;
            }
            ul, ol {
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .signatures, .signature-block {
                page-break-inside: avoid;
                break-inside: avoid;
            }
            a[href]:after { 
                content: none !important; 
            }
            a {
                text-decoration: none;
                color: inherit;
            }
        }
        @page {
            size: letter portrait;
            margin: 1.8cm 1.5cm 2cm 1.5cm;
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #f0fdf4; padding: 15px; margin-bottom: 20px; text-align: center; border: 1px solid #22c55e; border-radius: 8px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #16a34a; color: white; border: none; border-radius: 5px; font-weight: bold; font-size: 14px;">IMPRIMIR CONTRATO</button>
    </div>

    <!-- Marca de agua -->
    <div class="watermark">ORIGINAL</div>

    <div class="header">
        @if(file_exists(public_path('images/logo.png')))
        <img src="{{ asset('images/logo.png') }}" style="height: 60px; margin-bottom: 10px;" alt="Logo Empresa">
        @else
        <div style="font-size: 18pt; font-weight: bold; color: #1e40af; margin-bottom: 10px;">CLIMAS DEL DESIERTO</div>
        @endif
        <div class="title">CONTRATO INDIVIDUAL DE TRABAJO</div>
        <div style="font-size: 9pt; color: #666; margin-top: 5px;">Folio: <span class="bold">CTR-{{ str_pad($empleado->id, 4, '0', STR_PAD_LEFT) }}-{{ date('Y') }}</span></div>
    </div>

    <p>
        <span class="bold">CONTRATO INDIVIDUAL DE TRABAJO</span> que celebran por una parte <span class="bold">CLIMAS DEL DESIERTO</span>, representada por su Representante Legal, el C. <span class="bold">{{ strtoupper($empresa->titular ?? 'JESÚS ALBERTO LÓPEZ NORIEGA') }}</span>, a quien en lo sucesivo se le denominará <span class="bold">“EL PATRÓN”</span>, y por la otra el C. <span class="bold">{{ strtoupper($empleado->user->name) }}</span>, a quien en lo sucesivo se le denominará <span class="bold">“EL TRABAJADOR”</span>, al tenor de las siguientes:
    </p>

    <div class="section-title">D E C L A R A C I O N E S</div>

    <p><span class="bold">I. DECLARA “EL PATRÓN”:</span></p>
    <ul>
        <li>Ser una persona física, {{ $empresa->regimen_fiscal ?? 'RÉGIMEN SIMPLIFICADO DE CONFIANZA' }}, conforme a las leyes mexicanas.</li>
        <li>Tener como actividad principal la comercialización, instalación, mantenimiento y servicio de sistemas de climatización.</li>
        <li>Tener su domicilio para efectos del presente contrato en <span class="bold">{{ $empresa->direccion_completa ?? 'DOMICILIO DE LA EMPRESA' }}</span>.</li>
    </ul>

    <p><span class="bold">II. DECLARA “EL TRABAJADOR”:</span></p>
    <ul>
        <li>Ser de nacionalidad mexicana, mayor de edad, en pleno uso de sus facultades.</li>
        <li>Llamarse <span class="bold">{{ strtoupper($empleado->user->name) }}</span>, con CURP <span class="bold">{{ $empleado->curp }}</span> y RFC <span class="bold">{{ $empleado->rfc }}</span>.</li>
        <li>Fecha de nacimiento: <span class="bold">{{ $empleado->fecha_nacimiento ? \Carbon\Carbon::parse($empleado->fecha_nacimiento)->isoFormat('LL') : 'NO PROPORCIONADA' }}</span>.</li>
        <li>Número de Seguridad Social (NSS): <span class="bold">{{ $empleado->numero_seguro_social ?? 'POR ASIGNAR' }}</span>.</li>
        <li>Tener domicilio particular en <span class="bold">{{ $empleado->direccion }}</span>.</li>
        <li>Teléfono de contacto: <span class="bold">{{ $empleado->telefono ?? 'NO PROPORCIONADO' }}</span>.</li>
        <li>Contacto de emergencia: <span class="bold">{{ $empleado->contacto_emergencia_nombre ?? 'NO PROPORCIONADO' }}</span>, Tel: <span class="bold">{{ $empleado->contacto_emergencia_telefono ?? 'N/A' }}</span>.</li>
        <li>Contar con la capacidad, conocimientos y experiencia necesarios para desempeñar el puesto contratado.</li>
    </ul>

    <p style="text-align: center; font-style: italic; margin-top: 15px;">Reconociéndose ambas partes la personalidad con la que comparecen, convienen en sujetarse a las siguientes:</p>

    <div class="section-title">C L Á U S U L A S</div>

    <p><span class="bold">PRIMERA. NATURALEZA DEL CONTRATO</span></p>
    <p>El presente contrato se celebra por <span class="bold">TIEMPO INDETERMINADO</span>, con una jornada de <span class="bold">{{ strtoupper($empleado->tipo_contrato_formateado) }}</span>, iniciando su vigencia el día {{ \Carbon\Carbon::parse($empleado->fecha_contratacion)->isoFormat('LL') }}, conforme a los artículos 35 y 39 de la Ley Federal del Trabajo.</p>

    <p><span class="bold">PRIMERA BIS. PERIODO DE CAPACITACIÓN INICIAL</span></p>
    <p>Las partes acuerdan un periodo de capacitación inicial de <span class="bold">30 días naturales</span>, contados a partir de la fecha de inicio de la relación laboral, durante el cual cualquiera de las partes podrá dar por terminada la relación de trabajo sin responsabilidad, conforme a los artículos 39-A al 39-F de la Ley Federal del Trabajo. Si al término de dicho periodo el trabajador no acredita los requisitos y conocimientos necesarios, el patrón podrá dar por terminada la relación sin responsabilidad.</p>

    <p><span class="bold">SEGUNDA. PUESTO Y FUNCIONES</span></p>
    <p>“EL TRABAJADOR” se desempeñará en el puesto de <span class="bold">{{ strtoupper($empleado->puesto) }}</span>, dentro del DEPARTAMENTO DE <span class="bold">{{ strtoupper($empleado->departamento) }}</span>, teniendo entre otras, las siguientes funciones:</p>
    <ul>
        <li>Desempeño de tareas asignadas acorde a su perfil.</li>
        <li>Uso responsable de herramientas, sistemas y equipos proporcionados.</li>
        <li>Reporte de actividades al supervisor inmediato.</li>
        <li>Cumplimiento de objetivos del área.</li>
        <li>Apoyo en actividades operativas cuando sea requerido.</li>
    </ul>
    <p>Las funciones descritas son de carácter enunciativo y no limitativo, por lo que el trabajador se obliga a desempeñar cualesquiera otras actividades afines o relacionadas con la operación y necesidades de la empresa, sin que ello implique modificación o incremento en su salario, siempre que dichas actividades sean compatibles con su puesto, capacidad y dignidad laboral, conforme a la Ley Federal del Trabajo.</p>

    <p><span class="bold">TERCERA. JORNADA DE TRABAJO</span></p>
    <p>La jornada laboral será de:</p>
    <ul>
        <li><span class="bold">Lunes a viernes:</span> de {{ $empleado->hora_entrada ?? '08:00' }} a {{ $empleado->hora_salida ?? '17:00' }} horas, con 60 minutos de descanso.</li>
        @if($empleado->trabaja_sabado)
        <li><span class="bold">Sábados:</span> de {{ $empleado->hora_entrada_sabado ?? '08:00' }} a {{ $empleado->hora_salida_sabado ?? '14:00' }} horas.</li>
        @endif
        <li><span class="bold">Domingo:</span> día de descanso semanal.</li>
    </ul>
    <p>El trabajador se obliga a llegar puntualmente, registrar su asistencia y cumplir con los horarios establecidos.</p>

    <p><span class="bold">TERCERA BIS. LUGAR DE TRABAJO</span></p>
    <p>El trabajador prestará sus servicios principalmente en el domicilio del patrón ubicado en <span class="bold">{{ $empresa->direccion_completa ?? 'DOMICILIO DE LA EMPRESA' }}</span>, o en cualquier otro lugar donde la empresa tenga operaciones, instalaciones, obras o servicios, según las necesidades del trabajo, sin que ello implique modificación de las condiciones laborales.</p>

    <p><span class="bold">CUARTA. SALARIO</span></p>
    <p>"EL TRABAJADOR" percibirá un salario mensual integrado de <span class="bold">${{ number_format($empleado->salario_base, 2) }}</span> (<span class="bold">{{ $empleado->salario_en_letras ?? 'SALARIO EN LETRAS' }} PESOS 00/100 M.N.</span>), mismo que será cubierto de forma <span class="bold">{{ strtoupper($empleado->frecuencia_pago_formateada) }}</span>, mediante transferencia electrónica a la cuenta bancaria proporcionada por el trabajador, conforme a la legislación laboral vigente.</p>

    <p><span class="bold">CUARTA BIS. DESCUENTOS AUTORIZADOS</span></p>
    <p>El trabajador autoriza expresamente al patrón a efectuar descuentos a su salario por los siguientes conceptos, conforme al Artículo 110 de la Ley Federal del Trabajo:</p>
    <ul>
        <li>Cuotas al Instituto Mexicano del Seguro Social.</li>
        <li>Impuesto Sobre la Renta (ISR) conforme a la ley.</li>
        <li>Préstamos, anticipos o adeudos a la empresa que el trabajador haya aceptado por escrito.</li>
        <li>Pago de pensiones alimenticias ordenadas por autoridad judicial.</li>
        <li>Cuotas sindicales, en su caso.</li>
    </ul>

    <p><span class="bold">CUARTA TER. COMISIONES Y BONOS</span></p>
    <p>Además del salario base, el trabajador podrá percibir comisiones, bonos de productividad o incentivos conforme a las políticas internas de la empresa, los cuales serán determinados y comunicados por el patrón según el desempeño y resultados del trabajador.</p>

    <p><span class="bold">CUARTA QUATER. VIÁTICOS Y GASTOS</span></p>
    <p>Los viáticos para comisiones de trabajo se otorgarán previa autorización del patrón y deberán comprobarse con facturas o recibos fiscales en un plazo no mayor a 5 días hábiles posteriores a la comisión. El incumplimiento de esta obligación faculta al patrón a descontar los importes no comprobados del salario del trabajador.</p>

    <p><span class="bold">QUINTA. UNIFORME Y PRESENTACIÓN</span></p>
    <p>Cuando el patrón proporcione uniforme, herramientas o equipo, el trabajador se obliga a:</p>
    <ul>
        <li>Usarlos correctamente durante su jornada.</li>
        <li>Mantenerlos en buen estado.</li>
        <li>Presentarse con higiene, orden y apariencia adecuada.</li>
        <li>Devolverlos al finalizar la relación laboral.</li>
    </ul>

    <p><span class="bold">QUINTA QUATER. USO DE VEHÍCULO DE LA EMPRESA</span></p>
    <p>Cuando se asigne vehículo de la empresa al trabajador, éste se obliga a:</p>
    <ul>
        <li>Utilizarlo exclusivamente para actividades laborales autorizadas.</li>
        <li>Mantener vigente su licencia de conducir.</li>
        <li>Cubrir las multas de tránsito derivadas de su conducción.</li>
        <li>No prestarlo a terceros ni utilizarlo para fines personales.</li>
        <li>Reportar inmediatamente cualquier accidente, daño o siniestro.</li>
        <li>Devolverlo en buen estado al terminar la relación laboral.</li>
    </ul>

    <p><span class="bold">QUINTA TER. USO DE EQUIPO DE CÓMPUTO Y TECNOLOGÍA</span></p>
    <p>El trabajador se compromete a utilizar los equipos de cómputo, software, correo electrónico, teléfonos y sistemas proporcionados por el patrón exclusivamente para fines laborales, obligándose a:</p>
    <ul>
        <li>No instalar software no autorizado ni descargar contenido ajeno a sus funciones.</li>
        <li>Proteger las credenciales de acceso y no compartirlas con terceros.</li>
        <li>Reportar inmediatamente cualquier falla, pérdida o robo del equipo asignado.</li>
        <li>Devolver el equipo en buen estado al terminar la relación laboral.</li>
    </ul>

    <p><span class="bold">QUINTA BIS. HORAS EXTRAORDINARIAS</span></p>
    <p>Las horas extraordinarias únicamente se laborarán cuando exista autorización expresa y por escrito del patrón, y se pagarán conforme a lo establecido en los artículos 66, 67 y 68 de la Ley Federal del Trabajo, a saber:</p>
    <ul>
        <li>Las primeras nueve horas extraordinarias a la semana se pagarán con un 100% más del salario correspondiente.</li>
        <li>Las horas extraordinarias que excedan de nueve a la semana se pagarán con un 200% más del salario correspondiente.</li>
        <li>En ningún caso las horas extraordinarias podrán exceder de tres horas diarias ni de tres veces en una semana.
    </ul>

    <p><span class="bold">SEXTA. CONFIDENCIALIDAD</span></p>
    <p>El trabajador se obliga de manera permanente, aun después de terminada la relación laboral, a no divulgar información relacionada con:</p>
    <ul>
        <li>Datos personales de clientes, empleados y proveedores.</li>
        <li>Precios, cotizaciones, procesos internos.</li>
        <li>Sistemas, contraseñas, bases de datos.</li>
        <li>Información financiera, administrativa u operativa.</li>
    </ul>
    <p>El incumplimiento será causa de responsabilidad laboral y legal.</p>

    <p><span class="bold">SEXTA BIS. PROPIEDAD INTELECTUAL</span></p>
    <p>Todo trabajo, diseño, invención, mejora, desarrollo de software, documentación técnica, material publicitario o cualquier creación realizada por el trabajador durante la vigencia de este contrato y en el ámbito de sus funciones laborales, será propiedad exclusiva e irrevocable del patrón, sin derecho a compensación adicional. El trabajador cede expresamente todos los derechos patrimoniales de autor que pudieran corresponderle.</p>

    <p><span class="bold">SÉPTIMA. VACACIONES Y PRESTACIONES</span></p>
    <p>El trabajador gozará de:</p>
    <ul>
        <li>Vacaciones conforme al Artículo 76 LFT.</li>
        <li>Prima vacacional mínima del 25%.</li>
        <li>Aguinaldo mínimo de 15 días de salario, pagadero antes del 20 de diciembre.</li>
    </ul>

    <p><span class="bold">SÉPTIMA BIS. SEGURIDAD E HIGIENE</span></p>
    <p>El trabajador se obliga a cumplir con las normas de seguridad e higiene establecidas por la empresa y la normatividad aplicable, incluyendo:</p>
    <ul>
        <li>Utilizar correctamente el equipo de protección personal proporcionado.</li>
        <li>Acatar las disposiciones internas en materia de prevención de riesgos laborales.</li>
        <li>Reportar inmediatamente cualquier condición insegura o accidente de trabajo.</li>
        <li>Participar en las capacitaciones de seguridad que imparta la empresa.</li>
    </ul>

    <p><span class="bold">OCTAVA. DÍAS DE DESCANSO OBLIGATORIO</span></p>
    <p>Serán los establecidos en el Artículo 74 de la LFT, con goce íntegro de salario.</p>

    <p><span class="bold">OCTAVA BIS. SEGURIDAD SOCIAL</span></p>
    <p>El patrón se obliga a inscribir al trabajador ante el Instituto Mexicano del Seguro Social (IMSS), efectuando las aportaciones correspondientes conforme a la Ley del Seguro Social. El trabajador declara tener el Número de Seguridad Social: <span class="bold">{{ $empleado->numero_seguro_social ?? 'POR ASIGNAR' }}</span>.</p>

    <p><span class="bold">NOVENA. NOM-035-STPS-2018</span></p>
    <p>El patrón cumplirá con la NOM-035-STPS-2018, identificando, analizando y previniendo factores de riesgo psicosocial. El trabajador se compromete a:</p>
    <ul>
        <li>Participar en evaluaciones.</li>
        <li>Conducirse con respeto.</li>
        <li>Evitar actos de violencia laboral, acoso o hostigamiento.</li>
    </ul>

    <p><span class="bold">DÉCIMA. REGLAMENTO INTERIOR</span></p>
    <p>El trabajador declara conocer y aceptar el Reglamento Interior de Trabajo, obligándose a cumplirlo.</p>

    <p><span class="bold">DÉCIMA BIS. EXCLUSIVIDAD LABORAL</span></p>
    <p>El trabajador se compromete a no laborar para otra empresa del mismo giro comercial durante la vigencia de este contrato, salvo autorización expresa y por escrito del patrón. El incumplimiento de esta cláusula será causa de rescisión sin responsabilidad para el patrón.</p>

    <p><span class="bold">DÉCIMA TER. RENUNCIA VOLUNTARIA</span></p>
    <p>En caso de renuncia voluntaria, el trabajador deberá notificar por escrito al patrón con un mínimo de 15 días naturales de anticipación, y realizar la entrega formal de:</p>
    <ul>
        <li>Pendientes y proyectos en curso.</li>
        <li>Herramientas, equipo y uniformes asignados.</li>
        <li>Documentación, credenciales y accesos.</li>
        <li>Vehículo de la empresa, en su caso.</li>
    </ul>

    <p><span class="bold">DÉCIMA QUATER. INVENTARIO DE HERRAMIENTAS</span></p>
    <p>El trabajador recibirá herramientas y equipo de trabajo conforme al inventario registrado en el sistema de la empresa, firmando responsiva digital por cada equipo entregado. Dicho inventario forma parte integral de este contrato como <span class="bold">Anexo "Responsiva de Herramientas"</span>.</p>
    @php
        $tecnico = \App\Models\Tecnico::where('user_id', $empleado->user_id)->first();
        $herramientasAsignadas = $tecnico ? $tecnico->herramientasAsignadas : collect();
    @endphp
    @if($herramientasAsignadas->count() > 0)
    <p style="font-size: 9pt; color: #666;">Herramientas actualmente asignadas: <span class="bold">{{ $herramientasAsignadas->count() }}</span> | Valor total: <span class="bold">${{ number_format($herramientasAsignadas->sum('costo_reemplazo'), 2) }}</span></p>
    @endif

    <p><span class="bold">DÉCIMA PRIMERA. CAUSAS DE RESCISIÓN</span></p>
    <p>Serán causas de rescisión SIN RESPONSABILIDAD PARA EL PATRÓN, las previstas en el Artículo 47 de la Ley Federal del Trabajo, entre ellas:</p>
    <ul>
        <li>Faltas injustificadas.</li>
        <li>Deshonestidad, robo o abuso de confianza.</li>
        <li>Revelación de información confidencial.</li>
        <li>Insubordinación.</li>
        <li>Presentarse bajo efectos de alcohol o drogas.</li>
        <li>Daños intencionales a bienes de la empresa.</li>
    </ul>

    <p><span class="bold">DÉCIMA PRIMERA BIS. PROTECCIÓN DE DATOS PERSONALES</span></p>
    <p>El trabajador autoriza expresamente al patrón el tratamiento de sus datos personales conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP), exclusivamente para los siguientes fines:</p>
    <ul>
        <li>Administración de la relación laboral y pago de salarios.</li>
        <li>Cumplimiento de obligaciones fiscales y de seguridad social.</li>
        <li>Contacto de emergencia y notificaciones laborales.</li>
        <li>Elaboración de credenciales, uniformes y control de acceso.</li>
    </ul>
    <p>El patrón se compromete a proteger dichos datos conforme a la legislación aplicable.</p>

    <p><span class="bold">DÉCIMA SEGUNDA. LEGISLACIÓN APLICABLE</span></p>
    <p>Para lo no previsto en este contrato, se aplicará la Ley Federal del Trabajo, disposiciones de la STPS y demás leyes laborales vigentes en México.</p>

    <p><span class="bold">DÉCIMA TERCERA. JURISDICCIÓN</span></p>
    <p>Para la interpretación y cumplimiento del presente contrato, las partes se someten a las autoridades laborales competentes en el Estado correspondiente, renunciando a cualquier otro fuero.</p>

    <div class="section-title">F I R M A S</div>

    <p style="text-align: center; margin-bottom: 40px;">
        Leído que fue el presente contrato y enteradas las partes de su contenido y alcance legal, lo firman en <span class="bold">{{ $empresa->ciudad ?? 'la ciudad correspondiente' }}, {{ $empresa->estado ?? 'México' }}</span>, a los {{ \Carbon\Carbon::now()->day }} días del mes de {{ \Carbon\Carbon::now()->monthName }} de {{ \Carbon\Carbon::now()->year }}, quedando un ejemplar para cada parte.
    </p>

    <div class="signatures clearfix">
        <div class="signature-block">
            <div class="signature-line"></div>
            <span class="bold">EL PATRÓN</span><br>
            {{ $empresa->titular ?? 'JESÚS ALBERTO LÓPEZ NORIEGA' }}<br>
            Representante Legal<br>
            CLIMAS DEL DESIERTO
        </div>
        <div class="signature-block" style="float: right;">
            <div class="signature-line"></div>
            <span class="bold">EL TRABAJADOR</span><br>
            {{ $empleado->user->name }}
        </div>
    </div>

    <div class="section-title" style="margin-top: 60px;">T E S T I G O S</div>

    <div class="signatures clearfix" style="margin-top: 30px;">
        <div class="signature-block">
            <div class="signature-line"></div>
            <span class="bold">TESTIGO 1</span><br>
            Nombre: _______________________________<br>
            Domicilio: _______________________________
        </div>
        <div class="signature-block" style="float: right;">
            <div class="signature-line"></div>
            <span class="bold">TESTIGO 2</span><br>
            Nombre: _______________________________<br>
            Domicilio: _______________________________
        </div>
    </div>

    <!-- ACUSE DE RECIBO EN HOJA APARTE -->
    <div style="page-break-before: always;"></div>

    <div class="section-title">A C U S E   D E   R E C I B O</div>

    <p style="margin-top: 20px;">
        El trabajador <span class="bold">{{ strtoupper($empleado->user->name) }}</span> declara haber recibido un ejemplar original de este Contrato Individual de Trabajo, así como los siguientes documentos:
    </p>
    <ul>
        <li>☐ Reglamento Interior de Trabajo</li>
        <li>☐ Aviso de Privacidad</li>
        <li>☐ Descripción del Puesto</li>
        <li>☐ Políticas de la Empresa</li>
    </ul>

    <div class="signatures clearfix" style="margin-top: 30px;">
        <div class="signature-block">
            <div class="signature-line"></div>
            <span class="bold">FIRMA DEL TRABAJADOR</span><br>
            {{ $empleado->user->name }}
        </div>
        <div class="signature-block" style="float: right;">
            <p style="text-align: center;">Fecha de recepción:<br><br>______ / ______ / ________</p>
        </div>
    </div>

</body>
</html>

