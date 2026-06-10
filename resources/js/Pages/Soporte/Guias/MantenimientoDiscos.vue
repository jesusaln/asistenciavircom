<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout title="SOP: Mantenimiento Avanzado de Almacenamiento">
        <Head title="SOP: Discos Avanzado" />

        <div class="min-h-screen bg-slate-950 text-slate-300 py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-6xl mx-auto">
                <!-- Header del Procedimiento -->
                <div class="mb-10 text-center">
                    <span class="inline-block py-1 px-3 rounded-full bg-blue-500/10 text-blue-400 text-xs font-black uppercase tracking-widest border border-blue-500/20 mb-4">
                        Standard Operating Procedure (SOP-D01)
                    </span>
                    <h1 class="text-4xl font-black text-white tracking-tight mb-4">Protocolo Avanzado de Mantenimiento de Discos</h1>
                    <p class="text-slate-400 max-w-2xl mx-auto text-lg">Guía técnica detallada para el diagnóstico, reparación y optimización de unidades de almacenamiento en entornos de producción.</p>
                </div>

                <!-- Advertencia Critica -->
                <div class="bg-rose-500/10 border-l-4 border-rose-500 p-6 rounded-r-xl mb-12 flex gap-6 items-start">
                    <div class="text-3xl">🛡️</div>
                    <div>
                        <h3 class="text-lg font-bold text-rose-400 mb-2">Protocolo de Seguridad: "Backup First"</h3>
                        <p class="text-slate-300 text-sm leading-relaxed">
                            Antes de ejecutar cualquier comando de reparación de sistema de archivos (chkdsk, fsck) o manipulación de particiones, 
                            <strong>es obligatorio verificar la integridad de la última copia de seguridad</strong>. Las reparaciones intensivas en discos dañados físicamente pueden precipitar la pérdida total de datos.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- 1. HDD / SATA Mecánico -->
                    <div class="group bg-slate-900 rounded-[2rem] border border-slate-800 hover:border-amber-500/50 transition-all duration-300 shadow-2xl overflow-hidden">
                        <div class="bg-slate-800/50 p-6 border-b border-slate-700/50 flex items-center justify-between">
                            <h2 class="text-xl font-bold text-white flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full bg-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.5)]"></span>
                                HDD Mecánico / SATA
                            </h2>
                            <span class="text-xs font-mono text-slate-500">Legacy Protocol</span>
                        </div>
                        <div class="p-8 space-y-6">
                            <!-- Surface Scan -->
                            <div>
                                <h3 class="text-amber-400 font-bold text-sm uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                    Diagnóstico Profundo (Windows)
                                </h3>
                                <div class="bg-black rounded-xl p-4 font-mono text-xs text-green-400 border border-slate-700/50 shadow-inner">
                                    <p class="mb-2 text-slate-500"># Verificar integridad física y lógica (Requiere reinicio)</p>
                                    <p>> chkdsk C: /f /r /b</p>
                                </div>
                                <ul class="mt-2 text-xs text-slate-400 space-y-1 ml-1">
                                    <li><code class="text-slate-300">/f</code>: Corrige errores del sistema de archivos.</li>
                                    <li><code class="text-slate-300">/r</code>: Localiza sectores defectuosos y recupera info legible.</li>
                                    <li><code class="text-slate-300">/b</code>: Vuelve a evaluar los clústeres defectuosos (Sólo NTFS).</li>
                                </ul>
                            </div>

                            <!-- Linux Smart Checks -->
                            <div>
                                <h3 class="text-amber-400 font-bold text-sm uppercase tracking-wider mb-3">Linux Servers (Debian/RHEL)</h3>
                                <div class="bg-black rounded-xl p-4 font-mono text-xs text-green-400 border border-slate-700/50 shadow-inner overflow-x-auto">
                                    <p class="mb-1 text-slate-500"># Test corto (no intrusivo)</p>
                                    <p class="mb-3">$ smartctl -t short /dev/sda</p>
                                    
                                    <p class="mb-1 text-slate-500"># Ver atributos críticos (Seek_Error, Reallocated)</p>
                                    <p>$ smartctl -A /dev/sda | grep -E "Reallocated|Spin|Seek"</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. SSD / NVMe -->
                    <div class="group bg-slate-900 rounded-[2rem] border border-slate-800 hover:border-blue-500/50 transition-all duration-300 shadow-2xl overflow-hidden">
                        <div class="bg-slate-800/50 p-6 border-b border-slate-700/50 flex items-center justify-between">
                            <h2 class="text-xl font-bold text-white flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.5)]"></span>
                                SSD & NVMe Enterprise
                            </h2>
                            <span class="text-xs font-mono text-slate-500">Flash Storage</span>
                        </div>
                        <div class="p-8 space-y-6">
                            <!-- TRIM & Overprovisioning -->
                            <div>
                                <h3 class="text-blue-400 font-bold text-sm uppercase tracking-wider mb-3">Validación de TRIM</h3>
                                <div class="bg-black rounded-xl p-4 font-mono text-xs text-green-400 border border-slate-700/50 shadow-inner">
                                    <p class="mb-1 text-slate-500"># Verificar estado (Windows Admin)</p>
                                    <p class="mb-3">> fsutil behavior query DisableDeleteNotify</p>
                                    <p class="text-slate-500 italic border-l-2 border-slate-700 pl-2">
                                        Resultado = 0 (TRIM Activado ✅)<br>
                                        Resultado = 1 (TRIM Desactivado ❌)
                                    </p>
                                </div>
                            </div>

                            <!-- Firmware Check -->
                            <div class="bg-blue-900/10 rounded-xl p-4 border border-blue-500/20">
                                <h3 class="font-bold text-blue-300 text-sm mb-2">⚡ Actualización de Firmware</h3>
                                <p class="text-xs text-slate-400 mb-2">
                                    La mayoría de problemas de latencia o "freezes" en SSDs se resuelven actualizando el microcódigo. Usar tools oficiales:
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <span class="px-2 py-1 bg-slate-800 rounded text-[10px] text-slate-300 border border-slate-600">Samsung Magician</span>
                                    <span class="px-2 py-1 bg-slate-800 rounded text-[10px] text-slate-300 border border-slate-600">WD Dashboard</span>
                                    <span class="px-2 py-1 bg-slate-800 rounded text-[10px] text-slate-300 border border-slate-600">Crucial Executive</span>
                                </div>
                            </div>

                            <!-- Life Remaining -->
                            <div>
                                <h3 class="text-blue-400 font-bold text-sm uppercase tracking-wider mb-3">Salud de Celdas (NVMe CLI Linux)</h3>
                                <div class="bg-black rounded-xl p-4 font-mono text-xs text-green-400 border border-slate-700/50 shadow-inner">
                                    <p>$ nvme smart-log /dev/nvme0n1 | grep "percentage_used"</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Apple Silicon (M1/M2/M3) -->
                    <div class="group bg-slate-900 rounded-[2rem] border border-slate-800 hover:border-purple-500/50 transition-all duration-300 shadow-2xl overflow-hidden lg:col-span-2">
                        <div class="bg-slate-800/50 p-6 border-b border-slate-700/50 flex items-center justify-between">
                            <h2 class="text-xl font-bold text-white flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full bg-purple-500 shadow-[0_0_10px_rgba(168,85,247,0.5)]"></span>
                                Apple Silicon Ecosystem (macOS)
                            </h2>
                            <span class="text-xs font-mono text-slate-500">APFS Optimization</span>
                        </div>
                        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h3 class="text-purple-400 font-bold text-sm uppercase tracking-wider mb-3">Verificación de Contenedores APFS</h3>
                                <p class="text-xs text-slate-400 mb-4">
                                    En M1/M2, el SSD está soldado. La integridad del contenedor APFS es crítica. Usar Terminal para logs detallados que "Utilidad de Discos" oculta.
                                </p>
                                <div class="bg-black rounded-xl p-4 font-mono text-xs text-green-400 border border-slate-700/50 shadow-inner">
                                    <p class="mb-1 text-slate-500"># Listar discos para identificar identifier (disk0s1, etc)</p>
                                    <p class="mb-3">$ diskutil list</p>
                                    
                                    <p class="mb-1 text-slate-500"># Verificar volumen sin desmontar (Live Verification)</p>
                                    <p>$ diskutil verifyVolume /</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50">
                                    <h4 class="font-bold text-white text-sm mb-1">Modo de Recuperación (Recovery)</h4>
                                    <p class="text-xs text-slate-400">
                                        Para reparar errores que `verifyVolume` reporte:
                                    </p>
                                    <ol class="list-decimal list-inside text-xs text-slate-400 mt-2 space-y-1">
                                        <li>Apagar el Mac por completo.</li>
                                        <li>Mantener presionado el botón <strong>Power</strong> hasta ver "Cargando opciones de inicio".</li>
                                        <li>Seleccionar "Opciones" > Continuar.</li>
                                        <li>Abrir Utilidad de Discos > Ejecutar <strong>First Aid</strong> en el volumen "Data".</li>
                                    </ol>
                                </div>

                                <div class="bg-purple-900/10 rounded-xl p-4 border border-purple-500/20">
                                    <h4 class="font-bold text-purple-300 text-sm mb-1">💡 Tip Pro: Snapshots Locales</h4>
                                    <p class="text-xs text-slate-400">
                                        Si el disco parece lleno pero no lo está, purgar snapshots de Time Machine locales:
                                    </p>
                                    <p class="font-mono text-xs text-green-400 mt-2 bg-black/50 p-1 rounded inline-block">tmutil listlocalsnapshots /</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer de Logs -->
                <div class="mt-12 bg-slate-900 rounded-2xl p-6 border border-slate-800 text-center">
                    <h3 class="text-sm font-bold text-slate-400 mb-2">Análisis Forense de Fallos</h3>
                    <p class="text-xs text-slate-500 max-w-3xl mx-auto">
                        Si el disco "desaparece" aleatoriamente o el sistema se congela, buscar en logs de sistema: 
                        <span class="text-slate-300 font-mono">Event Viewer > Windows Logs > System (Filtrar por Source: Disk)</span> o 
                        <span class="text-slate-300 font-mono">/var/log/syslog (Buscar "I/O error" o "ATA bus error")</span>.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
