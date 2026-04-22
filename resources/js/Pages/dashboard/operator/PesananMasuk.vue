<script setup>
import { Head, usePage, router, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';
import QrcodeVue from 'qrcode.vue';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS, Title, Tooltip, Legend,
    LineElement, LinearScale, PointElement, CategoryScale,
    Filler, ArcElement, BarElement
} from 'chart.js';
import html2pdf from 'html2pdf.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, LinearScale,
    PointElement, CategoryScale, Filler, ArcElement, BarElement);

const props = defineProps({
    orders:    Object,   
    stats:     Object,
    filters:   Object,
    chartData: Object,
    services:  Array,
    customers: Array,
});

const flash = computed(() => usePage().props.flash ?? {});

// Search / Filter
const search       = ref(props.filters?.search ?? '');
const statusFilter = ref(props.filters?.status ?? '');
const dateFilter   = ref(props.filters?.date   ?? '');

let searchTimeout = null;
watch([search, statusFilter, dateFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('operator.pesanan.masuk'), {
            search: search.value,
            status: statusFilter.value,
            date:   dateFilter.value,
        }, { preserveState: true, replace: true });
    }, 350);
});

const isChartExpanded = ref(true);
onMounted(() => {
    const saved = localStorage.getItem('operator-pesanan-chart-expanded');
    if (saved !== null) isChartExpanded.value = saved === 'true';

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'add') {
        openCreateForm();
        window.history.replaceState({}, '', window.location.pathname);
    }
});
watch(isChartExpanded, v => localStorage.setItem('operator-pesanan-chart-expanded', v));

// Modals CRUD
const showAddModal = ref(false);
const showEditModal = ref(false);

const addForm = useForm({
    user_id: '',
    service_id: '',
    delivery_type: 'jemput',
    pickup_address: '',
    pickup_date: '',
    pickup_time: '',
    laundry_notes: '',
    courier_notes: '',
    payment_preference: 'cash',
    weight: '',
    item_qty: 1,
    extra_services: [],
});

const editForm = useForm({
    id: null,
    status: 'pending',
    total_price: 0,
});

function openCreateForm() {
    addForm.reset();
    showAddModal.value = true;
}

function openEditForm(o) {
    editForm.id = o.id;
    editForm.status = o.status;
    editForm.total_price = o.total_price;
    showEditModal.value = true;
}

function submitAddForm() {
    addForm.post(route('operator.pesanan.masuk.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            addForm.reset();
        }
    });
}

function submitEditForm() {
    editForm.put(route('operator.pesanan.masuk.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        }
    });
}

function closeAddModal() {
    showAddModal.value = false;
    addForm.reset();
    addForm.clearErrors();
}

// ── Add Form Reactivity & Options ──
const deliveryOptions = [
    { value: 'antar_jemput', label: 'Antar Jemput', fee: 10000, icon: 'fa-truck' },
    { value: 'jemput',       label: 'Jemput Saja',  fee: 5000, icon: 'fa-motorcycle' },
    { value: 'antar',        label: 'Antar Saja',   fee: 5000, icon: 'fa-box-open' },
    { value: 'outlet',       label: 'Datang Sendiri', fee: 0, icon: 'fa-store' },
];

// No exact weight options needed anymore


const currentService = computed(() => props.services.find(s => s.id === addForm.service_id) || props.services[0]);
const isKgService = computed(() => ['/kg', 'kg'].includes(currentService.value?.unit?.toLowerCase() || ''));

const formatRupiah = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);

const extraOptions = computed(() => {
    const expressPrice = (currentService.value?.price || 0) * 0.5;
    return [
        { value: 'express', label: 'Express (24 Jam)', priceText: `+${formatRupiah(expressPrice)}${isKgService.value ? '/kg' : '/item'}`, priceValue: expressPrice },
        { value: 'treatment', label: 'Treatment Khusus', priceText: isKgService.value ? '+Rp 2rb/kg' : '+Rp 5rb/item', priceValue: isKgService.value ? 2000 : 5000 },
        { value: 'own_perfume', label: 'Pewangi Sendiri', priceText: 'Gratis', priceValue: 0 }
    ];
});

const totalExtraCostPerUnit = computed(() => {
    return extraOptions.value.filter(opt => addForm.extra_services.includes(opt.value)).reduce((sum, opt) => sum + opt.priceValue, 0);
});

const combinedServicePrice = computed(() => (currentService.value?.price || 0) + totalExtraCostPerUnit.value);

const estimatedDeliveryFee = computed(() => {
    const opt = deliveryOptions.find(d => d.value === addForm.delivery_type);
    return opt ? opt.fee : 0;
});

const calculatedServiceCostText = computed(() => {
    if (!isKgService.value) return formatRupiah(combinedServicePrice.value * (addForm.item_qty || 1));
    return formatRupiah(combinedServicePrice.value * (addForm.weight || 0));
});

const totalEstimatedCostText = computed(() => {
    if (!isKgService.value) return formatRupiah((combinedServicePrice.value * (addForm.item_qty || 1)) + estimatedDeliveryFee.value);
    const weightCost = combinedServicePrice.value * (addForm.weight || 0);
    return formatRupiah(weightCost + estimatedDeliveryFee.value);
});

const isAddSubmitDisabled = computed(() => {
    if (addForm.processing) return true;
    if (!addForm.user_id || !addForm.service_id) return true;
    if (['jemput', 'antar_jemput'].includes(addForm.delivery_type) && (!addForm.pickup_date || !addForm.pickup_time)) return true;
    if (isKgService.value && (!addForm.weight || addForm.weight <= 0)) return true;
    if (!isKgService.value && (!addForm.item_qty || addForm.item_qty < 1)) return true;
    return false;
});

// Modals Detail
const showDetailModal = ref(false);
const viewingOrder    = ref(null);

function openDetail(o) {
    viewingOrder.value    = o;
    showDetailModal.value = true;
}

function closeDetailModal() {
    showDetailModal.value = false;
    viewingOrder.value    = null;
    isCopied.value = false;
}

// QR & Invoice copy helpers
const isCopied = ref(false);

function copyInvoice() {
    if (!viewingOrder.value) return;
    navigator.clipboard.writeText(viewingOrder.value.invoice).then(() => {
        isCopied.value = true;
        setTimeout(() => { isCopied.value = false; }, 2000);
    }).catch(() => {});
}

function downloadQR() {
    const canvas = document.querySelector('#operatorInvoiceQR canvas');
    if (!canvas || !viewingOrder.value) return;
    const url = canvas.toDataURL('image/png');
    const link = document.createElement('a');
    link.href = url;
    link.download = `QR-${viewingOrder.value.invoice}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Detail Modal Stepper
const detailSteps = computed(() => {
    if (!viewingOrder.value) return ['Dibuat', 'Diproses', 'Selesai'];
    const steps = ['Dibuat'];
    // Assume pickup if status includes dijemput or beyond
    const hasPickup = ['dijemput', 'diproses', 'selesai', 'diantar'].includes(viewingOrder.value.status);
    if (hasPickup) steps.push('Dijemput');
    steps.push('Diproses', 'Selesai');
    const hasDelivery = viewingOrder.value.status === 'diantar';
    if (hasDelivery) steps.push('Diantar');
    return steps;
});

const detailStepIndex = computed(() => {
    if (!viewingOrder.value) return 0;
    const statusToStep = {
        pending:  0,
        dijemput: 1,
        diproses: detailSteps.value.indexOf('Diproses'),
        selesai:  detailSteps.value.indexOf('Selesai'),
        diantar:  detailSteps.value.length - 1,
    };
    return statusToStep[viewingOrder.value.status] ?? 0;
});

const STATUS_MAP = {
    pending:  { label: 'Pending',   cls: 'bg-indigo-100 text-indigo-700 border-indigo-200', dot: 'bg-indigo-500' },
    dijemput: { label: 'Dijemput',  cls: 'bg-teal-100 text-teal-700 border-teal-200',       dot: 'bg-teal-500' },
    diproses: { label: 'Diproses',  cls: 'bg-amber-100 text-amber-700 border-amber-200',    dot: 'bg-amber-500' },
    selesai:  { label: 'Selesai',   cls: 'bg-emerald-100 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500' },
    diantar:  { label: 'Diantar',   cls: 'bg-blue-100 text-blue-700 border-blue-200',       dot: 'bg-blue-500' },
};
const getStatus = (s) => STATUS_MAP[s] ?? { label: s, cls: 'bg-slate-100 text-slate-600 border-slate-200', dot: 'bg-slate-400' };

// Cetak Nota PDF
const printingId = ref(null);
async function cetakNota(o) {
    printingId.value = o.id;

    // Try to get QR if we are in detail modal, or it might be hidden in background
    let qrDataUrl = '';
    const qrCanvas = document.querySelector('#operatorInvoiceQR canvas');
    if (qrCanvas) qrDataUrl = qrCanvas.toDataURL('image/png');

    const now = new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    const html = `
    <div style="font-family: 'Courier New', Courier, monospace; max-width: 320px; margin: 0 auto; padding: 20px; background: white; color: #111;">
      <div style="text-align: center; border-bottom: 2px dashed #ddd; padding-bottom: 14px; margin-bottom: 14px;">
        <div style="font-size: 22px; font-weight: 900; letter-spacing: -1px; color: #E30613;">Hi Wash</div>
        <div style="font-size: 9px; color: #666; font-weight: bold; text-transform: uppercase; letter-spacing: 3px; margin-top: 2px;">Laundry & Dry Cleaning</div>
        <div style="font-size: 8px; color: #888; margin-top: 6px; line-height: 1.5;">Jl. Contoh No.1, Kota Anda<br/>Telp: 0812-3456-7890</div>
      </div>
      <div style="border-bottom: 1px dashed #ddd; padding-bottom: 10px; margin-bottom: 10px;">
        <div style="display: flex; justify-content: space-between; font-size: 9px; color: #555; margin-bottom: 4px;">
          <span style="font-weight: bold; text-transform: uppercase;">No. Invoice</span>
          <span style="font-weight: 900; color: #E30613;">${o.invoice}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 9px; color: #555; margin-bottom: 4px;">
          <span style="font-weight: bold; text-transform: uppercase;">Pelanggan</span>
          <span style="font-weight: bold; color: #111;">${o.customer}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 9px; color: #555;">
          <span style="font-weight: bold; text-transform: uppercase;">Dicetak</span>
          <span style="font-weight: bold; color: #111;">${now}</span>
        </div>
      </div>
      <div style="border-bottom: 1px dashed #ddd; padding-bottom: 10px; margin-bottom: 10px;">
        <div style="font-size: 8px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; color: #888; margin-bottom: 6px;">Detail Layanan</div>
        <div style="font-size: 10px; font-weight: bold; color: #111; margin-bottom: 4px;">${o.service}</div>
        ${o.pickup_address ? `<div style="font-size: 8px; color: #555; margin-top: 4px;"><span style="font-weight:bold;">Alamat Jemput:</span> ${o.pickup_address}</div>` : ''}
        ${o.laundry_notes ? `<div style="font-size: 8px; color: #E30613; margin-top: 4px; font-style: italic;">Catatan: ${o.laundry_notes}</div>` : ''}
      </div>
      <div style="border-bottom: 2px dashed #ddd; padding-bottom: 10px; margin-bottom: 10px;">
        <div style="font-size: 8px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; color: #888; margin-bottom: 6px;">Rincian Biaya</div>
        <div style="display: flex; justify-content: space-between; font-size: 10px; color: #444; margin-bottom: 4px;">
          <span>Biaya Layanan (${o.items_qty} ${o.unit.replace('/', '')})</span>
          <span style="font-weight: bold;">${formatRupiah(o.items_qty * o.service_price)}</span>
        </div>
        ${o.fee > 0 ? `
        <div style="display: flex; justify-content: space-between; font-size: 10px; color: #444; margin-bottom: 4px;">
          <span>Ongkos Kirim</span>
          <span style="font-weight: bold;">${formatRupiah(o.fee)}</span>
        </div>` : ''}
        <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 900; color: #111; margin-top: 6px; padding-top: 6px; border-top: 1px solid #eee;">
          <span>TOTAL</span>
          <span style="color: #E30613;">${formatRupiah(o.total_price)}</span>
        </div>
      </div>
      <div style="border-bottom: 1px dashed #ddd; padding-bottom: 10px; margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; font-size: 9px;">
          <span style="font-weight: bold; text-transform: uppercase; color: #555;">Metode Bayar</span>
          <span style="font-weight: bold; color: #111;">${o.payment_method ?? '-'}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 9px; margin-top: 4px;">
          <span style="font-weight: bold; text-transform: uppercase; color: #555;">Status Bayar</span>
          <span style="font-weight: 900; color: ${o.payment_status === 'berhasil' ? '#16a34a' : '#E30613'}">${o.payment_status === 'berhasil' ? 'LUNAS' : o.payment_status.toUpperCase()}</span>
        </div>
      </div>
      ${qrDataUrl ? `
      <div style="text-align: center; margin-bottom: 14px;">
        <img src="${qrDataUrl}" style="width: 80px; height: 80px;" />
        <div style="font-size: 7px; color: #888; margin-top: 4px; letter-spacing: 1px;">Scan untuk verifikasi</div>
      </div>` : ''}
      <div style="text-align: center; font-size: 8px; color: #aaa; line-height: 1.8;">
        <div style="font-weight: 900; color: #555; margin-bottom: 2px;">Terima kasih atas kepercayaan Anda!</div>
        <div>Nota ini adalah bukti pembayaran yang sah.</div>
        <div style="margin-top: 4px; letter-spacing: 2px;">★ HI WASH ★</div>
      </div>
    </div>
    `;
    const el = document.createElement('div');
    el.innerHTML = html;
    document.body.appendChild(el);
    await html2pdf().set({ margin: [5, 5], filename: `Nota-${o.invoice}.pdf`, image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2, useCORS: true, logging: false }, jsPDF: { unit: 'mm', format: [80, 200], orientation: 'portrait' } }).from(el).save();
    document.body.removeChild(el);
    printingId.value = null;
}

// Chart Configs
const tooltipDefaults = { backgroundColor: '#0A0A0B', titleFont: { family: 'Space Mono', size: 10 }, bodyFont: { family: 'Inter', size: 12, weight: 'bold' }, padding: 12, cornerRadius: 4 };
const doughnutOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { font: { family: 'Inter', size: 10, weight: 'bold' }, color: '#888' } }, tooltip: { ...tooltipDefaults, displayColors: true } }, cutout: '70%' };
const lineOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { ...tooltipDefaults, displayColors: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } }, x: { grid: { display: false }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } } } };
const barOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 10, weight: 'bold' }, color: '#888' } }, tooltip: { ...tooltipDefaults } }, scales: { x: { stacked: true, grid: { display: false }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } }, y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' }, border: { display: false }, ticks: { font: { family: 'Space Mono', size: 9 }, color: '#888' } } } };

const statCards = computed(() => [
    { label: 'Total Pesanan',   value: props.stats?.total    ?? 0, icon: 'fa-shopping-bag',  color: 'text-blue-600',   bg: 'bg-blue-500/10' },
    { label: 'Pesanan Selesai', value: props.stats?.selesai  ?? 0, icon: 'fa-circle-check',  color: 'text-emerald-600', bg: 'bg-emerald-500/10' },
    { label: 'Sedang Diproses', value: props.stats?.diproses ?? 0, icon: 'fa-spinner',       color: 'text-amber-600',  bg: 'bg-amber-500/10' },
    { label: 'Menunggu',        value: props.stats?.pending  ?? 0, icon: 'fa-clock',         color: 'text-rose-600',   bg: 'bg-rose-500/10' },
]);

const chartStatusData = computed(() => ({ labels: ['Selesai', 'Diproses', 'Pending', 'Diantar', 'Dijemput'], datasets: [{ data: props.chartData?.statusDist ?? [0,0,0,0,0], backgroundColor: ['#059669','#d97706','#6366f1','#3b82f6','#14b8a6'], borderWidth: 0, hoverOffset: 4 }] }));
const chartTrendData = computed(() => ({ labels: (props.chartData?.weeklyTrend ?? []).map(d => d.label), datasets: [{ label: 'Pesanan Masuk', data: (props.chartData?.weeklyTrend ?? []).map(d => d.value), borderColor: '#5B4CF3', backgroundColor: 'rgba(91,76,243,0.1)', fill: true, tension: 0.4, pointBackgroundColor: '#fff', pointBorderColor: '#5B4CF3', pointBorderWidth: 2, pointHoverRadius: 6 }] }));
const chartPaymentData = computed(() => ({ labels: ['Transfer Bank', 'Tunai', 'E-Wallet'], datasets: [{ data: props.chartData?.paymentMethods ?? [0,0,0], backgroundColor: ['#5B4CF3','#059669','#0891b2'], borderWidth: 0, hoverOffset: 4 }] }));
const barSvcColors = ['#3b82f6','#d97706','#8b5cf6'];
const chartServiceData = computed(() => {
    const names  = props.chartData?.serviceNames ?? [];
    const months = props.chartData?.topServices  ?? [];
    return { labels: months.map(m => m.label), datasets: names.map((name, i) => ({ label: name, data: months.map(m => m[name] ?? 0), backgroundColor: barSvcColors[i] ?? '#888', borderRadius: 4 })) };
});
</script>

<template>
    <Head title="Pesanan Masuk - Hi Wash Operator" />

    <DashboardLayout title="Pesanan Masuk">
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
                    <div class="absolute -right-2 -bottom-2 opacity-5 group-hover:opacity-10 transition-opacity"><i :class="['fa-solid', stat.icon]" class="text-6xl"></i></div>
                    <div class="flex items-center gap-4 mb-4">
                        <div :class="[stat.bg, stat.color]" class="w-10 h-10 rounded-full flex items-center justify-center text-sm"><i :class="['fa-solid', stat.icon]"></i></div>
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
                        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs"><i class="fa-solid fa-chart-pie"></i></div>
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-text">Visual Analitik - Tren Order</h3>
                    </div>
                    <div class="flex items-center gap-4">
                        <i :class="['fa-solid transition-transform duration-300', isChartExpanded ? 'fa-chevron-up' : 'fa-chevron-down text-primary']"></i>
                    </div>
                </button>
                <transition enter-active-class="transition-all duration-500 ease-out" leave-active-class="transition-all duration-300 ease-in"
                    enter-from-class="max-h-0 opacity-0" enter-to-class="max-h-[2000px] opacity-100" leave-from-class="max-h-[2000px] opacity-100" leave-to-class="max-h-0 opacity-0">
                    <div v-show="isChartExpanded" class="p-6 border-t border-dashed border-border overflow-hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-12">
                            <div class="flex flex-col space-y-4">
                                <div class="border-b border-dashed border-border pb-2"><h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Status Pesanan</h4></div>
                                <div class="h-48 md:h-56 w-full relative"><Doughnut :data="chartStatusData" :options="doughnutOptions" /></div>
                            </div>
                            <div class="flex flex-col space-y-4">
                                <div class="border-b border-dashed border-border pb-2"><h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-text">Trend Pesanan 7 Hari Terakhir</h4></div>
                                <div class="h-48 md:h-56 w-full relative"><Line :data="chartTrendData" :options="lineOptions" /></div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- Header Tabel  -->
            <div class="flex items-center justify-between pb-4 border-b border-border">
                <div class="flex flex-col">
                    <h2 class="text-xl font-black text-text tracking-tighter">Manajemen Pesanan</h2>
                    <p class="text-xs text-muted">Kelola data pesanan masuk pelanggan</p>
                </div>
                <button @click="openCreateForm"
                    class="px-5 py-2.5 bg-primary text-white text-xs font-black uppercase tracking-widest rounded-sm hover:bg-primary/90 transition-all shadow-lg shadow-primary/20 flex items-center gap-3">
                    <i class="fa-solid fa-plus"></i> <span class="hidden sm:inline">Tambah Pesanan</span>
                </button>
            </div>

            <!-- Table Filters Option -->
            <div class="space-y-4">
                <div class="flex flex-col md:flex-row gap-3 justify-between items-end">
                    <div class="w-full md:max-w-md relative group">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-muted group-focus-within:text-primary transition-colors"></i>
                        <input v-model="search" type="text" placeholder="Cari nama pelanggan atau no. invoice..."
                            class="w-full pl-12 pr-4 py-3 bg-white border border-border rounded-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm" />
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 items-end w-full md:w-auto">
                        <div class="relative w-full sm:w-auto">
                            <i class="fa-solid fa-calendar-days absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none text-xs"></i>
                            <input type="date" v-model="dateFilter" class="w-full sm:w-[175px] pl-9 pr-8 py-3 border border-border bg-surface text-text rounded-sm text-sm outline-none focus:border-primary transition" />
                        </div>
                        <select v-model="statusFilter" class="w-full sm:w-auto px-4 py-3 border border-border bg-surface text-text rounded-sm text-xs font-bold uppercase tracking-widest outline-none focus:border-primary transition">
                            <option value="">Semua Status</option>
                            <option value="pending">Pending</option>
                            <option value="dijemput">Dijemput</option>
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="diantar">Diantar</option>
                        </select>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="bg-surface border border-border rounded-sm shadow-xl overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-container/50 border-b border-border">
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">No. Invoice</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Pelanggan</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Layanan</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted">Total</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-muted text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-if="orders.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-muted italic text-sm">
                                    <i class="fa-solid fa-inbox text-2xl mb-2 block opacity-40"></i>
                                    Tidak ada pesanan.
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
                                        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs ring-1 ring-primary/20 shrink-0 uppercase">
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
                                    <span class="font-mono font-bold text-text bg-container/50 px-3 py-1.5 rounded-sm border border-border flex w-fit text-xs">
                                        {{ formatRupiah(o.total_price) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button @click="cetakNota(o)" :disabled="printingId === o.id"
                                            class="w-8 h-8 flex items-center justify-center rounded-sm border border-border text-muted hover:bg-slate-700 hover:border-slate-700 hover:text-white transition-all disabled:opacity-50" title="Cetak Nota PDF">
                                            <i v-if="printingId === o.id" class="fa-solid fa-spinner fa-spin text-xs"></i>
                                            <i v-else class="fa-solid fa-file-pdf text-xs"></i>
                                        </button>
                                        <button @click="openEditForm(o)"
                                            class="w-8 h-8 flex items-center justify-center rounded-sm border border-border text-amber-600 hover:bg-amber-600 hover:border-amber-600 hover:text-white transition-all" title="Edit Pesanan">
                                            <i class="fa-solid fa-pencil text-xs"></i>
                                        </button>
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
                                :disabled="!link.url" v-html="link.label"
                                :class="[ link.active ? 'z-10 bg-primary border-primary text-white' : 'bg-surface border-border text-muted hover:bg-container', 'relative inline-flex items-center px-3 py-1.5 border text-sm font-medium transition-colors', !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' , key === 0 ? 'rounded-l-md' : '', key === orders.links.length - 1 ? 'rounded-r-md' : '' ]"
                            ></button>
                        </template>
                    </nav>
                </div>
            </div>
        </div>

        <!-- ====== Modal: Add Form (Complex) ====== -->
        <teleport to="body">
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0 sm:scale-100" leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-4 overflow-y-auto pt-10 sm:pt-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeAddModal"></div>
                    <div class="relative w-full max-w-4xl bg-surface rounded-sm shadow-2xl overflow-hidden border border-border my-8 sm:my-0">
                        <div class="flex justify-between items-center px-6 py-4 border-b border-border bg-container/50 sticky top-0 z-10 hidden sm:flex">
                            <h2 class="text-xs font-black uppercase tracking-[0.2em] text-text flex items-center gap-3">
                                <i class="fa-solid fa-plus text-primary"></i> Tambah Pesanan Baru
                            </h2>
                            <button @click="closeAddModal" class="text-muted hover:text-text transition-colors"><i class="fa-solid fa-xmark text-lg"></i></button>
                        </div>
                        
                        <form @submit.prevent="submitAddForm" class="p-6 space-y-6 max-h-[80vh] overflow-y-auto">
                            <!-- Basic Selects -->
                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-muted">Pilih Pelanggan <span class="text-red-500">*</span></label>
                                    <select v-model="addForm.user_id" required class="w-full px-4 py-3 border border-border rounded-sm text-sm focus:border-primary outline-none transition bg-surface text-text font-bold">
                                        <option value="" disabled>-- Pilih Pelanggan --</option>
                                        <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }} ({{ c.email }})</option>
                                    </select>
                                    <p v-if="addForm.errors.user_id" class="text-[10px] text-rose-500 font-bold mt-1 shadow-sm">{{ addForm.errors.user_id }}</p>
                                </div>
                                
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-muted">Pilih Layanan <span class="text-red-500">*</span></label>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                        <button v-for="s in services" :key="s.id" type="button" @click="addForm.service_id = s.id"
                                            class="relative flex flex-col items-center justify-center p-4 rounded-sm border-2 transition-all overflow-hidden"
                                            :class="addForm.service_id === s.id ? 'border-primary bg-primary/5' : 'border-border hover:border-gray-300 bg-surface'">

                                            <div class="w-10 h-10 rounded-full mb-3 flex items-center justify-center"
                                                :class="addForm.service_id === s.id ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-container text-muted'">
                                                <i class="fa-solid fa-shirt text-lg"></i>
                                            </div>

                                            <span class="text-xs font-bold text-center leading-tight mb-1" :class="addForm.service_id === s.id ? 'text-primary' : 'text-text'">{{ s.name }}</span>
                                            <span class="text-[10px] font-bold" :class="addForm.service_id === s.id ? 'text-primary/80' : 'text-muted'">{{ formatRupiah(s.price) }}{{ s.unit }}</span>

                                            <!-- Checkmark Indicator -->
                                            <div v-if="addForm.service_id === s.id" class="absolute -top-1 -right-1 w-6 h-6 bg-primary rounded-bl-xl rounded-tr text-white flex items-center justify-center shadow-sm">
                                                <i class="fa-solid fa-check text-[10px]"></i>
                                            </div>
                                        </button>
                                    </div>
                                    <p v-if="addForm.errors.service_id" class="text-[10px] text-rose-500 font-bold mt-1 shadow-sm">{{ addForm.errors.service_id }}</p>
                                </div>
                            </div>

                            <hr class="border-border border-dashed" />

                            <!-- Logic from buat-pesanan.vue -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8" v-if="addForm.service_id">
                                
                                <!-- Left Column: Delivery details & Notes -->
                                <div class="space-y-6">
                                    <div class="space-y-3">
                                        <h3 class="text-xs font-bold text-gray-800 border-b border-border pb-2 border-dashed">Tipe Pengiriman & Alamat</h3>
                                        <div class="grid grid-cols-2 gap-3">
                                            <label v-for="opt in deliveryOptions" :key="opt.value"
                                                class="flex flex-col items-center gap-1 p-3 rounded-sm border cursor-pointer transition-all text-center"
                                                :class="addForm.delivery_type === opt.value ? 'border-primary bg-primary/10' : 'border-border bg-surface hover:bg-container/50'">
                                                <input type="radio" v-model="addForm.delivery_type" :value="opt.value" class="sr-only">
                                                <i :class="['fa-solid', opt.icon, addForm.delivery_type === opt.value ? 'text-primary' : 'text-gray-400']"></i>
                                                <span class="text-[10px] font-bold mt-1" :class="addForm.delivery_type === opt.value ? 'text-primary' : 'text-gray-600'">{{ opt.label }}</span>
                                                <span class="text-[9px] text-gray-500">Ongkir: {{ opt.fee === 0 ? 'Gratis' : formatRupiah(opt.fee) }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div v-show="['jemput', 'antar_jemput'].includes(addForm.delivery_type)" class="grid grid-cols-2 gap-4">
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-black uppercase tracking-widest text-muted">Tanggal Jemput</label>
                                            <input v-model="addForm.pickup_date" type="date" class="w-full px-4 py-2 bg-surface border border-border rounded-sm text-sm focus:border-primary outline-none transition" />
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-black uppercase tracking-widest text-muted">Jam PickUp</label>
                                            <input v-model="addForm.pickup_time" type="time" class="w-full px-4 py-2 bg-surface border border-border rounded-sm text-sm focus:border-primary outline-none transition" />
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-black uppercase tracking-widest text-muted">Alamat Pelanggan</label>
                                            <textarea v-model="addForm.pickup_address" rows="2" placeholder="Masukan alamat jemput/antar detail..."
                                                class="w-full px-4 py-2 bg-surface border border-border rounded-sm text-sm focus:border-primary outline-none transition font-medium"></textarea>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="space-y-1">
                                                <label class="text-[10px] font-black uppercase tracking-widest text-muted">Catatan Layanan <span class="lowercase tracking-normal font-normal opacity-70">(Ops)</span></label>
                                                <input v-model="addForm.laundry_notes" type="text" placeholder="Baju putih bedakan" class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-xs focus:border-primary outline-none transition" />
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-[10px] font-black uppercase tracking-widest text-muted">Catatan Kurir <span class="lowercase tracking-normal font-normal opacity-70">(Ops)</span></label>
                                                <input v-model="addForm.courier_notes" type="text" placeholder="Pagar hijau" class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-xs focus:border-primary outline-none transition" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Qty, Extras & Total -->
                                <div class="space-y-6">
                                    <div class="space-y-3">
                                        <h3 class="text-xs font-bold text-gray-800 border-b border-border pb-2 border-dashed">Detail Layanan & Ekstra</h3>
                                        
                                        <!-- Qty / Actual Weight -->
                                        <div v-if="isKgService">
                                            <label class="text-[10px] font-black uppercase tracking-widest text-muted mb-2 block">Berat Aktual (Kg) <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                <input type="number" step="0.1" min="0.1" v-model="addForm.weight" class="w-full px-4 py-2 bg-surface text-center border border-border rounded-sm text-sm font-bold text-gray-800 focus:border-primary outline-none transition" placeholder="0.0" />
                                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-muted font-bold text-xs pointer-events-none">Kg</span>
                                            </div>
                                        </div>
                                        <div v-else>
                                            <label class="text-[10px] font-black uppercase tracking-widest text-muted mb-2 block">Jumlah Item (Pcs) <span class="text-red-500">*</span></label>
                                            <div class="flex items-center gap-4">
                                                <button type="button" @click="addForm.item_qty = Math.max(1, addForm.item_qty - 1)" class="w-10 h-10 rounded-sm bg-container flex items-center justify-center font-bold text-gray-700 hover:bg-gray-300">-</button>
                                                <input type="number" v-model="addForm.item_qty" min="1" class="w-20 py-2 text-center border-border rounded-sm font-bold text-gray-800" />
                                                <button type="button" @click="addForm.item_qty++" class="w-10 h-10 rounded-sm bg-container flex items-center justify-center font-bold text-gray-700 hover:bg-gray-300">+</button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Extras -->
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-muted block">Layanan Tambahan (Opsional)</label>
                                        <div class="grid gap-2">
                                            <label v-for="opt in extraOptions" :key="opt.value"
                                                class="flex justify-between items-center p-3 rounded-sm border cursor-pointer transition-all"
                                                :class="addForm.extra_services.includes(opt.value) ? 'border-primary bg-primary/5' : 'border-border'">
                                                <div class="flex items-center gap-2">
                                                    <input type="checkbox" v-model="addForm.extra_services" :value="opt.value" class="text-primary focus:ring-primary rounded-sm">
                                                    <span class="text-xs font-bold text-gray-800">{{ opt.label }}</span>
                                                </div>
                                                <span class="text-[11px] font-bold" :class="opt.priceValue > 0 ? 'text-primary' : 'text-green-600'">{{ opt.priceText }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Payment & Summary -->
                                    <div class="space-y-3 bg-container/30 p-4 rounded-sm border border-border">
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-gray-600 font-bold uppercase tracking-widest">Biaya Layanan</span>
                                            <span class="font-mono font-bold">{{ calculatedServiceCostText }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-gray-600 font-bold uppercase tracking-widest">Biaya Ongkir</span>
                                            <span class="font-mono font-bold">{{ formatRupiah(estimatedDeliveryFee) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center pt-2 border-t border-dashed border-gray-300">
                                            <div>
                                                <span class="block text-xs font-black uppercase tracking-widest text-gray-900">Total Harga</span>
                                                <span v-if="isKgService" class="text-[9px] text-gray-500 font-mono">(Sesuai Berat)</span>
                                            </div>
                                            <span class="font-mono text-lg font-black text-primary">{{ totalEstimatedCostText }}</span>
                                        </div>
                                        <div class="pt-3 border-t border-border mt-3 space-y-2">
                                            <label class="text-[10px] font-black uppercase tracking-widest text-muted block">Pembayaran</label>
                                            <div class="flex gap-4">
                                                <label class="flex items-center gap-2 text-xs font-bold cursor-pointer">
                                                    <input type="radio" v-model="addForm.payment_preference" value="cash" class="text-primary focus:ring-primary" /> Tunai
                                                </label>
                                                <label class="flex items-center gap-2 text-xs font-bold cursor-pointer">
                                                    <input type="radio" v-model="addForm.payment_preference" value="transfer" class="text-primary focus:ring-primary" /> Transfer / QRIS
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Footer -->
                            <div class="flex justify-end gap-3 pt-6 border-t border-border mt-6">
                                <button type="button" @click="closeAddModal" class="px-6 py-2 border border-border rounded-sm text-xs font-black uppercase tracking-widest text-muted hover:text-text hover:bg-container transition-colors">
                                    Batal
                                </button>
                                <button type="submit" :disabled="isAddSubmitDisabled" class="px-6 py-2 bg-primary text-white rounded-sm text-xs font-black uppercase tracking-widest shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all disabled:opacity-50 min-w-[120px]">
                                    <span v-if="addForm.processing" class="flex items-center justify-center gap-2"><i class="fa-solid fa-spinner fa-spin"></i> Processing...</span>
                                    <span v-else>Tambah Pesanan</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </transition>
        </teleport>

        <!-- ====== Modal: Edit Form (Simple) ====== -->
        <teleport to="body">
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0 sm:scale-100" leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showEditModal = false"></div>
                    <div class="relative w-full max-w-sm bg-surface rounded-sm shadow-2xl overflow-hidden border border-border">
                        <div class="flex justify-between items-center px-6 py-4 border-b border-border bg-container/50">
                            <h2 class="text-xs font-black uppercase tracking-[0.2em] text-text flex items-center gap-3">
                                <i class="fa-solid fa-pen-to-square text-amber-600"></i> Update Pesanan
                            </h2>
                            <button @click="showEditModal = false" class="text-muted hover:text-text transition-colors"><i class="fa-solid fa-xmark text-lg"></i></button>
                        </div>
                        <form @submit.prevent="submitEditForm" class="p-6 space-y-5">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-muted">Status</label>
                                <select v-model="editForm.status" required class="w-full px-4 py-2 bg-surface border border-border rounded-sm text-sm focus:border-primary outline-none transition uppercase tracking-widest font-bold">
                                    <option value="pending">Pending</option>
                                    <option value="dijemput">Dijemput</option>
                                    <option value="diproses">Diproses</option>
                                    <option value="selesai">Selesai</option>
                                    <option value="diantar">Diantar</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-muted">Total Harga Aktual</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-muted font-bold text-sm">Rp</span>
                                    <input v-model="editForm.total_price" type="number" required class="w-full pl-10 pr-4 py-2 bg-surface border border-border rounded-sm text-sm font-mono focus:border-primary outline-none transition" />
                                </div>
                                <p class="text-[9px] text-gray-500 mt-1">Ubah manual jika berat aktual berbeda.</p>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-border mt-4">
                                <button type="button" @click="showEditModal = false" class="px-5 py-2 border border-border rounded-sm text-xs font-black uppercase tracking-widest text-muted hover:text-text transition-colors">Batal</button>
                                <button type="submit" :disabled="editForm.processing" class="px-5 py-2 bg-amber-600 text-white rounded-sm text-xs font-black uppercase tracking-widest shadow-lg hover:bg-amber-700 transition-all disabled:opacity-50 w-24">
                                    <span v-if="editForm.processing"><i class="fa-solid fa-spinner fa-spin"></i></span>
                                    <span v-else>Simpan</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </transition>
        </teleport>

        <!-- ====== Modal: Detail Order (Rich) ====== -->
        <teleport to="body">
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                <div v-if="showDetailModal && viewingOrder" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeDetailModal">
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
                    <div class="relative z-10 w-full max-w-lg bg-surface border border-border rounded-sm shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-border bg-container/40 shrink-0">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-primary/10 text-primary rounded-full flex items-center justify-center text-sm">
                                    <i class="fa-solid fa-receipt"></i>
                                </div>
                                <div>
                                    <h2 class="text-xs font-black uppercase tracking-[0.2em] text-text">Detail Pesanan</h2>
                                    <p class="font-mono text-[10px] text-primary font-bold">{{ viewingOrder.invoice }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span :class="getStatus(viewingOrder.status).cls" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border flex items-center gap-1.5">
                                    <span :class="getStatus(viewingOrder.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                    {{ getStatus(viewingOrder.status).label }}
                                </span>
                                <button @click="closeDetailModal" class="text-muted hover:text-text transition"><i class="fa-solid fa-xmark text-lg"></i></button>
                            </div>
                        </div>

                        <!-- Scrollable Body -->
                        <div class="overflow-y-auto flex-1 space-y-4 p-5 detail-scroll">

                            <!-- Progress Stepper -->
                            <div class="bg-white border border-border rounded-sm p-5">
                                <p class="text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-6">Progres Pesanan</p>
                                <div class="relative flex items-center justify-between w-full px-2">
                                    <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-gray-100 rounded-full z-0"></div>
                                    <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-emerald-500 rounded-full z-0 transition-all duration-700"
                                        :style="`width: ${(detailStepIndex / (detailSteps.length - 1)) * 100}%`"></div>
                                    <div v-for="(step, i) in detailSteps" :key="i" class="relative z-10 flex flex-col items-center gap-1">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center shadow-sm border transition-colors"
                                            :class="detailStepIndex >= i ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-white border-gray-200 text-gray-300'">
                                            <i v-if="detailStepIndex >= i" class="fa-solid fa-check text-[10px]"></i>
                                            <span v-else class="text-[10px] font-bold">{{ i + 1 }}</span>
                                        </div>
                                        <span class="text-[8px] font-black uppercase tracking-tight whitespace-nowrap mt-1"
                                            :class="detailStepIndex >= i ? 'text-emerald-500' : 'text-gray-400'">{{ step }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Customer & Service Info -->
                            <div class="bg-white border border-border rounded-sm overflow-hidden">
                                <div class="p-4 border-b border-gray-100 flex items-center gap-4 bg-gray-50/40">
                                    <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center text-sm font-black ring-1 ring-primary/20 shrink-0 uppercase">
                                        {{ viewingOrder.customer?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-black text-text text-sm">{{ viewingOrder.customer }}</p>
                                        <p class="text-[10px] text-muted">{{ viewingOrder.customer_email }}</p>
                                    </div>
                                </div>
                                <div class="p-4 border-b border-gray-100 flex items-center gap-4 bg-gray-50/10">
                                    <div class="w-10 h-10 bg-primary/5 text-primary rounded-sm border border-primary/10 flex items-center justify-center text-sm shrink-0">
                                        <i class="fa-solid fa-shirt"></i>
                                    </div>
                                    <div>
                                        <p class="font-black text-text text-sm">{{ viewingOrder.service }}</p>
                                        <p class="text-[10px] text-muted">{{ viewingOrder.date }}</p>
                                    </div>
                                </div>

                                <!-- Laundry Notes -->
                                <div v-if="viewingOrder.laundry_notes" class="p-4 border-b border-gray-100 flex items-start gap-3">
                                    <i class="fa-solid fa-clipboard-list text-muted mt-0.5 text-xs w-4 text-center shrink-0"></i>
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-muted mb-1">Catatan Pelanggan</p>
                                        <p class="text-xs font-medium text-amber-700 italic">"{{ viewingOrder.laundry_notes }}"</p>
                                    </div>
                                </div>

                                <div v-if="viewingOrder.pickup_address && viewingOrder.pickup_address !== '-'" class="p-4 flex items-start gap-3 border-b border-gray-100">
                                    <i class="fa-solid fa-map-marker-alt text-muted mt-0.5 text-xs w-4 text-center shrink-0"></i>
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-muted mb-1">Alamat Penjemputan</p>
                                        <p class="text-xs font-medium text-text">{{ viewingOrder.pickup_address }}</p>
                                    </div>
                                </div>
                                <div v-if="viewingOrder.delivery_address && viewingOrder.delivery_address !== '-'" class="p-4 flex items-start gap-3">
                                    <i class="fa-solid fa-house text-muted mt-0.5 text-xs w-4 text-center shrink-0"></i>
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-muted mb-1">Alamat Pengiriman</p>
                                        <p class="text-xs font-medium text-text">{{ viewingOrder.delivery_address }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- QR & Invoice Number -->
                            <div class="bg-white border border-border rounded-sm p-5">
                                <p class="text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-4">QR Code & Invoice</p>
                                <div class="bg-gray-50/50 p-6 rounded-xl border border-gray-100 flex flex-col items-center justify-center space-y-5">
                                    <!-- Real QR Code -->
                                    <div class="relative">
                                        <div class="p-3 bg-white rounded-2xl shadow-sm border border-gray-200">
                                            <QrcodeVue :value="viewingOrder.invoice" :size="140" level="H" />
                                        </div>
                                        <!-- Download Button -->
                                        <button @click="downloadQR"
                                            class="absolute -top-2 -right-2 w-9 h-9 bg-white border border-gray-200 rounded-full flex items-center justify-center text-muted hover:text-primary hover:border-primary shadow-sm transition-all active:scale-90"
                                            title="Download QR PNG">
                                            <i class="fa-solid fa-download text-xs"></i>
                                        </button>
                                    </div>

                                    <!-- Invoice Number + Copy -->
                                    <div class="text-center">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-muted mb-1">Nomor Invoice</p>
                                        <div class="flex items-center justify-center gap-2">
                                            <code class="font-mono font-black text-text text-sm tracking-tight">{{ viewingOrder.invoice }}</code>
                                            <button @click="copyInvoice"
                                                :class="[isCopied ? 'text-emerald-500' : 'text-muted hover:text-primary', 'transition-colors p-1']"
                                                :title="isCopied ? 'Tersalin!' : 'Salin Invoice'">
                                                <i :class="isCopied ? 'fa-solid fa-check text-xs' : 'fa-regular fa-copy text-xs'"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Hidden large QR for download -->
                                    <div class="hidden" id="operatorInvoiceQR">
                                        <QrcodeVue :value="viewingOrder.invoice" :size="500" level="H" />
                                    </div>
                                </div>
                            </div>

                            <!-- Cost Breakdown -->
                            <div class="bg-white border border-border rounded-sm p-5 space-y-3">
                                <p class="text-[10px] font-black uppercase tracking-widest text-muted border-b border-dashed border-gray-200 pb-3">Rincian Biaya</p>
                                
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-muted font-medium">
                                        Biaya Layanan
                                        <span v-if="viewingOrder.items_qty > 0" class="text-[10px] opacity-70">
                                            ({{ viewingOrder.items_qty }} {{ viewingOrder.unit.replace('/', '') }})
                                        </span>
                                    </span>
                                    <span class="font-bold text-text font-mono">{{ formatRupiah(viewingOrder.items_qty * viewingOrder.service_price) }}</span>
                                </div>

                                <div v-if="viewingOrder.fee > 0" class="flex justify-between items-center text-xs">
                                    <span class="text-muted font-medium">Ongkos Kirim</span>
                                    <span class="font-bold text-text font-mono">{{ formatRupiah(viewingOrder.fee) }}</span>
                                </div>

                                <div class="flex justify-between items-center text-xs border-t border-dashed border-gray-200 pt-3 mt-1">
                                    <span class="font-black text-text text-sm uppercase tracking-wider">Total Pembayaran</span>
                                    <span class="font-black text-primary text-lg font-mono">{{ formatRupiah(viewingOrder.total_price) }}</span>
                                </div>
                            </div>

                            <!-- Payment Info -->
                            <div class="bg-white border border-border rounded-sm p-5 space-y-3">
                                <p class="text-[10px] font-black uppercase tracking-widest text-muted border-b border-dashed border-gray-200 pb-3">Pembayaran</p>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 bg-gray-100 rounded-sm flex items-center justify-center text-muted text-sm">
                                            <i class="fa-solid fa-credit-card"></i>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-muted font-bold uppercase">Metode</p>
                                            <p class="text-sm font-black text-text uppercase">{{ viewingOrder.payment_method ?? '-' }}</p>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full border"
                                        :class="viewingOrder.payment_status === 'berhasil' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'">
                                        {{ viewingOrder.payment_status === 'berhasil' ? '✓ Lunas' : viewingOrder.payment_status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="px-5 py-4 border-t border-border bg-container/20 flex justify-between items-center gap-3 shrink-0">
                            <button @click="cetakNota(viewingOrder)" :disabled="printingId === viewingOrder.id"
                                class="flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-xs font-black uppercase tracking-widest rounded-sm hover:bg-black transition-all disabled:opacity-50">
                                <i v-if="printingId === viewingOrder.id" class="fa-solid fa-spinner fa-spin"></i>
                                <i v-else class="fa-solid fa-file-pdf"></i>
                                Cetak Nota
                            </button>
                            <div class="flex gap-2">
                                <button @click="openEditForm(viewingOrder); closeDetailModal()"
                                    class="flex items-center gap-2 px-4 py-2 bg-amber-600 text-white text-xs font-black uppercase tracking-widest rounded-sm hover:bg-amber-700 transition-all">
                                    <i class="fa-solid fa-pencil"></i> Edit
                                </button>
                                <button @click="closeDetailModal"
                                    class="px-4 py-2 border border-border rounded-sm text-xs font-black uppercase tracking-widest text-muted hover:text-text hover:bg-container transition">
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

/* Custom scrollbar for add form modal limits */
form::-webkit-scrollbar {
    width: 6px;
}
form::-webkit-scrollbar-track {
    background: transparent;
}
form::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 4px;
}
form::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

.detail-scroll::-webkit-scrollbar {
    width: 6px;
}
.detail-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.detail-scroll::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 4px;
}
.detail-scroll::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>