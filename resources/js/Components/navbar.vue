<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    canLogin: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: false },
});

const isProfileDesktopOpen = ref(false);
const activeSection = ref('');
const isBottomNavVisible = ref(true);
let lastScrollY = 0;

const updateActiveSection = () => {
    if (route().current('home')) {
        // If on home, default to beranda unless a section is set by scroll/observer
        if (window.scrollY < 100) activeSection.value = 'beranda';
    } else if (route().current('pelanggan.aktivitas*')) {
        activeSection.value = 'aktivitas';
    } else if (route().current('profile.edit')) {
        activeSection.value = 'profil';
    } else if (route().current('pelanggan.lacak*')) {
        activeSection.value = 'lacak';
    } else {
        activeSection.value = '';
    }
};

const logout = () => {
    router.post(route('logout'));
};

const getInitials = (name) => {
    if (!name) return 'U';
    const parts = name.trim().split(' ');
    return parts.length > 1 ? (parts[0][0] + parts[1][0]).toUpperCase() : name.substring(0, 2).toUpperCase();
};

onMounted(() => {
    updateActiveSection();
    
    // Listen for Inertia navigation to update active section
    const unregisterNavigate = router.on('navigate', () => {
        updateActiveSection();
    });

    const sections = ['layanan', 'cara-kerja', 'kontak', 'faq'];
    const observer = new IntersectionObserver((entries) => {
        // Only trigger observer logic if we are on the home page
        if (!route().current('home')) return;

        entries.forEach(entry => {
            if (entry.isIntersecting) activeSection.value = entry.target.id;
        });
    }, { rootMargin: '-50% 0px -50% 0px' });

    const handleScroll = () => {
        const currentScrollY = window.scrollY;

        // Hide/Show bottom nav based on scroll direction
        if (currentScrollY > lastScrollY && currentScrollY > 100) {
            isBottomNavVisible.value = false;
        } else {
            isBottomNavVisible.value = true;
        }
        lastScrollY = currentScrollY;

        if (route().current('home') && window.scrollY < 100) {
            activeSection.value = 'beranda';
        }
    };
    window.addEventListener('scroll', handleScroll, { passive: true });
    sections.forEach(id => {
        const el = document.getElementById(id);
        if (el) observer.observe(el);
    });

    onUnmounted(() => {
        observer.disconnect();
        window.removeEventListener('scroll', handleScroll);
        unregisterNavigate();
    });
});
</script>

<template>
    <nav class="fixed top-0 left-0 right-0 z-50 bg-primary/95 backdrop-blur-md border-b border-white/10 shadow-lg px-4 py-2 lg:px-6 lg:py-3">
        <div class="max-w-screen-2xl mx-auto text-white">
            <!-- ── Mobile Top Bar (Scan, Search, Notif) ──────────────── -->
            <div class="flex lg:hidden items-center gap-3 h-10">
                <!-- SCAN BUTTON -->
                <Link :href="route('pelanggan.lacak')" class="w-10 h-10 flex items-center justify-center bg-white/10 rounded-xl active:scale-95 transition-transform" aria-label="Scan">
                    <i class="fa-solid fa-qrcode text-white text-xl"></i>
                </Link>

                <!-- SEARCH INPUT -->
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input
                        type="text"
                        placeholder="Cari layanan laundry..."
                        class="w-full h-10 pl-9 pr-4 bg-white text-gray-900 text-xs font-medium rounded-xl border-none focus:ring-2 focus:ring-secondary placeholder:text-gray-400 shadow-inner"
                    >
                </div>

                <!-- NOTIFICATION -->
                <button class="w-10 h-10 flex items-center justify-center bg-white/10 rounded-xl relative active:scale-95 transition-transform" aria-label="Notifications">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <!-- Dot indicator -->
                    <span class="absolute top-2.5 right-3 w-2 h-2 bg-rose-500 rounded-full border-2 border-primary"></span>
                </button>
            </div>

            <!-- ── Desktop Top Bar (Logo, Links, Profile) ────────────── -->
            <div class="hidden lg:flex justify-between items-center h-12">
                <Link :href="route('home')" class="flex items-center opacity-90 hover:opacity-100 transition">
                    <img src="/logo.png" alt="HiWash Logo" class="h-9 w-9 rounded-full">
                </Link>

                <div class="flex items-center gap-8 text-[13px] font-medium tracking-wide">
                    <Link :href="route('home')" @click="activeSection = 'beranda'" :class="[activeSection === 'beranda' ? 'text-secondary' : 'text-white/70 hover:text-white']">Beranda</Link>
                    <Link :href="route('pelanggan.aktivitas')" @click="activeSection = 'aktivitas'" :class="[activeSection === 'aktivitas' ? 'text-secondary' : 'text-white/70 hover:text-white']">Aktivitas</Link>
                </div>

                <div class="flex items-center gap-4">
                    <template v-if="canLogin">
                        <div v-if="$page.props.auth.user" class="relative">
                            <button @click="isProfileDesktopOpen = !isProfileDesktopOpen" class="flex items-center focus:outline-none shrink-0">
                                <div v-if="$page.props.auth.user.profile_photo_url" class="w-10 h-10 rounded-full overflow-hidden border border-white/20">
                                    <img :src="$page.props.auth.user.profile_photo_url" alt="Profile" class="w-full h-full object-cover">
                                </div>
                                <div v-else class="w-10 h-10 rounded-full bg-secondary text-primary font-bold flex items-center justify-center text-sm">
                                    {{ getInitials($page.props.auth.user.name) }}
                                </div>
                            </button>
                            <transition enter-active-class="transition ease-out duration-200" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                <div v-if="isProfileDesktopOpen" class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl py-2 z-50 text-gray-800 border border-gray-100 overflow-hidden">
                                     <Link :href="route('dashboard')" class="block px-4 py-2 text-sm font-medium hover:bg-gray-50 hover:text-[#E30613] transition-colors"><i class="fas fa-chart-pie mr-2 text-gray-400"></i> Dashboard</Link>
                                     <Link :href="route('profile.edit')" class="block px-4 py-2 text-sm font-medium hover:bg-gray-50 hover:text-[#E30613] transition-colors"><i class="fas fa-user-circle mr-2 text-gray-400"></i> Akun Saya</Link>
                                     <div class="border-t border-gray-100 my-1"></div>
                                     <button @click="logout" class="w-full text-left px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 transition-colors"><i class="fas fa-sign-out-alt mr-2"></i> Keluar</button>
                                </div>
                            </transition>
                            <div v-if="isProfileDesktopOpen" @click="isProfileDesktopOpen = false" class="fixed inset-0 z-40"></div>
                        </div>
                        <template v-else>
                            <Link :href="route('login')" class="text-white/70 text-xs lg:text-sm hover:text-white transition-colors">Masuk</Link>
                            <Link v-if="canRegister" :href="route('register')" class="bg-secondary text-primary px-5 py-2 rounded-xl text-sm font-bold hover:brightness-110 active:scale-95 transition-all shadow-lg">Daftar</Link>
                        </template>
                    </template>
                </div>
            </div>
        </div>
    </nav>

    <nav :class="[
        'lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-primary/95 backdrop-blur-lg border-t border-white/5 pb-safe shadow-[0_-4px_25px_rgba(0,0,0,0.4)] transition-transform duration-500 ease-in-out',
        isBottomNavVisible ? 'translate-y-0' : 'translate-y-full'
    ]">
        <div class="flex justify-between items-center h-16 relative">

            <!-- BERANDA -->
            <Link :href="route('home')" @click="activeSection = 'beranda'"
                class="relative h-full flex flex-col items-center justify-center gap-1 flex-1 transition-all duration-300 overflow-hidden"
                :class="activeSection === 'beranda' ? 'text-secondary' : 'text-white/40'">
                <!-- Active Indicator Line -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-secondary transition-all duration-300 origin-center"
                     :class="activeSection === 'beranda' ? 'scale-x-100 opacity-100' : 'scale-x-0 opacity-0'"></div>
                <!-- Active Glow -->
                <div class="absolute inset-0 bg-gradient-to-b from-secondary/10 to-transparent transition-opacity duration-300"
                     :class="activeSection === 'beranda' ? 'opacity-100' : 'opacity-0'"></div>

                <i class="fas fa-home text-[17px]"></i>
                <span class="text-[9px] font-bold uppercase tracking-tighter">Beranda</span>
            </Link>

            <!-- AKTIVITAS -->
            <Link :href="route('pelanggan.aktivitas')" @click="activeSection = 'aktivitas'"
                class="relative h-full flex flex-col items-center justify-center gap-1 flex-1 transition-all duration-300 overflow-hidden"
                :class="activeSection === 'aktivitas' ? 'text-secondary' : 'text-white/40'">
                <!-- Active Indicator Line -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-secondary transition-all duration-300 origin-center"
                     :class="activeSection === 'aktivitas' ? 'scale-x-100 opacity-100' : 'scale-x-0 opacity-0'"></div>
                <!-- Active Glow -->
                <div class="absolute inset-0 bg-gradient-to-b from-secondary/10 to-transparent transition-opacity duration-300"
                     :class="activeSection === 'aktivitas' ? 'opacity-100' : 'opacity-0'"></div>

                <i class="fas fa-receipt text-[17px]"></i>
                <span class="text-[9px] font-bold uppercase tracking-tighter">Aktivitas</span>
            </Link>

            <!-- ACTION BUTTON (CENTER) -->
            <div class="flex-1 flex justify-center relative z-10">
                <Link :href="route('pelanggan.pesan')" class="absolute -top-6 w-14 h-14 bg-secondary text-primary rounded-full shadow-[0_8px_20px_rgba(255,232,0,0.4)] flex items-center justify-center hover:scale-105 active:scale-95 transition-all border-4 border-[#8B0000]">
                    <i class="fas fa-plus text-2xl"></i>
                </Link>
            </div>

            <!-- BANTUAN/KONTAK -->
            <a href="/#kontak" @click="activeSection = 'kontak'"
                class="relative h-full flex flex-col items-center justify-center gap-1 flex-1 transition-all duration-300 overflow-hidden"
                :class="activeSection === 'kontak' ? 'text-secondary' : 'text-white/40'">
                <!-- Active Indicator Line -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-secondary transition-all duration-300 origin-center"
                     :class="activeSection === 'kontak' ? 'scale-x-100 opacity-100' : 'scale-x-0 opacity-0'"></div>
                <!-- Active Glow -->
                <div class="absolute inset-0 bg-gradient-to-b from-secondary/10 to-transparent transition-opacity duration-300"
                     :class="activeSection === 'kontak' ? 'opacity-100' : 'opacity-0'"></div>

                <i class="fas fa-headset text-[17px]"></i>
                <span class="text-[9px] font-bold uppercase tracking-tighter">Bantuan</span>
            </a>

            <!-- PROFIL -->
            <Link :href="route('profile.edit')" @click="activeSection = 'profil'"
                class="relative h-full flex flex-col items-center justify-center gap-1 flex-1 transition-all duration-300 overflow-hidden"
                :class="activeSection === 'profil' ? 'text-secondary' : 'text-white/40'">
                <!-- Active Indicator Line -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-secondary transition-all duration-300 origin-center"
                     :class="activeSection === 'profil' ? 'scale-x-100 opacity-100' : 'scale-x-0 opacity-0'"></div>
                <!-- Active Glow -->
                <div class="absolute inset-0 bg-gradient-to-b from-secondary/10 to-transparent transition-opacity duration-300"
                     :class="activeSection === 'profil' ? 'opacity-100' : 'opacity-0'"></div>

                <template v-if="$page.props.auth?.user">
                    <img v-if="$page.props.auth.user.avatar" :src="$page.props.auth.user.avatar" alt="Profile" class="w-5 h-5 rounded-full object-cover border" :class="activeSection === 'profil' ? 'border-secondary' : 'border-white/20'">
                    <div v-else class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-black uppercase shadow-sm transition-colors"
                         :class="activeSection === 'profil' ? 'bg-secondary text-primary' : 'bg-white/20 text-white'">
                        {{ getInitials($page.props.auth.user.name) }}
                    </div>
                </template>
                <i v-else class="fas fa-user-circle text-[17px]"></i>
                <span class="text-[9px] font-bold uppercase tracking-tighter">Profil</span>
            </Link>

        </div>
    </nav>
</template>

<style scoped>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
