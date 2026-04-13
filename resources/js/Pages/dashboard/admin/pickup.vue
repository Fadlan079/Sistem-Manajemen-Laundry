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
    const savedState = localStorage.getItem('pickup-chart-expanded');
    if (savedState !== null) {
        isChartExpanded.value = savedState === 'true';
    }
});

watch(isChartExpanded, (newValue) => {
    localStorage.setItem('pickup-chart-expanded', newValue.toString());
});

// Dummy Data Tasks
const tasks = ref([
    { id: '#TSK-1001', customer: 'Andi Wijaya', address: 'Jl. Melati No. 4A', courier: 'Budi (Kurir)', type: 'Pickup', status: 'Selesai', time: '09:00 WIB' },
    { id: '#TSK-1002', customer: 'Siti Aminah', address: 'Apartemen Sudirman Tower B', courier: 'Anto (Kurir)', type: 'Delivery', status: 'Diperjalanan', time: '11:30 WIB' },
    { id: '#TSK-1003', customer: 'Rina Kartika', address: 'Perumahan Indah Blok C', courier: 'Belum Ditugaskan', type: 'Pickup', status: 'Menuggu', time: '14:00 WIB' },
    { id: '#TSK-1004', customer: 'Fadlan Rizqi', address: 'Jl. Merdeka Raya 10', courier: 'Budi (Kurir)', type: 'Delivery', status: 'Diperjalanan', time: '13:00 WIB' },
    { id: '#TSK-1005', customer: 'Budi Santoso', address: 'Kos Marina No. 12', courier: 'Anto (Kurir)', type: 'Pickup', status: 'Terlambat', time: '10:00 WIB' },
]);

// Statistik Logistik
const pickupStats = [
    { label: 'Menuggu Penugasan', value: '12', icon: 'fa-clipboard-list', color: 'text-amber-600', bg: 'bg-amber-500/10' },
    { label: 'Sedang Diperjalanan', value: '8', icon: 'fa-truck-fast', color: 'text-blue-600', bg: 'bg-blue-500/10' },
    { label: 'Selesai Hari Ini', value: '45', icon: 'fa-box-check', color: 'text-emerald-600', bg: 'bg-emerald-500/10' },
    { label: 'Kurir Aktif', value: '4', icon: 'fa-users', color: 'text-indigo-600', bg: 'bg-indigo-500/10' },
];

/** CHART CONFIGURATIONS **/

// 1. Status Tugas Kurir (Doughnut)
const chartStatusData = {
    labels: ['Selesai', 'Diperjalanan', 'Menuggu', 'Terkendala/Terlambat'],
    datasets: [{
        data: [45, 8, 12, 2],
        backgroundColor: ['#059669', '#3b82f6', '#d97706', '#e11d48'], 
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

// 2. Volume Pickup & Delivery Mingguan (Line)
const chartTrendData = {
    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
    datasets: [
        {
            label: 'Total Tugas Logistik',
            data: [40, 52, 60, 48, 80, 110, 85],
            borderColor: '#0284c7', backgroundColor: 'rgba(2, 132, 199, 0.1)', fill: true, tension: 0.4,
            pointBackgroundColor: '#fff', pointBorderColor: '#0284c7', pointBorderWidth: 2, pointHoverRadius: 6,
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

// 3. Rasio Tepat Waktu (Doughnut)
const chartTimeData = {
    labels: ['Tepat Waktu', 'Terlambat (< 30 mnt)', 'Terlambat (> 30 mnt)'],
    datasets: [{
        data: [85, 10, 5],
        backgroundColor: ['#059669', '#f59e0b', '#e11d48'], 
        borderWidth: 0,
        hoverOffset: 4
    }]
};

// 4. Beban Kerja Kurir (Bar)
const chartCourierData = {
    labels: ['Budi', 'Anto', 'Bagas', 'Doni'],
    datasets: [
        { label: 'Tugas Selesai', data: [15, 12, 10, 8], backgroundColor: '#059669', borderRadius: 4 },
        { label: 'Sedang Jalan', data: [4, 3, 1, 0], backgroundColor: '#3b82f6', borderRadius: 4 },
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
        case 'Diperjalanan': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'Menuggu': return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'Terlambat': return 'bg-rose-100 text-rose-700 border-rose-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};

const getStatusDot = (status) => {
    switch (status) {
        case 'Selesai': return 'bg-emerald-500';
        case 'Diperjalanan': return 'bg-blue-500';
        case 'Menuggu': return 'bg-amber-500';
        case 'Terlambat': return 'bg-rose-500';
        default: return 'bg-slate-300';
    }
};

const getTypeClass = (type) => {
     switch (type) {
        case 'Pickup': return 'bg-purple-100 text-purple-700 border-purple-200';
        case 'Delivery': return 'bg-teal-100 text-teal-700 border-teal-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};
</script>

<template>
    <Head title="Pickup & Delivery - Hi Wash" />

    <DashboardLayout title="Pickup & Delivery">
        <div class="space-y-10 pb-20">
            <!-- Header Section -->
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <h1 class="text-4xl font-black tracking-tighter text-text uppercase">
                        Logistik <span class="text-muted font-medium">Kurir</span>
                    </h1>
                    <p class="text-muted font-medium italic">Pantau penugasan penjemputan dan pengiriman pakaian pelanggan.</p>
                </div>
                
                <button class="bg-primary hover:bg-primary-hover text-white px-8 py-4 rounded-sm font-black uppercase tracking-widest text-xs shadow-lg transition-all transform hover:-translate-y-1 flex items-center gap-3">
                    <i class="fa-solid fa-plus"></i>
                    Tugas & Rute Baru
                </button>
            </header>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="(stat, index) in pickupStats" :key="index" 
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
                            <i class="fa-solid fa-route"></i>
                        </div>
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-text">Visual Analitik - Aktivitas Kurir</h3>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-[9px] font-mono text-muted uppercase tracking-widest italic">Live Tracking</span>
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
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center gap-2 border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Status Tugas</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative"><Doughnut :data="chartStatusData" :options="doughnutOptions" /></div>
                            </div>
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center gap-2 border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Volume Tugas Mingguan</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative"><Line :data="chartTrendData" :options="lineOptions" /></div>
                            </div>
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center gap-2 border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Rasio Tepat Waktu</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative"><Doughnut :data="chartTimeData" :options="doughnutOptions" /></div>
                            </div>
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center gap-2 border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Beban Kerja Kurir Hari Ini</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative"><Bar :data="chartCourierData" :options="barOptions" /></div>
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
                        <input type="text" placeholder="Cari alamat, nama pelanggan, atau kurir..." 
                            class="w-full pl-12 pr-4 py-3 bg-white border border-border rounded-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all font-medium text-sm">
                    </div>

                    <div class="flex gap-2">
                        <button class="px-4 py-3 border border-border bg-surface text-muted hover:text-text rounded-sm transition flex items-center gap-2 text-xs font-bold uppercase tracking-widest">
                            <i class="fa-solid fa-filter"></i> Filter
                        </button>
                    </div>
                </div>

                <div class="bg-surface border border-border rounded-sm shadow-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[900px]">
                            <thead>
                                <tr class="bg-container/50 border-b border-border">
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">ID Tugas</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Pelanggan & Lokasi</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Ditugaskan Kepada</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Detail Jadwal</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Status Tugas</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="task in tasks" :key="task.id" class="hover:bg-container/30 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-text group-hover:text-primary transition-colors">{{ task.id }}</span>
                                            <span :class="getTypeClass(task.type)" class="px-2 py-0.5 mt-2 rounded-sm text-[9px] w-fit font-black uppercase tracking-widest border">
                                                {{ task.type }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-text">{{ task.customer }}</span>
                                            <span class="text-[11px] text-muted mt-1 max-w-[200px] truncate"><i class="fa-solid fa-location-dot mr-1"></i> {{ task.address }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div v-if="task.courier !== 'Belum Ditugaskan'" class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-[10px] font-bold">
                                                {{ task.courier.charAt(0) }}
                                            </div>
                                            <div v-else class="w-6 h-6 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-[10px] font-bold border border-dashed border-slate-300">
                                                ?
                                            </div>
                                            <span :class="task.courier === 'Belum Ditugaskan' ? 'text-muted italic' : 'font-bold text-text'" class="text-xs">
                                                {{ task.courier }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-2 text-text text-xs font-mono font-bold">
                                                <i class="fa-regular fa-calendar text-muted"></i> Hari Ini
                                            </div>
                                            <div class="flex items-center gap-2 text-text text-xs font-mono font-bold">
                                                <i class="fa-regular fa-clock text-muted"></i> Pkl {{ task.time }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div :class="getStatusDot(task.status)" class="w-2 h-2 rounded-full shadow-sm animate-pulse"></div>
                                            <span :class="getStatusClass(task.status)" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border">
                                                {{ task.status }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button class="w-8 h-8 flex items-center justify-center rounded-sm border border-border text-muted hover:bg-primary hover:border-primary hover:text-white transition-all" title="Lacak di Peta">
                                                <i class="fa-solid fa-map-location-dot text-xs"></i>
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
                    <p class="text-[10px] font-bold text-muted uppercase tracking-widest text-center sm:text-left">Menampilkan 5 tugas terbaru</p>
                    <div class="flex gap-2">
                        <button disabled class="w-8 h-8 flex items-center justify-center border border-border text-muted opacity-50 cursor-not-allowed rounded-sm"><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="w-8 h-8 flex items-center justify-center border border-border bg-primary text-white font-bold text-xs rounded-sm shadow-sm transition">1</button>
                        <button class="w-8 h-8 flex items-center justify-center border border-border text-muted hover:text-text rounded-sm transition"><i class="fa-solid fa-chevron-right"></i></button>
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