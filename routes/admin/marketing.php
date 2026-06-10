<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Marketing\CampaignController;
use App\Http\Controllers\Marketing\AudienceController;
use App\Http\Controllers\Marketing\TemplateController;
use App\Http\Controllers\Marketing\WhatsAppChatController;

Route::prefix('marketing')->name('marketing.')->group(function () {
    // Campañas, audiencias y plantillas: solo administración
    Route::middleware('role:admin|super-admin')->group(function () {
        Route::get('/campanias', [CampaignController::class, 'index'])->name('campanias.index');
        Route::get('/campanias/crear', [CampaignController::class, 'create'])->name('campanias.create');
        Route::post('/campanias', [CampaignController::class, 'store'])->name('campanias.store');
        Route::get('/campanias/{campania}', [CampaignController::class, 'show'])->name('campanias.show');
        Route::post('/campanias/{campania}/ejecutar', [CampaignController::class, 'execute'])->name('campanias.execute');
        Route::delete('/campanias/{campania}', [CampaignController::class, 'destroy'])->name('campanias.destroy');

        Route::get('/audiencias', [AudienceController::class, 'index'])->name('audiencias.index');
        Route::post('/audiencias', [AudienceController::class, 'store'])->name('audiencias.store');
        Route::put('/audiencias/{audiencia}', [AudienceController::class, 'update'])->name('audiencias.update');
        Route::delete('/audiencias/{audiencia}', [AudienceController::class, 'destroy'])->name('audiencias.destroy');

        Route::get('/plantillas', [TemplateController::class, 'index'])->name('plantillas.index');
    });

    // WhatsApp Inbox + respuestas rápidas: ventas puede atender (alineado con cotizaciones → inbox)
    Route::middleware('role:admin|super-admin|ventas')->group(function () {
        Route::get('/whatsapp-inbox', [WhatsAppChatController::class, 'index'])->name('whatsapp.inbox');
        Route::get('/whatsapp-inbox/messages/{waId}', [WhatsAppChatController::class, 'getMessages'])->name('whatsapp.messages');
        Route::get('/whatsapp-inbox/context/{waId}', [WhatsAppChatController::class, 'getContactContext'])->name('whatsapp.context');
        Route::get('/whatsapp-inbox/ai-suggestion/{waId}', [WhatsAppChatController::class, 'getAISuggestion'])->name('whatsapp.ai-suggestion');
        Route::get('/whatsapp-inbox/audio/{messageId}', [WhatsAppChatController::class, 'getAudio'])->name('whatsapp.audio');
        Route::get('/whatsapp-inbox/image/{messageId}', [WhatsAppChatController::class, 'getImage'])->name('whatsapp.image');
        Route::post('/whatsapp-inbox/send', [WhatsAppChatController::class, 'sendMessage'])->name('whatsapp.send');
        Route::post('/whatsapp-inbox/upload', [WhatsAppChatController::class, 'uploadAndSendMedia'])->name('whatsapp.upload');
        Route::post('/whatsapp-inbox/internal-note', [WhatsAppChatController::class, 'sendInternalNote'])->name('whatsapp.internal-note');
        Route::post('/whatsapp-inbox/assign/{waId}', [WhatsAppChatController::class, 'assignAgent'])->name('whatsapp.assign');
        Route::post('/whatsapp-inbox/status/{waId}', [WhatsAppChatController::class, 'toggleStatus'])->name('whatsapp.status');
        Route::post('/whatsapp-inbox/toggle-chatbot', [WhatsAppChatController::class, 'toggleChatbot'])->name('whatsapp.toggle-chatbot');
        Route::put('/whatsapp-inbox/conversation/{waId}', [WhatsAppChatController::class, 'updateConversation'])->name('whatsapp.conversation.update');
        Route::post('/whatsapp-inbox/start-bot/{waId}', [WhatsAppChatController::class, 'startBot'])->name('whatsapp.start-bot');
        Route::post('/whatsapp-inbox/toggle-bot-conversation/{waId}', [WhatsAppChatController::class, 'toggleBotConversation'])->name('whatsapp.toggle-bot-conversation');
        Route::get('/whatsapp-inbox/{waId}/active-citas', [WhatsAppChatController::class, 'getActiveCitas'])->name('whatsapp.active-citas');
        Route::post('/whatsapp-inbox/{waId}/save-evidence/{citaId}', [WhatsAppChatController::class, 'saveEvidence'])->name('whatsapp.save-evidence');

        Route::apiResource('/whatsapp-quick-responses', \App\Http\Controllers\Marketing\WhatsAppQuickResponseController::class);
    });

    Route::prefix('social-posts')->name('social-posts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Marketing\SocialPostController::class, 'index'])->name('index');
        Route::get('/status', [\App\Http\Controllers\Marketing\SocialPostController::class, 'status'])->name('status');
        Route::post('/publish', [\App\Http\Controllers\Marketing\SocialPostController::class, 'publish'])->name('publish');
        Route::delete('/{id}', [\App\Http\Controllers\Marketing\SocialPostController::class, 'deletePost'])->name('delete');
        Route::get('/productos', [\App\Http\Controllers\Marketing\SocialPostController::class, 'productos'])->name('productos');
    });
});
