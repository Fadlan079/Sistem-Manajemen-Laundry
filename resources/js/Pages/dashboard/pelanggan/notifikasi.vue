<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';

const props = defineProps({
    notifications: Object,
    filters: Object,
});

const getNotifIcon = (type) => {
    switch (type) {
        case 'order': return 'fa-shopping-basket text-blue-500 bg-blue-50 border-blue-100';
        case 'payment': return 'fa-credit-card text-emerald-500 bg-emerald-50 border-emerald-100';
        case 'delivery': return 'fa-truck text-amber-500 bg-amber-50 border-amber-100';
        case 'promo': return 'fa-tag text-purple-500 bg-purple-50 border-purple-100';
        case 'system': return 'fa-exclamation-triangle text-red-500 bg-red-50 border-red-100';
        default: return 'fa-bell text-gray-400 bg-gray-50 border-gray-100';
    }
};

const filterTypes = [
    { label: 'Semua', value: 'semua' },
    { label: 'Pesanan', value: 'order' },
    { label: 'Pembayaran', value: 'payment' },
    { label: 'Promo', value: 'promo' },
];

const setFilter = (type) => {
    router.get(route('notifications.index'), { type }, { preserveState: true, preserveScroll: true });
};

const handleNotifClick = (notif) => {
    if (notif.id === 'unverified-email') {
        router.visit(route('profile.edit'));
        return;
    }

    if (!notif.read_at) {
        router.patch(route('notifications.read', { id: notif.id }), {}, { preserveScroll: true });
    }
    
    if (notif.metadata?.order_id) {
        router.visit(route('pelanggan.aktivitas.detail', { id: notif.metadata.order_id }));
    } else if (notif.type === 'promo') {
        router.visit(route('pelanggan.daftar-layanan'));
    }
};

const markAllAsRead = () => {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Notifikasi" />

    <AppLayout>
        <!-- Red Header Section -->
        <div class="bg-[#E30613] pt-24 lg:pt-32 pb-24 lg:pb-32 relative overflow-hidden">
            <div class="absolute inset-0 opacity-20 pointer-events-none z-0">
                <div class="absolute top-10 left-10 w-12 h-12 bg-white rounded-full opacity-50"></div>
                <div class="absolute top-20 right-1/4 w-8 h-8 bg-white rounded-full opacity-50"></div>
                <div class="absolute bottom-10 right-10 w-24 h-24 bg-white rounded-full opacity-30"></div>
            </div>

            <div class="relative z-10 max-w-2xl mx-auto px-6 text-center text-white">
                <h1 class="text-3xl font-bold mb-2">Notifikasi</h1>
                <p class="text-sm opacity-90">Pantau semua aktivitas dan pembaruan layanan Anda</p>
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

        <div class="bg-gray-50 min-h-screen pb-32">
            <div class="max-w-2xl mx-auto px-4 mt-8 relative z-20">
                
                <!-- Action Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <!-- Filters -->
                    <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-gray-200 shadow-sm overflow-x-auto no-scrollbar">
                        <button 
                            v-for="filter in filterTypes" 
                            :key="filter.value"
                            @click="setFilter(filter.value)"
                            :class="[
                                'px-4 py-1.5 rounded-lg text-[11px] font-black uppercase tracking-widest transition-all whitespace-nowrap',
                                filters.type === filter.value 
                                ? 'bg-primary text-white shadow-md' 
                                : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'
                            ]"
                        >
                            {{ filter.label }}
                        </button>
                    </div>

                    <button 
                        @click="markAllAsRead"
                        class="text-[10px] font-black text-primary hover:text-red-700 uppercase tracking-widest flex items-center gap-2 group transition-colors self-end sm:self-center"
                    >
                        <i class="fas fa-check-double group-hover:scale-110 transition-transform"></i>
                        Tandai semua dibaca
                    </button>
                </div>

                <!-- Notifications List -->
                <div v-if="notifications.data.length === 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-12 text-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100 shadow-inner">
                        <i class="fa-solid fa-bell-slash text-3xl text-gray-200"></i>
                    </div>
                    <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest mb-1">Belum ada notifikasi</h3>
                    <p class="text-xs text-gray-400">Kami akan memberi tahu Anda jika ada aktivitas terbaru.</p>
                </div>

                <div v-else class="space-y-10">
                    <div v-for="group in notifications.data" :key="group.label" class="space-y-4">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] px-2 flex items-center gap-3">
                            {{ group.label }}
                            <span class="h-px flex-1 bg-gray-100"></span>
                        </h4>

                        <div class="space-y-3">
                            <div 
                                v-for="notif in group.items" 
                                :key="notif.id"
                                @click="handleNotifClick(notif)"
                                :class="[
                                    'group bg-white rounded-2xl border transition-all duration-300 p-4 sm:p-5 flex gap-4 sm:gap-6 cursor-pointer relative overflow-hidden',
                                    !notif.read_at 
                                    ? 'border-primary/20 shadow-md ring-1 ring-primary/5' 
                                    : 'border-gray-100 hover:border-gray-200 shadow-sm hover:shadow-md'
                                ]"
                            >
                                <!-- Unread Indicator bar -->
                                <div v-if="!notif.read_at" class="absolute left-0 top-0 bottom-0 w-1 bg-primary"></div>

                                <!-- Icon -->
                                <div :class="['w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center shrink-0 text-lg border shadow-sm transition-transform group-hover:scale-105', getNotifIcon(notif.type)]">
                                    <i :class="['fas', getNotifIcon(notif.type).split(' ')[0]]"></i>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start gap-3 mb-1">
                                        <h3 class="text-sm font-black text-gray-900 leading-tight" :class="{'text-primary': !notif.read_at}">
                                            {{ notif.title }}
                                        </h3>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter shrink-0">
                                            {{ formatTime(notif.created_at) }}
                                        </span>
                                    </div>
                                    <p class="text-xs sm:text-[13px] text-gray-500 leading-relaxed mb-3" :class="{'text-gray-800 font-medium': !notif.read_at}">
                                        {{ notif.description }}
                                    </p>
                                    
                                    <!-- Metadata / Actions -->
                                    <div class="flex items-center gap-4">
                                        <span v-if="notif.metadata?.order_id" class="text-[9px] font-black bg-gray-100 text-gray-500 px-2 py-0.5 rounded uppercase tracking-widest">
                                            ORD #{{ notif.metadata.order_id }}
                                        </span>
                                        <div class="flex-1"></div>
                                        <i class="fas fa-chevron-right text-gray-300 group-hover:text-primary transition-colors text-[10px]"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Load More / Pagination placeholder -->
                <div v-if="notifications.next_page_url" class="mt-12 text-center">
                    <button class="px-8 py-3 bg-white border border-gray-200 rounded-xl text-[11px] font-black uppercase tracking-widest text-gray-500 hover:text-primary hover:border-primary transition-all shadow-sm">
                        Muat Lebih Banyak
                    </button>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
