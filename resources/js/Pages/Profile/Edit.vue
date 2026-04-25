<script setup>
import AppLayout from '@/Layouts/app.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UserAddress from './Partials/alamat.vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    ordersCount: {
        type: Number,
        default: 0
    },
    addressesCount: {
        type: Number,
        default: 0
    },
    addresses: {
        type: Array,
        default: () => []
    }
});

const user = computed(() => usePage().props.auth.user);
const activeView = ref('profile'); // 'profile', 'edit-info', 'edit-password', 'delete'

function getInitials(name) {
    if (!name) return 'U';
    const words = name.trim().split(' ');
    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

const avatarInput = ref(null);
const isUploadingAvatar = ref(false);

function handleAvatarChange(event) {
    const file = event.target.files[0];
    if (!file) return;

    isUploadingAvatar.value = true;
    const formData = new FormData();
    formData.append('avatar', file);

    router.post(route('profile.avatar.update'), formData, {
        preserveScroll: true,
        onSuccess: () => {
            isUploadingAvatar.value = false;
        },
        onError: () => {
            isUploadingAvatar.value = false;
        },
        onFinish: () => {
            if(avatarInput.value) avatarInput.value.value = '';
        }
    });
}
</script>

<template>
    <Head title="Profil Saya" />

    <AppLayout :hideBottomNav="activeView !== 'profile'">
        <!-- Dynamic Red Header Section -->
        <div class="bg-[#E30613] pt-24 pb-24 relative overflow-hidden">
            <!-- Decorative background -->
            <div class="absolute inset-0 opacity-20 pointer-events-none z-0">
                <div class="absolute top-10 left-10 w-12 h-12 bg-white rounded-full opacity-50"></div>
                <div class="absolute top-20 right-1/4 w-8 h-8 bg-white rounded-full opacity-50"></div>
                <div class="absolute bottom-10 right-10 w-24 h-24 bg-white rounded-full opacity-30"></div>
            </div>

            <!-- Back Button for Edit Views -->
            <button
                v-if="activeView !== 'profile'"
                @click="activeView = 'profile'"
                class="absolute top-[100px] left-6 w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center transition-all z-30 backdrop-blur-sm border border-white/20 shadow-lg active:scale-95"
            >
                <i class="fas fa-arrow-left"></i>
            </button>

            <!-- Profile Summary Header Content -->
            <div v-if="activeView === 'profile'" class="relative z-20 px-6 max-w-xl mx-auto flex items-center gap-6">
                <div class="relative shrink-0">
                    <div class="w-20 h-20 rounded-full border-4 border-white/20 bg-white flex items-center justify-center overflow-hidden shadow-lg relative">
                        <img v-if="user.avatar_url" :src="user.avatar_url" class="w-full h-full object-cover">
                        <span v-else class="text-2xl font-black text-[#E30613]">{{ getInitials(user.name) }}</span>

                        <div v-if="isUploadingAvatar" class="absolute inset-0 bg-white/70 flex items-center justify-center z-10 backdrop-blur-sm">
                            <i class="fas fa-spinner fa-spin text-[#E30613] text-xl"></i>
                        </div>
                    </div>
                    <button
                        @click="avatarInput.click()"
                        :disabled="isUploadingAvatar"
                        class="absolute -bottom-1 -right-1 w-7 h-7 bg-white rounded-full flex items-center justify-center text-gray-500 shadow-md border border-gray-100 hover:text-[#E30613] active:scale-90 transition-all disabled:opacity-50"
                    >
                        <i class="fas fa-camera text-[10px]"></i>
                    </button>
                    <input type="file" ref="avatarInput" accept="image/*" class="hidden" @change="handleAvatarChange">
                </div>

                <div class="flex-1">
                    <div class="flex flex-col gap-1.5">
                        <div class="flex flex-col items-start gap-1">
                            <span class="inline-flex items-center px-2 py-0.5 bg-[#FFE800] text-[#E30613] text-[8px] font-black rounded-full uppercase tracking-widest mb-1">
                                {{ user.role }}
                            </span>
                            <h2 class="text-white font-black text-xl tracking-tight leading-none">{{ user.name }}</h2>
                        </div>
                        <div class="mt-2 space-y-1">
                            <p class="text-white/80 text-xs font-medium flex items-center gap-2">
                                <i class="far fa-envelope opacity-70"></i> {{ user.email }}
                            </p>
                            <p class="text-white/80 text-xs font-medium flex items-center gap-2">
                                <i class="fas fa-phone-alt opacity-70"></i> {{ user.phone || 'Belum diatur' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Views Header Content -->
            <div v-else class="relative z-20 px-6 max-w-xl mx-auto text-center text-white">
                <h1 class="text-2xl font-black mb-1">
                    {{ 
                        activeView === 'edit-info' ? 'Edit Profil' : 
                        activeView === 'edit-password' ? 'Ubah Password' : 
                        activeView === 'edit-address' ? 'Alamat Saya' : 
                        'Hapus Akun' 
                    }}
                </h1>
                <p class="text-xs opacity-80 font-medium">
                    {{ 
                        activeView === 'edit-info' ? 'Perbarui informasi dasar akun Anda' : 
                        activeView === 'edit-password' ? 'Gunakan password yang kuat & aman' : 
                        activeView === 'edit-address' ? 'Kelola alamat penjemputan (Maks. 3)' : 
                        'Pikirkan kembali sebelum menghapus data Anda' 
                    }}
                </p>
            </div>

            <!-- Curved Bottom -->
            <div class="absolute bottom-0 left-0 w-full z-10 leading-none pointer-events-none">
                <svg class="block w-full h-12 sm:h-16 lg:h-20" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="fill-[#FFE800]" d="M0,128L80,144C160,160,320,192,480,197.3C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                </svg>
                <svg class="absolute bottom-0 left-0 w-full h-8 sm:h-10 lg:h-12" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="fill-gray-50" d="M0,64L80,90.7C160,117,320,171,480,186.7C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                </svg>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="bg-gray-50 min-h-screen pb-24 relative">
            <div class="max-w-xl mx-auto px-4 mt-6 relative z-20">

                <!-- SUMMARY VIEW -->
                <template v-if="activeView === 'profile'">
                    <!-- Summary Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex justify-around items-center mb-8">
                        <div class="text-center">
                            <div class="w-10 h-10 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <p class="text-xl font-black text-gray-900">{{ ordersCount }}</p>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Total Pesanan</p>
                        </div>

                        <div class="w-px h-12 bg-gray-100"></div>

                        <div class="text-center">
                            <div class="w-10 h-10 bg-green-50 text-green-500 rounded-xl flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-location-dot"></i>
                            </div>
                            <p class="text-xl font-black text-gray-900">{{ addressesCount }}</p>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Alamat Saya</p>
                        </div>
                    </div>

                    <!-- Menu List Section -->
                    <div class="space-y-8">
                        <!-- Akun Saya -->
                        <div>
                            <h3 class="text-[13px] font-black text-gray-900 uppercase tracking-wider mb-4 px-2">Akun Saya</h3>
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                                <button @click="activeView = 'edit-info'" class="w-full flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors border-b border-gray-50 group">
                                    <div class="w-10 h-10 bg-gray-50 text-gray-400 rounded-xl flex items-center justify-center group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <p class="text-sm font-bold text-gray-800">Edit Profil</p>
                                        <p class="text-[11px] text-gray-400">Ubah nama, email, dan nomor HP</p>
                                    </div>
                                    <i class="fas fa-chevron-right text-xs text-gray-300"></i>
                                </button>
                                <button @click="activeView = 'edit-password'" class="w-full flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors border-b border-gray-50 group">
                                    <div class="w-10 h-10 bg-gray-50 text-gray-400 rounded-xl flex items-center justify-center group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <p class="text-sm font-bold text-gray-800">Ubah Password</p>
                                        <p class="text-[11px] text-gray-400">Perbarui keamanan akun Anda</p>
                                    </div>
                                    <i class="fas fa-chevron-right text-xs text-gray-300"></i>
                                </button>
                                <button @click="activeView = 'edit-address'" class="w-full flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors group">
                                    <div class="w-10 h-10 bg-gray-50 text-gray-400 rounded-xl flex items-center justify-center group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                        <i class="fas fa-map-location-dot"></i>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <p class="text-sm font-bold text-gray-800">Alamat Saya</p>
                                        <p class="text-[11px] text-gray-400">Kelola alamat penjemputan</p>
                                    </div>
                                    <i class="fas fa-chevron-right text-xs text-gray-300"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Bantuan & Lainnya -->
                        <div>
                            <h3 class="text-[13px] font-black text-gray-900 uppercase tracking-wider mb-4 px-2">Bantuan & Lainnya</h3>
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                                <button @click="activeView = 'delete'" class="w-full flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors border-b border-gray-50 group">
                                    <div class="w-10 h-10 bg-red-50 text-red-400 rounded-xl flex items-center justify-center group-hover:bg-red-500 group-hover:text-white transition-colors">
                                        <i class="fas fa-trash-alt"></i>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <p class="text-sm font-bold text-red-600">Hapus Akun</p>
                                        <p class="text-[11px] text-red-400/70">Hapus permanen data akun Anda</p>
                                    </div>
                                    <i class="fas fa-chevron-right text-xs text-gray-300"></i>
                                </button>
                                <Link :href="route('logout')" method="post" as="button" class="w-full flex items-center gap-4 p-4 hover:bg-red-50 transition-colors group">
                                    <div class="w-10 h-10 bg-red-50 text-red-500 rounded-xl flex items-center justify-center group-hover:bg-red-500 group-hover:text-white transition-colors">
                                        <i class="fas fa-sign-out-alt"></i>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <p class="text-sm font-black text-red-600">Keluar</p>
                                        <p class="text-[11px] text-red-400">Logout dari akun Anda</p>
                                    </div>
                                    <i class="fas fa-chevron-right text-xs text-gray-300"></i>
                                </Link>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- EDIT VIEWS -->
                <template v-else>
                    <div class="pt-2">
                        <div v-if="activeView === 'edit-info'">
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                                @back="activeView = 'profile'"
                            />
                        </div>

                        <div v-if="activeView === 'edit-password'">
                            <UpdatePasswordForm @back="activeView = 'profile'" />
                        </div>

                        <div v-if="activeView === 'edit-address'">
                            <UserAddress :addresses="addresses" @back="activeView = 'profile'" />
                        </div>

                        <div v-if="activeView === 'delete'" class="space-y-6">
                            <div class="bg-red-50 p-6 shadow-sm rounded-2xl border border-red-100">
                                <h3 class="font-black text-red-600 mb-6 flex items-center gap-2">
                                    <i class="fas fa-exclamation-triangle"></i> Bahaya
                                </h3>
                                <DeleteUserForm />
                            </div>
                        </div>
                    </div>
                </template>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.bg-primary {
    background-color: #E30613;
}
</style>
