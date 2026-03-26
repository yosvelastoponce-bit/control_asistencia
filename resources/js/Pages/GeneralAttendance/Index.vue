<script setup>
import { ref, onUnmounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { Html5Qrcode } from 'html5-qrcode'

const props = defineProps({
  auth: Object,
})

const scannerActivo = ref(false)
const resultado = ref(null)
const historial = ref([])
const errorCamera = ref('')
const procesando = ref(false)
let qrScanner = null

const fechaHoy = new Date().toLocaleDateString('es-PE', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
})

const logout = () => router.post(route('general-attendance.logout'))

const playSound = (type) => {
  const ctx = new (window.AudioContext || window.webkitAudioContext)()
  const osc = ctx.createOscillator()
  const gain = ctx.createGain()

  osc.connect(gain)
  gain.connect(ctx.destination)

  if (type === 'success') {
    osc.frequency.setValueAtTime(880, ctx.currentTime)
    osc.frequency.setValueAtTime(1100, ctx.currentTime + 0.1)
    gain.gain.setValueAtTime(0.3, ctx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3)
    osc.start(ctx.currentTime)
    osc.stop(ctx.currentTime + 0.3)
  } else if (type === 'warning') {
    osc.frequency.setValueAtTime(440, ctx.currentTime)
    gain.gain.setValueAtTime(0.3, ctx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4)
    osc.start(ctx.currentTime)
    osc.stop(ctx.currentTime + 0.4)
  } else {
    osc.frequency.setValueAtTime(300, ctx.currentTime)
    osc.frequency.setValueAtTime(150, ctx.currentTime + 0.15)
    gain.gain.setValueAtTime(0.3, ctx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4)
    osc.start(ctx.currentTime)
    osc.stop(ctx.currentTime + 0.4)
  }
}

const iniciarScanner = async () => {
  errorCamera.value = ''
  scannerActivo.value = true
  resultado.value = null
  await nextTick()

  try {
    qrScanner = new Html5Qrcode('qr-video')
    await qrScanner.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 250, height: 250 } },
      onScanSuccess,
      () => {}
    )
  } catch (err) {
    errorCamera.value = 'No se pudo acceder a la camara: ' + err
    scannerActivo.value = false
    qrScanner = null
  }
}

const detenerScanner = async () => {
  if (qrScanner) {
    try { await qrScanner.stop() } catch (_) {}
    qrScanner = null
  }
  scannerActivo.value = false
}

const onScanSuccess = async (uuid) => {
  if (procesando.value || resultado.value?.uuid === uuid) return

  procesando.value = true
  resultado.value = { uuid, message: 'Registrando...', status: 'loading' }

  try {
    const { data } = await axios.post(route('attendance.general.scan'), { uuid })
    resultado.value = { uuid, ...data }

    if (data.success) playSound('success')
    else playSound(data.status === 'already_registered' ? 'warning' : 'error')

    if (data.student) {
      historial.value.unshift({
        ...data,
        hora: new Date().toLocaleTimeString('es-PE'),
      })
    }
  } catch (err) {
    playSound('error')
    resultado.value = {
      uuid,
      success: false,
      message: err.response?.data?.message ?? 'Error al registrar.',
      status: 'error',
    }
  } finally {
    setTimeout(() => {
      resultado.value = null
      procesando.value = false
    }, 3500)
  }
}

onUnmounted(() => detenerScanner())
</script>

<template>
  <div class="min-h-screen bg-white text-gray-900 flex">
    <aside class="hidden lg:flex fixed left-0 top-0 h-full w-64 bg-gray-100 border-r border-gray-200 flex-col z-40">
      <div class="p-5 border-b border-gray-200">
        <p class="font-bold text-sm text-gray-900">Asistencia General</p>
        <p class="text-gray-500 text-xs mt-0.5 capitalize truncate">{{ fechaHoy }}</p>
      </div>

      <div class="flex-1 p-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-4">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Usuario autorizado</p>
          <p class="mt-3 text-sm font-semibold text-gray-900">{{ auth?.user?.name }}</p>
          <p class="text-xs text-gray-500">{{ auth?.user?.email ?? 'Sin correo registrado' }}</p>
          <p class="mt-2 inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 border border-green-200">
            Permiso activo
          </p>
        </div>

        <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-4">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 mb-3">Resumen actual</p>
          <div class="grid grid-cols-2 gap-2">
            <div class="rounded-xl border border-gray-200 p-3 text-center">
              <p class="text-xl font-bold text-gray-900">{{ historial.filter(r => r.status === 'on_time').length }}</p>
              <p class="text-xs text-gray-500 mt-1">A tiempo</p>
            </div>
            <div class="rounded-xl border border-gray-200 p-3 text-center">
              <p class="text-xl font-bold text-gray-900">{{ historial.filter(r => r.status === 'late').length }}</p>
              <p class="text-xs text-gray-500 mt-1">Tardanza</p>
            </div>
          </div>
          <div class="mt-2 rounded-xl border border-gray-200 p-3 text-center">
            <p class="text-xl font-bold text-gray-900">{{ historial.length }}</p>
            <p class="text-xs text-gray-500 mt-1">Total registrados</p>
          </div>
        </div>
      </div>

      <div class="p-4 border-t border-gray-200">
        <button @click="logout" class="w-full rounded-xl border border-red-200 px-4 py-3 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
          Cerrar sesion
        </button>
      </div>
    </aside>

    <div class="lg:pl-64 flex-1 flex flex-col min-h-screen">
      <header class="sticky top-0 z-20 bg-white border-b border-gray-200 px-4 lg:px-8 py-4 flex items-center justify-between">
        <div>
          <h2 class="text-base font-semibold text-gray-900">Registro de entrada protegido</h2>
          <p class="text-gray-400 text-xs capitalize hidden sm:block">{{ fechaHoy }}</p>
        </div>
        <div class="flex items-center gap-3 text-xs text-gray-500 lg:hidden">
          <span>{{ auth?.user?.name }}</span>
          <button @click="logout" class="text-red-600">Salir</button>
        </div>
      </header>

      <main class="flex-1 p-4 lg:p-8 flex flex-col lg:flex-row gap-6 items-start">
        <div class="w-full lg:w-96 flex-shrink-0">
          <div v-if="!scannerActivo" class="bg-gray-50 border border-gray-200 rounded-xl p-8 text-center">
            <div class="w-16 h-16 bg-white border border-gray-200 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
              <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
            </div>
            <h3 class="text-gray-900 font-semibold mb-2">Escanear codigo QR</h3>
            <p class="text-gray-500 text-sm mb-6">
              Solo usuarios autorizados pueden abrir este scanner y registrar el ingreso del estudiante.
            </p>

            <p v-if="errorCamera" class="text-red-600 text-xs mb-4 bg-red-50 border border-red-200 px-3 py-2 rounded-lg">
              {{ errorCamera }}
            </p>

            <button @click="iniciarScanner" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-medium py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              Abrir scanner
            </button>
          </div>

          <div v-else class="bg-gray-50 border border-gray-200 rounded-xl overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 bg-white border-b border-gray-200">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"/>
                <p class="text-sm font-medium text-gray-900">Camara activa</p>
              </div>
              <button @click="detenerScanner" class="text-gray-500 hover:text-gray-900 text-xs px-3 py-1.5 rounded-lg border border-gray-300 hover:border-gray-500 transition-colors">
                Detener
              </button>
            </div>

            <div class="p-3">
              <div id="qr-video" class="w-full rounded-lg overflow-hidden bg-black"/>
            </div>

            <div v-if="resultado" class="mx-3 mb-3 rounded-lg p-4 border transition-all"
              :class="{
                'bg-green-50 border-green-300': resultado.success && resultado.status === 'on_time',
                'bg-yellow-50 border-yellow-300': resultado.success && resultado.status === 'late',
                'bg-red-50 border-red-300': !resultado.success && resultado.status !== 'loading',
                'bg-white border-gray-200 animate-pulse': resultado.status === 'loading',
              }">
              <p class="text-sm font-semibold"
                :class="{
                  'text-green-800': resultado.success && resultado.status === 'on_time',
                  'text-yellow-800': resultado.success && resultado.status === 'late',
                  'text-red-700': !resultado.success && resultado.status !== 'loading',
                  'text-gray-400': resultado.status === 'loading',
                }">
                {{ resultado.message }}
              </p>
              <p v-if="resultado.time" class="text-xs mt-1 font-mono"
                :class="resultado.status === 'on_time' ? 'text-green-600' : 'text-yellow-600'">
                Hora: {{ resultado.time }}
              </p>
            </div>
          </div>
        </div>

        <div class="flex-1 w-full">
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
              <h3 class="text-gray-900 font-semibold text-sm">Registros recientes</h3>
              <span class="text-gray-400 text-xs">{{ historial.length }} registros</span>
            </div>

            <div v-if="!historial.length" class="px-5 py-12 text-center text-gray-400 text-sm">
              Aun no hay registros. Escanea el primer QR para comenzar.
            </div>

            <div v-else class="divide-y divide-gray-100 max-h-[60vh] overflow-y-auto">
              <div v-for="(r, i) in historial" :key="i" class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                    :class="{
                      'bg-green-100': r.status === 'on_time',
                      'bg-yellow-100': r.status === 'late',
                      'bg-red-100': !r.success,
                    }">
                    <svg v-if="r.status === 'on_time'" class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg v-else-if="r.status === 'late'" class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg v-else class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-gray-900 text-sm font-medium">{{ r.student ?? 'Desconocido' }}</p>
                    <p class="text-gray-400 text-xs">
                      {{ r.status === 'on_time' ? 'A tiempo' : r.status === 'late' ? 'Con tardanza' : 'Error' }}
                    </p>
                  </div>
                </div>
                <span class="text-gray-400 text-xs font-mono">{{ r.hora }}</span>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
