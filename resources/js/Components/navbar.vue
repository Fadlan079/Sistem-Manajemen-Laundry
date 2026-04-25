<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

defineProps({
    canLogin: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: false },
    hideBottomNav: { type: Boolean, default: false },
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

// ── Notification State ─────────────────────────────────────
const notifications = ref([]);
const unreadCount = ref(0);
const isNotifOpen = ref(false);
const isNotifLoading = ref(false);

const fetchNotifications = async () => {
    if (!usePage().props.auth.user) return;
    isNotifLoading.value = true;
    try {
        const res = await fetch(route('notifications.latest'));
        const data = await res.json();
        notifications.value = data.notifications;
        unreadCount.value = data.unread_count;
    } catch (e) {
        console.error('Failed to fetch notifications', e);
    } finally {
        isNotifLoading.value = false;
    }
};

const markNotifAsRead = async (notif) => {
    if (!notif.read_at) {
        try {
            await router.patch(route('notifications.read', { id: notif.id }), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    notif.read_at = new Date().toISOString();
                    unreadCount.value = Math.max(0, unreadCount.value - 1);
                }
            });
        } catch (e) {
            console.error('Failed to mark notification as read', e);
        }
    }

    isNotifOpen.value = false;

    if (notif.metadata?.order_id) {
        router.visit(route('pelanggan.aktivitas.detail', { id: notif.metadata.order_id }));
    }
};

const markAllNotifsAsRead = async () => {
    try {
        await router.post(route('notifications.read-all'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                notifications.value.forEach(n => n.read_at = new Date().toISOString());
                unreadCount.value = 0;
            }
        });
    } catch (e) {
        console.error('Failed to mark all as read', e);
    }
};

const getNotifIcon = (type) => {
    switch (type) {
        case 'order': return 'fa-shopping-basket text-blue-500 bg-blue-50';
        case 'payment': return 'fa-credit-card text-emerald-500 bg-emerald-50';
        case 'delivery': return 'fa-truck text-amber-500 bg-amber-50';
        case 'promo': return 'fa-tag text-purple-500 bg-purple-50';
        default: return 'fa-bell text-gray-400 bg-gray-50';
    }
};

const timeAgo = (date) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};

watch(isNotifOpen, (val) => {
    if (val) {
        isProfileDesktopOpen.value = false;
        fetchNotifications();
    }
});

watch(isProfileDesktopOpen, (val) => {
    if (val) isNotifOpen.value = false;
});

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
    if (item.type === 'order') {
        router.visit(route('pelanggan.aktivitas.detail', { id: item.id }));
    } else {
        router.visit(route('pelanggan.pesan', { service: item.id }));
    }
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
    if (usePage().props.auth.user) {
        fetchNotifications();
    }

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
    class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100"
>

    <!-- Loading -->
    <div v-if="isSearchLoading && searchResults.length === 0" class="p-4 space-y-3">
        <div v-for="i in 3" :key="i" class="flex items-center gap-3 animate-pulse">
            <div class="w-10 h-10 bg-gray-200 rounded-xl"></div>
            <div class="flex-1 space-y-2">
                <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                <div class="h-2 bg-gray-100 rounded w-1/2"></div>
            </div>
        </div>
    </div>

    <!-- Empty -->
    <div v-else-if="searchResults.length === 0" class="px-4 py-5 text-center text-gray-400 text-xs">
        Tidak ada hasil
    </div>

    <!-- Results -->
    <ul v-else class="max-h-[360px] overflow-y-auto py-2">
        <li
            v-for="(item, idx) in searchResults"
            :key="item.type + '-' + item.id"
            @click="selectResult(item)"
            :class="[
                'flex items-center gap-3 px-3 py-2.5 mx-2 my-1 rounded-xl transition',
                highlightIndex === idx
                ? 'bg-red-50 ring-1 ring-red-200'
                : 'bg-white active:scale-[0.98]'
            ]"
        >

            <!-- ICON -->
            <div class="w-10 h-10 rounded-xl overflow-hidden shrink-0
                        bg-gradient-to-br from-red-50 to-yellow-50
                        flex items-center justify-center">

                <template v-if="item.type === 'order'">
                    <i class="fas fa-file-invoice text-blue-500 text-sm"></i>
                </template>

                <template v-else>
                    <img v-if="item.image_url" :src="item.image_url" class="w-full h-full object-cover">
                    <i v-else class="fas fa-shirt text-primary/60 text-sm"></i>
                </template>
            </div>

            <!-- TEXT -->
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-gray-800 truncate"
                   v-html="highlightMatch(item.name || item.invoice, searchQueryMobile)">
                </p>

                <p class="text-[10px] text-gray-400 mt-0.5">
                    {{ item.category || item.date }}
                </p>
            </div>

            <!-- PRICE -->
            <div class="text-right">
                <span v-if="item.price" class="text-xs font-bold text-primary">
                    {{ formatPrice(item.price) }}
                </span>
            </div>

        </li>
    </ul>

</div>
                    </transition>
                </Teleport>

                <div v-if="$page.props.auth.user" class="relative">
                    <button
                        @click="isNotifOpen = !isNotifOpen"
                        class="w-10 h-10 flex items-center justify-center bg-white/10 rounded-xl relative active:scale-95 transition-transform"
                        aria-label="Notifications"
                    >
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span v-if="unreadCount > 0" class="absolute top-2 right-2 w-4 h-4 bg-secondary text-primary text-[10px] font-black flex items-center justify-center rounded-full border-2 border-primary">
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </button>

                    <!-- NOTIFICATION DROPDOWN MOBILE -->
                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 translate-y-1"
                    >
                        <div v-if="isNotifOpen" class="fixed inset-x-4 top-16 bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100 z-[60] max-h-[80vh] flex flex-col">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 shrink-0">
                                <h3 class="text-sm font-black text-gray-900 uppercase tracking-tight">Notifikasi</h3>
                                <button @click="markAllNotifsAsRead" class="text-[10px] font-bold text-primary hover:underline">Tandai semua dibaca</button>
                            </div>

                            <div class="overflow-y-auto flex-1">
                                <div v-if="isNotifLoading && notifications.length === 0" class="p-4 space-y-4">
                                    <div v-for="i in 3" :key="i" class="flex gap-3 animate-pulse">
                                        <div class="w-10 h-10 bg-gray-100 rounded-lg shrink-0"></div>
                                        <div class="flex-1 space-y-2">
                                            <div class="h-3 bg-gray-100 rounded w-1/2"></div>
                                            <div class="h-2 bg-gray-50 rounded w-3/4"></div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else-if="notifications.length === 0" class="px-4 py-12 text-center">
                                    <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="fa-solid fa-bell-slash text-gray-300"></i>
                                    </div>
                                    <p class="text-xs font-bold text-gray-400">Belum ada notifikasi</p>
                                </div>
                                <div v-else>
                                    <div
                                        v-for="notif in notifications"
                                        :key="notif.id"
                                        @click="markNotifAsRead(notif)"
                                        class="px-4 py-3 flex gap-3 hover:bg-gray-50 transition border-b border-gray-50 last:border-0"
                                        :class="{'bg-red-50/30': !notif.read_at}"
                                    >
                                        <div :class="['w-10 h-10 rounded-lg flex items-center justify-center shrink-0 text-sm', getNotifIcon(notif.type)]">
                                            <i :class="['fas', getNotifIcon(notif.type).split(' ')[0]]"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex justify-between items-start gap-2">
                                                <p class="text-xs font-black text-gray-900 leading-tight" :class="{'font-extrabold': !notif.read_at}">{{ notif.title }}</p>
                                                <span v-if="!notif.read_at" class="w-2 h-2 bg-primary rounded-full shrink-0 mt-1"></span>
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-0.5 line-clamp-1" :class="{'text-gray-900 font-medium': !notif.read_at}">{{ notif.description }}</p>
                                            <p class="text-[9px] text-gray-400 mt-1 uppercase font-bold tracking-wider">{{ timeAgo(notif.created_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Link :href="route('notifications.index')" @click="isNotifOpen = false" class="block w-full py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest hover:text-primary hover:bg-gray-50 border-t border-gray-100 transition shrink-0 bg-white">
                                Lihat semua notifikasi <i class="fas fa-arrow-right ml-1"></i>
                            </Link>
                        </div>
                    </transition>
                    <div v-if="isNotifOpen" @click="isNotifOpen = false" class="fixed inset-0 z-50"></div>
                </div>
            </div>

            <!-- ── Desktop Top Bar ────────────────────────────────── -->
            <div class="hidden lg:flex justify-between items-center h-12">
                <Link :href="route('home')" class="flex items-center opacity-90 hover:opacity-100 transition">
                    <img src="/logo.png" alt="HiWash Logo" class="h-9 w-9 rounded-full">
                </Link>

                <div class="flex items-center gap-8 text-[13px] font-medium tracking-wide">
                    <Link :href="route('home')" @click="activeSection = 'beranda'" :class="[activeSection === 'beranda' ? 'text-secondary' : 'text-white/70 hover:text-white']">Beranda</Link>
                    <Link :href="route('pelanggan.aktivitas')" @click="activeSection = 'aktivitas'" :class="[activeSection === 'aktivitas' ? 'text-secondary' : 'text-white/70 hover:text-white']">Aktivitas</Link>
                    <Link :href="route('pelanggan.lacak')" @click="activeSection = 'lacak'" :class="[activeSection === 'lacak' ? 'text-secondary' : 'text-white/70 hover:text-white']">Lacak Pesanan</Link>
                    <Link :href="route('pelanggan.daftar-layanan')" @click="activeSection = 'daftar-layanan'" :class="[activeSection === 'daftar-layanan' ? 'text-secondary' : 'text-white/70 hover:text-white']">Pesan Sekarang</Link>
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

<!-- Dropdown Desktop (Improved UI) -->
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
        class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl overflow-hidden z-[9999] border border-gray-100"
        @mousedown.prevent
    >

        <!-- Loading -->
        <div v-if="isSearchLoading && searchResults.length === 0" class="p-4 space-y-3">
            <div v-for="i in 3" :key="i" class="flex items-center gap-3 animate-pulse">
                <div class="w-12 h-12 bg-gray-200 rounded-xl"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-2 bg-gray-100 rounded w-1/2"></div>
                </div>
            </div>
        </div>

        <!-- Empty -->
        <div v-else-if="searchResults.length === 0" class="px-4 py-6 text-center text-gray-400 text-sm">
            Tidak ada hasil ditemukan
        </div>

        <!-- Results -->
        <div v-else class="py-2 max-h-[380px] overflow-y-auto">

            <ul>
                <li
                    v-for="(item, idx) in searchResults"
                    :key="item.type + '-' + item.id"
                    @click="selectResult(item)"
                    :class="[
                        'group flex items-center gap-3 px-3 py-3 mx-2 my-1 rounded-xl cursor-pointer transition-all',
                        highlightIndex === idx
                        ? 'bg-red-50 ring-1 ring-red-200 shadow-sm'
                        : 'bg-white hover:bg-gray-50 hover:shadow'
                    ]"
                >

                    <!-- ICON -->
                    <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0
                                bg-gradient-to-br from-red-50 to-yellow-50
                                flex items-center justify-center border border-gray-100
                                group-hover:scale-105 transition-transform">

                        <template v-if="item.type === 'order'">
                            <i class="fas fa-file-invoice text-blue-500"></i>
                        </template>

                        <template v-else>
                            <img v-if="item.image_url" :src="item.image_url" class="w-full h-full object-cover">
                            <i v-else class="fas fa-shirt text-primary/60"></i>
                        </template>
                    </div>

                    <!-- TEXT -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-800 truncate"
                           v-html="highlightMatch(item.name || item.invoice, searchQuery)">
                        </p>

                        <div class="flex items-center gap-2 mt-1">

                            <span v-if="item.category"
                                class="text-[10px] px-2 py-0.5 bg-gray-100 rounded-md text-gray-500 font-medium"
                                v-html="highlightMatch(item.category, searchQuery)">
                            </span>

                            <span v-if="item.date"
                                class="text-[10px] text-gray-400">
                                {{ item.date }}
                            </span>

                        </div>
                    </div>

                    <!-- RIGHT -->
                    <div class="text-right shrink-0">
                        <template v-if="item.price">
                            <p class="text-sm font-extrabold text-primary">
                                {{ formatPrice(item.price) }}
                            </p>
                        </template>

                        <template v-if="item.status">
                            <span class="text-[10px] px-2 py-0.5 bg-blue-100 text-blue-700 rounded-md font-bold">
                                {{ item.status }}
                            </span>
                        </template>
                    </div>

                    <!-- ARROW -->
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-primary transition shrink-0"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5l7 7-7 7"/>
                    </svg>

                </li>
            </ul>

        </div>
    </div>
</transition>
                    </div>

                    <template v-if="canLogin">
                        <div v-if="$page.props.auth.user" class="relative">
                            <button @click="isProfileDesktopOpen = !isProfileDesktopOpen" class="flex items-center focus:outline-none shrink-0 group">
                                <div v-if="$page.props.auth.user.avatar_url" class="w-10 h-10 rounded-full overflow-hidden border border-white/20 group-hover:border-white transition-colors">
                                    <img :src="$page.props.auth.user.avatar_url" alt="Profile" class="w-full h-full object-cover">
                                </div>
                                <div v-else class="w-10 h-10 rounded-full bg-secondary text-primary font-bold flex items-center justify-center text-sm shadow-lg group-hover:scale-105 transition-transform">
                                    {{ getInitials($page.props.auth.user.name) }}
                                </div>
                            </button>
                            <transition enter-active-class="transition ease-out duration-200" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                <div v-if="isProfileDesktopOpen" class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl py-2 z-50 text-gray-800 border border-gray-100 overflow-hidden">
                                     <Link v-if="$page.props.auth.user.role !== 'pelanggan'" :href="route('dashboard')" class="block px-4 py-2 text-sm font-medium hover:bg-gray-50 hover:text-[#E30613] transition-colors"><i class="fas fa-chart-pie mr-2 text-gray-400"></i> Dashboard</Link>
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

                    <div v-if="$page.props.auth.user" class="relative">
                        <button
                            @click="isNotifOpen = !isNotifOpen"
                            class="w-10 h-10 hidden lg:flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-xl relative active:scale-95 transition-all group"
                            aria-label="Notifications"
                        >
                            <svg class="w-6 h-6 text-white/70 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <span v-if="unreadCount > 0" class="absolute top-2.5 right-2.5 w-3.5 h-3.5 bg-secondary text-primary text-[9px] font-black flex items-center justify-center rounded-full border-2 border-primary group-hover:scale-110 transition-transform">
                                {{ unreadCount > 9 ? '9+' : unreadCount }}
                            </span>
                        </button>

                        <!-- NOTIFICATION DROPDOWN DESKTOP -->
                        <transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 translate-y-1 scale-95"
                            enter-to-class="opacity-100 translate-y-0 scale-100"
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100 translate-y-0 scale-100"
                            leave-to-class="opacity-0 translate-y-1 scale-95"
                        >
                            <div v-if="isNotifOpen" class="absolute right-0 mt-3 w-80 bg-white rounded-2xl shadow-2xl overflow-hidden z-50 border border-gray-100 flex flex-col">
                                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 shrink-0">
                                    <h3 class="text-xs font-black text-gray-900 uppercase tracking-widest">Notifikasi</h3>
                                    <button @click="markAllNotifsAsRead" class="text-[10px] font-black text-primary hover:underline uppercase tracking-tight">Tandai semua dibaca</button>
                                </div>

                                <div class="max-h-[380px] overflow-y-auto">
                                    <div v-if="isNotifLoading && notifications.length === 0" class="p-4 space-y-4">
                                        <div v-for="i in 3" :key="i" class="flex gap-3 animate-pulse">
                                            <div class="w-10 h-10 bg-gray-100 rounded-lg shrink-0"></div>
                                            <div class="flex-1 space-y-2">
                                                <div class="h-3 bg-gray-100 rounded w-1/2"></div>
                                                <div class="h-2 bg-gray-50 rounded w-3/4"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else-if="notifications.length === 0" class="px-4 py-12 text-center">
                                        <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <i class="fa-solid fa-bell-slash text-gray-300"></i>
                                        </div>
                                        <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Belum ada notifikasi</p>
                                    </div>
                                    <div v-else>
                                        <div
                                            v-for="notif in notifications"
                                            :key="notif.id"
                                            @click="markNotifAsRead(notif)"
                                            class="px-4 py-3.5 flex gap-3 hover:bg-gray-50 cursor-pointer transition border-b border-gray-50 last:border-0"
                                            :class="{'bg-red-50/20': !notif.read_at}"
                                        >
                                            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center shrink-0 text-sm shadow-sm border border-black/5', getNotifIcon(notif.type)]">
                                                <i :class="['fas', getNotifIcon(notif.type).split(' ')[0]]"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-start gap-2">
                                                    <p class="text-xs font-black text-gray-900 leading-tight" :class="{'font-extrabold': !notif.read_at}">{{ notif.title }}</p>
                                                    <span v-if="!notif.read_at" class="w-2 h-2 bg-primary rounded-full shrink-0 mt-1 shadow-[0_0_8px_rgba(227,6,19,0.4)]"></span>
                                                </div>
                                                <p class="text-[11px] text-gray-500 mt-0.5 line-clamp-2 leading-relaxed" :class="{'text-gray-900 font-medium': !notif.read_at}">{{ notif.description }}</p>
                                                <p class="text-[9px] text-gray-400 mt-1.5 uppercase font-black tracking-widest">{{ timeAgo(notif.created_at) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <Link :href="route('notifications.index')" @click="isNotifOpen = false" class="block w-full py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] hover:text-primary hover:bg-gray-50 border-t border-gray-100 transition shrink-0 bg-white">
                                    Lihat semua notifikasi <i class="fas fa-arrow-right ml-1 transition-transform group-hover:translate-x-1"></i>
                                </Link>
                            </div>
                        </transition>
                        <div v-if="isNotifOpen" @click="isNotifOpen = false" class="fixed inset-0 z-40"></div>
                    </div>
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

<!-- ── Bottom Nav (Mobile - Clean Version) ──────────────────────────────── -->
<nav
    v-show="!hideBottomNav && !route().current('pelanggan.pesan') && !route().current('pelanggan.aktivitas.detail')"
    :class="[
        'lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 pb-safe shadow-sm transition-transform duration-300',
        isBottomNavVisible ? 'translate-y-0' : 'translate-y-full'
    ]"
>
    <div class="flex justify-between items-center h-16 relative text-gray-500">

        <!-- BERANDA -->
        <Link
            :href="route('home')"
            @click="activeSection = 'beranda'"
            class="flex flex-col items-center justify-center flex-1 gap-1 transition"
            :class="activeSection === 'beranda' ? 'text-red-600' : ''"
        >
            <i class="fas fa-home text-[18px]"></i>
            <span class="text-[10px] font-medium">Beranda</span>
        </Link>

        <!-- AKTIVITAS -->
        <Link
            :href="route('pelanggan.aktivitas')"
            @click="activeSection = 'aktivitas'"
            class="flex flex-col items-center justify-center flex-1 gap-1 transition"
            :class="activeSection === 'aktivitas' ? 'text-red-600' : ''"
        >
            <i class="fas fa-receipt text-[18px]"></i>
            <span class="text-[10px] font-medium">Aktivitas</span>
        </Link>

        <!-- CENTER CTA (PESAN) -->
        <div class="flex-1 flex justify-center relative">
            <Link
                :href="route('pelanggan.daftar-layanan')"
                class="absolute -top-7 flex flex-col items-center"
            >
                <div class="w-14 h-14 bg-red-600 text-white rounded-full
                            flex items-center justify-center shadow-md
                            transition active:scale-95">
                    <i class="fas fa-basket-shopping text-lg"></i>
                </div>
                <span class="text-[10px] mt-1 font-semibold text-gray-700">
                    Pesan
                </span>
            </Link>
        </div>

        <!-- LACAK -->
        <Link
            :href="route('pelanggan.lacak')"
            @click="activeSection = 'lacak'"
            class="flex flex-col items-center justify-center flex-1 gap-1 transition"
            :class="activeSection === 'lacak' ? 'text-red-600' : ''"
        >
            <i class="fa-solid fa-qrcode text-[18px]"></i>
            <span class="text-[10px] font-medium">Lacak</span>
        </Link>

        <!-- PROFIL -->
        <Link
            :href="route('profile.edit')"
            @click="activeSection = 'profil'"
            class="flex flex-col items-center justify-center flex-1 gap-1 transition"
            :class="activeSection === 'profil' ? 'text-red-600' : ''"
        >
            <template v-if="$page.props.auth?.user">
                <img
                    v-if="$page.props.auth.user.avatar_url"
                    :src="$page.props.auth.user.avatar_url"
                    class="w-5 h-5 rounded-full object-cover border border-gray-300"
                >
                <div
                    v-else
                    class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold bg-gray-200 text-gray-700"
                >
                    {{ getInitials($page.props.auth.user.name) }}
                </div>
            </template>

            <i v-else class="fas fa-user text-[18px]"></i>

            <span class="text-[10px] font-medium">Profil</span>
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
