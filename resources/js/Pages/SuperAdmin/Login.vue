<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const showPassword = ref(false)

const form = useForm({
  email: '',
  password: '',
})

const loading = computed(() => form.processing)

const submit = () => {
  form.post(route('super-admin.login.post'))
}
</script>

<template>
  <Head title="Super Admin Login" />

  <div class="min-h-screen bg-white flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gray-100 border border-gray-200 mb-4">
          <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0112 20.055 12.083 12.083 0 015.84 10.578L12 14z"/>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Super Admin</h1>
        <p class="text-gray-500 mt-2 text-sm">Acceso privado de administracion global</p>
      </div>

      <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
        <form @submit.prevent="submit" class="space-y-5">
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
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="********"
                class="w-full bg-white border rounded-xl px-4 py-3 pr-11 text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800 transition-all"
                :class="form.errors.password ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:border-gray-800'"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
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
            <p v-if="form.errors.password" class="text-red-600 text-xs mt-1.5">{{ form.errors.password }}</p>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-gray-800 hover:bg-gray-900 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold py-3.5 rounded-xl transition-all duration-200 flex items-center justify-center gap-2">
            <svg v-if="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span>{{ loading ? 'Ingresando...' : 'Ingresar' }}</span>
          </button>

          <p class="text-center text-sm text-gray-500">
            Aun no tienes cuenta?
            <Link :href="route('super-admin.register')" class="text-gray-800 hover:text-gray-900 font-medium transition-colors">
              Crear cuenta
            </Link>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>
