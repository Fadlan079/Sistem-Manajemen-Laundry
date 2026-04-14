<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/dashboard.vue';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    auth: Object,
    orders: {
        type: Array,
        default: () => []
    },
    dateFilter: {
        type: String,
        default: 'semua'
    }
});

// 1. State
const activeTab = ref('aktif');
const searchQuery = ref('');
const selectedDate = ref(props.dateFilter);

// 2. Stepper config – harus cocok dengan nilai status yang dikirim controller
const orderSteps = ['Diterima', 'Dicuci', 'Dikeringkan', 'Selesai', 'Diantar'];

function getStepProgress(currentStatus) {
    const index = orderSteps.indexOf(currentStatus);
    return index >= 0 ? index : 0;
}

// 3. Watch date filter -> kirim request ke server
watch(selectedDate, (val) => {
    router.get(
        route('pelanggan.aktivitas'),
        { date: val },
        { preserveState: true, preserveScroll: true, replace: true }
    );
});

// 4. Client-side filter: tab + search (date sudah difilter di server)
const filteredOrders = computed(() => {
    return props.orders.filter(order => {
        if (order.type !== activeTab.value) return false;

        if (searchQuery.value) {
            const q = searchQuery.value.toLowerCase();
            return order.id.toLowerCase().includes(q) ||
                   order.service.toLowerCase().includes(q);
        }

        return true;
    });
});

function formatRupiah(val) {
    if (!val) return 'Rp0';
    return 'Rp' + new Intl.NumberFormat('id-ID').format(val);
}
</script>

<template>
    <Head title="Aktivitas Pesanan" />

    <DashboardLayout title="Aktivitas Pesanan">
        <div class="max-w-3xl mx-auto pb-12 space-y-6">
            
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-gray-900">Aktivitas Pesanan</h1>
                    <p class="text-sm text-gray-500 mt-1">Pantau status cucian dan riwayat transaksi Anda.</p>
                </div>

                <div class="flex border-b border-gray-200 mb-6">
                    <button 
                        @click="activeTab = 'aktif'" 
                        class="px-5 py-3 font-bold text-sm border-b-2 transition-colors outline-none uppercase tracking-tight" 
                        :class="activeTab === 'aktif' ? 'border-[#E30613] text-[#E30613]' : 'border-transparent text-gray-500 hover:text-gray-700'">
                        Pesanan Aktif
                    </button>
                    <button 
                        @click="activeTab = 'riwayat'" 
                        class="px-5 py-3 font-bold text-sm border-b-2 transition-colors outline-none uppercase tracking-tight" 
                        :class="activeTab === 'riwayat' ? 'border-[#E30613] text-[#E30613]' : 'border-transparent text-gray-500 hover:text-gray-700'">
                        Riwayat Selesai
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 mb-6">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-search text-sm"></i>
                        </div>
                        <input 
                            type="text" 
                            v-model="searchQuery" 
                            placeholder="Cari ID Pesanan..." 
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] transition-all outline-none"
                        >
                    </div>
                    <select 
                        v-model="selectedDate" 
                        class="w-full sm:w-48 px-4 py-2.5 border border-gray-200 rounded text-sm bg-white focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none cursor-pointer font-medium text-gray-700">
                        <option value="semua">Semua Waktu</option>
                        <option value="hari_ini">Hari Ini</option>
                        <option value="minggu_ini">Minggu Ini</option>
                        <option value="bulan_ini">Bulan Ini</option>
                    </select>
                </div>

                <div class="flex flex-col">
                    <div v-if="filteredOrders.length === 0" class="text-center py-12 text-gray-500 bg-gray-50 rounded border border-dashed border-gray-200">
                        <i class="fas fa-box-open text-3xl text-gray-300 mb-3"></i>
                        <p class="font-bold text-gray-900">Tidak ada pesanan</p>
                    </div>

                    <div v-for="order in filteredOrders" :key="order.id" class="flex flex-col py-6 border-b border-gray-100 last:border-0">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded bg-green-50 text-[#22C55E] flex items-center justify-center shrink-0 border border-green-100">
                                <i class="fas fa-tshirt text-lg"></i>
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-[10px] font-black text-[#E30613] mb-0.5 uppercase tracking-widest">{{ order.id }}</p>
                                        <h3 class="font-bold text-gray-900 text-base leading-tight">
                                            {{ order.service }} <span class="text-gray-400 font-medium text-sm ml-1">/ {{ order.items }}</span>
                                        </h3>
                                    </div>
                                    <span class="font-black text-gray-900">{{ formatRupiah(order.price) }}</span>
                                </div>
                                <p class="text-gray-500 text-xs font-semibold mt-1 uppercase tracking-tighter">{{ order.date }}</p>
                            </div>
                        </div>

                        <div v-if="activeTab === 'aktif'" class="mt-6 pl-16 pr-4">
                            <div class="relative flex items-center justify-between w-full">
                                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-gray-100 rounded-full z-0"></div>
                                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-[#22C55E] rounded-full z-0 transition-all duration-700 ease-in-out" 
                                     :style="`width: ${(getStepProgress(order.status) / (orderSteps.length - 1)) * 100}%`"></div>

                                <div v-for="(step, index) in orderSteps" :key="index" class="relative z-10 flex flex-col items-center">
                                    <div class="w-3 h-3 rounded-full border-2 bg-white flex items-center justify-center transition-colors duration-500"
                                         :class="getStepProgress(order.status) >= index ? 'border-[#22C55E]' : 'border-gray-200'">
                                         <div v-if="getStepProgress(order.status) >= index" class="w-1.5 h-1.5 bg-[#22C55E] rounded-full"></div>
                                    </div>
                                    <span class="absolute top-5 text-[9px] font-bold uppercase tracking-tighter whitespace-nowrap"
                                          :class="getStepProgress(order.status) >= index ? 'text-[#22C55E]' : 'text-gray-300'">
                                        {{ step }}
                                    </span>
                                </div>
                            </div>
                            <div class="h-8"></div>
                        </div>

                        <div class="flex items-center gap-5 mt-4" :class="{'pl-16' : activeTab === 'aktif'}">
                            <Link v-if="activeTab === 'aktif'" :href="route('pelanggan.aktivitas.detail', order.dbId)" class="text-[#E30613] text-xs font-bold uppercase tracking-wider flex items-center hover:opacity-80 transition-opacity">
                                Detail Pesanan 
                                <i class="fas fa-arrow-right ml-2 text-[10px]"></i>
                            </Link>
                            
                            <template v-if="activeTab === 'riwayat'">
                                <Link :href="route('pelanggan.aktivitas.ulasan', order.dbId)" class="text-[#E30613] text-xs font-bold uppercase tracking-wider flex items-center hover:opacity-80 transition-opacity">
                                    Beri Ulasan 
                                    <i class="fas fa-star ml-2 text-[10px]"></i>
                                </Link>
                                <button class="text-gray-700 text-xs font-bold uppercase tracking-wider flex items-center hover:text-[#E30613] transition-colors">
                                    Pesan lagi 
                                    <i class="fas fa-redo ml-2 text-[10px]"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </DashboardLayout>
</template>