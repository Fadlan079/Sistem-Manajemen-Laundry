<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    password: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Konfirmasi Sandi - Hi Wash Laundry" />

    <div class="flex min-h-screen bg-white lg:bg-gray-100 font-sans items-start lg:items-center justify-center p-6 sm:p-12 relative overflow-y-auto">
        <!-- Mobile Back Button -->
        <button
            @click="() => window.history.back()"
            class="lg:hidden absolute top-6 left-4 w-10 h-10 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-white z-50 border border-white/30 shadow-sm active:scale-95 transition-transform"
        >
            <i class="fas fa-arrow-left"></i>
        </button>

        <!-- Mobile Top Waves Decoration -->
        <div class="lg:hidden absolute top-0 left-0 right-0 h-64 bg-[#E30613] overflow-hidden z-0">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-10 left-10 w-12 h-12 bg-white rounded-full opacity-50 blur-md"></div>
                <div class="absolute top-20 right-10 w-20 h-20 bg-[#FFE800] rounded-full opacity-60 blur-lg"></div>
            </div>

            <!-- Mobile Header Content -->
            <div class="relative z-10 flex flex-col items-center justify-center h-full pt-4 pb-16 text-center px-6">
                <img src="/logo.png" alt="Logo" class="h-16 w-16 rounded-full mb-4 border-2 border-white/30 shadow-xl object-cover">
                <h2 class="text-2xl font-black text-white tracking-tight">Keamanan Berlapis</h2>
                <p class="text-red-50 text-xs font-medium opacity-90">Konfirmasi kata sandi Anda sebelum melanjutkan</p>
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
                <div class="h-16 w-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center drop-shadow-sm border border-red-100 transition-transform duration-300 hover:scale-105">
                    <i class="fas fa-lock text-2xl"></i>
                </div>
            </div>

            <h2 class="hidden lg:block text-3xl font-black text-gray-900 tracking-tighter text-center mb-2">Keamanan Berlapis</h2>
            <p class="hidden lg:block text-center text-gray-500 text-sm mb-8 leading-relaxed">
                Area ini membutuhkan verifikasi keamanan. Silakan konfirmasi kata sandi Anda sebelum melanjutkan.
            </p>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Password -->
                <div>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-red-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="Konfirmasi Kata Sandi"
                            required
                            autofocus
                            autocomplete="current-password"
                            class="w-full pl-12 pr-12 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-red-600/20 focus:border-red-600 outline-none transition-all font-medium text-gray-700"
                        />
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="mt-1 text-xs font-medium text-red-500">{{ form.errors.password }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-[#E30613] hover:bg-red-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-red-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Konfirmasi & Lanjutkan
                </button>
            </form>
        </div>
    </div>
</template>

<style scoped>
input:focus {
    outline: none;
}
</style>
