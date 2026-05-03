<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/dashboard.vue';
import { computed } from 'vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    auth: Object,
    order: {
        type: Object,
        required: true
    }
});

const steps = computed(() => {
    let s = ['Dibuat'];
    if (props.order.hasPickup) s.push('Dijemput');
    s.push('Diproses');
    s.push('Selesai');
    if (props.order.hasDelivery) s.push('Diantar');
    return s;
});

function getStepIndex() {
    let current = 'Dibuat';
    if (props.order.dbStatus === 'pending') current = 'Dibuat';
    else if (props.order.dbStatus === 'dijemput') current = 'Dijemput';
    else if (props.order.dbStatus === 'diproses') current = 'Diproses';
    else if (props.order.dbStatus === 'diantar') current = 'Diantar';
    else if (props.order.dbStatus === 'selesai') current = 'Selesai';
    return steps.value.indexOf(current);
}

function formatRupiah(val) {
    if (val === null || val === undefined || isNaN(val)) return 'Rp0';
    const num = typeof val === 'string' ? parseFloat(val) : val;
    if (isNaN(num) || !num) return 'Rp0';
    return 'Rp' + new Intl.NumberFormat('id-ID').format(num);
}

const estimatedKG = computed(() => {
    if (!props.order.isKg || props.order.isCalculated || !props.order.estimated_weight) return { min: 0, max: 0 };
    const key = props.order.estimated_weight;
    const minMap = { '<3': 1, '3-5': 3, '5-10': 5, '>10': 10 };
    const maxMap = { '<3': 3, '3-5': 5, '5-10': 10, '>10': 15 };
    return {
        min: minMap[key] || 0,
        max: maxMap[key] || 0
    };
});

const estimatedServiceCostText = computed(() => {
    if (!props.order.isKg || props.order.isCalculated) return formatRupiah(props.order.price);
    const minCost = props.order.service_price * estimatedKG.value.min;
    const maxCost = props.order.service_price * estimatedKG.value.max;
    return `${formatRupiah(minCost)} - ${formatRupiah(maxCost)}`;
});

const estimatedTotalCostText = computed(() => {
    if (!props.order.isKg || props.order.isCalculated) return formatRupiah(props.order.total);
    const minTot = (props.order.service_price * estimatedKG.value.min) + props.order.fee;
    const maxTot = (props.order.service_price * estimatedKG.value.max) + props.order.fee;
    return `${formatRupiah(minTot)} - ${formatRupiah(maxTot)}`;
});

const formatPhoneWA = (phone) => {
    if (!phone) return '';
    let formatted = phone.replace(/\D/g, '');
    if (formatted.startsWith('0')) {
        formatted = '62' + formatted.substring(1);
    }
    return formatted;
};

const waLink = computed(() => {
    if (!props.order.customerPhone || props.order.customerPhone === '-') return null;
    return `https://wa.me/${formatPhoneWA(props.order.customerPhone)}`;
});

</script>

<template>
    <Head title="Detail Pesanan - Kurir" />

    <DashboardLayout title="Detail Pesanan">
        <div class="max-w-2xl mx-auto pb-32 lg:pb-12 space-y-4 px-4 sm:px-0">

            <Link :href="route('kurir.dashboard')" class="flex items-center text-[11px] font-black text-slate-400 hover:text-primary uppercase tracking-widest transition-colors mb-2 group">
                <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                Kembali ke Tugas Saya
            </Link>

            <section class="bg-surface p-6 rounded-lg border border-border shadow-sm">
                <h2 class="text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-8">
                    Progres Pesanan
                </h2>

                <div class="relative flex items-center justify-between w-full px-2">
                    <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-container rounded-full z-0"></div>
                    <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-emerald-500 rounded-full z-0 transition-all duration-1000"
                         :style="`width: ${(getStepIndex() / (steps.length - 1)) * 100}%` "></div>

                    <div v-for="(step, index) in steps" :key="index" class="relative z-10 flex flex-col items-center">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center transition-colors shadow-sm border"
                             :class="getStepIndex() >= index ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-surface border-border text-muted'">
                            <i v-if="getStepIndex() >= index" class="fas fa-check text-[10px]"></i>
                            <span v-else class="text-[10px] font-bold">{{ index + 1 }}</span>
                        </div>
                        <span class="absolute top-8 text-[9px] font-black uppercase tracking-tighter whitespace-nowrap"
                              :class="getStepIndex() >= index ? 'text-emerald-500' : 'text-muted'">
                            {{ step }}
                        </span>
                    </div>
                </div>

                <!-- Info Box based on dbStatus -->
                <div v-if="order.dbStatus === 'pending'" class="mt-14 space-y-3 text-center">
                    <div class="flex items-center gap-2 justify-center bg-blue-50 text-blue-600 font-bold py-1.5 px-4 rounded-full border border-blue-100 text-[10px] w-max mx-auto uppercase tracking-widest shadow-sm">
                        <i class="fas fa-info-circle"></i> Menunggu Penjemputan Anda
                    </div>
                </div>
                <div v-else-if="order.dbStatus === 'dijemput'" class="mt-14 space-y-4 text-center">
                    <div class="flex items-center gap-2 justify-center bg-emerald-50 text-emerald-600 font-bold py-1.5 px-4 rounded-full border border-emerald-100 text-[10px] w-max mx-auto uppercase tracking-widest shadow-sm">
                        <i class="fas fa-motorcycle animate-pulse"></i> Anda Sedang Menjemput
                    </div>
                </div>
                <div v-else-if="order.dbStatus === 'diproses'" class="mt-14 space-y-4 text-center">
                    <div class="flex items-center gap-2 justify-center bg-blue-50 text-blue-600 font-bold py-1.5 px-4 rounded-full border border-blue-100 text-[10px] w-max mx-auto uppercase tracking-widest shadow-sm">
                        <i class="fas fa-tshirt animate-bounce"></i> Pakaian Sedang Diproses
                    </div>
                </div>
                <div v-else-if="order.dbStatus === 'selesai' && order.paymentStatus === 'PAID'" class="mt-14 space-y-4 text-center">
                    <div class="flex items-center gap-2 justify-center bg-emerald-50 text-emerald-600 font-bold py-1.5 px-4 rounded-full border border-emerald-100 text-[10px] w-max mx-auto uppercase tracking-widest shadow-sm">
                        <i class="fas fa-box"></i> Siap Anda Antar
                    </div>
                </div>
                <div v-else-if="order.dbStatus === 'diantar'" class="mt-14 space-y-4 text-center">
                    <div class="flex items-center gap-2 justify-center bg-purple-50 text-purple-600 font-bold py-1.5 px-4 rounded-full border border-purple-100 text-[10px] w-max mx-auto uppercase tracking-widest shadow-sm">
                        <i class="fas fa-motorcycle animate-pulse"></i> Anda Sedang Mengantar
                    </div>
                </div>
            </section>

            <section class="bg-surface rounded-lg border border-border shadow-sm overflow-hidden">
                <div class="p-5 border-b border-border flex flex-col md:flex-row md:items-center justify-between gap-4 bg-container/30">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-primary/10 text-primary rounded border border-primary/20 shadow-sm flex items-center justify-center text-xl">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <div>
                            <h2 class="font-black text-text text-sm tracking-tight">{{ order.service }}</h2>
                            <p v-if="order.isKg && !order.isCalculated" class="text-[10px] text-muted font-medium">Jangan lupa timbang berat saat jemput</p>
                            <p v-else-if="order.isKg && order.isCalculated" class="text-[11px] font-bold text-primary mt-0.5">Sudah Ditimbang ({{ order.items_qty }} KG)</p>
                            <p v-else class="text-[11px] font-bold text-primary mt-0.5">Harga Fix ({{ order.items_qty }} Item)</p>
                        </div>
                    </div>
                    
                    <!-- ACTION HUBUNGI CUSTOMER -->
                    <a v-if="waLink" :href="waLink" target="_blank"
                       class="shrink-0 flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg font-bold text-white text-xs bg-emerald-500 hover:bg-emerald-600 transition-colors shadow-sm w-full md:w-auto">
                        <i class="fa-brands fa-whatsapp text-lg"></i>
                        Hubungi Pelanggan
                    </a>
                </div>

                <div class="p-5 space-y-4">
                    <div class="flex gap-4 items-start text-xs border-b border-border pb-4">
                        <i class="fas fa-user text-muted mt-1 text-base w-4 text-center"></i>
                        <div class="flex-1">
                            <span class="font-bold text-text block mb-1">Informasi Pelanggan</span>
                            <span class="font-medium text-text block leading-relaxed">{{ order.customerName }}</span>
                            <span class="font-mono text-muted block leading-relaxed">{{ order.customerPhone }}</span>
                        </div>
                    </div>

                    <div v-if="order.laundry_notes" class="flex gap-4 items-start text-xs border-b border-border pb-4">
                        <i class="fas fa-clipboard-list text-muted mt-1 text-base w-4 text-center"></i>
                        <div class="flex-1">
                            <span class="font-bold text-text block mb-1">Catatan Layanan Pelanggan</span>
                            <span class="font-medium text-muted block leading-relaxed italic">"{{ order.laundry_notes }}"</span>
                        </div>
                    </div>

                    <div v-if="order.pickup_address" class="flex gap-4 items-start text-xs border-b border-border pb-4">
                        <i class="fas fa-map-marker-alt text-muted mt-1 text-base w-4 text-center"></i>
                        <div class="flex-1">
                            <span class="font-bold text-text block mb-1">Alamat Penjemputan / Pengantaran</span>
                            <span class="font-medium text-muted block leading-relaxed">{{ order.pickup_address }}</span>
                        </div>
                    </div>

                    <!-- Nomor Invoice -->
                    <div class="bg-container p-4 rounded-2xl border border-border flex items-center justify-center space-y-2 mt-4">
                         <div class="text-center w-full flex items-center justify-between">
                            <p class="text-[10px] font-black text-muted uppercase tracking-widest mb-0.5">Invoice / Resi</p>
                            <code class="text-sm font-black text-primary tracking-tight">{{ order.invoice }}</code>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t-2 border-dashed border-border space-y-2">
                        <h3 class="text-[11px] font-black uppercase text-muted tracking-wider mb-2">Estimasi Tagihan Pelanggan</h3>

                        <!-- Total Biaya -->
                        <div class="flex justify-between text-sm py-2">
                            <span class="font-bold text-text">
                                Total Biaya
                                <span v-if="order.isKg && !order.isCalculated" class="text-muted font-medium text-[10px] uppercase ml-1">(Estimasi)</span>
                            </span>
                            <span class="font-black text-primary">{{ estimatedTotalCostText }}</span>
                        </div>

                        <div class="flex justify-between text-xs py-2 border-t border-border mt-1">
                            <span class="font-bold text-muted uppercase tracking-widest text-[9px]">Status Bayar</span>
                            <span class="font-black" :class="order.paymentStatus === 'PAID' ? 'text-emerald-500' : 'text-rose-500'">
                                {{ order.paymentStatus === 'PAID' ? 'LUNAS (' + order.paymentMethod + ')' : 'BELUM DIBAYAR' }}
                            </span>
                        </div>
                    </div>

                    <!-- Not Calculated Note -->
                    <template v-if="order.isKg && !order.isCalculated">
                        <div class="bg-blue-50/50 border border-blue-100 rounded-lg p-3 mt-4 flex gap-3 text-blue-700 text-[11px] items-start shadow-sm">
                            <i class="fas fa-lightbulb mt-0.5"></i>
                            <p class="leading-relaxed">Biaya adalah estimasi menurut pelanggan. <strong>Pastikan timbang barang dan update di form selesai</strong>.</p>
                        </div>
                    </template>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
