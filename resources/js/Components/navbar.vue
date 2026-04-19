<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    canLogin: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: false },
});

const isProfileDesktopOpen = ref(false);
const activeSection = ref('');
const isBottomNavVisible = ref(true);
let lastScrollY = 0;

// ── Search State ─────────────────────────────────────────────
const searchQuery = ref('');
const searchQueryMobile = ref('');
const searchResults = ref([]);
const isSearchOpen = ref(false);
const isSearchOpenMobile = ref(false);
const isSearchLoading = ref(false);
const highlightIndex = ref(-1);
const mobileSearchContainer = ref(null);
const mobileDropdownStyle = ref({});

let debounceTimer = null;

const updateMobileDropdownPosition = () => {
    if (!mobileSearchContainer.value) return;
    const rect = mobileSearchContainer.value.getBoundingClientRect();
    mobileDropdownStyle.value = {
        position: 'fixed',
        top: (rect.bottom + 8) + 'px',
        left: rect.left + 'px',
        width: rect.width + 'px',
        zIndex: 99999,
    };
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price);
};

const highlightMatch = (text, query) => {
    if (!query || !text) return text;
    const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return text.replace(regex, '<mark class="search-highlight">$1</mark>');
};

const doSearch = async (q) => {
    const trimmed = q.trim();
    if (trimmed.length < 1) {
        searchResults.value = [];
        isSearchOpen.value = false;
        isSearchOpenMobile.value = false;
        isSearchLoading.value = false;
        return;
    }
    isSearchLoading.value = true;
    try {
        const res = await fetch(`/search/services?q=${encodeURIComponent(trimmed)}`);
        searchResults.value = await res.json();
        isSearchOpen.value = true;
        isSearchOpenMobile.value = true;
        updateMobileDropdownPosition();
    } catch (e) {
        searchResults.value = [];
    } finally {
        isSearchLoading.value = false;
        highlightIndex.value = -1;
    }
};

watch(searchQuery, (val) => {
    clearTimeout(debounceTimer);
    if (!val.trim()) {
        searchResults.value = [];
        isSearchOpen.value = false;
        return;
    }
    debounceTimer = setTimeout(() => doSearch(val), 300);
});

watch(searchQueryMobile, (val) => {
    clearTimeout(debounceTimer);
    if (!val.trim()) {
        searchResults.value = [];
        isSearchOpenMobile.value = false;
        return;
    }
    debounceTimer = setTimeout(() => doSearch(val), 300);
});

const selectResult = (item) => {
    searchQuery.value = '';
    searchQueryMobile.value = '';
    searchResults.value = [];
    isSearchOpen.value = false;
    isSearchOpenMobile.value = false;
    router.visit(route('pelanggan.pesan', { service: item.id }));
};

const closeSearch = () => {
    isSearchOpen.value = false;
    isSearchOpenMobile.value = false;
};

const handleKeydown = (e) => {
    if (!isSearchOpen.value && !isSearchOpenMobile.value) return;
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightIndex.value = Math.min(highlightIndex.value + 1, searchResults.value.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightIndex.value = Math.max(highlightIndex.value - 1, -1);
    } else if (e.key === 'Enter' && highlightIndex.value >= 0) {
        e.preventDefault();
        selectResult(searchResults.value[highlightIndex.value]);
    } else if (e.key === 'Escape') {
        closeSearch();
    }
};

// ── Navbar Active Section ─────────────────────────────────────
const updateActiveSection = () => {
    if (route().current('home')) {
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

    const unregisterNavigate = router.on('navigate', () => {
        updateActiveSection();
        closeSearch();
    });

    const sections = ['layanan', 'cara-kerja', 'kontak', 'faq'];
    const observer = new IntersectionObserver((entries) => {
        if (!route().current('home')) return;
        entries.forEach(entry => {
            if (entry.isIntersecting) activeSection.value = entry.target.id;
        });
    }, { rootMargin: '-50% 0px -50% 0px' });

    const handleScroll = () => {
        const currentScrollY = window.scrollY;
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
    window.addEventListener('keydown', handleKeydown);
    sections.forEach(id => {
        const el = document.getElementById(id);
        if (el) observer.observe(el);
    });

    onUnmounted(() => {
        observer.disconnect();
        window.removeEventListener('scroll', handleScroll);
        window.removeEventListener('keydown', handleKeydown);
        clearTimeout(debounceTimer);
        unregisterNavigate();
    });
});
</script>

<template>
    <nav class="fixed top-0 left-0 right-0 z-50 bg-primary/95 backdrop-blur-md border-b border-white/10 shadow-lg px-4 py-2 lg:px-6 lg:py-3">
        <div class="max-w-screen-2xl mx-auto text-white">

            <!-- ── Mobile Top Bar ─────────────────────────────────── -->
            <div class="flex lg:hidden items-center gap-3 h-10">
                <!-- SCAN BUTTON -->
                <Link :href="route('pelanggan.lacak')" class="w-10 h-10 flex items-center justify-center bg-white/10 rounded-xl active:scale-95 transition-transform" aria-label="Scan">
                    <i class="fa-solid fa-qrcode text-white text-xl"></i>
                </Link>

                <!-- SEARCH INPUT MOBILE -->
                <div class="flex-1 relative" ref="mobileSearchContainer">
                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none z-10">
                        <svg v-if="!isSearchLoading" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4 text-primary animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                    <input
                        v-model="searchQueryMobile"
                        type="text"
                        placeholder="Cari layanan laundry..."
                        autocomplete="off"
                        @focus="() => { updateMobileDropdownPosition(); searchResults.length > 0 && (isSearchOpenMobile = true); }"
                        @input="updateMobileDropdownPosition"
                        class="w-full h-10 pl-9 pr-4 bg-white text-gray-900 text-xs font-medium rounded-xl border-none focus:ring-2 focus:ring-secondary placeholder:text-gray-400 shadow-inner outline-none"
                    >
                </div>

                <!-- Dropdown Mobile — di-Teleport ke body agar bebas dari stacking context nav -->
                <Teleport to="body">
                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 translate-y-1"
                    >
                        <div
                            v-if="isSearchOpenMobile && (searchResults.length > 0 || isSearchLoading)"
                            :style="mobileDropdownStyle"
                            class="search-dropdown-teleport bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100"
                        >
                            <!-- Loading skeleton -->
                            <div v-if="isSearchLoading && searchResults.length === 0" class="px-4 py-3 space-y-3">
                                <div v-for="i in 3" :key="i" class="flex items-center gap-3 animate-pulse">
                                    <div class="w-9 h-9 bg-gray-200 rounded-xl shrink-0"></div>
                                    <div class="flex-1 space-y-1.5">
                                        <div class="h-3 bg-gray-200 rounded-full w-3/4"></div>
                                        <div class="h-2.5 bg-gray-100 rounded-full w-1/2"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Results -->
                            <template v-else>
                                <div class="px-3 py-2 border-b border-gray-50 flex items-center gap-1.5">
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="text-[10px] text-gray-400 font-medium">{{ searchResults.length }} layanan ditemukan</span>
                                </div>
                                <ul>
                                    <li
                                        v-for="(item, idx) in searchResults"
                                        :key="item.id"
                                        @click="selectResult(item)"
                                        :class="['flex items-center gap-3 px-3 py-2.5 cursor-pointer transition-colors border-b border-gray-50 last:border-0', highlightIndex === idx ? 'bg-red-50' : 'hover:bg-gray-50']"
                                    >
                                        <!-- Icon / Image -->
                                        <div class="w-9 h-9 rounded-xl overflow-hidden shrink-0 bg-gradient-to-br from-red-50 to-yellow-50 flex items-center justify-center border border-gray-100">
                                            <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="w-full h-full object-cover">
                                            <i v-else class="fas fa-shirt text-primary/60 text-sm"></i>
                                        </div>
                                        <!-- Text -->
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-gray-800 truncate" v-html="highlightMatch(item.name, searchQueryMobile)"></p>
                                            <p class="text-[10px] text-gray-400 truncate flex items-center gap-1 mt-0.5">
                                                <span class="inline-block px-1.5 py-0.5 bg-primary/10 text-primary rounded text-[9px] font-bold uppercase tracking-wide" v-html="highlightMatch(item.category, searchQueryMobile)"></span>
                                            </p>
                                        </div>
                                        <!-- Price -->
                                        <div class="text-right shrink-0">
                                            <span class="text-xs font-bold text-primary">{{ formatPrice(item.price) }}</span>
                                            <span v-if="item.unit" class="block text-[10px] text-gray-400">/{{ item.unit }}</span>
                                        </div>
                                        <!-- Arrow -->
                                        <svg class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </li>
                                </ul>
                                <div class="px-3 py-2 bg-gray-50 border-t border-gray-100">
                                    <button @click="router.visit(route('pelanggan.pesan'))" class="text-[10px] text-primary font-semibold hover:underline flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                                        Lihat semua layanan
                                    </button>
                                </div>
                            </template>
                        </div>
                    </transition>
                </Teleport>

                <!-- NOTIFICATION -->
                <button class="w-10 h-10 flex items-center justify-center bg-white/10 rounded-xl relative active:scale-95 transition-transform" aria-label="Notifications">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="absolute top-2.5 right-3 w-2 h-2 bg-rose-500 rounded-full border-2 border-primary"></span>
                </button>
            </div>

            <!-- ── Desktop Top Bar ────────────────────────────────── -->
            <div class="hidden lg:flex justify-between items-center h-12">
                <Link :href="route('home')" class="flex items-center opacity-90 hover:opacity-100 transition">
                    <img src="/logo.png" alt="HiWash Logo" class="h-9 w-9 rounded-full">
                </Link>

                <div class="flex items-center gap-8 text-[13px] font-medium tracking-wide">
                    <Link :href="route('home')" @click="activeSection = 'beranda'" :class="[activeSection === 'beranda' ? 'text-secondary' : 'text-white/70 hover:text-white']">Beranda</Link>
                    <Link :href="route('pelanggan.aktivitas')" @click="activeSection = 'aktivitas'" :class="[activeSection === 'aktivitas' ? 'text-secondary' : 'text-white/70 hover:text-white']">Aktivitas</Link>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Search Input (Desktop) -->
                    <div class="relative w-72 mr-2" @focusout="(e) => { if (!e.currentTarget.contains(e.relatedTarget)) isSearchOpen = false }" tabindex="-1" style="outline:none">
                        <!-- Input wrapper -->
                        <div :class="['relative transition-all duration-200', isSearchOpen && searchResults.length > 0 ? 'ring-2 ring-secondary rounded-xl' : '']">
                            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none z-10">
                                <svg v-if="!isSearchLoading" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 text-primary animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari layanan laundry..."
                                autocomplete="off"
                                @focus="searchResults.length > 0 && (isSearchOpen = true)"
                                class="w-full h-10 pl-9 pr-8 bg-white text-gray-900 text-xs font-medium rounded-xl border-none focus:ring-2 focus:ring-secondary placeholder:text-gray-400 shadow-inner outline-none transition-all"
                            >
                            <!-- Clear button -->
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''; isSearchOpen = false"
                                class="absolute inset-y-0 right-2.5 flex items-center text-gray-400 hover:text-gray-600"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <!-- Dropdown Desktop -->
                        <transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 translate-y-1 scale-95"
                            enter-to-class="opacity-100 translate-y-0 scale-100"
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100 translate-y-0 scale-100"
                            leave-to-class="opacity-0 translate-y-1 scale-95"
                        >
                            <div
                                v-if="isSearchOpen && (searchResults.length > 0 || isSearchLoading)"
                                class="search-dropdown absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl overflow-hidden z-[9999] border border-gray-100"
                                @mousedown.prevent
                            >
                                <!-- Loading skeleton -->
                                <div v-if="isSearchLoading && searchResults.length === 0" class="px-4 py-3 space-y-3">
                                    <div v-for="i in 3" :key="i" class="flex items-center gap-3 animate-pulse">
                                        <div class="w-10 h-10 bg-gray-200 rounded-xl shrink-0"></div>
                                        <div class="flex-1 space-y-2">
                                            <div class="h-3 bg-gray-200 rounded-full w-3/4"></div>
                                            <div class="h-2.5 bg-gray-100 rounded-full w-1/2"></div>
                                        </div>
                                        <div class="w-16 h-4 bg-gray-200 rounded-full"></div>
                                    </div>
                                </div>

                                <!-- Results -->
                                <template v-else>
                                    <!-- Header -->
                                    <div class="px-4 pt-3 pb-2 flex items-center justify-between">
                                        <span class="text-[11px] text-gray-400 font-medium uppercase tracking-wide">Rekomendasi Layanan</span>
                                        <span class="text-[10px] text-primary font-bold">{{ searchResults.length }} hasil</span>
                                    </div>
                                    <ul class="pb-1">
                                        <li
                                            v-for="(item, idx) in searchResults"
                                            :key="item.id"
                                            @click="selectResult(item)"
                                            :class="['group flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors border-b border-gray-50 last:border-0', highlightIndex === idx ? 'bg-red-50' : 'hover:bg-gray-50']"
                                        >
                                            <!-- Icon / Image -->
                                            <div class="w-10 h-10 rounded-xl overflow-hidden shrink-0 bg-gradient-to-br from-red-50 to-yellow-50 flex items-center justify-center border border-gray-100 group-hover:border-primary/20 transition-colors">
                                                <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="w-full h-full object-cover">
                                                <i v-else class="fas fa-shirt text-primary/60"></i>
                                            </div>
                                            <!-- Text -->
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-primary transition-colors" v-html="highlightMatch(item.name, searchQuery)"></p>
                                                <p class="text-xs text-gray-400 flex items-center gap-1.5 mt-0.5">
                                                    <span class="inline-block px-2 py-0.5 bg-primary/10 text-primary rounded-md text-[10px] font-bold uppercase tracking-wide" v-html="highlightMatch(item.category, searchQuery)"></span>
                                                </p>
                                            </div>
                                            <!-- Price -->
                                            <div class="text-right shrink-0">
                                                <span class="text-sm font-bold text-primary">{{ formatPrice(item.price) }}</span>
                                                <span v-if="item.unit" class="block text-[10px] text-gray-400 mt-0.5">/{{ item.unit }}</span>
                                            </div>
                                            <!-- Arrow chevron -->
                                            <svg class="w-4 h-4 text-gray-300 group-hover:text-primary transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </li>
                                    </ul>
                                    <!-- Footer -->
                                    <div class="px-4 py-2.5 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                                        <span class="text-[10px] text-gray-400">Tekan <kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded text-[9px] font-mono shadow-sm">↑↓</kbd> untuk navigasi, <kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded text-[9px] font-mono shadow-sm">Enter</kbd> untuk pilih</span>
                                        <button @click="router.visit(route('pelanggan.pesan'))" class="text-[11px] text-primary font-semibold hover:underline flex items-center gap-1">
                                            Semua layanan <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </transition>
                    </div>

                    <template v-if="canLogin">
                        <div v-if="$page.props.auth.user" class="relative">
                            <button @click="isProfileDesktopOpen = !isProfileDesktopOpen" class="flex items-center focus:outline-none shrink-0 group">
                                <div v-if="$page.props.auth.user.profile_photo_url" class="w-10 h-10 rounded-full overflow-hidden border border-white/20 group-hover:border-white transition-colors">
                                    <img :src="$page.props.auth.user.profile_photo_url" alt="Profile" class="w-full h-full object-cover">
                                </div>
                                <div v-else class="w-10 h-10 rounded-full bg-secondary text-primary font-bold flex items-center justify-center text-sm shadow-lg group-hover:scale-105 transition-transform">
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
                            <Link :href="route('login')" class="text-white/70 text-xs lg:text-sm hover:text-white transition-colors px-2">Masuk</Link>
                            <Link v-if="canRegister" :href="route('register')" class="bg-secondary text-primary px-5 py-2 rounded-xl text-sm font-bold hover:brightness-110 active:scale-95 transition-all shadow-lg">Daftar</Link>
                        </template>
                    </template>

                    <!-- Notification Button (Desktop) -->
                    <button class="w-10 h-10 hidden lg:flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-xl relative active:scale-95 transition-all group" aria-label="Notifications">
                        <svg class="w-6 h-6 text-white/70 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span class="absolute top-2.5 right-3 w-2 h-2 bg-secondary rounded-full border-2 border-primary animate-pulse"></span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Overlay to close search when clicking outside (mobile) -->
    <div
        v-if="isSearchOpenMobile && searchResults.length > 0"
        @click="isSearchOpenMobile = false"
        class="lg:hidden fixed inset-0 z-[9998] bg-black/20 backdrop-blur-[1px]"
    ></div>

    <!-- ── Bottom Nav (Mobile) ──────────────────────────────── -->
    <nav :class="[
        'lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-primary/95 backdrop-blur-lg border-t border-white/5 pb-safe shadow-[0_-4px_25px_rgba(0,0,0,0.4)] transition-transform duration-500 ease-in-out',
        isBottomNavVisible ? 'translate-y-0' : 'translate-y-full'
    ]">
        <div class="flex justify-between items-center h-16 relative">

            <!-- BERANDA -->
            <Link :href="route('home')" @click="activeSection = 'beranda'"
                class="relative h-full flex flex-col items-center justify-center gap-1 flex-1 transition-all duration-300 overflow-hidden"
                :class="activeSection === 'beranda' ? 'text-secondary' : 'text-white/40'">
                <div class="absolute top-0 left-0 right-0 h-1 bg-secondary transition-all duration-300 origin-center"
                     :class="activeSection === 'beranda' ? 'scale-x-100 opacity-100' : 'scale-x-0 opacity-0'"></div>
                <div class="absolute inset-0 bg-gradient-to-b from-secondary/10 to-transparent transition-opacity duration-300"
                     :class="activeSection === 'beranda' ? 'opacity-100' : 'opacity-0'"></div>
                <i class="fas fa-home text-[17px]"></i>
                <span class="text-[9px] font-bold uppercase tracking-tighter">Beranda</span>
            </Link>

            <!-- AKTIVITAS -->
            <Link :href="route('pelanggan.aktivitas')" @click="activeSection = 'aktivitas'"
                class="relative h-full flex flex-col items-center justify-center gap-1 flex-1 transition-all duration-300 overflow-hidden"
                :class="activeSection === 'aktivitas' ? 'text-secondary' : 'text-white/40'">
                <div class="absolute top-0 left-0 right-0 h-1 bg-secondary transition-all duration-300 origin-center"
                     :class="activeSection === 'aktivitas' ? 'scale-x-100 opacity-100' : 'scale-x-0 opacity-0'"></div>
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
                <div class="absolute top-0 left-0 right-0 h-1 bg-secondary transition-all duration-300 origin-center"
                     :class="activeSection === 'kontak' ? 'scale-x-100 opacity-100' : 'scale-x-0 opacity-0'"></div>
                <div class="absolute inset-0 bg-gradient-to-b from-secondary/10 to-transparent transition-opacity duration-300"
                     :class="activeSection === 'kontak' ? 'opacity-100' : 'opacity-0'"></div>
                <i class="fas fa-headset text-[17px]"></i>
                <span class="text-[9px] font-bold uppercase tracking-tighter">Bantuan</span>
            </a>

            <!-- PROFIL -->
            <Link :href="route('profile.edit')" @click="activeSection = 'profil'"
                class="relative h-full flex flex-col items-center justify-center gap-1 flex-1 transition-all duration-300 overflow-hidden"
                :class="activeSection === 'profil' ? 'text-secondary' : 'text-white/40'">
                <div class="absolute top-0 left-0 right-0 h-1 bg-secondary transition-all duration-300 origin-center"
                     :class="activeSection === 'profil' ? 'scale-x-100 opacity-100' : 'scale-x-0 opacity-0'"></div>
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

/* Search highlight */
:deep(.search-highlight) {
    background: #ffe8001a;
    color: #E30613;
    font-weight: 700;
    border-radius: 2px;
    padding: 0 1px;
}

/* Scrollable dropdown if many results */
.search-dropdown {
    max-height: 380px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #f3f4f6 transparent;
}

.search-dropdown-teleport {
    max-height: 360px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #f3f4f6 transparent;
}

.search-dropdown::-webkit-scrollbar,
.search-dropdown-teleport::-webkit-scrollbar {
    width: 4px;
}
.search-dropdown::-webkit-scrollbar-track,
.search-dropdown-teleport::-webkit-scrollbar-track {
    background: transparent;
}
.search-dropdown::-webkit-scrollbar-thumb,
.search-dropdown-teleport::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 99px;
}
</style>
