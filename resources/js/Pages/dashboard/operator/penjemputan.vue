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

const searchStr = ref(props.filters.search || '');

const tabs = computed(() => [
    { id: 'semua', name: 'Semua', count: props.stats.semua, active: props.filters.tab === 'semua' },
    { id: 'belum-diassign', name: 'Belum Di-assign', count: props.stats.belum_diassign, active: props.filters.tab === 'belum-diassign' },
    { id: 'sudah-diassign', name: 'Sudah Di-assign', count: props.stats.sudah_diassign, active: props.filters.tab === 'sudah-diassign' },
    { id: 'terlama', name: 'Terlama', count: props.stats.terlama, active: props.filters.tab === 'terlama', alert: true },
]);

// Custom debounce function
const debounce = (cb, delay) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => cb(...args), delay);
    };
};

// Handle search with debounce
watch(searchStr, debounce((value) => {
    router.get(route('operator.penjemputan'), {
        tab: props.filters.tab,
        search: value
    }, { preserveState: true, preserveScroll: true, replace: true });
}, 300));

const selectTab = (tabId) => {
    router.get(route('operator.penjemputan'), { tab: tabId, search: searchStr.value }, { preserveState: true, preserveScroll: true });
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
const completeForm = useForm({
    kg: '',
    notes: '',
    proof_image: null,
});

const openCompleteModal = (order) => {
    if (order.is_external) {
        selectedDeliveryForComplete.value = order;
        completeForm.reset();
        showCompleteModal.value = true;
    } else {
        if (confirm('Tandai pesanan ini sudah dijemput?')) {
            router.put(route('operator.penjemputan.dijemput', order.id), {}, { preserveScroll: true });
        }
    }
};

const handleProofImageChange = (e) => {
    completeForm.proof_image = e.target.files[0];
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

// Data Dummy Pesanan

</script>

<template>
    <Head title="Manajemen Penjemputan - Hi Wash" />
    <DashboardLayout title="Manajemen Penjemputan">

        <div class="space-y-6 pb-20">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 xl:gap-6">
                <div class="bg-surface border border-border rounded-lg p-5 flex flex-col hover:shadow-md transition-shadow">
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

                <button @click="selectTab('belum-diassign')" class="bg-surface border border-border rounded-lg p-5 flex flex-col hover:border-amber-400 hover:shadow-md transition-all text-left group">
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

                <button @click="selectTab('terlama')" class="bg-surface border border-border rounded-lg p-5 flex flex-col hover:border-rose-400 hover:shadow-md transition-all text-left group">
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

                <div class="bg-surface border border-border rounded-lg p-5 flex flex-col hover:shadow-md transition-shadow">
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
                        :class="tab.active ? 'bg-container text-text shadow-sm' : 'text-muted hover:text-text hover:bg-container/50'">
                        {{ tab.name }}
                        <span v-if="tab.count !== null"
                            class="px-2 py-0.5 rounded-full text-[11px]"
                            :class="tab.alert ? 'bg-rose-100 text-rose-600' : (tab.active ? 'bg-surface text-text border border-border' : 'bg-surface border border-border text-muted')">
                            {{ tab.count }}
                        </span>
                    </button>
                </div>

                <div class="w-full lg:max-w-xs relative text-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted"></i>
                    <input type="text" v-model="searchStr" placeholder="Cari nomor invoice / pelanggan..."
                        class="w-full pl-9 pr-4 py-2 bg-surface border border-border rounded-md focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all placeholder:text-muted/60 text-text" />
                </div>
            </div>

            <div class="space-y-3">
                <div v-if="deliveries.data.length === 0" class="text-center py-12 text-muted text-sm border border-border rounded-lg bg-surface">
                    Tidak ada pesanan penjemputan ditemukan.
                </div>

                <template v-for="order in deliveries.data" :key="order.id">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between border border-border rounded-lg bg-surface p-4 hover:shadow-md transition-shadow gap-4">

                        <div class="flex-1">
                            <div class="flex items-center gap-2.5 mb-1">
                                <span class="font-medium text-primary text-sm">#{{ order.order_id }}</span>
                                <span class="font-semibold text-text text-base truncate max-w-[200px]" :title="order.name">{{ order.name }}</span>
                                <span v-if="order.isLate" class="bg-rose-100 text-rose-600 text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide ml-2">
                                    {{ order.lateText || 'Terlambat' }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-4 text-sm text-muted">
                                <div class="flex items-center gap-1.5" :class="{'text-rose-600 font-medium': order.isLate}">
                                    <i class="fa-regular fa-clock"></i> {{ order.time }}
                                </div>
                                <div class="flex items-center gap-1.5" :title="order.address">
                                    <i class="fa-solid fa-location-dot"></i> <span class="truncate max-w-[250px]">{{ order.address }}</span>
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
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showAssignModal = false"></div>

            <div class="bg-surface border border-border rounded-lg shadow-xl w-full max-w-sm overflow-hidden z-10">
                <div class="p-5 border-b border-border">
                    <h3 class="font-semibold text-text text-base">Assign Kurir Penjemputan</h3>
                </div>
                <div class="p-5 space-y-4">
                    <div class="bg-container rounded-md p-3">
                        <div class="text-sm font-medium text-text">{{ selectedDeliveryForAssign?.name }}</div>
                        <div class="text-xs text-muted mt-1">Invoice: #{{ selectedDeliveryForAssign?.order_id }}</div>
                    </div>

                    <div class="flex gap-4 mb-4">
                        <label class="flex items-center gap-2 text-sm text-text cursor-pointer">
                            <input type="radio" :value="false" v-model="isExternalCourier" class="text-primary focus:ring-primary">
                            Kurir Internal
                        </label>
                        <label class="flex items-center gap-2 text-sm text-text cursor-pointer">
                            <input type="radio" :value="true" v-model="isExternalCourier" class="text-primary focus:ring-primary">
                            Kurir Eksternal
                        </label>
                    </div>

                    <div v-if="!isExternalCourier">
                        <label class="block text-sm font-medium text-text mb-2">Pilih Kurir</label>
                        <select v-model="selectedCourierId" class="w-full bg-surface border border-border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-primary focus:border-primary outline-none text-text">
                            <option value="">-- Pilih Kurir --</option>
                            <option v-for="courier in couriers" :key="courier.id" :value="courier.id">
                                {{ courier.name }}
                            </option>
                        </select>
                    </div>

                    <div v-else class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">Nama Driver</label>
                            <input type="text" v-model="externalCourierName" placeholder="Contoh: Budi Driver Lepasan" class="w-full bg-surface border border-border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-primary focus:border-primary outline-none text-text" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">No. HP Driver</label>
                            <input type="text" v-model="externalCourierPhone" placeholder="Contoh: 08123456789" class="w-full bg-surface border border-border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-primary focus:border-primary outline-none text-text" />
                        </div>
                    </div>
                </div>
                <div class="p-4 flex justify-end gap-3 border-t border-border bg-surface">
                    <button @click="showAssignModal = false" class="px-4 py-2 text-sm font-medium text-muted hover:text-text transition-colors">Batal</button>
                    <button @click="submitAssignCourier" :disabled="(!isExternalCourier && !selectedCourierId) || (isExternalCourier && (!externalCourierName || !externalCourierPhone))"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors bg-primary hover:bg-primary-hover text-white disabled:opacity-50 disabled:cursor-not-allowed">
                        Simpan
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showCompleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showCompleteModal = false"></div>

            <div class="bg-surface border border-border rounded-lg shadow-xl w-full max-w-sm overflow-hidden z-10">
                <div class="p-5 border-b border-border">
                    <h3 class="font-semibold text-text text-base">Selesaikan Penjemputan</h3>
                </div>
                <form @submit.prevent="submitCompleteForm">
                    <div class="p-5 space-y-4">
                        <div class="bg-container rounded-md p-3">
                            <div class="text-sm font-medium text-text">{{ selectedDeliveryForComplete?.name }}</div>
                            <div class="text-xs text-muted mt-1">Invoice: #{{ selectedDeliveryForComplete?.order_id }}</div>
                            <div class="text-xs text-primary font-medium mt-1">
                                Kurir Eksternal: {{ selectedDeliveryForComplete?.courier?.replace('Eksternal: ', '') }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text mb-2">Berat Pakaian (KG)<span class="text-rose-500">*</span></label>
                            <input type="number" step="0.1" min="0.5" v-model="completeForm.kg" required placeholder="Contoh: 5.5" class="w-full bg-surface border border-border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-primary focus:border-primary outline-none text-text" />
                            <p class="text-[11px] text-muted mt-1">Minimal 0.5 KG</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-text mb-2">Catatan Kurir</label>
                            <textarea v-model="completeForm.notes" rows="2" placeholder="Catatan opsional..." class="w-full bg-surface border border-border rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-primary focus:border-primary outline-none text-text"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text mb-2">Bukti Foto Penjemputan</label>
                            <input type="file" accept="image/*" @change="handleProofImageChange" class="w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-colors" />
                        </div>
                    </div>
                    <div class="p-4 flex justify-end gap-3 border-t border-border bg-surface">
                        <button type="button" @click="showCompleteModal = false" class="px-4 py-2 text-sm font-medium text-muted hover:text-text transition-colors">Batal</button>
                        <button type="submit" :disabled="completeForm.processing"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors bg-emerald-600 hover:bg-emerald-700 text-white flex items-center justify-center min-w-[90px]">
                            <i v-if="completeForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                            <span v-else>Selesai</span>
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

