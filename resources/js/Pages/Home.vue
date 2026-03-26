<script setup>
defineProps({
  auth: Object,
})
</script>

<template>
  <div class="min-h-screen bg-white text-gray-900 flex items-center justify-center p-4">
    <div class="w-full max-w-3xl border border-gray-200 rounded-3xl bg-white shadow-sm overflow-hidden">
      <div class="px-8 py-10 border-b border-gray-200 bg-gray-50">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 mb-3">Sistema de Asistencia</p>
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">Control de ingreso con acceso protegido</h1>
        <p class="mt-3 text-sm sm:text-base text-gray-600 max-w-2xl">
          El escaner de asistencia general ya no esta disponible de forma publica. Solo usuarios autorizados pueden abrirlo y registrar ingresos.
        </p>
      </div>

      <div class="px-8 py-8 grid gap-6 sm:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-6">
          <h2 class="text-lg font-semibold text-gray-900">Acceso para personal autorizado</h2>
          <p class="mt-2 text-sm text-gray-600">
            Inicia sesion como director o profesor para abrir el escaner de asistencia general.
          </p>

          <div class="mt-5 flex flex-col gap-3">
            <a href="/director/login" class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-4 py-3 text-sm font-medium text-white hover:bg-black transition-colors">
              Ingresar como director
            </a>
            <a href="/profesor/login" class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-3 text-sm font-medium text-gray-700 hover:border-gray-500 hover:text-gray-900 transition-colors">
              Ingresar como profesor
            </a>
          </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6">
          <h2 class="text-lg font-semibold text-gray-900">Acceso al escaner</h2>
          <p class="mt-2 text-sm text-gray-600">
            Si ya tienes una sesion iniciada y cuentas con permiso para registrar el ingreso general, puedes ir directamente al modulo de escaneo.
          </p>

          <div class="mt-5">
            <a
              href="/general-attendance"
              class="inline-flex w-full items-center justify-center rounded-xl bg-gray-800 px-4 py-3 text-sm font-medium text-white hover:bg-gray-900 transition-colors"
            >
              Abrir asistencia general
            </a>
          </div>

          <p v-if="auth?.user" class="mt-4 text-xs text-gray-500">
            Sesion detectada: {{ auth.user.name }}.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
