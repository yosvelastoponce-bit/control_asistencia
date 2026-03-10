<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    profesores: {type: Array, default: () => []},
    cursos:     {type: Array, default: () => []},
    horarios:   {type: Array, default: () => []},
});

const cursoSeleccionado = ref(''); // ← guarda el curso seleccionado
const diaSeleccionado   = ref('')
const horarioSeleccionado = ref(null);

// Dias unicos disponibles en el horario
const diasDisponibles = computed(() => {
    const dias = [...new Set(props.horarios.map(h => h.dia))];
    return dias;
});

// Filtrar horarios por días seleccionado
const horariosFiltrados = computed(() => {
    if (!diaSeleccionado.value) return props.horarios;
    return props.horarios.filter(h => h.dia === diaSeleccionado.value);
});

const seleccionarHorario = (horario) => {
    horarioSeleccionado.value = horario;
};

const logout = () => {
    router.post(route('profesor.logout'));
}

</script>

<template>
    <AuthenticatedLayout>
        <Head title="profesores"></Head>

        <div class="py-12 max-w-5xl mx-auto space-y-8 px-4">

            <!-- Select de cursos -->
            <div>
                <label class="block text-sm font-medium mb-1">Seleccionar Curso</label>
                <select v-model="cursoSeleccionado" class="w-full border rounded px-3 py-2">
                    <option value="" disabled>-- Selecciona un curso --</option>
                    <option v-for="curso in props.cursos" :key="curso.id" :value="curso.id">
                        {{ curso.name }}
                    </option>
                </select>
            </div>

            <!-- Filtro por día -->
            <div>
                <label class="block text-sm font-medium mb-1">Filtrar por día</label>
                <div class="flex gap-2 flex-wrap">
                    <button
                        @click="diaSeleccionado = ''"
                        :class="diaSeleccionado === '' ? 'bg-blue-500 text-white' : 'bg-gray-200'"
                        class="px-3 py-1 rounded text-sm font-medium"
                    >
                        Todos
                    </button>
                    <button
                        v-for="dia in diasDisponibles"
                        :key="dia"
                        @click="diaSeleccionado = dia"
                        :class="diaSeleccionado === dia ? 'bg-blue-500 text-white' : 'bg-gray-200'"
                        class="px-3 py-1 rounded text-sm font-medium"
                    >
                        {{ dia }}
                    </button>
                </div>
            </div>

            <!-- Tabla de horarios -->
            <div>
                <h3 class="text-lg font-semibold mb-2">Mi Horario</h3>

                <div v-if="horariosFiltrados.length === 0" class="text-gray-500 text-sm">
                    No hay horarios disponibles.
                </div>

                <table v-else class="w-full border rounded text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left px-4 py-2">Día</th>
                            <th class="text-left px-4 py-2">Curso</th>
                            <th class="text-left px-4 py-2">Grado</th>
                            <th class="text-left px-4 py-2">Sección</th>
                            <th class="text-left px-4 py-2">Inicio</th>
                            <th class="text-left px-4 py-2">Fin</th>
                            <th class="text-left px-4 py-2">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="horario in horariosFiltrados"
                            :key="horario.id"
                            :class="horarioSeleccionado?.id === horario.id ? 'bg-blue-50' : 'hover:bg-gray-50'"
                        >
                            <td class="px-4 py-2 font-medium">{{ horario.dia }}</td>
                            <td class="px-4 py-2">{{ horario.subject }}</td>
                            <td class="px-4 py-2">{{ horario.grade }}</td>
                            <td class="px-4 py-2">{{ horario.section }}</td>
                            <td class="px-4 py-2">{{ horario.start_time }}</td>
                            <td class="px-4 py-2">{{ horario.end_time }}</td>
                            <td class="px-4 py-2">
                                <button
                                    @click="seleccionarHorario(horario)"
                                    class="bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-1 rounded"
                                >
                                    Seleccionar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Horario seleccionado -->
            <div v-if="horarioSeleccionado" class="bg-blue-50 border border-blue-200 rounded p-4">
                <h4 class="font-semibold text-blue-800 mb-2">Horario seleccionado:</h4>
                <p><span class="font-medium">Día:</span> {{ horarioSeleccionado.dia }}</p>
                <p><span class="font-medium">Curso:</span> {{ horarioSeleccionado.subject }}</p>
                <p><span class="font-medium">Grado:</span> {{ horarioSeleccionado.grade }}</p>
                <p><span class="font-medium">Sección:</span> {{ horarioSeleccionado.section }}</p>
                <p><span class="font-medium">Horario:</span> {{ horarioSeleccionado.start_time }} - {{ horarioSeleccionado.end_time }}</p>
            </div>

            <!-- Logout -->
            <button
                @click="logout"
                class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded"
            >
                Cerrar sesión
            </button>
        </div>
        
    </AuthenticatedLayout>
</template>