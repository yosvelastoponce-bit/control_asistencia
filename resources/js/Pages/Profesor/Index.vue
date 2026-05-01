<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import QrScanner from '@/Components/QrScanner.vue'

const props = defineProps({
  auth:      Object,
  profesores: { type: Array, default: () => [] },
  cursos:     { type: Array, default: () => [] },
  horarios:   { type: Array, default: () => [] },
  grados:     { type: Array, default: () => [] },
  school:     Object,
})

const sidebarOpen   = ref(false)
const activeSection = ref('horarios')

const navItems = [
  { id: 'horarios',   label: 'Mis Horarios' },
  { id: 'asistencia', label: 'Asistencia' },
  { id: 'reportes',   label: 'Reportes' },
  { id: 'configuracion', label: 'Configuracion' },
]

const currentSectionLabel = computed(
  () => navItems.find(i => i.id === activeSection.value)?.label ?? ''
)

const logout = () => router.post(route('profesor.logout'))

// ── HORARIOS ──────────────────────────────────────────────────────────────────
const listaHorarios      = ref([...props.horarios])
const mostrarFormHorario = ref(false)
const loadingHorario     = ref(false)
const erroresHorario     = ref({})
const successHorario     = ref('')
const mostrarDias        = ref(false)

const diasSemana = [
  { id: 1, label: 'Lunes' },
  { id: 2, label: 'Martes' },
  { id: 3, label: 'Miércoles' },
  { id: 4, label: 'Jueves' },
  { id: 5, label: 'Viernes' },
  { id: 6, label: 'Sábado' },
  { id: 7, label: 'Domingo' },
]

const formHorario = ref({
  subject_id:   null,
  grade_name:   '',
  section_name: '',
  day:          null,
  start_time:   '',
  end_time:     '',
})

const diaSeleccionadoLabel = computed(() =>
  diasSemana.find(d => d.id === formHorario.value.day)?.label ?? ''
)

const resetFormHorario = () => {
  formHorario.value = {
    subject_id: null, grade_name: '', section_name: '',
    day: null, start_time: '', end_time: '',
  }
  erroresHorario.value = {}
  mostrarDias.value    = false
}

const seleccionarDia = (dia) => {
  formHorario.value.day = dia.id
  mostrarDias.value     = false
}

const guardarHorario = async () => {
  erroresHorario.value = {}
  successHorario.value = ''

  if (!formHorario.value.subject_id)   erroresHorario.value.subject_id   = ['Selecciona un curso.']
  if (!formHorario.value.grade_name)   erroresHorario.value.grade_name   = ['El grado es obligatorio.']
  if (!formHorario.value.section_name) erroresHorario.value.section_name = ['La sección es obligatoria.']
  if (!formHorario.value.day)          erroresHorario.value.day          = ['Selecciona un día.']
  if (!formHorario.value.start_time)   erroresHorario.value.start_time   = ['La hora de inicio es obligatoria.']
  if (!formHorario.value.end_time)     erroresHorario.value.end_time     = ['La hora de fin es obligatoria.']

  if (Object.keys(erroresHorario.value).length) return

  loadingHorario.value = true
  try {
    const { data } = await axios.post(route('director.horarios.store'), formHorario.value)
    listaHorarios.value.push(data)
    successHorario.value = 'Horario registrado correctamente.'
    resetFormHorario()
    mostrarFormHorario.value = false
  } catch (err) {
    erroresHorario.value = err.response?.data?.errors ?? {}
    if (!Object.keys(erroresHorario.value).length)
      erroresHorario.value._general = err.response?.data?.message ?? 'Error al guardar.'
  } finally {
    loadingHorario.value = false
  }
}

const eliminarHorario = async (id) => {
  if (!confirm('¿Eliminar este horario?')) return
  try {
    await axios.delete(route('director.horarios.destroy', id))
    listaHorarios.value = listaHorarios.value.filter(h => h.id !== id)
  } catch (err) {
    alert(err.response?.data?.message ?? 'No se pudo eliminar.')
  }
}

// ── ASISTENCIA / SCANNER ──────────────────────────────────────────────────────
const diaFiltrado         = ref('')
const horarioSeleccionado = ref(null)
const mostrarScanner      = ref(false)

const diasDisponibles = computed(() => {
  const dias = [...new Set(listaHorarios.value.map(h => h.dia))]
  return dias
})

const horariosFiltrados = computed(() => {
  if (!diaFiltrado.value) return listaHorarios.value
  return listaHorarios.value.filter(h => h.dia === diaFiltrado.value)
})

const seleccionarHorario = (horario) => {
  horarioSeleccionado.value = horario
  mostrarScanner.value      = false
}

const gradoReporteFiltro = ref(null)
const seccionReporteFiltro = ref(null)
const modoFechaReporteGeneral = ref('all')
const fechaReporteGeneral = ref('')
const cursoReporteFiltro = ref(null)
const gradoCursoFiltro = ref(null)
const seccionCursoFiltro = ref(null)
const modoFechaReporteCurso = ref('all')
const fechaReporteCurso = ref('')
const descargandoReporteGeneral = ref(false)
const descargandoReporteCurso = ref(false)

const seccionesReporteFiltro = computed(() => {
  if (!gradoReporteFiltro.value) return []
  const grado = props.grados.find((item) => item.id === gradoReporteFiltro.value)
  return grado?.sections ?? []
})

const seccionesCursoFiltro = computed(() => {
  if (!gradoCursoFiltro.value) return []
  const grado = props.grados.find((item) => item.id === gradoCursoFiltro.value)
  return grado?.sections ?? []
})

const descargarReporteProfesor = () => {
  descargandoReporteGeneral.value = true

  const params = {}
  if (gradoReporteFiltro.value) params.grade_id = gradoReporteFiltro.value
  if (seccionReporteFiltro.value) params.section_id = seccionReporteFiltro.value
  params.date_filter_mode = modoFechaReporteGeneral.value
  if (modoFechaReporteGeneral.value === 'date' && fechaReporteGeneral.value) params.date = fechaReporteGeneral.value

  window.open(route('profesor.reports.attendance.export', params), '_blank')

  window.setTimeout(() => {
    descargandoReporteGeneral.value = false
  }, 1200)
}

const descargarReporteCurso = () => {
  descargandoReporteCurso.value = true

  const params = {}
  if (cursoReporteFiltro.value) params.subject_id = cursoReporteFiltro.value
  if (gradoCursoFiltro.value) params.grade_id = gradoCursoFiltro.value
  if (seccionCursoFiltro.value) params.section_id = seccionCursoFiltro.value
  params.date_filter_mode = modoFechaReporteCurso.value
  if (modoFechaReporteCurso.value === 'date' && fechaReporteCurso.value) params.date = fechaReporteCurso.value

  window.open(route('profesor.reports.course-attendance.export', params), '_blank')

  window.setTimeout(() => {
    descargandoReporteCurso.value = false
  }, 1200)
}

const perfilForm = ref({
  name: props.auth?.user?.name ?? '',
  email: props.auth?.user?.email ?? '',
})
const schoolForm = ref({
  name: props.school?.name ?? '',
  code: props.school?.code ?? '',
  address: props.school?.address ?? '',
})
const loadingPerfil = ref(false)
const loadingSchool = ref(false)
const successPerfil = ref('')
const successSchool = ref('')
const errorPerfil = ref('')
const errorSchool = ref('')

const guardarPerfil = async () => {
  loadingPerfil.value = true
  successPerfil.value = ''
  errorPerfil.value = ''

  try {
    const { data } = await axios.patch(route('app-user.settings.profile'), perfilForm.value)
    perfilForm.value.name = data.user.name
    perfilForm.value.email = data.user.email
    if (props.auth?.user) {
      props.auth.user.name = data.user.name
      props.auth.user.email = data.user.email
    }
    successPerfil.value = data.message
  } catch (err) {
    errorPerfil.value = err.response?.data?.message
      ?? Object.values(err.response?.data?.errors ?? {})?.[0]?.[0]
      ?? 'No se pudo actualizar tu perfil.'
  } finally {
    loadingPerfil.value = false
  }
}

const guardarColegio = async () => {
  loadingSchool.value = true
  successSchool.value = ''
  errorSchool.value = ''

  try {
    const { data } = await axios.patch(route('app-user.settings.school'), schoolForm.value)
    schoolForm.value.name = data.school.name
    schoolForm.value.code = data.school.code
    schoolForm.value.address = data.school.address
    if (props.school) {
      props.school.name = data.school.name
      props.school.code = data.school.code
      props.school.address = data.school.address
    }
    successSchool.value = data.message
  } catch (err) {
    errorSchool.value = err.response?.data?.message
      ?? Object.values(err.response?.data?.errors ?? {})?.[0]?.[0]
      ?? 'No se pudo actualizar el colegio.'
  } finally {
    loadingSchool.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-white text-gray-900">

    <!-- Scanner modal -->
    <QrScanner
      v-if="mostrarScanner && horarioSeleccionado"
      :schedule-id="horarioSeleccionado.id"
      @close="mostrarScanner = false"
    />

    <!-- ── Sidebar ────────────────────────────────────────────────────────── -->
    <aside
      class="fixed left-0 top-0 h-full w-60 bg-gray-100 border-r border-gray-200 flex flex-col z-40 transition-transform duration-300"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <div class="p-5 border-b border-gray-200">
        <p class="font-bold text-sm text-gray-900">Portal Profesor</p>
        <p class="text-gray-500 text-xs mt-0.5 truncate">{{ auth?.user?.name }}</p>
      </div>

      <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
        <p class="text-gray-400 text-xs font-semibold uppercase tracking-wider px-3 pt-3 pb-2">Menú</p>
        <button type="button"
          v-for="item in navItems" :key="item.id"
          @click="activeSection = item.id; sidebarOpen = false"
          class="w-full text-left px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
          :class="activeSection === item.id
            ? 'bg-gray-800 text-white'
            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200'"
        >
          {{ item.label }}
        </button>
      </nav>

      <div class="p-4 border-t border-gray-200">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
            {{ auth?.user?.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="min-w-0">
            <p class="text-gray-900 text-sm font-medium truncate">{{ auth?.user?.name }}</p>
            <p class="text-gray-500 text-xs truncate">{{ auth?.user?.email }}</p>
          </div>
        </div>
        <button type="button" @click="logout"
          class="w-full text-left px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors">
          Cerrar sesión
        </button>
      </div>
    </aside>

    <div v-if="sidebarOpen" @click="sidebarOpen = false"
      class="fixed inset-0 bg-black bg-opacity-20 z-30 lg:hidden"/>

    <!-- ── Main ──────────────────────────────────────────────────────────── -->
    <div class="lg:pl-60 min-h-screen">

      <header class="sticky top-0 z-20 bg-white border-b border-gray-200 px-4 lg:px-8 py-4 flex items-center gap-4">
        <button type="button" @click="sidebarOpen = true" class="lg:hidden text-gray-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
        <h2 class="text-base font-semibold text-gray-900">{{ currentSectionLabel }}</h2>
      </header>

      <main class="p-4 lg:p-8">

        <!-- ── MIS HORARIOS ── -->
        <section v-if="activeSection === 'horarios'">

          <!-- Éxito -->
          <div v-if="successHorario"
            class="mb-5 bg-green-50 border border-green-300 text-green-700 text-sm px-4 py-3 rounded-lg flex items-center justify-between">
            <span>{{ successHorario }}</span>
            <button type="button" @click="successHorario = ''" class="ml-4 text-green-700 hover:text-gray-900">✕</button>
          </div>

          <!-- Header -->
          <div class="flex items-center justify-between mb-5">
            <p class="text-gray-500 text-sm">Gestiona tus horarios de clase.</p>
            <button type="button"
              @click="mostrarFormHorario = !mostrarFormHorario; if(!mostrarFormHorario) resetFormHorario()"
              class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
              {{ mostrarFormHorario ? 'Cancelar' : '+ Agregar horario' }}
            </button>
          </div>

          <!-- Formulario -->
          <div v-if="mostrarFormHorario" class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-5">
            <h3 class="text-gray-900 font-semibold mb-4">Nuevo horario</h3>

            <p v-if="erroresHorario._general"
              class="text-red-600 text-sm mb-4 bg-red-50 px-3 py-2 rounded-lg border border-red-300">
              {{ erroresHorario._general }}
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

              <!-- Curso -->
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Curso *</label>
                <select v-model="formHorario.subject_id"
                  class="w-full bg-white border rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-colors"
                  :class="erroresHorario.subject_id ? 'border-red-300' : 'border-gray-300'">
                  <option :value="null" disabled>Selecciona un curso</option>
                  <option v-for="curso in cursos" :key="curso.id" :value="curso.id">{{ curso.name }}</option>
                </select>
                <p v-if="erroresHorario.subject_id" class="text-red-600 text-xs mt-1">{{ erroresHorario.subject_id[0] }}</p>
              </div>

              <!-- Día — dropdown solo al hacer click -->
              <div class="relative">
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Día *</label>
                <button type="button"
                  @click="mostrarDias = !mostrarDias"
                  class="w-full bg-white border rounded-lg px-3 py-2.5 text-sm text-left flex items-center justify-between focus:outline-none focus:ring-1 focus:ring-gray-800 transition-colors"
                  :class="erroresHorario.day ? 'border-red-300' : 'border-gray-300'">
                  <span :class="formHorario.day ? 'text-gray-900' : 'text-gray-400'">
                    {{ diaSeleccionadoLabel || 'Selecciona un día' }}
                  </span>
                  <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                    :class="mostrarDias ? 'rotate-180' : ''"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                  </svg>
                </button>

                <!-- Lista de días — visible SOLO cuando mostrarDias === true -->
                <div v-if="mostrarDias"
                  class="absolute z-20 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                  <button
                    v-for="dia in diasSemana" :key="dia.id"
                    type="button"
                    @click="seleccionarDia(dia)"
                    class="w-full text-left px-4 py-2.5 text-sm transition-colors flex items-center justify-between"
                    :class="formHorario.day === dia.id
                      ? 'bg-gray-800 text-white'
                      : 'text-gray-700 hover:bg-gray-50'">
                    {{ dia.label }}
                    <svg v-if="formHorario.day === dia.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                  </button>
                </div>
                <p v-if="erroresHorario.day" class="text-red-600 text-xs mt-1">{{ erroresHorario.day[0] }}</p>
              </div>

              <!-- Grado -->
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Grado *</label>
                <select v-model="formHorario.grade_name"
                  class="w-full bg-white border rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-colors"
                  :class="erroresHorario.grade_name ? 'border-red-300' : 'border-gray-300'">
                  <option value="" disabled>Selecciona un grado</option>
                  <option value="1er Grado">1er Grado</option>
                  <option value="2do Grado">2do Grado</option>
                  <option value="3er Grado">3er Grado</option>
                  <option value="4to Grado">4to Grado</option>
                  <option value="5to Grado">5to Grado</option>
                </select>
                <p v-if="erroresHorario.grade_name" class="text-red-600 text-xs mt-1">{{ erroresHorario.grade_name[0] }}</p>
              </div>

              <!-- Sección -->
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Sección *</label>
                <select v-model="formHorario.section_name"
                  class="w-full bg-white border rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-colors"
                  :class="erroresHorario.section_name ? 'border-red-300' : 'border-gray-300'">
                  <option value="" disabled>Selecciona una sección</option>
                  <option value="Única">Única</option>
                  <option value="A">A</option>
                  <option value="B">B</option>
                  <option value="C">C</option>
                </select>
                <p v-if="erroresHorario.section_name" class="text-red-600 text-xs mt-1">{{ erroresHorario.section_name[0] }}</p>
              </div>

              <!-- Hora inicio -->
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Hora de inicio *</label>
                <input v-model="formHorario.start_time" type="time"
                  class="w-full bg-white border rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-colors"
                  :class="erroresHorario.start_time ? 'border-red-300' : 'border-gray-300'"/>
                <p v-if="erroresHorario.start_time" class="text-red-600 text-xs mt-1">{{ erroresHorario.start_time[0] }}</p>
              </div>

              <!-- Hora fin -->
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Hora de fin *</label>
                <input v-model="formHorario.end_time" type="time"
                  class="w-full bg-white border rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-colors"
                  :class="erroresHorario.end_time ? 'border-red-300' : 'border-gray-300'"/>
                <p v-if="erroresHorario.end_time" class="text-red-600 text-xs mt-1">{{ erroresHorario.end_time[0] }}</p>
              </div>

            </div>

            <div class="mt-5 flex justify-end">
              <button type="button" @click="guardarHorario" :disabled="loadingHorario"
                class="bg-gray-800 hover:bg-gray-900 disabled:opacity-50 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors flex items-center gap-2">
                <svg v-if="loadingHorario" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ loadingHorario ? 'Guardando...' : 'Guardar horario' }}
              </button>
            </div>
          </div>

          <!-- Tabla horarios -->
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
              <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Día</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Curso</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Grado</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Sección</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Inicio</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Fin</th>
                  <th class="px-5 py-3"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!listaHorarios.length">
                  <td colspan="7" class="px-5 py-8 text-center text-gray-400">Sin horarios registrados.</td>
                </tr>
                <tr v-for="h in listaHorarios" :key="h.id"
                  class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                  <td class="px-5 py-3">
                    <span class="inline-flex items-center bg-gray-100 text-gray-700 text-xs font-medium px-2.5 py-1 rounded-full">
                      {{ h.dia }}
                    </span>
                  </td>
                  <td class="px-5 py-3 text-gray-900 font-medium">{{ h.subject }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ h.grade }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ h.section }}</td>
                  <td class="px-5 py-3 text-gray-500 font-mono text-xs">{{ h.start_time }}</td>
                  <td class="px-5 py-3 text-gray-500 font-mono text-xs">{{ h.end_time }}</td>
                  <td class="px-5 py-3 text-right">
                    <button type="button" @click="eliminarHorario(h.id)"
                      class="text-red-600 hover:text-red-700 text-xs px-2 py-1 rounded hover:bg-red-50 transition-colors">
                      Eliminar
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- ── ASISTENCIA ── -->
        <section v-else-if="activeSection === 'asistencia'">

          <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <p class="text-sm font-semibold text-blue-900">Asistencia general de ingreso</p>
              <p class="text-xs text-blue-700 mt-1">
                Si tienes permiso, puedes abrir el escaner general protegido desde aqui.
              </p>
            </div>
            <a :href="route('general-attendance.index')"
              class="inline-flex items-center justify-center rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white hover:bg-blue-950 transition-colors">
              Abrir escaner general
            </a>
          </div>

          <!-- Filtro por día -->
          <div class="mb-5">
            <p class="text-gray-500 text-sm mb-3">Selecciona un horario para registrar asistencia.</p>
            <div class="flex gap-2 flex-wrap">
              <button type="button"
                @click="diaFiltrado = ''"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors border"
                :class="diaFiltrado === ''
                  ? 'bg-gray-800 text-white border-gray-800'
                  : 'bg-white text-gray-600 border-gray-300 hover:border-gray-500'">
                Todos
              </button>
              <button type="button"
                v-for="dia in diasDisponibles" :key="dia"
                @click="diaFiltrado = dia"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors border"
                :class="diaFiltrado === dia
                  ? 'bg-gray-800 text-white border-gray-800'
                  : 'bg-white text-gray-600 border-gray-300 hover:border-gray-500'">
                {{ dia }}
              </button>
            </div>
          </div>

          <!-- Tabla horarios para seleccionar -->
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden mb-5">
            <table class="w-full text-sm">
              <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Día</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Curso</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Grado</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Sección</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Inicio</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Fin</th>
                  <th class="px-5 py-3"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!horariosFiltrados.length">
                  <td colspan="7" class="px-5 py-8 text-center text-gray-400">No hay horarios disponibles.</td>
                </tr>
                <tr v-for="h in horariosFiltrados" :key="h.id"
                  class="border-b border-gray-200 transition-colors cursor-pointer"
                  :class="horarioSeleccionado?.id === h.id ? 'bg-gray-50' : 'hover:bg-gray-50'"
                  @click="seleccionarHorario(h)">
                  <td class="px-5 py-3">
                    <span class="inline-flex items-center bg-gray-100 text-gray-700 text-xs font-medium px-2.5 py-1 rounded-full">
                      {{ h.dia }}
                    </span>
                  </td>
                  <td class="px-5 py-3 text-gray-900 font-medium">{{ h.subject }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ h.grade }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ h.section }}</td>
                  <td class="px-5 py-3 text-gray-500 font-mono text-xs">{{ h.start_time }}</td>
                  <td class="px-5 py-3 text-gray-500 font-mono text-xs">{{ h.end_time }}</td>
                  <td class="px-5 py-3 text-right">
                    <span v-if="horarioSeleccionado?.id === h.id"
                      class="inline-flex items-center gap-1 text-gray-800 text-xs font-medium">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                      </svg>
                      Seleccionado
                    </span>
                    <span v-else class="text-gray-400 text-xs">Seleccionar</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Panel horario seleccionado + botón scanner -->
          <div v-if="horarioSeleccionado"
            class="bg-gray-50 border border-gray-200 rounded-xl p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <p class="text-gray-500 text-xs mb-1 font-medium uppercase tracking-wide">Horario seleccionado</p>
              <p class="text-gray-900 font-semibold">
                {{ horarioSeleccionado.subject }}
                <span class="text-gray-400 font-normal mx-1">·</span>
                {{ horarioSeleccionado.grade }} — Sección {{ horarioSeleccionado.section }}
              </p>
              <p class="text-gray-500 text-sm mt-0.5">
                {{ horarioSeleccionado.dia }}
                <span class="mx-1">·</span>
                {{ horarioSeleccionado.start_time }} – {{ horarioSeleccionado.end_time }}
              </p>
            </div>
            <button type="button"
              @click="mostrarScanner = true"
              class="flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white font-medium text-sm px-5 py-2.5 rounded-lg transition-colors whitespace-nowrap">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              Escanear QR
            </button>
          </div>

        </section>

        <section v-else-if="activeSection === 'reportes'">
          <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-5">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
              <div>
                <h3 class="text-gray-900 font-semibold text-sm">Reporte general de ingreso</h3>
                <p class="text-gray-500 text-xs mt-1">
                  Exporta la asistencia general del colegio desde general attendance.
                </p>
                <p class="text-gray-400 text-xs mt-2">
                  Puedes descargarlo general, por grado o por seccion.
                </p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Grado</label>
                <select
                  v-model="gradoReporteFiltro"
                  @change="seccionReporteFiltro = null"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800">
                  <option :value="null">General</option>
                  <option v-for="grado in grados" :key="grado.id" :value="grado.id">{{ grado.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Seccion</label>
                <select
                  v-model="seccionReporteFiltro"
                  :disabled="!gradoReporteFiltro"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                  <option :value="null">Todas las secciones</option>
                  <option v-for="seccion in seccionesReporteFiltro" :key="seccion.id" :value="seccion.id">{{ seccion.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Fechas</label>
                <select
                  v-model="modoFechaReporteGeneral"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800">
                  <option value="all">Todas las fechas</option>
                  <option value="date">Por fecha</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Fecha</label>
                <input
                  v-model="fechaReporteGeneral"
                  type="date"
                  :disabled="modoFechaReporteGeneral !== 'date'"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"/>
              </div>
            </div>

            <div class="mt-5 flex justify-end">
              <button type="button"
                @click="descargarReporteProfesor"
                :disabled="descargandoReporteGeneral"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700 disabled:opacity-50">
                <svg v-if="descargandoReporteGeneral" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ descargandoReporteGeneral ? 'Preparando...' : 'Descargar Excel general' }}
              </button>
            </div>
          </div>

          <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
              <div>
                <h3 class="text-gray-900 font-semibold text-sm">Reporte por curso</h3>
                <p class="text-gray-500 text-xs mt-1">
                  Exporta la asistencia tomada en clase desde attendance.
                </p>
                <p class="text-gray-400 text-xs mt-2">
                  Puedes filtrar por curso y tambien por grado o seccion.
                </p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Curso</label>
                <select
                  v-model="cursoReporteFiltro"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800">
                  <option :value="null">Todos los cursos</option>
                  <option v-for="curso in cursos" :key="curso.id" :value="curso.id">{{ curso.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Grado</label>
                <select
                  v-model="gradoCursoFiltro"
                  @change="seccionCursoFiltro = null"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800">
                  <option :value="null">Todos los grados</option>
                  <option v-for="grado in grados" :key="grado.id" :value="grado.id">{{ grado.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Seccion</label>
                <select
                  v-model="seccionCursoFiltro"
                  :disabled="!gradoCursoFiltro"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                  <option :value="null">Todas las secciones</option>
                  <option v-for="seccion in seccionesCursoFiltro" :key="seccion.id" :value="seccion.id">{{ seccion.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Fechas</label>
                <select
                  v-model="modoFechaReporteCurso"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800">
                  <option value="all">Todas las fechas</option>
                  <option value="date">Por fecha</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Fecha</label>
                <input
                  v-model="fechaReporteCurso"
                  type="date"
                  :disabled="modoFechaReporteCurso !== 'date'"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"/>
              </div>
            </div>

            <div class="mt-5 flex justify-end">
              <button type="button"
                @click="descargarReporteCurso"
                :disabled="descargandoReporteCurso"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50">
                <svg v-if="descargandoReporteCurso" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ descargandoReporteCurso ? 'Preparando...' : 'Descargar Excel por curso' }}
              </button>
            </div>
          </div>
        </section>

        <section v-else-if="activeSection === 'configuracion'">
          <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
              <div class="flex items-start gap-3 mb-4">
                <div class="w-8 h-8 bg-gray-800 rounded-lg flex items-center justify-center flex-shrink-0 text-white">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div>
                  <h3 class="text-gray-900 font-semibold text-sm">Mis datos</h3>
                  <p class="text-gray-500 text-xs mt-0.5">
                    Actualiza tu nombre y correo electronico.
                  </p>
                </div>
              </div>

              <div class="space-y-4">
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">Nombre completo *</label>
                  <input
                    v-model="perfilForm.name"
                    type="text"
                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">Correo electronico *</label>
                  <input
                    v-model="perfilForm.email"
                    type="email"
                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                </div>
              </div>

              <p v-if="errorPerfil" class="mt-4 text-red-600 text-xs bg-red-50 border border-red-200 px-3 py-2 rounded-lg">{{ errorPerfil }}</p>
              <div v-if="successPerfil" class="mt-4 text-green-700 text-xs bg-green-50 border border-green-200 px-3 py-2 rounded-lg">✓ {{ successPerfil }}</div>

              <div class="mt-4 flex justify-end">
                <button type="button"
                  @click="guardarPerfil"
                  :disabled="loadingPerfil"
                  class="bg-gray-800 hover:bg-gray-900 disabled:opacity-50 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors flex items-center gap-2">
                  <svg v-if="loadingPerfil" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  {{ loadingPerfil ? 'Guardando...' : 'Guardar perfil' }}
                </button>
              </div>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
              <div class="flex items-start gap-3 mb-4">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4m-9 13V10m-7 10h14a2 2 0 002-2V8a2 2 0 00-1.106-1.789l-7-3.5a2 2 0 00-1.788 0l-7 3.5A2 2 0 003 8v10a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div>
                  <h3 class="text-gray-900 font-semibold text-sm">Datos del colegio</h3>
                  <p class="text-gray-500 text-xs mt-0.5">
                    Edita el nombre, codigo y direccion de la institucion.
                  </p>
                </div>
              </div>

              <div class="space-y-4">
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">Nombre del colegio *</label>
                  <input
                    v-model="schoolForm.name"
                    type="text"
                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">Codigo del colegio *</label>
                  <input
                    v-model="schoolForm.code"
                    type="text"
                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">Direccion *</label>
                  <textarea
                    v-model="schoolForm.address"
                    rows="4"
                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                </div>
              </div>

              <p v-if="errorSchool" class="mt-4 text-red-600 text-xs bg-red-50 border border-red-200 px-3 py-2 rounded-lg">{{ errorSchool }}</p>
              <div v-if="successSchool" class="mt-4 text-green-700 text-xs bg-green-50 border border-green-200 px-3 py-2 rounded-lg">✓ {{ successSchool }}</div>

              <div class="mt-4 flex justify-end">
                <button type="button"
                  @click="guardarColegio"
                  :disabled="loadingSchool"
                  class="bg-gray-800 hover:bg-gray-900 disabled:opacity-50 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors flex items-center gap-2">
                  <svg v-if="loadingSchool" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  {{ loadingSchool ? 'Guardando...' : 'Guardar colegio' }}
                </button>
              </div>
            </div>
          </div>
        </section>

      </main>
    </div>
  </div>
</template>

