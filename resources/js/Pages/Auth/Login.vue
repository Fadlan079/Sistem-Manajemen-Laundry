<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const resendCooldown = ref(0);
let cooldownInterval = null;
const isResending = ref(false);

const startCooldown = (seconds) => {
    resendCooldown.value = seconds;
    localStorage.setItem('resend_verification_cooldown', Date.now() + seconds * 1000);
    
    if (cooldownInterval) clearInterval(cooldownInterval);
    cooldownInterval = setInterval(() => {
        const remaining = Math.max(0, Math.ceil((parseInt(localStorage.getItem('resend_verification_cooldown')) - Date.now()) / 1000));
        resendCooldown.value = remaining;
        
        if (remaining <= 0) {
            clearInterval(cooldownInterval);
        }
    }, 1000);
};

onMounted(() => {
    const expiresAt = localStorage.getItem('resend_verification_cooldown');
    if (expiresAt) {
        const remaining = Math.max(0, Math.ceil((parseInt(expiresAt) - Date.now()) / 1000));
        if (remaining > 0) {
            startCooldown(remaining);
        }
    }
});

onUnmounted(() => {
    if (cooldownInterval) clearInterval(cooldownInterval);
});

const resendVerification = () => {
    if (resendCooldown.value > 0 || isResending.value || !form.email) return;
    
    isResending.value = true;
    router.post(route('verification.resend.guest'), { email: form.email }, {
        preserveScroll: true,
        onSuccess: () => {
            startCooldown(120);
            form.clearErrors('email');
        },
        onFinish: () => {
            isResending.value = false;
        }
    });
};
</script>

<template>
    <Head title="Masuk - Hi Wash Laundry" />

    <div class="flex min-h-screen bg-gray-100 font-sans">
        <div class="hidden lg:flex w-1/2 bg-red-600 flex-col justify-center p-16 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-red-500 rounded-full translate-x-1/3 -translate-y-1/3 blur-[80px] opacity-60"></div>
            <div class="absolute bottom-0 left-0 w-72 h-72 bg-red-800 rounded-full -translate-x-1/3 translate-y-1/3 blur-[80px] opacity-40"></div>

            <div class="relative z-10 w-full max-w-lg mx-auto">
                <div class="flex items-center gap-4 mb-12">
                    <img 
                        src="logo.png" 
                        alt="Logo Hi Wash Laundry" 
                        class="rounded-full h-14 w-14 object-cover drop-shadow-sm transition-transform duration-300 hover:scale-105"
                    />
                    
                    <span class="text-3xl font-bold italic tracking-tighter text-white">
                        Hi Wash <span class="font-light not-italic">Laundry</span>
                    </span>
                </div>

                <h1 class="text-4xl lg:text-5xl font-black mb-4 leading-tight tracking-tighter">
                    Sistem Manajemen<br/>Laundry Terpadu.
                </h1>
                <p class="text-red-100 mb-10 text-lg font-medium">
                    Efisiensi waktu dan kualitas terjamin. Solusi digital untuk pengalaman laundry yang lebih transparan.
                </p>

                <div class="space-y-6">
                    <div class="flex items-start space-x-4 group">
                        <div class="flex-shrink-0 w-12 h-12 bg-white/10 backdrop-blur-sm rounded-xl flex items-center justify-center border border-white/20 group-hover:bg-white/20 transition-colors">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white tracking-tight">Real-Time Tracking</h3>
                            <p class="text-red-100/80 text-sm mt-1 leading-relaxed">Pantau status pesanan dan estimasi penyelesaian pakaian Anda secara langsung dan akurat.</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4 group">
                        <div class="flex-shrink-0 w-12 h-12 bg-white/10 backdrop-blur-sm rounded-xl flex items-center justify-center border border-white/20 group-hover:bg-white/20 transition-colors">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white tracking-tight">Standar Higienitas</h3>
                            <p class="text-red-100/80 text-sm mt-1 leading-relaxed">Sistem pencucian profesional dengan mesin terkalibrasi dan pemisahan ketat antar pelanggan.</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-4 group">
                        <div class="flex-shrink-0 w-12 h-12 bg-white/10 backdrop-blur-sm rounded-xl flex items-center justify-center border border-white/20 group-hover:bg-white/20 transition-colors">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white tracking-tight">Layanan Kilat</h3>
                            <p class="text-red-100/80 text-sm mt-1 leading-relaxed">Penyelesaian cepat dengan integrasi notifikasi WhatsApp otomatis saat pakaian siap diambil.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 lg:p-12 border border-gray-100">

                <h2 class="text-3xl font-black text-gray-900 tracking-tighter text-center mb-2">Selamat Datang</h2>
                <p class="text-center text-gray-500 text-sm mb-10">Silakan masuk menggunakan kredensial Anda.</p>

                <div v-if="status" class="mb-4 text-sm font-medium text-green-600 bg-green-50 p-3 rounded-lg border border-green-100">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-red-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>
                            </span>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="Alamat Email"
                                required
                                class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-red-600/20 focus:border-red-600 outline-none transition-all font-medium text-gray-700"
                            />
                        </div>
                        <div v-if="form.errors.email" class="mt-1">
                            <p class="text-xs font-medium text-red-500">{{ form.errors.email }}</p>
                            <button
                                v-if="form.errors.email.toLowerCase().includes('belum diverifikasi')"
                                type="button"
                                @click="resendVerification"
                                :disabled="resendCooldown > 0 || isResending"
                                class="mt-2 text-xs font-bold text-red-600 hover:text-red-700 disabled:opacity-50 transition-colors"
                            >
                                <span v-if="resendCooldown > 0">Kirim ulang dalam {{ resendCooldown }} detik</span>
                                <span v-else-if="isResending">Mengirim...</span>
                                <span v-else>Kirim Ulang Email Verifikasi</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-red-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </span>
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="Kata Sandi"
                                required
                                class="w-full pl-12 pr-12 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-red-600/20 focus:border-red-600 outline-none transition-all font-medium text-gray-700"
                            />
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition-colors">
                                <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs font-medium text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center cursor-pointer group">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500 transition-colors"
                            />
                            <span class="ml-2 text-gray-500 group-hover:text-gray-700 transition-colors">Ingat sesi saya</span>
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-red-600 hover:text-red-700 hover:underline font-bold transition-colors"
                        >
                            Lupa sandi?
                        </Link>
                    </div>

                    <div class="space-y-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-red-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Masuk
                        </button>

                        <Link
                            :href="route('home')"
                            class="w-full flex items-center justify-center gap-2 py-3 bg-gray-50 text-gray-500 font-bold rounded-xl border border-gray-200 hover:bg-gray-100 hover:text-gray-700 transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Kembali ke Beranda
                        </Link>
                    </div>

                    <p class="text-center text-sm text-gray-500 mt-8">
                        Belum terdaftar di sistem?
                        <Link :href="route('register')" class="text-red-600 font-bold hover:underline ml-1 transition-colors">
                            Buat Akun Baru
                        </Link>
                    </p>
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