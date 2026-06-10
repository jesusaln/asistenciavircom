<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from '@/Utils/Swal';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { 
    faPlus, faTrash, faEdit, faCheckCircle, faCircle, 
    faCalendarAlt, faExclamationTriangle, faFilter,
    faClock, faCheck, faChevronRight, faSun, faSync, 
    faTimes, faBell, faStar, faClipboardList, faPaperclip, faExternalLinkAlt, faUser
} from '@fortawesome/free-solid-svg-icons';
import axios from 'axios';

const props = defineProps({
    todos: Array,
    prefilled: Object,
    users: Array,
    open_id: [Number, String]
});

import { onMounted } from 'vue';

onMounted(() => {
    if (props.prefilled) {
        form.title = props.prefilled.title;
        form.description = props.prefilled.description;
        showNewTaskModal.value = true;
    }

    if (props.open_id) {
        const idToSearch = String(props.open_id);
        const todo = props.todos.find(t => String(t.id) === idToSearch);
        if (todo) {
            openDetail(todo);
        }
    }
});

const filter = ref('all');
const showNewTaskModal = ref(false);
const selectedTodo = ref(null);
const showDetailPanel = ref(false);

const filteredTodos = computed(() => {
    let list = props.todos || [];
    if (filter.value === 'my_day') return list.filter(t => t.is_my_day);
    if (filter.value === 'pending') return list.filter(t => t.status === 'pending');
    if (filter.value === 'completed') return list.filter(t => t.status === 'completed');
    return list;
});

const form = useForm({
    title: '',
    description: '',
    priority: 'medium',
    due_date: null,
    is_my_day: false,
    assigned_user_id: null,
});

const detailForm = useForm({
    id: null,
    title: '',
    description: '',
    notes: '',
    priority: 'medium',
    status: 'pending',
    due_date: null,
    reminder_at: null,
    recurrence: 'none',
    is_my_day: false,
    steps: [],
});

const newStepTitle = ref('');

const openDetail = (todo) => {
    selectedTodo.value = todo;
    detailForm.id = todo.id;
    detailForm.title = todo.title;
    detailForm.description = todo.description;
    detailForm.notes = todo.notes || '';
    detailForm.priority = todo.priority;
    detailForm.status = todo.status;
    detailForm.due_date = todo.due_date ? String(todo.due_date).substring(0, 10) : null;
    detailForm.reminder_at = todo.reminder_at ? String(todo.reminder_at).substring(0, 16) : null;
    detailForm.recurrence = todo.recurrence || 'none';
    detailForm.is_my_day = todo.is_my_day || false;
    detailForm.steps = todo.steps ? JSON.parse(JSON.stringify(todo.steps)) : [];
    showDetailPanel.value = true;
};

const saveDetail = () => {
    if (!detailForm.id) return;
    
    // Usamos router.put para mantener la sincronización con Inertia pero con preserveScroll
    router.put(`/mis-pendientes/${detailForm.id}`, detailForm.data(), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // Sincronizado
        },
        onError: (errors) => {
            console.error('Error al guardar:', errors);
        }
    });
};

const addStep = () => {
    if (!newStepTitle.value.trim()) return;
    detailForm.steps.push({
        title: newStepTitle.value,
        is_completed: false
    });
    newStepTitle.value = '';
    saveDetail();
};

const toggleStep = (index) => {
    detailForm.steps[index].is_completed = !detailForm.steps[index].is_completed;
    saveDetail();
};

const removeStep = (index) => {
    if (detailForm.steps[index].id) {
        detailForm.steps[index].delete = true;
    } else {
        detailForm.steps.splice(index, 1);
    }
    saveDetail();
};

const submit = () => {
    const data = {
        title: form.title,
        description: form.description,
        priority: form.priority,
        due_date: form.due_date,
        is_my_day: form.is_my_day,
    };
    if (form.assigned_user_id) {
        data.user_id = form.assigned_user_id;
    }
    form.transform(() => data).post(route('todos.store'), {
        onSuccess: () => {
            form.reset();
            showNewTaskModal.value = false;
        },
    });
};

const toggleStatus = (todo) => {
    if (!todo) return;
    const newStatus = todo.status === 'pending' ? 'completed' : 'pending';
    
    // Si estamos en el detalle, actualizamos el form también
    if (detailForm.id === todo.id) {
        detailForm.status = newStatus;
    }

    router.put(`/mis-pendientes/${todo.id}`, {
        status: newStatus
    }, {
        preserveScroll: true,
        preserveState: true
    });
};

const toggleMyDay = (todo) => {
    if (!todo) return;
    const newValue = !todo.is_my_day;
    
    if (detailForm.id === todo.id) {
        detailForm.is_my_day = newValue;
    }

    router.put(`/mis-pendientes/${todo.id}`, {
        is_my_day: newValue
    }, {
        preserveScroll: true,
        preserveState: true
    });
};

const deleteTodo = async (todo) => {
    if (!todo) return;
    const { isConfirmed } = await Swal.fire({
        title: 'Eliminar tarea',
        text: '¿Estás seguro de eliminar esta tarea?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No',
    });
    if (!isConfirmed) return;
    router.delete(route('todos.destroy', todo.id), {
            onSuccess: () => {
                showDetailPanel.value = false;
                selectedTodo.value = null;
            },
            preserveScroll: true
        });
};

const uploadFile = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    axios.post(`/api/todos/${selectedTodo.value.id}/attachments`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    }).then(response => {
        if (!selectedTodo.value.attachments) selectedTodo.value.attachments = [];
        selectedTodo.value.attachments.push(response.data);
    }).catch(error => {
        console.error('Error al subir archivo:', error);
        Swal.fire({ title: 'Error', text: 'Error al subir el archivo', icon: 'error', confirmButtonText: 'Aceptar' });
    });
};

const deleteAttachment = async (file) => {
    const { isConfirmed } = await Swal.fire({
        title: 'Eliminar archivo',
        text: '¿Eliminar este archivo?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No',
    });
    if (!isConfirmed) return;

    axios.delete(`/api/todos/${selectedTodo.value.id}/attachments/${file.id}`)
        .then(() => {
            selectedTodo.value.attachments = selectedTodo.value.attachments.filter(a => a.id !== file.id);
        });
};

const setDueDate = (option) => {
    const today = new Date();
    let target = new Date();

    if (option === 'today') {
        // Keep today
    } else if (option === 'tomorrow') {
        target.setDate(today.getDate() + 1);
    } else if (option === 'next_week') {
        target.setDate(today.getDate() + (1 + 7 - today.getDay()) % 7 || 7);
    } else if (option === 'none') {
        detailForm.due_date = null;
        saveDetail();
        return;
    }

    detailForm.due_date = target.toISOString().split('T')[0];
    saveDetail();
};

const setReminder = (option) => {
    const today = new Date();
    let target = new Date();
    target.setMinutes(0, 0, 0);

    if (option === 'today_later') {
        target.setHours(today.getHours() + 3);
    } else if (option === 'tomorrow_morning') {
        target.setDate(today.getDate() + 1);
        target.setHours(9, 0);
    } else if (option === 'next_week') {
        target.setDate(today.getDate() + (1 + 7 - today.getDay()) % 7 || 7);
        target.setHours(9, 0);
    } else if (option === 'none') {
        detailForm.reminder_at = null;
        saveDetail();
        return;
    }

    detailForm.reminder_at = target.toISOString().slice(0, 16);
    saveDetail();
};

const linkify = (text) => {
    if (!text) return '';
    const urlPattern = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
    return text.replace(urlPattern, '<a href="$1" target="_blank" class="text-brand-500 hover:underline">$1</a>');
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('es-MX', {
        day: 'numeric',
        month: 'short',
    });
};

const formatDateTime = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleString('es-MX', { 
        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' 
    });
};

const getPriorityLabel = (priority) => {
    const labels = { high: 'Alta', medium: 'Media', low: 'Baja' };
    return labels[priority] || 'Media';
};

const getRecurrenceLabel = (recurrence) => {
    const labels = {
        daily: 'Diariamente',
        weekly: 'Semanalmente',
        monthly: 'Mensualmente',
        yearly: 'Anualmente'
    };
    return labels[recurrence] || 'Nunca';
};

const activeMenu = ref(null);

const stats = computed(() => {
    const list = props.todos || [];
    return {
        total: list.length,
        pending: list.filter(t => t.status === 'pending').length,
        completed: list.filter(t => t.status === 'completed').length,
        myDay: list.filter(t => t.is_my_day).length
    };
});
</script>

<template>
    <AppLayout title="Mis Pendientes">
        <Head title="Mis Pendientes" />

        <div class="flex h-[calc(100vh-64px)] overflow-hidden bg-[var(--ui-bg)] transition-colors duration-500" @click="activeMenu = null">
            <!-- Main Content -->
            <div class="flex-1 overflow-y-auto px-6 py-10 custom-scrollbar bg-[var(--ui-surface)] transition-colors duration-500">
                <div class="max-w-4xl mx-auto">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-10">
                        <div>
                            <div class="flex items-center gap-4 mb-2">
                                <div class="w-12 h-12 rounded-2xl bg-brand-500/10 flex items-center justify-center text-brand-500 shadow-lg shadow-brand-500/5">
                                    <FontAwesomeIcon :icon="filter === 'my_day' ? faSun : faClipboardList" class="text-xl" />
                                </div>
                                <h1 class="text-2xl font-black text-[var(--ui-text)] uppercase tracking-widest">
                                    <span v-if="filter === 'my_day'">Mi <span class="text-brand-500">Día</span></span>
                                    <span v-else>Mis <span class="text-brand-500">Pendientes</span></span>
                                </h1>
                            </div>
                            <p class="text-[var(--ui-text-soft)] font-bold text-xs uppercase tracking-[0.2em] ml-1">
                                {{ filter === 'my_day' ? 'Enfócate en lo importante para hoy' : 'Organiza tu flujo de trabajo diario' }}
                            </p>
                        </div>

                        <div class="flex items-center gap-4">
                            <button 
                                @click="showNewTaskModal = true"
                                class="flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-brand-500 to-brand-600 rounded-2xl text-white font-black text-xs uppercase tracking-widest shadow-lg shadow-brand-600/20 hover:scale-105 active:scale-95 transition-all"
                            >
                                <FontAwesomeIcon :icon="faPlus" />
                                Nueva Tarea
                            </button>
                        </div>
                    </div>

                    <!-- Filters / Tabs -->
                    <div class="flex items-center gap-2 mb-8 p-1.5 bg-[var(--ui-surface-soft)] backdrop-blur-xl border border-[var(--ui-border)] rounded-2xl w-fit">
                        <button @click="filter = 'all'" class="filter-btn" :class="{ 'active': filter === 'all' }">Todas</button>
                        <button @click="filter = 'my_day'" class="filter-btn flex items-center gap-2" :class="{ 'active': filter === 'my_day' }">
                            <FontAwesomeIcon :icon="faSun" class="text-brand-500" /> Mi Día
                        </button>
                        <button @click="filter = 'pending'" class="filter-btn" :class="{ 'active': filter === 'pending' }">Pendientes</button>
                        <button @click="filter = 'completed'" class="filter-btn" :class="{ 'active': filter === 'completed' }">Completadas</button>
                    </div>

                    <!-- List Section -->
                    <div class="space-y-3">
                        <div v-if="filteredTodos.length === 0" class="flex flex-col items-center justify-center py-20 bg-[var(--ui-surface-soft)] rounded-[2.5rem] border border-dashed border-[var(--ui-border)]">
                            <div class="w-16 h-16 rounded-full bg-[var(--ui-surface)] flex items-center justify-center mb-4">
                                <FontAwesomeIcon :icon="faCheckCircle" class="text-2xl text-[var(--ui-text-soft)]" />
                            </div>
                            <h3 class="text-sm font-black text-[var(--ui-text-soft)] uppercase tracking-widest">No hay tareas que mostrar</h3>
                        </div>

                        <div 
                            v-for="todo in filteredTodos" 
                            :key="todo.id"
                            @click="openDetail(todo)"
                            class="todo-card-v2 group"
                            :class="{ 'active': selectedTodo?.id === todo.id }"
                        >
                            <div class="flex items-center gap-4">
                                <button 
                                    @click.stop="toggleStatus(todo)"
                                    class="checkbox-mini"
                                    :class="{ 'checked': todo.status === 'completed' }"
                                >
                                    <FontAwesomeIcon v-if="todo.status === 'completed'" :icon="faCheck" />
                                </button>

                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-bold text-[var(--ui-text)] tracking-wide truncate" :class="{ 'line-through text-[var(--ui-text-soft)]': todo.status === 'completed' }">
                                        {{ todo.title }}
                                    </h3>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-[9px] font-black uppercase tracking-wider" :class="todo.priority === 'high' ? 'text-rose-500' : (todo.priority === 'medium' ? 'text-brand-500' : 'text-emerald-500')">
                                            {{ getPriorityLabel(todo.priority) }}
                                        </span>
                                        <span v-if="todo.steps && todo.steps.length" class="text-[9px] text-slate-500 font-bold uppercase flex items-center gap-1">
                                            <FontAwesomeIcon :icon="faCheck" class="text-[8px]" />
                                            {{ todo.steps.filter(s => s.is_completed).length }}/{{ todo.steps.length }} Pasos
                                        </span>
                                        <span v-if="todo.due_date" class="text-[9px] text-slate-500 font-bold uppercase flex items-center gap-1">
                                            <FontAwesomeIcon :icon="faCalendarAlt" /> {{ formatDate(todo.due_date) }}
                                        </span>
                                        <FontAwesomeIcon v-if="todo.recurrence !== 'none'" :icon="faSync" class="text-[9px] text-slate-600" />
                                        <span v-if="todo.user" class="text-[9px] text-slate-500 font-bold uppercase flex items-center gap-1">
                                            <FontAwesomeIcon :icon="faUser" class="text-[8px]" /> {{ todo.user.name }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button 
                                        v-if="!todo.is_bitacora"
                                        @click.stop="toggleMyDay(todo)"
                                        class="p-2 transition-all"
                                        :class="todo.is_my_day ? 'text-brand-500' : 'text-slate-700 hover:text-slate-500'"
                                        title="Añadir a Mi Día"
                                    >
                                        <FontAwesomeIcon :icon="faSun" />
                                    </button>

                                    <button 
                                        @click.stop="deleteTodo(todo)"
                                        class="p-2 text-slate-700 hover:text-rose-500 transition-all opacity-0 group-hover:opacity-100"
                                        title="Eliminar tarea"
                                    >
                                        <FontAwesomeIcon :icon="faTrash" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Sidebar Backdrop -->
            <Transition
                enter-active-class="transition-opacity ease-linear duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity ease-linear duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDetailPanel" @click="showDetailPanel = false" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-[40]"></div>
            </Transition>

            <!-- Detail Sidebar (Microsoft To Do Style) -->
            <Transition
                enter-active-class="transition transform ease-in-out duration-300"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition transform ease-in-out duration-300"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
            >
                <div v-if="showDetailPanel" @click.stop class="fixed top-16 right-0 bottom-0 w-full max-w-[450px] bg-[var(--ui-surface)] border-l border-[var(--ui-border)] shadow-2xl flex flex-col z-[50]">
                    <div class="p-6 border-b border-[var(--ui-border)] flex items-center justify-between bg-[var(--ui-surface-soft)] backdrop-blur-md sticky top-0 z-10">
                        <h2 class="text-xs font-black text-[var(--ui-text-soft)] uppercase tracking-[0.2em]">Detalles de Tarea</h2>
                        <button @click="showDetailPanel = false" class="w-8 h-8 rounded-xl bg-[var(--ui-surface)] border border-[var(--ui-border)] flex items-center justify-center text-[var(--ui-text-soft)] hover:text-rose-500 transition-colors">
                            <FontAwesomeIcon :icon="faTimes" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">
                        <!-- Info de Bitácora (Solo si es técnica) -->
                        <div v-if="selectedTodo?.is_bitacora" class="p-5 rounded-[1.5rem] bg-brand-500/10 border border-brand-500/20 space-y-3">
                            <div class="flex items-center gap-3 text-brand-500">
                                <FontAwesomeIcon :icon="faUser" class="text-xs" />
                                <span class="text-[10px] font-black uppercase tracking-wider">Cliente:</span>
                                <span class="text-xs font-bold">{{ selectedTodo.cliente_nombre || 'General' }}</span>
                            </div>
                            <div v-if="selectedTodo.ubicacion" class="flex items-center gap-3 text-[var(--ui-text-soft)]">
                                <FontAwesomeIcon :icon="faClipboardList" class="text-xs" />
                                <span class="text-[10px] font-black uppercase tracking-wider">Ubicación:</span>
                                <span class="text-xs italic">{{ selectedTodo.ubicacion }}</span>
                            </div>
                        </div>

                        <!-- Main Title Input -->
                        <div class="space-y-4">
                            <div class="flex items-start gap-4 bg-[var(--ui-surface-soft)] p-5 rounded-[1.5rem] border border-[var(--ui-border)]">
                                <button @click="toggleStatus(selectedTodo)" class="checkbox-mini mt-1.5" :class="{ 'checked': detailForm.status === 'completed' }">
                                    <FontAwesomeIcon v-if="detailForm.status === 'completed'" :icon="faCheck" />
                                </button>
                                <textarea 
                                    v-model="detailForm.title" 
                                    @blur="saveDetail"
                                    class="flex-1 bg-transparent border-none text-[var(--ui-text)] font-bold text-lg p-0 focus:ring-0 resize-none leading-tight"
                                    rows="2"
                                    placeholder="Título de la tarea"
                                ></textarea>
                            </div>

                            <!-- Steps Section -->
                            <div v-if="!selectedTodo?.is_bitacora" class="bg-[var(--ui-surface-soft)] p-4 rounded-[1.5rem] border border-[var(--ui-border)] space-y-3">
                                <div v-for="(step, idx) in detailForm.steps.filter(s => !s.delete)" :key="idx" class="flex items-center gap-3 group/step">
                                    <button @click="toggleStep(idx)" class="w-4 h-4 rounded-md border border-[var(--ui-border)] flex items-center justify-center text-[7px]" :class="{ 'bg-emerald-500 border-emerald-500 text-white': step.is_completed }">
                                        <FontAwesomeIcon v-if="step.is_completed" :icon="faCheck" />
                                    </button>
                                    <input 
                                        v-model="step.title" 
                                        @blur="saveDetail"
                                        class="flex-1 bg-transparent border-none text-xs text-[var(--ui-text)] p-0 focus:ring-0"
                                        :class="{ 'line-through text-[var(--ui-text-soft)]': step.is_completed }"
                                    >
                                    <button @click="removeStep(idx)" class="opacity-0 group-hover/step:opacity-100 text-slate-600 hover:text-rose-500 transition-all">
                                        <FontAwesomeIcon :icon="faTimes" class="text-[10px]" />
                                    </button>
                                </div>
                                <div class="flex items-center gap-3 pt-2">
                                    <FontAwesomeIcon :icon="faPlus" class="text-brand-500 text-[10px]" />
                                    <input 
                                        v-model="newStepTitle" 
                                        @keyup.enter="addStep"
                                        placeholder="Siguiente paso"
                                        class="flex-1 bg-transparent border-none text-xs text-brand-500/50 placeholder:text-slate-600 p-0 focus:ring-0"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Add to My Day -->
                        <button 
                            v-if="!selectedTodo?.is_bitacora"
                            @click="toggleMyDay(selectedTodo)"
                            class="w-full flex items-center gap-4 p-5 rounded-[1.5rem] border border-[var(--ui-border)] hover:bg-[var(--ui-surface-soft)] transition-all text-left"
                            :class="detailForm.is_my_day ? 'bg-brand-500/10 border-brand-500/20 text-brand-500 shadow-lg shadow-brand-500/5' : 'bg-[var(--ui-surface-soft)] text-[var(--ui-text-soft)]'"
                        >
                            <FontAwesomeIcon :icon="faSun" :class="{ 'animate-pulse': detailForm.is_my_day }" />
                            <span class="text-xs font-black uppercase tracking-wider">{{ detailForm.is_my_day ? 'Añadido a Mi Día' : 'Añadir a Mi Día' }}</span>
                        </button>

                        <!-- Settings Group (Microsoft To Do Style) -->
                        <div v-if="!selectedTodo?.is_bitacora" class="space-y-2">
                            <!-- Reminder Dropdown -->
                            <div class="relative">
                                <button 
                                    @click.stop="activeMenu = activeMenu === 'reminder' ? null : 'reminder'"
                                    class="w-full flex items-center gap-4 p-5 rounded-[1.5rem] border border-[var(--ui-border)] bg-[var(--ui-surface-soft)] hover:bg-[var(--ui-surface)] transition-all text-left"
                                    :class="{ 'border-brand-500/20 bg-brand-500/5': detailForm.reminder_at }"
                                >
                                    <FontAwesomeIcon :icon="faBell" :class="detailForm.reminder_at ? 'text-brand-500' : 'text-[var(--ui-text-soft)]'" />
                                    <div class="flex-1">
                                        <span class="block text-[8px] font-black uppercase text-[var(--ui-text-soft)] mb-0.5">Recordarme</span>
                                        <span class="text-xs font-bold" :class="detailForm.reminder_at ? 'text-brand-500' : 'text-[var(--ui-text-soft)]'">
                                            {{ detailForm.reminder_at ? formatDateTime(detailForm.reminder_at) : 'Sin recordatorio' }}
                                        </span>
                                    </div>
                                    <FontAwesomeIcon v-if="detailForm.reminder_at" @click.stop="setReminder('none')" :icon="faTimes" class="text-[10px] text-[var(--ui-text-soft)] hover:text-rose-500" />
                                </button>
                                
                                <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                    <div v-if="activeMenu === 'reminder'" @click.stop class="absolute left-0 right-0 mt-2 p-2 bg-slate-800 border border-white/10 rounded-2xl shadow-2xl z-20">
                                        <button @click="setReminder('today_later'); activeMenu = null" class="menu-item">
                                            <FontAwesomeIcon :icon="faClock" class="w-4" /> Hoy más tarde (18:00)
                                        </button>
                                        <button @click="setReminder('tomorrow_morning'); activeMenu = null" class="menu-item">
                                            <FontAwesomeIcon :icon="faSun" class="w-4" /> Mañana (09:00)
                                        </button>
                                        <button @click="setReminder('next_week'); activeMenu = null" class="menu-item">
                                            <FontAwesomeIcon :icon="faCalendarAlt" class="w-4" /> Próxima semana (Lunes 09:00)
                                        </button>
                                        <div class="border-t border-white/5 my-1"></div>
                                        <button @click.stop="$refs.reminderPicker.showPicker ? $refs.reminderPicker.showPicker() : $refs.reminderPicker.click()" class="menu-item">
                                            <FontAwesomeIcon :icon="faEdit" class="w-4" /> Elegir fecha y hora
                                            <input type="datetime-local" ref="reminderPicker" v-model="detailForm.reminder_at" @change="saveDetail(); activeMenu = null" class="absolute inset-0 opacity-0 pointer-events-none">
                                        </button>
                                    </div>
                                </Transition>
                            </div>

                            <!-- Due Date Dropdown -->
                            <div class="relative">
                                <button 
                                    @click.stop="activeMenu = activeMenu === 'due_date' ? null : 'due_date'"
                                    class="w-full flex items-center gap-4 p-5 rounded-[1.5rem] border border-[var(--ui-border)] bg-[var(--ui-surface-soft)] hover:bg-[var(--ui-surface)] transition-all text-left"
                                    :class="{ 'border-emerald-500/20 bg-emerald-500/5': detailForm.due_date }"
                                >
                                    <FontAwesomeIcon :icon="faCalendarAlt" :class="detailForm.due_date ? 'text-emerald-500' : 'text-[var(--ui-text-soft)]'" />
                                    <div class="flex-1">
                                        <span class="block text-[8px] font-black uppercase text-[var(--ui-text-soft)] mb-0.5">Fecha de vencimiento</span>
                                        <span class="text-xs font-bold" :class="detailForm.due_date ? 'text-emerald-500' : 'text-[var(--ui-text-soft)]'">
                                            {{ detailForm.due_date ? formatDate(detailForm.due_date) : 'Sin vencimiento' }}
                                        </span>
                                    </div>
                                    <FontAwesomeIcon v-if="detailForm.due_date" @click.stop="setDueDate('none')" :icon="faTimes" class="text-[10px] text-[var(--ui-text-soft)] hover:text-rose-500" />
                                </button>
                                
                                <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                    <div v-if="activeMenu === 'due_date'" @click.stop class="absolute left-0 right-0 mt-2 p-2 bg-slate-800 border border-white/10 rounded-2xl shadow-2xl z-20">
                                        <button @click="setDueDate('today'); activeMenu = null" class="menu-item">
                                            <FontAwesomeIcon :icon="faSun" class="w-4 text-brand-500" /> Hoy
                                        </button>
                                        <button @click="setDueDate('tomorrow'); activeMenu = null" class="menu-item">
                                            <FontAwesomeIcon :icon="faChevronRight" class="w-4 text-emerald-500" /> Mañana
                                        </button>
                                        <button @click="setDueDate('next_week'); activeMenu = null" class="menu-item">
                                            <FontAwesomeIcon :icon="faCalendarAlt" class="w-4 text-indigo-500" /> Próxima semana
                                        </button>
                                        <div class="border-t border-white/5 my-1"></div>
                                        <button @click.stop="$refs.dueDatePicker.showPicker ? $refs.dueDatePicker.showPicker() : $refs.dueDatePicker.click()" class="menu-item">
                                            <FontAwesomeIcon :icon="faEdit" class="w-4" /> Elegir una fecha
                                            <input type="date" ref="dueDatePicker" v-model="detailForm.due_date" @change="saveDetail(); activeMenu = null" class="absolute inset-0 opacity-0 pointer-events-none">
                                        </button>
                                    </div>
                                </Transition>
                            </div>

                            <!-- Recurrence Dropdown -->
                            <div class="relative">
                                <button 
                                    @click.stop="activeMenu = activeMenu === 'recurrence' ? null : 'recurrence'"
                                    class="w-full flex items-center gap-4 p-5 rounded-[1.5rem] border border-white/5 bg-slate-800/20 hover:bg-white/5 transition-all text-left"
                                    :class="{ 'border-sky-500/20 bg-sky-500/5': detailForm.recurrence !== 'none' }"
                                >
                                    <FontAwesomeIcon :icon="faSync" :class="detailForm.recurrence !== 'none' ? 'text-sky-500' : 'text-slate-600'" />
                                    <div class="flex-1">
                                        <span class="block text-[8px] font-black uppercase text-slate-500 mb-0.5">Repetir</span>
                                        <span class="text-xs font-bold" :class="detailForm.recurrence !== 'none' ? 'text-sky-500' : 'text-slate-400'">
                                            {{ detailForm.recurrence === 'none' ? 'Nunca' : getRecurrenceLabel(detailForm.recurrence) }}
                                        </span>
                                    </div>
                                </button>
                                
                                <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                    <div v-if="activeMenu === 'recurrence'" @click.stop class="absolute left-0 right-0 mt-2 p-2 bg-slate-800 border border-white/10 rounded-2xl shadow-2xl z-20">
                                        <button v-for="opt in [
                                            { id: 'none', label: 'Nunca' },
                                            { id: 'daily', label: 'Diariamente' },
                                            { id: 'weekly', label: 'Semanalmente' },
                                            { id: 'monthly', label: 'Mensualmente' },
                                            { id: 'yearly', label: 'Anualmente' }
                                        ]" :key="opt.id" @click="detailForm.recurrence = opt.id; saveDetail(); activeMenu = null" class="menu-item" :class="{ 'text-sky-500 bg-sky-500/10': detailForm.recurrence === opt.id }">
                                            {{ opt.label }}
                                        </button>
                                    </div>
                                </Transition>
                            </div>
                        </div>

                        <!-- Linked Reference / Description -->
                        <div v-if="detailForm.description" class="bg-slate-800/20 p-5 rounded-[1.5rem] border border-white/5">
                            <label class="block text-[8px] font-black uppercase text-slate-500 mb-2 ml-1">Descripción / Referencia</label>
                            <div 
                                v-html="linkify(detailForm.description)" 
                                class="text-xs text-slate-400 leading-relaxed break-words"
                            ></div>
                        </div>

                        <!-- Attachments Section -->
                        <div v-if="!selectedTodo?.is_bitacora" class="space-y-4">
                            <div class="flex items-center justify-between ml-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Archivos Adjuntos</label>
                                <button @click="$refs.fileInput.click()" class="text-[10px] font-black text-brand-500 uppercase hover:text-brand-400 transition-colors">
                                    Añadir
                                </button>
                            </div>
                            <input type="file" ref="fileInput" class="hidden" @change="uploadFile">
                            
                            <div class="space-y-2">
                                <div v-for="file in selectedTodo.attachments" :key="file.id" class="flex items-center gap-3 p-3 bg-slate-800/20 rounded-xl border border-white/5 group/file">
                                    <div class="w-8 h-8 rounded-lg bg-slate-700/50 flex items-center justify-center text-slate-400">
                                        <FontAwesomeIcon :icon="faPaperclip" class="text-xs" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11px] font-bold text-[var(--ui-text)] truncate">{{ file.file_name }}</p>
                                        <p class="text-[8px] text-[var(--ui-text-soft)] uppercase font-black">{{ (file.file_size / 1024).toFixed(1) }} KB</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a :href="file.url" target="_blank" class="p-2 text-[var(--ui-text-soft)] hover:text-[var(--ui-text)] transition-colors">
                                            <FontAwesomeIcon :icon="faExternalLinkAlt" class="text-[10px]" />
                                        </a>
                                        <button @click="deleteAttachment(file)" class="p-2 text-[var(--ui-text-soft)] hover:text-rose-500 transition-colors">
                                            <FontAwesomeIcon :icon="faTrash" class="text-[10px]" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes Section -->
                        <div v-if="!selectedTodo?.is_bitacora" class="space-y-3">
                            <label class="block text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-[0.2em] ml-1">Notas</label>
                            <textarea 
                                v-model="detailForm.notes" 
                                @blur="saveDetail"
                                placeholder="Añade una nota..."
                                class="w-full bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-[1.5rem] p-5 text-xs text-[var(--ui-text)] placeholder:text-[var(--ui-text-soft)] focus:ring-0 focus:border-brand-500/30 transition-all min-h-[150px] resize-none leading-relaxed"
                            ></textarea>
                        </div>
                    </div>

                    <div class="p-6 border-t border-[var(--ui-border)] bg-[var(--ui-surface-soft)] flex items-center justify-between mt-auto">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">{{ formatDate(selectedTodo.created_at) }}</span>
                        </div>
                        <button @click="deleteTodo(selectedTodo)" class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-white transition-all duration-300">
                            <FontAwesomeIcon :icon="faTrash" class="text-xs" />
                        </button>
                    </div>
                </div>
            </Transition>
        </div>

        <!-- New Task Modal (Custom Overlay) -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showNewTaskModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
                <div 
                    class="bg-[var(--ui-surface)] w-full max-w-lg rounded-[2.5rem] border border-[var(--ui-border)] shadow-2xl overflow-hidden animate-modal-in"
                    @click.stop
                >
                    <div class="px-8 py-8 border-b border-[var(--ui-border)] bg-gradient-to-br from-[var(--ui-surface-soft)] to-transparent">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-black text-[var(--ui-text)] uppercase tracking-wider">Nueva Tarea</h2>
                                <p class="text-[10px] font-bold text-[var(--ui-text-soft)] uppercase tracking-[0.2em] mt-1">Organiza tu próximo paso</p>
                            </div>
                            <button @click="showNewTaskModal = false" class="w-10 h-10 rounded-2xl bg-[var(--ui-surface-alt)] flex items-center justify-center text-[var(--ui-text-soft)] hover:text-rose-500 transition-colors">
                                <FontAwesomeIcon :icon="faPlus" class="rotate-45" />
                            </button>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="p-8 space-y-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-[0.2em] ml-1">Título</label>
                            <input 
                                v-model="form.title" 
                                type="text" 
                                required
                                placeholder="¿Qué hay que hacer?"
                                class="w-full bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-2xl px-5 py-4 text-sm text-[var(--ui-text)] placeholder:text-[var(--ui-text-soft)] focus:border-brand-500/50 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none"
                            >
                        </div>

                        <div v-if="users && users.length" class="space-y-2">
                            <label class="block text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-[0.2em] ml-1">Asignar a</label>
                            <select 
                                v-model="form.assigned_user_id"
                                class="w-full bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-2xl px-5 py-4 text-xs font-black uppercase text-[var(--ui-text)] focus:border-brand-500/50 transition-all outline-none cursor-pointer"
                            >
                                <option :value="null" class="bg-[var(--ui-surface)] text-[var(--ui-text)]">— A mí mismo —</option>
                                <option v-for="user in users" :key="user.id" :value="user.id" class="bg-[var(--ui-surface)] text-[var(--ui-text)]">{{ user.name }}</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-[0.2em] ml-1">Prioridad</label>
                                <select 
                                    v-model="form.priority"
                                    class="w-full bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-2xl px-5 py-4 text-xs font-black uppercase text-[var(--ui-text)] focus:border-brand-500/50 transition-all outline-none cursor-pointer"
                                >
                                    <option value="low" class="bg-[var(--ui-surface)] text-[var(--ui-text)]">Baja</option>
                                    <option value="medium" class="bg-[var(--ui-surface)] text-[var(--ui-text)]">Media</option>
                                    <option value="high" class="bg-[var(--ui-surface)] text-[var(--ui-text)]">Alta</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-[0.2em] ml-1">Mi Día</label>
                                <button 
                                    type="button"
                                    @click="form.is_my_day = !form.is_my_day"
                                    class="w-full flex items-center justify-center gap-3 px-5 py-4 rounded-2xl border transition-all"
                                    :class="form.is_my_day ? 'bg-brand-500/10 border-brand-500/30 text-brand-500' : 'bg-[var(--ui-surface-soft)] border-[var(--ui-border)] text-[var(--ui-text-soft)]'"
                                >
                                    <FontAwesomeIcon :icon="faSun" />
                                    <span class="text-[10px] font-black uppercase">{{ form.is_my_day ? 'Activado' : 'Añadir' }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button 
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-5 bg-gradient-to-r from-brand-500 to-brand-600 rounded-3xl text-white font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-brand-600/20 hover:shadow-brand-600/40 hover:-translate-y-1 active:scale-95 transition-all disabled:opacity-50"
                            >
                                {{ form.processing ? 'Guardando...' : 'Crear Tarea' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<style scoped>
.todo-card-v2 {
    background: var(--ui-surface-soft);
    border: 1px solid var(--ui-border);
    border-radius: 1.25rem;
    padding: 16px 20px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.todo-card-v2:hover {
    background: var(--ui-surface);
    border-color: var(--ui-accent);
    transform: translateY(-2px);
}

.todo-card-v2.active {
    background: var(--ui-surface);
    border-color: var(--ui-accent);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.checkbox-mini {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: transparent;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.checkbox-mini.checked {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.setting-row {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px 20px;
    border-bottom: 1px solid var(--ui-border);
    transition: all 0.2s ease;
}

.setting-row:last-child {
    border-bottom: none;
}

.setting-row:hover {
    background: rgba(255, 255, 255, 0.02);
}

input[type="date"], input[type="datetime-local"] {
    color-scheme: light dark;
}

.detail-input {
    width: 100%;
    background: transparent;
    border: none;
    color: var(--ui-text);
    font-size: 11px;
    padding: 0;
    outline: none;
    cursor: pointer;
}


.filter-btn {
    padding: 10px 24px;
    border-radius: 14px;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--ui-text-soft);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.filter-btn:hover {
    color: var(--ui-text);
}

.filter-btn.active {
    background: var(--ui-accent);
    color: var(--ui-accent-contrast);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

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

@keyframes modal-in {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.animate-modal-in {
    animation: modal-in 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
