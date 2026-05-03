<script setup>
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS, Title, Tooltip, Legend,
    LineElement, LinearScale, PointElement, CategoryScale,
    Filler, ArcElement, BarElement
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, LinearScale,
    PointElement, CategoryScale, Filler, ArcElement, BarElement);

// ── Props ──────────────────────────────────────────────────────────
const props = defineProps({
    orders:    Object,   // paginated
    stats:     Object,
    filters:   Object,
    chartData: Object,
});

const flash = computed(() => usePage().props.flash ?? {});

// ── Search / Filter ────────────────────────────────────────────────
const search       = ref(props.filters?.search ?? '');
const statusFilter = ref(props.filters?.status ?? '');
const dateFilter   = ref(props.filters?.date   ?? '');

let searchTimeout = null;
watch([search, statusFilter, dateFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.orders'), {
            search: search.value,
            status: statusFilter.value,
            date:   dateFilter.value,
        }, { preserveState: true, replace: true });
    }, 350);
});

// ── Collapsible chart ──────────────────────────────────────────────
const isChartExpanded = ref(true);
onMounted(() => {
    const saved = localStorage.getItem('manajemen-order-chart-expanded');
    if (saved !== null) isChartExpanded.value = saved === 'true';
});
watch(isChartExpanded, v => localStorage.setItem('manajemen-order-chart-expanded', v));

// ── Modals (Read-only: hanya detail) ──────────────────────────────
const showDetailModal = ref(false);
const viewingOrder    = ref(null);

function openDetail(o) {
    viewingOrder.value    = o;
    showDetailModal.value = true;
}

function closeModals() {
    showDetailModal.value = false;
    viewingOrder.value    = null;
}

// ── Helpers ────────────────────────────────────────────────────────
function formatRupiah(val) {
    if (val === null || val === undefined) return 'Rp 0';
    return 'Rp ' + parseFloat(val).toLocaleString('id-ID');
}

const STATUS_MAP = {
    menunggu:  { label: 'Menunggu',   cls: 'bg-indigo-100 text-indigo-700 border-indigo-200', dot: 'bg-indigo-500' },
    diproses: { label: 'Diproses',  cls: 'bg-amber-100 text-amber-700 border-amber-200',    dot: 'bg-amber-500' },
    selesai:  { label: 'Selesai',   cls: 'bg-emerald-100 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500' },
    diantar:  { label: 'Diantar',   cls: 'bg-blue-100 text-blue-700 border-blue-200',       dot: 'bg-blue-500' },
};
const getStatus = (s) => STATUS_MAP[s] ?? { label: s, cls: 'bg-slate-100 text-slate-600 border-slate-200', dot: 'bg-slate-400' };

// ── Stat cards ─────────────────────────────────────────────────────
const statCards = computed(() => [
    { label: 'Total Pesanan',   value: props.stats?.total    ?? 0, icon: 'fa-shopping-bag',  color: 'text-blue-600',   bg: 'bg-blue-500/10' },
    { label: 'Pesanan Selesai', value: props.stats?.selesai  ?? 0, icon: 'fa-circle-check',  color: 'text-emerald-600', bg: 'bg-emerald-500/10' },
    { label: 'Sedang Diproses', value: props.stats?.diproses ?? 0, icon: 'fa-spinner',       color: 'text-amber-600',  bg: 'bg-amber-500/10' },
    { label: 'Menunggu',        value: props.stats?.menunggu  ?? 0, icon: 'fa-clock',         color: 'text-rose-600',   bg: 'bg-rose-500/10' },
]);

// ── Chart Configs ──────────────────────────────────────────────────
const tooltipDefaults = {
    backgroundColor: '#0A0A0B',
    titleFont: { family: 'Space Mono', size: 10 },
    bodyFont: { family: 'Inter', size: 12, weight: 'bold' },
    padding: 12, cornerRadius: 4,
};

const doughnutOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'right', labels: { font: { family: 'Inter', size: 10, weight: 'bold' }, color: '#888' } }, tooltip: { ...tooltipDefaults, displayColors: true } },
    cutout: '70%',
};

const lineOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { ...tooltipDefaults, displayColors: false } },
    scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } },
        x: { grid: { display: false }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } },
    },
};

const barOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 10, weight: 'bold' }, color: '#888' } }, tooltip: { ...tooltipDefaults } },
    scales: {
        x: { stacked: true, grid: { display: false }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } },
        y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } },
    },
};

const chartStatusData = computed(() => ({
    labels: ['Selesai', 'Diproses', 'Menunggu', 'Diantar'],
    datasets: [{ data: props.chartData?.statusDist ?? [0,0,0,0], backgroundColor: ['#059669','#d97706','#6366f1','#3b82f6'], borderWidth: 0, hoverOffset: 4 }],
}));

const chartTrendData = computed(() => ({
    labels: (props.chartData?.weeklyTrend ?? []).map(d => d.label),
    datasets: [{ label: 'Pesanan Masuk', data: (props.chartData?.weeklyTrend ?? []).map(d => d.value), borderColor: '#5B4CF3', backgroundColor: 'rgba(91,76,243,0.1)', fill: true, tension: 0.4, pointBackgroundColor: '#fff', pointBorderColor: '#5B4CF3', pointBorderWidth: 2, pointHoverRadius: 6 }],
}));

const chartPaymentData = computed(() => ({
    labels: ['Transfer Bank', 'Tunai', 'E-Wallet'],
    datasets: [{ data: props.chartData?.paymentMethods ?? [0,0,0], backgroundColor: ['#5B4CF3','#059669','#0891b2'], borderWidth: 0, hoverOffset: 4 }],
}));

const barSvcColors = ['#3b82f6','#d97706','#8b5cf6'];
const chartServiceData = computed(() => {
    const names  = props.chartData?.serviceNames ?? [];
    const months = props.chartData?.topServices  ?? [];
    return {
        labels: months.map(m => m.label),
        datasets: names.map((name, i) => ({
            label: name,
            data: months.map(m => m[name] ?? 0),
            backgroundColor: barSvcColors[i] ?? '#888',
            borderRadius: 4,
        })),
    };
});
</script>

<template>
    <Head title="Manajemen Order - Hi Wash" />

    <DashboardLayout title="Manajemen Order">
        <div class="space-y-10 pb-20">

            <!-- Flash -->
            <div v-if="flash.success" class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-sm px-5 py-3 text-sm font-bold">
                <i class="fa-solid fa-circle-check"></i> {{ flash.success }}
            </div>
            <div v-if="flash.error" class="flex items-center gap-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-sm px-5 py-3 text-sm font-bold">
                <i class="fa-solid fa-circle-xmark"></i> {{ flash.error }}
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="(stat, i) in statCards" :key="i"
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
                    <h3 class="text-3xl font-black text-text tracking-tighter">{{ stat.value.toLocaleString('id-ID') }}</h3>
                </div>
            </div>

            <!-- Chart Section (Collapsible) -->
            <div class="bg-surface border border-border rounded-sm shadow-sm overflow-hidden">
                <button @click="isChartExpanded = !isChartExpanded"
                    class="w-full flex items-center justify-between px-6 py-4 hover:bg-container/50 transition-colors">
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

                <transition enter-active-class="transition-all duration-500 ease-out" leave-active-class="transition-all duration-300 ease-in"
                    enter-from-class="max-h-0 opacity-0" enter-to-class="max-h-[2000px] opacity-100"
                    leave-from-class="max-h-[2000px] opacity-100" leave-to-class="max-h-0 opacity-0">
                    <div v-show="isChartExpanded" class="p-6 border-t border-dashed border-border overflow-hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-12">
                            <div class="flex flex-col space-y-4">
                                <div class="border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Status Pesanan</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative">
                                    <Doughnut :data="chartStatusData" :options="doughnutOptions" />
                                </div>
                            </div>
                            <div class="flex flex-col space-y-4">
                                <div class="border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Trend Pesanan 7 Hari Terakhir</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative">
                                    <Line :data="chartTrendData" :options="lineOptions" />
                                </div>
                            </div>
                            <div class="flex flex-col space-y-4">
                                <div class="border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Metode Pembayaran</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative">
                                    <Doughnut :data="chartPaymentData" :options="doughnutOptions" />
                                </div>
                            </div>
                            <div class="flex flex-col space-y-4">
                                <div class="border-b border-dashed border-border pb-2">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Layanan Terpopuler (4 Bln)</h4>
                                </div>
                                <div class="h-48 md:h-56 w-full relative">
                                    <Bar :data="chartServiceData" :options="barOptions" />
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- Table -->
            <div class="space-y-4">
                <div class="flex flex-col md:flex-row gap-3 justify-between items-end">
                    <!-- Search -->
                    <div class="w-full md:max-w-md relative group">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-muted group-focus-within:text-primary transition-colors"></i>
                        <input v-model="search" type="text" placeholder="Cari nama pelanggan atau no. invoice (INV-20260422-0001)..."
                            class="w-full pl-12 pr-4 py-3 bg-white border border-border rounded-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all font-medium text-sm" />
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
                            <option value="menunggu">Menunggu</option>
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="diantar">Diantar</option>
                        </select>
                    </div>
                </div>

                <div class="bg-surface border border-border rounded-sm shadow-xl overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-container/50 border-b border-border">
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">No. Invoice</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Pelanggan</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Layanan</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Total</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted text-center">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-if="orders.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-muted italic text-sm">
                                    <i class="fa-solid fa-inbox text-2xl mb-2 block opacity-40"></i>
                                    Tidak ada pesanan ditemukan.
                                </td>
                            </tr>
                            <tr v-for="o in orders.data" :key="o.id" class="hover:bg-container/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-text group-hover:text-primary transition-colors font-mono text-sm">{{ o.invoice }}</span>
                                        <span class="font-mono text-[10px] text-muted mt-0.5">{{ o.date }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs border border-primary/20 shrink-0 uppercase">
                                            {{ o.customer.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-text text-sm">{{ o.customer }}</p>
                                            <p class="text-[10px] text-muted">{{ o.customer_email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold text-text">{{ o.service }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div :class="getStatus(o.status).dot" class="w-2 h-2 rounded-full shrink-0"></div>
                                        <span :class="getStatus(o.status).cls" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border whitespace-nowrap">
                                            {{ getStatus(o.status).label }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono font-bold text-text bg-container/50 px-3 py-1.5 rounded-sm border border-border block w-fit text-xs">
                                        {{ formatRupiah(o.total_price) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button @click="openDetail(o)"
                                            class="w-8 h-8 flex items-center justify-center rounded-sm border border-border text-muted hover:bg-primary hover:border-primary hover:text-white transition-all" title="Lihat Detail">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="orders.links && orders.links.length > 3" class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4 px-2 pt-2">
                    <p class="text-[10px] font-bold text-muted uppercase tracking-widest">
                        Menampilkan {{ orders.from ?? 0 }}–{{ orders.to ?? 0 }} dari {{ orders.total }} pesanan
                    </p>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <template v-for="(link, key) in orders.links" :key="key">
                            <button
                                @click="link.url && router.get(link.url, { search: search, status: statusFilter, date: dateFilter }, { preserveState: true, preserveScroll: true })"
                                :disabled="!link.url"
                                v-html="link.label"
                                :class="[
                                    link.active ? 'z-10 bg-primary border-primary text-white' : 'bg-surface border-border text-muted hover:bg-container',
                                    'relative inline-flex items-center px-3 py-1.5 border text-sm font-medium transition-colors',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                                    key === 0 ? 'rounded-l-md' : '',
                                    key === orders.links.length - 1 ? 'rounded-r-md' : '',
                                ]"
                            ></button>
                        </template>
                    </nav>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════
             Modal: Detail Order
        ══════════════════════════════════════ -->
        <teleport to="body">
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showDetailModal && viewingOrder" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeModals">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
                    <div class="relative z-10 w-full max-w-md bg-surface border border-border rounded-sm shadow-2xl overflow-hidden">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-border bg-container/40">
                            <h2 class="text-sm font-black uppercase tracking-[0.2em] text-text flex items-center gap-3">
                                <i class="fa-solid fa-receipt text-primary"></i> Detail Pesanan
                            </h2>
                            <button @click="closeModals" class="text-muted hover:text-text transition">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="font-mono font-black text-primary text-lg">{{ viewingOrder.invoice }}</span>
                                <span :class="getStatus(viewingOrder.status).cls" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border">
                                    {{ getStatus(viewingOrder.status).label }}
                                </span>
                            </div>
                            <div class="divide-y divide-border rounded-sm border border-border overflow-hidden">
                                <div v-for="(row, idx) in [
                                    ['No. Invoice',   viewingOrder.invoice],
                                    ['Pelanggan',     viewingOrder.customer],
                                    ['Email',         viewingOrder.customer_email],
                                    ['Layanan',       viewingOrder.service],
                                    ['Tanggal',       viewingOrder.date],
                                    ['Total Harga',   formatRupiah(viewingOrder.total_price)],
                                    ['Pembayaran',    viewingOrder.payment_method + ' — ' + viewingOrder.payment_status],
                                    ['Alamat Pickup', viewingOrder.pickup_address],
                                    ['Alamat Kirim',  viewingOrder.delivery_address],
                                ]" :key="idx" class="flex gap-4 px-4 py-3 bg-surface hover:bg-container/30 transition">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-muted w-28 shrink-0 pt-0.5">{{ row[0] }}</span>
                                    <span class="text-sm font-medium text-text break-all">{{ row[1] }}</span>
                                </div>
                            </div>
                            <div class="flex justify-end pt-2 border-t border-border">
                                <button @click="closeModals"
                                    class="px-5 py-2.5 border border-border rounded-sm text-xs font-black uppercase tracking-widest text-muted hover:text-text hover:bg-container transition">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>


    </DashboardLayout>
</template>

<style scoped>
.font-mono { font-family: 'Space Mono', monospace; }
</style>