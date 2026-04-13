<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import { 
    Chart as ChartJS, Title, Tooltip, Legend, LineElement, LinearScale, PointElement, CategoryScale, Filler, ArcElement, BarElement
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, LinearScale, PointElement, CategoryScale, Filler, ArcElement, BarElement);

const user = computed(() => usePage().props.auth.user);

// State for collapsible chart
const isChartExpanded = ref(true);

onMounted(() => {
    const savedState = localStorage.getItem('manajemen-order-chart-expanded');
    if (savedState !== null) {
        isChartExpanded.value = savedState === 'true';
    }
});

watch(isChartExpanded, (newValue) => {
    localStorage.setItem('manajemen-order-chart-expanded', newValue.toString());
});

// Dummy Data Order
const orders = ref([
    { id: '#ORD-1001', customer: 'Andi Wijaya', service: 'Cuci Komplit', status: 'Baru', date: '13 Apr 2026', total: 'Rp 45.000' },
    { id: '#ORD-1002', customer: 'Siti Aminah', service: 'Setrika Saja', status: 'Diproses', date: '13 Apr 2026', total: 'Rp 20.000' },
    { id: '#ORD-1003', customer: 'Budi Santoso', service: 'Cuci Kering', status: 'Selesai', date: '12 Apr 2026', total: 'Rp 35.000' },
    { id: '#ORD-1004', customer: 'Rina Kartika', service: 'Cuci Komplit', status: 'Menuggu Pembayaran', date: '12 Apr 2026', total: 'Rp 55.000' },
    { id: '#ORD-1005', customer: 'Fadlan Rizqi', service: 'Cuci Sepatu', status: 'Batal', date: '11 Apr 2026', total: 'Rp 100.000' },
]);

// Statistik Order
const orderStats = [
    { label: 'Total Pesanan', value: '1,284', icon: 'fa-shopping-bag', color: 'text-blue-600', bg: 'bg-blue-500/10' },
    { label: 'Pesanan Selesai', value: '856', icon: 'fa-check-circle', color: 'text-emerald-600', bg: 'bg-emerald-500/10' },
    { label: 'Sedang Diproses', value: '342', icon: 'fa-spinner', color: 'text-amber-600', bg: 'bg-amber-500/10' },
    { label: 'Dibatalkan', value: '86', icon: 'fa-times-circle', color: 'text-rose-600', bg: 'bg-rose-500/10' },
];

/** CHART CONFIGURATIONS **/

// 1. Status Pesanan (Doughnut)
const chartStatusData = {
    labels: ['Selesai', 'Diproses', 'Baru', 'Batal'],
    datasets: [{
        data: [65, 20, 10, 5],
        backgroundColor: ['#059669', '#d97706', '#3b82f6', '#e11d48'], 
        borderWidth: 0,
        hoverOffset: 4
    }]
};

const doughnutOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
        legend: { position: 'right', labels: { font: { family: 'Inter', size: 10, weight: 'bold' }, color: '#888' } },
        tooltip: { backgroundColor: '#0A0A0B', titleFont: { family: 'Space Mono', size: 10 }, bodyFont: { family: 'Inter', size: 12, weight: 'bold' }, padding: 12, cornerRadius: 4, displayColors: true }
    },
    cutout: '70%'
};

// 2. Trend Pesanan Mingguan (Line)
const chartTrendData = {
    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
    datasets: [
        {
            label: 'Pesanan Masuk',
            data: [45, 52, 38, 65, 80, 110, 95],
            borderColor: '#5B4CF3', backgroundColor: 'rgba(91, 76, 243, 0.1)', fill: true, tension: 0.4,
            pointBackgroundColor: '#fff', pointBorderColor: '#5B4CF3', pointBorderWidth: 2, pointHoverRadius: 6,
        }
    ]
};

const lineOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0A0A0B', titleFont: { family: 'Space Mono', size: 10 }, bodyFont: { family: 'Inter', size: 12, weight: 'bold' }, padding: 12, cornerRadius: 4, displayColors: false } },
    scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } },
        x: { grid: { display: false }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } }
    }
};

// 3. Metode Pembayaran (Doughnut)
const chartPaymentData = {
    labels: ['Transfer Bank', 'Tunai', 'E-Wallet', 'QRIS'],
    datasets: [{
        data: [40, 25, 15, 20],
        backgroundColor: ['#5B4CF3', '#059669', '#0891b2', '#8b5cf6'], 
        borderWidth: 0,
        hoverOffset: 4
    }]
};

// 4. Layanan Terpopuler (Bar - Stacked)
const chartServiceData = {
    labels: ['M1', 'M2', 'M3', 'M4'],
    datasets: [
        { label: 'Cuci Komplit', data: [120, 135, 150, 180], backgroundColor: '#3b82f6', borderRadius: 4 },
        { label: 'Setrika', data: [80, 90, 85, 100], backgroundColor: '#d97706', borderRadius: 4 },
        { label: 'Cuci Kering', data: [50, 60, 55, 70], backgroundColor: '#8b5cf6', borderRadius: 4 },
    ]
};

const barOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 10, weight: 'bold' }, color: '#888' } }, tooltip: { backgroundColor: '#0A0A0B', titleFont: { family: 'Space Mono', size: 10 }, bodyFont: { family: 'Inter', size: 12, weight: 'bold' }, padding: 12, cornerRadius: 4 } },
    scales: {
        x: { stacked: true, grid: { display: false }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } },
        y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } }
    }
};

const getStatusClass = (status) => {
    switch (status) {
        case 'Selesai': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'Diproses': return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'Menuggu Pembayaran': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'Baru': return 'bg-indigo-100 text-indigo-700 border-indigo-200';
        case 'Batal': return 'bg-rose-100 text-rose-700 border-rose-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};

const getStatusDot = (status) => {
    switch (status) {
        case 'Selesai': return 'bg-emerald-500';
        case 'Diproses': return 'bg-amber-500';
        case 'Menuggu Pembayaran': return 'bg-blue-500';
        case 'Baru': return 'bg-indigo-500';
        case 'Batal': return 'bg-rose-500';
        default: return 'bg-slate-300';
    }
};
</script>

<template>
    <Head title="Manajemen Order - Hi Wash" />

    <DashboardLayout title="Manajemen Order">
        <div class="space-y-10 pb-20">
            <!-- Header Section -->
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <h1 class="text-4xl font-black tracking-tighter text-text uppercase">
                        Manajemen <span class="text-muted font-medium">Order</span>
                    </h1>
                    <p class="text-muted font-medium italic">Kelola semua transaksi dan pantau status pesanan pelanggan.</p>
                </div>
                
                <button class="bg-primary hover:bg-primary-hover text-white px-8 py-4 rounded-sm font-black uppercase tracking-widest text-xs shadow-lg transition-all transform hover:-translate-y-1 flex items-center gap-3">
                    <i class="fa-solid fa-plus"></i>
                    Buat Pesanan Baru
                </button>
            </header>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="(stat, index) in orderStats" :key="index" 
                    class="bg-surface border border-border p-6 rounded-sm shadow-sm relative group hover:border-primary/50 transition-all overflow-hidden">
                    <div class="absolute -right-2 -bottom-2 opacity-5 group-hover:opacity-10 transition-opacity">
                        <i :class="['fa-solid', stat.icon]" class="text-6xl"></i>
                    </div>
                    
                    <div class="flex items-center gap-4 mb-4">
                        <div :class="[stat.bg, stat.color]" class="w-10 h-10 rounded-full flex items-center justify-center text-sm">
                            <i :class="['fa-solid', stat.icon]"></i>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted">{{ stat.label }}</span>
                    </div>

                    <h3 class="text-3xl font-black text-text tracking-tighter">{{ stat.value }}</h3>
                </div>
            </div>

            <!-- Chart Section (Collapsible) -->
            <div class="bg-surface border border-border rounded-sm shadow-sm overflow-hidden">
                <button 
                    @click="isChartExpanded = !isChartExpanded"
                    class="w-full flex items-center justify-between px-6 py-4 hover:bg-container/50 transition-colors group"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs">
                            <i class="fa-solid fa-chart-pie"></i>
                        </div>
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-text">Visual Analitik - Tren Order</h3>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-[9px] font-mono text-muted uppercase tracking-widest italic">Live Trend Analysis</span>
                        </div>
                        <i :class="['fa-solid transition-transform duration-300', isChartExpanded ? 'fa-chevron-up' : 'fa-chevron-down text-primary']"></i>
                    </div>
                </button>

                <transition 
                    enter-active-class="transition-all duration-500 ease-out"
                    leave-active-class="transition-all duration-300 ease-in"
                    enter-from-class="max-h-0 opacity-0"
                    enter-to-class="max-h-[2000px] opacity-100"
                    leave-from-class="max-h-[2000px] opacity-100"
                    leave-to-class="max-h-0 opacity-0"
                >
                    <div v-show="isChartExpanded" class="p-6 border-t border-dashed border-border overflow-hidden">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-12">
                            <!-- 1. Distribusi Status -->
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center gap-2 border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Status Pesanan</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative">
                                    <Doughnut :data="chartStatusData" :options="doughnutOptions" />
                                </div>
                            </div>

                            <!-- 2. Trend Pesanan Global -->
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center gap-2 border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Trend Pesanan Mingguan</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative">
                                    <Line :data="chartTrendData" :options="lineOptions" />
                                </div>
                            </div>

                            <!-- 3. Metode Pembayaran -->
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center gap-2 border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Metode Pembayaran</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative">
                                    <Doughnut :data="chartPaymentData" :options="doughnutOptions" />
                                </div>
                            </div>

                            <!-- 4. Layanan Terpopuler -->
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center gap-2 border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Layanan Terpopuler</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative">
                                    <Bar :data="chartServiceData" :options="barOptions" />
                                </div>
                            </div>
                        </div>

                    </div>
                </transition>
            </div>

            <!-- Main Table Section -->
            <div class="space-y-4">
                <div class="flex flex-col md:flex-row gap-4 justify-between items-end">
                    <div class="w-full md:max-w-md relative group">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-muted group-focus-within:text-primary transition-colors"></i>
                        <input type="text" placeholder="Cari ID resi, nama, atau status..." 
                            class="w-full pl-12 pr-4 py-3 bg-white border border-border rounded-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all font-medium text-sm">
                    </div>

                    <div class="flex gap-2">
                        <button class="px-4 py-3 border border-border bg-surface text-muted hover:text-text rounded-sm transition flex items-center gap-2 text-xs font-bold uppercase tracking-widest">
                            <i class="fa-solid fa-filter"></i> Filter
                        </button>
                        <button class="px-4 py-3 border border-border bg-surface text-muted hover:text-text rounded-sm transition flex items-center gap-2 text-xs font-bold uppercase tracking-widest">
                            <i class="fa-solid fa-download"></i> Export
                        </button>
                    </div>
                </div>

                <div class="bg-surface border border-border rounded-sm shadow-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[800px]">
                            <thead>
                                <tr class="bg-container/50 border-b border-border">
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">ID Order</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Pelanggan</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Layanan</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Status</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Total Tagihan</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="order in orders" :key="order.id" class="hover:bg-container/30 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-text group-hover:text-primary transition-colors">{{ order.id }}</span>
                                            <span class="font-mono text-[10px] text-muted mt-1">{{ order.date }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-container flex items-center justify-center font-bold text-xs text-muted border border-border">
                                                {{ order.customer.charAt(0) }}
                                            </div>
                                            <span class="font-bold text-text">{{ order.customer }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-bold text-muted">{{ order.service }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div :class="getStatusDot(order.status)" class="w-2 h-2 rounded-full shadow-sm"></div>
                                            <span :class="getStatusClass(order.status)" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border">
                                                {{ order.status }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-mono font-bold text-text bg-container/50 px-3 py-1.5 rounded-sm border border-border block w-fit">
                                            {{ order.total }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button class="w-8 h-8 flex items-center justify-center rounded-sm border border-border text-muted hover:bg-primary hover:border-primary hover:text-white transition-all">
                                                <i class="fa-solid fa-eye text-xs"></i>
                                            </button>
                                            <button class="w-8 h-8 flex items-center justify-center rounded-sm border border-border text-muted hover:bg-text hover:text-white transition-all">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-2 pt-4">
                    <p class="text-[10px] font-bold text-muted uppercase tracking-widest text-center sm:text-left">Menampilkan 5 dari 1,284 pesanan</p>
                    <div class="flex gap-2">
                        <button disabled class="w-8 h-8 flex items-center justify-center border border-border text-muted opacity-50 cursor-not-allowed rounded-sm">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button class="w-8 h-8 flex items-center justify-center border border-border bg-primary text-white font-bold text-xs rounded-sm shadow-sm transition">1</button>
                        <button class="w-8 h-8 flex items-center justify-center border border-border hover:bg-container text-text font-bold text-xs rounded-sm transition">2</button>
                        <button class="w-8 h-8 flex items-center justify-center border border-border hover:bg-container text-text font-bold text-xs rounded-sm transition">3</button>
                        <button class="w-8 h-8 flex items-center justify-center border border-border text-muted hover:text-text rounded-sm transition">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.font-mono {
    font-family: 'Space Mono', monospace;
}
</style>