<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
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
    <section>
        <header class="mb-5 flex gap-3 items-center">
            <div class="w-10 h-10 rounded bg-red-50 flex items-center justify-center text-[#E30613]">
                <i class="fas fa-lock text-xl"></i>
            </div>
            <div>
                <h2 class="text-base font-black text-gray-900">Ubah Password</h2>
                <p class="text-xs font-medium text-gray-500">Pastikan akun menggunakan password yang kuat dan unik.</p>
            </div>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Password Saat Ini</label>
                <input type="password" v-model="form.current_password" ref="currentPasswordInput" class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all" autocomplete="current-password">
                <div v-if="form.errors.current_password" class="text-[11px] text-[#E30613] font-bold mt-1">{{ form.errors.current_password }}</div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Password Baru</label>
                <input type="password" v-model="form.password" ref="passwordInput" class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all" autocomplete="new-password">
                <div v-if="form.errors.password" class="text-[11px] text-[#E30613] font-bold mt-1">{{ form.errors.password }}</div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Konfirmasi Password Baru</label>
                <input type="password" v-model="form.password_confirmation" class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all" autocomplete="new-password">
                <div v-if="form.errors.password_confirmation" class="text-[11px] text-[#E30613] font-bold mt-1">{{ form.errors.password_confirmation }}</div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit" :disabled="form.processing" class="bg-gray-900 hover:bg-black text-white px-6 py-3 rounded-lg text-xs font-bold tracking-wide transition-colors disabled:opacity-50 inline-flex items-center gap-2">
                    <i v-if="form.processing" class="fas fa-spinner fa-spin"></i> Simpan Password
                </button>

                <Transition enter-active-class="transition ease-in-out duration-300" enter-from-class="opacity-0 translate-y-1" leave-active-class="transition ease-in-out duration-300" leave-to-class="opacity-0 translate-y-1">
                    <p v-if="form.recentlySuccessful" class="text-[11px] font-bold text-green-600 uppercase tracking-wider flex items-center gap-1"><i class="fas fa-check-circle"></i> Tersimpan</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
