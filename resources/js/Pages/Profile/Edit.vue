<script setup>
import AppLayout from '@/Layouts/app.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

function getInitials(name) {
    if (!name) return 'U';
    const words = name.trim().split(' ');
    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}
</script>

<template>
    <Head title="Profile" />

    <AppLayout>
        <div class="pt-20 lg:pt-28 max-w-xl mx-auto pb-12 px-4 shadow-none">

            <!-- Profile Info Banner -->
            <div class="bg-red-50 rounded-2xl p-6 flex flex-col items-center justify-center gap-3 mb-8 border border-red-100/50 shadow-sm relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-[#E30613] rounded-full opacity-5 blur-2xl pointer-events-none"></div>
                <div class="w-20 h-20 rounded-full bg-white border-2 border-[#E30613] text-[#E30613] flex items-center justify-center font-black text-3xl tracking-tighter shrink-0 shadow-md">
                    {{ getInitials(user.name) }}
                </div>
                <div class="text-center">
                    <h2 class="text-[17px] font-black text-gray-900 tracking-tight leading-tight">{{ user.name }}</h2>
                    <p class="text-[13px] font-medium text-gray-600 mt-1">{{ user.email }}</p>
                    <p class="text-[11px] font-bold text-[#E30613] bg-red-100/50 px-2.5 py-1 rounded-full inline-block mt-3 uppercase tracking-wide border border-red-200">
                        <i class="fas fa-phone-alt mr-1 opacity-70 text-[10px]"></i> {{ user.phone || 'Nomor Belum Diatur' }}
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-white p-5 shadow-sm rounded-xl border border-gray-200/60">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>

                <div class="bg-white p-5 shadow-sm rounded-xl border border-gray-200/60">
                    <UpdatePasswordForm />
                </div>

                <div class="bg-white p-5 shadow-sm rounded-xl border border-red-200 bg-red-50/10">
                    <DeleteUserForm />
                </div>
            </div>

            <div class="mt-8 pt-4 border-t border-gray-200">
                <Link :href="route('logout')" method="post" as="button" class="w-full bg-white hover:bg-gray-50 border border-gray-200/80 text-[#E30613] font-bold py-4 rounded-xl text-sm transition-colors flex justify-center items-center gap-2 shadow-sm">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </Link>
            </div>

        </div>
    </AppLayout>
</template>
