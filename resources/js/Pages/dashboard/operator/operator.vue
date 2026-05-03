<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';
import { Line, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS, Title, Tooltip, Legend,
    LineElement, LinearScale, PointElement, CategoryScale, Filler, ArcElement
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, LinearScale, PointElement, CategoryScale, Filler, ArcElement);

const user = computed(() => usePage().props.auth.user);

const props = defineProps({
    pesanan:        Object,
    pickup:         Object,
    delivery:       Object,
    pembayaran:     Object,
    recentOrders:   Array,
    urgentPickups:  Array,
    urgentPayments: Array,
    weeklyTrend:    Array,
    statusDist:     Array,
});

const formatRupiah = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 'Rp0';
    const num = typeof val === 'string' ? parseFloat(val) : val;
    if (isNaN(num) || !num) return 'Rp0';
    return 'Rp' + new Intl.NumberFormat('id-ID').format(num);
};

// Clock & Greeting logic
const currentTime = ref(new Date());
const updateTime = () => { currentTime.value = new Date(); };
let timer;
onMounted(() => { timer = setInterval(updateTime, 1000); });
onUnmounted(() => clearInterval(timer));

const greeting = computed(() => {
    const hrs = currentTime.value.getHours();
    if (hrs < 11) return { text: 'Selamat Pagi', icon: 'fa-sun', sub: 'Mari mulai hari dengan produktif!' };
    if (hrs < 15) return { text: 'Selamat Siang', icon: 'fa-cloud-sun', sub: 'Tetap semangat melayani pelanggan!' };
    if (hrs < 19) return { text: 'Selamat Sore', icon: 'fa-cloud-moon', sub: 'Pastikan semua antrean terkendali.' };
    return { text: 'Selamat Malam', icon: 'fa-moon', sub: 'Waktunya rekapitulasi harian.' };
});

const STATUS_MAP = {
    dibuat:    { label: 'Dibuat',   cls: 'bg-slate-100 text-slate-700', dot: 'bg-slate-400' },
    antri:     { label: 'Antri',    cls: 'bg-amber-100 text-amber-700', dot: 'bg-amber-400' },
    dijemput:  { label: 'Dijemput',  cls: 'bg-teal-100 text-teal-700',   dot: 'bg-teal-400' },
    diproses:  { label: 'Diproses',  cls: 'bg-blue-100 text-blue-700',   dot: 'bg-blue-400' },
    selesai:   { label: 'Selesai',   cls: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-400' },
    diantar:   { label: 'Diantar',   cls: 'bg-purple-100 text-purple-700', dot: 'bg-purple-400' },
    diterima:  { label: 'Diterima',  cls: 'bg-rose-100 text-rose-700',     dot: 'bg-rose-400' },
};

const getStatus = (s) => STATUS_MAP[s] ?? { label: s, cls: 'bg-slate-100 text-slate-600', dot: 'bg-slate-400' };

// Stepper Logic for Live Monitor
const getSteps = (o) => {
    let s = ['Dibuat', 'Antri'];
    // Assuming we have basic flags or we can infer from status history, 
    // for now we use a generic set if we don't have detailed order flags in recentOrders
    if (o.delivery_type === 'jemput' || o.delivery_type === 'antar_jemput' || o.hasPickup) s.push('Dijemput');
    s.push('Diproses', 'Selesai');
    if (o.delivery_type === 'antar' || o.delivery_type === 'antar_jemput' || o.hasDelivery) s.push('Diantar');
    s.push('Diterima');
    return s;
};

const statusToLabelMap = {
    'pending': 'Dibuat', 'dibuat': 'Dibuat', 'antri': 'Antri', 'dijemput': 'Dijemput',
    'diproses': 'Diproses', 'selesai': 'Selesai', 'diantar': 'Diantar', 'diterima': 'Diterima'
};

const getOrderStepIndex = (o) => {
    const steps = getSteps(o);
    const label = statusToLabelMap[o.status] || 'Dibuat';
    const idx = steps.indexOf(label);
    return idx === -1 ? 0 : idx;
};

// Charts
const chartData = computed(() => ({
    labels: (props.weeklyTrend ?? []).map(d => d.label),
    datasets: [{
        label: 'Pesanan',
        data: (props.weeklyTrend ?? []).map(d => d.pesanan),
        borderColor: '#E30613',
        backgroundColor: 'rgba(227, 6, 19, 0.05)',
        fill: true, tension: 0.4, pointRadius: 4, pointHoverRadius: 6,
    }]
}));

const chartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#111', padding: 12, cornerRadius: 8 } },
    scales: {
        y: { grid: { color: 'rgba(0,0,0,0.03)', drawBorder: false }, ticks: { font: { size: 10 }, color: '#999' } },
        x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#999' } }
    }
};

const searchQuery = ref('');
const handleSearch = () => {
    if (searchQuery.value) {
        router.get(route('operator.pesanan.masuk'), { search: searchQuery.value });
    }
};
</script>

<template>
    <Head title="Operator Overview - Hi Wash" />
    <DashboardLayout title="Overview">
        <div class="space-y-8 pb-24">
            
            <!-- ══ HEADER & SEARCH ══ -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 rounded-2xl bg-primary shadow-2xl shadow-primary/30 flex items-center justify-center text-white text-3xl">
                        <i :class="['fa-solid', greeting.icon]"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-text tracking-tight">{{ greeting.text }}, {{ user.name.split(' ')[0] }}!</h1>
                        <p class="text-sm text-muted font-medium">{{ greeting.sub }}</p>
                    </div>
                </div>

                <div class="relative w-full lg:w-[400px] group">
                    <input v-model="searchQuery" @keyup.enter="handleSearch" type="text" placeholder="Cari No. Invoice atau Nama Pelanggan..." 
                        class="w-full bg-surface border border-border rounded-2xl px-12 py-4 text-sm font-bold shadow-sm focus:ring-4 focus:ring-primary/5 focus:border-primary outline-none transition-all" />
                    <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-muted group-focus-within:text-primary transition-colors"></i>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2">
                        <span class="px-2 py-1 bg-container border border-border rounded text-[10px] font-black text-muted">ENTER</span>
                    </div>
                </div>
            </div>

            <!-- ══ PRIMARY STATS ROW ══ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="(s, i) in [
                    { label: 'Belum Diproses', val: pesanan?.antrian, sub: 'Dibuat & Antri', icon: 'fa-hourglass-half', color: 'amber' },
                    { label: 'Sedang Proses', val: pesanan?.diproses, sub: 'Dalam pencucian', icon: 'fa-spinner', color: 'blue', spin: true },
                    { label: 'Selesai Hari Ini', val: pesanan?.selesai_hari_ini, sub: 'Siap diantar/ambil', icon: 'fa-check-double', color: 'emerald' },
                    { label: 'Omzet Harian', val: formatRupiah(pembayaran?.pendapatan_hari_ini), sub: 'Pembayaran lunas', icon: 'fa-coins', color: 'primary' },
                ]" :key="i" class="bg-surface border border-border rounded-3xl p-6 shadow-sm hover:shadow-xl transition-all duration-500 group relative overflow-hidden">
                    <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-current opacity-[0.03] group-hover:scale-150 transition-transform duration-700 rounded-full" :class="`text-${s.color === 'primary' ? 'primary' : s.color + '-600'}`"></div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-muted">{{ s.label }}</span>
                        <div :class="[s.color === 'primary' ? 'bg-primary/10 text-primary' : `bg-${s.color}-50 text-${s.color}-600`]" class="w-10 h-10 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform shadow-inner">
                            <i :class="['fa-solid', s.icon, s.spin ? 'fa-spin-pulse' : '']"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-black text-text mb-1 font-mono tracking-tighter">{{ s.val ?? 0 }}</div>
                    <p class="text-[10px] font-bold text-muted uppercase tracking-widest">{{ s.sub }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                
                <!-- ══ LEFT COLUMN: Operational Center (8 Units) ══ -->
                <div class="xl:col-span-8 space-y-8">
                    
                    <!-- Monitoring Pesanan Aktif -->
                    <div class="bg-surface border border-border rounded-3xl overflow-hidden shadow-sm">
                        <div class="px-8 py-6 border-b border-border flex items-center justify-between bg-container/30">
                            <div>
                                <h3 class="text-sm font-black text-text uppercase tracking-widest">Live Monitor</h3>
                                <p class="text-[10px] text-muted font-bold mt-0.5 uppercase tracking-widest">Pesanan yang sedang berjalan</p>
                            </div>
                            <Link :href="route('operator.pesanan.masuk')" class="text-[10px] font-black text-primary hover:underline uppercase tracking-widest">Lihat Semua →</Link>
                        </div>
                        <div class="p-6">
                            <div v-if="!recentOrders?.length" class="py-20 text-center space-y-4">
                                <div class="w-20 h-20 bg-container rounded-full mx-auto flex items-center justify-center text-muted text-2xl">
                                    <i class="fa-solid fa-inbox"></i>
                                </div>
                                <p class="text-sm font-bold text-muted">Belum ada pesanan aktif saat ini.</p>
                            </div>
                            <div v-else class="space-y-4">
                                <div v-for="o in recentOrders.slice(0, 5)" :key="o.id" class="p-4 bg-container/40 border border-border rounded-2xl hover:border-primary/30 transition-all group flex flex-col sm:flex-row items-center gap-6">
                                    <!-- Identity -->
                                    <div class="flex items-center gap-4 w-full sm:w-[220px] shrink-0">
                                        <div class="w-12 h-12 bg-white border border-border rounded-xl flex items-center justify-center text-primary shadow-sm shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                                            <i class="fa-solid fa-shirt"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-mono font-black text-primary">{{ o.invoice }}</span>
                                                <span v-if="o.status === 'dibuat'" class="px-1.5 py-0.5 bg-rose-500 text-white text-[7px] font-black uppercase rounded shadow-sm">NEW</span>
                                            </div>
                                            <h4 class="text-xs font-black text-text truncate uppercase mt-0.5">{{ o.customer }}</h4>
                                            <p class="text-[9px] text-muted font-bold uppercase truncate">{{ o.service }}</p>
                                        </div>
                                    </div>

                                    <!-- Progress Stepper (Minimalist) -->
                                    <div class="flex-1 w-full flex flex-col items-center px-4">
                                        <div class="relative w-full h-1.5 bg-gray-100 rounded-full overflow-hidden mb-3">
                                            <div class="absolute top-0 left-0 h-full bg-primary transition-all duration-1000 shadow-[0_0_8px_rgba(227,6,19,0.5)]"
                                                :style="{ width: `${Math.min(100, (getOrderStepIndex(o) / (getSteps(o).length - 1)) * 100)}%` }"></div>
                                        </div>
                                        <div class="flex justify-between w-full px-1">
                                            <span v-for="(step, i) in getSteps(o)" :key="i" 
                                                class="text-[7px] font-black uppercase tracking-tighter"
                                                :class="getOrderStepIndex(o) >= i ? 'text-primary' : 'text-muted/40'">
                                                {{ step }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Action -->
                                    <div class="shrink-0">
                                        <Link :href="route('operator.pesanan.masuk')" class="w-9 h-9 bg-white border border-border rounded-xl flex items-center justify-center text-muted hover:text-primary hover:border-primary transition-all shadow-sm">
                                            <i class="fa-solid fa-arrow-right text-xs"></i>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row: Urgent Lists -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Jemput Menunggu -->
                        <div class="bg-surface border border-border rounded-3xl p-6 shadow-sm flex flex-col">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm shadow-inner">
                                        <i class="fa-solid fa-motorcycle"></i>
                                    </div>
                                    <h3 class="text-xs font-black text-text uppercase tracking-widest">Penjemputan Urgent</h3>
                                </div>
                                <span class="bg-rose-100 text-rose-600 text-[9px] font-black px-2 py-0.5 rounded-full">{{ urgentPickups?.length ?? 0 }}</span>
                            </div>
                            <div v-if="!urgentPickups?.length" class="flex-1 flex flex-col items-center justify-center py-10 text-muted">
                                <i class="fa-solid fa-circle-check text-2xl mb-2 text-emerald-500"></i>
                                <p class="text-[10px] font-bold uppercase tracking-widest">Semua Aman!</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div v-for="p in urgentPickups.slice(0, 3)" :key="p.id" class="flex items-center gap-3 p-3 bg-container/30 border border-border rounded-2xl hover:bg-white hover:shadow-md transition-all">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[9px] font-mono font-black text-primary leading-none mb-1">{{ p.invoice }}</p>
                                        <p class="text-xs font-black text-text truncate uppercase">{{ p.name }}</p>
                                        <p class="text-[8px] text-muted truncate mt-0.5">{{ p.address }}</p>
                                    </div>
                                    <Link :href="route('operator.penjemputan')" class="px-3 py-1.5 bg-primary text-white text-[8px] font-black uppercase rounded-lg shadow-lg shadow-primary/20 hover:scale-105 transition-transform">
                                        Assign
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Tagihan Menunggu -->
                        <div class="bg-surface border border-border rounded-3xl p-6 shadow-sm flex flex-col">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm shadow-inner">
                                        <i class="fa-solid fa-money-bill-wave"></i>
                                    </div>
                                    <h3 class="text-xs font-black text-text uppercase tracking-widest">Tagihan Tertunggak</h3>
                                </div>
                                <span class="bg-rose-100 text-rose-600 text-[9px] font-black px-2 py-0.5 rounded-full">{{ urgentPayments?.length ?? 0 }}</span>
                            </div>
                            <div v-if="!urgentPayments?.length" class="flex-1 flex flex-col items-center justify-center py-10 text-muted">
                                <i class="fa-solid fa-circle-check text-2xl mb-2 text-emerald-500"></i>
                                <p class="text-[10px] font-bold uppercase tracking-widest">Tidak ada tunggakan!</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div v-for="p in urgentPayments.slice(0, 3)" :key="p.id" class="flex items-center gap-3 p-3 bg-rose-50/30 border border-rose-100 rounded-2xl hover:bg-white hover:shadow-md transition-all">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[9px] font-mono font-black text-primary leading-none mb-1">{{ p.invoice }}</p>
                                        <p class="text-xs font-black text-text truncate uppercase">{{ p.name }}</p>
                                        <p class="text-[10px] font-mono font-black text-rose-600 mt-1">{{ formatRupiah(p.total_price) }}</p>
                                    </div>
                                    <Link :href="route('operator.pembayaran')" class="w-8 h-8 bg-rose-600 text-white rounded-lg flex items-center justify-center hover:scale-105 transition-transform shadow-lg shadow-rose-200">
                                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ RIGHT COLUMN: Hub & Performance (4 Units) ══ -->
                <div class="xl:col-span-4 space-y-8">
                    
                    <!-- Quick Action Hub -->
                    <div class="bg-white border border-border rounded-3xl p-6 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-8 opacity-[0.05] pointer-events-none">
                            <i class="fa-solid fa-bolt text-7xl text-primary"></i>
                        </div>
                        <h3 class="text-sm font-black text-text uppercase tracking-widest mb-6">Action Hub</h3>
                        <div class="grid grid-cols-1 gap-3">
                            <Link :href="route('operator.pesanan.masuk') + '?action=add'" class="flex items-center gap-4 p-4 bg-primary text-white rounded-2xl shadow-xl shadow-primary/20 hover:-translate-y-1 transition-all group">
                                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-plus"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs font-black uppercase tracking-widest">Tambah Pesanan</p>
                                    <p class="text-[9px] opacity-70 uppercase tracking-tighter">Buat order baru manual</p>
                                </div>
                            </Link>

                            <div class="grid grid-cols-2 gap-3">
                                <Link :href="route('operator.penjemputan', { tab: 'belum-diassign' })" class="flex flex-col items-center text-center gap-3 p-4 bg-container/50 border border-border rounded-2xl hover:bg-white hover:shadow-md transition-all group">
                                    <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-lg group-hover:rotate-12 transition-transform">
                                        <i class="fa-solid fa-motorcycle"></i>
                                    </div>
                                    <span class="text-[9px] font-black text-text uppercase tracking-widest">Assign Jemput</span>
                                </Link>
                                <Link :href="route('operator.pengantaran', { tab: 'belum-diassign' })" class="flex flex-col items-center text-center gap-3 p-4 bg-container/50 border border-border rounded-2xl hover:bg-white hover:shadow-md transition-all group">
                                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-lg group-hover:rotate-12 transition-transform">
                                        <i class="fa-solid fa-truck text-lg"></i>
                                    </div>
                                    <span class="text-[9px] font-black text-text uppercase tracking-widest">Assign Antar</span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Stats -->
                    <div class="bg-surface border border-border rounded-3xl p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-sm font-black text-text uppercase tracking-widest">Performance</h3>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                                <span class="text-[9px] font-black text-muted uppercase tracking-widest">Weekly</span>
                            </div>
                        </div>
                        <div class="h-40 mb-6">
                            <Line :data="chartData" :options="chartOptions" />
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-container/30 rounded-xl border border-border">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                    <span class="text-[10px] font-bold text-muted uppercase tracking-widest">Lunas Hari Ini</span>
                                </div>
                                <span class="text-xs font-black text-text font-mono">{{ pembayaran?.lunas_hari_ini ?? 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-container/30 rounded-xl border border-border">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-primary"></div>
                                    <span class="text-[10px] font-bold text-muted uppercase tracking-widest">Bulan Ini</span>
                                </div>
                                <span class="text-xs font-black text-primary font-mono">{{ formatRupiah(pembayaran?.pendapatan_bulan_ini) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Service Distribution -->
                    <div class="bg-surface border border-border rounded-3xl p-6 shadow-sm">
                        <h3 class="text-sm font-black text-text uppercase tracking-widest mb-6">Status Mix</h3>
                        <div class="h-48 relative">
                            <Doughnut :data="{
                                labels: ['Selesai', 'Proses', 'Antri', 'Antar', 'Jemput'],
                                datasets: [{
                                    data: statusDist ?? [0,0,0,0,0],
                                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#06b6d4'],
                                    borderWidth: 0, cutout: '75%', borderRadius: 5
                                }]
                            }" :options="{ responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }" />
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-2xl font-black text-text leading-none">{{ (pesanan?.antrian ?? 0) + (pesanan?.diproses ?? 0) + (pesanan?.selesai_hari_ini ?? 0) }}</span>
                                <span class="text-[8px] font-black text-muted uppercase tracking-widest">Total Active</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.font-mono { font-family: 'JetBrains Mono', 'Fira Code', monospace; }
</style>
