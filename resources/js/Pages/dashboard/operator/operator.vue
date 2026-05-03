<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
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

const formatRupiah = (v) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v ?? 0);

const now = new Date();
const greeting = now.getHours() < 12 ? 'Selamat Pagi' : now.getHours() < 17 ? 'Selamat Siang' : 'Selamat Sore';

const STATUS_MAP = {
    menunggu:  { label: 'Menunggu',   cls: 'bg-indigo-100 text-indigo-700', dot: 'bg-indigo-400' },
    dijemput: { label: 'Dijemput',  cls: 'bg-teal-100 text-teal-700',     dot: 'bg-teal-400' },
    diproses: { label: 'Diproses',  cls: 'bg-amber-100 text-amber-700',   dot: 'bg-amber-400' },
    selesai:  { label: 'Selesai',   cls: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-400' },
    diantar:  { label: 'Diantar',   cls: 'bg-blue-100 text-blue-700',     dot: 'bg-blue-400' },
};
const getStatus = (s) => STATUS_MAP[s] ?? { label: s, cls: 'bg-slate-100 text-slate-600', dot: 'bg-slate-400' };

const chartData = computed(() => ({
    labels: (props.weeklyTrend ?? []).map(d => d.label),
    datasets: [
        {
            label: 'Pesanan Masuk',
            data: (props.weeklyTrend ?? []).map(d => d.pesanan),
            borderColor: '#5B4CF3',
            backgroundColor: 'rgba(91,76,243,0.08)',
            fill: true, tension: 0.4,
            pointBackgroundColor: '#fff', pointBorderColor: '#5B4CF3', pointBorderWidth: 2, pointHoverRadius: 5,
        },
        {
            label: 'Selesai',
            data: (props.weeklyTrend ?? []).map(d => d.selesai),
            borderColor: '#059669',
            backgroundColor: 'rgba(5,150,105,0.05)',
            fill: true, tension: 0.4,
            pointBackgroundColor: '#fff', pointBorderColor: '#059669', pointBorderWidth: 2, pointHoverRadius: 5,
        },
    ],
}));

const chartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 10, weight: 'bold' }, color: '#888', boxWidth: 10 } },
        tooltip: { backgroundColor: '#111', titleFont: { size: 10 }, bodyFont: { size: 12, weight: 'bold' }, padding: 10, cornerRadius: 4 },
    },
    scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, border: { display: false }, ticks: { font: { size: 9 }, color: '#aaa', precision: 0 } },
        x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 9 }, color: '#aaa' } },
    },
};

const chartStatusData = computed(() => ({
    labels: ['Selesai', 'Diproses', 'Menunggu', 'Diantar', 'Dijemput'],
    datasets: [{
        data: props.statusDist ?? [0,0,0,0,0],
        backgroundColor: ['#059669','#d97706','#6366f1','#3b82f6','#14b8a6'],
        borderWidth: 0, hoverOffset: 4
    }]
}));

const doughnutOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
        legend: { position: 'right', labels: { font: { family: 'Inter', size: 10, weight: 'bold' }, color: '#888' } },
        tooltip: { backgroundColor: '#111', titleFont: { size: 10 }, bodyFont: { size: 12, weight: 'bold' }, padding: 12, cornerRadius: 4, displayColors: true }
    },
    cutout: '70%'
};

const totalAlert = computed(() =>
    (props.pickup?.belum_assign ?? 0) +
    (props.delivery?.belum_assign ?? 0) +
    (props.pembayaran?.belum_lunas ?? 0)
);
</script>

<template>
    <Head title="Dashboard Operator - Hi Wash Laundry" />
    <DashboardLayout title="Overview">

        <div class="space-y-8 pb-20">

            <!-- ══ ROW 1: 4 Stat Cards ══ -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Antrian -->
                <div class="bg-surface border border-border rounded-xl p-5 shadow-sm hover:shadow-md transition group">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-muted uppercase tracking-widest">Antrian</span>
                        <div class="w-8 h-8 rounded-lg bg-yellow-50 text-yellow-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-hourglass-half text-sm"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-black text-text mb-1">{{ pesanan?.antrian ?? 0 }}</div>
                    <p class="text-xs text-muted">Pesanan menunggu</p>
                </div>

                <!-- Diproses -->
                <div class="bg-surface border border-border rounded-xl p-5 shadow-sm hover:shadow-md transition group">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-muted uppercase tracking-widest">Diproses</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-spinner text-sm fa-spin-pulse"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-black text-text mb-1">{{ pesanan?.diproses ?? 0 }}</div>
                    <p class="text-xs text-muted">Sedang dicuci</p>
                </div>

                <!-- Selesai Hari Ini -->
                <div class="bg-surface border border-border rounded-xl p-5 shadow-sm hover:shadow-md transition group">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-muted uppercase tracking-widest">Selesai</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-circle-check text-sm"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-black text-text mb-1">{{ pesanan?.selesai_hari_ini ?? 0 }}</div>
                    <p class="text-xs text-muted">Hari ini</p>
                </div>

                <!-- Pendapatan -->
                <div class="bg-surface border border-border rounded-xl p-5 shadow-sm hover:shadow-md transition group">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-muted uppercase tracking-widest">Pendapatan</span>
                        <div class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-coins text-sm"></i>
                        </div>
                    </div>
                    <div class="text-xl font-black text-text mb-1 truncate">{{ formatRupiah(pembayaran?.pendapatan_hari_ini) }}</div>
                    <p class="text-xs text-muted">Lunas hari ini</p>
                </div>
            </div>

            <!-- ══ ROW 2: Alert Modules (3 col) ══ -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Penjemputan -->
                <Link :href="route('operator.penjemputan')"
                    class="group bg-surface border rounded-xl p-5 shadow-sm hover:shadow-md transition block"
                    :class="pickup?.belum_assign > 0 ? 'border-amber-300 hover:border-amber-400' : 'border-border'">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center">
                                <i class="fa-solid fa-motorcycle text-sm"></i>
                            </div>
                            <span class="text-sm font-bold text-text">Penjemputan</span>
                        </div>
                        <i class="fa-solid fa-arrow-right text-muted text-xs group-hover:translate-x-1 transition-transform"></i>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="bg-amber-50 rounded-lg p-2">
                            <div class="text-xl font-black text-amber-600">{{ pickup?.belum_assign ?? 0 }}</div>
                            <div class="text-[10px] text-muted mt-0.5">Belum Assign</div>
                        </div>
                        <div class="rounded-lg p-2" :class="pickup?.terlambat > 0 ? 'bg-rose-50' : 'bg-container'">
                            <div class="text-xl font-black" :class="pickup?.terlambat > 0 ? 'text-rose-600' : 'text-text'">{{ pickup?.terlambat ?? 0 }}</div>
                            <div class="text-[10px] text-muted mt-0.5">Terlambat</div>
                        </div>
                    </div>
                </Link>

                <!-- Pengantaran -->
                <Link :href="route('operator.pengantaran')"
                    class="group bg-surface border rounded-xl p-5 shadow-sm hover:shadow-md transition block"
                    :class="delivery?.belum_assign > 0 ? 'border-blue-300 hover:border-blue-400' : 'border-border'">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center">
                                <i class="fa-solid fa-truck text-sm"></i>
                            </div>
                            <span class="text-sm font-bold text-text">Pengantaran</span>
                        </div>
                        <i class="fa-solid fa-arrow-right text-muted text-xs group-hover:translate-x-1 transition-transform"></i>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="bg-blue-50 rounded-lg p-2">
                            <div class="text-xl font-black text-blue-600">{{ delivery?.belum_assign ?? 0 }}</div>
                            <div class="text-[10px] text-muted mt-0.5">Belum Assign</div>
                        </div>
                        <div class="rounded-lg p-2" :class="delivery?.terlambat > 0 ? 'bg-rose-50' : 'bg-container'">
                            <div class="text-xl font-black" :class="delivery?.terlambat > 0 ? 'text-rose-600' : 'text-text'">{{ delivery?.terlambat ?? 0 }}</div>
                            <div class="text-[10px] text-muted mt-0.5">Terlambat</div>
                        </div>
                    </div>
                </Link>

                <!-- Pembayaran -->
                <Link :href="route('operator.pembayaran')"
                    class="group bg-surface border rounded-xl p-5 shadow-sm hover:shadow-md transition block"
                    :class="pembayaran?.belum_lunas > 0 ? 'border-rose-300 hover:border-rose-400' : 'border-border'">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center">
                                <i class="fa-solid fa-money-bill-wave text-sm"></i>
                            </div>
                            <span class="text-sm font-bold text-text">Pembayaran</span>
                        </div>
                        <i class="fa-solid fa-arrow-right text-muted text-xs group-hover:translate-x-1 transition-transform"></i>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="rounded-lg p-2" :class="pembayaran?.belum_lunas > 0 ? 'bg-rose-50' : 'bg-container'">
                            <div class="text-xl font-black" :class="pembayaran?.belum_lunas > 0 ? 'text-rose-600' : 'text-text'">{{ pembayaran?.belum_lunas ?? 0 }}</div>
                            <div class="text-[10px] text-muted mt-0.5">Belum Lunas</div>
                        </div>
                        <div class="bg-emerald-50 rounded-lg p-2">
                            <div class="text-xl font-black text-emerald-600">{{ pembayaran?.lunas_hari_ini ?? 0 }}</div>
                            <div class="text-[10px] text-muted mt-0.5">Lunas Hari Ini</div>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- ══ ROW 3: Quick Actions ══ -->
            <div>
                <h2 class="text-xs font-black text-muted uppercase tracking-widest mb-3">Quick Actions</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <Link :href="route('operator.pesanan.masuk') + '?action=add'"
                        class="flex flex-col items-center gap-2 bg-primary/5 hover:bg-primary/10 border border-primary/20 rounded-xl p-4 transition text-center group">
                        <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm shadow-primary/30">
                            <i class="fa-solid fa-plus text-sm"></i>
                        </div>
                        <span class="text-xs font-bold text-primary">Tambah Pesanan</span>
                    </Link>

                    <Link :href="route('operator.penjemputan', { tab: 'belum-diassign' })"
                        class="flex flex-col items-center gap-2 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-xl p-4 transition text-center group">
                        <div class="w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm shadow-amber-300">
                            <i class="fa-solid fa-motorcycle text-sm"></i>
                        </div>
                        <span class="text-xs font-bold text-amber-700">Assign Jemput</span>
                    </Link>

                    <Link :href="route('operator.pengantaran', { tab: 'belum-diassign' })"
                        class="flex flex-col items-center gap-2 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl p-4 transition text-center group">
                        <div class="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm shadow-blue-300">
                            <i class="fa-solid fa-truck text-sm"></i>
                        </div>
                        <span class="text-xs font-bold text-blue-700">Assign Antar</span>
                    </Link>

                    <Link :href="route('operator.pembayaran', { tab: 'belum-lunas' })"
                        class="flex flex-col items-center gap-2 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl p-4 transition text-center group">
                        <div class="w-10 h-10 rounded-full bg-emerald-500 text-white flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm shadow-emerald-300">
                            <i class="fa-solid fa-check-double text-sm"></i>
                        </div>
                        <span class="text-xs font-bold text-emerald-700">Konfirmasi Bayar</span>
                    </Link>
                </div>
            </div>

            <!-- ══ ROW 4: Charts ══ -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <!-- Chart -->
                <div class="lg:col-span-3 bg-surface border border-border rounded-xl p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-black text-text">Tren 7 Hari Terakhir</h3>
                            <p class="text-xs text-muted mt-0.5">Pesanan masuk vs diselesaikan</p>
                        </div>
                        <div class="flex items-center gap-3 text-[10px] text-muted font-bold">
                            <span class="flex items-center gap-1"><span class="w-3 h-0.5 bg-primary inline-block rounded"></span> Masuk</span>
                            <span class="flex items-center gap-1"><span class="w-3 h-0.5 bg-emerald-500 inline-block rounded"></span> Selesai</span>
                        </div>
                    </div>
                    <div class="h-48">
                        <Line :data="chartData" :options="chartOptions" />
                    </div>
                </div>

                <!-- Doughnut -->
                <div class="lg:col-span-2 bg-surface border border-border rounded-xl p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-black text-text">Status Pesanan</h3>
                    </div>
                    <div class="h-48">
                        <Doughnut :data="chartStatusData" :options="doughnutOptions" />
                    </div>
                </div>
            </div>

            <!-- ══ ROW 5: Urgents ══ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Urgent Pickups -->
                <div class="bg-surface border border-border rounded-xl p-5 shadow-sm flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-black text-text">Jemput Menunggu</h3>
                        <Link :href="route('operator.penjemputan', { tab: 'belum-diassign' })"
                            class="text-[10px] font-bold text-primary hover:underline">Lihat Semua →</Link>
                    </div>
                    <div v-if="urgentPickups?.length === 0" class="flex-1 flex items-center justify-center text-sm text-muted italic text-center py-6">
                        <span>Tidak ada antrian penjemputan 🎉</span>
                    </div>
                    <div v-else class="space-y-3 flex-1">
                        <div v-for="p in urgentPickups" :key="p.id"
                            class="flex items-start gap-3 p-3 rounded-lg border border-border hover:bg-container/40 transition">
                            <div class="w-8 h-8 rounded-full shrink-0 flex items-center justify-center text-xs font-black"
                                :class="p.age_hours >= 20 ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600'">
                                <i class="fa-solid fa-motorcycle"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1 flex-wrap">
                                    <span class="text-[10px] font-mono font-bold text-primary">{{ p.invoice }}</span>
                                    <span v-if="p.age_hours >= 20" class="text-[9px] bg-rose-100 text-rose-600 px-1.5 py-0.5 rounded-full font-bold">TERLAMBAT</span>
                                </div>
                                <p class="text-xs font-bold text-text truncate">{{ p.name }}</p>
                                <p class="text-[10px] text-muted truncate">{{ p.address }}</p>
                            </div>
                            <Link :href="route('operator.penjemputan')"
                                class="shrink-0 text-[10px] font-bold bg-primary text-white px-2 py-1 rounded hover:bg-primary/90 transition">
                                Assign
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Urgent Payments -->
                <div class="bg-surface border border-border rounded-xl p-5 shadow-sm flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-black text-text">Tagihan Menunggu</h3>
                        <Link :href="route('operator.pembayaran', { tab: 'belum-lunas' })"
                            class="text-[10px] font-bold text-primary hover:underline">Lihat Semua →</Link>
                    </div>

                    <!-- Summary card -->
                    <div class="bg-gradient-to-br from-violet-50 to-primary/5 border border-primary/10 rounded-lg p-3 mb-4">
                        <p class="text-[10px] text-muted font-bold uppercase tracking-widest mb-1">Pendapatan Bulan Ini</p>
                        <p class="text-xl font-black text-primary">{{ formatRupiah(pembayaran?.pendapatan_bulan_ini) }}</p>
                        <p class="text-[10px] text-muted mt-1">{{ pembayaran?.lunas_hari_ini ?? 0 }} transaksi lunas hari ini</p>
                    </div>

                    <div v-if="!urgentPayments?.length" class="flex-1 flex items-center justify-center text-sm text-muted italic text-center py-4">
                        Tidak ada tagihan tertunggak 🎉
                    </div>
                    <div v-else class="space-y-2.5 flex-1">
                        <div v-for="p in urgentPayments" :key="p.id"
                            class="flex items-center gap-3 p-3 rounded-lg border border-rose-100 bg-rose-50/40 hover:bg-rose-50 transition">
                            <div class="w-7 h-7 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-exclamation text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-mono font-bold text-primary">{{ p.invoice }}</p>
                                <p class="text-xs font-bold text-text truncate">{{ p.name }}</p>
                                <p class="text-[10px] text-muted uppercase">{{ p.method === 'unspecified' ? 'Belum dipilih' : p.method }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-xs font-black text-rose-600">{{ formatRupiah(p.total) }}</p>
                                <p class="text-[9px] text-muted">{{ p.date }}</p>
                            </div>
                        </div>
                    </div>

                    <Link v-if="urgentPayments?.length" :href="route('operator.pembayaran', { tab: 'belum-lunas' })"
                        class="mt-3 w-full text-center text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white py-2 rounded-lg transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-money-bill-wave"></i> Proses Pembayaran
                    </Link>
                </div>
            </div>

            <!-- ══ ROW 6: Recent Orders ══ -->
            <div class="bg-surface border border-border rounded-xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-border">
                    <h3 class="text-sm font-black text-text">Pesanan Terbaru</h3>
                    <Link :href="route('operator.pesanan.masuk')" class="text-[10px] font-bold text-primary hover:underline">Lihat Semua →</Link>
                </div>
                <div v-if="!recentOrders?.length" class="text-sm text-muted italic text-center py-10">Belum ada pesanan.</div>
                <div v-else>
                    <div v-for="o in recentOrders" :key="o.id"
                        class="flex items-center gap-3 px-5 py-3 border-b border-border/60 hover:bg-container/40 transition last:border-b-0">
                        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-black text-xs shrink-0 uppercase">
                            {{ o.customer?.charAt(0) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[10px] font-mono font-bold text-primary">{{ o.invoice }}</span>
                                <span class="text-xs font-bold text-text truncate">{{ o.customer }}</span>
                            </div>
                            <p class="text-[10px] text-muted">{{ o.service }} · {{ o.date }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <span :class="getStatus(o.status).cls" class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full flex items-center gap-1">
                                <span :class="getStatus(o.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                {{ getStatus(o.status).label }}
                            </span>
                            <span class="text-[10px] font-mono font-bold text-text">{{ formatRupiah(o.total) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </DashboardLayout>
</template>
