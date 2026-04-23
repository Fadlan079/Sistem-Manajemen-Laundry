<script setup>
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section>
        <header class="mb-5 flex gap-3 items-center">
            <div class="w-10 h-10 rounded bg-red-100/50 flex items-center justify-center text-[#E30613]">
                <i class="fas fa-trash-alt text-xl"></i>
            </div>
            <div>
                <h2 class="text-base font-black text-red-900">Hapus Akun</h2>
                <p class="text-[11px] font-medium text-red-700/80">Semua data secara permanen akan dihapus.</p>
            </div>
        </header>

        <button @click="confirmUserDeletion" class="bg-[#E30613] hover:bg-black text-white px-6 py-3 rounded-lg text-xs font-bold tracking-wide transition-colors inline-flex items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i> Hapus Akun Secara Permanen
        </button>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <div class="mb-4 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-[#E30613]">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-gray-900 leading-tight">Yakin ingin menghapus?</h2>
                        <p class="text-xs font-medium text-gray-500 mt-1">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                </div>

                <p class="mt-4 text-[13px] text-gray-600 mb-6 bg-red-50 border border-red-100 p-3 rounded-lg">
                    Setelah akun Anda dihapus, semua sumber daya dan data akan dihapus secara permanen. Masukkan kata sandi Anda untuk mengonfirmasi.
                </p>

                <div class="mt-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Password Anda</label>

                    <input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all"
                        placeholder="Masukkan password..."
                        @keyup.enter="deleteUser"
                    />

                    <div v-if="form.errors.password" class="text-[11px] text-[#E30613] font-bold mt-1">{{ form.errors.password }}</div>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <button @click="closeModal" type="button" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-5 py-2.5 rounded-lg text-[13px] font-bold tracking-wide transition-colors">
                        Batal
                    </button>

                    <button
                        class="bg-[#E30613] hover:bg-black text-white px-5 py-2.5 rounded-lg text-[13px] font-bold tracking-wide transition-colors inline-flex items-center gap-2"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                        Ya, Hapus Akun
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
