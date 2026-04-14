<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: false
    },
    canRegister: {
        type: Boolean,
        default: false
    },
});

const isMenuOpen = ref(false);
const isProfileDesktopOpen = ref(false);
const isProfileMobileOpen = ref(false);

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
};

const getInitials = (name) => {
    if (!name) return 'U';
    const parts = name.trim().split(' ');
    if (parts.length > 1) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const logout = () => {
    router.post(route('logout'));
};

const activeSection = ref('beranda');

onMounted(() => {
    const sections = ['layanan', 'harga', 'cara-kerja', 'kontak', 'faq'];
    
    // Add default observer for scroll spy
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                activeSection.value = entry.target.id;
            }
        });
    }, {
        rootMargin: '-50% 0px -50% 0px' // Trigger when section is around the middle of the viewport
    });

    // Check if we are at the top to highlight Beranda
    const handleScroll = () => {
        if (window.scrollY < 100) {
            activeSection.value = 'beranda';
        }
    };
    window.addEventListener('scroll', handleScroll);

    sections.forEach(id => {
        const el = document.getElementById(id);
        if (el) observer.observe(el);
    });

    onUnmounted(() => {
        observer.disconnect();
        window.removeEventListener('scroll', handleScroll);
    });
});
</script>

<template>
    <div v-if="isMenuOpen"
         @click="isMenuOpen = false"
         class="fixed inset-0 bg-black/50 z-[60] lg:hidden backdrop-blur-sm">
    </div>

    <nav class="fixed top-0 left-0 right-0 z-50 bg-primary/90 backdrop-blur-md border-b border-white/10">
        <div class="flex justify-between items-center px-6 py-3 max-w-screen-2xl mx-auto text-white">
            <Link :href="route('home')" class="flex items-center opacity-90 hover:opacity-100 transition">
                <img src="logo.png" alt="HiWash Logo" class="h-8 w-8 rounded-full">
            </Link>

            <div class="hidden lg:flex items-center gap-8 text-[13px] font-medium tracking-wide">
                <Link :href="route('home')" @click="activeSection = 'beranda'" :class="[activeSection === 'beranda' ? 'text-secondary' : 'text-white/70 hover:text-white', 'transition-colors']">Beranda</Link>
                <a href="#layanan" @click="activeSection = 'layanan'" :class="[activeSection === 'layanan' ? 'text-secondary' : 'text-white/70 hover:text-white', 'transition-colors']">Layanan</a>
                <a href="#harga" @click="activeSection = 'harga'" :class="[activeSection === 'harga' ? 'text-secondary' : 'text-white/70 hover:text-white', 'transition-colors']">Harga</a>
                <a href="#cara-kerja" @click="activeSection = 'cara-kerja'" :class="[activeSection === 'cara-kerja' ? 'text-secondary' : 'text-white/70 hover:text-white', 'transition-colors']">Cara Kerja</a>
                <a href="#kontak" @click="activeSection = 'kontak'" :class="[activeSection === 'kontak' ? 'text-secondary' : 'text-white/70 hover:text-white', 'transition-colors']">Kontak</a>
                <a href="#faq" @click="activeSection = 'faq'" :class="[activeSection === 'faq' ? 'text-secondary' : 'text-white/70 hover:text-white', 'transition-colors']">FAQ</a>
            </div>

            <div class="flex items-center gap-5 text-[13px] font-medium">
                <button aria-label="Search" class="hidden lg:block text-white/70 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>

                <div class="hidden lg:flex items-center gap-5">
                    <template v-if="canLogin">
                        <div v-if="$page.props.auth.user" class="relative">
                            <button @click="isProfileDesktopOpen = !isProfileDesktopOpen" class="flex items-center justify-center focus:outline-none transition-transform hover:scale-105">
                                <div v-if="$page.props.auth.user.profile_photo_url" class="w-10 h-10 rounded-full overflow-hidden border-2 border-white/20 shadow-lg">
                                    <img :src="$page.props.auth.user.profile_photo_url" alt="Profile" class="w-full h-full object-cover">
                                </div>
                                <div v-else class="w-10 h-10 rounded-full bg-secondary text-primary font-bold flex items-center justify-center border-2 border-white/20 shadow-lg tracking-wider">
                                    {{ getInitials($page.props.auth.user.name) }}
                                </div>
                            </button>

                            <!-- Desktop Dropdown -->
                            <div v-if="isProfileDesktopOpen" class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl py-2 z-50 border border-gray-100 overflow-hidden transform origin-top-right transition-all">
                                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/50">
                                    <p class="text-sm font-bold text-gray-900 truncate">{{ $page.props.auth.user.name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ $page.props.auth.user.email }}</p>
                                    <p class="text-[10px] font-bold text-primary uppercase tracking-widest mt-1">{{ $page.props.auth.user.role }}</p>
                                </div>
                                
                                <div class="py-1">
                                    <Link :href="route('dashboard')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary/5 hover:text-primary transition-colors flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                                        Dashboard
                                    </Link>
                                    <Link :href="route('profile.edit')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary/5 hover:text-primary transition-colors flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        Profil Akun
                                    </Link>
                                    <button @click="logout" class="w-full text-left px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 transition-colors flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        Keluar
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Invisible overlay to close dropdown -->
                            <div v-if="isProfileDesktopOpen" @click="isProfileDesktopOpen = false" class="fixed inset-0 z-40"></div>
                        </div>
                        <template v-else>
                            <Link :href="route('login')" class="text-white/70 hover:text-white transition">Masuk</Link>
                            <Link v-if="canRegister" :href="route('register')"
                                class="px-4 py-1.5 rounded bg-secondary text-primary font-semibold hover:bg-secondary/90 transition-all">
                                Daftar
                            </Link>
                        </template>
                    </template>
                </div>

                <button @click="isMenuOpen = true" class="lg:hidden text-white focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <aside :class="[
        'fixed top-0 right-0 h-full w-64 bg-primary z-[70] shadow-2xl transform transition-transform duration-300 ease-in-out lg:hidden',
        isMenuOpen ? 'translate-x-0' : 'translate-x-full'
    ]">
        <div class="flex flex-col p-6 gap-6">
            <button @click="isMenuOpen = false" class="self-end text-white/70 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <nav class="flex flex-col space-y-1 text-white font-medium">
                <!-- Beranda -->
                <Link :href="route('home')" @click="isMenuOpen = false; activeSection = 'beranda'" 
                    class="relative flex items-center gap-4 px-4 py-3 rounded-xl transition overflow-hidden group"
                    :class="[activeSection === 'beranda' ? 'bg-black/20 text-white font-semibold shadow-inner' : 'text-white/70 hover:bg-black/10 hover:text-white']">
                    <div v-if="activeSection === 'beranda'" class="absolute left-0 top-0 bottom-0 w-1 bg-secondary rounded-r-md"></div>
                    <span class="text-[14px]">Beranda</span>
                </Link>
                
                <!-- Layanan -->
                <a href="#layanan" @click="isMenuOpen = false; activeSection = 'layanan'" 
                    class="relative flex items-center gap-4 px-4 py-3 rounded-xl transition overflow-hidden group"
                    :class="[activeSection === 'layanan' ? 'bg-black/20 text-white font-semibold shadow-inner' : 'text-white/70 hover:bg-black/10 hover:text-white']">
                    <div v-if="activeSection === 'layanan'" class="absolute left-0 top-0 bottom-0 w-1 bg-secondary rounded-r-md"></div>
                    <span class="text-[14px]">Layanan</span>
                </a>
                
                <!-- Harga -->
                <a href="#harga" @click="isMenuOpen = false; activeSection = 'harga'" 
                    class="relative flex items-center gap-4 px-4 py-3 rounded-xl transition overflow-hidden group"
                    :class="[activeSection === 'harga' ? 'bg-black/20 text-white font-semibold shadow-inner' : 'text-white/70 hover:bg-black/10 hover:text-white']">
                    <div v-if="activeSection === 'harga'" class="absolute left-0 top-0 bottom-0 w-1 bg-secondary rounded-r-md"></div>
                    <span class="text-[14px]">Harga</span>
                </a>
                
                <!-- Cara Kerja -->
                <a href="#cara-kerja" @click="isMenuOpen = false; activeSection = 'cara-kerja'" 
                    class="relative flex items-center gap-4 px-4 py-3 rounded-xl transition overflow-hidden group"
                    :class="[activeSection === 'cara-kerja' ? 'bg-black/20 text-white font-semibold shadow-inner' : 'text-white/70 hover:bg-black/10 hover:text-white']">
                    <div v-if="activeSection === 'cara-kerja'" class="absolute left-0 top-0 bottom-0 w-1 bg-secondary rounded-r-md"></div>
                    <span class="text-[14px]">Cara Kerja</span>
                </a>
                
                <!-- Kontak -->
                <a href="#kontak" @click="isMenuOpen = false; activeSection = 'kontak'" 
                    class="relative flex items-center gap-4 px-4 py-3 rounded-xl transition overflow-hidden group"
                    :class="[activeSection === 'kontak' ? 'bg-black/20 text-white font-semibold shadow-inner' : 'text-white/70 hover:bg-black/10 hover:text-white']">
                    <div v-if="activeSection === 'kontak'" class="absolute left-0 top-0 bottom-0 w-1 bg-secondary rounded-r-md"></div>
                    <span class="text-[14px]">Kontak</span>
                </a>
                
                <!-- FAQ -->
                <a href="#faq" @click="isMenuOpen = false; activeSection = 'faq'" 
                    class="relative flex items-center gap-4 px-4 py-3 rounded-xl transition overflow-hidden group"
                    :class="[activeSection === 'faq' ? 'bg-black/20 text-white font-semibold shadow-inner' : 'text-white/70 hover:bg-black/10 hover:text-white']">
                    <div v-if="activeSection === 'faq'" class="absolute left-0 top-0 bottom-0 w-1 bg-secondary rounded-r-md"></div>
                    <span class="text-[14px]">FAQ</span>
                </a>
            </nav>

            <hr class="border-white/10">

            <div class="flex flex-col gap-4">
                <template v-if="canLogin">
                    <div v-if="$page.props.auth.user" class="border border-white/10 rounded-xl overflow-hidden shadow-lg bg-black/20">
                        <button @click="isProfileMobileOpen = !isProfileMobileOpen" class="w-full p-4 flex items-center justify-between text-left hover:bg-white/5 transition-colors">
                            <div class="flex items-center gap-3">
                                <div v-if="$page.props.auth.user.profile_photo_url" class="w-10 h-10 rounded-full overflow-hidden border border-white/20 shadow-md">
                                    <img :src="$page.props.auth.user.profile_photo_url" alt="Profile" class="w-full h-full object-cover">
                                </div>
                                <div v-else class="w-10 h-10 rounded-full bg-secondary text-primary font-bold flex items-center justify-center border border-white/20 shadow-md tracking-wider">
                                    {{ getInitials($page.props.auth.user.name) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white truncate max-w-[140px]">{{ $page.props.auth.user.name }}</p>
                                    <p class="text-[10px] text-white/50 uppercase tracking-widest">{{ $page.props.auth.user.role }}</p>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-white/50 transition-transform duration-300" :class="{'rotate-180': isProfileMobileOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>

                        <div v-if="isProfileMobileOpen" class="bg-black/30 flex flex-col p-2 space-y-1 rounded-b-xl border-t border-white/10">
                            <Link :href="route('dashboard')" @click="isMenuOpen = false" class="py-2.5 px-4 text-sm text-white/80 hover:text-secondary hover:bg-white/5 rounded-lg flex items-center gap-3 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                                Dashboard
                            </Link>
                            <Link :href="route('profile.edit')" @click="isMenuOpen = false" class="py-2.5 px-4 text-sm text-white/80 hover:text-secondary hover:bg-white/5 rounded-lg flex items-center gap-3 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Profil Akun
                            </Link>
                            <button @click="logout(); isMenuOpen = false;" class="text-left py-2.5 px-4 text-sm text-rose-400 hover:bg-rose-500/20 hover:text-rose-300 rounded-lg flex items-center gap-3 transition-colors w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                Keluar Aplikasi
                            </button>
                        </div>
                    </div>
                    <template v-else>
                        <Link :href="route('login')" @click="isMenuOpen = false" class="text-center px-4 py-2 border border-white/20 rounded-lg text-white hover:bg-white/10 transition-colors">Masuk</Link>
                        <Link v-if="canRegister" :href="route('register')" @click="isMenuOpen = false"
                            class="text-center px-4 py-2 rounded-lg bg-secondary text-primary font-bold shadow-lg hover:brightness-110 transition-colors">
                            Daftar Member
                        </Link>
                    </template>
                </template>
            </div>
        </div>
    </aside>
</template>
