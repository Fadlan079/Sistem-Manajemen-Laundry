<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    auth: Object,
    orders: {
        type: Array,
        default: () => []
    }
});

const activeTab = ref('aktif'); // 'aktif' atau 'riwayat'

const filteredOrders = computed(() => {
    return props.orders.filter(order => order.type === activeTab.value);
});

const groupedOrders = computed(() => {
    const groups = {};
    filteredOrders.value.forEach(order => {
        // Extract Month Year from shortDate (e.g., "Senin, 25 April 2026" -> "April 2026")
        const parts = order.shortDate.split(' ');
        const monthYear = parts.slice(-2).join(' ');

        if (!groups[monthYear]) {
            groups[monthYear] = [];
        }
        groups[monthYear].push(order);
    });

    return Object.keys(groups).map(monthYear => ({
        monthYear,
        items: groups[monthYear]
    }));
});

function getBadgeClasses(color) {
    if (color === 'green') return 'bg-green-50 text-green-600 border border-green-100';
    if (color === 'yellow') return 'bg-yellow-50 text-yellow-600 border border-yellow-100';
    return 'bg-blue-50 text-blue-600 border border-blue-100';
}

function getBadgeIcon(color) {
    if (color === 'green') return 'fas fa-check-circle';
    if (color === 'yellow') return 'fas fa-clock'; // Or exclamation
    return 'fas fa-info-circle';
}
</script>

<template>
    <Head title="Aktivitas Pesanan" />

    <AppLayout>
        <!-- Red Header Section -->
        <div class="bg-[#E30613] pt-24 lg:pt-32 pb-24 lg:pb-32 relative overflow-hidden">
            <div class="absolute inset-0 opacity-20 pointer-events-none z-0">
                <div class="absolute top-10 left-10 w-12 h-12 bg-white rounded-full opacity-50"></div>
                <div class="absolute top-20 right-1/4 w-8 h-8 bg-white rounded-full opacity-50"></div>
                <div class="absolute bottom-10 right-10 w-24 h-24 bg-white rounded-full opacity-30"></div>
            </div>

            <div class="relative z-10 max-w-2xl mx-auto px-6 text-center text-white">
                <h1 class="text-3xl font-bold mb-2">Aktivitas Saya</h1>
                <p class="text-sm opacity-90">Pantau perkembangan pesanan laundry Anda</p>
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

        <div class="bg-gray-50 min-h-screen pb-24">
            <div class="max-w-xl mx-auto px-4 mt-6 relative z-20">

                <!-- Segmented Tabs -->
                <div class="flex bg-white rounded-xl shadow-sm border border-gray-100 p-1 mb-8">
                    <button
                        @click="activeTab = 'aktif'"
                        class="flex-1 py-2.5 text-sm font-bold rounded-lg transition-all"
                        :class="activeTab === 'aktif' ? 'bg-[#E30613] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50'">
                        Aktif
                    </button>
                    <button
                        @click="activeTab = 'riwayat'"
                        class="flex-1 py-2.5 text-sm font-bold rounded-lg transition-all"
                        :class="activeTab === 'riwayat' ? 'bg-[#E30613] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50'">
                        Selesai
                    </button>
                </div>

                <!-- List -->
                <div class="space-y-10">
                    <div v-if="groupedOrders.length === 0" class="text-center py-12 text-gray-500 bg-white rounded-xl border border-dashed border-gray-200">
                        <i class="fas fa-box-open text-3xl text-gray-300 mb-3"></i>
                        <p class="font-bold text-gray-900">Tidak ada pesanan</p>
                    </div>

                    <div v-for="group in groupedOrders" :key="group.monthYear" class="space-y-6">
                        <!-- Month Header -->
                        <div class="flex items-center gap-3">
                            <div class="h-px flex-1 bg-gray-100"></div>
                            <span class="text-[11px] font-black text-gray-400 uppercase tracking-[0.2em] px-2 bg-gray-50/50">
                                {{ group.monthYear }}
                            </span>
                            <div class="h-px flex-1 bg-gray-100"></div>
                        </div>

                        <!-- Orders in Month -->
                        <div v-for="order in group.items" :key="order.dbId" class="space-y-3 pb-2">
                            <!-- Invoice & Badge -->
                            <div class="flex flex-col gap-2">
                                <p class="font-black text-gray-900 tracking-tight">{{ order.invoice }}</p>
                                <div class="inline-flex w-max items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                     :class="getBadgeClasses(order.badgeColor)">
                                    <i :class="getBadgeIcon(order.badgeColor)"></i>
                                    {{ order.badgeText }}
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                                <Link :href="route('pelanggan.aktivitas.detail', order.dbId)" class="flex items-center gap-4 mb-4 group w-full text-left">
                                    <!-- Icon -->
                                    <div class="w-14 h-14 bg-[#f4f7f9] flex items-center justify-center rounded-lg border border-gray-100 transition-colors group-hover:bg-[#E30613]/10 overflow-hidden">
                                        <img v-if="order.service_image" :src="order.service_image" alt="" class="w-full h-full object-cover mix-blend-multiply group-hover:mix-blend-normal transition-all" />
                                        <i v-else class="fas fa-shopping-basket text-2xl text-[#829ab1] transition-colors group-hover:text-[#E30613]"></i>
                                    </div>

                                    <div class="flex-1">
                                        <h3 class="font-black text-gray-900 text-[15px] transition-colors group-hover:text-[#E30613]">{{ order.service }}</h3>
                                        <!-- Different date format based on tab for exact match to mockup -->
                                        <p class="text-[12px] font-medium text-gray-500 mt-1">
                                            {{ activeTab === 'aktif' ? order.date : order.shortDate }}
                                        </p>
                                    </div>

                                    <div class="text-gray-300 transition-transform group-hover:text-[#E30613] group-hover:translate-x-1">
                                        <i class="fas fa-chevron-right text-sm"></i>
                                    </div>
                                </Link>

                                <!-- Card Actions -->
                                <div class="flex items-center justify-end" :class="activeTab === 'riwayat' ? 'gap-3' : ''">
                                    <template v-if="activeTab === 'aktif'">
                                        <Link :href="route('pelanggan.aktivitas.detail', order.dbId)"
                                              class="bg-[#E30613] hover:bg-black text-white px-5 py-2.5 rounded-lg text-xs font-bold tracking-wide shadow-sm transition-colors block text-center w-max ml-auto">
                                            Detail Pesanan
                                        </Link>
                                    </template>

                                    <template v-if="activeTab === 'riwayat'">
                                        <Link v-if="!order.is_reviewed"
                                              :href="route('pelanggan.aktivitas.ulasan', order.dbId)"
                                              class="flex-1 text-center bg-white border border-gray-200 hover:bg-gray-50 text-[#E30613] px-3 py-2.5 rounded-lg text-xs font-bold tracking-wide transition-colors">
                                            Beri Ulasan
                                        </Link>
                                        <Link :href="route('pelanggan.pesan') + '?reorder=' + order.dbId"
                                              class="flex-1 text-center bg-[#E30613] hover:bg-black text-white px-3 py-2.5 rounded-lg text-xs font-bold tracking-wide shadow-sm transition-colors">
                                            Pesan Lagi
                                        </Link>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
