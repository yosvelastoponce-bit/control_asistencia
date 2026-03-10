<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email:    '',
    password: '',
});

const submit = () => {
    form.post(route('profesor.login.post'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login Profesor" />

    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">
                Iniciar sesión como Profesor
            </h2>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                        placeholder="correo@ejemplo.com"
                        autofocus
                    />
                    <span v-if="form.errors.email" class="text-red-500 text-sm">
                        {{ form.errors.email }}
                    </span>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Contraseña</label>
                    <input
                        v-model="form.password"
                        type="password"
                        class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                        placeholder="••••••••"
                    />
                    <span v-if="form.errors.password" class="text-red-500 text-sm">
                        {{ form.errors.password }}
                    </span>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                >
                    {{ form.processing ? 'Ingresando...' : 'Ingresar' }}
                </button>
            </form>
        </div>
    </div>
</template>
