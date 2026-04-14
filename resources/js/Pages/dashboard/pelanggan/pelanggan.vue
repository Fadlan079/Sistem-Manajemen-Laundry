<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/dashboard.vue';

const props = defineProps({
    auth: Object,
    // Placeholder data, idealnya dikirim dari Controller
    activeOrder: {
        type: Object,
        default: null
    },
    pendingBill: {
        type: Object,
        default: null
    },
    recentOrders: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({ totalLaundry: 0, poinReward: 0 })
    }
});

function formatRupiah(val) {
    if (!val) return 'Rp 0';
    return 'Rp' + new Intl.NumberFormat('id-ID').format(val);
}
</script>

<template>
    <Head title="Beranda" />

    <DashboardLayout title="Beranda">
        <div class="max-w-4xl mx-auto pb-24 space-y-6 px-4 sm:px-0">
            
            <section class="grid grid-cols-3 gap-3">
                <Link :href="route('pelanggan.aktivitas')" class="group flex flex-col items-center justify-center bg-[#E30613] text-white p-5 rounded-lg shadow hover:bg-[#B7050F] transition-all duration-300">
                    <div class="w-12 h-12 bg-white/10 rounded-md flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                        <i class="fas fa-plus-circle text-xl"></i>
                    </div>
                    <span class="text-[12px] font-bold uppercase tracking-wider text-center">Buat Pesanan</span>
                </Link>
                
                <button class="group flex flex-col items-center justify-center bg-white border border-gray-200 p-5 rounded-lg shadow-sm hover:border-[#E30613]/20 transition-all">
                    <div class="w-12 h-12 bg-gray-50 text-gray-500 rounded-md flex items-center justify-center mb-3 group-hover:text-[#E30613]">
                        <i class="fas fa-history text-lg"></i>
                    </div>
                    <span class="text-[12px] font-bold text-gray-700 uppercase tracking-tight text-center">Pesan Lagi</span>
                </button>

                <button class="group flex flex-col items-center justify-center bg-white border border-gray-200 p-5 rounded-lg shadow-sm hover:border-[#E30613]/20 transition-all">
                    <div class="w-12 h-12 bg-gray-50 text-gray-500 rounded-md flex items-center justify-center mb-3 group-hover:text-[#E30613]">
                        <i class="fas fa-truck-loading text-lg"></i>
                    </div>
                    <span class="text-[12px] font-bold text-gray-700 uppercase tracking-tight text-center">Pickup</span>
                </button>
            </section>

            <section v-if="pendingBill" class="bg-white border border-gray-200 border-l-[6px] border-l-[#EF4444] p-5 rounded-lg shadow-sm flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-50 text-[#EF4444] rounded flex items-center justify-center">
                        <i class="fas fa-receipt text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tagihan Menunggu</p>
                        <p class="text-lg font-extrabold text-gray-900 leading-tight">{{ formatRupiah(pendingBill.total) }}</p>
                    </div>
                </div>
                <Link :href="route('pelanggan.aktivitas')" class="bg-[#1F2937] text-white px-6 py-2.5 rounded text-xs font-bold hover:bg-black transition-colors shadow-sm">
                    Bayar Sekarang
                </Link>
            </section>

            <section v-if="activeOrder" class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white text-[#22C55E] rounded flex items-center justify-center border border-gray-100 shadow-sm">
                            <i class="fas fa-spinner fa-spin text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base leading-tight">{{ activeOrder.service }}</h3>
                            <p class="text-xs text-gray-500 font-medium mt-0.5">{{ activeOrder.id }} <span class="mx-2 text-gray-300">|</span> {{ activeOrder.weight }}</p>
                        </div>
                    </div>
                    <span class="bg-[#22C55E]/10 text-[#22C55E] text-[10px] px-3 py-1.5 rounded font-bold uppercase tracking-wider border border-[#22C55E]/20">
                        {{ activeOrder.status }}
                    </span>
                </div>
                <div class="p-6 space-y-6">
                    <div>
                        <div class="flex justify-between items-end text-sm mb-3">
                            <span class="text-gray-500 font-semibold">Progress Cucian</span>
                            <span class="text-gray-900 font-bold">{{ activeOrder.progress }}%</span>
                        </div>
                        <div class="w-full h-3 bg-gray-100 rounded-sm overflow-hidden border border-gray-200/50">
                            <div class="h-full bg-[#22C55E] transition-all duration-1000 ease-out" 
                                 :style="{ width: activeOrder.progress + '%' }">
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between pt-5 border-t border-gray-100">
                        <div class="flex items-center gap-3 text-gray-500">
                            <i class="far fa-calendar-alt text-gray-400"></i>
                            <span class="text-xs font-semibold uppercase tracking-tight">Selesai Hari Ini: <span class="text-gray-900 font-bold ml-1">{{ activeOrder.eta }}</span></span>
                        </div>
                        <Link :href="route('pelanggan.aktivitas')" class="text-[#E30613] text-sm font-bold flex items-center gap-2 group">
                            Lacak Pesanan <i class="fas fa-chevron-right text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                        </Link>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="bg-white rounded-lg p-5 border border-gray-200 shadow-sm flex items-center justify-around">
                    <div class="text-center">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Total Laundry</p>
                        <p class="text-2xl font-black text-gray-900">{{ stats.totalLaundry }}</p>
                    </div>
                    <div class="w-px h-10 bg-gray-100"></div>
                    <div class="text-center">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Poin Reward</p>
                        <p class="text-2xl font-black text-[#E30613]">{{ stats.poinReward }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="text-[11px] font-bold text-gray-400 uppercase tracking-widest px-2">Layanan Favorit</h4>
                    <div class="flex gap-2.5">
                        <button class="flex-1 bg-white border border-gray-200 p-4 rounded-lg shadow-sm text-left hover:border-[#E30613]/20 transition-all">
                            <p class="text-xs font-bold text-gray-900">Cuci Komplit</p>
                            <p class="text-[10px] text-[#E30613] font-bold mt-1.5 uppercase tracking-wide">Pesan Lagi <i class="fas fa-arrow-right ml-1"></i></p>
                        </button>
                        <button class="flex-1 bg-white border border-gray-200 p-4 rounded-lg shadow-sm text-left hover:border-[#E30613]/20 transition-all">
                            <p class="text-xs font-bold text-gray-900">Setrika Saja</p>
                            <p class="text-[10px] text-[#E30613] font-bold mt-1.5 uppercase tracking-wide">Pesan Lagi <i class="fas fa-arrow-right ml-1"></i></p>
                        </button>
                    </div>
                </div>
            </div>

            <section class="space-y-4">
                <div class="flex items-center justify-between px-2">
                    <h4 class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Aktivitas Terakhir</h4>
                    <Link :href="route('pelanggan.aktivitas')" class="text-xs font-bold text-[#E30613] hover:underline">Lihat Semua</Link>
                </div>
                
                <div v-if="recentOrders.length === 0" class="text-center py-6 text-gray-400 text-sm">
                    Belum ada aktivitas pesanan.
                </div>
                
                <div v-for="order in recentOrders" :key="order.id" class="bg-white border border-gray-100 p-4 rounded-lg flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gray-50 text-gray-400 rounded flex items-center justify-center text-sm border border-gray-100">
                            <i class="fas fa-check-circle" :class="{'text-[#22C55E]': order.status === 'Selesai'}"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-800">{{ order.service }}</p>
                            <p class="text-[11px] text-gray-400 font-medium tracking-tight">{{ order.id }} &bull; {{ order.date }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-bold text-gray-900 bg-gray-100 px-3 py-1.5 rounded uppercase tracking-tighter" :class="{'bg-[#22C55E]/10 text-[#22C55E]': order.status === 'Selesai'}">
                            {{ order.status }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="bg-[#FFE800] p-7 rounded-lg relative overflow-hidden shadow-sm group border border-gray-200/50">
                <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-white/20 rounded-full blur-3xl"></div>
                <div class="relative z-10 flex items-center justify-between">
                    <div class="max-w-[65%]">
                        <div class="inline-block bg-[#E30613] text-white text-[10px] font-black px-3 py-1 rounded mb-3.5 uppercase tracking-widest">
                            Promo Spesial
                        </div>
                        <h3 class="text-[#1F2937] font-black text-2xl mt-2 leading-[1.1] tracking-tight">Diskon 20% Paket Cuci Boneka</h3>
                        <p class="text-gray-700 text-xs mt-3.5 font-semibold uppercase tracking-wider">KODE: <span class="bg-white px-2.5 py-1.5 rounded ml-1 text-[#E30613] border border-[#E30613]/10 font-bold">BERSIHMAX</span></p>
                    </div>
                    <div class="hidden sm:block text-[#E30613]/10 text-8xl absolute right-6 top-1/2 -translate-y-1/2 -rotate-12">
                        <i class="fas fa-tags"></i>
                    </div>
                </div>
            </section>

        </div>
    </DashboardLayout>
</template>