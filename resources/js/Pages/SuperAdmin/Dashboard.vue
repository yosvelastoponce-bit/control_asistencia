<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  superAdmin: Object,
  stats: Object,
  directors: { type: Array, default: () => [] },
  schools: { type: Array, default: () => [] },
})

const sidebarOpen = ref(false)
const activeSection = ref('inicio')
const listaDirectores = ref([...props.directors])
const listaColegios = ref([...props.schools])
const loadingDirector = ref(false)
const togglingSchoolId = ref(null)
const formDirector = ref({
  name: '',
  email: '',
})
const erroresDirector = ref({})

const navItems = [
  { id: 'inicio', label: 'Inicio' },
  { id: 'directores', label: 'Directores' },
  { id: 'colegios', label: 'Colegios' },
]

const currentSectionLabel = computed(
  () => navItems.find((item) => item.id === activeSection.value)?.label ?? ''
)

const logout = () => router.post(route('super-admin.logout'))

const guardarDirector = async () => {
  loadingDirector.value = true
  erroresDirector.value = {}

  try {
    const { data } = await axios.post(route('super-admin.directors.store'), formDirector.value)
    listaDirectores.value.unshift(data)
    formDirector.value = { name: '', email: '' }
  } catch (error) {
    erroresDirector.value = error.response?.data?.errors ?? {}
    if (!Object.keys(erroresDirector.value).length) {
      erroresDirector.value._general = error.response?.data?.message ?? 'No se pudo registrar el director.'
    }
  } finally {
    loadingDirector.value = false
  }
}

const toggleSchoolAccess = async (school) => {
  togglingSchoolId.value = school.id

  try {
    const { data } = await axios.patch(route('super-admin.schools.access', school.id), {
      is_access_enabled: !school.is_access_enabled,
    })

    const index = listaColegios.value.findIndex((item) => item.id === school.id)
    if (index !== -1) {
      listaColegios.value[index].is_access_enabled = data.is_access_enabled
    }
  } catch (error) {
    alert(error.response?.data?.message ?? 'No se pudo actualizar el acceso del colegio.')
  } finally {
    togglingSchoolId.value = null
  }
}
</script>

<template>
  <div class="min-h-screen bg-white text-gray-900">
    <aside
      class="fixed left-0 top-0 h-full w-60 bg-gray-100 border-r border-gray-200 flex flex-col z-40 transition-transform duration-300"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
      <div class="p-5 border-b border-gray-200">
        <p class="font-bold text-sm text-gray-900">Portal Super Admin</p>
        <p class="text-gray-500 text-xs mt-0.5 truncate">{{ superAdmin?.email }}</p>
      </div>

      <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
        <p class="text-gray-400 text-xs font-semibold uppercase tracking-wider px-3 pt-3 pb-2">Menu</p>
        <button
          v-for="item in navItems"
          :key="item.id"
          @click="activeSection = item.id; sidebarOpen = false"
          class="w-full text-left px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
          :class="activeSection === item.id ? 'bg-gray-800 text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200'">
          {{ item.label }}
        </button>
      </nav>

      <div class="p-4 border-t border-gray-200">
        <button
          @click="logout"
          class="w-full text-left px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors">
          Cerrar sesion
        </button>
      </div>
    </aside>

    <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black bg-opacity-20 z-30 lg:hidden"/>

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
        <section v-if="activeSection === 'inicio'">
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
              <p class="text-3xl font-bold text-gray-900">{{ stats?.directors ?? 0 }}</p>
              <p class="text-gray-500 text-sm mt-1">Directores</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
              <p class="text-3xl font-bold text-gray-900">{{ stats?.schools ?? 0 }}</p>
              <p class="text-gray-500 text-sm mt-1">Colegios</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
              <p class="text-3xl font-bold text-green-700">{{ stats?.enabled_schools ?? 0 }}</p>
              <p class="text-gray-500 text-sm mt-1">Colegios habilitados</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
              <p class="text-3xl font-bold text-red-700">{{ stats?.blocked_schools ?? 0 }}</p>
              <p class="text-gray-500 text-sm mt-1">Colegios bloqueados</p>
            </div>
          </div>
        </section>

        <section v-else-if="activeSection === 'directores'">
          <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-5">
            <h3 class="text-gray-900 font-semibold mb-4">Registrar director</h3>
            <p v-if="erroresDirector._general" class="text-red-600 text-sm mb-4 bg-red-50 px-3 py-2 rounded-lg border border-red-300">
              {{ erroresDirector._general }}
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Nombre completo</label>
                <input v-model="formDirector.name" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800" />
                <p v-if="erroresDirector.name" class="text-red-600 text-xs mt-1">{{ erroresDirector.name[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Correo electronico</label>
                <input v-model="formDirector.email" type="email" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800" />
                <p v-if="erroresDirector.email" class="text-red-600 text-xs mt-1">{{ erroresDirector.email[0] }}</p>
              </div>
            </div>

            <div class="mt-4 flex justify-end">
              <button
                @click="guardarDirector"
                :disabled="loadingDirector"
                class="bg-gray-800 hover:bg-gray-900 disabled:opacity-50 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                {{ loadingDirector ? 'Guardando...' : 'Registrar director' }}
              </button>
            </div>
          </div>

          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
              <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Nombre</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Correo</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Codigo</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Colegio</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!listaDirectores.length">
                  <td colspan="5" class="px-5 py-8 text-center text-gray-400">Sin directores registrados.</td>
                </tr>
                <tr v-for="director in listaDirectores" :key="director.id" class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                  <td class="px-5 py-3 text-gray-900">{{ director.name }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ director.email }}</td>
                  <td class="px-5 py-3 text-gray-900 font-mono">{{ director.code ?? '-' }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ director.school?.name ?? 'Pendiente de registro' }}</td>
                  <td class="px-5 py-3">
                    <span
                      class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                      :class="director.school_id ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-yellow-50 text-yellow-700 border border-yellow-200'">
                      {{ director.school_id ? 'Vinculado' : 'Pendiente' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section v-else-if="activeSection === 'colegios'">
          <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
              <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Colegio</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Codigo</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Usuarios</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Profesores</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Estudiantes</th>
                  <th class="text-left px-5 py-3 text-gray-500 font-medium">Acceso</th>
                  <th class="px-5 py-3"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!listaColegios.length">
                  <td colspan="7" class="px-5 py-8 text-center text-gray-400">Sin colegios registrados.</td>
                </tr>
                <tr v-for="school in listaColegios" :key="school.id" class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                  <td class="px-5 py-3 text-gray-900">{{ school.name }}</td>
                  <td class="px-5 py-3 text-gray-500 font-mono">{{ school.code }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ school.app_users_count }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ school.teachers_count }}</td>
                  <td class="px-5 py-3 text-gray-500">{{ school.students_count }}</td>
                  <td class="px-5 py-3">
                    <span
                      class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                      :class="school.is_access_enabled ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
                      {{ school.is_access_enabled ? 'Habilitado' : 'Bloqueado' }}
                    </span>
                  </td>
                  <td class="px-5 py-3 text-right">
                    <button
                      @click="toggleSchoolAccess(school)"
                      :disabled="togglingSchoolId === school.id"
                      class="text-xs px-3 py-1.5 rounded-lg border transition-colors"
                      :class="school.is_access_enabled
                        ? 'border-red-300 text-red-600 hover:bg-red-50'
                        : 'border-green-300 text-green-700 hover:bg-green-50'">
                      {{ togglingSchoolId === school.id ? 'Actualizando...' : school.is_access_enabled ? 'Bloquear' : 'Habilitar' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </main>
    </div>
  </div>
</template>
