<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    phone: user.phone || '',
    address: user.address || '',
});
</script>

<template>
    <section>
        <header class="mb-5 flex gap-3 items-center">
            <div class="w-10 h-10 rounded bg-red-50 flex items-center justify-center text-[#E30613]">
                <i class="fas fa-user-edit text-xl"></i>
            </div>
            <div>
                <h2 class="text-base font-black text-gray-900">Informasi Profil</h2>
                <p class="text-xs font-medium text-gray-500">Perbarui data diri Anda.</p>
            </div>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nama Lengkap</label>
                <input type="text" v-model="form.name" class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all" required autofocus autocomplete="name">
                <div v-if="form.errors.name" class="text-[11px] text-[#E30613] font-bold mt-1">{{ form.errors.name }}</div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Email</label>
                <input type="email" v-model="form.email" class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all" required autocomplete="username">
                <div v-if="form.errors.email" class="text-[11px] text-[#E30613] font-bold mt-1">{{ form.errors.email }}</div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nomor Telepon</label>
                <input type="text" v-model="form.phone" placeholder="Contoh: 08123456789" class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all">
                <div v-if="form.errors.phone" class="text-[11px] text-[#E30613] font-bold mt-1">{{ form.errors.phone }}</div>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Alamat</label>
                <textarea v-model="form.address" rows="2" placeholder="Alamat lengkap Anda" class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all resize-none"></textarea>
                <div v-if="form.errors.address" class="text-[11px] text-[#E30613] font-bold mt-1">{{ form.errors.address }}</div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Alamat email Anda belum terverifikasi.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="text-[#E30613] underline hover:text-black font-bold"
                    >
                        Klik di sini untuk mengirim ulang link verifikasi.
                    </Link>
                </p>

                <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                    Link verifikasi baru telah dikirim ke alamat email Anda.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit" :disabled="form.processing" class="bg-gray-900 hover:bg-black text-white px-6 py-3 rounded-lg text-xs font-bold tracking-wide transition-colors disabled:opacity-50 inline-flex items-center gap-2">
                    <i v-if="form.processing" class="fas fa-spinner fa-spin"></i> Simpan
                </button>

                <Transition enter-active-class="transition ease-in-out duration-300" enter-from-class="opacity-0 translate-y-1" leave-active-class="transition ease-in-out duration-300" leave-to-class="opacity-0 translate-y-1">
                    <p v-if="form.recentlySuccessful" class="text-[11px] font-bold text-green-600 uppercase tracking-wider flex items-center gap-1"><i class="fas fa-check-circle"></i> Tersimpan</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
