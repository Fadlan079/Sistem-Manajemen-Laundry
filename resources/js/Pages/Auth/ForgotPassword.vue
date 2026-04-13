<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Lupa Kata Sandi - Hi Wash Laundry" />

    <div class="flex min-h-screen bg-gray-100 font-sans items-center justify-center p-6 sm:p-12 relative overflow-hidden">


        <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 lg:p-12 border border-gray-100 relative z-10">
            
            <div class="flex justify-center mb-8">
                <img src="/logo.png" alt="Logo Hi Wash" class="h-16 w-16 drop-shadow-sm rounded-full object-cover transition-transform duration-300 hover:scale-105">
            </div>

            <h2 class="text-3xl font-black text-gray-900 tracking-tighter text-center mb-2">Lupa Sandi?</h2>
            <p class="text-center text-gray-500 text-sm mb-8 leading-relaxed">
                Tidak masalah. Masukkan email Anda dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi Anda.
            </p>

            <div v-if="status" class="mb-6 text-sm font-medium text-green-600 bg-green-50 p-3 rounded-lg border border-green-100 text-center">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Email -->
                <div>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-red-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                        </span>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="Alamat Email Terdaftar"
                            required
                            autofocus
                            class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-red-600/20 focus:border-red-600 outline-none transition-all font-medium text-gray-700"
                        />
                    </div>
                    <p v-if="form.errors.email" class="mt-1 text-xs font-medium text-red-500">{{ form.errors.email }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-red-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Kirim Tautan Reset Sandi
                </button>

                <p class="text-center text-sm text-gray-500 mt-8">
                    Ingat kata sandi Anda?
                    <Link :href="route('login')" class="text-red-600 font-bold hover:underline ml-1 transition-colors">
                        Kembali ke Login
                    </Link>
                </p>
            </form>
        </div>
    </div>
</template>

<style scoped>
input:focus {
    outline: none;
}
</style>
