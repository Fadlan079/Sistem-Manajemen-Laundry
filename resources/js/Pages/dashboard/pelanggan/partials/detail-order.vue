<script setup>
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    auth: Object,
    order: {
        type: Object,
        required: true
    }
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const steps = computed(() => {
    let s = ['Dibuat'];
    if (props.order.hasPickup) s.push('Dijemput');
    s.push('Diproses');
    s.push('Selesai');
    if (props.order.hasDelivery) s.push('Diantar');
    return s;
});

const isExpanded = ref(false);
const isChangingPaymentMethod = ref(false);

const countdownText = ref('');
let countdownInterval = null;

const updateCountdown = () => {
    if (!props.order.pickup_timestamp || props.order.dbStatus !== 'pending') return;

    // Normalizing DB timestamp syntax 'YYYY-MM-DD HH:mm:ss' to 'YYYY-MM-DDT... '
    let rawTime = props.order.pickup_timestamp;
    if (rawTime && !rawTime.includes('T')) rawTime = rawTime.replace(' ', 'T');

    const targetDate = new Date(rawTime);
    const now = new Date();
    const diffMs = targetDate - now;

    if (diffMs <= 0) {
        countdownText.value = 'Waktu penjemputan telah tiba!';
        return;
    }

    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
    // const diffSecs = Math.floor((diffMs % (1000 * 60)) / 1000);
    // Not displaying seconds to avoid jitter, just Hour and Min is sufficient
    countdownText.value = `${diffHours} Jam ${diffMins} Menit`;
};

onMounted(() => {
    if (props.order.dbStatus === 'pending' && props.order.pickup_timestamp) {
        updateCountdown();
        countdownInterval = setInterval(updateCountdown, 60000); // per minute
    }
});

onUnmounted(() => {
    if (countdownInterval) clearInterval(countdownInterval);
});

const payForm = useForm({
    method: props.order.paymentMethodRaw || 'cash'
});

const getPaymentLabel = (val) => {
    if (val === 'cash' || val === 'cod') return 'Bayar Tunai ke Kurir';
    if (val === 'transfer') return 'Transfer Bank';
    if (val === 'ewallet' || val === 'e-wallet') return 'E-Wallet (QRIS)';
    return val;
};

function konfirmasiBayar() {
    payForm.post(route('pelanggan.aktivitas.bayar', props.order.dbId));
}

function batalkanPesanan() {
    if (confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')) {
        // Here we'd send inertia delete/cancel request
        router.post(route('pelanggan.aktivitas.batal', props.order.dbId));
    }
}

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
    if (!val) return 'Rp0';
    return 'Rp' + new Intl.NumberFormat('id-ID').format(val);
}

function downloadQR() {
    // Cari canvas di dalam ID invoiceQR
    const canvas = document.querySelector('#invoiceQR canvas');
    if (canvas) {
        const url = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.href = url;
        link.download = `QR-${props.order.invoice}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

const isCopied = ref(false);
function copyInvoice() {
    navigator.clipboard.writeText(props.order.invoice).then(() => {
        isCopied.value = true;
        setTimeout(() => {
            isCopied.value = false;
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy!', err);
    });
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
</script>

<template>
    <Head title="Detail Pesanan" />

    <AppLayout>
        <div class="pt-20 lg:pt-28 max-w-2xl mx-auto pb-32 lg:pb-12 space-y-4 px-4 sm:px-0">

            <Link :href="route('pelanggan.aktivitas')" class="flex items-center text-[11px] font-black text-gray-400 hover:text-[#E30613] uppercase tracking-widest transition-colors mb-2 group">
                <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                Kembali ke Aktivitas
            </Link>

            <!-- Flash success after order creation -->
            <div v-if="flash.success" class="bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm font-bold text-green-700 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500"></i>
                {{ flash.success }}
            </div>

            <section class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-8">
                    {{ order.paymentStatus === 'PAID' ? 'Progres Pesanan' : 'Progres Transaksi' }}
                </h2>

                <div class="relative flex items-center justify-between w-full px-2">
                    <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-gray-100 rounded-full z-0"></div>
                    <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-[#22C55E] rounded-full z-0 transition-all duration-1000"
                         :style="`width: ${(getStepIndex() / (steps.length - 1)) * 100}%` "></div>

                    <div v-for="(step, index) in steps" :key="index" class="relative z-10 flex flex-col items-center">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center transition-colors shadow-sm border"
                             :class="getStepIndex() >= index ? 'bg-[#22C55E] border-[#22C55E] text-white' : 'bg-white border-gray-200 text-gray-300'">
                            <i v-if="getStepIndex() >= index" class="fas fa-check text-[10px]"></i>
                            <span v-else class="text-[10px] font-bold">{{ index + 1 }}</span>
                        </div>
                        <span class="absolute top-8 text-[9px] font-black uppercase tracking-tighter whitespace-nowrap"
                              :class="getStepIndex() >= index ? 'text-[#22C55E]' : 'text-gray-400'">
                            {{ step }}
                        </span>
                    </div>
                </div>

                <div v-if="order.dbStatus === 'pending'" class="mt-14 space-y-3 text-center">
                    <div class="flex items-center gap-2 justify-center bg-blue-50 text-blue-600 font-bold py-1.5 px-4 rounded-full border border-blue-100 text-[10px] w-max mx-auto uppercase tracking-widest shadow-sm">
                        <i class="fas fa-info-circle"></i> Menunggu Penjemputan
                    </div>
                    <p v-if="countdownText" class="text-[11px] font-black text-gray-500 tracking-wider h-4 drop-shadow-sm">
                        Tersisa: <span class="text-blue-600">{{ countdownText }}</span>
                    </p>
                </div>
                <div v-else-if="order.dbStatus === 'dijemput'" class="mt-14 space-y-4 text-center">
                    <div class="flex items-center gap-2 justify-center bg-emerald-50 text-emerald-600 font-bold py-1.5 px-4 rounded-full border border-emerald-100 text-[10px] w-max mx-auto uppercase tracking-widest shadow-sm">
                        <i class="fas fa-motorcycle animate-pulse"></i> Kurir Sedang Di Jalan
                    </div>
                </div>
                <div v-else-if="order.dbStatus === 'diproses'" class="mt-14 space-y-4 text-center">
                    <div class="flex items-center gap-2 justify-center bg-blue-50 text-blue-600 font-bold py-1.5 px-4 rounded-full border border-blue-100 text-[10px] w-max mx-auto uppercase tracking-widest shadow-sm">
                        <i class="fas fa-tshirt animate-bounce"></i> Pakaian Sedang Diproses
                    </div>
                </div>
                <div v-else-if="order.dbStatus === 'selesai' && order.paymentStatus === 'UNPAID'" class="mt-14 space-y-3 text-center">
                    <div class="flex items-center gap-2 justify-center bg-yellow-50 text-yellow-600 font-bold py-1.5 px-4 rounded-full border border-yellow-100 text-[10px] w-max mx-auto uppercase tracking-widest shadow-sm">
                        <i class="fas fa-exclamation-circle animate-pulse"></i> Menunggu Pembayaran
                    </div>
                    <p class="text-[10px] font-black text-gray-400 tracking-wider px-4 leading-relaxed mt-2 uppercase">
                         Selesaikan via sistem / Siapkan uang tunai saat pengantaran.
                    </p>
                </div>
                <div v-else-if="order.dbStatus === 'selesai' && order.paymentStatus === 'PAID'" class="mt-14 space-y-4 text-center">
                    <div class="flex items-center gap-2 justify-center bg-emerald-50 text-emerald-600 font-bold py-1.5 px-4 rounded-full border border-emerald-100 text-[10px] w-max mx-auto uppercase tracking-widest shadow-sm">
                        <i class="fas fa-box"></i> Pesanan Siap Diantar
                    </div>
                </div>
                <div v-else-if="order.dbStatus === 'diantar'" class="mt-14 space-y-4 text-center">
                    <div class="flex items-center gap-2 justify-center bg-purple-50 text-purple-600 font-bold py-1.5 px-4 rounded-full border border-purple-100 text-[10px] w-max mx-auto uppercase tracking-widest shadow-sm">
                        <i class="fas fa-motorcycle animate-pulse"></i> Kurir Sedang Mengantar
                    </div>
                </div>
            </section>

            <section class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center gap-4 bg-gray-50/30">
                    <div class="w-12 h-12 bg-[#E30613]/10 text-[#E30613] rounded border border-red-100 shadow-sm flex items-center justify-center text-xl">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div>
                        <h2 class="font-black text-gray-900 text-sm tracking-tight">{{ order.service }}</h2>
                        <p v-if="order.isKg && !order.isCalculated" class="text-[10px] text-gray-500 font-medium">Berat akan ditimbang saat penjemputan</p>
                        <p v-else-if="order.isKg && order.isCalculated" class="text-[11px] font-bold text-[#E30613] mt-0.5">Sudah Ditimbang</p>
                        <p v-else class="text-[11px] font-bold text-[#E30613] mt-0.5">Harga Berdasarkan Jumlah Item</p>
                    </div>
                </div>

                <div class="p-5 space-y-4">
                    <div v-if="order.laundry_notes" class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                        <i class="fas fa-clipboard-list text-gray-400 mt-1 text-base w-4 text-center"></i>
                        <div class="flex-1">
                            <span class="font-bold text-gray-900 block mb-1">Catatan Layanan</span>
                            <span class="font-medium text-gray-500 block leading-relaxed italic">"{{ order.laundry_notes }}"</span>
                        </div>
                    </div>

                    <div v-if="order.pickup_address" class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                        <i class="fas fa-map-marker-alt text-gray-400 mt-1 text-base w-4 text-center"></i>
                        <div class="flex-1">
                            <span class="font-bold text-gray-900 block mb-1">Alamat Penjemputan</span>
                            <span class="font-medium text-gray-500 block leading-relaxed">{{ order.pickup_address }}</span>
                            
                            <!-- Catatan Kurir -->
                            <div v-if="order.courier_notes" class="mt-2.5 bg-[#E30613]/5 border border-red-100 rounded-lg p-2.5 text-[10px] text-gray-700 flex gap-2 shadow-sm w-full">
                                <i class="fas fa-comment-dots text-[#E30613] mt-0.5 shrink-0"></i>
                                <span class="leading-relaxed"><strong class="font-bold text-[#E30613]">Catatan Kurir:</strong> {{ order.courier_notes }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                        <i class="fas fa-calendar-alt text-gray-400 mt-1 text-base w-4 text-center"></i>
                        <div>
                            <span class="font-bold text-gray-900 block mb-1">Jadwal Penjemputan</span>
                            <span class="font-medium text-gray-500">{{ order.pickup_date_text || order.date }}</span>
                        </div>
                    </div>

                    <!-- Nomor Invoice & QR (Pusat & Besar) -->
                    <div class="bg-gray-50/50 p-8 rounded-2xl border border-gray-100 flex flex-col items-center justify-center space-y-5">
                        <div class="relative">
                            <div class="p-3 bg-white rounded-2xl shadow-sm border border-gray-200">
                                <QrcodeVue :value="order.invoice" :size="140" level="H" />
                            </div>
                            <!-- Small Floating Download Button -->
                            <button @click="downloadQR" 
                                class="absolute -top-2 -right-2 w-9 h-9 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-400 hover:text-[#E30613] hover:border-[#E30613] shadow-sm transition-all active:scale-90"
                                title="Download QR">
                                <i class="fas fa-download text-xs"></i>
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Nomor Invoice</p>
                            <div class="flex items-center justify-center gap-2">
                                <code class="text-sm font-black text-gray-900 tracking-tight">{{ order.invoice }}</code>
                                <button @click="copyInvoice" 
                                    :class="[isCopied ? 'text-green-500' : 'text-gray-400 hover:text-[#E30613]', 'transition-colors p-1']"
                                    :title="isCopied ? 'Tersalin!' : 'Salin Invoice'">
                                    <i :class="isCopied ? 'fas fa-check' : 'far fa-copy text-[10px]'"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Hidden Rendered QR for Download -->
                        <div class="hidden" id="invoiceQR">
                            <QrcodeVue :value="order.invoice" :size="500" level="H" />
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t-2 border-dashed border-gray-300 space-y-2">
                        <h3 class="text-[11px] font-black uppercase text-gray-500 tracking-wider mb-2">Rincian Biaya</h3>

                        <!-- Biaya Layanan -->
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">
                                Biaya Layanan
                                <span v-if="order.isKg && !order.isCalculated && order.estimated_weight">(~{{ estimatedKG.min }}-{{ estimatedKG.max }} kg)</span>
                                <span v-else-if="order.isKg && order.isCalculated">({{ order.items_qty }} kg)</span>
                                <span v-else>({{ order.items_qty }} {{ order.unit.replace('/', '') }})</span>
                            </span>
                            <span class="font-bold text-gray-800">{{ estimatedServiceCostText }}</span>
                        </div>

                        <!-- Extra Info on service price -->
                        <div v-if="order.isCalculated" class="text-[10px] text-gray-400 -mt-1 font-medium">
                            {{ formatRupiah(order.service_price) }}{{ order.unit }}
                        </div>

                        <!-- Ongkos Kirim -->
                        <div class="flex justify-between text-xs pt-1">
                            <span class="text-gray-600">Ongkos Kirim</span>
                            <span class="font-bold text-gray-800">{{ formatRupiah(order.fee) }}</span>
                        </div>

                        <!-- Total Biaya -->
                        <div class="flex justify-between text-sm pt-2 mt-2 border-t border-gray-200">
                            <span class="font-black text-gray-800">
                                Total Biaya
                                <span v-if="order.isKg && !order.isCalculated" class="text-gray-400 font-medium text-[10px] uppercase">(Estimasi)</span>
                            </span>
                            <span class="font-black text-[#E30613]">{{ estimatedTotalCostText }}</span>
                        </div>
                    </div>

                    <!-- Not Calculated Note -->
                    <template v-if="order.isKg && !order.isCalculated">
                        <div class="bg-blue-50/50 border border-blue-100 rounded-lg p-3 mt-4 flex gap-3 text-blue-700 text-[11px] items-start shadow-sm">
                            <i class="fas fa-lightbulb mt-0.5"></i>
                            <p class="leading-relaxed">Berat & harga layanan di atas masih berupa perkiraan. Harga akhir akan ditentukan setelah pesanan dijemput dan ditimbang.</p>
                        </div>

                        <button
                            v-if="order.dbStatus === 'pending'"
                            @click="batalkanPesanan"
                            class="w-full mt-4 py-3 rounded-lg font-bold text-[#E30613] text-xs bg-red-50 hover:bg-[#E30613] hover:text-white border border-red-100 transition-colors shadow-sm">
                            Batalkan Pesanan
                        </button>
                    </template>
                    <template v-else-if="order.dbStatus === 'pending'">
                        <!-- Even if calculated (PCS), if still pending allow cancellation -->
                         <button
                            @click="batalkanPesanan"
                            class="w-full mt-4 py-3 rounded-lg font-bold text-[#E30613] text-xs bg-red-50 hover:bg-[#E30613] hover:text-white border border-red-100 transition-colors shadow-sm">
                            Batalkan Pesanan
                        </button>
                    </template>
                </div>
            </section>

            <!-- Show Payment Section Always -->
            <section class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 space-y-5">
                <div v-if="order.paymentStatus === 'UNPAID'" class="space-y-4">
                    <h3 class="font-black text-sm text-gray-900 border-b border-gray-100 pb-2">Informasi Pembayaran</h3>

                    <!-- View Mode (Satu Box) -->
                    <div v-if="!isChangingPaymentMethod" class="flex items-center justify-between border-2 border-red-100 bg-red-50 p-4 rounded-xl shadow-sm transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center border border-red-200 text-[#E30613] shadow-sm shrink-0">
                                <i :class="['cash', 'cod'].includes(payForm.method) ? 'fas fa-money-bill-wave' : (payForm.method === 'transfer' ? 'fas fa-credit-card' : 'fas fa-qrcode')" class="text-lg"></i>
                            </div>
                            <div>
                                <p class="text-[9px] uppercase font-black tracking-[0.1em] text-[#E30613] mb-0.5">Pilihan Anda</p>
                                <p class="text-[13px] font-bold text-gray-900 leading-tight drop-shadow-sm">{{ getPaymentLabel(payForm.method) }}</p>
                            </div>
                        </div>
                        <button @click="isChangingPaymentMethod = true" class="text-[10px] font-bold text-[#E30613] bg-white border border-red-200 px-3 py-2 rounded-lg shadow-sm hover:bg-gray-50 uppercase tracking-widest transition-colors flex items-center gap-1.5 shrink-0">
                            <i class="fas fa-edit"></i> Ganti
                        </button>
                    </div>

                    <!-- Edit Mode (Radio Buttons) -->
                    <div v-else class="space-y-2 relative border-2 border-gray-100 rounded-xl p-4 bg-gray-50/50">
                        <div class="flex justify-between items-center mb-3">
                           <span class="text-xs font-black text-gray-700 uppercase tracking-wide">Pilih Metode Baru</span>
                           <button @click="isChangingPaymentMethod = false" class="text-gray-400 hover:text-[#E30613] text-[10px] font-bold transition-colors">
                               <i class="fas fa-times"></i> Batal
                           </button>
                        </div>

                        <label class="flex items-center gap-3 border-2 p-3 rounded-lg cursor-pointer transition-colors"
                            :class="payForm.method === 'cash' || payForm.method === 'cod' ? 'border-[#E30613] bg-red-50/30' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <input type="radio" v-model="payForm.method" value="cash" class="w-4 h-4 text-[#E30613] focus:ring-[#E30613]">
                            <span class="text-xs font-bold text-gray-800">Bayar Tunai ke Kurir (COD)</span>
                        </label>

                        <label class="flex items-center gap-3 border-2 p-3 rounded-lg cursor-pointer transition-colors"
                            :class="payForm.method === 'transfer' ? 'border-[#E30613] bg-red-50/30' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <input type="radio" v-model="payForm.method" value="transfer" class="w-4 h-4 text-[#E30613] focus:ring-[#E30613]">
                            <span class="text-xs font-bold text-gray-800">Transfer Bank</span>
                        </label>

                        <label class="flex items-center gap-3 border-2 p-3 rounded-lg cursor-pointer transition-colors"
                            :class="payForm.method === 'ewallet' || payForm.method === 'e-wallet' ? 'border-[#E30613] bg-red-50/30' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <input type="radio" v-model="payForm.method" value="ewallet" class="w-4 h-4 text-[#E30613] focus:ring-[#E30613]">
                            <span class="text-xs font-bold text-gray-800">E-Wallet (QRIS)</span>
                        </label>
                        
                        <div class="pt-2 text-right">
                           <button @click="isChangingPaymentMethod = false" class="text-[10px] bg-gray-900 text-white px-3 py-1.5 rounded-lg font-bold shadow-sm hover:bg-black transition-colors">Terapkan</button>
                        </div>
                    </div>

                    <!-- Desktop Payment Button -->
                    <button v-if="order.isCalculated && !['cash', 'cod'].includes(payForm.method)"
                        @click="konfirmasiBayar"
                        :disabled="payForm.processing"
                        class="hidden lg:flex w-full mt-4 py-3 rounded-lg font-bold text-white text-xs bg-[#E30613] shadow-md hover:bg-black transition-colors justify-center items-center">
                        <i v-if="payForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                        Bayar Sekarang
                    </button>
                    <button v-else-if="order.isCalculated"
                        disabled
                        class="hidden lg:flex w-full mt-4 py-3 rounded-lg font-bold text-gray-500 text-xs bg-gray-200 cursor-not-allowed justify-center items-center border border-gray-300 shadow-sm">
                        Bayar Tunai ke Kurir / Operator
                    </button>
                    <button v-else
                        disabled
                        class="hidden lg:flex w-full mt-4 py-3 rounded-lg font-bold text-white text-xs bg-gray-400 cursor-not-allowed justify-center items-center">
                        Menunggu Penimbangan Kurir
                    </button>

                    <div class="bg-blue-50/50 rounded-lg p-3 mt-4 flex gap-3 text-blue-700 text-[11px] items-start">
                        <i class="fas fa-lightbulb mt-0.5 text-base"></i>
                        <p class="leading-relaxed">Anda bisa bayar via Tunai <strong class="font-bold">saat penjemputan atau pengantaran</strong> oleh kurir kami, atau selesaikan sekarang via Transfer / E-Wallet.</p>
                    </div>
                </div>

                <div v-else class="space-y-4">
                    <div class="flex items-center justify-between border-b pb-4">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Status Pembayaran</p>
                        <span class="bg-[#22C55E] text-white text-[9px] px-2.5 py-1 rounded font-black uppercase tracking-widest">
                            SUDAH DIBAYAR
                        </span>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-500 mb-1">Metode Pembayaran</p>
                        <p class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ order.paymentMethod }}</p>
                    </div>
                    <button class="w-full bg-gray-900 py-3 rounded-lg font-bold text-white text-xs mt-2 flex justify-center items-center gap-2 hover:bg-black transition-colors">
                        <i class="fas fa-print"></i> Cetak Nota Lengkap
                    </button>
                </div>
            </section>

            <section v-if="order.review" class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Penilaian Layanan Anda</p>
                    <div class="flex gap-0.5">
                        <template v-for="i in 5" :key="i">
                            <i class="fas fa-star text-sm" :class="i <= order.review.rating ? 'text-[#FFE800]' : 'text-gray-200'"></i>
                        </template>
                    </div>
                </div>

                <div>
                    <p v-if="order.review.comment" class="text-xs text-gray-600 leading-relaxed bg-gray-50 border border-gray-100 p-3 rounded-lg italic">
                        "{{ order.review.comment }}"
                    </p>
                    <p v-else class="text-xs text-gray-500 bg-gray-50 border border-gray-100 p-3 rounded-lg italic">
                        Tidak ada komentar tambahan.
                    </p>
                    <p class="text-[10px] text-gray-400 font-medium mt-3 text-right">{{ order.review.date }}</p>
                </div>
            </section>

        </div>

        <!-- Mobile Fixed Action Bar (Payment) -->
        <div v-if="order.paymentStatus === 'UNPAID'" class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 px-5 pt-3 pb-[calc(1rem+env(safe-area-inset-bottom))] shadow-[0_-4px_15px_rgba(0,0,0,0.05)] lg:hidden flex justify-between items-center rounded-t-2xl">
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">
                    {{ order.isCalculated ? 'Total Tagihan' : 'Batas Estimasi' }}
                </p>
                <p class="text-[17px] font-black text-gray-900 leading-none">
                    {{ estimatedTotalCostText }}
                </p>
            </div>

            <!-- Calculated: Pay Now -->
            <button v-if="order.isCalculated && !['cash', 'cod'].includes(payForm.method)" type="button" @click="konfirmasiBayar"
                :disabled="payForm.processing"
                class="px-8 py-3.5 rounded-full font-bold text-white text-sm shadow-md transition-all active:scale-95 flex items-center justify-center gap-2"
                :class="payForm.processing ? 'bg-gray-300 cursor-not-allowed text-gray-500 shadow-none' : 'bg-[#E30613] hover:bg-[#B7050F] shadow-[0_4px_14px_rgba(227,6,19,0.3)]'">
                <i v-if="payForm.processing" class="fas fa-spinner fa-spin"></i>
                <span v-if="!payForm.processing">Bayar Sekarang</span>
            </button>
            <button v-else-if="order.isCalculated" type="button" disabled
                class="px-6 py-3.5 rounded-full font-bold text-gray-500 text-[11px] bg-gray-200 border border-gray-300 cursor-not-allowed shadow-none flex items-center justify-center gap-2">
                Bayar Tunai ke Kurir
            </button>
            
            <!-- Not Calculated: Wait for courier -->
            <button v-else type="button" disabled
                class="px-6 py-3.5 rounded-full font-bold text-white text-xs bg-gray-400 cursor-not-allowed shadow-none flex items-center justify-center gap-2">
                Tunggu Finalisasi
            </button>
        </div>
    </AppLayout>
</template>
