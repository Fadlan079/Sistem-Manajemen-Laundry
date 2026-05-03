<script setup>
import { Head, usePage, router, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch, onUnmounted } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    orders:    Object,
    stats:     Object,
    filters:   Object,
    chartData: Object,
    services:  Array,
    customers: Array,
    couriers:  Array,
});

const flash = computed(() => usePage().props.flash ?? {});

// Search / Filter
const search       = computed(() => props.filters?.search ?? '');
const statusFilter = ref(props.filters?.status ?? '');
const dateFilter   = ref(props.filters?.date   ?? '');
const reportMode   = ref(props.filters?.reportMode ?? 'harian');
const monthFilter  = ref(props.filters?.month ?? new Date().getMonth() + 1);
const yearFilter   = ref(props.filters?.year ?? new Date().getFullYear());

let searchTimeout = null;
watch([statusFilter, dateFilter, reportMode, monthFilter, yearFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('operator.pesanan.masuk'), {
            search: search.value,
            status: statusFilter.value,
            date:   dateFilter.value,
            reportMode: reportMode.value,
            month:  monthFilter.value,
            year:   yearFilter.value,
        }, { preserveState: true, replace: true });
    }, 350);
});

const groupedOrders = computed(() => {
    const groups = {};
    if (!props.orders.data) return groups;

    props.orders.data.forEach(o => {
        // Use created_at if possible for accurate grouping, fallback to o.date
        const dateObj = o.created_at ? new Date(o.created_at) : new Date();

        const monthKey = dateObj.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
        const dateKey = dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

        if (!groups[monthKey]) groups[monthKey] = {};
        if (!groups[monthKey][dateKey]) groups[monthKey][dateKey] = [];
        groups[monthKey][dateKey].push(o);
    });
    return groups;
});

const importForm = useForm({
    file: null,
});

const showImportModal = ref(false);
const importFile     = ref(null);

function exportExcel() {
    const params = {
        search: search.value,
        status: statusFilter.value,
        reportMode: reportMode.value,
        date: dateFilter.value,
        month: monthFilter.value,
        year: yearFilter.value
    };
    window.location.href = route('operator.pesanan.masuk.export', params);
}

const downloadTemplate = () => {
    window.location.href = route('operator.pesanan.masuk.template');
};

function handleImport(event) {
    const file = event.target.files[0];
    if (!file) return;

    importFile.value = file;
    showImportModal.value = true;

    // Reset input agar bisa pilih file yang sama lagi jika perlu
    event.target.value = '';
}

function confirmImport() {
    if (!importFile.value) return;

    importForm.file = importFile.value;
    importForm.post(route('operator.pesanan.masuk.import'), {
        forceFormData: true,
        onSuccess: () => {
            importForm.reset();
            showImportModal.value = false;
            importFile.value = null;
        },
        onError: (err) => {
            console.error(err);
            alert('Gagal meng-import data. Pastikan format file sesuai.');
        }
    });
}

const tabs = computed(() => [
    { id: '', name: 'Semua Status', active: statusFilter.value === '' },
    { id: 'menunggu', name: 'Menunggu', active: statusFilter.value === 'menunggu' },
    { id: 'antri', name: 'Antri', active: statusFilter.value === 'antri' },
    { id: 'dijemput', name: 'Dijemput', active: statusFilter.value === 'dijemput' },
    { id: 'diproses', name: 'Diproses', active: statusFilter.value === 'diproses' },
    { id: 'selesai', name: 'Selesai', active: statusFilter.value === 'selesai' },
    { id: 'diantar', name: 'Diantar', active: statusFilter.value === 'diantar' },
    { id: 'diterima', name: 'Diterima', active: statusFilter.value === 'diterima' },
    { id: 'dibatalkan', name: 'Dibatalkan', active: statusFilter.value === 'dibatalkan' },
]);

const selectTab = (tabId) => {
    statusFilter.value = tabId;
};

// Utility to highlight search term in text
function highlight(text, query) {
    if (!query || !text) return text;
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${escaped})`, 'gi');
    return String(text).replace(regex, '<mark class="bg-yellow-200 text-yellow-900 rounded px-0.5">$1</mark>');
}

// --- Dynamic Stats Configuration ---
const availableStats = {
    total: { label: 'Total Pesanan', icon: 'fa-shopping-bag', bg: 'bg-blue-50', text: 'text-blue-600', desc: 'Keseluruhan data' },
    selesai: { label: 'Pesanan Selesai', icon: 'fa-circle-check', bg: 'bg-emerald-50', text: 'text-emerald-600', desc: 'Berhasil diselesaikan' },
    diproses: { label: 'Sedang Diproses', icon: 'fa-spinner fa-spin-pulse', bg: 'bg-amber-50', text: 'text-amber-600', desc: 'Sedang dikerjakan' },
    menunggu: { label: 'Menunggu', icon: 'fa-clock', bg: 'bg-rose-50', text: 'text-rose-600', desc: 'Belum diproses' },
    diterima: { label: 'Pesanan Diterima', icon: 'fa-handshake', bg: 'bg-teal-50', text: 'text-teal-600', desc: 'Telah diterima' },
    antri: { label: 'Pesanan Antri', icon: 'fa-users', bg: 'bg-orange-50', text: 'text-orange-600', desc: 'Menunggu giliran' },
    dibatalkan: { label: 'Pesanan Dibatalkan', icon: 'fa-ban', bg: 'bg-red-50', text: 'text-red-600', desc: 'Dibatalkan pelanggan' },
    siap_jemput: { label: 'Siap Dijemput', icon: 'fa-motorcycle', bg: 'bg-indigo-50', text: 'text-indigo-600', desc: 'Menunggu dijemput' },
    siap_antar: { label: 'Siap Diantar', icon: 'fa-truck', bg: 'bg-purple-50', text: 'text-purple-600', desc: 'Selesai & menunggu diantar' },
};

let initialStats = ['total', 'selesai', 'diproses', 'menunggu'];
try {
    const saved = localStorage.getItem('operator_dashboard_stats');
    if (saved) {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed) && parsed.length === 4) {
            initialStats = parsed;
        }
    }
} catch (e) {}

const selectedStats = ref(initialStats);
watch(selectedStats, (newVal) => {
    localStorage.setItem('operator_dashboard_stats', JSON.stringify(newVal));
}, { deep: true });

const activeDropdown = ref(null);
const toggleDropdown = (index) => {
    activeDropdown.value = activeDropdown.value === index ? null : index;
};

const closeDropdown = (e) => {
    if (!e.target.closest('.stat-dropdown-container')) {
        activeDropdown.value = null;
    }
    if (!e.target.closest('.action-menu-container')) {
        activeMenu.value = null;
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown);
});

const selectStat = (index, key) => {
    if (!selectedStats.value.includes(key) || selectedStats.value[index] === key) {
        selectedStats.value[index] = key;
        activeDropdown.value = null;
    }
};

const activeMenu = ref(null);
const toggleMenu = (id) => {
    activeMenu.value = activeMenu.value === id ? null : id;
};
// -----------------------------------

// Tab Drag to Scroll
const tabsContainer = ref(null);
let isDown = false;
let isDragging = false;
let startX, scrollLeft;
const startDrag = (e) => {
    isDown = true;
    isDragging = false;
    startX = e.pageX - tabsContainer.value.offsetLeft;
    scrollLeft = tabsContainer.value.scrollLeft;
};
const stopDrag = () => { isDown = false; };
const doDrag = (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - tabsContainer.value.offsetLeft;
    const walk = (x - startX) * 1.5;
    if (Math.abs(walk) > 5) isDragging = true;
    tabsContainer.value.scrollLeft = scrollLeft - walk;
};
const handleTabScroll = (e) => {
    if (tabsContainer.value && e.deltaY !== 0) {
        e.preventDefault();
        tabsContainer.value.scrollLeft += e.deltaY > 0 ? 50 : -50;
    }
};
const handleTabClick = (e, tabId) => {
    if (isDragging) {
        e.preventDefault();
        return;
    }
    selectTab(tabId);
};

// Modals CRUD
const showAddModal = ref(false);
const showEditModal = ref(false);

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'add') {
        openCreateForm();
        window.history.replaceState({}, '', window.location.pathname);
    }
});

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
    actual_weight: null,
    isKg: false,
});

function openCreateForm() {
    addForm.reset();
    showAddModal.value = true;
}

function openEditForm(o) {
    const nextMap = {
        'pending': 'antri',
        'dibuat': 'antri',
        'antri': 'dijemput',
        'dijemput': 'diproses',
        'diproses': 'selesai',
        'selesai': 'diantar',
        'diantar': 'diterima'
    };
    editForm.id = o.id;
    editForm.status = nextMap[o.status] || o.status;
    editForm.total_price = o.total_price;
    editForm.isKg = o.isKg;
    editForm.actual_weight = o.isKg ? (o.actual_weight || o.items_qty || 0) : null;
    showEditModal.value = true;
}

// Courier Assignment Logic
const showCourierModal = ref(false);
const courierTargetOrder = ref(null);
const courierForm = useForm({
    courier_type: 'internal',
    courier_id: '',
    external_courier_name: '',
    external_courier_phone: '',
    status: '',
    total_price: null,
});

function openCourierAssignment(o, nextStatus) {
    courierTargetOrder.value = o;
    courierForm.reset();
    courierForm.status = nextStatus;
    showCourierModal.value = true;
}

function submitCourierAssignment() {
    // Ensure total_price is sent as it's required by the backend validation
    courierForm.total_price = courierTargetOrder.value.total_price;
    
    courierForm.put(route('operator.pesanan.masuk.update', courierTargetOrder.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCourierModal.value = false;
            courierTargetOrder.value = null;
            courierForm.reset();
        }
    });
}

// Weight Update Logic
const showWeightModal = ref(false);
const weightTargetOrder = ref(null);
const weightForm = useForm({
    actual_weight: '',
    status: '',
    total_price: null,
});

function openWeightInput(o, nextStatus) {
    weightTargetOrder.value = o;
    weightForm.actual_weight = o.actual_weight || o.items_qty || '';
    weightForm.status = nextStatus;
    showWeightModal.value = true;
}

function submitWeightInput() {
    // Ensure total_price is sent as it's required by the backend validation
    weightForm.total_price = weightTargetOrder.value.total_price;

    weightForm.put(route('operator.pesanan.masuk.update', weightTargetOrder.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showWeightModal.value = false;
            weightTargetOrder.value = null;
            weightForm.reset();
        }
    });
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

// Loading states for buttons
const updatingOrderId = ref(null);

function handleInstantUpdate(o) {
    if (o.status === 'diterima' || o.status === 'dibatalkan') return;
    if (updatingOrderId.value === o.id) return;

    const steps = getSteps(o);
    const currentLabel = statusToLabelMap[o.status] || 'Dibuat';
    const currentIndex = steps.indexOf(currentLabel);

    if (currentIndex !== -1 && currentIndex < steps.length - 1) {
        const nextLabel = steps[currentIndex + 1];
        const labelToStatus = Object.fromEntries(Object.entries(statusToLabelMap).map(([k, v]) => [v, k]));
        const nextStatus = labelToStatus[nextLabel];

        // 1. Dibuat -> Antri (Terima Pesanan)
        if (o.status === 'dibuat' && nextStatus === 'antri') {
            return performUpdate(o, { status: 'antri' });
        }

        // 2. Antri -> Dijemput (Tugaskan Kurir Pickup)
        if (o.status === 'antri' && nextStatus === 'dijemput' && o.hasPickup) {
            return openCourierAssignment(o, 'dijemput');
        }

        // 3. Antri -> Diproses (Jika tidak ada pickup, langsung mulai cuci)
        if (o.status === 'antri' && nextStatus === 'diproses' && !o.hasPickup) {
            if (o.isKg) return openWeightInput(o, 'diproses');
            return performUpdate(o, { status: 'diproses' });
        }

        // 4. Dijemput -> Diproses (Mulai Cuci)
        if (o.status === 'dijemput' && nextStatus === 'diproses') {
            // Jika kurir internal (ada courier_id), tidak perlu modal berat (input di kurir)
            // Jika kurir eksternal (tidak ada courier_id tapi ada external_name), butuh modal berat
            if (o.courier_id) {
                return performUpdate(o, { status: 'diproses' });
            } else if (o.external_courier_name || o.isKg) {
                return openWeightInput(o, 'diproses');
            }
            return performUpdate(o, { status: 'diproses' });
        }

        // 5. Diproses -> Selesai
        if (o.status === 'diproses' && nextStatus === 'selesai') {
            return performUpdate(o, { status: 'selesai' });
        }

        // 6. Selesai -> Diantar (Tugaskan Kurir Delivery)
        if (o.status === 'selesai' && nextStatus === 'diantar' && o.hasDelivery) {
            return openCourierAssignment(o, 'diantar');
        }

        // 7. Selesai -> Diterima (Jika tidak ada delivery, pelanggan ambil di outlet)
        if (o.status === 'selesai' && nextStatus === 'diterima' && !o.hasDelivery) {
            return performUpdate(o, { status: 'diterima' });
        }

        // 8. Diantar -> Diterima
        if (o.status === 'diantar' && nextStatus === 'diterima') {
            return performUpdate(o, { status: 'diterima' });
        }

        // Default fallback (should be covered by rules above)
        performUpdate(o, { status: nextStatus });
    }
}

function performUpdate(o, payload) {
    updatingOrderId.value = o.id;
    
    // Auto-fill actual weight if not provided and it exists
    if (!payload.actual_weight) {
        const weightVal = parseFloat(o.actual_weight) || parseFloat(o.items_qty) || 0;
        if (weightVal > 0) payload.actual_weight = weightVal;
    }
    
    // Ensure total_price is included
    if (!payload.total_price) payload.total_price = o.total_price;

    router.put(route('operator.pesanan.masuk.update', o.id), payload, {
        preserveScroll: true,
        onFinish: () => {
            updatingOrderId.value = null;
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

// Detail Modal Stepper (Linear Workflow)

const getSteps = (o) => {
    if (!o) return ['Dibuat', 'Antri', 'Diproses', 'Selesai', 'Diterima'];
    let s = ['Dibuat', 'Antri'];
    if (o.hasPickup) s.push('Dijemput');
    s.push('Diproses', 'Selesai');
    if (o.hasDelivery) s.push('Diantar');
    s.push('Diterima');
    return s;
};

const statusToLabelMap = {
    'pending': 'Dibuat',
    'dibuat': 'Dibuat',
    'antri': 'Antri',
    'dijemput': 'Dijemput',
    'diproses': 'Diproses',
    'selesai': 'Selesai',
    'diantar': 'Diantar',
    'diterima': 'Diterima'
};

const getOrderStepIndex = (o) => {
    if (!o) return 0;
    const steps = getSteps(o);
    const label = statusToLabelMap[o.status] || 'Dibuat';
    const idx = steps.indexOf(label);
    
    // Fallback logic for statuses that might be skipped in the UI but exist in DB
    if (idx === -1) {
        if (o.status === 'diterima') return steps.length - 1;
        if (o.status === 'selesai') return steps.indexOf('Selesai');
        return 0;
    }
    return idx;
};

const detailStepIndex = computed(() => {
    if (!viewingOrder.value) return 0;
    return getOrderStepIndex(viewingOrder.value);
});

const detailSteps = computed(() => getSteps(viewingOrder.value));

const STATUS_MAP = {
    pending:  { label: 'Pending',  cls: 'bg-slate-100 text-slate-700 border-slate-200', dot: 'bg-slate-500' },
    dibuat:   { label: 'Dibuat',   cls: 'bg-slate-100 text-slate-700 border-slate-200', dot: 'bg-slate-500' },
    antri:    { label: 'Antri',    cls: 'bg-amber-100 text-amber-700 border-amber-200', dot: 'bg-amber-500' },
    dijemput: { label: 'Dijemput',  cls: 'bg-teal-100 text-teal-700 border-teal-200',       dot: 'bg-teal-500' },
    diproses: { label: 'Diproses',  cls: 'bg-blue-100 text-blue-700 border-blue-200',       dot: 'bg-blue-500' },
    selesai:  { label: 'Selesai',   cls: 'bg-emerald-100 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500' },
    diantar:  { label: 'Diantar',   cls: 'bg-purple-100 text-purple-700 border-purple-200', dot: 'bg-purple-500' },
    diterima: { label: 'Diterima',  cls: 'bg-pink-100 text-pink-700 border-pink-200',       dot: 'bg-pink-500' },
    dibatalkan: { label: 'Batal',   cls: 'bg-rose-100 text-rose-700 border-rose-200',       dot: 'bg-rose-500' },
};

const getNextStatusLabel = (o) => {
    if (!o) return 'Update Status';
    const status = o.status;
    const hasPickup = o.hasPickup;
    const hasDelivery = o.hasDelivery;

    const nextMap = {
        'pending': 'Terima',
        'dibuat': 'Terima',
        'antri': hasPickup ? 'Tugaskan Kurir' : 'Mulai Proses',
        'dijemput': 'Mulai Proses',
        'diproses': 'Tandai Selesai',
        'selesai': hasDelivery ? 'Tugaskan Kurir' : 'Selesai Diambil',
        'diantar': 'Telah Diterima',
        'diterima': 'Pesanan Selesai'
    };
    return nextMap[status] ?? 'Update Status';
};
const getStatus = (s) => STATUS_MAP[s] ?? { label: s, cls: 'bg-slate-100 text-slate-600 border-slate-200', dot: 'bg-slate-400' };

// Cetak Nota PDF / Print
const printingId = ref(null);
const downloadingId = ref(null);

function getReceiptHtml(o) {
    let qrDataUrl = '';
    const qrCanvas = document.querySelector('#operatorInvoiceQR canvas');
    if (qrCanvas) qrDataUrl = qrCanvas.toDataURL('image/png');

    const now = new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    return `
    <div style="font-family: 'Courier New', Courier, monospace; width: 100%; max-width: 320px; margin: 0 auto; padding: 10px; background: white; color: #111;">
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
}

function printNota(o) {
    printingId.value = o.id;
    const html = getReceiptHtml(o);

    const iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.top = '-10000px';
    iframe.style.left = '-10000px';
    iframe.style.width = '320px';
    document.body.appendChild(iframe);

    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write(`
        <html>
            <head>
                <title>Print Nota ${o.invoice}</title>
                <style>
                    body {
                        margin: 0;
                        padding: 0;
                        background: #fff;
                    }a
                    @media print {
                        @page {
                            margin: 0;
                            size: 80mm 200mm;
                        }
                        body {
                            margin: 0;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                        /* Hide scrollbars for printing */
                        ::-webkit-scrollbar { display: none; }
                    }
                </style>
            </head>
            <body>${html}</body>
        </html>
    `);
    doc.close();

    iframe.onload = function() {
        setTimeout(() => {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            setTimeout(() => {
                document.body.removeChild(iframe);
                printingId.value = null;
            }, 1000);
        }, 500);
    };
}

async function unduhPDF(o) {
    downloadingId.value = o.id;
    const html = getReceiptHtml(o);

    const el = document.createElement('div');
    el.innerHTML = html;
    document.body.appendChild(el);
    const html2pdf = (await import('html2pdf.js')).default;
    await html2pdf().set({ margin: [5, 5], filename: `Nota-${o.invoice}.pdf`, image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2, useCORS: true, logging: false }, jsPDF: { unit: 'mm', format: [80, 200], orientation: 'portrait' } }).from(el).save();
    document.body.removeChild(el);
    downloadingId.value = null;
}


</script>

<template>
    <Head title="Pesanan Masuk - Hi Wash Operator" />

    <DashboardLayout title="Pesanan Masuk">
        <div class="space-y-6 pb-20">

            <!-- Minimalist Elegant FAB -->
            <button @click="openCreateForm"
                class="fixed bottom-10 right-10 w-12 h-12 bg-primary text-white rounded-full shadow-[0_10px_25px_rgba(0,0,0,0.2)] flex items-center justify-center hover:bg-primary-hover active:scale-95 transition-all z-40 group">
                <i class="fa-solid fa-plus text-lg transition-transform duration-300 group-hover:rotate-90"></i>
            </button>

            <!-- Flash -->
            <div v-if="flash.success" class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-md px-5 py-3 text-sm font-medium">
                <i class="fa-solid fa-circle-check"></i> {{ flash.success }}
            </div>
            <div v-if="flash.warning" class="flex items-center gap-3 bg-amber-50 border border-amber-200 text-amber-700 rounded-md px-5 py-3 text-sm font-medium">
                <i class="fa-solid fa-circle-exclamation"></i> {{ flash.warning }}
            </div>
            <div v-if="flash.error" class="flex items-center gap-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-md px-5 py-3 text-sm font-medium">
                <i class="fa-solid fa-circle-xmark"></i> {{ flash.error }}
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 xl:gap-6">
                <!-- Dynamic Stat Cards -->
                <div v-for="(statKey, index) in selectedStats" :key="index" class="stat-dropdown-container bg-surface border border-border rounded-lg p-5 flex flex-col shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)] hover:shadow-md transition-all relative" :class="{'z-20': activeDropdown === index, 'z-10': activeDropdown !== index}">
                    <div class="flex items-center justify-between mb-3 relative">
                        <div class="relative w-full mr-3 border-b border-dashed border-transparent hover:border-border transition-colors group/select cursor-pointer" @click="toggleDropdown(index)">
                            <div class="w-full text-sm font-bold text-muted group-hover/select:text-primary transition-colors flex items-center pr-4 truncate" :title="availableStats[statKey].label">
                                {{ availableStats[statKey].label }}
                            </div>
                            <i class="fa-solid fa-chevron-down absolute right-0 top-1/2 -translate-y-1/2 text-[9px] text-gray-300 transition-transform duration-200 group-hover/select:text-primary" :class="{ 'rotate-180': activeDropdown === index }"></i>
                        </div>

                        <!-- Custom Dropdown Menu -->
                        <transition enter-active-class="transition duration-200 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                            <div v-if="activeDropdown === index" class="absolute top-full left-0 mt-2 w-56 bg-white rounded-md shadow-lg border border-gray-100 z-50 overflow-hidden text-sm">
                                <div class="py-1 max-h-64 overflow-y-auto">
                                    <button v-for="(cfg, key) in availableStats" :key="key" @click.stop="selectStat(index, key)"
                                        class="w-full text-left px-4 py-2 flex items-center justify-between hover:bg-gray-50 transition-colors"
                                        :class="[
                                            selectedStats.includes(key) && selectedStats[index] !== key ? 'opacity-50 cursor-not-allowed bg-gray-50/50' : 'cursor-pointer',
                                            selectedStats[index] === key ? 'bg-primary/5 text-primary' : 'text-gray-700'
                                        ]"
                                        :disabled="selectedStats.includes(key) && selectedStats[index] !== key">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-fw" :class="cfg.icon"></i>
                                            <span :class="{'font-medium': selectedStats[index] === key}">{{ cfg.label }}</span>
                                        </div>
                                        <i v-if="selectedStats.includes(key)" class="fa-solid fa-check text-primary text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </transition>

                        <div class="w-8 h-8 rounded-md flex items-center justify-center shrink-0" :class="[availableStats[statKey].bg, availableStats[statKey].text]">
                            <i class="fa-solid text-sm" :class="availableStats[statKey].icon"></i>
                        </div>
                    </div>
                    <div :class="{'opacity-50': activeDropdown === index}" class="transition-opacity">
                        <p class="text-3xl font-semibold text-text">{{ stats?.[statKey] ?? 0 }}</p>
                        <p class="text-xs mt-2 font-medium" :class="availableStats[statKey].text">{{ availableStats[statKey].desc }}</p>
                    </div>
                </div>
            </div>

            <!-- Tabs & Filter -->
            <!-- Filters & Tools (2 Rows) -->
            <div class="space-y-3 mt-6">
                <!-- Row 1: Status Filters -->
                <div class="bg-surface p-2 rounded-xl border border-border shadow-sm overflow-hidden">
                    <div ref="tabsContainer" @mousedown="startDrag" @mouseleave="stopDrag" @mouseup="stopDrag" @mousemove="doDrag" @wheel="handleTabScroll"
                        class="flex items-center gap-1.5 overflow-x-auto hide-scrollbar cursor-grab active:cursor-grabbing select-none scroll-smooth">
                        <button v-for="(tab, index) in tabs" :key="index" @click="handleTabClick($event, tab.id)"
                            class="px-5 py-2.5 text-[11px] font-black uppercase tracking-widest transition-all rounded-lg flex items-center gap-2 whitespace-nowrap shrink-0 border border-transparent"
                            :class="tab.active ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-muted hover:text-primary hover:bg-primary/5 border-border/50'">
                            {{ tab.name }}
                        </button>
                    </div>
                </div>

                <!-- Row 2: Report Modes & Actions -->
                <div class="flex flex-col xl:flex-row items-center gap-4 bg-surface p-3 rounded-xl border border-border shadow-sm">
                    <!-- Report Mode Selector -->
                    <div class="flex bg-container p-1 rounded-xl border border-border w-full xl:w-auto overflow-x-auto hide-scrollbar">
                        <button v-for="mode in ['harian', 'bulanan', 'tahunan']" :key="mode"
                            @click="reportMode = mode"
                            class="flex-1 xl:flex-none px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all"
                            :class="reportMode === mode ? 'bg-white text-primary shadow-sm border border-border' : 'text-muted hover:text-text'">
                            {{ mode }}
                        </button>
                    </div>

                    <!-- Dynamic Pickers -->
                    <div class="flex items-center gap-2 w-full xl:w-auto">
                        <!-- Harian -->
                        <div v-if="reportMode === 'harian'" class="relative flex-1 xl:flex-none">
                            <input type="date" v-model="dateFilter"
                                class="w-full px-4 py-2.5 bg-container border border-border rounded-xl text-[11px] font-bold text-text focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all" />
                        </div>

                        <!-- Bulanan -->
                        <template v-if="reportMode === 'bulanan'">
                            <select v-model="monthFilter" class="flex-1 xl:flex-none px-4 py-2.5 bg-container border border-border rounded-xl text-[11px] font-bold text-text outline-none focus:ring-1 focus:ring-primary">
                                <option v-for="(m, i) in ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']" :key="i" :value="i+1">{{ m }}</option>
                            </select>
                            <select v-model="yearFilter" class="flex-1 xl:flex-none px-4 py-2.5 bg-container border border-border rounded-xl text-[11px] font-bold text-text outline-none focus:ring-1 focus:ring-primary">
                                <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </template>

                        <!-- Tahunan -->
                        <select v-if="reportMode === 'tahunan'" v-model="yearFilter" class="w-full xl:w-auto px-4 py-2.5 bg-container border border-border rounded-xl text-[11px] font-bold text-text outline-none focus:ring-1 focus:ring-primary">
                            <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>

                    <!-- Export & Import Tools -->
                    <div class="flex items-center gap-2 ml-auto w-full xl:w-auto border-t xl:border-t-0 xl:border-l border-border pt-3 xl:pt-0 xl:pl-4">
                        <button @click="downloadTemplate"
                            class="flex-1 xl:flex-none h-10 px-4 bg-container border border-border hover:bg-surface text-muted hover:text-text rounded-xl flex items-center justify-center gap-2 transition-all active:scale-95 group"
                            title="Unduh Template Excel">
                            <i class="fa-solid fa-download text-xs group-hover:text-primary transition-colors"></i>
                            <span class="text-[9px] font-black uppercase tracking-widest">Template</span>
                        </button>

                        <button @click="exportExcel"
                            class="flex-1 xl:flex-none h-10 px-5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl flex items-center justify-center gap-2 transition-all active:scale-95 shadow-lg shadow-emerald-100">
                            <i class="fa-solid fa-file-excel text-xs"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest">Export</span>
                        </button>

                        <label class="flex-1 xl:flex-none h-10 px-5 bg-blue-500 hover:bg-blue-600 text-white rounded-xl flex items-center justify-center gap-2 cursor-pointer transition-all active:scale-95 shadow-lg shadow-blue-100">
                            <i class="fa-solid fa-file-import text-xs"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest">Import</span>
                            <input type="file" class="hidden" @change="handleImport" accept=".xlsx,.xls,.csv" />
                        </label>
                    </div>
                </div>
            </div>

                <!-- List Data -->
                <div class="space-y-8 mt-6">
                    <div v-if="orders.data.length === 0" class="text-center py-12 text-muted text-sm border border-border rounded-lg bg-surface">
                        <i class="fa-solid fa-inbox text-2xl mb-2 block opacity-40"></i>
                        Tidak ada pesanan ditemukan.
                    </div>

                    <div v-for="(dates, month) in groupedOrders" :key="month" class="space-y-6">
                        <!-- Month Header -->
                        <div class="flex items-center gap-4">
                            <h2 class="text-xs font-black uppercase tracking-[0.3em] text-primary bg-primary/5 px-4 py-1.5 rounded-full border border-primary/10 shadow-sm">
                                {{ month }}
                            </h2>
                            <div class="h-px flex-1 bg-gradient-to-r from-primary/20 to-transparent"></div>
                        </div>

                        <div v-for="(items, date) in dates" :key="date" class="space-y-3">
                            <!-- Date Sub-Header -->
                            <div class="flex items-center gap-3 pl-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-muted"></div>
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-muted">{{ date }}</h3>
                            </div>

                            <template v-for="o in items" :key="o.id">
                        <div class="bg-surface border border-border rounded-xl p-4 xl:p-5 shadow-sm hover:shadow-md transition-all flex flex-col xl:flex-row xl:items-center gap-4 xl:gap-6 group"
                            :class="search && (o.customer.toLowerCase().includes(search.toLowerCase()) || o.invoice?.toLowerCase().includes(search.toLowerCase())) ? 'border-yellow-300 ring-1 ring-yellow-300' : 'border-border'">

                            <!-- Left: Order Identity -->
                            <div class="flex items-start gap-3 w-full xl:w-[200px] shrink-0">
                                <div class="w-9 h-9 rounded-lg bg-container flex items-center justify-center text-muted shrink-0 group-hover:bg-primary/5 group-hover:text-primary transition-colors">
                                    <i class="fa-solid fa-basket-shopping text-xs"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between xl:justify-start gap-1.5 mb-0.5 flex-wrap">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[9px] font-black text-primary font-mono tracking-tight" v-html="highlight(o.invoice, search)"></span>
                                            <span v-if="o.status === 'dibuat' || o.status === 'menunggu'" class="px-1.5 py-0.5 bg-red-100 text-[#E30613] text-[7px] font-black uppercase rounded">Baru</span>
                                        </div>
                                        <span :class="getStatus(o.status).cls" class="px-1.5 py-0.5 rounded text-[7px] font-black uppercase tracking-widest border border-current xl:hidden">
                                            {{ getStatus(o.status).label }}
                                        </span>
                                    </div>
                                    <h3 class="font-black text-xs xl:text-sm text-text truncate uppercase tracking-tight" v-html="highlight(o.customer, search)"></h3>
                                    <div class="flex items-center gap-1 text-[9px] text-muted mt-0.5">
                                        <i class="fa-solid fa-location-dot text-[7px]"></i>
                                        <p class="truncate">{{ o.pickup_address }}</p>
                                    </div>
                                </div>
                            </div>

                        <!-- Center: Progress Visual -->
                        <div class="flex flex-col items-center justify-center flex-1 w-full xl:w-auto px-2 xl:px-4 xl:border-l xl:border-r border-border min-w-0 py-4 xl:py-0 border-y xl:border-y-0 border-dashed">
                            <!-- Current Step Badge (Center Top) -->
                            <div class="hidden xl:block mb-3 text-[9px] font-black uppercase tracking-[0.2em] text-primary bg-primary/5 px-3 py-0.5 rounded-full border border-primary/20 shadow-sm">
                                {{ getStatus(o.status).label }}
                            </div>

                            <div class="relative w-full max-w-[450px]">
                                <!-- Line background -->
                                <div class="absolute top-2 left-0 right-0 h-[3px] bg-gray-100 rounded-full"></div>
                                <!-- Line active -->
                                <div class="absolute top-2 left-0 h-[3px] bg-primary rounded-full transition-all duration-1000 shadow-[0_0_8px_rgba(227,6,19,0.3)]"
                                    :style="{ width: `${Math.min(100, (getOrderStepIndex(o) / (getSteps(o).length - 1)) * 100)}%` }">
                                </div>

                                <!-- Steps Dots & Labels -->
                                <div class="relative flex justify-between">
                                    <div v-for="(step, i) in getSteps(o)" :key="i" class="flex flex-col items-center group/step">
                                        <div :class="[
                                            'w-4 h-4 rounded-full border-2 transition-all duration-500 relative z-10 flex items-center justify-center',
                                            getOrderStepIndex(o) >= i ? 'bg-primary border-primary scale-110 shadow-[0_0_10px_rgba(227,6,19,0.4)]' : 'bg-white border-gray-200'
                                        ]">
                                            <i v-if="getOrderStepIndex(o) > i" class="fa-solid fa-check text-[7px] text-white"></i>
                                            <div v-if="getOrderStepIndex(o) === i" class="absolute inset-0 rounded-full animate-ping bg-primary opacity-30"></div>
                                        </div>
                                        <!-- Step Labels -->
                                        <div :class="[
                                            'text-[6px] font-black uppercase mt-2 tracking-widest transition-colors duration-500 text-center',
                                            getOrderStepIndex(o) >= i ? 'text-primary' : 'text-gray-300'
                                        ]">
                                            {{ step }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Value & Workflow Action -->
                        <div class="flex items-center justify-between xl:justify-end gap-3 xl:gap-4 w-full xl:w-[180px] shrink-0">
                            <div class="text-left xl:text-right shrink-0">
                                <p class="text-[8px] font-black text-muted uppercase tracking-[0.2em] leading-none mb-1">Total Biaya</p>
                                <p class="text-[11px] xl:text-sm font-black text-primary font-mono leading-none tracking-tighter">{{ formatRupiah(o.total_price) }}</p>
                            </div>

                            <div class="flex items-center gap-1.5">
                                 <button @click="handleInstantUpdate(o)"
                                    class="h-8 px-3 bg-primary text-white text-[9px] font-black uppercase tracking-widest rounded shadow-lg shadow-primary/20 hover:bg-black hover:shadow-xl active:scale-95 disabled:opacity-50 transition-all flex items-center justify-center gap-1.5 min-w-[100px]"
                                    :disabled="o.status === 'diterima' || o.status === 'dibatalkan' || updatingOrderId === o.id">
                                    <template v-if="updatingOrderId === o.id">
                                        <i class="fa-solid fa-spinner fa-spin text-[8px]"></i>
                                        <span>Proses...</span>
                                    </template>
                                    <template v-else>
                                        {{ getNextStatusLabel(o) }}
                                        <i class="fa-solid fa-chevron-right text-[7px] opacity-70"></i>
                                    </template>
                                </button>
                                <div class="relative action-menu-container z-20">
                                    <button @click="toggleMenu(o.id)" :class="activeMenu === o.id ? 'text-primary border-primary/50 bg-primary/5' : 'text-muted border-border hover:text-primary hover:border-primary/30'" class="w-9 h-9 flex items-center justify-center transition-colors border rounded-lg">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 scale-95 translate-y-2" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100 scale-100 translate-y-0" leave-to-class="opacity-0 scale-95 translate-y-2">
                                        <div v-show="activeMenu === o.id" class="absolute right-0 bottom-full mb-2 w-48 bg-white border border-border rounded-lg shadow-2xl py-1 origin-bottom-right">
                                            <button @click="openDetail(o); toggleMenu(o.id)" class="w-full px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <i class="fa-solid fa-eye w-4 text-gray-400"></i> Lihat Detail
                                            </button>
                                            <button @click="printNota(o); toggleMenu(o.id)" class="w-full px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <i class="fa-solid fa-print w-4 text-gray-400"></i> Cetak Nota
                                            </button>
                                            <button @click="unduhPDF(o); toggleMenu(o.id)" class="w-full px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <i class="fa-solid fa-file-pdf w-4 text-gray-400"></i> Unduh PDF
                                            </button>
                                            <button @click="openEditForm(o); toggleMenu(o.id)" class="w-full px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <i class="fa-solid fa-pen-to-square w-4 text-gray-400"></i> Ubah Data (Manual)
                                            </button>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </div>
                    </div>
                            </template>
                        </div>
                    </div>
                </div>

            <!-- Pagination -->
            <div v-if="orders.links && orders.links.length > 3" class="mt-6 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <template v-for="(link, key) in orders.links" :key="key">
                        <button
                            @click="link.url && router.get(link.url, { search: search, status: statusFilter, date: dateFilter, reportMode: reportMode, month: monthFilter, year: yearFilter }, { preserveState: true, preserveScroll: true })"
                            :disabled="!link.url" v-html="link.label"
                            :class="[ link.active ? 'z-10 bg-primary border-primary text-white' : 'bg-surface border-border text-muted hover:bg-container', 'relative inline-flex items-center px-3 py-1.5 border text-sm font-medium transition-colors', !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' , key === 0 ? 'rounded-l-md' : '', key === orders.links.length - 1 ? 'rounded-r-md' : '' ]"
                        ></button>
                    </template>
                </nav>
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
                                    <option value="dibuat">Dibuat</option>
                                    <option value="antri">Antri</option>
                                    <option value="dijemput">Dijemput</option>
                                    <option value="diproses">Diproses</option>
                                    <option value="selesai">Selesai</option>
                                    <option value="diantar">Diantar</option>
                                    <option value="diterima">Diterima</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-muted">Total Harga Aktual</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-muted font-bold text-sm">Rp</span>
                                    <input v-model="editForm.total_price" type="number" required class="w-full pl-10 pr-4 py-2 bg-surface border border-border rounded-sm text-sm font-mono focus:border-primary outline-none transition" />
                                </div>
                                <p class="text-[9px] text-gray-500 mt-1">Ubah manual jika harga berbeda.</p>
                            </div>
                            <div class="space-y-1" v-if="editForm.isKg">
                                <label class="text-[10px] font-black uppercase tracking-widest text-muted">Berat Aktual (Kg)</label>
                                <div class="relative">
                                    <input v-model="editForm.actual_weight" type="number" step="0.1" min="0.1" required class="w-full px-4 pr-10 py-2 bg-surface border border-border rounded-sm text-sm font-bold focus:border-primary outline-none transition" />
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-muted font-bold text-xs">Kg</span>
                                </div>
                                <p class="text-[9px] text-gray-500 mt-1">Isi berat pasti pakaian setelah ditimbang.</p>
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

        <!-- ====== Modal: Tugaskan Kurir ====== -->
        <teleport to="body">
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                <div v-if="showCourierModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showCourierModal = false"></div>
                    <div class="relative w-full max-w-md bg-surface rounded-sm shadow-2xl overflow-hidden border border-border">
                        <div class="flex justify-between items-center px-6 py-4 border-b border-border bg-container/50">
                            <h2 class="text-xs font-black uppercase tracking-[0.2em] text-text flex items-center gap-3">
                                <i class="fa-solid fa-motorcycle text-primary"></i> Penugasan Kurir
                            </h2>
                            <button @click="showCourierModal = false" class="text-muted hover:text-text transition-colors"><i class="fa-solid fa-xmark text-lg"></i></button>
                        </div>
                        <form @submit.prevent="submitCourierAssignment" class="p-6 space-y-6">
                            <!-- Type Selector -->
                            <div class="flex p-1 bg-container rounded-lg border border-border">
                                <button type="button" @click="courierForm.courier_type = 'internal'"
                                    class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-md transition-all"
                                    :class="courierForm.courier_type === 'internal' ? 'bg-white text-primary shadow-sm border border-border' : 'text-muted'">
                                    Kurir Internal
                                </button>
                                <button type="button" @click="courierForm.courier_type = 'external'"
                                    class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-md transition-all"
                                    :class="courierForm.courier_type === 'external' ? 'bg-white text-primary shadow-sm border border-border' : 'text-muted'">
                                    Kurir Eksternal
                                </button>
                            </div>

                            <!-- Internal Courier Select -->
                            <div v-if="courierForm.courier_type === 'internal'" class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-muted">Pilih Kurir HiWash</label>
                                <select v-model="courierForm.courier_id" required class="w-full px-4 py-3 border border-border rounded-sm text-sm focus:border-primary outline-none transition font-bold">
                                    <option value="" disabled>-- Pilih Nama Kurir --</option>
                                    <option v-for="c in couriers" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>

                            <!-- External Courier Inputs -->
                            <div v-if="courierForm.courier_type === 'external'" class="space-y-4">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-muted">Nama Kurir (Grab/Gojek/Lainnya)</label>
                                    <input v-model="courierForm.external_courier_name" type="text" required placeholder="Contoh: Budi - Grab"
                                        class="w-full px-4 py-3 border border-border rounded-sm text-sm focus:border-primary outline-none transition font-bold" />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-muted">No. Telepon / WA Kurir</label>
                                    <input v-model="courierForm.external_courier_phone" type="text" required placeholder="0812..."
                                        class="w-full px-4 py-3 border border-border rounded-sm text-sm focus:border-primary outline-none transition font-bold" />
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-border">
                                <button type="button" @click="showCourierModal = false" class="px-5 py-2 border border-border rounded-sm text-xs font-black uppercase tracking-widest text-muted hover:text-text transition-colors">Batal</button>
                                <button type="submit" :disabled="courierForm.processing" class="px-6 py-2 bg-primary text-white rounded-sm text-xs font-black uppercase tracking-widest shadow-lg hover:bg-black transition-all disabled:opacity-50 min-w-[120px]">
                                    <span v-if="courierForm.processing"><i class="fa-solid fa-spinner fa-spin"></i></span>
                                    <span v-else>Tugaskan Sekarang</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </transition>
        </teleport>

        <!-- ====== Modal: Input Berat Aktual ====== -->
        <teleport to="body">
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                <div v-if="showWeightModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showWeightModal = false"></div>
                    <div class="relative w-full max-w-sm bg-surface rounded-sm shadow-2xl overflow-hidden border border-border">
                        <div class="flex justify-between items-center px-6 py-4 border-b border-border bg-container/50">
                            <h2 class="text-xs font-black uppercase tracking-[0.2em] text-text flex items-center gap-3">
                                <i class="fa-solid fa-weight-hanging text-blue-600"></i> Berat Aktual Pakaian
                            </h2>
                            <button @click="showWeightModal = false" class="text-muted hover:text-text transition-colors"><i class="fa-solid fa-xmark text-lg"></i></button>
                        </div>
                        <form @submit.prevent="submitWeightInput" class="p-6 space-y-6">
                            <div class="bg-blue-50 border border-blue-100 p-3 rounded-lg flex items-start gap-3">
                                <i class="fa-solid fa-circle-info text-blue-500 text-xs mt-1"></i>
                                <p class="text-[10px] text-blue-700 leading-relaxed font-medium">Pakaian sudah dijemput kurir. Harap timbang dan masukkan berat pasti sebelum memulai proses pencucian.</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-muted">Berat (Kg) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input v-model="weightForm.actual_weight" type="number" step="0.1" min="0.1" required autofocus
                                        class="w-full px-4 py-4 bg-surface text-center border border-border rounded-sm text-xl font-black text-gray-800 focus:border-primary outline-none transition" placeholder="0.0" />
                                    <span class="absolute right-6 top-1/2 -translate-y-1/2 text-muted font-black text-sm pointer-events-none">Kg</span>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-border">
                                <button type="button" @click="showWeightModal = false" class="px-5 py-2 border border-border rounded-sm text-xs font-black uppercase tracking-widest text-muted hover:text-text transition-colors">Batal</button>
                                <button type="submit" :disabled="weightForm.processing" class="px-6 py-2 bg-blue-600 text-white rounded-sm text-xs font-black uppercase tracking-widest shadow-lg hover:bg-blue-700 transition-all disabled:opacity-50 min-w-[120px]">
                                    <span v-if="weightForm.processing"><i class="fa-solid fa-spinner fa-spin"></i></span>
                                    <span v-else>Mulai Proses</span>
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
                        <div class="overflow-y-auto flex-1 space-y-5 p-5 detail-scroll bg-[#F9FAFB]">

                            <!-- Stepper Design -->
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-2">
                                <div class="flex items-center justify-between relative px-2">
                                    <!-- Progress Line Background -->
                                    <div class="absolute top-4 left-0 right-0 h-0.5 bg-gray-200 -z-0 mx-6 sm:mx-10">
                                        <!-- Active Progress Line -->
                                        <div
                                            class="h-full bg-[#E30613] transition-all duration-500 ease-in-out"
                                            :style="{ width: `${Math.min(100, (detailStepIndex / (detailSteps.length - 1)) * 100)}%` }"
                                        ></div>
                                    </div>

                                    <div v-for="(step, index) in detailSteps" :key="index" class="relative z-10 flex flex-col items-center">
                                        <div
                                            class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-black transition-all duration-500 border-2"
                                            :class="[
                                                detailStepIndex === index ? 'bg-white border-[#E30613] text-[#E30613] scale-110 shadow-lg' :
                                                detailStepIndex > index ? 'bg-[#E30613] border-[#E30613] text-white' :
                                                'bg-gray-100 border-gray-200 text-gray-400'
                                            ]"
                                        >
                                            <i v-if="detailStepIndex > index" class="fas fa-check"></i>
                                            <span v-else>{{ index + 1 }}</span>
                                        </div>
                                        <span
                                            class="text-[8px] font-black mt-2 uppercase tracking-tight text-center max-w-[60px] leading-tight transition-colors duration-300"
                                            :class="detailStepIndex >= index ? 'text-[#E30613]' : 'text-gray-400'"
                                        >
                                            {{ step }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Badge Section -->
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center justify-center gap-4 overflow-hidden relative">
                                <div v-if="viewingOrder.status === 'dibuat' || viewingOrder.status === 'pending'" class="flex flex-col items-center gap-2">
                                    <div class="flex items-center gap-2 bg-blue-50 text-blue-600 font-bold py-1.5 px-4 rounded-full border border-blue-100 text-[10px] uppercase tracking-widest">
                                        <i class="fas fa-info-circle"></i> Pesanan Berhasil Dibuat
                                    </div>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Menunggu Konfirmasi Admin</p>
                                </div>
                                <div v-else-if="viewingOrder.status === 'antri'" class="flex flex-col items-center gap-2">
                                    <div class="flex items-center gap-2 bg-amber-50 text-amber-600 font-bold py-1.5 px-4 rounded-full border border-amber-100 text-[10px] uppercase tracking-widest">
                                        <i class="fas fa-hourglass-half"></i> Pesanan Masuk Antrean
                                    </div>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest text-center px-4">Operator telah menerima pesanan</p>
                                </div>
                                <div v-else-if="viewingOrder.status === 'dijemput'" class="flex items-center gap-2 bg-emerald-50 text-emerald-600 font-bold py-1.5 px-4 rounded-full border border-emerald-100 text-[10px] uppercase tracking-widest">
                                    <i class="fas fa-motorcycle animate-pulse"></i> Kurir Sedang Di Jalan
                                </div>
                                <div v-else-if="viewingOrder.status === 'diproses'" class="flex items-center gap-2 bg-blue-50 text-blue-600 font-bold py-1.5 px-4 rounded-full border border-blue-100 text-[10px] uppercase tracking-widest">
                                    <i class="fas fa-tshirt animate-bounce"></i> Pakaian Sedang Diproses
                                </div>
                                <div v-else-if="viewingOrder.status === 'selesai' && viewingOrder.payment_status !== 'berhasil'" class="flex flex-col items-center gap-2">
                                    <div class="flex items-center gap-2 bg-yellow-50 text-yellow-600 font-bold py-1.5 px-4 rounded-full border border-yellow-100 text-[10px] uppercase tracking-widest">
                                        <i class="fas fa-exclamation-circle animate-pulse"></i> Menunggu Pembayaran
                                    </div>
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Tagihan Belum Lunas</p>
                                </div>
                                <div v-else-if="viewingOrder.status === 'selesai' && viewingOrder.payment_status === 'berhasil'" class="flex items-center gap-2 bg-emerald-50 text-emerald-600 font-bold py-1.5 px-4 rounded-full border border-emerald-100 text-[10px] uppercase tracking-widest">
                                    <i class="fas fa-box"></i> Pesanan Siap Diantar / Diambil
                                </div>
                                <div v-else-if="viewingOrder.status === 'diantar'" class="flex items-center gap-2 bg-purple-50 text-purple-600 font-bold py-1.5 px-4 rounded-full border border-purple-100 text-[10px] uppercase tracking-widest">
                                    <i class="fas fa-motorcycle animate-pulse"></i> Kurir Sedang Mengantar
                                </div>
                                <div v-else-if="viewingOrder.status === 'diterima'" class="flex items-center gap-2 bg-[#E30613]/10 text-[#E30613] font-bold py-1.5 px-4 rounded-full border border-[#E30613]/20 text-[10px] uppercase tracking-widest">
                                    <i class="fas fa-check-double"></i> Pesanan Telah Diterima
                                </div>
                                <div v-else-if="viewingOrder.status === 'dibatalkan'" class="flex items-center gap-2 bg-red-50 text-red-600 font-bold py-1.5 px-4 rounded-full border border-red-100 text-[10px] uppercase tracking-widest">
                                    <i class="fas fa-times-circle"></i> Pesanan Dibatalkan
                                </div>
                            </div>

                            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                <!-- Service Header -->
                                <div class="p-5 border-b border-gray-100 bg-gray-50/30">
                                    <div class="flex items-start gap-4">
                                        <div class="w-12 h-12 bg-[#E30613] text-white rounded-xl shadow-lg shadow-red-500/20 flex items-center justify-center text-xl shrink-0">
                                            <i class="fas fa-tshirt"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <span v-if="viewingOrder.isKg && (!viewingOrder.actual_weight || viewingOrder.actual_weight <= 0)"
                                                class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-widest text-blue-600 bg-blue-50 border border-blue-100 px-2 py-0.5 rounded-full mb-1.5">
                                                <i class="fas fa-clock"></i> Menunggu Penimbangan
                                            </span>
                                            <span v-else-if="viewingOrder.isKg && viewingOrder.actual_weight > 0"
                                                class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full mb-1.5">
                                                <i class="fas fa-check"></i> Sudah Ditimbang
                                            </span>
                                            <span v-else
                                                class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-widest text-gray-500 bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-full mb-1.5">
                                                <i class="fas fa-box"></i> Per Satuan / Item
                                            </span>

                                            <!-- Info rows -->
                                            <div class="space-y-1.5 mt-1">
                                                <div class="flex items-baseline gap-2">
                                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest shrink-0 w-28">Jenis Layanan</span>
                                                    <span class="text-[13px] font-black text-gray-900 leading-tight">{{ viewingOrder.service }}</span>
                                                </div>

                                                <div v-if="viewingOrder.isKg" class="flex items-baseline gap-2">
                                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest shrink-0 w-28">
                                                        {{ viewingOrder.actual_weight > 0 ? 'Berat Aktual' : 'Estimasi Berat' }}
                                                    </span>
                                                    <span v-if="viewingOrder.actual_weight > 0" class="text-[13px] font-black text-[#E30613]">
                                                        {{ viewingOrder.actual_weight }} kg
                                                    </span>
                                                    <span v-else-if="viewingOrder.estimated_weight" class="text-[13px] font-bold text-gray-700">
                                                        {{ viewingOrder.estimated_weight }}
                                                    </span>
                                                    <span v-else class="text-[12px] text-gray-400 italic">Belum ditentukan</span>
                                                </div>

                                                <div v-else-if="viewingOrder.items_qty > 0" class="flex items-baseline gap-2">
                                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest shrink-0 w-28">Jumlah</span>
                                                    <span class="text-[13px] font-black text-gray-900">{{ viewingOrder.items_qty }} {{ (viewingOrder.unit || '').replace('/', '') }}</span>
                                                </div>

                                                <div v-if="viewingOrder.extras && viewingOrder.extras.length > 0" class="flex items-start gap-2">
                                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest shrink-0 w-28 mt-0.5">Layanan Ekstra</span>
                                                    <div class="flex flex-wrap gap-1">
                                                        <span v-for="extra in viewingOrder.extras" :key="extra.type"
                                                            class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full border text-[#E30613] bg-red-50 border-red-100">
                                                            <i class="fas fa-plus"></i> {{ extra.label }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-5 space-y-4">
                                    <div class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                                        <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center shrink-0">
                                            <i class="fas fa-user text-gray-400 text-sm"></i>
                                        </div>
                                        <div class="flex-1">
                                            <span class="font-black text-gray-400 text-[10px] uppercase tracking-widest block mb-1">Pelanggan</span>
                                            <span class="font-bold text-gray-700 block text-xs">{{ viewingOrder.customer }}</span>
                                            <span class="text-gray-500 block text-[10px] mt-0.5">{{ viewingOrder.customer_email }}</span>
                                        </div>
                                    </div>

                                    <div v-if="viewingOrder.laundry_notes" class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                                        <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center shrink-0">
                                            <i class="fas fa-clipboard-list text-gray-400 text-sm"></i>
                                        </div>
                                        <div class="flex-1">
                                            <span class="font-black text-gray-400 text-[10px] uppercase tracking-widest block mb-1">Catatan Layanan</span>
                                            <span class="font-bold text-gray-700 block leading-relaxed italic text-xs">"{{ viewingOrder.laundry_notes }}"</span>
                                        </div>
                                    </div>

                                    <div v-if="viewingOrder.pickup_address && viewingOrder.pickup_address !== '-'" class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                                        <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center shrink-0">
                                            <i class="fas fa-map-marker-alt text-gray-400 text-sm"></i>
                                        </div>
                                        <div class="flex-1">
                                            <span class="font-black text-gray-400 text-[10px] uppercase tracking-widest block mb-1">Alamat Penjemputan</span>
                                            <span class="font-bold text-gray-700 block leading-relaxed text-xs">{{ viewingOrder.pickup_address }}</span>
                                        </div>
                                    </div>

                                    <div v-if="viewingOrder.delivery_address && viewingOrder.delivery_address !== '-'" class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                                        <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center shrink-0">
                                            <i class="fas fa-house text-gray-400 text-sm"></i>
                                        </div>
                                        <div class="flex-1">
                                            <span class="font-black text-gray-400 text-[10px] uppercase tracking-widest block mb-1">Alamat Pengiriman</span>
                                            <span class="font-bold text-gray-700 block leading-relaxed text-xs">{{ viewingOrder.delivery_address }}</span>
                                        </div>
                                    </div>

                                    <div class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                                        <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center shrink-0">
                                            <i class="fas fa-user-tie text-gray-400 text-sm"></i>
                                        </div>
                                        <div class="flex-1">
                                            <span class="font-black text-gray-400 text-[10px] uppercase tracking-widest block mb-1">Petugas / Operator</span>
                                            <span class="font-bold text-gray-700 block text-xs">{{ viewingOrder.operator_name }}</span>
                                        </div>
                                    </div>

                                    <div class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                                        <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center shrink-0">
                                            <i class="fas fa-calendar-alt text-gray-400 text-sm"></i>
                                        </div>
                                        <div>
                                            <span class="font-black text-gray-400 text-[10px] uppercase tracking-widest block mb-1">Tanggal Pesanan</span>
                                            <span class="font-bold text-gray-700 text-xs">{{ viewingOrder.date }}</span>
                                        </div>
                                    </div>

                                    <!-- QR & Invoice Number -->
                                    <div class="bg-gray-50/50 p-8 rounded-2xl border border-gray-100 flex flex-col items-center justify-center space-y-5">
                                        <div class="relative">
                                            <div class="p-3 bg-white rounded-2xl shadow-sm border border-gray-200">
                                                <QrcodeVue :value="viewingOrder.invoice" :size="140" level="H" />
                                            </div>
                                            <button @click="downloadQR"
                                                class="absolute -top-2 -right-2 w-9 h-9 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-400 hover:text-[#E30613] hover:border-[#E30613] shadow-sm transition-all active:scale-90"
                                                title="Download QR">
                                                <i class="fas fa-download text-xs"></i>
                                            </button>
                                        </div>

                                        <div class="text-center">
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Nomor Invoice</p>
                                            <div class="flex items-center justify-center gap-2">
                                                <code class="text-sm font-black text-gray-900 tracking-tight">{{ viewingOrder.invoice }}</code>
                                                <button @click="copyInvoice"
                                                    :class="[isCopied ? 'text-green-500' : 'text-gray-400 hover:text-[#E30613]', 'transition-colors p-1']"
                                                    :title="isCopied ? 'Tersalin!' : 'Salin Invoice'">
                                                    <i :class="isCopied ? 'fas fa-check' : 'far fa-copy text-[10px]'"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="hidden" id="operatorInvoiceQR">
                                            <QrcodeVue :value="viewingOrder.invoice" :size="500" level="H" />
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-3 border-t-2 border-dashed border-gray-200 space-y-3">
                                        <h3 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-3">Rincian Biaya</h3>

                                        <div class="flex justify-between text-xs font-bold">
                                            <span class="text-gray-500 uppercase tracking-tighter">
                                                Biaya Layanan
                                                <span v-if="viewingOrder.isKg && viewingOrder.actual_weight > 0" class="text-xs text-primary font-black ml-1">({{ viewingOrder.actual_weight }} kg)</span>
                                                <span v-else-if="!viewingOrder.isKg && viewingOrder.items_qty > 0" class="text-gray-300">({{ viewingOrder.items_qty }} {{ (viewingOrder.unit || '').replace('/', '') }})</span>
                                            </span>
                                            <span class="text-gray-900">{{ formatRupiah((viewingOrder.actual_weight || viewingOrder.items_qty || 1) * viewingOrder.service_price) }}</span>
                                        </div>

                                        <div class="text-[9px] text-gray-400 -mt-2 font-black uppercase tracking-widest">
                                            {{ formatRupiah(viewingOrder.service_price) }}{{ viewingOrder.unit }}
                                        </div>

                                        <div v-if="viewingOrder.fee > 0" class="flex justify-between text-xs font-bold pt-1">
                                            <span class="text-gray-500 uppercase tracking-tighter">Ongkos Kirim</span>
                                            <span class="text-gray-900">{{ formatRupiah(viewingOrder.fee) }}</span>
                                        </div>

                                        <div class="flex justify-between items-end pt-4 mt-2 border-t border-gray-100">
                                            <div>
                                                <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Biaya</span>
                                                <span v-if="viewingOrder.isKg && (!viewingOrder.actual_weight || viewingOrder.actual_weight <= 0)" class="text-[8px] text-blue-500 font-black uppercase tracking-tighter leading-none italic">*Belum ditimbang</span>
                                            </div>
                                            <span class="text-2xl font-black text-[#E30613] tracking-tighter leading-none">{{ formatRupiah(viewingOrder.total_price) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
                                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Status Pembayaran</p>
                                    <span :class="viewingOrder.payment_status === 'berhasil' ? 'bg-[#22C55E] text-white' : 'bg-rose-100 text-rose-700'" class="text-[9px] px-2.5 py-1 rounded font-black uppercase tracking-widest">
                                        {{ viewingOrder.payment_status === 'berhasil' ? 'SUDAH DIBAYAR' : viewingOrder.payment_status }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-500 mb-1">Metode Pembayaran</p>
                                    <p class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ viewingOrder.payment_method || '-' }}</p>
                                </div>
                            </section>
                        </div>

                        <!-- Footer Actions -->
                        <div class="px-5 py-4 border-t border-border bg-container/20 flex flex-wrap justify-between items-center gap-3 shrink-0">
                            <div class="flex gap-2">
                                <button @click="printNota(viewingOrder)" :disabled="printingId === viewingOrder.id"
                                    class="flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-xs font-black uppercase tracking-widest rounded-sm hover:bg-black transition-all disabled:opacity-50">
                                    <i v-if="printingId === viewingOrder.id" class="fa-solid fa-spinner fa-spin"></i>
                                    <i v-else class="fa-solid fa-print"></i>
                                    Cetak
                                </button>
                                <button @click="unduhPDF(viewingOrder)" :disabled="downloadingId === viewingOrder.id"
                                    class="flex items-center gap-2 px-4 py-2 bg-rose-600 text-white text-xs font-black uppercase tracking-widest rounded-sm hover:bg-rose-700 transition-all disabled:opacity-50">
                                    <i v-if="downloadingId === viewingOrder.id" class="fa-solid fa-spinner fa-spin"></i>
                                    <i v-else class="fa-solid fa-file-pdf"></i>
                                    Unduh
                                </button>
                            </div>
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

        <!-- Modal Import Excel -->
        <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showImportModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-surface w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-border">
                    <div class="p-6">
                        <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center mb-6 mx-auto shadow-inner">
                            <i class="fa-solid fa-file-import text-2xl"></i>
                        </div>

                        <h3 class="text-lg font-black text-center text-text uppercase tracking-widest mb-2">Konfirmasi Import</h3>
                        <p class="text-sm text-muted text-center mb-6 px-4">Anda akan meng-import data pesanan dari file berikut:</p>

                        <div class="bg-container border border-border rounded-xl p-4 mb-8 flex items-center gap-3">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center border border-border shadow-sm shrink-0 text-emerald-500">
                                <i class="fa-solid fa-file-excel"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-black text-text truncate uppercase tracking-tight">{{ importFile?.name }}</p>
                                <p class="text-[10px] text-muted mt-0.5">{{ (importFile?.size / 1024).toFixed(2) }} KB</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-blue-50/50 rounded-lg border border-blue-100/50">
                                <i class="fa-solid fa-circle-info text-blue-500 text-xs mt-0.5"></i>
                                <p class="text-[10px] text-blue-700 leading-relaxed font-medium">Pastikan kolom Email Pelanggan dan Nama Layanan sudah sesuai dengan yang ada di sistem agar tidak dilewati.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-container border-t border-border flex gap-3">
                        <button @click="showImportModal = false" class="flex-1 h-12 bg-white text-muted text-[11px] font-black uppercase tracking-widest rounded-xl border border-border hover:bg-gray-50 transition-all">
                            Batal
                        </button>
                        <button @click="confirmImport" :disabled="importForm.processing" class="flex-1 h-12 bg-blue-500 text-white text-[11px] font-black uppercase tracking-widest rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-600 active:scale-95 transition-all disabled:opacity-50">
                            {{ importForm.processing ? 'Memproses...' : 'Ya, Import Data' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>

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
