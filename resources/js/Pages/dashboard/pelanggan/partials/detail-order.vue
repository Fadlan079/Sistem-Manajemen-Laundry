<script setup>
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/dashboard.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    auth: Object,
    order: {
        type: Object,
        required: true
    }
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const steps = ['Diterima', 'Dicuci', 'Dikeringkan', 'Selesai', 'Diantar'];

const isExpanded = ref(false);

// Form untuk konfirmasi pembayaran mandiri
const payForm = useForm({});
function konfirmasiBayar() {
    payForm.post(route('pelanggan.aktivitas.bayar', props.order.dbId));
}

function getStepIndex(status) {
    return steps.indexOf(status);
}

function formatRupiah(val) {
    if (!val) return 'Rp0';
    return 'Rp' + new Intl.NumberFormat('id-ID').format(val);
}
</script>

<template>
    <Head title="Detail Pesanan" />

    <DashboardLayout title="Detail Pesanan">
        <div class="max-w-2xl mx-auto pb-12 space-y-4 px-4 sm:px-0">
            
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
                         :style="`width: ${(getStepIndex(order.status) / (steps.length - 1)) * 100}%` "></div>

                    <div v-for="(step, index) in steps" :key="index" class="relative z-10 flex flex-col items-center">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center transition-colors shadow-sm border"
                             :class="getStepIndex(order.status) >= index ? 'bg-[#22C55E] border-[#22C55E] text-white' : 'bg-white border-gray-200 text-gray-300'">
                            <i v-if="getStepIndex(order.status) >= index" class="fas fa-check text-[10px]"></i>
                            <span v-else class="text-[10px] font-bold">{{ index + 1 }}</span>
                        </div>
                        <span class="absolute top-8 text-[9px] font-black uppercase tracking-tighter whitespace-nowrap"
                              :class="getStepIndex(order.status) >= index ? 'text-[#22C55E]' : 'text-gray-400'">
                            {{ step }}
                        </span>
                    </div>
                </div>

                <div v-if="order.paymentStatus === 'UNPAID'" class="mt-14 bg-red-50 text-[#E30613] py-3 px-4 rounded border border-red-100 text-center text-[11px] font-bold uppercase tracking-wider">
                    <i class="far fa-clock mr-2"></i> Sisa waktu pembayaran: <span class="font-black">{{ order.timeLeft }}</span>
                </div>
            </section>

            <section class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center gap-4 bg-gray-50/30">
                    <div class="w-12 h-12 bg-white text-[#E30613] rounded border border-gray-100 shadow-sm flex items-center justify-center">
                        <i class="fas fa-receipt text-xl"></i>
                    </div>
                    <div>
                        <h2 class="font-black text-gray-900 text-sm uppercase tracking-tight">Informasi Pesanan</h2>
                        <p class="text-[11px] font-bold text-[#E30613] uppercase tracking-widest">{{ order.service }}</p>
                    </div>
                </div>
                <div class="p-5 space-y-4">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500 font-semibold uppercase tracking-wider">Nomor Antrean</span>
                        <span class="font-black text-gray-900 bg-gray-100 px-2 py-1 rounded">{{ order.id }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500 font-semibold uppercase tracking-wider">Berat / Jumlah</span>
                        <span class="font-black text-gray-900">{{ order.items }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-gray-500 font-semibold uppercase tracking-wider">Waktu Masuk</span>
                        <span class="font-black text-gray-900 uppercase">{{ order.date }}</span>
                    </div>
                    <div v-if="order.pickup_address" class="flex justify-between items-center text-xs">
                        <span class="text-gray-500 font-semibold uppercase tracking-wider">Alamat Jemput</span>
                        <span class="font-black text-gray-900 text-right max-w-[60%]">{{ order.pickup_address }}</span>
                    </div>
                </div>
            </section>

            <section class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 space-y-4">
                <h2 @click="isExpanded = !isExpanded" class="text-[11px] font-black text-gray-900 uppercase tracking-[0.15em] flex items-center justify-between cursor-pointer select-none">
                    Rincian Pembayaran
                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300 text-[10px]" :class="{'rotate-180': isExpanded}"></i>
                </h2>
                <div v-show="isExpanded" class="space-y-3 pt-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600 font-bold uppercase">Harga Layanan</span>
                        <span class="text-gray-900 font-black">{{ formatRupiah(order.price) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600 font-bold uppercase">Biaya Penanganan</span>
                        <span class="text-gray-900 font-black">{{ formatRupiah(order.fee) }}</span>
                    </div>
                    <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                        <span class="text-xs font-black text-gray-900 uppercase tracking-widest">Total Bayar</span>
                        <span class="text-xl font-black text-[#E30613]">{{ formatRupiah(order.total) }}</span>
                    </div>
                </div>
            </section>

            <section class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Metode Pembayaran</p>
                        <p class="text-xs font-black text-gray-800 uppercase">{{ order.paymentMethod }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Status</p>
                        <span :class="order.paymentStatus === 'PAID' ? 'bg-[#22C55E] text-white' : 'bg-[#E30613] text-white'" 
                              class="text-[9px] px-2.5 py-1 rounded font-black uppercase tracking-widest">
                            {{ order.paymentStatus }}
                        </span>
                    </div>
                </div>

                <div>
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Nomor Invoice</p>
                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded border border-gray-100 group">
                        <code class="text-xs font-black text-gray-700 tracking-tight">{{ order.invoice }}</code>
                        <button class="text-gray-400 hover:text-[#E30613] transition-colors">
                            <i class="far fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div v-if="order.paymentStatus === 'UNPAID'" class="flex flex-col items-center pt-4 space-y-4">
                    <!-- Info metode pembayaran -->
                    <div v-if="order.paymentMethod === 'Transfer Bank'" class="w-full bg-blue-50 border border-blue-100 rounded-lg p-4 space-y-2">
                        <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest">Instruksi Transfer</p>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Bank BCA</span><span class="font-black">1234567890</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Bank BRI</span><span class="font-black">0987654321</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">a/n</span><span class="font-black">HiWash Laundry</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="order.paymentMethod === 'E-Wallet / QRIS'" class="flex flex-col items-center gap-3">
                        <div class="p-3 bg-white border border-gray-200 rounded-lg shadow-inner">
                            <div class="w-44 h-44 bg-gray-50 flex flex-col items-center justify-center border border-dashed border-gray-300 rounded">
                                <i class="fas fa-qrcode text-6xl text-gray-200 mb-2"></i>
                                <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest text-center px-4 leading-tight">QR Code QRIS</div>
                            </div>
                        </div>
                        <p class="text-center text-[10px] text-gray-500 font-bold uppercase tracking-tight">Scan kode QR melalui GoPay / OVO / Dana / ShopeePay</p>
                    </div>

                    <!-- Tombol konfirmasi mandiri -->
                    <button
                        @click="konfirmasiBayar"
                        :disabled="payForm.processing"
                        class="w-full py-4 rounded font-black text-xs uppercase tracking-[0.2em] shadow-lg transition-all active:scale-[0.98] flex items-center justify-center gap-3"
                        :class="payForm.processing ? 'bg-gray-300 cursor-not-allowed text-gray-500' : 'bg-[#22C55E] shadow-green-100 hover:bg-green-700 text-white'">
                        <i v-if="payForm.processing" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-check-circle"></i>
                        {{ payForm.processing ? 'Memproses...' : 'Saya Sudah Bayar' }}
                    </button>
                    <p class="text-[10px] text-gray-400 text-center">Dengan menekan tombol ini, Anda menyatakan telah menyelesaikan pembayaran</p>
                </div>

                <div v-else class="pt-2">
                    <button class="w-full bg-gray-900 text-white py-4 rounded font-black text-xs uppercase tracking-[0.2em] hover:bg-black transition-all flex items-center justify-center gap-3">
                        <i class="fas fa-print"></i>
                        Cetak Nota Transaksi
                    </button>
                </div>
            </section>

        </div>
    </DashboardLayout>
</template>