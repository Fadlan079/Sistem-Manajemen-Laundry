<script setup>
import { Head, usePage, useForm, router, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import { 
    Chart as ChartJS, Title, Tooltip, Legend, LineElement, LinearScale, PointElement, CategoryScale, Filler, ArcElement, BarElement
} from 'chart.js';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

ChartJS.register(Title, Tooltip, Legend, LineElement, LinearScale, PointElement, CategoryScale, Filler, ArcElement, BarElement);

const props = defineProps({
    deliveries: Object,
    stats: Object,
    filters: Object,
    orderList: Array,
    courierList: Array,
    chartData: Object,
});

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

const search       = ref(props.filters?.search ?? '');
const statusFilter = ref(props.filters?.status ?? '');
const tab          = ref(props.filters?.tab    ?? 'pickup');
const dateFilter   = ref(props.filters?.date   ?? '');

let searchTimeout = null;
watch([search, statusFilter, tab, dateFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.pickup'), {
            search: search.value,
            status: statusFilter.value,
            tab:    tab.value,
            date:   dateFilter.value,
        }, { preserveState: true, preserveScroll: true, replace: true });
    }, 350);
});

// Statistik Logistik
const pickupStats = computed(() => [
    { label: 'Menunggu Penugasan', value: props.stats.menunggu, icon: 'fa-clipboard-list', color: 'text-amber-600', bg: 'bg-amber-500/10' },
    { label: 'Sedang Diperjalanan', value: props.stats.diperjalanan, icon: 'fa-truck-fast', color: 'text-blue-600', bg: 'bg-blue-500/10' },
    { label: 'Selesai Hari Ini', value: props.stats.selesai_hari_ini, icon: 'fa-box-check', color: 'text-emerald-600', bg: 'bg-emerald-500/10' },
    { label: 'Kurir Aktif', value: props.stats.kurir_aktif, icon: 'fa-users', color: 'text-indigo-600', bg: 'bg-indigo-500/10' },
]);

/** CHART CONFIGURATIONS **/

// 1. Status Tugas Kurir (Doughnut)
const chartStatusData = computed(() => {
    return {
        labels: ['Selesai', 'Diperjalanan', 'Menunggu'],
        datasets: [{
            data: props.chartData.statusDist,
            backgroundColor: ['#059669', '#3b82f6', '#d97706'], 
            borderWidth: 0,
            hoverOffset: 4
        }]
    };
});

const doughnutOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
        legend: { position: 'right', labels: { font: { family: 'Inter', size: 10, weight: 'bold' }, color: '#888' } },
        tooltip: { backgroundColor: '#0A0A0B', titleFont: { family: 'Space Mono', size: 10 }, bodyFont: { family: 'Inter', size: 12, weight: 'bold' }, padding: 12, cornerRadius: 4, displayColors: true }
    },
    cutout: '70%'
};

// 2. Volume Pickup & Delivery Mingguan (Line)
const chartTrendData = computed(() => {
    return {
        labels: props.chartData.weeklyVolume.map(v => v.label).reverse(),
        datasets: [
            {
                label: 'Total Tugas Logistik',
                data: props.chartData.weeklyVolume.map(v => v.value).reverse(),
                borderColor: '#0284c7', backgroundColor: 'rgba(2, 132, 199, 0.1)', fill: true, tension: 0.4,
                pointBackgroundColor: '#fff', pointBorderColor: '#0284c7', pointBorderWidth: 2, pointHoverRadius: 6,
            }
        ]
    };
});

const lineOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0A0A0B', titleFont: { family: 'Space Mono', size: 10 }, bodyFont: { family: 'Inter', size: 12, weight: 'bold' }, padding: 12, cornerRadius: 4, displayColors: false } },
    scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } },
        x: { grid: { display: false }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } }
    }
};

// 3. Rasio Tepat Waktu (Doughnut)
const chartTimeData = computed(() => {
    return {
        labels: ['Selesai', 'Sedang Diproses'],
        datasets: [{
            data: props.chartData.timeDist,
            backgroundColor: ['#059669', '#f59e0b'], 
            borderWidth: 0,
            hoverOffset: 4
        }]
    };
});

// 4. Beban Kerja Kurir (Bar)
const chartCourierData = computed(() => {
    return {
        labels: props.chartData.couriers.map(c => c.name),
        datasets: [
            { label: 'Tugas Selesai', data: props.chartData.couriers.map(c => c.selesai), backgroundColor: '#059669', borderRadius: 4 },
            { label: 'Sedang Jalan', data: props.chartData.couriers.map(c => c.jalan), backgroundColor: '#3b82f6', borderRadius: 4 },
        ]
    };
});

const barOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 10, weight: 'bold' }, color: '#888' } }, tooltip: { backgroundColor: '#0A0A0B', titleFont: { family: 'Space Mono', size: 10 }, bodyFont: { family: 'Inter', size: 12, weight: 'bold' }, padding: 12, cornerRadius: 4 } },
    scales: {
        x: { stacked: true, grid: { display: false }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } },
        y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } }
    }
};

const getStatusClass = (status) => {
    switch (status.toLowerCase()) {
        case 'selesai': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'diantar': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'dijemput': return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'menunggu': return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'terlambat': return 'bg-rose-100 text-rose-700 border-rose-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};

const getStatusDot = (status) => {
    switch (status.toLowerCase()) {
        case 'selesai': return 'bg-emerald-500';
        case 'diantar': return 'bg-blue-500';
        case 'dijemput': return 'bg-amber-500';
        case 'menunggu': return 'bg-amber-500';
        case 'terlambat': return 'bg-rose-500';
        default: return 'bg-slate-300';
    }
};

const getTypeClass = (type) => {
     switch (type.toLowerCase()) {
        case 'pickup': return 'bg-purple-100 text-purple-700 border-purple-200';
        case 'delivery': return 'bg-teal-100 text-teal-700 border-teal-200';
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
            </header>

            <!-- Tabs -->
            <div class="flex border-b border-border">
                <button
                    @click="tab = 'pickup'"
                    :class="[
                        'px-6 py-4 text-xs font-black tracking-widest uppercase transition-all whitespace-nowrap',
                        tab === 'pickup' 
                            ? 'text-primary border-b-2 border-primary bg-primary/5' 
                            : 'text-muted hover:text-text hover:bg-container'
                    ]"
                >
                    <i class="fa-solid fa-truck-pickup mr-2"></i> Penjemputan
                </button>
                <button
                    @click="tab = 'delivery'"
                    :class="[
                        'px-6 py-4 text-xs font-black tracking-widest uppercase transition-all whitespace-nowrap',
                        tab === 'delivery' 
                            ? 'text-primary border-b-2 border-primary bg-primary/5' 
                            : 'text-muted hover:text-text hover:bg-container'
                    ]"
                >
                    <i class="fa-solid fa-box-open mr-2"></i> Pengiriman
                </button>
            </div>


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
                <div class="flex flex-col md:flex-row gap-3 justify-between items-end">
                    <!-- Search -->
                    <div class="w-full md:max-w-md relative group">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-muted group-focus-within:text-primary transition-colors"></i>
                        <input v-model="search" type="text" placeholder="Cari alamat, nama pelanggan, atau kurir..." 
                            class="w-full pl-12 pr-4 py-3 bg-white border border-border rounded-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all font-medium text-sm">
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 items-end w-full md:w-auto">
                        <!-- Date Filter -->
                        <div class="relative w-full sm:w-auto">
                            <i class="fa-solid fa-calendar-days absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none text-xs"></i>
                            <input type="date" v-model="dateFilter"
                                class="w-full sm:w-[175px] pl-9 pr-8 py-3 border border-border bg-surface text-text rounded-sm text-sm outline-none focus:border-primary transition" />
                            <button v-if="dateFilter" @click="dateFilter = ''"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-muted hover:text-rose-500 transition-colors">
                                <i class="fa-solid fa-times text-xs"></i>
                            </button>
                        </div>

                        <!-- Status Filter -->
                        <select v-model="statusFilter"
                            class="w-full sm:w-auto px-4 py-3 border border-border bg-surface text-text rounded-sm text-xs font-bold uppercase tracking-widest outline-none focus:border-primary transition">
                            <option value="">Semua Status</option>
                            <option value="dijemput">Menunggu Penugasan</option>
                            <option value="diantar">Sedang Diperjalanan</option>
                            <option value="selesai">Selesai</option>
                            <option value="terlambat">Terlambat</option>
                        </select>
                    </div>
                </div>

                <div class="bg-surface border border-border rounded-sm shadow-xl overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[900px]">
                            <thead>
                                <tr class="bg-container/50 border-b border-border">
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">ID Tugas</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Pelanggan & Lokasi</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Ditugaskan Kepada</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Detail Jadwal</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Status Tugas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-if="deliveries.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-muted italic text-sm">
                                        <i class="fa-solid fa-inbox text-2xl mb-2 block opacity-40"></i>
                                        Tidak ada tugas ditemukan.
                                    </td>
                                </tr>
                                <tr v-for="task in deliveries.data" :key="task.id" class="hover:bg-container/30 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-text group-hover:text-primary transition-colors">{{ task.task_no }}</span>
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
                                            <div v-if="task.courier_id" class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-[10px] font-bold">
                                                {{ task.courier?.charAt(0) ?? 'K' }}
                                            </div>
                                            <div v-else class="w-6 h-6 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-[10px] font-bold border border-dashed border-slate-300">
                                                ?
                                            </div>
                                            <span :class="!task.courier_id ? 'text-muted italic' : 'font-bold text-text'" class="text-xs">
                                                {{ task.courier ?? 'Belum Ditugaskan' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1">
                                            <div v-if="task.scheduled_at" class="flex items-center gap-2 text-text text-xs font-mono font-bold">
                                                <i class="fa-regular fa-clock text-muted"></i> {{ task.scheduled_at }}
                                            </div>
                                            <div v-else class="flex items-center gap-2 text-text text-xs font-mono font-bold text-muted">
                                                Belum diatur
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
                                </tr>
                            </tbody>
                        </table>
                </div>

                <!-- Pagination -->
                <div v-if="deliveries.links && deliveries.links.length > 3" class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4 px-2 pt-2">
                    <p class="text-[10px] font-bold text-muted uppercase tracking-widest">
                        Menampilkan {{ deliveries.from ?? 0 }}–{{ deliveries.to ?? 0 }} dari {{ deliveries.total }} tugas
                    </p>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <template v-for="(link, key) in deliveries.links" :key="key">
                            <button
                                @click="link.url && router.get(link.url, { search: search, status: statusFilter, tab: tab, date: dateFilter }, { preserveState: true, preserveScroll: true })"
                                :disabled="!link.url"
                                v-html="link.label"
                                :class="[
                                    link.active ? 'z-10 bg-primary border-primary text-white' : 'bg-surface border-border text-muted hover:bg-container',
                                    'relative inline-flex items-center px-3 py-1.5 border text-sm font-medium transition-colors',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                                    key === 0 ? 'rounded-l-md' : '',
                                    key === deliveries.links.length - 1 ? 'rounded-r-md' : '',
                                ]"
                            ></button>
                        </template>
                    </nav>
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