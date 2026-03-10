<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm} from '@inertiajs/vue3';

const form = useForm({
    school_id: '',
    name: '',
    email: '',
    password: '',
    specialty:'',
});

const submit = () =>{

    const url= route('profesor.store');
    form.post(url, {
        onSuccess: (Response) => {

        }
    });
};

</script>

<template>
    <AuthenticatedLayout>
        <Head title="Registrar Profesor"></Head>
        <div class="py-12 max-w-2xl mx-auto">
            <h2>Registrar nuevo Profesor</h2>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">ID Colegio</label>
                    <input 
                        v-model="form.school_id" 
                        type="number"
                        class="w-full border rounded px-3 py-2"
                        placeholder="ID del colegio"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" >Nombre</label>
                    <input 
                        v-model="form.name" 
                        type="text" 
                        class="w-full border rounded px-3 py-2"
                        placeholder="Nombre completo"
                    />
                    <span v-if="form.errors.name" class="text-red-500 text-sm">
                        {{ form.errors.name }}
                    </span>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" >Email</label>
                    <input 
                        v-model="form.email" 
                        type="email" 
                        class="w-full border rounded px-3 py-2"
                        placeholder="correo@ejemplo.com"
                    />
                    <span v-if="form.errors.email" class="text-red-500 text-sm">
                        {{ form.errors.email }}
                    </span>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Contraseña</label>
                    <input 
                        v-model="form.password" 
                        type="text" 
                        class="w-full border rounded px-3 py-2"
                        placeholder="Contraseña"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Especialidad</label>
                    <input 
                        v-model="form.specialty" 
                        type="text" 
                        class="w-full border rounded px-3 py-2"
                        placeholder="Especialidad"
                    />
                </div>

                <button 
                    type="submit" 
                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded disabled:opacity-50"
                >
                    Guardar
                </button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>