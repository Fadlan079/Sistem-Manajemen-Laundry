<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, nextTick } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    isCollapsed: Boolean
});

const emit = defineEmits(['update:isOpen', 'toggle-collapse']);

const page = usePage();
const user = computed(() => page.props.auth?.user || { role: 'admin' });
const role = computed(() => (user.value.role || 'admin').toLowerCase());

const allLinks = computed(() => ({
    admin: {
        'Monitoring': [
            { name: 'Overview', href: route('admin.dashboard'), active: route().current('admin.dashboard'), icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' },
        ],
        'Manajemen Data': [
            { name: 'Manajemen User', href: route('admin.users'), active: route().current('admin.users'), icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
            { name: 'Layanan Laundry', href: route('admin.services'), active: route().current('admin.services'), icon: 'M3 7h18M5 7l1 12a2 2 0 002 2h8a2 2 0 002-2l1-12M9 7V4a3 3 0 016 0v3' },
        ],
        'Operasional': [
            { name: 'Manajemen Order', href: route('admin.orders'), active: route().current('admin.orders'), icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
            { name: 'Pickup & Delivery', href: route('admin.pickup'), active: route().current('admin.pickup'), icon: 'M3 13h13v-2H3v2zm0 4h9v-2H3v2zm0-8h18V7H3v2zm13 8a2 2 0 100-4 2 2 0 000 4zm4 0a2 2 0 100-4 2 2 0 000 4z' }
        ]
    },
    operator: {
        'Monitoring': [
            {
                name: 'Overview',
                href: route('operator.dashboard'),
                active: route().current('operator.dashboard'),
                icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'
            },
        ],
        'Tugas': [
            {
                name: 'Pesanan',
                href: route('operator.pesanan.masuk'),
                active: route().current('operator.pesanan.masuk'),
                icon: 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'
            },
            {
                name: 'Penjemputan',
                href: route('operator.penjemputan'),
                active: route().current('operator.penjemputan'),
                icon: 'M3 7h13v10H3V7zm13 3h3l2 3v4h-5v-7zM5 17a2 2 0 104 0 2 2 0 00-4 0zm10 0a2 2 0 104 0 2 2 0 00-4 0z'
            },
            {
                name: 'Pengantaran',
                href: route('operator.pengantaran'),
                active: route().current('operator.pengantaran'),
                icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'
            },
            {
                name: 'Pembayaran',
                href: route('operator.pembayaran'),
                active: route().current('operator.pembayaran'),
                icon: 'M2 5a2 2 0 012-2h16a2 2 0 012 2v3H2V5zm0 5h20v7a2 2 0 01-2 2H4a2 2 0 01-2-2v-7zm4 3h4v2H6v-2z'
            },
        ]
    },
    kurir: {
        'Menu': [
            {
                name: 'Tugas Saya',
                href: route('kurir.dashboard'),
                active: route().current('kurir.dashboard') && !page.url.includes('tab=riwayat'),
                icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'
            },
            {
                name: 'Riwayat',
                href: route('kurir.dashboard') + '?tab=riwayat',
                active: page.url.includes('tab=riwayat'),
                icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
            },
        ]
    },
}));

const linksGroup = computed(() => allLinks.value[role.value] || allLinks.value.admin);

onMounted(() => {
    nextTick(() => {
        const activeEles = document.getElementsByClassName('active-nav-item');
        if (activeEles.length > 0) {
            activeEles[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    });
});
</script>

<template>
    <aside
        class="fixed top-0 left-0 h-full bg-primary text-white flex flex-col z-50 transition-all duration-300 ease-in-out lg:translate-x-0 shadow-2xl"
        :class="[
            isOpen ? 'translate-x-0' : '-translate-x-full',
            isCollapsed ? 'w-20' : 'w-64'
        ]"
    >
        <!-- Close Button (Mobile Only) -->
        <button
            @click="emit('update:isOpen', false)"
            class="absolute top-4 right-4 lg:hidden p-2 rounded-lg bg-black/10 hover:bg-black/20 text-white transition-all"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Sidebar Header / Logo (Now Toggle Button) -->
        <button 
            @click="emit('toggle-collapse')"
            class="relative px-4 py-8 border-b border-white/10 flex items-center transition-all duration-300 overflow-hidden group/header outline-none"
            :class="isCollapsed ? 'justify-center px-0' : 'px-8 gap-3'"
        >
            <!-- Logo with Hover Overlay -->
            <div class="relative shrink-0 transition-all duration-300">
                <img
                    src="/logo.png"
                    alt="Logo"
                    class="w-12 h-12 object-cover rounded-full shadow-md shadow-black/20 group-hover/header:opacity-30 transition-opacity"
                    :class="isCollapsed ? 'scale-90' : ''"
                />
                <!-- Hover Icon Overlay -->
                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover/header:opacity-100 transition-opacity">
                    <svg v-if="isCollapsed" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                    </svg>
                    <svg v-else class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </div>
            </div>

            <!-- Brand Info (Hidden on Collapsed) -->
            <div 
                class="flex flex-col text-left transition-all duration-300 whitespace-nowrap"
                :class="isCollapsed ? 'opacity-0 w-0 scale-0' : 'opacity-100 w-auto scale-100'"
            >
                <span class="font-bold text-xl tracking-tight leading-none text-white">HiWash</span>
                <span class="text-[10px] text-white/70 font-semibold tracking-widest uppercase mt-1">{{ role }} Dashboard</span>
            </div>
        </button>

        <!-- Navigation Links -->
        <nav scroll-region class="flex-1 px-3 py-6 space-y-6 overflow-y-auto custom-scrollbar">
            <div v-for="(groupLinks, category) in linksGroup" :key="category">
                <!-- Category Header (Hidden on Collapsed) -->
                <h3 
                    class="px-4 text-[11px] font-bold text-white/40 uppercase tracking-[2px] mb-3 transition-all duration-300 overflow-hidden"
                    :class="isCollapsed ? 'opacity-0 h-0 mb-0' : 'opacity-100 h-auto'"
                >
                    {{ category }}
                </h3>

                <div class="space-y-1">
                    <Link v-for="(link, index) in groupLinks" :key="index" :href="link.href"
                        class="relative flex items-center rounded-xl transition-all duration-300 overflow-hidden group/item h-12"
                        :class="[
                            link.active ? 'active-nav-item bg-black/30 text-white font-semibold shadow-[inset_0_2px_8px_rgba(0,0,0,0.4)]' : 'text-white/70 hover:bg-black/10 hover:text-white',
                            isCollapsed ? 'justify-center px-0' : 'px-4 gap-4'
                        ]"
                    >

                        <div v-if="link.active" class="absolute left-0 top-0 bottom-0 w-1 bg-secondary rounded-r-md"></div>

                        <svg 
                            class="w-5 h-5 opacity-90 group-hover/item:scale-110 group-hover/item:-rotate-12 transition-transform shrink-0" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon"/>
                        </svg>

                        <!-- Label (Hidden on Collapsed) -->
                        <span 
                            class="text-[14px] transition-all duration-300 whitespace-nowrap overflow-hidden"
                            :class="isCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'"
                        >
                            {{ link.name }}
                        </span>
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Bottom Actions -->
        <div 
            class="p-4 space-y-3 border-t border-white/10 bg-black/5 transition-all duration-300"
            :class="isCollapsed ? 'px-2' : 'p-6'"
        >
            <Link
                :href="route('home')"
                class="flex items-center justify-center bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-300 border border-white/10 group/bottom overflow-hidden h-12"
                :class="isCollapsed ? 'w-12 mx-auto' : 'w-full gap-2 px-4 text-sm font-semibold'"
                title="Lihat Landing Page"
            >
                <svg class="w-5 h-5 shrink-0 group-hover/bottom:scale-110 group-hover/bottom:-rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
                <span 
                    class="transition-all duration-300 whitespace-nowrap overflow-hidden"
                    :class="isCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'"
                >
                    Landing Page
                </span>
            </Link>

            <Link 
                v-if="role === 'operator'" 
                :href="route('operator.pesanan.masuk', { action: 'add' })" 
                class="flex items-center justify-center bg-secondary hover:bg-yellow-400 text-gray-900 rounded-xl shadow-lg transition-all duration-300 transform active:scale-95 group/btn overflow-hidden h-12"
                :class="isCollapsed ? 'w-12 mx-auto' : 'w-full gap-2 px-4 text-sm font-bold'"
                title="Buat Pesanan"
            >
                <svg class="w-5 h-5 shrink-0 group-hover/btn:scale-110 group-hover/btn:-rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span 
                    class="transition-all duration-300 whitespace-nowrap overflow-hidden"
                    :class="isCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'"
                >
                    Buat Pesanan
                </span>
            </Link>
        </div>
    </aside>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 2px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
</style>
