<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';

const props = defineProps({
    orders: Object,
    stats: Object,
    filters: Object,
});

const searchStr = computed(() => props.filters.search || '');
const dateFilter = ref(props.filters.date || '');

const tabs = computed(() => [
    { id: 'semua', name: 'Semua', count: props.stats.semua, active: props.filters.tab === 'semua' },
    { id: 'belum-lunas', name: 'Belum Lunas', count: props.stats.belum_lunas, active: props.filters.tab === 'belum-lunas' },
    { id: 'tunai-cod', name: 'Sistem Tunai/COD', count: props.stats.tunai_cod, active: props.filters.tab === 'tunai-cod', alert: true },
    { id: 'lunas-hari-ini', name: 'Lunas Hari Ini', count: props.stats.lunas_hari_ini, active: props.filters.tab === 'lunas-hari-ini' },
]);

// Removed local search watcher because Topbar handles it

watch(dateFilter, (value) => {
    router.get(route('operator.pembayaran'), {
        tab: props.filters.tab,
        search: searchStr.value,
        date: value
    }, { preserveState: true, preserveScroll: true, replace: true });
});

const selectTab = (tabId) => {
    router.get(route('operator.pembayaran'), { tab: tabId, search: searchStr.value, date: dateFilter.value }, { preserveState: true, preserveScroll: true });
};

// Modal Pembayaran
const showPayModal = ref(false);
const selectedOrder = ref(null);

const payForm = useForm({
    method: 'cash',
    amount_given: '',
});

const formatRupiah = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 'Rp0';
    const num = typeof val === 'string' ? parseFloat(val) : val;
    if (isNaN(num) || !num) return 'Rp0';
    return 'Rp' + new Intl.NumberFormat('id-ID').format(num);
};

const calculatedChange = computed(() => {
    if (!selectedOrder.value || !payForm.amount_given) return 0;
    const diff = parseFloat(payForm.amount_given) - parseFloat(selectedOrder.value.total_price);
    return diff > 0 ? diff : 0;
});

const isUnderpaid = computed(() => {
    if (!selectedOrder.value || !payForm.amount_given) return true;
    return parseFloat(payForm.amount_given) < parseFloat(selectedOrder.value.total_price);
});

const openPayModal = (order) => {
    selectedOrder.value = order;
    payForm.reset();
    payForm.method = ['cash', 'cod', 'transfer', 'ewallet'].includes(order.payment_method) ? order.payment_method : 'cash';
    payForm.amount_given = order.total_price;
    showPayModal.value = true;
};

const submitPayment = () => {
    payForm.put(route('operator.pembayaran.process', selectedOrder.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPayModal.value = false;
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
    <Head title="Manajemen Pembayaran - Hi Wash" />
    <DashboardLayout title="Manajemen Pembayaran">

        <div class="space-y-6 pb-20">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 xl:gap-6">
                <!-- Status Cards -->
                <div class="bg-surface border border-border rounded-lg p-5 flex flex-col shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)] hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-muted">Lunas Hari Ini</h3>
                        <div class="w-8 h-8 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i class="fa-solid fa-check-circle text-sm"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-text">{{ stats.lunas_hari_ini }}</p>
                        <p class="text-xs text-muted mt-2">Tagihan selesai</p>
                    </div>
                </div>

                <button @click="selectTab('belum-lunas')" class="bg-surface border border-border rounded-lg p-5 flex flex-col shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)] hover:border-amber-400 hover:shadow-md transition-all text-left group">
                    <div class="flex items-center justify-between mb-3 w-full">
                        <h3 class="text-sm font-medium text-muted group-hover:text-amber-600 transition-colors">Belum Lunas</h3>
                        <div class="w-8 h-8 rounded-md bg-amber-50 text-amber-500 flex items-center justify-center">
                            <i class="fa-solid fa-clock text-sm"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-text">{{ stats.belum_lunas }}</p>
                        <p class="text-xs text-amber-600 mt-2 font-medium">Perlu tindakan</p>
                    </div>
                </button>

                <button @click="selectTab('tunai-cod')" class="bg-surface border border-border rounded-lg p-5 flex flex-col shadow-[inset_0_2px_10px_rgba(0,0,0,0.05)] hover:border-blue-400 hover:shadow-md transition-all text-left group">
                    <div class="flex items-center justify-between mb-3 w-full">
                        <h3 class="text-sm font-medium text-muted group-hover:text-blue-600 transition-colors">Tunai / COD</h3>
                        <div class="w-8 h-8 rounded-md bg-blue-50 text-blue-500 flex items-center justify-center">
                            <i class="fa-solid fa-money-bill-wave text-sm"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-semibold text-text">{{ stats.tunai_cod }}</p>
                        <p class="text-xs text-blue-600 mt-2 font-medium">Bakal bayar nanti</p>
                    </div>
                </button>

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
                <div v-if="orders.data.length === 0" class="text-center py-12 text-muted text-sm border border-border rounded-lg bg-surface">
                    Tidak ada pesanan / tagihan ditemukan.
                </div>

                <template v-for="order in orders.data" :key="order.id">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between border rounded-lg bg-surface p-4 shadow-[inset_0_2px_10px_rgba(0,0,0,0.03)] hover:shadow-md transition-all gap-4"
                        :class="searchStr && (order.name.toLowerCase().includes(searchStr.toLowerCase()) || order.invoice?.toLowerCase().includes(searchStr.toLowerCase()) || order.phone?.includes(searchStr)) ? 'border-yellow-300 ring-1 ring-yellow-300' : 'border-border'">

                        <div class="flex-1">
                            <div class="flex items-center gap-2.5 mb-1 flex-wrap">
                                <span class="font-medium text-primary text-xs font-mono" v-html="highlight(order.invoice, searchStr)"></span>
                                <span class="font-semibold text-text text-base truncate max-w-[200px]" :title="order.name" v-html="highlight(order.name, searchStr)"></span>
                                
                                <span v-if="order.payment_status === 'paid'" class="bg-emerald-100 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">
                                    LUNAS
                                </span>
                                <span v-else class="bg-rose-100 text-rose-600 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">
                                    BELUM DIBAYAR
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-4 text-sm text-muted mt-2">
                                <div class="flex items-center gap-1.5 font-mono font-bold text-base text-text">
                                    {{ formatRupiah(order.total_price) }}
                                </div>
                                <div class="flex items-center gap-1.5 pt-1 lg:max-w-none text-xs">
                                     Metode: <strong class="uppercase">{{ order.payment_method === 'unspecified' ? 'BELUM DIPILIH' : order.payment_method }}</strong>
                                </div>
                                <div v-if="order.phone && order.phone !== '-'" class="flex items-center gap-1.5 pt-1 text-xs">
                                    <i class="fa-solid fa-phone"></i>
                                    <span v-html="highlight(order.phone, searchStr)"></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 w-full lg:w-auto justify-end mt-4 lg:mt-0">
                            <button v-if="order.payment_status !== 'paid' && order.calculated" @click="openPayModal(order)"
                                class="px-5 py-2.5 rounded-md font-bold text-sm transition-colors bg-[#E30613] hover:bg-black text-white shadow-sm flex items-center gap-2">
                                <i class="fas fa-money-bill-wave"></i> Konfirmasi Pembayaran
                            </button>
                            <button v-else-if="order.payment_status !== 'paid' && !order.calculated" disabled
                                class="px-5 py-2.5 rounded-md font-bold text-sm transition-colors bg-gray-200 text-gray-500 border border-gray-300 shadow-none cursor-not-allowed flex items-center gap-2">
                                <i class="fas fa-lock"></i> Menunggu Ditimbang
                            </button>

                            <button class="px-4 py-2.5 rounded-md font-medium text-sm transition-colors border border-border text-text hover:bg-container bg-surface">
                                Detail
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div v-if="orders.links && orders.links.length > 3" class="mt-6 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <template v-for="(link, key) in orders.links" :key="key">
                        <button
                            @click="link.url && router.get(link.url, { tab: filters.tab, search: searchStr }, { preserveState: true, preserveScroll: true })"
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

        <div v-if="showPayModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showPayModal = false"></div>

            <div class="bg-surface border-2 border-text shadow-md w-full max-w-sm relative z-10 rounded-none overflow-hidden font-sans">
                <!-- Ticket Header -->
                <div class="p-4 border-b-2 border-text bg-container flex justify-between items-center">
                    <h3 class="font-bold text-text text-xs uppercase tracking-widest">Konfirmasi Pembayaran</h3>
                    <span class="text-[10px] font-mono text-muted">{{ selectedOrder?.invoice }}</span>
                </div>

                <form @submit.prevent="submitPayment">
                    <div class="p-5 space-y-6">
                        <div class="border-2 border-dashed border-primary p-3 bg-primary/5 space-y-2 font-mono text-xs text-center">
                            <span class="text-[10px] font-bold text-primary uppercase tracking-tight block">Total Tagihan</span>
                            <span class="font-black text-2xl text-text block my-2">{{ formatRupiah(selectedOrder?.total_price) }}</span>
                            <span class="font-bold text-text truncate max-w-[150px] inline-block">{{ selectedOrder?.name }}</span>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-text uppercase tracking-widest mb-1.5">Metode Pembayaran</label>
                                <select v-model="payForm.method" class="w-full bg-surface border-2 border-border rounded-none px-3 py-2 text-sm focus:border-text focus:ring-0 outline-none text-text font-mono font-bold uppercase transition-colors uppercase">
                                    <option value="cash">Tunai (Cash)</option>
                                    <option value="cod">Tunai ke Kurir (COD)</option>
                                    <option value="transfer">Transfer Bank</option>
                                    <option value="ewallet">E-Wallet (QRIS)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-text uppercase tracking-widest mb-1.5 text-rose-600">
                                    Nominal Diterima (Rp) <span class="text-primary">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="number"
                                        v-model="payForm.amount_given"
                                        required
                                        :min="selectedOrder?.total_price"
                                        placeholder="0"
                                        class="w-full bg-surface border-2 border-border rounded-none px-3 py-3 text-lg font-black focus:border-[#E30613] focus:ring-0 outline-none text-[#E30613] transition-colors"
                                    />
                                    <div v-if="isUnderpaid" class="text-[10px] text-rose-500 font-bold mt-1.5 flex items-center gap-1">
                                        <i class="fas fa-exclamation-triangle"></i> Nominal kurang dari tagihan
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border-t border-dashed border-border pt-3">
                                <label class="block text-[10px] font-bold text-text uppercase tracking-widest mb-1.5">Kembalian</label>
                                <div class="font-mono text-xl font-bold text-emerald-600">
                                    {{ formatRupiah(calculatedChange) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket Footer -->
                    <div class="p-4 flex gap-2 border-t-2 border-text bg-container">
                        <button
                            type="button"
                            @click="showPayModal = false"
                            class="flex-1 py-2 text-xs font-bold uppercase tracking-wider text-muted hover:text-text border-2 border-transparent transition-colors">
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing || isUnderpaid"
                            class="flex-1 py-2 text-xs font-bold uppercase tracking-widest transition-all bg-[#E30613] hover:bg-black text-white border-2 border-transparent disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="payForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                            <span v-else>Bayar Lunas</span>
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