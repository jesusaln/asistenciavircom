<template>
    <div class="list-none">
        <component
            :is="target === '_blank' ? 'a' : Link"
            :href="href"
            :target="target"
            prefetch
            class="group relative flex items-center py-2.5 px-4 text-white/60 transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] hover:text-white focus:outline-none rounded-xl mx-2 mb-1 overflow-hidden"
            :class="{
                'bg-white/[0.04] text-white/95 shadow-[0_4px_12px_rgba(0,0,0,0.2)] border border-white/[0.03]': isActive,
                'justify-center': collapsed,
                'hover:bg-white/[0.02]': !isActive && !collapsed,
                'px-4': !collapsed,
            }"
            :aria-current="isActive ? 'page' : undefined"
        >
            <!-- Background Glow on Active -->
            <div v-if="isActive" class="absolute inset-0 bg-gradient-to-r from-brand-500/5 to-transparent pointer-events-none"></div>
            
            <div v-if="icon" class="relative flex items-center justify-center z-10">
                <div v-if="isActive" class="absolute -inset-2 bg-brand-500/10 blur-lg rounded-full animate-pulse"></div>
                <FontAwesomeIcon
                    :icon="iconObject"
                    class="flex-shrink-0 transition-all duration-700 relative z-10"
                    :class="{
                        'mr-3.5 h-[14px] w-[14px]': !collapsed,
                        'h-5 w-5': collapsed,
                        'text-brand-500 drop-shadow-[0_0_8px_rgba(245,158,11,0.5)] scale-110': isActive,
                        'text-white/50 group-hover:text-brand-500/80 group-hover:scale-110': !isActive
                    }"
                />
            </div>
            
            <span
                v-if="!collapsed"
                class="font-black uppercase text-[10px] tracking-[0.18em] truncate transition-all duration-500 relative z-10"
                :class="isActive ? 'text-white translate-x-0.5' : 'text-white/60 group-hover:text-white group-hover:translate-x-1'"
            >
                <slot />
            </span>
            
            <!-- Active Indicator Bar -->
            <div v-if="isActive && !collapsed" class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-4 bg-brand-500 rounded-full shadow-[0_0_10px_rgba(245,158,11,0.8)]"></div>
            
            <!-- Hover Slide Effect -->
            <div class="absolute inset-0 bg-gradient-to-r from-brand-500/[0.02] to-transparent -translate-x-full group-hover:translate-x-0 transition-transform duration-700 ease-out pointer-events-none"></div>
        </component>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    icon: {
        type: [String, Array, Object],
        default: null,
    },
    exact: {
        type: Boolean,
        default: false,
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
    target: {
        type: String,
        default: null,
    },
});

const page = usePage();

const isActive = computed(() => {
    const currentUrl = page.url;
    const currentPath = currentUrl.split(/[?#]/)[0];
    
    // Si el href es una URL completa, extraemos solo el path
    let targetPath = props.href;
    try {
        if (targetPath.startsWith('http')) {
            targetPath = new URL(targetPath).pathname;
        }
    } catch (e) {
        // Fallback si no es una URL válida
    }
    
    targetPath = targetPath.split(/[?#]/)[0];

    if (props.exact) {
        return currentPath === targetPath;
    }

    if (targetPath === '/') {
        return currentPath === '/';
    }

    return currentPath === targetPath || currentPath.startsWith(targetPath + '/');
});

const iconObject = computed(() => {
    if (!props.icon) return null;
    if (typeof props.icon === 'object') return props.icon;
    if (typeof props.icon === 'string') {
        if (props.icon.startsWith('fa-')) {
            const iconName = props.icon.replace('fa-', '');
            return ['fas', iconName];
        }
        return ['fas', props.icon];
    }
    return null;
});
</script>

<style scoped>
.group:focus-visible {
    outline: 1px solid rgba(245, 158, 11, 0.4);
    outline-offset: 2px;
}

/* Smooth slide animation for span */
span {
    will-change: transform, color;
}
</style>

