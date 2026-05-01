<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';

const props = defineProps({
    deliveries: Object,
    stats: Object,
    couriers: Array,
    filters: Object,
});

const searchStr = computed(() => props.filters.search || '');
const dateFilter = ref(props.filters.date || '');

const tabs = computed(() => [
    { id: 'semua', name: 'Semua', count: props.stats.semua, active: props.filters.tab === 'semua' },
    { id: 'belum-diassign', name: 'Belum Di-assign', count: props.stats.belum_diassign, active: props.filters.tab === 'belum-diassign' },
    { id: 'sudah-diassign', name: 'Sudah Di-assign', count: props.stats.sudah_diassign, active: props.filters.tab === 'sudah-diassign' },
    { id: 'terlama', name: 'Terlama', count: props.stats.terlama, active: props.filters.tab === 'terlama', alert: true },
]);

// Removed local search watcher because Topbar handles it

// Handle date filter change
watch(dateFilter, (value) => {
    router.get(route('operator.penjemputan'), {
        tab: props.filters.tab,
        search: searchStr.value,
        date: value
    }, { preserveState: true, preserveScroll: true, replace: true });
});

const selectTab = (tabId) => {
    router.get(route('operator.penjemputan'), { tab: tabId, search: searchStr.value, date: dateFilter.value }, { preserveState: true, preserveScroll: true });
};

// Modal Assign Kurir State
const showAssignModal = ref(false);
const selectedDeliveryForAssign = ref(null);
const selectedCourierId = ref('');
const isExternalCourier = ref(false);
const externalCourierName = ref('');
const externalCourierPhone = ref('');

const openAssignModal = (delivery) => {
    selectedDeliveryForAssign.value = delivery;
    selectedCourierId.value = '';
    isExternalCourier.value = false;
    externalCourierName.value = '';
    externalCourierPhone.value = '';
    showAssignModal.value = true;
};

const submitAssignCourier = () => {
    if (!isExternalCourier.value && !selectedCourierId.value) return;
    if (isExternalCourier.value && (!externalCourierName.value || !externalCourierPhone.value)) return;

    router.put(route('operator.penjemputan.assign', selectedDeliveryForAssign.value.id), {
        is_external: isExternalCourier.value,
        courier_id: selectedCourierId.value,
        external_courier_name: externalCourierName.value,
        external_courier_phone: externalCourierPhone.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showAssignModal.value = false;
        }
    });
};

// Modal Selesai (External)
const showCompleteModal = ref(false);
const selectedDeliveryForComplete = ref(null);
const imagePreviewUrl = ref(null);

const completeForm = useForm({
    kg: '',
    notes: '',
    proof_image: null,
});

const openCompleteModal = (order) => {
    selectedDeliveryForComplete.value = order;
    completeForm.reset();
    completeForm.kg = order.kg || '';
    completeForm.notes = order.notes || '';
    imagePreviewUrl.value = order.proof_image || null;
    showCompleteModal.value = true;
};

const handleProofImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        completeForm.proof_image = file;
        imagePreviewUrl.value = URL.createObjectURL(file);
    }
};

const submitCompleteForm = () => {
    // Inertia requires post for multipart/form-data with method spoofing
    completeForm.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(route('operator.penjemputan.dijemput', selectedDeliveryForComplete.value.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showCompleteModal.value = false;
            completeForm.reset();
        }
    });
};  

const refreshData = () => {
    router.reload({ only: ['deliveries', 'stats'] });
};

// Utility to highlight search term in text
function highlight(text, query) {
    if (!query || !text) return text;
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${escaped})`, 'gi');
    return String(text).replace(regex, '<mark class="bg-yellow-200 text-yellow-900 rounded px-0.5">$1</mark>');
}

</script>

<template>
    <Head title="Manajemen Penjemputan - Hi Wash" />
    <DashboardLayout title="Manajemen Penjemputan">

        <div class="space-y-6 pb-20">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 xl:gap-6">
                <div class="bg-surface border border-border rounded-lg p-5 flex flex-col shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)] hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-muted">Total Hari Ini</h3>
                        <div class="w-8 h-8 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="fa-solid fa-box-open text-sm"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-text">{{ stats.hari_ini }}</p>
                        <p class="text-xs text-muted mt-2 flex items-center gap-1">
                            <span :class="stats.hari_ini >= stats.kemarin ? 'text-emerald-500' : 'text-rose-500'">
                                <i class="fa-solid" :class="stats.hari_ini >= stats.kemarin ? 'fa-arrow-up' : 'fa-arrow-down'"></i>
                                {{ Math.abs(stats.hari_ini - stats.kemarin) }}
                            </span>
                            <span class="font-normal">dari kemarin</span>
                        </p>
                    </div>
                </div>

                <button @click="selectTab('belum-diassign')" class="bg-surface border border-border rounded-lg p-5 flex flex-col shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)] hover:border-amber-400 hover:shadow-md transition-all text-left group">
                    <div class="flex items-center justify-between mb-3 w-full">
                        <h3 class="text-sm font-medium text-muted group-hover:text-amber-600 transition-colors">Belum Di-assign</h3>
                        <div class="w-8 h-8 rounded-md bg-amber-50 text-amber-500 flex items-center justify-center">
                            <i class="fa-solid fa-user-clock text-sm"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-text">{{ stats.belum_diassign }}</p>
                        <p class="text-xs text-amber-600 mt-2 font-medium">Perlu tindakan</p>
                    </div>
                </button>

                <button @click="selectTab('terlama')" class="bg-surface border border-border rounded-lg p-5 flex flex-col shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)] hover:border-rose-400 hover:shadow-md transition-all text-left group">
                    <div class="flex items-center justify-between mb-3 w-full">
                        <h3 class="text-sm font-medium text-muted group-hover:text-rose-600 transition-colors">Terlambat</h3>
                        <div class="w-8 h-8 rounded-md flex items-center justify-center" :class="stats.terlama > 0 ? 'bg-rose-100 text-rose-600' : 'bg-container text-muted'">
                            <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold" :class="stats.terlama > 0 ? 'text-rose-600' : 'text-text'">{{ stats.terlama }}</p>
                        <p class="text-xs mt-2 font-medium" :class="stats.terlama > 0 ? 'text-rose-600' : 'text-muted'">Overdue limit</p>
                    </div>
                </button>

                <div class="bg-surface border border-border rounded-lg p-5 flex flex-col shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)] hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-muted">Selesai Hari Ini</h3>
                        <div class="w-8 h-8 rounded-md bg-emerald-50 text-emerald-500 flex items-center justify-center">
                            <i class="fa-solid fa-check-double text-sm"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-text">{{ stats.selesai_hari_ini }}</p>
                        <p class="text-xs text-emerald-600 mt-2 font-medium flex items-center gap-1.5">
                            <i class="fa-solid fa-spinner fa-spin-pulse"></i> Sedang berjalan
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-surface p-2 border-b border-border">
                <div class="flex items-center overflow-x-auto hide-scrollbar gap-1 w-full lg:w-auto">
                    <button v-for="(tab, index) in tabs" :key="index" @click="selectTab(tab.id)"
                        class="px-4 py-2.5 text-sm font-medium transition-all rounded-md flex items-center gap-2 whitespace-nowrap"
                        :class="tab.active ? 'bg-primary text-white shadow-md' : 'text-muted hover:text-primary hover:bg-primary/5'">
                        {{ tab.name }}
                        <span v-if="tab.count !== null"
                            class="px-2 py-0.5 rounded-full text-[11px]"
                            :class="tab.alert && !tab.active ? 'bg-rose-100 text-rose-600' : (tab.active ? 'bg-white/20 text-white border border-white/20' : 'bg-container text-muted border border-border')">
                            {{ tab.count }}
                        </span>
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                    <!-- Date Filter -->
                    <div class="w-full sm:w-auto relative text-sm">
                        <input type="date" v-model="dateFilter"
                            class="w-full sm:w-[150px] px-3 py-2 bg-surface border border-border rounded-md focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all text-text" />
                        <button v-if="dateFilter" @click="dateFilter = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted hover:text-rose-500 bg-surface pl-1">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>

                </div>
            </div>

            <div class="space-y-3">
                <div v-if="deliveries.data.length === 0" class="text-center py-12 text-muted text-sm border border-border rounded-lg bg-surface">
                    Tidak ada pesanan penjemputan ditemukan.
                </div>

                <template v-for="order in deliveries.data" :key="order.id">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between border rounded-lg bg-surface p-4 shadow-[inset_0_2px_10px_rgba(0,0,0,0.03)] hover:shadow-md transition-all gap-4"
                        :class="searchStr && (order.name.toLowerCase().includes(searchStr.toLowerCase()) || order.invoice?.toLowerCase().includes(searchStr.toLowerCase()) || order.phone?.includes(searchStr)) ? 'border-yellow-300 ring-1 ring-yellow-300' : 'border-border'">

                        <div class="flex-1">
                            <div class="flex items-center gap-2.5 mb-1 flex-wrap">
                                <span class="font-medium text-primary text-xs font-mono" v-html="highlight(order.invoice, searchStr)"></span>
                                <span class="font-semibold text-text text-base truncate max-w-[200px]" :title="order.name" v-html="highlight(order.name, searchStr)"></span>
                                <span v-if="order.isLate" class="bg-rose-100 text-rose-600 text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide">
                                    {{ order.lateText || 'Terlambat' }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-4 text-sm text-muted">
                                <div class="flex items-center gap-1.5" :class="{'text-rose-600 font-medium': order.isLate}">
                                    <i class="fa-regular fa-clock"></i> {{ order.time }}
                                </div>
                                <div v-if="order.phone && order.phone !== '-'" class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-phone text-xs"></i>
                                    <span v-html="highlight(order.phone, searchStr)"></span>
                                </div>
                                <div class="flex items-center gap-1.5" :title="order.address">
                                    <i class="fa-solid fa-location-dot"></i> <span class="truncate max-w-[200px]">{{ order.address }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="w-full lg:w-48 shrink-0 flex items-center lg:border-l border-border lg:pl-4">
                            <div v-if="!order.courier" class="flex items-center gap-2 text-muted text-sm italic">
                                <i class="fa-solid fa-user-slash"></i> Belum ada kurir
                            </div>
                            <div v-else class="flex items-start gap-2.5 text-sm">
                                <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-motorcycle text-xs"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-medium text-text">{{ order.courier }}</span>
                                    <span class="text-muted text-xs">{{ order.courierTime }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 w-full lg:w-auto justify-end mt-2 lg:mt-0">
                            <button v-if="!order.courier" @click="openAssignModal(order)"
                                class="px-4 py-2 rounded-md font-medium text-sm transition-colors bg-primary hover:bg-primary-hover text-white">
                                Assign Kurir
                            </button>

                            <button v-if="order.courier" @click="openCompleteModal(order)"
                                class="px-4 py-2 rounded-md font-medium text-sm transition-colors bg-emerald-600 hover:bg-emerald-700 text-white">
                                Tandai Selesai
                            </button>

                            <a v-if="order.phone && order.phone !== '-'" :href="'https://wa.me/' + order.phone.replace(/^0/, '62')" target="_blank"
                                class="w-9 h-9 flex items-center justify-center rounded-md transition-colors border border-border text-emerald-500 hover:bg-container bg-surface">
                                <i class="fa-brands fa-whatsapp text-lg"></i>
                            </a>

                            <button class="px-3 py-2 rounded-md font-medium text-sm transition-colors border border-border text-text hover:bg-container bg-surface">
                                Detail
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div v-if="deliveries.links && deliveries.links.length > 3" class="mt-6 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <template v-for="(link, key) in deliveries.links" :key="key">
                        <button
                            @click="link.url && router.get(link.url, { tab: filters.tab, search: searchStr }, { preserveState: true, preserveScroll: true })"
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

        <div v-if="showAssignModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showAssignModal = false"></div>

            <div class="bg-surface border-2 border-text shadow-md w-full max-w-sm relative z-10 rounded-none overflow-hidden font-sans">
                <!-- Ticket Header -->
                <div class="p-4 border-b-2 border-text bg-container flex justify-between items-center">
                    <h3 class="font-bold text-text text-xs uppercase tracking-widest">Penugasan Kurir</h3>
                </div>

                <div class="p-5 space-y-6">
                    <!-- Ticket Details Section -->
                    <div class="border-2 border-dashed border-border p-3 bg-container/30 space-y-2">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] uppercase font-bold text-muted tracking-tight">Pelanggan</span>
                            <span class="text-xs font-mono font-bold text-text text-right">{{ selectedDeliveryForAssign?.name }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-dashed border-border pt-2">
                            <span class="text-[10px] uppercase font-bold text-muted tracking-tight">No. Invoice</span>
                            <span class="text-xs font-mono font-bold text-primary">{{ selectedDeliveryForAssign?.invoice }}</span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex p-1 bg-container border border-border">
                            <button
                                @click="isExternalCourier = false"
                                class="flex-1 py-2 text-[11px] font-bold uppercase tracking-wider transition-colors"
                                :class="!isExternalCourier ? 'bg-text text-surface' : 'text-muted hover:text-text'"
                            >
                                Internal
                            </button>
                            <button
                                @click="isExternalCourier = true"
                                class="flex-1 py-1.5 text-[11px] font-bold uppercase tracking-wider transition-colors"
                                :class="isExternalCourier ? 'bg-text text-surface' : 'text-muted hover:text-text'"
                            >
                                Eksternal
                            </button>
                        </div>

                        <div v-if="!isExternalCourier" class="space-y-2">
                            <label class="block text-[10px] font-bold text-text uppercase tracking-widest">Pilih Personel</label>
                            <select v-model="selectedCourierId" class="w-full bg-surface border-2 border-border rounded-none px-3 py-2 text-sm focus:border-text focus:ring-0 outline-none text-text font-mono">
                                <option value="">-- PILIH KURIR --</option>
                                <option v-for="courier in couriers" :key="courier.id" :value="courier.id">
                                    {{ courier.name.toUpperCase() }}
                                </option>
                            </select>
                        </div>

                        <div v-else class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-text uppercase tracking-widest mb-1.5">Nama Driver</label>
                                <input type="text" v-model="externalCourierName" placeholder="NAMA LENGKAP" class="w-full bg-surface border-2 border-border rounded-none px-3 py-2 text-sm focus:border-text focus:ring-0 outline-none text-text font-mono placeholder:text-muted/40 uppercase" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-text uppercase tracking-widest mb-1.5">Kontak (WA/TELP)</label>
                                <input type="text" v-model="externalCourierPhone" placeholder="08XX-XXXX-XXXX" class="w-full bg-surface border-2 border-border rounded-none px-3 py-2 text-sm focus:border-text focus:ring-0 outline-none text-text font-mono placeholder:text-muted/40" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ticket Footer -->
                <div class="p-4 flex gap-2 border-t-2 border-text bg-container">
                    <button
                        @click="showAssignModal = false"
                        class="flex-1 py-2 text-xs font-bold uppercase tracking-wider text-muted hover:text-text border-2 border-transparent transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitAssignCourier"
                        :disabled="(!isExternalCourier && !selectedCourierId) || (isExternalCourier && (!externalCourierName || !externalCourierPhone))"
                        class="flex-1 py-2 text-xs font-bold uppercase tracking-widest transition-all bg-primary hover:bg-primary-hover text-surface disabled:opacity-50 disabled:cursor-not-allowed border-2 border-primary"
                    >
                        Konfirmasi
                    </button>
                </div>
            </div>
        </div>

<div v-if="showCompleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCompleteModal = false"></div>

    <div class="bg-surface border-2 border-text shadow-md w-full max-w-sm relative z-10 rounded-none overflow-hidden font-sans">
        <!-- Ticket Header -->
        <div class="p-4 border-b-2 border-text bg-container flex justify-between items-center">
            <h3 class="font-bold text-text text-xs uppercase tracking-widest">Penyelesaian Sesi</h3>
            <span class="text-[10px] font-mono text-muted">{{ selectedDeliveryForComplete?.invoice }}</span>
        </div>

        <form @submit.prevent="submitCompleteForm">
            <div class="p-5 space-y-6 max-h-[75vh] overflow-y-auto hide-scrollbar">
                <!-- Summary Card -->
                <div class="border-2 border-dashed border-border p-3 bg-container/30 space-y-2 font-mono text-xs">
                    <div class="flex justify-between">
                        <span class="text-[10px] font-bold text-muted uppercase tracking-tight">Nama Pelanggan</span>
                        <span class="font-bold text-text truncate max-w-[150px] text-right">{{ selectedDeliveryForComplete?.name }}</span>
                    </div>
                    <div class="flex justify-between border-t border-dashed border-border pt-2">
                        <span class="text-[10px] font-bold text-muted uppercase tracking-tight">Personel Penjemput</span>
                        <span class="font-bold text-primary">{{ selectedDeliveryForComplete?.courier?.replace('Eksternal: ', '').toUpperCase() }}</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-text uppercase tracking-widest mb-1.5">
                            Berat Aktual (KG) <span class="text-primary">*</span>
                        </label>
                        <div class="relative">
                            <input
                                type="number"
                                step="0.1"
                                min="0.5"
                                v-model="completeForm.kg"
                                required
                                placeholder="0.0"
                                class="w-full bg-surface border-2 border-border rounded-none px-3 py-2.5 text-sm focus:border-text focus:ring-0 outline-none text-text font-mono transition-colors"
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-muted uppercase">KG</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-text uppercase tracking-widest mb-1.5">Catatan Tambahan</label>
                        <textarea
                            v-model="completeForm.notes"
                            rows="2"
                            placeholder="TULIS CATATAN DISINI..."
                            class="w-full bg-surface border-2 border-border rounded-none px-3 py-2 text-sm focus:border-text focus:ring-0 outline-none text-text font-mono transition-colors resize-none placeholder:text-muted/40"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-text uppercase tracking-widest mb-1.5">Upload Bukti</label>

                        <div v-if="imagePreviewUrl" class="mb-3 border-2 border-text p-1 relative group">
                            <img :src="imagePreviewUrl" alt="Proof Preview" class="w-full h-32 object-cover object-center" />
                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 backdrop-blur-sm">
                                <button type="button" @click="imagePreviewUrl = null; completeForm.proof_image = null" class="bg-rose-500 text-white text-[10px] uppercase font-bold px-3 py-1.5 border-2 border-transparent hover:border-white transition-all">Hapus Foto</button>
                            </div>
                        </div>

                        <div v-if="!imagePreviewUrl" class="border-2 border-dashed border-border p-2 bg-container hover:border-text transition-colors">
                            <input
                                type="file"
                                accept="image/*"
                                @change="handleProofImageChange"
                                class="w-full text-[10px] text-muted font-mono file:mr-3 file:py-1.5 file:px-3 file:rounded-none file:border-2 file:border-text file:text-[9px] file:font-bold file:uppercase file:bg-text file:text-surface hover:file:bg-muted transition-colors cursor-pointer"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket Footer -->
            <div class="p-4 flex gap-2 border-t-2 border-text bg-container">
                <button
                    type="button"
                    @click="showCompleteModal = false"
                    class="flex-1 py-2 text-xs font-bold uppercase tracking-wider text-muted hover:text-text border-2 border-transparent transition-colors">
                    Batal
                </button>
                <button
                    type="submit"
                    :disabled="completeForm.processing"
                    class="flex-1 py-2 text-xs font-bold uppercase tracking-widest transition-all bg-emerald-600 hover:bg-emerald-700 text-surface border-2 border-emerald-600 disabled:opacity-50">
                    <i v-if="completeForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                    <span v-else>Selesaikan</span>
                </button>
            </div>
        </form>
    </div>
</div>
    </DashboardLayout>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

