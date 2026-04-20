<script setup>
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';
import { ref, computed } from 'vue';
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
    if (props.order.hasDelivery) s.push('Diantar');
    s.push('Selesai');
    return s;
});

const isExpanded = ref(false);

const payForm = useForm({
    method: 'cod' // default COD
});

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
</script>

<template>
    <Head title="Detail Pesanan" />

    <AppLayout>
        <div class="pt-20 lg:pt-28 max-w-2xl mx-auto pb-12 space-y-4 px-4 sm:px-0">
            
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

                <div v-if="!order.isCalculated && order.dbStatus === 'pending'" class="mt-14 space-y-4">
                    <div class="flex items-center gap-2 justify-center bg-blue-50 text-blue-600 font-bold py-1.5 px-4 rounded-full border border-blue-100 text-[10px] w-max mx-auto uppercase tracking-widest">
                        <i class="fas fa-info-circle"></i> Menunggu Penjemputan
                    </div>
                </div>
                <div v-else-if="!order.isCalculated && order.dbStatus === 'dijemput'" class="mt-14 space-y-4">
                    <div class="flex items-center gap-2 justify-center bg-emerald-50 text-emerald-600 font-bold py-1.5 px-4 rounded-full border border-emerald-100 text-[10px] w-max mx-auto uppercase tracking-widest">
                        <i class="fas fa-motorcycle animate-pulse"></i> Kurir Sedang Di Jalan
                    </div>
                </div>
                <div v-else-if="order.isCalculated && order.paymentStatus === 'UNPAID'" class="mt-14 space-y-4">
                    <div class="flex items-center gap-2 justify-center bg-yellow-50 text-yellow-600 font-bold py-1.5 px-4 rounded-full border border-yellow-100 text-[10px] w-max mx-auto uppercase tracking-widest">
                        <i class="fas fa-exclamation-circle"></i> Menunggu Pembayaran
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
                        <p v-if="!order.isCalculated" class="text-[10px] text-gray-500 font-medium">Berat akan ditimbang saat penjemputan</p>
                        <p v-else class="text-[11px] font-bold text-[#E30613] mt-0.5">Sudah Ditimbang</p>
                    </div>
                </div>
                
                <div class="p-5 space-y-4">
                    <div v-if="order.pickup_address" class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                        <i class="fas fa-map-marker-alt text-gray-400 mt-1 text-base"></i>
                        <div>
                            <span class="font-bold text-gray-900 block mb-1">Alamat Penjemputan</span>
                            <span class="font-medium text-gray-500">{{ order.pickup_address }}</span>
                        </div>
                    </div>

                    <div class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                        <i class="fas fa-calendar-alt text-gray-400 mt-1 text-base"></i>
                        <div>
                            <span class="font-bold text-gray-900 block mb-1">Jadwal / Waktu Masuk</span>
                            <span class="font-medium text-gray-500">{{ order.date }}</span>
                        </div>
                    </div>

                    <!-- Not Calculated Mode -->
                    <template v-if="!order.isCalculated">
                        <div class="flex justify-between items-center text-xs pt-2">
                            <span class="font-bold text-gray-700">Total Sementara</span>
                            <span class="font-black text-gray-900">— Belum Dihitung —</span>
                        </div>

                        <div class="bg-blue-50/50 rounded-lg p-3 mt-4 flex gap-3 text-blue-700 text-[11px] items-start">
                            <i class="fas fa-lightbulb mt-0.5"></i>
                            <p class="leading-relaxed">Berat & harga akhir akan ditentukan setelah pesanan dijemput dan ditimbang di outlet kami.</p>
                        </div>

                        <button 
                            v-if="order.dbStatus === 'pending'"
                            @click="batalkanPesanan"
                            class="w-full mt-4 py-3 rounded-lg font-bold text-white text-xs bg-[#E30613] hover:bg-black transition-colors shadow-sm">
                            Batalkan Pesanan
                        </button>
                    </template>

                    <!-- Calculated Mode -->
                    <template v-else>
                        <div class="bg-gray-50 border border-gray-100 rounded-lg p-4 space-y-2 mt-2">
                            <div class="flex justify-between text-xs">
                                <span class="font-medium text-gray-700">Berat: {{ order.items_qty }} kg</span>
                                <span class="font-black text-gray-900">{{ formatRupiah(order.total) }}</span>
                            </div>
                            <div class="text-[10px] text-gray-500 font-medium">
                                Harga/kg: {{ formatRupiah(order.service_price) }}
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <section v-if="order.isCalculated" class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 space-y-5">
                <div v-if="order.paymentStatus === 'UNPAID'" class="space-y-4">
                    <h3 class="font-black text-sm text-gray-900 border-b border-gray-100 pb-2">Pilih Metode Pembayaran</h3>
                    
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 border p-3 rounded-lg cursor-pointer transition-colors"
                            :class="payForm.method === 'cod' ? 'border-[#E30613] bg-red-50/20' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" v-model="payForm.method" value="cod" class="w-4 h-4 text-[#E30613] focus:ring-[#E30613]">
                            <span class="text-xs font-bold text-gray-800">Bayar ke Kurir (COD)</span>
                        </label>

                        <label class="flex items-center gap-3 border p-3 rounded-lg cursor-pointer transition-colors"
                            :class="payForm.method === 'transfer' ? 'border-[#E30613] bg-red-50/20' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" v-model="payForm.method" value="transfer" class="w-4 h-4 text-[#E30613] focus:ring-[#E30613]">
                            <span class="text-xs font-bold text-gray-800">Transfer Bank</span>
                        </label>

                        <label class="flex items-center gap-3 border p-3 rounded-lg cursor-pointer transition-colors"
                            :class="payForm.method === 'ewallet' ? 'border-[#E30613] bg-red-50/20' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" v-model="payForm.method" value="ewallet" class="w-4 h-4 text-[#E30613] focus:ring-[#E30613]">
                            <span class="text-xs font-bold text-gray-800">E-Wallet (QRIS)</span>
                        </label>
                    </div>

                    <button
                        @click="konfirmasiBayar"
                        :disabled="payForm.processing"
                        class="w-full mt-4 py-3 rounded-lg font-bold text-white text-xs bg-[#E30613] shadow-md hover:bg-black transition-colors">
                        <i v-if="payForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                        Bayar Sekarang
                    </button>

                    <div class="bg-blue-50/50 rounded-lg p-3 mt-4 flex gap-3 text-blue-700 text-[11px] items-start">
                        <i class="fas fa-lightbulb mt-0.5"></i>
                        <p class="leading-relaxed">Anda bisa membayar saat kurir mengantar cucian atau sekarang melalui Transfer dan E-Wallet.</p>
                    </div>
                </div>

                <div v-else class="space-y-4">
                    <div class="flex items-center justify-between border-b pb-4">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Informasi Pembayaran</p>
                        <span class="bg-[#22C55E] text-white text-[9px] px-2.5 py-1 rounded font-black uppercase tracking-widest">
                            SUDAH DIBAYAR
                        </span>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-500 mb-1">Metode</p>
                        <p class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ order.paymentMethod }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Nomor Invoice</p>
                        <div class="flex items-center justify-between bg-gray-50 p-3 rounded border border-gray-100 group">
                            <code class="text-xs font-black text-gray-700 tracking-tight">{{ order.invoice }}</code>
                            <div class="flex items-center gap-3">
                                <button @click="downloadQR" class="text-blue-500 hover:text-blue-700 transition-colors" title="Unduh QR Code">
                                    <i class="fas fa-qrcode"></i>
                                </button>
                                <button @click="copyInvoice" :class="[isCopied ? 'text-green-500' : 'text-gray-400 hover:text-[#E30613]', 'transition-colors']" :title="isCopied ? 'Tersalin!' : 'Salin Teks'">
                                    <i :class="isCopied ? 'fas fa-check' : 'far fa-copy'"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Hidden Rendered QR -->
                        <div class="hidden" id="invoiceQR">
                            <QrcodeVue :value="order.invoice" :size="500" level="H" />
                        </div>
                    </div>
                    <button class="w-full bg-gray-900 py-3 rounded-lg font-bold text-white text-xs mt-2 flex justify-center items-center gap-2 hover:bg-black">
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
    </AppLayout>
</template>