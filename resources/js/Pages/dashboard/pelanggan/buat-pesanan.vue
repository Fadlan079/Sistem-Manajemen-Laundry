<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/dashboard.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    auth: Object,
    service: {
        type: Object,
        required: true
    }
});

const PICKUP_FEE  = 5000;
const ANJEMPUT_FEE = 10000;

const form = useForm({
    service_id:     props.service.id,
    kg:             1,
    delivery_type:  'jemput',   // 'jemput' | 'antar' | 'antar_jemput'
    pickup_address: props.auth?.user?.address ?? '',
    payment_method: 'transfer',
});

// Computed totals
const serviceTotal = computed(() => props.service.price * form.kg);
const deliveryFee  = computed(() => form.delivery_type === 'antar_jemput' ? ANJEMPUT_FEE : PICKUP_FEE);
const grandTotal   = computed(() => serviceTotal.value + deliveryFee.value);

function formatRupiah(val) {
    if (!val && val !== 0) return 'Rp0';
    return 'Rp' + new Intl.NumberFormat('id-ID').format(val);
}

const deliveryOptions = [
    {
        value: 'jemput',
        label: 'Jemput Saja',
        icon: 'fas fa-motorcycle',
        desc: 'Kurir jemput cucian dari alamat Anda',
        fee: PICKUP_FEE,
    },
    {
        value: 'antar',
        label: 'Antar Saja',
        icon: 'fas fa-box-open',
        desc: 'Cucian diantar kembali ke alamat Anda',
        fee: PICKUP_FEE,
    },
    {
        value: 'antar_jemput',
        label: 'Antar & Jemput',
        icon: 'fas fa-exchange-alt',
        desc: 'Dijemput dan diantar kembali oleh kurir kami',
        fee: ANJEMPUT_FEE,
    },
];

const paymentMethods = [
    { value: 'transfer', label: 'Transfer Bank',    icon: 'fas fa-university',  desc: 'BCA / BRI / Mandiri / BNI dan bank lainnya' },
    { value: 'e-wallet', label: 'E-Wallet / QRIS',  icon: 'fas fa-qrcode',      desc: 'GoPay, OVO, Dana, ShopeePay, dll' },
];

function submit() {
    form.post(route('pelanggan.pesan.simpan'));
}
</script>

<template>
    <Head title="Buat Pesanan" />

    <DashboardLayout title="Buat Pesanan">
        <div class="max-w-2xl mx-auto pb-16 px-4 sm:px-0 space-y-5">

            <!-- Back -->
            <Link href="/#layanan" class="flex items-center text-[11px] font-black text-gray-400 hover:text-[#E30613] uppercase tracking-widest transition-colors group">
                <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                Kembali ke Layanan
            </Link>

            <!-- Service Header -->
            <section class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="flex items-center gap-4 p-5 bg-gray-50 border-b border-gray-100">
                    <div class="w-12 h-12 rounded bg-[#E30613]/10 text-[#E30613] flex items-center justify-center shrink-0">
                        <i v-if="service.icon" :class="[service.icon, 'text-xl']"></i>
                        <i v-else class="fas fa-tshirt text-xl"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Layanan Dipilih</p>
                        <h1 class="text-base font-black text-gray-900">{{ service.name }}</h1>
                        <p class="text-[11px] text-[#E30613] font-bold">{{ formatRupiah(service.price) }} <span class="text-gray-400 font-normal">{{ service.unit }}</span></p>
                    </div>
                </div>
                <p v-if="service.description" class="px-5 py-3 text-sm text-gray-500">{{ service.description }}</p>
            </section>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- KG Input -->
                <section class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 space-y-4">
                    <h2 class="text-[11px] font-black text-gray-900 uppercase tracking-[0.15em]">Berapa Kilogram Cucian?</h2>

                    <div class="flex items-center gap-4">
                        <button type="button" @click="form.kg > 1 && form.kg--"
                            class="w-11 h-11 rounded border border-gray-200 bg-gray-50 text-gray-700 font-black text-xl flex items-center justify-center hover:bg-[#E30613] hover:text-white hover:border-[#E30613] transition-all active:scale-95">
                            −
                        </button>
                        <input type="number" v-model.number="form.kg" min="1" max="100"
                            class="flex-1 text-center text-3xl font-black text-gray-900 border border-gray-200 rounded py-3 focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all">
                        <button type="button" @click="form.kg < 100 && form.kg++"
                            class="w-11 h-11 rounded border border-gray-200 bg-gray-50 text-gray-700 font-black text-xl flex items-center justify-center hover:bg-[#E30613] hover:text-white hover:border-[#E30613] transition-all active:scale-95">
                            +
                        </button>
                    </div>
                    <p class="text-center text-xs text-gray-500">Minimal <span class="font-bold text-gray-800">1 kg</span> · Maks <span class="font-bold text-gray-800">100 kg</span></p>
                    <div v-if="form.errors.kg" class="text-[11px] text-[#E30613] font-bold">{{ form.errors.kg }}</div>
                </section>

                <!-- Delivery Type -->
                <section class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 space-y-4">
                    <h2 class="text-[11px] font-black text-gray-900 uppercase tracking-[0.15em]">Metode Pengiriman</h2>

                    <div class="grid grid-cols-1 gap-2">
                        <label v-for="opt in deliveryOptions" :key="opt.value"
                            class="flex items-center gap-4 p-4 rounded border-2 cursor-pointer transition-all"
                            :class="form.delivery_type === opt.value ? 'border-[#E30613] bg-[#E30613]/5' : 'border-gray-100 hover:border-gray-200'">
                            <input type="radio" v-model="form.delivery_type" :value="opt.value" class="sr-only">
                            <div class="w-10 h-10 rounded flex items-center justify-center shrink-0"
                                :class="form.delivery_type === opt.value ? 'bg-[#E30613] text-white' : 'bg-gray-100 text-gray-400'">
                                <i :class="[opt.icon, 'text-sm']"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-black text-gray-900">{{ opt.label }}</p>
                                    <span class="text-[10px] font-bold bg-gray-100 text-gray-500 px-2 py-0.5 rounded">+{{ formatRupiah(opt.fee) }}</span>
                                </div>
                                <p class="text-[11px] text-gray-500">{{ opt.desc }}</p>
                            </div>
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0"
                                :class="form.delivery_type === opt.value ? 'border-[#E30613]' : 'border-gray-300'">
                                <div v-if="form.delivery_type === opt.value" class="w-2 h-2 rounded-full bg-[#E30613]"></div>
                            </div>
                        </label>
                    </div>

                    <!-- Address (always required since all options involve courier) -->
                    <div class="space-y-2 pt-1">
                        <label class="text-xs font-black text-gray-700 uppercase tracking-wide">Alamat Pengiriman</label>
                        <textarea v-model="form.pickup_address" rows="3" placeholder="Contoh: Jl. Ahmad Yani No. 12, Samarinda Kota"
                            class="w-full border border-gray-200 rounded px-4 py-3 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all resize-none"></textarea>
                        <div v-if="form.errors.pickup_address" class="text-[11px] text-[#E30613] font-bold">{{ form.errors.pickup_address }}</div>
                    </div>
                </section>

                <!-- Payment Method -->
                <section class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 space-y-4">
                    <h2 class="text-[11px] font-black text-gray-900 uppercase tracking-[0.15em]">Metode Pembayaran</h2>

                    <div class="space-y-2">
                        <label v-for="method in paymentMethods" :key="method.value"
                            class="flex items-center gap-4 p-4 rounded border-2 cursor-pointer transition-all"
                            :class="form.payment_method === method.value ? 'border-[#E30613] bg-[#E30613]/5' : 'border-gray-100 hover:border-gray-200'">
                            <input type="radio" v-model="form.payment_method" :value="method.value" class="sr-only">
                            <div class="w-10 h-10 rounded flex items-center justify-center shrink-0"
                                :class="form.payment_method === method.value ? 'bg-[#E30613] text-white' : 'bg-gray-100 text-gray-400'">
                                <i :class="[method.icon, 'text-sm']"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-black text-gray-900">{{ method.label }}</p>
                                <p class="text-[11px] text-gray-500">{{ method.desc }}</p>
                            </div>
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                                :class="form.payment_method === method.value ? 'border-[#E30613]' : 'border-gray-300'">
                                <div v-if="form.payment_method === method.value" class="w-2 h-2 rounded-full bg-[#E30613]"></div>
                            </div>
                        </label>
                    </div>
                    <div v-if="form.errors.payment_method" class="text-[11px] text-[#E30613] font-bold">{{ form.errors.payment_method }}</div>
                </section>

                <!-- Price Summary -->
                <section class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 space-y-3">
                    <h2 class="text-[11px] font-black text-gray-900 uppercase tracking-[0.15em]">Ringkasan Harga</h2>
                    <div class="space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500 font-semibold">{{ service.name }} ({{ form.kg }} kg × {{ formatRupiah(service.price) }})</span>
                            <span class="font-black text-gray-900">{{ formatRupiah(serviceTotal) }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500 font-semibold">Biaya Pengiriman ({{ deliveryOptions.find(o => o.value === form.delivery_type)?.label }})</span>
                            <span class="font-black text-gray-900">{{ formatRupiah(deliveryFee) }}</span>
                        </div>
                        <div class="pt-3 border-t border-gray-100 flex justify-between items-center">
                            <span class="text-xs font-black text-gray-900 uppercase tracking-widest">Total</span>
                            <span class="text-2xl font-black text-[#E30613]">{{ formatRupiah(grandTotal) }}</span>
                        </div>
                    </div>
                </section>

                <!-- Errors -->
                <div v-if="form.hasErrors" class="bg-red-50 border border-red-200 rounded-lg p-4 text-xs text-red-600 font-bold">
                    Mohon periksa kembali isian formulir Anda.
                </div>

                <!-- Submit -->
                <button type="submit"
                    :disabled="form.processing || form.kg < 1"
                    class="w-full py-4 rounded font-black text-white text-sm uppercase tracking-[0.2em] shadow-lg transition-all active:scale-[0.98] flex items-center justify-center gap-3"
                    :class="form.processing || form.kg < 1 ? 'bg-gray-300 cursor-not-allowed' : 'bg-[#E30613] shadow-red-100 hover:bg-black'">
                    <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                    <i v-else class="fas fa-check-circle"></i>
                    {{ form.processing ? 'Memproses...' : 'Konfirmasi & Buat Pesanan' }}
                </button>

            </form>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.25s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
