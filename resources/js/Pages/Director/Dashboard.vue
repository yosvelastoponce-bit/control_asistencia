<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  director:    Object,
  school:      Object,
  stats:       Object,
  grados:      { type: Array, default: () => [] },
  secciones:   { type: Array, default: () => [] },
  cursos:      { type: Array, default: () => [] },
  profesores:  { type: Array, default: () => [] },
  estudiantes: { type: Array, default: () => [] },
})

const sidebarOpen   = ref(false)
const activeSection = ref('inicio')

const navItems = [
  { id: 'inicio',        label: 'Inicio' },
  { id: 'profesores',    label: 'Profesores' },
  { id: 'estudiantes',   label: 'Estudiantes' },
  { id: 'cursos',        label: 'Cursos' },
  { id: 'configuracion', label: 'Configuración' },
  { id: 'reportes', label: 'Reportes' },
]

const currentSectionLabel = computed(
  () => navItems.find(i => i.id === activeSection.value)?.label ?? ''
)

const statCards = [
  { key: 'profesores',  label: 'Profesores' },
  { key: 'estudiantes', label: 'Estudiantes' },
  { key: 'cursos',      label: 'Cursos' },
  { key: 'grados',      label: 'Grados' },
]

const logout = () => router.post(route('director.logout'))

// ── CRUD Cursos ───────────────────────────────────────────────────────────────
const listaCursos    = ref([...props.cursos])
const nombreCurso    = ref('')
const editandoCurso  = ref(null)
const loadingCurso   = ref(false)
const errorCurso     = ref('')

const guardarCurso = async () => {
  const nombre = nombreCurso.value.trim()
  if (!nombre) { errorCurso.value = 'El nombre no puede estar vacío.'; return }
  loadingCurso.value = true
  errorCurso.value   = ''
  try {
    if (editandoCurso.value) {
      const { data } = await axios.put(route('director.cursos.update', editandoCurso.value), { name: nombre })
      const idx = listaCursos.value.findIndex(i => i.id === editandoCurso.value)
      if (idx !== -1) listaCursos.value[idx] = data
      editandoCurso.value = null
    } else {
      const { data } = await axios.post(route('director.cursos.store'), { name: nombre })
      listaCursos.value.push(data)
    }
    nombreCurso.value = ''
  } catch (err) {
    errorCurso.value = err.response?.data?.message ?? 'Error al guardar.'
  } finally {
    loadingCurso.value = false
  }
}

const eliminarCurso = async (id) => {
  if (!confirm('¿Eliminar este curso?')) return
  try {
    await axios.delete(route('director.cursos.destroy', id))
    listaCursos.value = listaCursos.value.filter(i => i.id !== id)
  } catch (err) {
    alert(err.response?.data?.message ?? 'No se pudo eliminar.')
  }
}

// ── PROFESORES ────────────────────────────────────────────────────────────────
const listaProfesores     = ref([...props.profesores])
const mostrarFormProfesor = ref(false)
const loadingProfesor     = ref(false)
const erroresProfesor     = ref({})
const formProfesor        = ref({ name: '', email: '', password: '', specialty: '' })

const resetFormProfesor = () => {
  formProfesor.value    = { name: '', email: '', password: '', specialty: '' }
  erroresProfesor.value = {}
}

const guardarProfesor = async () => {
  loadingProfesor.value = true
  erroresProfesor.value = {}
  try {
    const { data } = await axios.post(route('director.profesores.store'), formProfesor.value)
    listaProfesores.value.push(data)
    resetFormProfesor()
    mostrarFormProfesor.value = false
  } catch (err) {
    erroresProfesor.value = err.response?.data?.errors ?? {}
    if (!Object.keys(erroresProfesor.value).length)
      erroresProfesor.value._general = err.response?.data?.message ?? 'Error al guardar.'
  } finally {
    loadingProfesor.value = false
  }
}

const eliminarProfesor = async (id) => {
  if (!confirm('¿Eliminar este profesor?')) return
  try {
    await axios.delete(route('director.profesores.destroy', id))
    listaProfesores.value = listaProfesores.value.filter(p => p.id !== id)
  } catch (err) {
    alert(err.response?.data?.message ?? 'No se pudo eliminar.')
  }
}

// ── ESTUDIANTES ───────────────────────────────────────────────────────────────
const listaEstudiantes    = ref([...props.estudiantes])
const loadingEstudiante   = ref(false)
const erroresEstudiante   = ref({})
const successMsg          = ref('')
const gradosDisponibles   = [
  { id: 1, label: '1er Grado' },
  { id: 2, label: '2do Grado' },
  { id: 3, label: '3er Grado' },
  { id: 4, label: '4to Grado' },
  { id: 5, label: '5to Grado' },
]
const seccionesDisponibles = ['Única', 'A', 'B', 'C']
const formEstudiante = ref({ name: '', dni: '', password: '', grade_id: null, section_id: null, grade_name: '', section_name: '' })
const gradoSeleccionado    = ref(null)
const seccionSeleccionada  = ref('')

const seleccionarGrado = (grado) => {
  gradoSeleccionado.value         = grado
  formEstudiante.value.grade_name = grado.label
}
const seleccionarSeccion = (seccion) => {
  seccionSeleccionada.value         = seccion
  formEstudiante.value.section_name = seccion
}
const resetFormEstudiante = () => {
  formEstudiante.value      = { name: '', dni: '', password: '', grade_id: null, section_id: null, grade_name: '', section_name: '' }
  gradoSeleccionado.value   = null
  seccionSeleccionada.value = ''
  erroresEstudiante.value   = {}
}

const guardarEstudiante = async () => {
  erroresEstudiante.value = {}
  successMsg.value        = ''
  if (!formEstudiante.value.name.trim())  erroresEstudiante.value.name     = ['El nombre es obligatorio.']
  if (!formEstudiante.value.dni.trim())   erroresEstudiante.value.dni      = ['El DNI es obligatorio.']
  if (!formEstudiante.value.password)     erroresEstudiante.value.password = ['La contraseña es obligatoria.']
  if (!gradoSeleccionado.value)           erroresEstudiante.value.grade    = ['Selecciona un grado.']
  if (!seccionSeleccionada.value)         erroresEstudiante.value.section  = ['Selecciona una sección.']
  if (Object.keys(erroresEstudiante.value).length) return

  loadingEstudiante.value = true
  try {
    const { data } = await axios.post(route('director.estudiantes.store'), {
      name: formEstudiante.value.name,
      dni: formEstudiante.value.dni,
      password: formEstudiante.value.password,
      grade_name: gradoSeleccionado.value.label,
      section_name: seccionSeleccionada.value,
    })
    listaEstudiantes.value.push(data)
    successMsg.value = `Estudiante "${data.name}" registrado exitosamente.`
    resetFormEstudiante()
  } catch (err) {
    erroresEstudiante.value = err.response?.data?.errors ?? {}
    if (!Object.keys(erroresEstudiante.value).length)
      erroresEstudiante.value._general = err.response?.data?.message ?? 'Error al guardar.'
  } finally {
    loadingEstudiante.value = false
  }
}

const eliminarEstudiante = async (id) => {
  if (!confirm('¿Eliminar este estudiante?')) return
  try {
    await axios.delete(route('director.estudiantes.destroy', id))
    listaEstudiantes.value = listaEstudiantes.value.filter(e => e.id !== id)
  } catch (err) {
    alert(err.response?.data?.message ?? 'No se pudo eliminar.')
  }
}

// ── CARGA MASIVA ──────────────────────────────────────────────────────────────
const mostrarCargaMasiva  = ref(false)
const arrastrandoArchivo  = ref(false)
const archivoSeleccionado = ref(null)
const loadingCarga        = ref(false)
const erroresCarga        = ref([])
const resultadoCarga      = ref('')

const onFileChange = (e) => {
  archivoSeleccionado.value = e.target.files[0] ?? null
  erroresCarga.value        = []
  resultadoCarga.value      = ''
}
const onFileDrop = (e) => {
  arrastrandoArchivo.value = false
  const file = e.dataTransfer.files[0]
  if (!file) return
  const ext = file.name.split('.').pop().toLowerCase()
  if (!['csv'].includes(ext)) { erroresCarga.value = ['Solo se aceptan archivos .csv']; return }
  archivoSeleccionado.value = file
  erroresCarga.value        = []
  resultadoCarga.value      = ''
}
const subirArchivo = async () => {
  if (!archivoSeleccionado.value) return
  loadingCarga.value   = true
  erroresCarga.value   = []
  resultadoCarga.value = ''
  const formData = new FormData()
  formData.append('archivo', archivoSeleccionado.value)
  try {
    const { data } = await axios.post(route('director.estudiantes.import'), formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    resultadoCarga.value = `${data.imported} estudiante(s) importados correctamente.`
    if (data.errors?.length) erroresCarga.value = data.errors
    if (data.students?.length) listaEstudiantes.value.push(...data.students)
    archivoSeleccionado.value = null
  } catch (err) {
    erroresCarga.value = err.response?.data?.errors ?? [err.response?.data?.message ?? 'Error al importar.']
  } finally {
    loadingCarga.value = false
  }
}

// ── CONFIGURACIÓN GOOGLE SHEETS ───────────────────────────────────────────────
const googleSheetId       = ref(props.school?.google_sheet_id ?? '')
const loadingSheet        = ref(false)
const successSheet        = ref('')
const errorSheet          = ref('')

const guardarGoogleSheet = async () => {
  if (!googleSheetId.value.trim()) { errorSheet.value = 'El ID no puede estar vacío.'; return }
  loadingSheet.value = true
  successSheet.value = ''
  errorSheet.value   = ''
  try {
    await axios.post(route('director.google-sheet.update'), {
      google_sheet_id: googleSheetId.value.trim(),
    })
    successSheet.value = 'Google Sheet configurado correctamente.'
  } catch (err) {
    errorSheet.value = err.response?.data?.message ?? 'Error al guardar.'
  } finally {
    loadingSheet.value = false
  }
}


const entryStart        = ref(props.school?.entry_start?.slice(0, 5) ?? '07:00')
const entryLimit        = ref(props.school?.entry_limit?.slice(0, 5) ?? '08:00')
const entryEnd          = ref(props.school?.entry_end?.slice(0, 5)   ?? '09:00')
const loadingSchedule   = ref(false)
const successSchedule   = ref('')
const errorSchedule     = ref('')

const guardarHorarioEntrada = async () => {
  errorSchedule.value   = ''
  successSchedule.value = ''
 
  if (!entryStart.value) { errorSchedule.value = 'La hora de inicio es obligatoria.'; return }
  if (!entryLimit.value) { errorSchedule.value = 'La hora límite es obligatoria.'; return }
  if (!entryEnd.value)   { errorSchedule.value = 'La hora de cierre es obligatoria.'; return }
  if (entryLimit.value <= entryStart.value) {
    errorSchedule.value = 'La hora límite debe ser posterior a la hora de inicio.'
    return
  }
  if (entryEnd.value <= entryLimit.value) {
    errorSchedule.value = 'La hora de cierre debe ser posterior a la hora límite.'
    return
  }
 
  loadingSchedule.value = true
  try {
    await axios.post(route('director.entry-schedule.update'), {
      entry_start: entryStart.value,
      entry_limit: entryLimit.value,
      entry_end:   entryEnd.value,
    })
    successSchedule.value = 'Horario de entrada actualizado correctamente.'
  } catch (err) {
    errorSchedule.value = err.response?.data?.message ?? 'Error al guardar.'
  } finally {
    loadingSchedule.value = false
  }
}


// ── REPORTES ──────────────────────────────────────────────────────────────────
const busquedaEstudiante  = ref('')
const gradoFiltro         = ref(null)   // id del grado seleccionado
const seccionFiltro       = ref(null)   // id de la sección seleccionada
 
// Secciones disponibles según el grado seleccionado
const seccionesFiltro = computed(() => {
  if (!gradoFiltro.value) return []
  const grado = props.grados.find(g => g.id === gradoFiltro.value)
  return grado?.sections ?? []
})
 
// Lista filtrada de estudiantes
const estudiantesFiltrados = computed(() => {
  let lista = [...props.estudiantes]
 
  // Filtro por búsqueda (nombre o DNI)
  if (busquedaEstudiante.value.trim()) {
    const q = busquedaEstudiante.value.toLowerCase()
    lista = lista.filter(e =>
      e.name.toLowerCase().includes(q) ||
      (e.dni ?? '').toLowerCase().includes(q)
    )
  }
 
  // Filtro por grado
  if (gradoFiltro.value) {
    lista = lista.filter(e => e.grade?.id === gradoFiltro.value)
  }
 
  // Filtro por sección
  if (seccionFiltro.value) {
    lista = lista.filter(e => e.section?.id === seccionFiltro.value)
  }
 
  return lista
})
 
const limpiarFiltros = () => {
  busquedaEstudiante.value = ''
  gradoFiltro.value        = null
  seccionFiltro.value      = null
}
 
// Al cambiar grado, resetear sección
watch(gradoFiltro, () => { seccionFiltro.value = null })



// ── LOGO DEL COLEGIO ──────────────────────────────────────────────────────────
const logoUrl         = ref(props.school?.logo_url ?? null)  // URL pública del logo
const loadingLogo     = ref(false)
const successLogo     = ref('')
const errorLogo       = ref('')
const inputLogo       = ref(null)  // ref al input file
 
const onLogoChange = async (e) => {
  const file = e.target.files[0]
  if (!file) return
 
  errorLogo.value   = ''
  successLogo.value = ''
  loadingLogo.value = true
 
  const formData = new FormData()
  formData.append('logo', file)
 
  try {
    const { data } = await axios.post(route('director.logo.upload'), formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    logoUrl.value     = data.logo_url
    successLogo.value = 'Logo actualizado correctamente.'
  } catch (err) {
    errorLogo.value = err.response?.data?.message
                   ?? err.response?.data?.errors?.logo?.[0]
                   ?? 'Error al subir el logo.'
  } finally {
    loadingLogo.value = false
    if (inputLogo.value) inputLogo.value.value = ''
  }
}
 
const eliminarLogo = async () => {
  if (!confirm('¿Eliminar el logo del colegio?')) return
  try {
    await axios.delete(route('director.logo.destroy'))
    logoUrl.value     = null
    successLogo.value = 'Logo eliminado.'
  } catch (err) {
    errorLogo.value = 'No se pudo eliminar el logo.'
  }
}



// ── PROCESAR AUSENCIAS ────────────────────────────────────────────────────────
const loadingAusencias  = ref(false)
const resultAusencias   = ref(null)   // { success, message, absent_count }
const errorAusencias    = ref('')
 
const procesarAusencias = async (force = false) => {
  errorAusencias.value  = ''
  resultAusencias.value = null
  loadingAusencias.value = true
 
  try {
    const { data } = await axios.post(route('director.process.absences'), { force })
    resultAusencias.value = data
  } catch (err) {
    errorAusencias.value = err.response?.data?.message ?? 'Error al procesar ausencias.'
  } finally {
    loadingAusencias.value = false
  }
}


</script>

<template>
  <div class="min-h-screen bg-white text-gray-900">

    <!-- ── Sidebar ── -->
    <aside
      class="fixed left-0 top-0 h-full w-60 bg-gray-100 border-r border-gray-200 flex flex-col z-40 transition-transform duration-300"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <!-- <div class="p-5 border-b border-gray-200">
        <p class="font-bold text-sm truncate">{{ school?.name }}</p>
        <p class="text-gray-500 text-xs mt-0.5">Cód: {{ school?.code }}</p>
      </div> -->
      <div class="p-5 border-b border-gray-200">
        <!-- Logo del colegio (si existe) -->
        <div v-if="logoUrl" class="mb-3 flex justify-center">
          <img :src="logoUrl" alt="Logo" class="w-14 h-14 object-contain rounded-lg border border-gray-200"/>
        </div>
        <p class="font-bold text-sm truncate text-center">{{ school?.name }}</p>
        <p class="text-gray-500 text-xs mt-0.5 text-center">Cód: {{ school?.code }}</p>
      </div>

      <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
        <p class="text-gray-400 text-xs font-semibold uppercase tracking-wider px-3 pt-3 pb-2">Menú</p>
        <button v-for="item in navItems" :key="item.id"
          @click="activeSection = item.id; sidebarOpen = false"
          class="w-full text-left px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
          :class="activeSection === item.id ? 'bg-gray-800 text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200'">
          {{ item.label }}
        </button>
      </nav>
      <div class="p-4 border-t border-gray-200">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
            {{ director?.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="min-w-0">
            <p class="text-gray-900 text-sm font-medium truncate">{{ director?.name }}</p>
            <p class="text-gray-500 text-xs truncate">{{ director?.email }}</p>
          </div>
        </div>
        <button @click="logout" class="w-full text-left px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors">
          Cerrar sesión
        </button>
      </div>
    </aside>

    <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black bg-opacity-20 z-30 lg:hidden"/>

    <!-- ── Main ── -->
    <div class="lg:pl-60 min-h-screen">
      <header class="sticky top-0 z-20 bg-white border-b border-gray-200 px-4 lg:px-8 py-4 flex items-center gap-4">
        <button @click="sidebarOpen = true" class="lg:hidden text-gray-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
        <h2 class="text-base font-semibold text-gray-900">{{ currentSectionLabel }}</h2>
      </header>

      <main class="p-4 lg:p-8">

        <!-- ── INICIO ── -->
        <section v-if="activeSection === 'inicio'">
          <p class="text-gray-500 text-sm mb-6">Bienvenido, <span class="text-gray-900 font-semibold">{{ director?.name }}</span>.</p>
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div v-for="stat in statCards" :key="stat.label" class="bg-gray-50 border border-gray-200 rounded-xl p-5">
              <p class="text-3xl font-bold text-gray-900">{{ stats?.[stat.key] ?? 0 }}</p>
              <p class="text-gray-500 text-sm mt-1">{{ stat.label }}</p>
            </div>
          </div>

          <!-- Panel de ausencias -->
          <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-8">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-gray-900 font-semibold text-sm">Registro de ausencias</h3>
                <p class="text-gray-500 text-xs mt-1">
                  Registra como ausentes a los estudiantes que no marcaron entrada hoy.
                  Solo disponible después del cierre del horario de entrada
                  <span class="font-medium text-gray-700">({{ school?.entry_end?.slice(0,5) ?? '09:00' }})</span>.
                </p>
              </div>
              <div class="flex gap-2 flex-shrink-0">
                <button
                  @click="procesarAusencias(false)"
                  :disabled="loadingAusencias"
                  class="bg-gray-800 hover:bg-gray-900 disabled:opacity-50 text-white text-xs font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5">
                  <svg v-if="loadingAusencias" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  {{ loadingAusencias ? 'Procesando...' : 'Procesar ausencias' }}
                </button>
                <button
                  @click="procesarAusencias(true)"
                  :disabled="loadingAusencias"
                  title="Forzar procesamiento aunque el horario no haya cerrado"
                  class="text-gray-500 hover:text-gray-900 text-xs px-3 py-2 rounded-lg border border-gray-300 hover:border-gray-500 transition-colors">
                  Forzar
                </button>
              </div>
            </div>
 
            <!-- Resultado -->
            <div v-if="resultAusencias" class="mt-4 rounded-lg px-4 py-3 text-sm border"
              :class="resultAusencias.success
                ? 'bg-green-50 border-green-200 text-green-700'
                : 'bg-yellow-50 border-yellow-200 text-yellow-700'">
              <p class="font-medium">{{ resultAusencias.message }}</p>
              <p v-if="resultAusencias.absent_count !== undefined" class="text-xs mt-1 opacity-75">
                {{ resultAusencias.registered_count }} presentes ·
                {{ resultAusencias.absent_count }} ausentes ·
                {{ resultAusencias.total_students }} total
              </p>
            </div>
            <p v-if="errorAusencias" class="mt-4 text-red-600 text-xs bg-red-50 border border-red-200 px-3 py-2 rounded-lg">
              {{ errorAusencias }}
            </p>
          </div>
          
          <h3 class="text-gray-700 font-semibold mb-4">Accesos rápidos</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <button v-for="item in navItems.filter(i => i.id !== 'inicio')" :key="item.id"
              @click="activeSection = item.id"
              class="bg-gray-50 border border-gray-200 hover:border-gray-400 rounded-xl p-4 text-left transition-all">
              <p class="text-gray-900 text-sm font-medium">{{ item.label }}</p>
              <p class="text-gray-400 text-xs mt-1">→ Gestionar</p>
            </button>
          </div>
        </section>

        <!-- ── PROFESORES ── -->
        <section v-else-if="activeSection === 'profesores'">
          <!-- <pre>{{ listaProfesores[0] }}</pre> -->
          <div class="flex items-center justify-between mb-5">
            <p class="text-gray-500 text-sm">Profesores de tu institución.</p>
            <button @click="mostrarFormProfesor = !mostrarFormProfesor"
              class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
              {{ mostrarFormProfesor ? 'Cancelar' : '+ Agregar profesor' }}
            </button>
          </div>
          <div v-if="mostrarFormProfesor" class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-5">
            <h3 class="text-gray-900 font-semibold mb-4">Nuevo profesor</h3>
            <p v-if="erroresProfesor._general" class="text-red-600 text-sm mb-3 bg-red-50 px-3 py-2 rounded-lg">{{ erroresProfesor._general }}</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Nombre completo *</label>
                <input v-model="formProfesor.name" type="text" placeholder="Juan Pérez"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                <p v-if="erroresProfesor.name" class="text-red-600 text-xs mt-1">{{ erroresProfesor.name[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Correo electrónico *</label>
                <input v-model="formProfesor.email" type="email" placeholder="profesor@colegio.pe"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                <p v-if="erroresProfesor.email" class="text-red-600 text-xs mt-1">{{ erroresProfesor.email[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Contraseña *</label>
                <input v-model="formProfesor.password" type="password" placeholder="Mínimo 8 caracteres"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                <p v-if="erroresProfesor.password" class="text-red-600 text-xs mt-1">{{ erroresProfesor.password[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Especialidad</label>
                <input v-model="formProfesor.specialty" type="text" placeholder="Ej: Matemática"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-800"/>
              </div>
            </div>
            <div class="mt-4 flex justify-end">
              <button @click="guardarProfesor" :disabled="loadingProfesor"
                class="bg-gray-800 hover:bg-gray-900 disabled:opacity-50 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                {{ loadingProfesor ? 'Guardando...' : 'Guardar profesor' }}
              </button>
            </div>
          </div>
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
              <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Nombre</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Email</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Especialidad</th>
                  <th class="px-5 py-3"/>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!listaProfesores.length">
                  <td colspan="4" class="px-5 py-8 text-center text-gray-400">Sin profesores registrados.</td>
                </tr>
                <tr v-for="p in listaProfesores" :key="p.id" class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                  <td class="px-5 py-3 text-gray-900">{{ p.app_user?.name ?? p.name }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ p.app_user?.email ?? p.email ?? '—' }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ p.specialty ?? '—' }}</td>
                  <td class="px-5 py-3 text-right">
                    <button @click="eliminarProfesor(p.id)" class="text-red-600 hover:text-red-700 text-xs px-2 py-1 rounded hover:bg-red-50 transition-colors">Eliminar</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- ── ESTUDIANTES ── -->
        <section v-else-if="activeSection === 'estudiantes'">
          <div v-if="successMsg" class="mb-5 bg-green-50 border border-green-300 text-green-700 text-sm px-4 py-3 rounded-lg flex items-center justify-between">
            <span>{{ successMsg }}</span>
            <button @click="successMsg = ''" class="text-green-700 hover:text-gray-900 ml-4">✕</button>
          </div>
          <p v-if="erroresEstudiante._general" class="mb-5 text-red-600 text-sm bg-red-50 px-4 py-3 rounded-lg border border-red-300">{{ erroresEstudiante._general }}</p>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <!-- Panel 1 -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
              <div class="flex items-center gap-2 mb-4">
                <div class="w-6 h-6 bg-gray-800 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">1</div>
                <h3 class="text-gray-900 font-semibold text-sm">Datos del estudiante</h3>
              </div>
              <div class="space-y-3">
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">Nombre completo *</label>
                  <input v-model="formEstudiante.name" type="text" placeholder="María García López"
                    class="w-full bg-white border rounded-lg px-3 py-2.5 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-800 transition-colors"
                    :class="erroresEstudiante.name ? 'border-red-300' : 'border-gray-300'"/>
                  <p v-if="erroresEstudiante.name" class="text-red-600 text-xs mt-1">{{ erroresEstudiante.name[0] }}</p>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">DNI *</label>
                  <input v-model="formEstudiante.dni" type="text" placeholder="12345678"
                    class="w-full bg-white border rounded-lg px-3 py-2.5 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-800 transition-colors"
                    :class="erroresEstudiante.dni ? 'border-red-300' : 'border-gray-300'"/>
                  <p v-if="erroresEstudiante.dni" class="text-red-600 text-xs mt-1">{{ erroresEstudiante.dni[0] }}</p>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">Contraseña *</label>
                  <input v-model="formEstudiante.password" type="password" placeholder="Mínimo 8 caracteres"
                    class="w-full bg-white border rounded-lg px-3 py-2.5 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-800 transition-colors"
                    :class="erroresEstudiante.password ? 'border-red-300' : 'border-gray-300'"/>
                  <p v-if="erroresEstudiante.password" class="text-red-600 text-xs mt-1">{{ erroresEstudiante.password[0] }}</p>
                </div>
              </div>
            </div>
            <!-- Panel 2 -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
              <div class="flex items-center gap-2 mb-4">
                <div class="w-6 h-6 bg-gray-800 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">2</div>
                <h3 class="text-gray-900 font-semibold text-sm">Grado</h3>
              </div>
              <p v-if="erroresEstudiante.grade" class="text-red-600 text-xs mb-3">{{ erroresEstudiante.grade[0] }}</p>
              <div class="space-y-2">
                <button v-for="grado in gradosDisponibles" :key="grado.id" @click="seleccionarGrado(grado)"
                  class="w-full text-left px-4 py-3 rounded-lg border text-sm font-medium transition-all"
                  :class="gradoSeleccionado?.id === grado.id ? 'bg-gray-800 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-700 hover:border-gray-800'">
                  <div class="flex items-center justify-between">
                    <span>{{ grado.label }}</span>
                    <svg v-if="gradoSeleccionado?.id === grado.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                  </div>
                </button>
              </div>
            </div>
            <!-- Panel 3 -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
              <div class="flex items-center gap-2 mb-4">
                <div class="w-6 h-6 bg-gray-800 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">3</div>
                <h3 class="text-gray-900 font-semibold text-sm">Sección</h3>
              </div>
              <p v-if="erroresEstudiante.section" class="text-red-600 text-xs mb-3">{{ erroresEstudiante.section[0] }}</p>
              <div class="grid grid-cols-2 gap-2">
                <button v-for="seccion in seccionesDisponibles" :key="seccion" @click="seleccionarSeccion(seccion)"
                  class="px-4 py-3 rounded-lg border text-sm font-medium transition-all text-center"
                  :class="seccionSeleccionada === seccion ? 'bg-gray-800 border-gray-800 text-white' : 'bg-white border-gray-300 text-gray-700 hover:border-gray-800'">
                  {{ seccion }}
                </button>
              </div>
              <div v-if="gradoSeleccionado || seccionSeleccionada" class="mt-4 pt-4 border-t border-gray-200">
                <p class="text-gray-400 text-xs mb-1">Selección actual:</p>
                <p class="text-gray-900 text-sm font-medium">{{ gradoSeleccionado?.label ?? '—' }} — Sección {{ seccionSeleccionada || '—' }}</p>
              </div>
            </div>
          </div>

          <div class="flex justify-end mb-8">
            <button @click="guardarEstudiante" :disabled="loadingEstudiante"
              class="bg-gray-800 hover:bg-gray-900 disabled:opacity-50 text-white font-semibold px-8 py-3 rounded-lg transition-colors flex items-center gap-2">
              <svg v-if="loadingEstudiante" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ loadingEstudiante ? 'Guardando...' : 'Registrar estudiante' }}
            </button>
          </div>

          <!-- Carga masiva -->
          <div class="flex items-center gap-3 mb-6">
            <div class="flex-1 h-px bg-gray-200"/>
            <button @click="mostrarCargaMasiva = !mostrarCargaMasiva"
              class="flex items-center gap-2 text-gray-500 hover:text-gray-900 text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-300 hover:border-gray-500 transition-colors">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
              </svg>
              Carga masiva desde CSV
            </button>
            <div class="flex-1 h-px bg-gray-200"/>
          </div>

          <div v-if="mostrarCargaMasiva" class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-6">
            <h3 class="text-gray-900 font-semibold text-sm mb-2">Importar desde CSV</h3>
            <p class="text-gray-500 text-xs mb-4">Columnas requeridas: <span class="font-mono text-gray-700">nombre, dni, contraseña, grado, sección</span></p>
            <div @dragover.prevent="arrastrandoArchivo = true" @dragleave="arrastrandoArchivo = false"
              @drop.prevent="onFileDrop" @click="$refs.inputArchivo.click()"
              class="border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
              :class="arrastrandoArchivo ? 'border-gray-800 bg-gray-100' : 'border-gray-300 hover:border-gray-500'">
              <input ref="inputArchivo" type="file" accept=".csv" class="hidden" @change="onFileChange"/>
              <p v-if="!archivoSeleccionado" class="text-gray-500 text-sm">Arrastra tu CSV aquí o <span class="text-gray-800">haz clic</span></p>
              <p v-else class="text-gray-900 text-sm font-medium">📄 {{ archivoSeleccionado.name }}</p>
            </div>
            <div v-if="erroresCarga.length" class="mt-4 bg-red-50 border border-red-300 rounded-lg p-3">
              <p class="text-red-700 text-xs font-semibold mb-1">Errores:</p>
              <ul class="space-y-0.5">
                <li v-for="(err, i) in erroresCarga" :key="i" class="text-red-600 text-xs">• {{ err }}</li>
              </ul>
            </div>
            <div v-if="resultadoCarga" class="mt-4 bg-green-50 border border-green-300 rounded-lg p-3">
              <p class="text-green-700 text-sm font-semibold">✓ {{ resultadoCarga }}</p>
            </div>
            <div class="mt-4 flex justify-end gap-2">
              <button @click="archivoSeleccionado = null; erroresCarga = []; resultadoCarga = ''"
                class="text-gray-500 hover:text-gray-900 text-sm px-4 py-2 rounded-lg border border-gray-300 transition-colors">Limpiar</button>
              <button @click="subirArchivo" :disabled="!archivoSeleccionado || loadingCarga"
                class="bg-gray-800 hover:bg-gray-900 disabled:opacity-40 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors flex items-center gap-2">
                <svg v-if="loadingCarga" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ loadingCarga ? 'Importando...' : 'Importar' }}
              </button>
            </div>
          </div>

          <!-- Tabla estudiantes -->
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden mb-4">
            <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
              <h3 class="text-gray-900 font-semibold text-sm">Estudiantes registrados</h3>
              <a :href="route('profesor.qr')" target="_blank"
                class="inline-flex items-center gap-1.5 bg-white hover:bg-gray-50 text-gray-700 text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-300 transition-colors">
                Descargar QR
              </a>
            </div>
            <table class="w-full text-sm">
              <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Nombre</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">DNI</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Grado</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Sección</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">QR</th>
                  <th class="px-5 py-3"/>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!listaEstudiantes.length">
                  <td colspan="6" class="px-5 py-8 text-center text-gray-400">Sin estudiantes registrados.</td>
                </tr>
                <tr v-for="e in listaEstudiantes" :key="e.id" class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                  <td class="px-5 py-3 text-gray-900">{{ e.name }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ e.dni }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ e.grade?.name ?? '—' }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ e.section?.name ?? '—' }}</td>
                  <td class="px-5 py-3">
                    <span v-if="e.qr_code?.active" class="inline-flex items-center bg-green-50 text-green-700 text-xs px-2 py-0.5 rounded-full border border-green-300">✓ Activo</span>
                    <span v-else class="text-gray-400 text-xs">Sin QR</span>
                  </td>
                  <td class="px-5 py-3 text-right">
                    <button @click="eliminarEstudiante(e.id)" class="text-red-600 hover:text-red-700 text-xs px-2 py-1 rounded hover:bg-red-50 transition-colors">Eliminar</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- ── REPORTES ── -->
        <section v-else-if="activeSection === 'reportes'">
         
          <!-- Filtros -->
          <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-5">
            <h3 class="text-gray-900 font-semibold text-sm mb-4">Filtros</h3>
         
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
         
              <!-- Búsqueda -->
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Buscar por nombre o DNI</label>
                <div class="relative">
                  <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                  </svg>
                  <input v-model="busquedaEstudiante" type="text" placeholder="Ej: García, 12345678..."
                    class="w-full bg-white border border-gray-300 rounded-lg pl-9 pr-3 py-2.5 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                </div>
              </div>
         
              <!-- Grado -->
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Grado</label>
                <select v-model="gradoFiltro"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800">
                  <option :value="null">Todos los grados</option>
                  <option v-for="g in grados" :key="g.id" :value="g.id">{{ g.name }}</option>
                </select>
              </div>
         
              <!-- Sección -->
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Sección</label>
                <select v-model="seccionFiltro" :disabled="!gradoFiltro"
                  class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                  <option :value="null">Todas las secciones</option>
                  <option v-for="s in seccionesFiltro" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
              </div>
         
            </div>
         
            <!-- Resumen y limpiar -->
            <div class="mt-4 flex items-center justify-between">
              <p class="text-gray-500 text-xs">
                Mostrando <span class="font-semibold text-gray-900">{{ estudiantesFiltrados.length }}</span>
                de <span class="font-semibold text-gray-900">{{ estudiantes.length }}</span> estudiantes
              </p>
              <button v-if="busquedaEstudiante || gradoFiltro || seccionFiltro"
                @click="limpiarFiltros"
                class="text-gray-500 hover:text-gray-900 text-xs px-3 py-1.5 rounded-lg border border-gray-300 hover:border-gray-500 transition-colors">
                Limpiar filtros
              </button>
            </div>
          </div>
         
          <!-- Tabla de resultados -->
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
              <h3 class="text-gray-900 font-semibold text-sm">
                {{ gradoFiltro
                  ? (seccionFiltro
                    ? grados.find(g => g.id === gradoFiltro)?.name + ' — Sección ' + seccionesFiltro.find(s => s.id === seccionFiltro)?.name
                    : grados.find(g => g.id === gradoFiltro)?.name)
                  : 'Todos los estudiantes' }}
              </h3>
              <span class="text-gray-400 text-xs">{{ estudiantesFiltrados.length }} estudiantes</span>
            </div>
         
            <table class="w-full text-sm">
              <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">#</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Nombre</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">DNI</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Grado</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Sección</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">QR</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!estudiantesFiltrados.length">
                  <td colspan="6" class="px-5 py-10 text-center text-gray-400">
                    {{ busquedaEstudiante || gradoFiltro
                      ? 'No se encontraron estudiantes con esos filtros.'
                      : 'Sin estudiantes registrados.' }}
                  </td>
                </tr>
                <tr v-for="(e, idx) in estudiantesFiltrados" :key="e.id"
                  class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                  <td class="px-5 py-3 text-gray-400 text-xs">{{ idx + 1 }}</td>
                  <td class="px-5 py-3 text-gray-900 font-medium">{{ e.name }}</td>
                  <td class="px-5 py-3 text-gray-500 font-mono text-xs">{{ e.dni ?? '—' }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ e.grade?.name ?? '—' }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ e.section?.name ?? '—' }}</td>
                  <td class="px-5 py-3">
                    <span v-if="e.qr_code?.active"
                      class="inline-flex items-center bg-green-50 text-green-700 text-xs px-2 py-0.5 rounded-full border border-green-300">
                      ✓ Activo
                    </span>
                    <span v-else class="text-gray-400 text-xs">Sin QR</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
         
        </section>

        <!-- ── CURSOS ── -->
        <section v-else-if="activeSection === 'cursos'">
          <div class="max-w-lg">
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-5">
              <h3 class="text-gray-900 font-semibold mb-4">{{ editandoCurso ? 'Editar curso' : 'Agregar curso' }}</h3>
              <div class="flex gap-2">
                <input v-model="nombreCurso" type="text" placeholder="Ej: Matemática..." @keyup.enter="guardarCurso"
                  class="flex-1 bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                <button @click="guardarCurso" :disabled="loadingCurso"
                  class="bg-gray-800 hover:bg-gray-900 disabled:opacity-50 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors whitespace-nowrap">
                  {{ editandoCurso ? 'Actualizar' : 'Agregar' }}
                </button>
                <button v-if="editandoCurso" @click="editandoCurso = null; nombreCurso = ''"
                  class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm px-3 py-2.5 rounded-lg transition-colors">✕</button>
              </div>
              <p v-if="errorCurso" class="text-red-600 text-xs mt-2">{{ errorCurso }}</p>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
              <div v-if="!listaCursos.length" class="px-5 py-8 text-center text-gray-400 text-sm">No hay cursos registrados.</div>
              <div v-for="(item, idx) in listaCursos" :key="item.id"
                class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition-colors"
                :class="idx < listaCursos.length - 1 ? 'border-b border-gray-200' : ''">
                <span class="text-gray-900 text-sm">{{ item.name }}</span>
                <div class="flex gap-2">
                  <button @click="editandoCurso = item.id; nombreCurso = item.name"
                    class="text-gray-500 hover:text-gray-900 text-xs px-2 py-1 rounded hover:bg-gray-100 transition-colors">Editar</button>
                  <button @click="eliminarCurso(item.id)"
                    class="text-gray-500 hover:text-red-600 text-xs px-2 py-1 rounded hover:bg-red-50 transition-colors">Eliminar</button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ── CONFIGURACIÓN ── -->
        <section v-else-if="activeSection === 'configuracion'">
          <div class="max-w-lg">

            <!-- Info del colegio -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-5">
              <h3 class="text-gray-900 font-semibold mb-4">Información del colegio</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-500">Nombre</span>
                  <span class="text-gray-900 font-medium">{{ school?.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">Código</span>
                  <span class="text-gray-900 font-mono">{{ school?.code }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-500">Dirección</span>
                  <span class="text-gray-900">{{ school?.address ?? '—' }}</span>
                </div>
              </div>
            </div>

            <!-- Logo del colegio -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-5">
              <div class="flex items-start gap-3 mb-4">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div>
                  <h3 class="text-gray-900 font-semibold text-sm">Logo de la institución</h3>
                  <p class="text-gray-500 text-xs mt-0.5">
                    Aparecerá en el sidebar y en el centro de los códigos QR generados.
                  </p>
                </div>
              </div>
 
              <!-- Preview del logo actual -->
              <div v-if="logoUrl" class="mb-4 flex items-center gap-4">
                <img :src="logoUrl" alt="Logo actual"
                  class="w-16 h-16 object-contain rounded-xl border border-gray-200 bg-white p-1"/>
                <div>
                  <p class="text-gray-700 text-xs font-medium mb-1">Logo actual</p>
                  <button @click="eliminarLogo"
                    class="text-red-600 hover:text-red-700 text-xs px-2 py-1 rounded hover:bg-red-50 transition-colors border border-red-200">
                    Eliminar logo
                  </button>
                </div>
              </div>
 
              <!-- Zona de subida -->
              <div
                @click="inputLogo.click()"
                class="border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-colors hover:border-gray-500"
                :class="logoUrl ? 'border-gray-200' : 'border-gray-300'">
                <input ref="inputLogo" type="file" accept=".png,.jpg,.jpeg" class="hidden" @change="onLogoChange"/>
                <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-500 text-sm">
                  {{ logoUrl ? 'Haz clic para cambiar el logo' : 'Haz clic para subir el logo' }}
                </p>
                <p class="text-gray-400 text-xs mt-1">PNG o JPG · máx. 2MB</p>
              </div>
 
              <div v-if="loadingLogo" class="mt-3 text-gray-500 text-xs text-center animate-pulse">Subiendo logo...</div>
              <p v-if="errorLogo" class="mt-3 text-red-600 text-xs bg-red-50 border border-red-200 px-3 py-2 rounded-lg">{{ errorLogo }}</p>
              <div v-if="successLogo" class="mt-3 text-green-700 text-xs bg-green-50 border border-green-200 px-3 py-2 rounded-lg">✓ {{ successLogo }}</div>
            </div>
            
            <!-- Google Sheets -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
              <div class="flex items-start gap-3 mb-4">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </div>
                <div>
                  <h3 class="text-gray-900 font-semibold text-sm">Google Sheets</h3>
                  <p class="text-gray-500 text-xs mt-0.5">
                    Conecta tu propio Google Sheet para recibir los registros de asistencia en tiempo real.
                  </p>
                </div>
              </div>

              <!-- Estado actual -->
              <div class="mb-4 flex items-center gap-2">
                <span v-if="school?.google_sheet_id"
                  class="inline-flex items-center gap-1.5 text-green-700 bg-green-50 border border-green-200 text-xs px-2.5 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-green-500 rounded-full"/>
                  Conectado
                </span>
                <span v-else
                  class="inline-flex items-center gap-1.5 text-gray-500 bg-gray-100 border border-gray-200 text-xs px-2.5 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"/>
                  Sin configurar
                </span>
              </div>

              <div class="space-y-3">
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">
                    ID de Google Sheets *
                  </label>
                  <input v-model="googleSheetId" type="text"
                    placeholder="Ej: 1xMJVyM1SB0qUNAZ031SvMl0BrOL2kTBdvfU_Dqmm9M"
                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm font-mono placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                  <p class="text-gray-400 text-xs mt-1.5">
                    Encuéntralo en la URL de tu Sheet:
                    <span class="font-mono">docs.google.com/spreadsheets/d/<strong>ID</strong>/edit</span>
                  </p>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700">
                  <p class="font-semibold mb-1">⚠️ Antes de guardar:</p>
                  <p>Comparte tu Google Sheet con este email como <strong>Editor</strong>:</p>
                  <p class="font-mono mt-1 break-all">asistencia-bot@control-asistencia-490604.iam.gserviceaccount.com</p>
                </div>

                <p v-if="errorSheet" class="text-red-600 text-xs bg-red-50 border border-red-200 px-3 py-2 rounded-lg">{{ errorSheet }}</p>
                <div v-if="successSheet" class="text-green-700 text-xs bg-green-50 border border-green-200 px-3 py-2 rounded-lg">✓ {{ successSheet }}</div>

                <div class="flex justify-end">
                  <button @click="guardarGoogleSheet" :disabled="loadingSheet"
                    class="bg-gray-800 hover:bg-gray-900 disabled:opacity-50 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors flex items-center gap-2">
                    <svg v-if="loadingSheet" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ loadingSheet ? 'Guardando...' : 'Guardar configuración' }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Horario de entrada -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mt-5">
              <div class="flex items-start gap-3 mb-4">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div>
                  <h3 class="text-gray-900 font-semibold text-sm">Horario de entrada</h3>
                  <p class="text-gray-500 text-xs mt-0.5">
                    Define el rango horario en que los estudiantes pueden registrar su entrada.
                  </p>
                </div>
              </div>
             
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
             
                <!-- Hora de inicio -->
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">
                    Inicio de registro *
                  </label>
                  <input v-model="entryStart" type="time"
                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                  <p class="text-gray-400 text-xs mt-1">Desde cuándo se acepta el QR</p>
                </div>
             
                <!-- Hora límite (puntualidad) -->
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">
                    Límite de puntualidad *
                  </label>
                  <input v-model="entryLimit" type="time"
                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                  <p class="text-gray-400 text-xs mt-1">Después = tardanza</p>
                </div>
             
                <!-- Hora de cierre -->
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">
                    Cierre de registro *
                  </label>
                  <input v-model="entryEnd" type="time"
                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-gray-900 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"/>
                  <p class="text-gray-400 text-xs mt-1">Después no se registra</p>
                </div>
             
              </div>
             
              <!-- Resumen visual -->
              <div class="bg-white border border-gray-200 rounded-lg p-3 mb-4 flex items-center gap-2 text-xs text-gray-600 flex-wrap">
                <span class="font-medium text-green-700">{{ entryStart }}</span>
                <span class="text-gray-300">→</span>
                <span class="text-gray-500">A tiempo</span>
                <span class="text-gray-300">→</span>
                <span class="font-medium text-yellow-600">{{ entryLimit }}</span>
                <span class="text-gray-300">→</span>
                <span class="text-gray-500">Tardanza</span>
                <span class="text-gray-300">→</span>
                <span class="font-medium text-red-600">{{ entryEnd }}</span>
                <span class="text-gray-300">→</span>
                <span class="text-gray-500">Sin registro</span>
              </div>
             
              <p v-if="errorSchedule" class="text-red-600 text-xs bg-red-50 border border-red-200 px-3 py-2 rounded-lg mb-3">
                {{ errorSchedule }}
              </p>
              <div v-if="successSchedule" class="text-green-700 text-xs bg-green-50 border border-green-200 px-3 py-2 rounded-lg mb-3">
                ✓ {{ successSchedule }}
              </div>
             
              <div class="flex justify-end">
                <button @click="guardarHorarioEntrada" :disabled="loadingSchedule"
                  class="bg-gray-800 hover:bg-gray-900 disabled:opacity-50 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors flex items-center gap-2">
                  <svg v-if="loadingSchedule" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  {{ loadingSchedule ? 'Guardando...' : 'Guardar horario' }}
                </button>
              </div>
            </div>

          </div>
        </section>

      </main>
    </div>
  </div>
</template>