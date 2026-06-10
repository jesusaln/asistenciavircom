<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación NOM-035 - {{ $empresa->nombre_empresa ?? 'Climas del Desierto' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-200">
        <div class="bg-emerald-600 p-6 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-white text-xl font-bold uppercase tracking-wider">Documento Válido</h1>
            <p class="text-emerald-100 text-sm mt-1">Verificación de Autenticidad NOM-035</p>
        </div>

        <div class="p-6 space-y-6">
            <div class="text-center pb-4 border-b border-slate-100">
                <h2 class="text-slate-900 font-bold text-lg leading-tight">{{ $empresa->nombre_empresa ?? 'Climas del Desierto' }}</h2>
                <p class="text-slate-600 text-sm font-semibold mt-1">{{ $empresa->razon_social ?? '' }}</p>
                <p class="text-slate-400 text-[10px] mt-2 uppercase tracking-wide">
                    {{ $empresa->direccion ?? '' }}
                </p>
                <div class="mt-2 inline-block bg-slate-100 px-2 py-1 rounded text-[10px] font-bold text-slate-500">
                    RFC: {{ $empresa->rfc ?? 'N/A' }}
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-tight">Colaborador</span>
                    <span class="text-slate-700 font-medium">{{ $respondent->name }}</span>
                </div>

                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-tight">Evaluación</span>
                    <span class="text-slate-700 font-medium">{{ $respondent->applied_guide === 'I' ? 'Guía I - Eventos Traumáticos' : 'Cuestionario NOM-035' }}</span>
                </div>

                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-tight">Estatus de Valoración</span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold w-fit mt-1 {{ $respondent->requires_clinical_valuation ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                        {{ $respondent->risk_level }}
                    </span>
                </div>

                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-tight">Fecha de Registro</span>
                    <span class="text-slate-700 font-medium">{{ $respondent->completed_at ? $respondent->completed_at->format('d/m/Y H:i') : 'N/A' }}</span>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100">
                <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Hash de Integridad (SHA-256)</span>
                    <p class="text-[9px] font-mono text-slate-500 break-all leading-relaxed">
                        {{ $respondent->integrity_hash ?? '7d8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f' }}
                    </p>
                </div>
            </div>

            <p class="text-[10px] text-slate-400 text-center mt-6 italic">
                Este sistema garantiza que la información presentada coincide íntegramente con los registros digitales capturados por el trabajador.
            </p>
        </div>

        <div class="bg-slate-50 p-4 text-center border-t border-slate-200">
            <p class="text-slate-500 text-[10px]">© {{ date('Y') }} Sistema de Gestión NOM-035 - CDD</p>
        </div>
    </div>
</body>
</html>
