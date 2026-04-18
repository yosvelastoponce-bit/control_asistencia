<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const showPassword = ref(false)

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const loading = computed(() => form.processing)

const submit = () => {
  form.post(route('super-admin.register.post'))
}
</script>

<template>
  <Head title="Super Admin Register" />

  <div class="min-h-screen bg-white flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gray-100 border border-gray-200 mb-4">
          <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Crear Super Admin</h1>
        <p class="text-gray-500 mt-2 text-sm">Cuenta global para administrar directores y colegios</p>
      </div>

      <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Nombre completo</label>
            <input
              v-model="form.name"
              type="text"
              placeholder="Tu nombre"
              class="w-full bg-white border rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-all"
              :class="form.errors.name ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:border-gray-800'"
            />
            <p v-if="form.errors.name" class="text-red-600 text-xs mt-1.5">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Correo electronico</label>
            <input
              v-model="form.email"
              type="email"
              placeholder="admin@presenteya.com"
              class="w-full bg-white border rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-all"
              :class="form.errors.email ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:border-gray-800'"
            />
            <p v-if="form.errors.email" class="text-red-600 text-xs mt-1.5">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Contrasena</label>
            <input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Minimo 8 caracteres"
              class="w-full bg-white border rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-all"
              :class="form.errors.password ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:border-gray-800'"
            />
            <p v-if="form.errors.password" class="text-red-600 text-xs mt-1.5">{{ form.errors.password }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmar contrasena</label>
            <input
              v-model="form.password_confirmation"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Repite la contrasena"
              class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-all"
            />
          </div>

          <button
            type="button"
            @click="showPassword = !showPassword"
            class="text-sm text-gray-500 hover:text-gray-800 transition-colors">
            {{ showPassword ? 'Ocultar contrasena' : 'Mostrar contrasena' }}
          </button>

          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-gray-800 hover:bg-gray-900 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold py-3.5 rounded-xl transition-all duration-200 flex items-center justify-center gap-2">
            <svg v-if="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span>{{ loading ? 'Creando...' : 'Crear cuenta' }}</span>
          </button>

          <p class="text-center text-sm text-gray-500">
            Ya tienes cuenta?
            <Link :href="route('super-admin.login')" class="text-gray-800 hover:text-gray-900 font-medium transition-colors">
              Iniciar sesion
            </Link>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>
