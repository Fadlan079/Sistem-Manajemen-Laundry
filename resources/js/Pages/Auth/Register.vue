<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    password: '',
    password_confirmation: '',
    role: 'pelanggan',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Daftar - Hi Wash Laundry" />

    <div class="flex min-h-screen bg-white lg:bg-gray-100 font-sans relative">
        <!-- Mobile Back Button -->
        <Link
            :href="route('home')"
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
                <h2 class="text-2xl font-black text-white tracking-tight">Buat Akun</h2>
                <p class="text-red-50 text-xs font-medium opacity-90">Lengkapi data diri Anda untuk mendaftar</p>
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
        <!-- Left Side (Branding - matched with Login) -->
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
                    Bergabunglah untuk merasakan efisiensi waktu, kualitas terjamin, dan pengalaman laundry yang lebih transparan.
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

        <!-- Right Side (Form) -->
        <div class="w-full lg:w-1/2 flex items-start lg:items-center justify-center p-6 sm:p-12 overflow-y-auto pb-32 lg:pb-12">
            <div class="w-full max-w-md bg-white lg:rounded-3xl lg:shadow-xl p-0 lg:p-12 lg:border border-gray-100 mt-56 lg:mt-0 relative z-20">

                <h2 class="hidden lg:block text-3xl font-black text-gray-900 tracking-tighter text-center mb-2">Buat Akun</h2>
                <p class="hidden lg:block text-center text-gray-500 text-sm mb-8 lg:mb-10">Lengkapi data diri Anda untuk mendaftar.</p>

                <form id="register-form" @submit.prevent="submit" class="space-y-5">
                    <!-- Nama -->
                    <div>
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-red-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Nama Lengkap"
                                required
                                autofocus
                                autocomplete="name"
                                class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-red-600/20 focus:border-red-600 outline-none transition-all font-medium text-gray-700"
                            />
                        </div>
                        <p v-if="form.errors.name" class="mt-1 text-xs font-medium text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-red-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                            </span>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="Alamat Email"
                                required
                                autocomplete="username"
                                class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-red-600/20 focus:border-red-600 outline-none transition-all font-medium text-gray-700"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1 text-xs font-medium text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <!-- Nomor HP -->
                    <div>
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-red-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </span>
                            <input
                                v-model="form.phone"
                                type="tel"
                                placeholder="Nomor Handphone"
                                required
                                autocomplete="tel"
                                class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-red-600/20 focus:border-red-600 outline-none transition-all font-medium text-gray-700"
                            />
                        </div>
                        <p v-if="form.errors.phone" class="mt-1 text-xs font-medium text-red-500">{{ form.errors.phone }}</p>
                    </div>

                    <!-- Alamat -->
                    <div>
                        <div class="relative group">
                            <span class="absolute top-3.5 left-0 flex items-start pl-4 text-gray-400 group-focus-within:text-red-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </span>
                            <textarea
                                v-model="form.address"
                                placeholder="Alamat Detail"
                                required
                                rows="2"
                                autocomplete="street-address"
                                class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-red-600/20 focus:border-red-600 outline-none transition-all font-medium text-gray-700 resize-none"
                            ></textarea>
                        </div>
                        <p v-if="form.errors.address" class="mt-1 text-xs font-medium text-red-500">{{ form.errors.address }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-red-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </span>
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="Kata Sandi"
                                required
                                autocomplete="new-password"
                                class="w-full pl-12 pr-12 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-red-600/20 focus:border-red-600 outline-none transition-all font-medium text-gray-700"
                            />
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition-colors">
                                <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs font-medium text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <!-- Konfirmasi Password -->
                    <div>
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-red-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </span>
                            <input
                                v-model="form.password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                placeholder="Konfirmasi Kata Sandi"
                                required
                                autocomplete="new-password"
                                class="w-full pl-12 pr-12 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-red-600/20 focus:border-red-600 outline-none transition-all font-medium text-gray-700"
                            />
                            <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition-colors">
                                <svg v-if="showPasswordConfirmation" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="mt-1 text-xs font-medium text-red-500">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <!-- Role (hidden, auto pelanggan) -->
                    <input type="hidden" v-model="form.role" />

                    <!-- Submit Button & Login Link -->
                    <div class="space-y-4 mt-8">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="hidden lg:block w-full bg-[#E30613] hover:bg-red-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-red-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Selesaikan Pendaftaran
                        </button>
                        
                        <p class="text-center text-sm text-gray-500 pt-2">
                            Sudah terdaftar sebelumnya?
                            <Link :href="route('login')" class="text-red-600 font-bold hover:underline ml-1 transition-colors">
                                Masuk Sekarang
                            </Link>
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white z-[100] px-6 pt-5 pb-6 shadow-[0_-15px_40px_-5px_rgba(0,0,0,0.3)] rounded-t-[32px] border-t border-gray-100">
            <div class="max-w-md mx-auto">
                <button
                    form="register-form"
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-[#E30613] hover:bg-red-700 text-white font-bold py-4 rounded-2xl shadow-xl shadow-red-600/30 transition-all active:scale-[0.98] disabled:opacity-50"
                >
                    Selesaikan Pendaftaran
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
input:focus,
textarea:focus {
    outline: none;
}
</style>
