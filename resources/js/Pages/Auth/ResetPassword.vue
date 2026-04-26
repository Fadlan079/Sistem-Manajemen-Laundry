<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Kata Sandi - Hi Wash Laundry" />

    <div class="flex min-h-screen bg-white lg:bg-gray-100 font-sans relative">
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
                <h2 class="text-2xl font-black text-white tracking-tight">Reset Sandi</h2>
                <p class="text-red-50 text-xs font-medium opacity-90">Silakan masukkan kata sandi baru Anda</p>
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

        <!-- Left Side (Branding) -->
        <div class="hidden lg:flex w-1/2 bg-[#E30613] flex-col justify-between p-16 text-white relative overflow-hidden border-r border-gray-100">
            <!-- Decorative Blobs -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-red-500 rounded-full translate-x-1/3 -translate-y-1/3 blur-[100px] opacity-40"></div>
            <div class="absolute bottom-0 left-0 w-72 h-72 bg-red-800 rounded-full -translate-x-1/3 translate-y-1/3 blur-[80px] opacity-30"></div>

            <div class="relative z-10 w-full max-w-lg mx-auto mt-auto mb-auto">
                <div class="flex items-center gap-4 mb-10">
                    <div class="p-1 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-xl shadow-black/10">
                        <img 
                            src="logo.png" 
                            alt="Logo Hi Wash Laundry" 
                            class="rounded-xl h-14 w-14 object-cover"
                        />
                    </div>
                    
                    <span class="text-3xl font-black italic tracking-tighter text-white">
                        Hi Wash <span class="font-light not-italic">Laundry</span>
                    </span>
                </div>

                <h1 class="text-5xl lg:text-6xl font-black mb-6 leading-[1.1] tracking-tighter">
                    Akses Kembali<br/>
                    <span class="text-[#FFE800]">Akun Anda.</span>
                </h1>
                <p class="text-red-100 mb-12 text-lg font-medium leading-relaxed opacity-90 max-w-md text-justify">
                    Lengkapi pemulihan akun Anda dengan membuat kata sandi baru yang kuat dan aman.
                </p>

                <div class="grid grid-cols-1 gap-8">
                    <div class="flex items-center space-x-5 group transition-transform hover:translate-x-2">
                        <div class="flex-shrink-0 w-14 h-14 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20 group-hover:bg-white/20 transition-all shadow-lg">
                            <i class="fas fa-user-shield text-xl text-[#FFE800]"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-extrabold text-white tracking-tight">Privasi Maksimal</h3>
                            <p class="text-red-100/70 text-sm mt-0.5 font-medium leading-relaxed">Sandi Anda dienkripsi secara aman.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Waves Accent -->
            <div class="absolute bottom-0 left-0 w-full leading-none z-0">
                <svg class="block w-full h-24" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="fill-[#FFE800]" d="M0,128L80,144C160,160,320,192,480,197.3C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                </svg>
                <svg class="absolute bottom-0 left-0 w-full h-16" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="fill-white lg:fill-gray-100" d="M0,64L80,90.7C160,117,320,171,480,186.7C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                </svg>
            </div>
        </div>

        <!-- Right Side (Form) -->
        <div class="w-full lg:w-1/2 flex items-start lg:items-center justify-center p-6 sm:p-12 overflow-y-auto pb-12 lg:pb-0">
            <div class="w-full max-w-md bg-white lg:rounded-3xl lg:shadow-xl p-0 lg:p-12 lg:border border-gray-100 mt-56 lg:mt-0 relative z-20">
                
                <div class="hidden lg:flex justify-center mb-8">
                    <div class="p-1 bg-red-50 rounded-2xl border border-red-100 shadow-sm transition-transform duration-300 hover:scale-105">
                        <img src="/logo.png" alt="Logo Hi Wash" class="h-16 w-16 rounded-xl object-cover">
                    </div>
                </div>

                <h2 class="hidden lg:block text-3xl font-black text-gray-900 tracking-tighter text-center mb-2">Reset Sandi</h2>
                <p class="hidden lg:block text-center text-gray-500 text-sm mb-8 leading-relaxed">
                    Silakan masukkan kata sandi baru Anda untuk akun {{ form.email }}.
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
                                placeholder="Kata Sandi Baru"
                                required
                                autofocus
                                class="w-full pl-12 pr-12 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-red-600/20 focus:border-red-600 outline-none transition-all font-medium text-gray-700"
                            />
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition-colors">
                                <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs font-medium text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-red-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618 4.618A9 9 0 0112 21a9 9 0 01-8.5-6.236M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </span>
                            <input
                                v-model="form.password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                placeholder="Konfirmasi Kata Sandi"
                                required
                                class="w-full pl-12 pr-12 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-red-600/20 focus:border-red-600 outline-none transition-all font-medium text-gray-700"
                            />
                            <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition-colors">
                                <svg v-if="showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="mt-1 text-xs font-medium text-red-500">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-[#E30613] hover:bg-red-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-red-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed mt-4"
                    >
                        Simpan Kata Sandi Baru
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
input:focus {
    outline: none;
}
</style>
