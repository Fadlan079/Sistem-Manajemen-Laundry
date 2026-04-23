<script setup>
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';

const props = defineProps({
    deliveries: Object,
    stats: Object,
    filters: Object,
});

const searchStr = ref(props.filters.search || '');
const dateFilter = ref(props.filters.date || '');

const tabs = computed(() => [
    { id: 'semua', name: 'Semua', count: props.stats.semua, active: props.filters.tab === 'semua' },
    { id: 'belum-selesai', name: 'Belum Selesai', count: props.stats.belum_selesai, active: props.filters.tab === 'belum-selesai' },
    { id: 'sudah-dijemput', name: 'Sudah di Jemput', count: props.stats.sudah_dijemput, active: props.filters.tab === 'sudah-dijemput' },
    { id: 'sudah-diantar', name: 'Sudah di Antar', count: props.stats.sudah_diantar, active: props.filters.tab === 'sudah-diantar' },
]);

// Custom debounce function
const debounce = (cb, delay) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => cb(...args), delay);
    };
};

watch(searchStr, debounce((value) => {
    router.get(route('kurir.dashboard'), {
        tab: props.filters.tab,
        search: value,
        date: dateFilter.value
    }, { preserveState: true, preserveScroll: true, replace: true });
}, 300));

watch(dateFilter, (value) => {
    router.get(route('kurir.dashboard'), {
        tab: props.filters.tab,
        search: searchStr.value,
        date: value
    }, { preserveState: true, preserveScroll: true, replace: true });
});

const selectTab = (tabId) => {
    router.get(route('kurir.dashboard'), { tab: tabId, search: searchStr.value, date: dateFilter.value }, { preserveState: true, preserveScroll: true });
};


// Modal Selesai
const showCompleteModal = ref(false);
const selectedDeliveryForComplete = ref(null);
const imagePreviewUrl = ref(null);

const completeForm = useForm({
    kg: '',
    notes: '',
    proof_image: null,
});

const openCompleteModal = (delivery) => {
    selectedDeliveryForComplete.value = delivery;
    completeForm.reset();
    completeForm.notes = delivery.notes || '';
    imagePreviewUrl.value = delivery.proof_image ? delivery.proof_image : null;
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
    completeForm.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(route('kurir.deliveries.selesai', selectedDeliveryForComplete.value.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showCompleteModal.value = false;
            completeForm.reset();
        }
    });
};

function highlight(text, query) {
    if (!query || !text) return text;
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${escaped})`, 'gi');
    return String(text).replace(regex, '<mark class="bg-yellow-200 text-yellow-900 rounded px-0.5">$1</mark>');
}
</script>

<template>
    <Head title="Manajemen Tugas - Kurir" />
    <DashboardLayout title="Tugas Saya">
        
        <div class="space-y-6 pb-20">
            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 xl:gap-6">
                <!-- Sisa / Belum Selesai -->
                <button @click="selectTab('belum-selesai')" class="bg-surface border border-border rounded-lg p-5 flex flex-col shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)] hover:border-amber-400 hover:shadow-md transition-all text-left group">
                    <div class="flex items-center justify-between mb-3 w-full">
                        <h3 class="text-sm font-medium text-muted group-hover:text-amber-600 transition-colors">Tugas Berjalan</h3>
                        <div class="w-8 h-8 rounded-md bg-amber-50 text-amber-500 flex items-center justify-center">
                            <i class="fa-solid fa-list-check text-sm"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-text">{{ stats.belum_selesai }}</p>
                        <p class="text-xs text-amber-600 mt-2 font-medium">Masih perlu ditangani</p>
                    </div>
                </button>

                <!-- Selesai Hari Ini -->
                <div class="bg-surface border border-border rounded-lg p-5 flex flex-col shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)] hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-muted">Selesai Hari Ini</h3>
                        <div class="w-8 h-8 rounded-md bg-emerald-50 text-emerald-500 flex items-center justify-center">
                            <i class="fa-solid fa-check-double text-sm"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-text">{{ stats.selesai_hari_ini }}</p>
                        <p class="text-xs text-emerald-600 mt-2 font-medium">Kerja bagus!</p>
                    </div>
                </div>

                <!-- Pickup Selesai -->
                 <button @click="selectTab('sudah-dijemput')" class="bg-surface border border-border rounded-lg p-5 flex flex-col shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)] hover:border-blue-400 hover:shadow-md transition-all text-left group">
                    <div class="flex items-center justify-between mb-3 w-full">
                        <h3 class="text-sm font-medium text-muted group-hover:text-blue-600 transition-colors">Total Pickup</h3>
                        <div class="w-8 h-8 rounded-md bg-blue-50 text-blue-500 flex items-center justify-center">
                            <i class="fa-solid fa-motorcycle text-sm"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-text">{{ stats.sudah_dijemput }}</p>
                        <p class="text-xs text-blue-600 mt-2 font-medium">Selesai dijemput</p>
                    </div>
                </button>

                <!-- Tugas Baru -->
                <div class="bg-surface border border-border rounded-lg p-5 flex flex-col shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)] hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-muted">Tugas Hari Ini</h3>
                        <div class="w-8 h-8 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center">
                            <i class="fa-solid fa-calendar-day text-sm"></i>
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
            </div>

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-surface p-2 border-b border-border">
                <div class="flex items-center overflow-x-auto hide-scrollbar gap-1 w-full lg:w-auto">
                    <button v-for="(tab, index) in tabs" :key="index" @click="selectTab(tab.id)"
                        class="px-4 py-2.5 text-sm font-medium transition-all rounded-md flex items-center gap-2 whitespace-nowrap"
                        :class="tab.active ? 'bg-primary text-white shadow-md' : 'text-muted hover:text-primary hover:bg-primary/5'">
                        {{ tab.name }}
                        <span v-if="tab.count !== null"
                            class="px-2 py-0.5 rounded-full text-[11px]"
                            :class="(tab.active ? 'bg-white/20 text-white border border-white/20' : 'bg-container text-muted border border-border')">
                            {{ tab.count }}
                        </span>
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                    <div class="w-full sm:w-auto relative text-sm">
                        <input type="date" v-model="dateFilter"
                            class="w-full sm:w-[150px] px-3 py-2 bg-surface border border-border rounded-md focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all text-text" />
                        <button v-if="dateFilter" @click="dateFilter = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted hover:text-rose-500 bg-surface pl-1">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>

                    <div class="w-full sm:max-w-xs relative text-sm">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted"></i>
                        <input type="text" v-model="searchStr" placeholder="Cari INV-..., nama, atau no. HP..."
                            class="w-full pl-9 pr-4 py-2 bg-surface border border-border rounded-md focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all placeholder:text-muted/60 text-text" />
                        <span v-if="searchStr" class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-primary bg-primary/10 px-1.5 py-0.5 rounded">
                            {{ deliveries.total }} hasil
                        </span>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <div v-if="deliveries.data.length === 0" class="text-center py-12 text-muted text-sm border border-border rounded-lg bg-surface">
                    Tidak ada tugas yang ditemukan.
                </div>

                <template v-for="delivery in deliveries.data" :key="delivery.id">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between border rounded-lg bg-surface p-4 shadow-[inset_0_2px_10px_rgba(0,0,0,0.03)] hover:shadow-md transition-all gap-4"
                        :class="[
                            delivery.status === 'selesai' ? 'border-emerald-200 bg-emerald-50/20' : 'border-border',
                            searchStr && (delivery.name.toLowerCase().includes(searchStr.toLowerCase()) || delivery.invoice?.toLowerCase().includes(searchStr.toLowerCase()) || delivery.phone?.includes(searchStr)) ? 'border-yellow-300 ring-1 ring-yellow-300' : ''
                        ]">

                        <div class="flex-1 w-full flex flex-col gap-2">
                            <div class="flex items-center gap-2.5 mb-1 flex-wrap">
                                <span class="bg-surface border shadow-sm text-text text-[10px] font-bold px-2 py-0.5 rounded-sm uppercase tracking-wider">
                                    <i :class="delivery.type === 'pickup' ? 'fa-solid fa-box-open text-blue-500' : 'fa-solid fa-motorcycle text-purple-500'"></i>
                                    {{ delivery.type === 'pickup' ? 'Penjemputan' : 'Pengantaran' }}
                                </span>
                                <span class="font-medium text-primary text-xs font-mono" v-html="highlight(delivery.invoice, searchStr)"></span>
                                <span class="font-semibold text-text text-base truncate max-w-[200px]" :title="delivery.name" v-html="highlight(delivery.name, searchStr)"></span>
                                <span v-if="delivery.status === 'selesai'" class="bg-emerald-100 text-emerald-600 border border-emerald-200 text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide">
                                    Selesai
                                </span>
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-4 text-sm text-muted bg-container p-2 rounded border border-border w-max shrink-0">
                                <div class="flex items-center gap-1.5 font-medium">
                                    <i class="fa-regular fa-clock"></i> {{ delivery.time }}
                                </div>
                                <div v-if="delivery.phone && delivery.phone !== '-'" class="flex items-center gap-1.5 bg-surface px-1.5 py-0.5 rounded shadow-sm">
                                    <i class="fa-solid fa-phone text-xs"></i>
                                    <span v-html="highlight(delivery.phone, searchStr)"></span>
                                </div>
                                <div class="flex items-center gap-1.5 text-xs">
                                     <i class="fa-solid fa-tshirt text-primary/80"></i> {{ delivery.service_name }}
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-1.5 text-sm font-medium mt-1">
                                <i class="fa-solid fa-location-dot mt-0.5 text-rose-500"></i> <span class="leading-relaxed">{{ delivery.address }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 w-full lg:w-auto justify-end mt-3 lg:mt-0 lg:border-l lg:border-border lg:pl-4">
                            <Link :href="route('kurir.detail', delivery.order_db_id)"
                                class="px-3 py-2 rounded-md font-medium text-sm transition-colors border border-border text-text hover:bg-container bg-surface w-full text-center lg:w-auto">
                                Detail
                            </Link>
                            
                            <button v-if="delivery.status !== 'selesai'" @click="openCompleteModal(delivery)"
                                class="px-4 py-2 flex-1 lg:flex-none rounded-md font-bold text-sm transition-colors shadow-md hover:shadow-lg bg-emerald-600 hover:bg-emerald-700 text-white whitespace-nowrap text-center flex items-center justify-center gap-2">
                                <i class="fa-solid fa-check"></i> Tandai Selesai
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

        <div v-if="showCompleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCompleteModal = false"></div>

            <div class="bg-surface border-2 border-text shadow-md w-full max-w-sm relative z-10 rounded-none overflow-hidden font-sans">
                <!-- Ticket Header -->
                <div class="p-4 border-b-2 border-text bg-container flex justify-between items-center">
                    <h3 class="font-bold text-text text-xs uppercase tracking-widest">Penyelesaian {{ selectedDeliveryForComplete?.type === 'pickup' ? 'Penjemputan' : 'Pengantaran' }}</h3>
                    <span class="text-[10px] font-mono text-muted">{{ selectedDeliveryForComplete?.invoice }}</span>
                </div>

                <form @submit.prevent="submitCompleteForm">
                    <div class="p-5 space-y-6 max-h-[75vh] overflow-y-auto hide-scrollbar">
                        <!-- Summary Card -->
                        <div class="border-2 border-dashed border-text p-3 bg-container/30 space-y-2 font-mono text-xs">
                            <div class="flex justify-between">
                                <span class="text-[10px] font-bold text-muted uppercase tracking-tight">Nama Pelanggan</span>
                                <span class="font-bold text-text truncate max-w-[150px] text-right">{{ selectedDeliveryForComplete?.name }}</span>
                            </div>
                            <div class="flex justify-between border-t border-dashed border-border pt-2">
                                <span class="text-[10px] font-bold text-muted uppercase tracking-tight">Layanan</span>
                                <span class="font-bold text-primary">{{ selectedDeliveryForComplete?.service_name }}</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- Kondisi: Hanya tampil jika Penjemputan DAN layanan berbasis Kiloan (isKg) -->
                            <div v-if="selectedDeliveryForComplete?.type === 'pickup' && selectedDeliveryForComplete?.isKg">
                                <label class="block text-[10px] font-bold text-text uppercase tracking-widest mb-1.5 flex items-center gap-1">
                                    Berat Aktual (KG) <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" 
                                        v-model="completeForm.kg" 
                                        step="0.1"
                                        min="0.5"
                                        required
                                        placeholder="0.0" 
                                        class="w-full bg-surface border-2 border-border rounded-none pl-3 pr-10 py-3 text-lg focus:border-text focus:ring-0 outline-none text-text font-mono font-bold placeholder:text-muted/40 uppercase" />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted font-bold">KG</span>
                                </div>
                                <p class="text-[9px] text-muted font-mono mt-1 w-full text-right uppercase">Masukan Berat Pasti Pakaian</p>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-text uppercase tracking-widest mb-1.5">Upload Bukti <span class="lowercase text-muted font-normal">(opsional)</span></label>
                                
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

                            <div>
                                <label class="block text-[10px] font-bold text-text uppercase tracking-widest mb-1.5">Catatan Tambahan <span class="lowercase text-muted font-normal">(opsional)</span></label>
                                <textarea
                                    v-model="completeForm.notes"
                                    rows="2"
                                    placeholder="TULIS CATATAN DISINI..."
                                    class="w-full bg-surface border-2 border-border rounded-none px-3 py-2 text-sm focus:border-text focus:ring-0 outline-none text-text font-mono transition-colors resize-none placeholder:text-muted/40"
                                ></textarea>
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
                            class="flex-1 py-2 text-xs font-bold uppercase tracking-widest transition-all bg-emerald-600 hover:bg-emerald-700 text-surface border-2 border-emerald-600 disabled:opacity-50 shadow-md">
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
