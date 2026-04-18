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
        <div class="pt-20 lg:pt-28 max-w-xl mx-auto pb-12 space-y-6 px-4">

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
            <div class="space-y-6">
                <div v-if="filteredOrders.length === 0" class="text-center py-12 text-gray-500 bg-white rounded-xl border border-dashed border-gray-200">
                    <i class="fas fa-box-open text-3xl text-gray-300 mb-3"></i>
                    <p class="font-bold text-gray-900">Tidak ada pesanan</p>
                </div>

                <div v-for="order in filteredOrders" :key="order.dbId" class="space-y-3 pb-4">
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
                        <div class="flex items-center gap-4 mb-4">
                            <!-- Icon -->
                            <div class="w-14 h-14 bg-[#f4f7f9] flex items-center justify-center rounded-lg border border-gray-100">
                                <i class="fas fa-shopping-basket text-2xl text-[#829ab1]"></i>
                            </div>

                            <div class="flex-1">
                                <h3 class="font-black text-gray-900 text-[15px]">{{ order.service }}</h3>
                                <!-- Different date format based on tab for exact match to mockup -->
                                <p class="text-[12px] font-medium text-gray-500 mt-1">
                                    {{ activeTab === 'aktif' ? order.date : order.shortDate }}
                                </p>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="flex items-center justify-end" :class="activeTab === 'riwayat' ? 'gap-3' : ''">
                            <template v-if="activeTab === 'aktif'">
                                <Link :href="route('pelanggan.aktivitas.detail', order.dbId)"
                                      class="bg-[#E30613] hover:bg-black text-white px-5 py-2.5 rounded-lg text-xs font-bold tracking-wide shadow-sm transition-colors block text-center w-max ml-auto">
                                    Detail Pesanan
                                </Link>
                            </template>

                            <template v-if="activeTab === 'riwayat'">
                                <Link :href="route('pelanggan.aktivitas.ulasan', order.dbId)"
                                      class="flex-1 text-center bg-white border border-gray-200 hover:bg-gray-50 text-[#E30613] px-3 py-2.5 rounded-lg text-xs font-bold tracking-wide transition-colors">
                                    Beri Ulasan
                                </Link>
                                <button class="flex-1 text-center bg-[#E30613] hover:bg-black text-white px-3 py-2.5 rounded-lg text-xs font-bold tracking-wide shadow-sm transition-colors">
                                    Pesan Lagi
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
