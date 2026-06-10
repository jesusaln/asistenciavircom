<template>
  <Head title="WhatsApp Inbox" />

  <AppLayout :hideNavigation="true">
    <div class="h-screen flex flex-col overflow-hidden bg-[var(--ui-surface)]">
      <!-- Header Seccion -->
      <div class="flex items-center justify-between p-4 border-b border-white/5 bg-white/[0.02] shrink-0">
        <div class="flex items-center gap-6">
          <Link :href="route('dashboard')" class="p-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition-all duration-200 text-white/70" title="Volver al Dashboard">
            <FontAwesomeIcon icon="arrow-left" />
          </Link>
          <div>
            <h1 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                <div class="p-2 bg-brand-500/20 rounded-xl">
                 <FontAwesomeIcon icon="comments" class="text-amber-400" />
               </div>
              WhatsApp Inbox
              <div v-if="chatbotConfig?.enabled" class="flex items-center gap-1.5 px-2 py-0.5 bg-sky-500/10 border border-sky-500/20 rounded-full shadow-[0_0_15px_rgba(14,165,233,0.1)]">
                 <span class="relative flex h-1.5 w-1.5">
                     <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-75"></span>
                     <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-sky-500"></span>
                 </span>
                 <span class="text-[9px] font-black uppercase text-sky-400 tracking-tighter">OpenClaw 🦞</span>
               </div>
            </h1>
          </div>
        </div>
        
        <div class="flex items-center gap-2">
          <button @click="toggleChatbotMode" 
                  :disabled="togglingChatbot"
                  class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-white/10 transition-all duration-200"
                   :class="chatbotConfig?.enabled 
                     ? 'bg-sky-500/10 border-sky-500/20 text-sky-400 hover:bg-sky-500/20 shadow-[0_0_20px_rgba(14,165,233,0.1)]' 
                     : 'bg-white/5 text-white/40 hover:bg-white/10 hover:text-white/60'">
            <FontAwesomeIcon :icon="chatbotConfig?.enabled ? 'robot' : 'power-off'" :class="{'animate-pulse': togglingChatbot}" />
            <span class="text-[10px] font-black uppercase tracking-wider">{{ chatbotConfig?.enabled ? 'OpenClaw ON' : 'Activar OpenClaw' }}</span>
          </button>
          <div class="hidden md:flex items-center rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-[10px] font-black uppercase tracking-wider text-white/50">
            {{ filteredChats.length }} conversaciones
          </div>
          <button 
            @click="fetchChats" 
            class="p-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition-all duration-200 text-white/70"
            :title="`Actualizado: ${formatTime(new Date())}`"
          >
            <FontAwesomeIcon icon="sync-alt" :class="{'animate-spin': loading}" />
          </button>
          <button 
            @click="showQRManager = true" 
            class="p-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition-all duration-200 text-white/70"
            title="Gestión de Respuestas Rápidas"
          >
            <FontAwesomeIcon icon="bolt" />
          </button>
        </div>
      </div>

      <!-- Confirmación: envío de cotización desde el módulo Cotizaciones (no se envía hasta confirmar aquí) -->
      <div
        v-if="showPendingCotizacionBanner"
        class="shrink-0 px-4 py-3 border-b border-brand-500/25 bg-brand-500/[0.08] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
      >
        <div class="min-w-0 text-sm text-brand-100/95">
          <p class="font-black text-brand-200/95 tracking-tight">
            Cotización {{ pendingCotizacionLabel }}
          </p>
          <p v-if="props.pendingCotizacion?.cliente_nombre" class="text-xs text-brand-100/70 mt-0.5">
            Cliente: {{ props.pendingCotizacion.cliente_nombre }}
          </p>
          <p v-if="pendingCotizacionBlockReason" class="text-xs text-rose-300/90 mt-1">
            {{ pendingCotizacionBlockReason }}
          </p>
          <p v-else-if="!is24HourWindowOpen" class="text-xs text-brand-300 mt-1">
            ⚠️ La ventana de 24h está cerrada. Debe enviar una plantilla primero.
          </p>
          <p v-else class="text-xs text-brand-100/60 mt-1">
            ¿Enviar el enlace al PDF por WhatsApp Business ahora?
          </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <button
            type="button"
             class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wide bg-white/10 text-white/70 hover:bg-white/15 border border-white/10 transition-colors"
            @click="dismissPendingCotizacion"
          >
            No enviar
          </button>
          <button
            type="button"
             class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wide bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            :disabled="!!pendingCotizacionBlockReason || !is24HourWindowOpen || sendingPendingCotizacion"
            @click="confirmSendPendingCotizacion"
          >
            {{ sendingPendingCotizacion ? 'Enviando…' : 'Sí, enviar' }}
          </button>
        </div>
      </div>

      <!-- Main Chat Area -->
      <div class="flex-1 flex overflow-hidden">
        
        <!-- Sidebar: Chat List -->
        <div class="w-full md:w-80 lg:w-96 flex flex-col bg-white/[0.01] border-r border-white/[0.05] overflow-hidden shrink-0">
          <div class="p-4 border-b border-white/[0.05] bg-white/[0.02]">
            <div class="relative">
              <FontAwesomeIcon icon="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-white/20 text-xs" />
              <input 
                v-model="searchQuery"
                type="text" 
                placeholder="Buscar conversación..."
                class="w-full bg-white/[0.05] border border-white/[0.05] rounded-xl py-2 pl-9 pr-4 text-xs text-white placeholder:text-white/20 focus:outline-none focus:ring-1 focus:ring-brand-500/30 transition-all"
              >
            </div>
            <!-- Status Tabs -->
            <div class="flex gap-1 p-1 bg-white/5 rounded-xl mt-3">
                <button v-for="s in ['open', 'closed', 'all']" :key="s"
                        @click="statusFilter = s"
                        :class="[
                           'flex-1 py-1 text-[8px] font-black uppercase tracking-wide rounded-xl transition-all',
                           statusFilter === s ? 'bg-brand-500 text-black shadow-xl' : 'text-white/30 hover:text-white/60'
                        ]">
                    {{ s === 'open' ? 'Abiertos' : (s === 'closed' ? 'Cerrados' : 'Todos') }}
                </button>
            </div>
            <p class="mt-3 text-[10px] font-bold uppercase tracking-wider text-white/25">
              {{ filteredChats.length === chats.length ? 'Bandeja completa' : `Resultados: ${filteredChats.length}` }}
            </p>
          </div>

          <div class="flex-1 overflow-y-auto custom-scrollbar">
            <div v-if="filteredChats.length === 0" class="flex flex-col items-center justify-center h-40 text-white/20 px-6 text-center">
              <FontAwesomeIcon icon="inbox" class="text-3xl mb-3 opacity-10" />
              <p class="text-xs font-medium uppercase tracking-wide">No hay mensajes</p>
            </div>

            <div 
              v-for="chat in filteredChats" 
              :key="chat.wa_id"
              @click="selectChat(chat)"
              :class="[
                'p-4 flex items-center gap-4 cursor-pointer transition-all duration-500 border-b border-white/[0.02] hover:bg-white/[0.05] group',
                selectedChat?.wa_id === chat.wa_id ? 'bg-brand-500/10 border-r-4 border-r-emerald-500' : ''
              ]"
            >
              <div class="relative flex-shrink-0">
                <div :class="[
                  'w-10 h-10 rounded-2xl flex items-center justify-center text-white font-bold border border-white/10 shadow-xl bg-gradient-to-tr transition-transform group-hover:scale-105',
                  getAvatarColor(chat.from_name || chat.wa_id)
                ]">
                  {{ getInitials(chat.from_name || chat.wa_id) }}
                </div>
                <!-- Status Dot -->
                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-[var(--ui-surface)] rounded-full flex items-center justify-center">
                  <div class="w-2 h-2 bg-emerald-500 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                </div>
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex justify-between items-baseline mb-1">
                  <h3 class="text-sm font-bold text-white/90 truncate pr-2">{{ String(chat.from_name || chat.wa_id) }}</h3>
                  <span class="text-[9px] text-white/30 font-bold uppercase">{{ formatChatDate(chat.last_message_at) }}</span>
                </div>
                <div class="flex items-center justify-between gap-1.5 overflow-hidden">
                   <div class="flex items-center gap-1.5 min-w-0">
                      <FontAwesomeIcon v-if="chat.direction === 'outbound'" icon="check-double" class="text-emerald-500 text-[9px] flex-shrink-0" />
                      <p class="text-[11px] text-white/40 truncate">{{ chat.last_message || 'Archivo multimedia' }}</p>
                   </div>
                   <!-- Status / Agent Badge -->
                   <div class="flex items-center gap-1">
                       <div v-if="chat.status === 'closed'" class="px-1.5 py-0.5 bg-rose-500/10 text-rose-400 text-[7px] font-black uppercase rounded-xl border border-rose-500/20">Cerrado</div>
                      <div v-if="chat.assigned_agent" class="w-4 h-4 rounded-full bg-white/10 flex items-center justify-center text-[7px] text-white/50 border border-white/5" :title="`Asignado a: ${String(chat.assigned_agent.name || '')}`">
                          {{ String(chat.assigned_agent.name || '').substring(0,1).toUpperCase() }}
                      </div>
                   </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Chat View -->
        <div class="flex-1 flex flex-col bg-white/[0.01] relative overflow-hidden">
          
          <template v-if="selectedChat">
            <!-- Chat Header -->
            <div class="p-4 border-b border-white/[0.05] bg-white/[0.02] flex items-center justify-between">
              <div class="flex items-center gap-4">
                <button @click="selectedChat = null; page=1; fetchChats()" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-white/50 hover:text-white transition-all shrink-0" title="Volver a la bandeja">
                  <FontAwesomeIcon icon="arrow-left" />
                </button>
                <div class="w-10 h-10 rounded-xl bg-sky-500/10 flex items-center justify-center text-sky-400 font-bold border border-sky-500/20">
                   {{ selectedChat.from_name ? selectedChat.from_name.substring(0,2).toUpperCase() : '?' }}
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h2 class="text-sm font-black text-white leading-none">{{ String(selectedChat.from_name || selectedChat.wa_id) }}</h2>
                    <span v-if="selectedChat.status === 'closed'" class="px-1.5 py-0.5 bg-rose-500/10 text-rose-400 text-[8px] font-black uppercase rounded-xl border border-rose-500/20 tracking-wider">Cerrado</span>
                  </div>
                   <p class="text-[10px] text-sky-500/70 mt-1 flex items-center gap-1.5 font-bold uppercase tracking-wide">
                     <span class="w-1.5 h-1.5 bg-sky-500 rounded-full"></span>
                     En línea
                   </p>
                  <!-- Tags Display -->
                  <div class="flex flex-wrap gap-1 mt-2">
                      <div v-for="tag in (selectedChat.tags || [])" :key="tag" 
                            class="px-1.5 py-0.5 bg-brand-500/10 text-brand-400 text-[7px] font-black uppercase rounded-xl border border-brand-500/20 flex items-center gap-1 group">
                          {{ tag }}
                          <FontAwesomeIcon icon="times" @click="removeTag(tag)" class="cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity" />
                      </div>
                      <input 
                        @keydown.enter="addTag"
                        placeholder="+ tag"
                        class="bg-transparent border-none p-0 text-[7px] font-black uppercase text-white/30 focus:ring-0 w-12"
                      >
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-2">
                 <!-- Agent Selector -->
                 <div class="relative group">
                    <button class="flex items-center gap-2 px-3 py-1.5 bg-white/5 border border-white/10 rounded-xl text-[10px] text-white/40 hover:text-white transition-all">
                        <FontAwesomeIcon icon="user" class="text-[9px]" />
                        <span>{{ selectedChat.assigned_agent ? selectedChat.assigned_agent.name : 'Sin asignar' }}</span>
                    </button>
                    <div class="absolute right-0 top-full mt-2 w-48 bg-[var(--ui-surface)] border border-white/10 rounded-2xl shadow-xl z-50 overflow-hidden hidden group-hover:block backdrop-blur-xl">
                        <div v-for="agent in agents" :key="agent.id" 
                             @click="assignAgent(agent.id)"
                             class="p-2.5 text-[10px] text-white/50 hover:bg-slate-500/10 hover:text-white cursor-pointer transition-all border-b border-white/5">
                            {{ agent.name }}
                        </div>
                        <div @click="assignAgent(null)" class="p-2.5 text-[10px] text-rose-400/50 hover:bg-slate-500/10 hover:text-rose-400 cursor-pointer transition-all">Desasignar</div>
                    </div>
                 </div>

                 <!-- Status Toggle -->
                 <button 
                  @click="toggleChatStatus(selectedChat.status === 'closed' ? 'open' : 'closed')"
                  class="p-2.5 rounded-xl transition-all duration-200 border border-white/10" 
                   :class="selectedChat.status === 'closed' ? 'bg-rose-500/10 text-rose-400' : 'text-white/20 hover:text-emerald-400 hover:bg-white/5'"
                  :title="selectedChat.status === 'closed' ? 'Reabrir chat' : 'Cerrar chat'"
                >
                    <FontAwesomeIcon :icon="selectedChat.status === 'closed' ? 'lock-open' : 'lock'" />
                 </button>

                  <!-- Bot Activation -->
                  <button 
                   v-if="chatbotConfig?.enabled"
                   @click="startChatbot"
                   :disabled="!is24HourWindowOpen || startingBot"
                   class="p-2.5 rounded-xl transition-all duration-200 border border-white/10" 
                   :class="!is24HourWindowOpen ? 'opacity-40 cursor-not-allowed text-white/10' : 'text-white/20 hover:text-sky-400 hover:bg-white/5'"
                   :title="is24HourWindowOpen ? 'Iniciar Asistente Virtual (Bot)' : 'No se puede iniciar el bot (fuera de la ventana de 24h)'"
                 >
                    <FontAwesomeIcon :icon="startingBot ? 'spinner' : 'robot'" :class="{'animate-spin': startingBot}" />
                  </button>

                  <!-- Bot Per-Conversation Toggle -->
                  <button
                   v-if="chatbotConfig?.enabled && selectedChat"
                   @click="toggleBotConversation"
                   class="p-2.5 rounded-xl transition-all duration-200 border border-white/10"
                   :class="selectedChat.chatbot_disabled ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'text-white/20 hover:text-emerald-400 hover:bg-white/5'"
                   :title="selectedChat.chatbot_disabled ? 'Bot desactivado para este cliente — Activar' : 'Bot activo para este cliente — Desactivar'"
                  >
                    <FontAwesomeIcon icon="robot" class="opacity-50" />
                    <span v-if="selectedChat.chatbot_disabled" class="absolute -top-1 -right-1 w-3 h-3 bg-rose-500 rounded-full flex items-center justify-center">
                      <FontAwesomeIcon icon="times" class="text-[6px] text-white" />
                    </span>
                  </button>

                 <button 
                  @click="showInfo = !showInfo"
                  class="p-2.5 rounded-xl transition-all duration-200 border border-white/10" 
                  :class="showInfo ? 'bg-brand-500/20 text-emerald-400' : 'text-white/20 hover:text-white hover:bg-white/5'"
                  title="Información del contacto"
                >
                    <FontAwesomeIcon icon="id-card" />
                 </button>
              </div>
            </div>

            <!-- Messages Area -->
            <div ref="messagesBox" class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6 custom-scrollbar bg-black/10">
                <div v-if="messagesError" class="rounded-2xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                 {{ messagesError }}
               </div>

               <div v-for="(msg, index) in messages" :key="msg.id || index" 
                    :class="['flex w-full', msg.direction === 'inbound' ? 'justify-start' : 'justify-end']">
                  
                  <div :class="[
                    'max-w-[80%] md:max-w-[70%] lg:max-w-[60%] p-4 rounded-2xl relative transition-all duration-200 group',
                    msg.is_internal 
                      ? 'bg-brand-100/10 border border-brand-500/30 text-amber-200' 
                      : (msg.direction === 'inbound' 
                        ? 'bg-white/[0.05] text-white rounded-tl-none border border-white/[0.05] shadow-xl' 
                        : 'bg-emerald-600 text-white rounded-tr-none shadow-[0_10px_30px_rgba(5,150,105,0.2)]')
                  ]">
                    <!-- AGENT NAME (For outbound/internal) -->
                    <div v-if="(msg.direction === 'outbound' || msg.is_internal) && msg.user" 
                         class="text-[9px] font-black uppercase tracking-wide mb-1.5 opacity-50 flex items-center gap-1.5">
                        <FontAwesomeIcon icon="user-circle" />
                        {{ String(msg.user.name || 'Desconocido') }}
                        <span v-if="msg.is_internal" class="bg-brand-500/20 text-brand-400 px-1.5 py-0.5 rounded-xl text-[7px]">NOTA INTERNA</span>
                    </div>

                    <!-- AUDIO PLAYER -->
                    <div v-if="msg.type === 'audio'" class="flex items-center gap-2 min-w-[240px]">
                         <div class="w-10 h-10 rounded-full bg-sky-500/20 flex items-center justify-center text-sky-400 flex-shrink-0">
                             <FontAwesomeIcon icon="microphone-alt" />
                        </div>
                        <div class="flex-1">
                          <audio controls class="h-8 w-full opacity-80 hover:opacity-100 transition-opacity contrast-125 filter invert">
                              <source :src="mediaProxyUrl('audio', msg.message_id)" type="audio/ogg">
                              No soportado
                          </audio>
                        </div>
                    </div>

                    <!-- DOCUMENT PLAYER -->
                    <div v-else-if="msg.type === 'document'" class="flex items-center gap-2 min-w-[200px] p-2 bg-white/5 rounded-xl border border-white/5">
                         <div class="w-10 h-10 rounded-xl bg-sky-500/20 flex items-center justify-center text-sky-400 flex-shrink-0">
                             <FontAwesomeIcon icon="file-pdf" />
                        </div>
                        <div class="flex-1 min-w-0">
                           <p class="text-[10px] font-black uppercase tracking-wide text-white/40 truncate">Documento PDF</p>
                           <a :href="mediaProxyUrl('image', msg.message_id)" target="_blank" class="text-xs font-bold text-sky-400 hover:underline truncate block">Ver Documento</a>
                        </div>
                    </div>

                    <!-- IMAGE / STICKER PLAYER -->
                    <div v-else-if="msg.type === 'image' || msg.type === 'sticker'" class="relative">
                        <img 
                            :src="mediaProxyUrl('image', msg.message_id)" 
                            :class="[
                                'rounded-xl max-w-full h-auto object-contain cursor-zoom-in hover:brightness-110 transition-all',
                                msg.type === 'sticker' ? 'max-h-32' : 'max-h-64'
                            ]"
                            @click="openImageModal(mediaProxyUrl('image', msg.message_id))"
                        >
                        <p v-if="msg.body && msg.body !== '🖼️ [Imagen]' && msg.body !== '🏷️ [Sticker]'" class="mt-2 text-sm">{{ msg.body }}</p>
                        <div v-if="msg.direction === 'inbound' && msg.type === 'image' && selectedChat">
                          <button v-if="!evidenciasGuardadas[msg.message_id]"
                            @click.stop="openGuardarEvidencia(msg.message_id)"
                            class="mt-2 w-full py-1.5 px-3 text-xs font-bold uppercase rounded-lg bg-amber-500/20 text-amber-400 border border-amber-500/30 hover:bg-amber-500/30 transition-all"
                          >
                            📸 Guardar en cita
                          </button>
                          <div v-else class="mt-2 w-full py-1.5 px-3 text-xs font-bold uppercase rounded-lg bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-center">
                            ✅ Evidencia guardada
                          </div>
                        </div>
                    </div>

                    <!-- MAP PREVIEW -->
                    <div v-else-if="msg.body && msg.body.includes('📍 [Ubicación:')" class="w-full">
                         <div class="mb-3 rounded-xl overflow-hidden border border-white/10 h-48 w-full md:w-80 shadow-inner group-hover:border-brand-500/30 transition-all duration-500">
                             <iframe 
                                width="100%" 
                                height="100%" 
                                frameborder="0" 
                                style="border:0" 
                                :src="`https://maps.google.com/maps?q=${extractCoords(msg.body)}&z=15&output=embed`" 
                                allowfullscreen>
                             </iframe>
                         </div>
                         <p class="text-sm leading-relaxed whitespace-pre-wrap break-words" v-html="formatMessageBody(msg)"></p>
                    </div>

                    <!-- TEXT / OTHER -->
                    <p v-else class="text-sm leading-relaxed whitespace-pre-wrap break-words" v-html="formatMessageBody(msg)"></p>
                    
                    <!-- Footer Info -->
                    <div :class="['flex items-center gap-2 mt-2', msg.direction === 'inbound' ? 'text-white/20' : 'text-emerald-200/50']">
                       <span class="text-[9px] font-bold uppercase tracking-wide">{{ formatChatDate(msg.created_at) }}</span>
                      <FontAwesomeIcon v-if="msg.direction === 'outbound'" 
                                      :icon="getStatusIcon(msg.status)" 
                                      class="text-[8px]" 
                                       :class="{'text-sky-300': msg.status === 'read'}" />
                    </div>

                    <!-- Subtle reflection effect for outbound -->
                    <div v-if="msg.direction === 'outbound'" class="absolute inset-0 bg-gradient-to-tr from-white/10 to-transparent opacity-30 rounded-2xl pointer-events-none"></div>
                  </div>
               </div>

               <div v-if="sending" class="flex justify-end w-full animate-pulse">
                  <div class="bg-emerald-600/50 text-white/50 p-4 rounded-2xl rounded-tr-none text-xs font-bold uppercase tracking-wide">
                     Enviando...
                  </div>
               </div>
            </div>

            <!-- Input Area -->
            <div class="p-4 bg-white/5 border-t border-white/5 backdrop-blur-md relative">
                <!-- WhatsApp Window Warning (24h) -->
                <div v-if="selectedChat && !is24HourWindowOpen && !loadingMessages" class="mb-4 p-4 rounded-2xl bg-brand-500/10 border border-brand-500/20 flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center text-brand-500 shrink-0">
                            <FontAwesomeIcon icon="clock-rotate-left" />
                        </div>
                        <div class="flex-1 min-w-0">
                             <h4 class="text-xs font-black text-brand-200 uppercase tracking-wide">Ventana de 24h Cerrada</h4>
                            <p class="text-[10px] text-brand-100/60 leading-relaxed mt-1">
                                Han pasado más de 24 horas desde el último mensaje del cliente. Para reabrir la conversación, debe enviar una 
                                <span class="font-black text-amber-400">Plantilla Autorizada</span>.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-1">
                        <button @click="showInternalNote = true" class="px-3 py-1.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-[8px] font-black uppercase text-white/50 transition-all">
                            Dejar Nota Interna
                        </button>
                    </div>
                </div>

                <!-- Window Closing Soon Hint -->
                <div v-if="selectedChat && is24HourWindowOpen && timeUntilWindowCloses" class="mb-4 flex items-center justify-between px-4 py-2 bg-brand-500/5 rounded-xl border border-brand-500/10">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                        <span class="text-[9px] font-black text-emerald-400/80 uppercase tracking-wide">Sesión de Servicio Activa</span>
                    </div>
                    <span class="text-[9px] font-bold text-white/30 uppercase">Cierra en: {{ timeUntilWindowCloses }}</span>
                </div>

                <div v-if="composerError" class="mb-3 rounded-xl border border-rose-500/20 bg-rose-500/10 px-3 py-2 text-xs text-rose-200">
                    {{ composerError }}
                </div>

                <!-- Quick Responses Picker -->
                <div v-if="filteredQuickResponses.length > 0" class="absolute bottom-full left-4 mb-2 p-2 bg-slate-900 border border-white/10 rounded-2xl shadow-xl z-50 w-64 max-h-48 overflow-y-auto custom-scrollbar">
                    <div v-for="qr in filteredQuickResponses" :key="qr.id" 
                         @click="useQuickResponse(qr)"
                         class="p-2 text-xs text-white/70 hover:bg-slate-500/10 hover:text-white cursor-pointer rounded-xl border-b border-white/5 last:border-0 transition-all">
                        <span class="font-black text-emerald-400 mr-2">{{ qr.shortcut }}</span>
                        <span class="truncate">{{ qr.message }}</span>
                    </div>
                </div>

                <!-- Emoji Picker (Simple) -->
                <div v-if="showEmojiPicker" class="absolute bottom-full left-4 mb-2 p-3 bg-slate-900 border border-white/10 rounded-2xl shadow-xl z-50 w-64">
                    <div class="grid grid-cols-7 gap-1">
                        <button v-for="emoji in popularEmojis" :key="emoji" @click="addEmoji(emoji)" class="w-10 h-10 flex items-center justify-center hover:bg-white/10 rounded-xl transition-colors text-lg">
                            {{ emoji }}
                        </button>
                    </div>
                </div>

                <!-- Sticker Picker (Simple) -->
                <div v-if="showStickerPicker" class="absolute bottom-full left-16 mb-2 p-3 bg-slate-900 border border-white/10 rounded-2xl shadow-xl z-50 w-72">
                    <h4 class="text-[10px] font-bold uppercase tracking-wide text-white/30 mb-2 px-1">Stickers</h4>
                    <div class="grid grid-cols-4 gap-2 max-h-48 overflow-y-auto custom-scrollbar">
                        <button v-for="sticker in stickers" :key="sticker.url" @click="sendSticker(sticker.url)" class="p-1 hover:bg-white/10 rounded-xl transition-colors">
                            <img :src="sticker.url" class="w-full h-auto aspect-square object-contain">
                        </button>
                    </div>
                </div>

                <div class="flex items-end gap-3">
                    <div class="flex flex-col gap-2 mb-1 relative">
                        <!-- Trigger Button -->
                        <button 
                            @click="showComposerActions = !showComposerActions"
                            class="w-10 h-10 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-white/40 hover:text-emerald-400 hover:bg-white/10 transition-all duration-300"
                            :class="{'rotate-45 text-emerald-400 bg-white/10 border-emerald-500/30': showComposerActions}"
                            title="Más acciones"
                        >
                            <FontAwesomeIcon icon="plus" class="text-lg" />
                        </button>

                        <!-- Dynamic Actions Menu -->
                        <transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="translate-y-2 opacity-0 scale-95"
                            enter-to-class="translate-y-0 opacity-100 scale-100"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="translate-y-0 opacity-100 scale-100"
                            leave-to-class="translate-y-2 opacity-0 scale-95"
                        >
                            <div v-if="showComposerActions" 
                                 class="absolute bottom-full left-0 mb-4 flex flex-col gap-3 p-2 bg-[#0f172a]/95 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.5)] z-[60]">
                                
                                <input type="file" ref="fileInput" @change="onFileSelected" class="hidden" accept="image/*,application/pdf">
                                
                                <button @click="$refs.fileInput.click(); showComposerActions = false" 
                                        class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-sky-400 transition-all flex items-center justify-center group" 
                                        title="Adjuntar Archivo">
                                    <FontAwesomeIcon icon="paperclip" class="text-lg group-hover:scale-110 transition-transform" />
                                </button>
                                
                                <button @click="showEmojiPicker = !showEmojiPicker; showStickerPicker = false; showInternalNote = false; showComposerActions = false" 
                                        class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-brand-400 transition-all flex items-center justify-center group" 
                                        title="Emojis">
                                    <FontAwesomeIcon icon="smile" class="text-lg group-hover:scale-110 transition-transform" />
                                </button>
                                
                                <button @click="showStickerPicker = !showStickerPicker; showEmojiPicker = false; showInternalNote = false; showComposerActions = false" 
                                        class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-purple-400 transition-all flex items-center justify-center group" 
                                        title="Stickers">
                                    <FontAwesomeIcon icon="sticky-note" class="text-lg group-hover:scale-110 transition-transform" />
                                </button>
                                
                                <button @click="showInternalNote = !showInternalNote; showEmojiPicker = false; showStickerPicker = false; showComposerActions = false" 
                                        class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 transition-all flex items-center justify-center group" 
                                        :class="showInternalNote ? 'text-brand-400 bg-brand-500/10 border border-brand-500/20' : 'text-white/40 hover:text-amber-400'"
                                        title="Nota Interna">
                                    <FontAwesomeIcon icon="comment-dots" class="text-lg group-hover:scale-110 transition-transform" />
                                </button>
                                
                                <button @click="getAISuggestion(); showComposerActions = false" 
                                        class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-sky-400 transition-all flex items-center justify-center group" 
                                        :disabled="gettingAISuggestion"
                                        title="Sugerencia OpenClaw 🦞">
                                    <FontAwesomeIcon icon="robot" class="text-lg group-hover:scale-110 transition-transform" :class="{'animate-pulse text-sky-400': gettingAISuggestion}" />
                                </button>
                            </div>
                        </transition>
                    </div>

                    <div class="relative flex-1">
                      <div v-if="showInternalNote" class="absolute left-5 -top-2 px-2 py-0.5 bg-brand-500 text-[8px] font-black text-black rounded-xl uppercase tracking-wide z-10 shadow-xl">Nota Interna Privada</div>
                      <textarea
                          ref="messageInput"
                          v-model="newMessage"
                          @keydown.enter="sendIfMsg"
                          @click="showEmojiPicker = false; showStickerPicker = false"
                          rows="1"
                          placeholder="Escribe un mensaje..."
                          :class="[
                            'min-h-[48px] max-h-36 w-full resize-none rounded-3xl border px-5 py-3 text-sm text-white placeholder-white/20 shadow-inner transition-all focus:outline-none focus:ring-2',
                            showInternalNote 
                              ? 'bg-brand-500/10 border-brand-500/30 focus:border-brand-500/50 focus:ring-brand-500/30' 
                              : 'bg-white/5 border-white/10 focus:border-emerald-500/30 focus:ring-brand-500/30'
                          ]"
                      ></textarea>
                    </div>
                    
                    <button 
                        @click="sendMessage"
                        :disabled="!canSend"
                        class="w-11 h-11 rounded-full text-white flex items-center justify-center disabled:opacity-40 disabled:scale-95 transition-all shadow-xl active:scale-90"
                        :class="showInternalNote ? 'bg-brand-600 hover:bg-brand-700 shadow-brand-500/20' : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/20'"
                    >
                        <FontAwesomeIcon :icon="showInternalNote ? 'lock' : 'paper-plane'" />
                    </button>
                </div>

                <p class="mt-3 text-[10px] font-bold uppercase tracking-wider text-white/20">
                    Enter envia. Shift + Enter agrega salto de linea. Usa <button @click="insertPlaceholder('{nombre}')" type="button" class="text-emerald-400 hover:text-emerald-300 hover:underline cursor-pointer focus:outline-none transition-colors">{nombre}</button> o <button @click="insertPlaceholder('{nombre_completo}')" type="button" class="text-emerald-400 hover:text-emerald-300 hover:underline cursor-pointer focus:outline-none transition-colors">{nombre_completo}</button> para personalizar.
                </p>
            </div>
          </template>

          <!-- Empty State -->
          <div v-else class="flex-1 flex flex-col items-center justify-center text-white/20 p-12 text-center bg-black/20">
             <div class="relative mb-8">
                <div class="absolute inset-0 bg-brand-500/10 blur-[80px] rounded-full"></div>
                <FontAwesomeIcon icon="comments" class="text-6xl relative z-10 opacity-5" />
             </div>
             <h2 class="text-xl font-black text-white/40 mb-2 tracking-tight">Selecciona una conversación</h2>
             <p class="max-w-xs text-sm text-white/20">Haz clic en un chat de la izquierda para ver los mensajes y empezar a chatear.</p>
          </div>

          <!-- Loading Overlay -->
          <div v-if="loadingMessages" class="absolute inset-0 bg-[var(--ui-surface)]/80 backdrop-blur-sm flex items-center justify-center z-50">
             <div class="flex flex-col items-center">
                <div class="w-10 h-10 border-4 border-emerald-500/20 border-t-emerald-500 rounded-full animate-spin"></div>
                <span class="mt-4 text-[10px] uppercase font-black tracking-wider text-emerald-500">Cargando Chat</span>
             </div>
          </div>
        </div>

        <!-- Info Panel (Right) -->
        <transition 
          enter-active-class="transform transition ease-out duration-200" 
          enter-from-class="translate-x-full opacity-0" 
          enter-to-class="translate-x-0 opacity-100" 
          leave-active-class="transform transition ease-in duration-200" 
          leave-from-class="translate-x-0 opacity-100" 
          leave-to-class="translate-x-full opacity-0"
        >
          <div v-if="showInfo && selectedChat" class="hidden xl:flex w-80 flex-col bg-white/[0.02] border-l border-white/[0.05] overflow-hidden shrink-0">
              <div class="p-8 border-b border-white/[0.05] flex flex-col items-center text-center">
                  <div :class="[
                    'w-16 h-16 rounded-[32px] flex items-center justify-center text-3xl text-white font-black shadow-2xl mb-6 bg-gradient-to-tr transition-all duration-500 hover:rotate-6',
                    getAvatarColor(selectedChat.from_name || selectedChat.wa_id)
                  ]">
                    {{ getInitials(selectedChat.from_name || selectedChat.wa_id) }}
                  </div>
                  <h2 class="text-xl font-black text-white leading-tight mb-1">{{ String(selectedChat.from_name || 'Desconocido') }}</h2>
                  <div class="flex items-center gap-2 bg-sky-500/10 px-3 py-1 rounded-full border border-sky-500/20">
                      <div class="w-1.5 h-1.5 bg-sky-500 rounded-full animate-pulse"></div>
                      <p class="text-[10px] text-sky-400 font-black tracking-wide uppercase">Activo</p>
                  </div>
              </div>

              <div class="p-6 space-y-6 flex-1 overflow-y-auto custom-scrollbar">
                  <div>
                      <h4 class="text-[10px] font-black uppercase tracking-wider text-white/20 mb-4 border-b border-white/5 pb-2">Datos de contacto</h4>
                      <div class="space-y-5">
                          <div class="flex items-center gap-4 group cursor-help">
                              <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-white/40 group-hover:bg-slate-500/10 group-hover:text-emerald-500 transition-all duration-200">
                                  <FontAwesomeIcon icon="phone" class="text-xs" />
                              </div>
                              <div>
                                   <p class="text-[10px] text-white/20 font-bold uppercase tracking-wide">WhatsApp</p>
                                  <p class="text-xs text-white/80 font-bold">{{ selectedChat.wa_id }}</p>
                              </div>
                          </div>
                          <div class="flex items-center gap-4 group">
                                   <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-white/40 group-hover:bg-slate-500/10 group-hover:text-sky-500 transition-all duration-200">
                                  <FontAwesomeIcon icon="id-card" class="text-xs" />
                              </div>
                              <div>
                                   <p class="text-[10px] text-white/20 font-bold uppercase tracking-wide">Estado CRM</p>
                                  <p class="text-xs text-white/80 font-bold">{{ (contactContext.ventas.length > 0 || contactContext.cotizaciones.length > 0) ? 'Cliente Activo' : 'Prospecto' }}</p>
                              </div>
                          </div>
                      </div>
                  </div>

                  <!-- Cotizaciones -->
                  <div v-if="contactContext.cotizaciones.length > 0">
                      <h4 class="text-[10px] font-black uppercase tracking-wider text-white/20 mb-4 border-b border-white/5 pb-2">Últimas cotizaciones</h4>
                      <div class="space-y-3">
                        <div v-for="cot in contactContext.cotizaciones" :key="'cot-'+cot.id" class="p-2.5 rounded-xl bg-white/5 border border-white/5 hover:bg-white/10 transition-all group">
                            <div class="flex justify-between items-start gap-2">
                                <div class="min-w-0 flex-1">
                                  <span class="text-[10px] font-black text-white/90 block truncate">{{ cot.numero_cotizacion || ('#' + cot.id) }}</span>
                                  <span class="text-[9px] text-brand-400 font-bold">${{ parseFloat(cot.total || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <button
                                  type="button"
                                  @click="sendDocumentPdfLink('cotizacion', cot)"
                                  :disabled="!cot.pdf_url || sendingDoc === 'cot-'+cot.id || !is24HourWindowOpen"
                                  class="shrink-0 px-2 py-1.5 rounded-xl text-[8px] font-black uppercase tracking-wide bg-sky-500/10 text-sky-400 border border-sky-500/20 hover:bg-slate-500/30 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                                  :title="!is24HourWindowOpen ? 'Ventana de 24h cerrada' : (cot.pdf_url ? 'Enviar enlace al PDF por WhatsApp' : 'Sin enlace público (token)')"
                                >
                                  {{ sendingDoc === 'cot-'+cot.id ? '…' : 'Enviar PDF' }}
                                </button>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-[8px] text-white/30 uppercase font-black">{{ cot.fecha }}</span>
                                <span class="text-[8px] px-1 bg-white/5 rounded-xl text-white/20 group-hover:text-white/40">{{ cot.estado }}</span>
                            </div>
                        </div>
                      </div>
                  </div>

                  <!-- Recent Activity: Ventas -->
                  <div v-if="contactContext.ventas.length > 0">
                      <h4 class="text-[10px] font-black uppercase tracking-wider text-white/20 mb-4 border-b border-white/5 pb-2">Últimas ventas</h4>
                      <div class="space-y-3">
                        <div v-for="venta in contactContext.ventas" :key="'v-'+venta.id" class="p-2.5 rounded-xl bg-white/5 border border-white/5 hover:bg-white/10 transition-all group">
                            <div class="flex justify-between items-start gap-2">
                                <div class="min-w-0 flex-1">
                                  <span class="text-[10px] font-black text-white/90 block truncate">#{{ venta.folio }}</span>
                                  <span class="text-[9px] text-emerald-400 font-bold">${{ parseFloat(venta.total || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <button
                                  type="button"
                                  @click="sendDocumentPdfLink('venta', venta)"
                                  :disabled="!venta.pdf_url || sendingDoc === 'venta-'+venta.id || !is24HourWindowOpen"
                                  class="shrink-0 px-2 py-1.5 rounded-xl text-[8px] font-black uppercase tracking-wide bg-sky-500/10 text-sky-400 border border-sky-500/20 hover:bg-slate-500/30 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                                  :title="!is24HourWindowOpen ? 'Ventana de 24h cerrada' : (venta.pdf_url ? 'Enviar enlace al PDF por WhatsApp' : 'Sin enlace público (token)')"
                                >
                                  {{ sendingDoc === 'venta-'+venta.id ? '…' : 'Enviar PDF' }}
                                </button>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-[8px] text-white/30 uppercase font-black">{{ venta.fecha }}</span>
                                <span class="text-[8px] px-1 bg-white/5 rounded-xl text-white/20 group-hover:text-white/40">{{ venta.status }}</span>
                            </div>
                        </div>
                      </div>
                  </div>

                  <div v-if="contactContext.servicios.length > 0">
                      <h4 class="text-[10px] font-black uppercase tracking-wider text-white/20 mb-4 border-b border-white/5 pb-2">Últimos Servicios</h4>
                      <div class="space-y-3">
                        <div v-for="servicio in contactContext.servicios" :key="servicio.id" class="p-2.5 rounded-xl bg-white/5 border border-white/5 hover:bg-white/10 transition-all group">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-black text-white/90">{{ servicio.tipo_servicio || 'Mantenimiento' }}</span>
                                <span class="text-[8px] px-1 bg-sky-500/10 text-sky-400 rounded-xl font-black uppercase tracking-wide">{{ servicio.status }}</span>
                            </div>
                            <p class="text-[8px] text-white/30 mt-1 uppercase font-black">{{ servicio.fecha }} - #{{ servicio.folio }}</p>
                        </div>
                      </div>
                  </div>

                  <!-- CRM Actions -->
                  <div class="pt-2">
                      <h4 class="text-[10px] font-black uppercase tracking-wider text-white/20 mb-4 border-b border-white/5 pb-2">Gestión</h4>
                      <div class="grid grid-cols-1 gap-2">
                        <button class="w-full py-3.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-wide text-white transition-all active:scale-95">
                            Ver Historial CRM
                        </button>
                        <button @click="toggleChatStatus(selectedChat.status === 'closed' ? 'open' : 'closed')" 
                                class="w-full py-3.5 border rounded-xl text-[10px] font-black uppercase tracking-wide transition-all active:scale-95"
                                 :class="selectedChat.status === 'closed' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400 hover:bg-emerald-500/20' : 'bg-rose-500/10 border-rose-500/20 text-rose-400 hover:bg-rose-500/20'">
                            {{ selectedChat.status === 'closed' ? 'Reabrir Conversación' : 'Cerrar Conversación' }}
                        </button>
                      </div>
                  </div>
              </div>

              <div class="p-4 bg-black/20 flex justify-center">
                   <button @click="showInfo = false" class="text-[9px] font-black uppercase tracking-wide text-white/10 hover:text-white transition-colors">
                      Ocultar Detalles
                   </button>
              </div>
          </div>
        </transition>

      </div>
    </div>

    <!-- MODAL GESTION RESPUESTAS RAPIDAS -->
    <div v-if="showQRManager" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-slate-950 border border-white/10 rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[80vh]">
            <div class="p-6 border-b border-white/5 flex justify-between items-center">
                <h3 class="text-xl font-black text-white flex items-center gap-2">
                    <FontAwesomeIcon icon="bolt" class="text-emerald-400" />
                    Respuestas Rápidas
                </h3>
                <button @click="showQRManager = false" class="text-white/20 hover:text-white transition-colors">
                    <FontAwesomeIcon icon="times" />
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
                <!-- Form Nuevo -->
                <div class="bg-white/5 rounded-2xl p-4 mb-6 border border-white/5">
                    <h4 class="text-[10px] font-black uppercase text-white/40 mb-4 tracking-wide">Crear nueva respuesta</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-bold text-white/20 uppercase mb-1.5 block">Atajo (ej: /hola)</label>
                            <input v-model="newQR.shortcut" type="text" placeholder="/atajo" class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:border-brand-500/50 focus:outline-none focus:ring-1 focus:ring-brand-500/30 placeholder:text-white/10">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-white/20 uppercase mb-1.5 block">Mensaje</label>
                            <textarea v-model="newQR.message" rows="1" placeholder="Escribe el texto... (Usa {nombre} o {nombre_completo})" class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:border-brand-500/50 focus:outline-none focus:ring-1 focus:ring-brand-500/30 placeholder:text-white/10"></textarea>
                        </div>
                    </div>
                    <button @click="addQuickResponse" :disabled="!newQR.shortcut || !newQR.message" class="mt-4 px-6 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-30 text-white text-[10px] font-black uppercase rounded-xl transition-all">
                        Añadir Atajo
                    </button>
                </div>

                <!-- Lista -->
                <div class="space-y-3">
                    <div v-for="qr in qrList" :key="qr.id" class="flex items-center justify-between p-4 bg-white/[0.02] border border-white/[0.05] rounded-2xl group hover:bg-white/[0.05] transition-all">
                        <div>
                            <span class="text-xs font-black text-emerald-400 mr-3">{{ qr.shortcut }}</span>
                            <p class="text-xs text-white/60 mt-1">{{ qr.message }}</p>
                        </div>
                        <button @click="deleteQuickResponse(qr.id)" class="p-2 text-white/10 hover:text-rose-400 opacity-0 group-hover:opacity-100 transition-all">
                            <FontAwesomeIcon icon="trash" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

  <!-- Modal Guardar Evidencia -->
  <Teleport to="body">
    <div v-if="showEvidenciaModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm" @click.self="showEvidenciaModal = false">
      <div class="bg-slate-900 border border-white/10 rounded-3xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="p-6">
          <h3 class="text-lg font-black text-white mb-4">📸 Guardar imagen como evidencia</h3>
          <div v-if="loadingCitas" class="text-center py-8 text-white/50">
            <FontAwesomeIcon icon="spinner" class="animate-spin text-2xl mb-2" />
            <p>Buscando citas activas...</p>
          </div>
          <div v-else-if="activeCitas.length === 0" class="text-center py-8">
            <p class="text-white/50 mb-4">No hay citas activas para este cliente.</p>
            <p class="text-sm text-white/30">Primero agenda una cita para poder guardar evidencias.</p>
          </div>
          <div v-else>
            <p class="text-sm text-white/50 mb-3">Selecciona la cita:</p>
            <div v-for="cita in activeCitas" :key="cita.id" 
              @click="guardarEvidencia(cita.id)"
              class="p-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 mb-2 cursor-pointer transition-all flex items-center gap-3"
            >
              <div class="w-10 h-10 rounded-lg bg-brand-500/20 flex items-center justify-center text-brand-400 font-bold">
                <FontAwesomeIcon icon="calendar" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-white font-bold text-sm truncate">{{ cita.tipo_servicio }}</p>
                <p class="text-white/50 text-xs">{{ cita.folio }} — {{ new Date(cita.fecha_hora).toLocaleDateString('es-MX') }}</p>
              </div>
              <FontAwesomeIcon icon="chevron-right" class="text-white/30" />
            </div>
          </div>
        </div>
        <div class="px-6 pb-4 flex justify-end">
          <button @click="showEvidenciaModal = false" class="px-4 py-2 text-xs font-bold uppercase text-white/50 hover:text-white transition-all">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Head, Link, router, usePage, usePoll } from '@inertiajs/vue3';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import axios from 'axios';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import Swal from '@/Utils/Swal';

const { props: pageProps } = usePage();
const empresaId = pageProps.auth?.user?.empresa_id ?? null;

const props = defineProps({
  initialChats: Array,
  agents: Array,
  quickResponses: Array,
  chatbotConfig: Object,
  /** Viene de ?cotizacion= al abrir el Inbox desde Cotizaciones; el envío solo ocurre si el usuario confirma aquí. */
  pendingCotizacion: {
    type: Object,
    default: null,
  },
});

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
  ],
});

const pendingCotizacionDismissed = ref(false);
const sendingPendingCotizacion = ref(false);

watch(
  () => props.pendingCotizacion?.id,
  () => {
    pendingCotizacionDismissed.value = false;
  }
);

const showPendingCotizacionBanner = computed(
  () => Boolean(props.pendingCotizacion) && !pendingCotizacionDismissed.value
);

const pendingCotizacionLabel = computed(() => {
  const p = props.pendingCotizacion;
  if (!p) return '';
  return p.numero_cotizacion || `#${p.id}`;
});

const pendingCotizacionBlockReason = computed(() => {
  const p = props.pendingCotizacion;
  if (!p) return '';
  if (!p.pdf_ready) {
    return 'La cotización no tiene enlace público (token). Guárdela de nuevo o contacte soporte.';
  }
  if (!p.telefono_ok) {
    return 'El cliente no tiene un teléfono válido registrado.';
  }
  return '';
});

const dismissPendingCotizacion = () => {
  pendingCotizacionDismissed.value = true;
  router.visit(route('marketing.whatsapp.inbox'), { replace: true });
};

const confirmSendPendingCotizacion = async () => {
  const p = props.pendingCotizacion;
  if (!p || pendingCotizacionBlockReason.value) return;
  if (sendingPendingCotizacion.value) return;

  sendingPendingCotizacion.value = true;
  try {
    const { data } = await axios.post(route('cotizaciones.whatsapp-api', p.id));

    if (data?.status === 'failed') {
      notyf.error('No se pudo entregar el mensaje por WhatsApp Business.');
      return;
    }

    notyf.success('Mensaje enviado por WhatsApp Business.');
    pendingCotizacionDismissed.value = true;
    router.visit(route('marketing.whatsapp.inbox'), { replace: true });
  } catch (e) {
    const msg =
      e.response?.data?.message ||
      e.response?.data?.error ||
      e.message ||
      'No se pudo enviar.';
    notyf.error(typeof msg === 'string' ? msg : 'No se pudo enviar.');
  } finally {
    sendingPendingCotizacion.value = false;
  }
};

const chats = ref(props.initialChats || []);

// Auto-polling en segundo plano con Inertia 3
usePoll(60000, { only: ['initialChats'] });

watch(() => props.initialChats, (newChats) => {
  if (newChats) {
    chats.value = newChats;
    if (selectedChat.value) {
      const refreshed = newChats.find(chat => chat.wa_id === selectedChat.value.wa_id);
      if (refreshed) {
        selectedChat.value = refreshed;
        if (!window.Echo || window.Echo.connector?.pusher?.connection?.state !== 'connected') {
          axios.get(`/marketing/whatsapp-inbox/messages/${selectedChat.value.wa_id}`)
            .then(response => {
              if (response.data.length !== messages.value.length) {
                messages.value = response.data;
                scrollToBottom();
              }
            });
        }
      }
    }
  }
}, { deep: true });

const qrList = ref(props.quickResponses || []);
const selectedChat = ref(null);
const messages = ref([]);
const newMessage = ref('');
const searchQuery = ref('');
const statusFilter = ref('open');
const loading = ref(false);
const loadingMessages = ref(false);
const sending = ref(false);
const gettingAISuggestion = ref(false);
const messagesBox = ref(null);
const showInfo = ref(false);
const composerError = ref('');
const messagesError = ref('');
const contactContext = ref({ ventas: [], servicios: [], cotizaciones: [] });
const sendingDoc = ref(null);

const showEmojiPicker = ref(false);
const showStickerPicker = ref(false);
const showInternalNote = ref(false);
const showComposerActions = ref(false);
const showQRManager = ref(false);
const newQR = ref({ shortcut: '', message: '' });
const fileInput = ref(null);
const messageInput = ref(null);

const popularEmojis = [
  '😀', '😂', '😍', '👍', '🙏', '🔥', '✅', 
  '❤️', '😊', '🤔', '😎', '🙌', '✨', '🎉',
  '👋', '🤝', '📍', '📞', '💬', '🚀', '⭐',
  '🏢', '❄️', '🔧', '📦', '💰', '📅', '⏰'
];

const stickers = [
  { url: 'https://cdn-icons-png.flaticon.com/512/5968/5968841.png', name: 'WhatsApp' },
  { url: 'https://cdn-icons-png.flaticon.com/512/2271/2271062.png', name: 'Cool' },
  { url: 'https://cdn-icons-png.flaticon.com/512/2271/2271068.png', name: 'Happy' },
  { url: 'https://cdn-icons-png.flaticon.com/512/2271/2271059.png', name: 'Ok' },
  { url: 'https://cdn-icons-png.flaticon.com/512/2271/2271047.png', name: 'Love' },
  { url: 'https://cdn-icons-png.flaticon.com/512/1791/1791400.png', name: 'Air Conditioning' },
  { url: 'https://cdn-icons-png.flaticon.com/512/911/911409.png', name: 'Repair' },
  { url: 'https://cdn-icons-png.flaticon.com/512/2933/2933761.png', name: 'Support' }
];

const addEmoji = (emoji) => {
  newMessage.value += emoji;
  showEmojiPicker.value = false;
};

const insertPlaceholder = (placeholder) => {
  const textarea = messageInput.value;
  if (!textarea) {
    newMessage.value += placeholder;
    return;
  }
  
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const text = newMessage.value || '';
  
  newMessage.value = text.substring(0, start) + placeholder + text.substring(end);
  
  nextTick(() => {
    textarea.focus();
    textarea.setSelectionRange(start + placeholder.length, start + placeholder.length);
  });
};

const canSend = computed(() => Boolean(newMessage.value.trim()) && !sending.value && selectedChat.value && (is24HourWindowOpen.value || showInternalNote.value));

const lastInboundMessage = computed(() => {
  if (!messages.value.length) return null;
  const inboundMessages = messages.value.filter(m => m.direction === 'inbound' && !m.is_internal);
  return inboundMessages.length > 0 ? inboundMessages[inboundMessages.length - 1] : null;
});

const is24HourWindowOpen = computed(() => {
  if (!lastInboundMessage.value) return false;
  // Si no hay fecha, asumimos que no hay ventana
  const dateStr = lastInboundMessage.value.created_at || lastInboundMessage.value.received_at;
  if (!dateStr) return false;
  
  try {
    const lastDate = parseISO(dateStr);
    const now = new Date();
    const diffInMs = now.getTime() - lastDate.getTime();
    const diffInHours = diffInMs / (1000 * 60 * 60);
    return diffInHours < 24;
  } catch (e) {
    return false;
  }
});

const timeUntilWindowCloses = computed(() => {
    if (!is24HourWindowOpen.value || !lastInboundMessage.value) return null;
    try {
        const dateStr = lastInboundMessage.value.created_at || lastInboundMessage.value.received_at;
        const lastDate = parseISO(dateStr);
        const expiresAt = new Date(lastDate.getTime() + 24 * 60 * 60 * 1000);
        const now = new Date();
        const diffInMs = expiresAt.getTime() - now.getTime();
        
        if (diffInMs <= 0) return null;

        const hours = Math.floor(diffInMs / (1000 * 60 * 60));
        const minutes = Math.floor((diffInMs % (1000 * 60 * 60)) / (1000 * 60));
        return `${hours}h ${minutes}m`;
    } catch (e) {
        return null;
    }
});

const filteredQuickResponses = computed(() => {
  if (!newMessage.value.startsWith('/')) return [];
  const search = newMessage.value.substring(1).toLowerCase();
  return qrList.value.filter(qr => qr.shortcut.toLowerCase().includes(search));
});

const useQuickResponse = (qr) => {
  newMessage.value = qr.shortcut;
  sendMessage();
};

const filteredChats = computed(() => {
  let list = chats.value;
  if (statusFilter.value !== 'all') {
    list = list.filter(c => c.status === statusFilter.value);
  }
  if (!searchQuery.value) return list;
  return list.filter(c => 
    (c.from_name && c.from_name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
    c.wa_id.includes(searchQuery.value)
  );
});

const fetchChats = async (skipAutoSelect = false) => {
  loading.value = true;
  try {
    const response = await axios.get(route('marketing.whatsapp.inbox'));
    chats.value = response.data;

    if (selectedChat.value) {
      const refreshed = response.data.find(chat => chat.wa_id === selectedChat.value.wa_id);
      if (refreshed) {
        selectedChat.value = refreshed;
      }
    } else if (response.data.length > 0 && !skipAutoSelect) {
      selectChat(response.data[0]);
    }
  } catch (e) {
    console.error(e);
    composerError.value = 'No se pudo refrescar la bandeja.';
  } finally {
    loading.value = false;
  }
};

const selectChat = async (chat) => {
  selectedChat.value = chat;
  messagesError.value = '';
  loadingMessages.value = true;
  contactContext.value = { ventas: [], servicios: [], cotizaciones: [] };

  try {
    const [messagesRes, contextRes] = await Promise.all([
      axios.get(`/marketing/whatsapp-inbox/messages/${chat.wa_id}`),
      axios.get(`/marketing/whatsapp-inbox/context/${chat.wa_id}`).catch(() => ({
        data: { ventas: [], servicios: [], cotizaciones: [] },
      })),
    ]);
    messages.value = messagesRes.data;
    const d = contextRes.data || {};
    contactContext.value = {
      ventas: Array.isArray(d.ventas) ? d.ventas : [],
      servicios: Array.isArray(d.servicios) ? d.servicios : [],
      cotizaciones: Array.isArray(d.cotizaciones) ? d.cotizaciones : [],
    };
    scrollToBottom();
  } catch (e) {
    console.error(e);
    messages.value = [];
    messagesError.value = 'No se pudieron cargar los mensajes de esta conversación.';
  } finally {
    loadingMessages.value = false;
  }
};

const replacePlaceholders = (text, chat) => {
  if (!text) return '';
  if (!chat) return text;
  
  let fullName = (chat.from_name || '').trim();
  const isPhone = /^\+?[\d\s\-]+$/.test(fullName);
  if (isPhone) {
    fullName = '';
  }

  let firstName = '';
  if (fullName) {
    firstName = fullName.split(' ')[0];
    if (firstName) {
      firstName = firstName.charAt(0).toUpperCase() + firstName.slice(1).toLowerCase();
    }
  }

  let result = text
    .replace(/\{nombre_completo\}/gi, fullName)
    .replace(/\{nombre\}/gi, firstName)
    .replace(/\{name\}/gi, firstName)
    .replace(/\{fullname\}/gi, fullName);

  result = result
    .replace(/[ \t]+/g, ' ')
    .replace(/[ \t]+([.,!?;])/g, '$1')
    .trim();

  return result;
};

const sendIfMsg = (e) => {
  if (e.shiftKey) return;
  e.preventDefault();
  sendMessage();
};

const sendMessage = async () => {
  if (!newMessage.value || !newMessage.value.trim()) return;
  if (!selectedChat.value || sending.value) return;

  composerError.value = '';
  const msgText = newMessage.value.trim();
  
  // Detectar si es una respuesta rápida
  const qr = qrList.value.find(q => q.shortcut === msgText);
  let finalMsg = qr ? qr.message : msgText;

  // Personalización por nombre
  finalMsg = replacePlaceholders(finalMsg, selectedChat.value);

  newMessage.value = '';
  sending.value = true;
  showEmojiPicker.value = false;
  showStickerPicker.value = false;

  const endpoint = showInternalNote.value ? '/marketing/whatsapp-inbox/internal-note' : '/marketing/whatsapp-inbox/send';

  try {
    const response = await axios.post(endpoint, {
      to: selectedChat.value.wa_id,
      body: finalMsg,
      type: 'text'
    });
    
    messages.value.push(response.data);
    scrollToBottom();
    
    // Actualizar ultimo mensaje en la lista si no es nota interna
    if (!showInternalNote.value) {
      const chatIndex = chats.value.findIndex(c => c.wa_id === selectedChat.value.wa_id);
      if (chatIndex !== -1) {
        chats.value[chatIndex].last_message = finalMsg;
        chats.value[chatIndex].last_message_at = new Date().toISOString();
        chats.value[chatIndex].direction = 'outbound';
        chats.value[chatIndex].status = response.data.status;
      }
    }
    
    showInternalNote.value = false;
  } catch (e) {
    console.error(e);
    newMessage.value = msgText;
    composerError.value = e?.response?.data?.message || 'No se pudo enviar el mensaje.';
  } finally {
    sending.value = false;
  }
};

/**
 * Envía por WhatsApp un mensaje con enlace público al PDF de cotización o venta.
 */
const sendDocumentPdfLink = async (kind, item) => {
  if (!selectedChat.value || !item?.pdf_url) return;

  const docKey = kind === 'cotizacion' ? `cot-${item.id}` : `venta-${item.id}`;
  sendingDoc.value = docKey;
  composerError.value = '';

  const refLabel =
    kind === 'cotizacion'
      ? item.numero_cotizacion || `#${item.id}`
      : item.folio || `#${item.id}`;
  const titulo = kind === 'cotizacion' ? 'Cotización' : 'Venta/nota';
  const totalFmt = Number(item.total || 0).toLocaleString('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

  const body = [
    `📄 *${titulo} ${refLabel}*`,
    `💰 Total: $${totalFmt} MXN`,
    `🔗 PDF: ${item.pdf_url}`,
    '',
    kind === 'cotizacion'
      ? '_Válida según políticas de la empresa._'
      : '_Gracias por su preferencia._',
  ].join('\n');

  try {
    const response = await axios.post('/marketing/whatsapp-inbox/send', {
      to: selectedChat.value.wa_id,
      body,
      type: 'text',
    });

    messages.value.push(response.data);
    scrollToBottom();

    const chatIndex = chats.value.findIndex((c) => c.wa_id === selectedChat.value.wa_id);
    if (chatIndex !== -1) {
      chats.value[chatIndex].last_message = `📄 ${titulo} ${refLabel}`;
      chats.value[chatIndex].last_message_at = new Date().toISOString();
      chats.value[chatIndex].direction = 'outbound';
      chats.value[chatIndex].status = response.data.status;
    }
  } catch (e) {
    console.error(e);
    composerError.value = e?.response?.data?.message || 'No se pudo enviar el enlace al PDF.';
  } finally {
    sendingDoc.value = null;
  }
};

const sendSticker = async (stickerUrl) => {
  if (!selectedChat.value || sending.value) return;

  sending.value = true;
  showStickerPicker.value = false;
  
  try {
    const response = await axios.post('/marketing/whatsapp-inbox/send', {
      to: selectedChat.value.wa_id,
      url: stickerUrl,
      type: 'sticker'
    });
    
    messages.value.push(response.data);
    scrollToBottom();
    
    // Actualizar ultimo mensaje en la lista
    const chatIndex = chats.value.findIndex(c => c.wa_id === selectedChat.value.wa_id);
    if (chatIndex !== -1) {
      chats.value[chatIndex].last_message = '🏷️ [Sticker]';
      chats.value[chatIndex].last_message_at = new Date().toISOString();
      chats.value[chatIndex].direction = 'outbound';
      chats.value[chatIndex].status = response.data.status;
    }
  } catch (e) {
    console.error(e);
    composerError.value = 'No se pudo enviar el sticker.';
  } finally {
    sending.value = false;
  }
};

const mediaProxyUrl = (kind, messageId) =>
  `/marketing/whatsapp-inbox/${kind}/${encodeURIComponent(messageId)}`;

const openImageModal = (url) => {
    window.open(url, '_blank');
};

const addTag = async (e) => {
  const tag = e.target.value.trim().toLowerCase();
  if (!tag || !selectedChat.value) return;
  
  if (!selectedChat.value.tags) selectedChat.value.tags = [];
  if (selectedChat.value.tags.includes(tag)) {
    e.target.value = '';
    return;
  }

  selectedChat.value.tags.push(tag);
  e.target.value = '';

  try {
    await axios.put(`/marketing/whatsapp-inbox/conversation/${selectedChat.value.wa_id}`, {
      tags: selectedChat.value.tags
    });
  } catch (e) {
    console.error(e);
  }
};

const removeTag = async (tag) => {
  if (!selectedChat.value) return;
  selectedChat.value.tags = selectedChat.value.tags.filter(t => t !== tag);

  try {
    await axios.put(`/marketing/whatsapp-inbox/conversation/${selectedChat.value.wa_id}`, {
      tags: selectedChat.value.tags
    });
  } catch (e) {
    console.error(e);
  }
};

const assignAgent = async (agentId) => {
  if (!selectedChat.value) return;
  try {
    await axios.post(`/marketing/whatsapp-inbox/assign/${selectedChat.value.wa_id}`, { agent_id: agentId });
    selectedChat.value.assigned_to = agentId;
    const agent = props.agents.find(a => a.id === agentId);
    selectedChat.value.assigned_agent = agent;
    
    // Actualizar en la lista lateral
    const chatIndex = chats.value.findIndex(c => c.wa_id === selectedChat.value.wa_id);
    if (chatIndex !== -1) {
      chats.value[chatIndex].assigned_to = agentId;
      chats.value[chatIndex].assigned_agent = agent;
    }
  } catch (e) {
    console.error(e);
  }
};

const toggleChatStatus = async (status) => {
  if (!selectedChat.value) return;
  try {
    await axios.post(`/marketing/whatsapp-inbox/status/${selectedChat.value.wa_id}`, { status });
    selectedChat.value.status = status;
    
    // Actualizar en la lista lateral
    const chatIndex = chats.value.findIndex(c => c.wa_id === selectedChat.value.wa_id);
    if (chatIndex !== -1) {
      chats.value[chatIndex].status = status;
    }
  } catch (e) {
    console.error(e);
  }
};

const startingBot = ref(false);
const startChatbot = async () => {
  if (!selectedChat.value || startingBot.value) return;
  
  const { isConfirmed } = await Swal.fire({
    title: 'Iniciar Asistente Virtual',
    text: '¿Deseas activar el bot y enviarle el menú principal al cliente de inmediato?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, iniciar',
    cancelButtonText: 'Cancelar',
    customClass: {
      popup: 'bg-slate-900 border border-white/10 rounded-3xl text-white shadow-2xl',
      title: 'text-white font-black',
      htmlContainer: 'text-white/70',
      confirmButton: 'px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase transition-all shadow-lg active:scale-95',
      cancelButton: 'px-6 py-2.5 bg-white/5 hover:bg-white/10 text-white rounded-xl text-xs font-black uppercase transition-all border border-white/10 active:scale-95'
    },
    buttonsStyling: false
  });

  if (!isConfirmed) return;

  startingBot.value = true;
  try {
    const response = await axios.post(`/marketing/whatsapp-inbox/start-bot/${selectedChat.value.wa_id}`);
    if (response.data.success) {
      notyf.success('Asistente virtual iniciado con éxito.');
      await selectChat(selectedChat.value);
    }
  } catch (e) {
    console.error(e);
    const errorMsg = e.response?.data?.error || 'Error al iniciar el bot.';
    notyf.error(errorMsg);
  } finally {
    startingBot.value = false;
  }
};

const showEvidenciaModal = ref(false);
const loadingCitas = ref(false);
const activeCitas = ref([]);
const currentMessageId = ref(null);

const openGuardarEvidencia = async (messageId) => {
  currentMessageId.value = messageId;
  showEvidenciaModal.value = true;
  loadingCitas.value = true;
  activeCitas.value = [];
  try {
    const response = await axios.get(`/marketing/whatsapp-inbox/${selectedChat.value.wa_id}/active-citas`);
    activeCitas.value = response.data.citas || [];
  } catch (e) {
    notyf.error('Error al buscar citas activas');
  } finally {
    loadingCitas.value = false;
  }
};

const evidenciasGuardadas = ref({});

const guardarEvidencia = async (citaId) => {
  try {
    const response = await axios.post(
      `/marketing/whatsapp-inbox/${selectedChat.value.wa_id}/save-evidence/${citaId}`,
      { message_id: currentMessageId.value }
    );
    if (response.data.success) {
      if (response.data.already_saved) {
        notyf.success('Imagen ya estaba guardada');
      } else {
        notyf.success('Imagen guardada como evidencia');
        evidenciasGuardadas.value[currentMessageId.value] = true;
      }
      showEvidenciaModal.value = false;
    }
  } catch (e) {
    notyf.error(e.response?.data?.error || 'Error al guardar evidencia');
  }
};

const togglingBotConv = ref(false);
const toggleBotConversation = async () => {
  if (!selectedChat.value || togglingBotConv.value) return;
  togglingBotConv.value = true;
  try {
    const response = await axios.post(`/marketing/whatsapp-inbox/toggle-bot-conversation/${selectedChat.value.wa_id}`);
    if (response.data.success) {
      selectedChat.value.chatbot_disabled = response.data.chatbot_disabled;
      const chatIndex = chats.value.findIndex(c => c.wa_id === selectedChat.value.wa_id);
      if (chatIndex !== -1) {
        chats.value[chatIndex].chatbot_disabled = response.data.chatbot_disabled;
      }
      notyf.success(response.data.chatbot_disabled ? 'Bot desactivado para este cliente' : 'Bot activado para este cliente');
    }
  } catch (e) {
    notyf.error(e.response?.data?.error || 'Error al cambiar estado del bot');
  } finally {
    togglingBotConv.value = false;
  }
};

const deleteQuickResponse = async (id) => {
  const { isConfirmed } = await Swal.fire({
    title: 'Eliminar respuesta rápida',
    text: '¿Seguro que deseas eliminar esta respuesta rápida?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  });
  if (!isConfirmed) return;
  try {
    await axios.delete(`/marketing/whatsapp-quick-responses/${id}`);
    qrList.value = qrList.value.filter(q => q.id !== id);
  } catch (e) {
    console.error(e);
  }
};

const addQuickResponse = async () => {
  try {
    const res = await axios.post('/marketing/whatsapp-quick-responses', newQR.value);
    qrList.value.push(res.data);
    newQR.value = { shortcut: '', message: '' };
  } catch (e) {
    console.error(e);
  }
};

const onFileSelected = async (e) => {
  const file = e.target.files[0];
  if (!file || !selectedChat.value) return;

  sending.value = true;
  const formData = new FormData();
  formData.append('file', file);
  formData.append('to', selectedChat.value.wa_id);

  try {
    const response = await axios.post('/marketing/whatsapp-inbox/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    messages.value.push(response.data);
    scrollToBottom();
  } catch (e) {
    console.error(e);
    composerError.value = 'Error al subir el archivo.';
  } finally {
    sending.value = false;
    e.target.value = ''; // Reset
  }
};

const getAISuggestion = async () => {
  if (!selectedChat.value || gettingAISuggestion.value) return;

  gettingAISuggestion.value = true;
  try {
    const response = await axios.get(`/marketing/whatsapp-inbox/ai-suggestion/${selectedChat.value.wa_id}`);
    newMessage.value = response.data.suggestion;
    // Auto-expand textarea if needed (usually handled by binding)
  } catch (e) {
    console.error(e);
    composerError.value = 'Error al obtener sugerencia de IA.';
  } finally {
    gettingAISuggestion.value = false;
  }
};

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesBox.value) {
      messagesBox.value.scrollTop = messagesBox.value.scrollHeight;
    }
  });
};

const getAvatarColor = (name) => {
  if (!name) return 'from-slate-500 to-slate-700';
  const nameStr = String(name);
  const colors = [
    'from-brand-500 to-amber-600',
    'from-emerald-500 to-emerald-600',
    'from-sky-500 to-sky-600',
    'from-rose-500 to-rose-600',
    'from-slate-500 to-slate-600',
    'from-brand-600 to-rose-600'
  ];
  let hash = 0;
  for (let i = 0; i < nameStr.length; i++) {
    hash = nameStr.charCodeAt(i) + ((hash << 5) - hash);
  }
  return colors[Math.abs(hash) % colors.length];
};

const getInitials = (name) => {
  if (!name) return '?';
  const nameStr = String(name);
  const parts = nameStr.trim().split(' ');
  if (parts.length >= 2 && parts[0] && parts[1]) {
      return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return nameStr.substring(0, 2).toUpperCase();
};

const formatMessageBody = (msg) => {
  let body = escapeHtml(msg.body || '');
  
  // 1. Detectar y linkear Ubicaciones
  if (msg.body && msg.body.includes('📍 [Ubicación:')) {
    const coords = msg.body.match(/Ubicación: ([\d.-]+), ([\d.-]+)/);
    if (coords) {
      const link = `https://www.google.com/maps?q=${coords[1]},${coords[2]}`;
      return `📍 <a href="${link}" target="_blank" class="underline font-black ${msg.direction === 'inbound' ? 'text-emerald-400' : 'text-white'} hover:opacity-80 transition-opacity">Abrir en Google Maps</a>`;
    }
  }

  return body.replace(/\n/g, '<br>');
};

const extractCoords = (body) => {
    if (!body) return '0,0';
    const coords = body.match(/Ubicación: ([\d.-]+), ([\d.-]+)/);
    return coords ? `${coords[1]},${coords[2]}` : '0,0';
};

const togglingChatbot = ref(false);
const toggleChatbotMode = async () => {
    if (togglingChatbot.value) return;
    togglingChatbot.value = true;
    try {
        const response = await axios.post(route('marketing.whatsapp.toggle-chatbot'));
        props.chatbotConfig.enabled = response.data.enabled;
        props.chatbotConfig.mode = response.data.mode;
    } catch (e) {
        console.error(e);
    } finally {
        togglingChatbot.value = false;
    }
};

const formatTime = (dateInput) => {
  if (!dateInput) return '';
  try {
    const date = typeof dateInput === 'string' ? parseISO(dateInput) : dateInput;
    if (isNaN(date.getTime())) return '';
    return format(date, 'HH:mm', { locale: es });
  } catch (e) {
    return '';
  }
};

const formatChatDate = (dateInput) => {
  if (!dateInput) return '';
  try {
    const date = typeof dateInput === 'string' ? parseISO(dateInput) : dateInput;
    if (isNaN(date.getTime())) return '';
    const now = new Date();
    const hoy = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const ayer = new Date(hoy.getTime() - 86400000);
    const antier = new Date(hoy.getTime() - 172800000);
    const msgDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const hora = format(date, 'HH:mm');
    if (msgDate.getTime() === hoy.getTime()) return hora;
    if (msgDate.getTime() === ayer.getTime()) return `Ayer ${hora}`;
    if (msgDate.getTime() === antier.getTime()) return `Antier ${hora}`;
    return format(date, 'dd/MM/yyyy HH:mm');
  } catch (e) {
    return '';
  }
};

const getStatusIcon = (status) => {
  switch (status) {
    case 'sent': return 'check';
    case 'delivered': return 'check-double';
    case 'read': return 'check-double';
    default: return 'clock';
  }
};

const escapeHtml = (value) => value
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;');

// Polling manejado por Inertia usePoll

const waFromQueryMatches = (chatWa, queryWa) => {
  if (!queryWa) return false;
  const a = String(chatWa ?? '').replace(/\D/g, '');
  const b = String(queryWa).replace(/\D/g, '');
  if (!a || !b) return String(chatWa) === String(queryWa);
  return a === b || a.endsWith(b.slice(-10)) || b.endsWith(a.slice(-10));
};

onMounted(async () => {
  const params = new URLSearchParams(window.location.search);
  const waOpen = params.get('wa');

  if (waOpen) {
    let match = chats.value.find((c) => waFromQueryMatches(c.wa_id, waOpen));
    if (!match) {
      await fetchChats(true);
      match = chats.value.find((c) => waFromQueryMatches(c.wa_id, waOpen));
    }
    if (match) {
      await selectChat(match);
    } else if (chats.value.length > 0) {
      selectChat(chats.value[0]);
    }
  } else if (chats.value.length > 0) {
    selectChat(chats.value[0]);
  }

  // Solicitar permiso para notificaciones
  if ("Notification" in window && Notification.permission !== "granted") {
    Notification.requestPermission();
  }

  // CONFIGURAR ECHO (Real-time)
  if (window.Echo) {

    window.Echo.private(`empresa.${empresaId}.whatsapp`)
      .listen('.message.received', (e) => {
        const incomingMsg = e.message;

        
        // 1. Si es el chat que tenemos abierto, añadir mensaje
        if (selectedChat.value && selectedChat.value.wa_id === incomingMsg.wa_id) {
            // Evitar duplicados (por si ya lo añadimos localmente al enviar)
            if (!messages.value.find(m => m.message_id === incomingMsg.message_id)) {
                messages.value.push(incomingMsg);
                scrollToBottom();
            }
        }

        // 2. Actualizar lista lateral (sidebar)
        const chatIndex = chats.value.findIndex(c => c.wa_id === incomingMsg.wa_id);
        if (chatIndex !== -1) {
            chats.value[chatIndex].last_message = incomingMsg.body;
            chats.value[chatIndex].last_message_at = incomingMsg.received_at;
            chats.value[chatIndex].direction = incomingMsg.direction;
            chats.value[chatIndex].status = incomingMsg.status;
            
            // Reordenar para ponerlo arriba
            const movedChat = chats.value.splice(chatIndex, 1)[0];
            chats.value.unshift(movedChat);
        } else {
            // Nuevo chat que no estaba en la lista: recargar lista completa
            fetchChats();
        }

        // 3. Notificar si es entrante
        if (incomingMsg.direction === 'inbound') {
            notifyNewMessage({
                from_name: incomingMsg.from_name,
                wa_id: incomingMsg.wa_id,
                last_message: incomingMsg.body
            });
        }
      });
  }
});

const notifyNewMessage = (chat) => {
  if ("Notification" in window && Notification.permission === "granted") {
    new Notification(`Nuevo mensaje de ${chat.from_name || chat.wa_id}`, {
      body: chat.last_message,
      icon: '/favicon.ico'
    });
  }
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.1);
}
</style>
