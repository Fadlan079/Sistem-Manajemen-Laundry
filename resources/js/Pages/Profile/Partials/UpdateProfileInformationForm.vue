<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const emit = defineEmits(['back']);
const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    phone: user.phone || '',
});

// Resend Verification Logic with Cooldown
import { onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

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

const sendVerification = () => {
    if (resendCooldown.value > 0 || isResending.value) return;
    
    isResending.value = true;
    router.post(route('verification.send'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            startCooldown(120);
        },
        onFinish: () => {
            isResending.value = false;
        }
    });
};
</script>

<template>
    <div class="flex flex-col">

        <form @submit.prevent="form.patch(route('profile.update'))" class="flex-1 space-y-8 pb-32">
            <!-- Full Name -->
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-red-50 text-primary rounded-lg flex items-center justify-center text-sm shadow-sm">
                        <i class="fas fa-user"></i>
                    </div>
                    <label class="text-sm font-black text-gray-800 tracking-tight">Nama Lengkap</label>
                </div>
                <div class="relative">
                    <input
                        type="text"
                        v-model="form.name"
                        placeholder="Masukkan nama lengkap"
                        class="w-full bg-white border border-gray-200 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all placeholder:text-gray-300 shadow-sm"
                        required
                        autofocus
                        autocomplete="name"
                    >
                </div>
                <div v-if="form.errors.name" class="text-[10px] text-primary font-bold mt-2 ml-1 uppercase tracking-tight">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ form.errors.name }}
                </div>
            </div>

            <!-- Email -->
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-red-50 text-primary rounded-lg flex items-center justify-center text-sm shadow-sm">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <label class="text-sm font-black text-gray-800 tracking-tight">Email</label>
                </div>
                <div class="relative">
                    <input
                        type="email"
                        v-model="form.email"
                        placeholder="email@contoh.com"
                        class="w-full bg-white border border-gray-200 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all placeholder:text-gray-300 shadow-sm"
                        required
                        autocomplete="username"
                    >
                </div>
                <div v-if="form.errors.email" class="text-[10px] text-primary font-bold mt-2 ml-1 uppercase tracking-tight">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ form.errors.email }}
                </div>

                <!-- Verification Status -->
                <div v-if="mustVerifyEmail" class="mt-4">
                    <div v-if="user.email_verified_at === null" class="bg-amber-50 border border-amber-200 p-5 rounded-3xl shadow-sm">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 shrink-0 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center shadow-sm">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-[11px] font-black text-amber-900 uppercase tracking-wider mb-1">Email Belum Terverifikasi</h4>
                                <p class="text-[11px] text-amber-700 font-medium leading-relaxed mb-4">
                                    Verifikasi email Anda untuk mendapatkan akses penuh ke semua fitur dan notifikasi transaksi penting.
                                </p>
                                <button
                                    type="button"
                                    @click="sendVerification"
                                    :disabled="resendCooldown > 0 || isResending"
                                    class="w-full sm:w-auto px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white text-[10px] font-black uppercase tracking-widest rounded-2xl transition-all shadow-md active:scale-95 disabled:opacity-50 disabled:grayscale flex items-center justify-center gap-2"
                                >
                                    <i v-if="isResending" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-paper-plane text-[9px]"></i>
                                    <span v-if="resendCooldown > 0">Tunggu {{ resendCooldown }}s</span>
                                    <span v-else-if="isResending">Mengirim...</span>
                                    <span v-else>Verifikasi Sekarang</span>
                                </button>
                                
                                <transition
                                    enter-active-class="transition ease-out duration-300"
                                    enter-from-class="opacity-0 -translate-y-2"
                                    enter-to-class="opacity-100 translate-y-0"
                                >
                                    <div v-show="status === 'verification-link-sent'" class="mt-4 py-2.5 px-4 bg-green-100 text-green-700 rounded-xl text-[9px] font-bold flex items-center gap-2 border border-green-200">
                                        <i class="fas fa-check-circle text-xs"></i>
                                        LINK VERIFIKASI TELAH DIKIRIM KE EMAIL ANDA.
                                    </div>
                                </transition>
                            </div>
                        </div>
                    </div>
                    <div v-else class="bg-green-50 border border-green-100 p-4 rounded-3xl flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center shadow-sm shrink-0">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="text-[11px] text-green-700 font-black uppercase tracking-wider">Email Telah Terverifikasi</span>
                    </div>
                </div>
            </div>

            <!-- Phone Number -->
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-red-50 text-primary rounded-lg flex items-center justify-center text-sm shadow-sm">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <label class="text-sm font-black text-gray-800 tracking-tight">Nomor Telepon</label>
                </div>
                <div class="relative">
                    <input
                        type="text"
                        v-model="form.phone"
                        placeholder="08xxxxxxxxxx"
                        class="w-full bg-white border border-gray-200 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all placeholder:text-gray-300 shadow-sm"
                    >
                </div>
                <div v-if="form.errors.phone" class="text-[10px] text-primary font-bold mt-2 ml-1 uppercase tracking-tight">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ form.errors.phone }}
                </div>
            </div>

            <!-- Info Card -->
            <div class="bg-gray-50/50 rounded-2xl p-5 border border-gray-100 flex gap-4 relative overflow-hidden group">
                <div class="shrink-0 w-10 h-10 bg-white rounded-xl flex items-center justify-center text-primary shadow-sm">
                    <i class="fas fa-info text-xs"></i>
                </div>
                <div class="flex-1">
                    <h4 class="text-xs font-black text-gray-900 uppercase tracking-widest mb-1">Penting</h4>
                    <p class="text-[11px] text-gray-500 leading-relaxed font-medium">Pastikan data yang Anda masukkan sudah benar untuk kelancaran pengiriman dan komunikasi.</p>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-20 group-hover:scale-110 transition-transform">
                    <i class="fas fa-id-card text-6xl text-primary"></i>
                </div>
            </div>

            <!-- Submit Section (Fixed for mobile) -->
            <div class="fixed bottom-0 left-0 right-0 bg-white sm:relative pt-4 pb-6 sm:pb-0 px-6 sm:px-0 border-t rounded-t-3xl border-gray-100 sm:border-0 z-50 shadow-[0_-20px_40px_-10px_rgba(0,0,0,0.12)] sm:shadow-none">
                <div class="max-w-xl mx-auto flex flex-col gap-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-primary hover:bg-black text-white py-4 sm:py-5 rounded-2xl text-sm font-black uppercase tracking-widest shadow-xl shadow-primary/20 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-3"
                    >
                        <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                        <span>Simpan Perubahan</span>
                    </button>

                    <Transition
                        enter-active-class="transition ease-in-out duration-300"
                        enter-from-class="opacity-0 translate-y-1"
                        leave-active-class="transition ease-in-out duration-300"
                        leave-to-class="opacity-0 translate-y-1"
                    >
                        <div v-if="form.recentlySuccessful" class="bg-green-50 text-green-600 p-3 rounded-xl border border-green-100 flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-wider mt-1">
                            <i class="fas fa-check-circle"></i> Profil Berhasil Diperbarui
                        </div>
                    </Transition>
                </div>
            </div>
        </form>
    </div>
</template>

<style scoped>
.text-primary {
    color: #E30613;
}
.bg-primary {
    background-color: #E30613;
}
.shadow-primary\/20 {
    --tw-shadow-color: rgba(227, 6, 19, 0.2);
    --tw-shadow: var(--tw-shadow-lookup, 0 0 #0000), 0 10px 15px -3px var(--tw-shadow-color), 0 4px 6px -4px var(--tw-shadow-color);
    box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
}
</style>
