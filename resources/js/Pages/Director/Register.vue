<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'

const showPassword = ref(false)

const form = useForm({
  school_name: '',
  school_code: '',
  school_address: '',
  name: '',
  email: '',
  director_code: '',
  password: '',
  password_confirmation: '',
})

const loading = computed(() => form.processing)
const errors = computed(() => form.errors)

const submit = () => {
  form.post(route('director.register.post'))
}
</script>

<template>
  <div class="min-h-screen bg-white flex items-center justify-center p-4">
    <div class="relative w-full max-w-2xl">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gray-100 border border-gray-200 mb-4">
          <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Registro de Director</h1>
        <p class="text-gray-500 mt-2 text-sm">Registra tu colegio usando los datos y el codigo entregados por el super admin</p>
      </div>

      <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <div class="flex items-center gap-2 mb-4">
              <div class="h-px flex-1 bg-gray-200"></div>
              <span class="text-xs font-semibold text-gray-600 uppercase tracking-widest">Datos del Colegio</span>
              <div class="h-px flex-1 bg-gray-200"></div>
            </div>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                  Nombre del colegio <span class="text-red-500">*</span>
                </label>
                <input v-model="form.school_name" type="text" placeholder="Ej: I.E. San Martin de Porres"
                  class="w-full bg-white border rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-all"
                  :class="errors.school_name ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:border-gray-800'" />
                <p v-if="errors.school_name" class="text-red-600 text-xs mt-1.5">{{ errors.school_name }}</p>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Codigo del colegio <span class="text-red-500">*</span>
                  </label>
                  <input v-model="form.school_code" type="text" placeholder="Ej: 4281154"
                    class="w-full bg-white border rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-all"
                    :class="errors.school_code ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:border-gray-800'" />
                  <p v-if="errors.school_code" class="text-red-600 text-xs mt-1.5">{{ errors.school_code }}</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Direccion <span class="text-red-500">*</span>
                  </label>
                  <input v-model="form.school_address" type="text" placeholder="Ej: Av. Los Pinos 123"
                    class="w-full bg-white border rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-all"
                    :class="errors.school_address ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:border-gray-800'" />
                  <p v-if="errors.school_address" class="text-red-600 text-xs mt-1.5">{{ errors.school_address }}</p>
                </div>
              </div>
            </div>
          </div>

          <div>
            <div class="flex items-center gap-2 mb-4">
              <div class="h-px flex-1 bg-gray-200"></div>
              <span class="text-xs font-semibold text-gray-600 uppercase tracking-widest">Datos del Director</span>
              <div class="h-px flex-1 bg-gray-200"></div>
            </div>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                  Nombre completo <span class="text-red-500">*</span>
                </label>
                <input v-model="form.name" type="text" placeholder="Ej: Juan Perez Garcia"
                  class="w-full bg-white border rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-all"
                  :class="errors.name ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:border-gray-800'" />
                <p v-if="errors.name" class="text-red-600 text-xs mt-1.5">{{ errors.name }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                  Correo electronico <span class="text-red-500">*</span>
                </label>
                <input v-model="form.email" type="email" placeholder="director@colegio.edu.pe"
                  class="w-full bg-white border rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-all"
                  :class="errors.email ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:border-gray-800'" />
                <p v-if="errors.email" class="text-red-600 text-xs mt-1.5">{{ errors.email }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                  Codigo del director <span class="text-red-500">*</span>
                </label>
                <input v-model="form.director_code" type="text" placeholder="Codigo entregado por el super admin"
                  class="w-full bg-white border rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-all"
                  :class="errors.director_code ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:border-gray-800'" />
                <p v-if="errors.director_code" class="text-red-600 text-xs mt-1.5">{{ errors.director_code }}</p>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Contrasena <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Minimo 8 caracteres"
                      class="w-full bg-white border rounded-xl px-4 py-3 pr-11 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-all"
                      :class="errors.password ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:border-gray-800'" />
                    <button type="button" @click="showPassword = !showPassword"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 transition-colors">
                      <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                      <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M3 3l18 18"/>
                      </svg>
                    </button>
                  </div>
                  <p v-if="errors.password" class="text-red-600 text-xs mt-1.5">{{ errors.password }}</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Confirmar contrasena <span class="text-red-500">*</span>
                  </label>
                  <input v-model="form.password_confirmation" :type="showPassword ? 'text' : 'password'" placeholder="Repite la contrasena"
                    class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 focus:border-gray-800 transition-all" />
                </div>
              </div>
            </div>
          </div>

          <button type="submit" :disabled="loading"
            class="w-full bg-gray-800 hover:bg-gray-900 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold py-3.5 rounded-xl transition-all duration-200 flex items-center justify-center gap-2">
            <svg v-if="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span>{{ loading ? 'Registrando...' : 'Crear cuenta' }}</span>
          </button>

          <p class="text-center text-sm text-gray-500">
            Ya tienes cuenta?
            <Link :href="route('director.login')" class="text-gray-800 hover:text-gray-900 font-medium transition-colors">
              Iniciar sesion
            </Link>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>
