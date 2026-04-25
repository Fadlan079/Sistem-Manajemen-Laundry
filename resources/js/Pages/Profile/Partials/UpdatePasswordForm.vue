<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const emit = defineEmits(['back']);

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const passwordStrength = computed(() => {
    const p = form.password;
    if (!p) return 0;
    let strength = 0;
    if (p.length >= 8) strength++;
    if (/[A-Z]/.test(p)) strength++;
    if (/[0-9]/.test(p)) strength++;
    if (/[^A-Za-z0-9]/.test(p)) strength++;
    return strength;
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <div class="flex flex-col min-h-[calc(100vh-120px)]">
        <!-- Header Section -->
        <header class="flex items-start justify-between mt-4 relative">
            <div class="flex-1 pr-12">
                <button @click="$emit('back')" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-primary shadow-sm border border-gray-100 mb-6 active:scale-95 transition-transform">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h1 class="text-2xl font-black text-gray-900 leading-tight">Ubah Password</h1>
                <p class="text-sm font-medium text-gray-400 mt-2 leading-relaxed">Gunakan password baru yang kuat untuk keamanan akun Anda.</p>
            </div>

            <!-- Header Illustration -->
            <div class="w-32 h-32 absolute -right-4 -top-4 opacity-40 pointer-events-none">
                <div class="w-full h-full relative flex items-center justify-center">
                    <div class="absolute inset-0 border border-red-100 rounded-full animate-[ping_4s_infinite]"></div>
                    <div class="absolute inset-4 border border-red-50 rounded-full"></div>
                    <div class="w-16 h-16 bg-red-50 rounded-2xl rotate-12 flex items-center justify-center text-primary shadow-inner">
                        <i class="fas fa-shield-alt text-3xl -rotate-12"></i>
                    </div>
                </div>
            </div>
        </header>

        <form @submit.prevent="updatePassword" class="flex-1 space-y-8 pb-32">
            <!-- Current Password -->
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-red-50 text-primary rounded-lg flex items-center justify-center text-sm shadow-sm">
                        <i class="fas fa-lock"></i>
                    </div>
                    <label class="text-sm font-black text-gray-800 tracking-tight">Password Saat Ini</label>
                </div>
                <div class="relative">
                    <input
                        :type="showCurrentPassword ? 'text' : 'password'"
                        v-model="form.current_password"
                        ref="currentPasswordInput"
                        placeholder="Masukkan password saat ini"
                        class="w-full bg-white border border-gray-200 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all placeholder:text-gray-300 shadow-sm"
                    >
                    <button type="button" @click="showCurrentPassword = !showCurrentPassword" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-400 transition-colors">
                        <i :class="['fas', showCurrentPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                    </button>
                </div>
                <p class="text-[11px] font-medium text-gray-400 mt-2 px-1">Masukkan password yang sedang Anda gunakan.</p>
                <div v-if="form.errors.current_password" class="text-[10px] text-primary font-bold mt-2 ml-1 uppercase tracking-tight">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ form.errors.current_password }}
                </div>
            </div>

            <!-- New Password -->
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-red-50 text-primary rounded-lg flex items-center justify-center text-sm shadow-sm">
                        <i class="fas fa-key"></i>
                    </div>
                    <label class="text-sm font-black text-gray-800 tracking-tight">Password Baru</label>
                </div>
                <div class="relative">
                    <input
                        :type="showNewPassword ? 'text' : 'password'"
                        v-model="form.password"
                        ref="passwordInput"
                        placeholder="Masukkan password baru"
                        class="w-full bg-white border border-gray-200 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all placeholder:text-gray-300 shadow-sm"
                    >
                    <button type="button" @click="showNewPassword = !showNewPassword" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-400 transition-colors">
                        <i :class="['fas', showNewPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                    </button>
                </div>

                <!-- Strength Indicator -->
                <div class="flex gap-1.5 mt-3 px-1">
                    <div v-for="i in 4" :key="i" :class="['h-1 rounded-full flex-1 transition-colors', i <= passwordStrength ? (passwordStrength <= 2 ? 'bg-primary' : 'bg-green-500') : 'bg-gray-100']"></div>
                </div>
                <p class="text-[11px] font-medium text-gray-400 mt-2 px-1">Minimal 8 karakter dengan kombinasi huruf & angka.</p>

                <div v-if="form.errors.password" class="text-[10px] text-primary font-bold mt-2 ml-1 uppercase tracking-tight">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ form.errors.password }}
                </div>
            </div>

            <!-- Confirm Password -->
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-red-50 text-primary rounded-lg flex items-center justify-center text-sm shadow-sm">
                        <i class="fas fa-shield-check"></i>
                    </div>
                    <label class="text-sm font-black text-gray-800 tracking-tight">Konfirmasi Password Baru</label>
                </div>
                <div class="relative">
                    <input
                        :type="showConfirmPassword ? 'text' : 'password'"
                        v-model="form.password_confirmation"
                        placeholder="Ulangi password baru"
                        class="w-full bg-white border border-gray-200 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all placeholder:text-gray-300 shadow-sm"
                    >
                    <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-400 transition-colors">
                        <i :class="['fas', showConfirmPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                    </button>
                </div>
                <p class="text-[11px] font-medium text-gray-400 mt-2 px-1">Pastikan password baru yang Anda masukkan sama.</p>
                <div v-if="form.errors.password_confirmation" class="text-[10px] text-primary font-bold mt-2 ml-1 uppercase tracking-tight">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ form.errors.password_confirmation }}
                </div>
            </div>

            <!-- Tips Card -->
            <div class="bg-gray-50/50 rounded-2xl p-5 border border-gray-100 flex gap-4 relative overflow-hidden group">
                <div class="shrink-0 w-10 h-10 bg-white rounded-xl flex items-center justify-center text-primary shadow-sm">
                    <i class="fas fa-info text-xs"></i>
                </div>
                <div class="flex-1">
                    <h4 class="text-xs font-black text-gray-900 uppercase tracking-widest mb-1">Tips Keamanan</h4>
                    <p class="text-[11px] text-gray-500 leading-relaxed font-medium">Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol untuk membuat password lebih kuat.</p>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-20 group-hover:scale-110 transition-transform">
                    <i class="fas fa-user-shield text-6xl text-primary"></i>
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
                        <span>Simpan Password Baru</span>
                    </button>

                    <Transition
                        enter-active-class="transition ease-in-out duration-300"
                        enter-from-class="opacity-0 translate-y-1"
                        leave-active-class="transition ease-in-out duration-300"
                        leave-to-class="opacity-0 translate-y-1"
                    >
                        <div v-if="form.recentlySuccessful" class="bg-green-50 text-green-600 p-3 rounded-xl border border-green-100 flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-wider mt-1">
                            <i class="fas fa-check-circle"></i> Password Berhasil Diperbarui
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
