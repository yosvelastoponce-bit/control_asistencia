<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';
import axios from 'axios';

const props = defineProps({
    scheduleId: { type: Number, required: true },
});

const emit = defineEmits(['close']);

const scanning    = ref(false);
const resultado   = ref(null);  // último resultado
const historial   = ref([]);    // lista de todos los escaneados
const error       = ref('');
let   html5QrCode = null;

const iniciarScanner = async () => {
    error.value   = '';
    scanning.value = true;

    html5QrCode = new Html5Qrcode('qr-reader');

    try {
        await html5QrCode.start(
            { facingMode: 'environment' }, // cámara trasera
            { fps: 10, qrbox: { width: 250, height: 250 } },
            onScanSuccess,
            () => {} // errores de frame ignorados
        );
    } catch (err) {
        error.value    = 'No se pudo acceder a la cámara: ' + err;
        scanning.value = false;
    }
};

const onScanSuccess = async (decodedText) => {
    // Evitar escanear el mismo QR dos veces seguidas
    if (resultado.value?.uuid === decodedText) return;

    resultado.value = { uuid: decodedText, status: 'cargando', message: 'Registrando...' };

    try {
        const { data } = await axios.post(route('attendance.scan'), {
            uuid:        decodedText,
            schedule_id: props.scheduleId,
        });

        resultado.value = {
            uuid:    decodedText,
            status:  data.success ? 'success' : 'warning',
            message: data.message ?? 'Sin mensaje',
            student: data.student ?? 'Desconocido',
        };

        historial.value.unshift({
            ...resultado.value,
            hora: new Date().toLocaleTimeString(),
        });

    } catch (err) {
        const msg = err.response?.data?.message ?? err.message;
        resultado.value = {
            uuid:    decodedText,
            status:  'error',
            message: 'Error: ' + msg,
            student: 'Error',
        };
        historial.value.unshift({
            ...resultado.value,
            hora: new Date().toLocaleTimeString(),
        });
    }

    setTimeout(() => { resultado.value = null; }, 3000);
};

// Función para leer el CSRF token del cookie
const getCsrfToken = () => {
    const name  = 'XSRF-TOKEN=';
    const cookies = decodeURIComponent(document.cookie).split(';');
    for (let cookie of cookies) {
        cookie = cookie.trim();
        if (cookie.startsWith(name)) {
            return cookie.substring(name.length);
        }
    }
    return '';
};

const detenerScanner = async () => {
    if (html5QrCode && scanning.value) {
        await html5QrCode.stop();
        html5QrCode.clear();
    }
    scanning.value = false;
    emit('close');
};

onMounted(() => {
    iniciarScanner();
});

onUnmounted(async () => {
    if (html5QrCode && scanning.value) {
        await html5QrCode.stop();
        html5QrCode.clear();
    }
});
</script>

<template>
    <div class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">

            <!-- Header -->
            <div class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center">
                <h2 class="text-lg font-bold">📷 Escáner de Asistencia</h2>
                <button
                    @click="detenerScanner"
                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-1 rounded text-sm font-medium"
                >
                    Terminar Escáner
                </button>
            </div>

            <!-- Cámara -->
            <div class="p-4">
                <div id="qr-reader" class="w-full rounded overflow-hidden"></div>

                <!-- Error de cámara -->
                <div v-if="error" class="mt-3 bg-red-100 text-red-700 px-4 py-2 rounded text-sm">
                    {{ error }}
                </div>

                <!-- Resultado del último scan -->
                <div
                    v-if="resultado"
                    class="mt-3 px-4 py-3 rounded text-sm font-medium text-center"
                    :class="{
                        'bg-green-100 text-green-800 border border-green-300': resultado.status === 'success',
                        'bg-yellow-100 text-yellow-800 border border-yellow-300': resultado.status === 'warning',
                        'bg-red-100 text-red-700 border border-red-300': resultado.status === 'error',
                        'bg-gray-100 text-gray-600': resultado.status === 'cargando',
                    }"
                >
                    {{ resultado.message }}
                </div>
            </div>

            <!-- Historial de scans -->
            <div v-if="historial.length > 0" class="px-4 pb-4">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">
                    Registros de esta sesión ({{ historial.length }})
                </h3>
                <div class="max-h-40 overflow-y-auto space-y-1">
                    <div
                        v-for="(item, i) in historial"
                        :key="i"
                        class="flex justify-between items-center text-xs px-3 py-1 rounded"
                        :class="item.status === 'success' ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700'"
                    >
                        <span>{{ item.student ?? 'Desconocido' }}</span>
                        <span class="text-gray-400">{{ item.hora }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>