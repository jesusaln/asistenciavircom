<script setup>
import { Link, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import ToastContainer from '@/Components/Toast/ToastContainer.vue';
import ChatbotWidget from '@/Components/Chatbot/ChatbotWidget.vue';

const props = defineProps({
    empresa: Object,
    activeTab: String,
});
// ... (rest of script)
// ... (inside template before closing div)
        <!-- Footer con mapa y datos de empresa -->
        <PublicFooter :empresa="empresaData" />

        <!-- Asistente Virtual IA -->
        <ChatbotWidget :empresa="empresaData" />

        <!-- Sistema de Notificaciones Premium -->
        <ToastContainer />
    </div>
</template>
<style scoped>
/* Eliminar outline azul de accesibilidad para un look mas limpio en este portal */
:deep(.transition-all) {
    -webkit-tap-highlight-color: transparent;
}
</style>
