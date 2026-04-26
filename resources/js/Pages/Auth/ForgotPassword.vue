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

    <div class="flex min-h-screen bg-white lg:bg-gray-100 font-sans items-start lg:items-center justify-center p-6 sm:p-12 relative overflow-y-auto">
        <!-- Mobile Back Button -->
        <Link
            :href="route('login')"
            class="lg:hidden absolute top-6 left-4 w-10 h-10 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-white z-50 border border-white/30 shadow-sm active:scale-95 transition-transform"
        >
            <i class="fas fa-arrow-left"></i>
        </Link>

        <!-- Mobile Top Waves Decoration -->
        <div class="lg:hidden absolute top-0 left-0 right-0 h-64 bg-[#E30613] overflow-hidden z-0">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-10 left-10 w-12 h-12 bg-white rounded-full opacity-50 blur-md"></div>
                <div class="absolute top-20 right-10 w-20 h-20 bg-[#FFE800] rounded-full opacity-60 blur-lg"></div>
            </div>

            <!-- Mobile Header Content -->
            <div class="relative z-10 flex flex-col items-center justify-center h-full pt-4 pb-16 text-center px-6">
                <img src="/logo.png" alt="Logo" class="h-16 w-16 rounded-full mb-4 border-2 border-white/30 shadow-xl object-cover">
                <h2 class="text-2xl font-black text-white tracking-tight">Lupa Sandi?</h2>
                <p class="text-red-50 text-xs font-medium opacity-90">Masukkan email Anda untuk tautan reset</p>
            </div>
            
            <!-- Decorative SVG Waves (Matched with daftar-layanan.vue) -->
            <div class="absolute bottom-0 left-0 w-full leading-none">
                <svg class="block w-full h-12" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="fill-[#FFE800]" d="M0,128L80,144C160,160,320,192,480,197.3C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                </svg>
                <svg class="absolute bottom-0 left-0 w-full h-8" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="fill-white" d="M0,64L80,90.7C160,117,320,171,480,186.7C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                </svg>
            </div>
        </div>

        <div class="w-full max-w-md bg-white lg:rounded-3xl lg:shadow-xl p-0 lg:p-12 lg:border border-gray-100 mt-56 lg:mt-0 relative z-20 pb-12">
            
            <div class="hidden lg:flex justify-center mb-8">
                <img src="/logo.png" alt="Logo Hi Wash" class="h-16 w-16 drop-shadow-sm rounded-full object-cover transition-transform duration-300 hover:scale-105">
            </div>

            <h2 class="hidden lg:block text-3xl font-black text-gray-900 tracking-tighter text-center mb-2">Lupa Sandi?</h2>
            <p class="hidden lg:block text-center text-gray-500 text-sm mb-8 leading-relaxed">
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
                    class="w-full bg-[#E30613] hover:bg-red-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-red-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed"
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
