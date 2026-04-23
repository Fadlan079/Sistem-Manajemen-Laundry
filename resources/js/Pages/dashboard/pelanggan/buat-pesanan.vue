<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';
import { onMounted, computed, watch } from 'vue';

const props = defineProps({
    auth: Object,
    services: {
        type: Array,
        required: true
    },
    service: {
        type: Object,
        required: true
    },
    prefill: {
        type: Object,
        default: null
    }
});

const form = useForm({
    service_id:         props.service.id,
    delivery_type:      'jemput',
    pickup_address:     props.auth?.user?.address ?? '',
    pickup_date:        '',
    pickup_time:        '',
    laundry_notes:      '',
    courier_notes:      '',
    payment_preference: 'cash',
    estimated_weight:   '',
    item_qty:           1,
    extra_services:     [],
});

// Apply prefill if coming from reorder
onMounted(() => {
    if (props.prefill) {
        form.service_id     = props.prefill.service_id;
        form.delivery_type  = props.prefill.delivery_type;
        form.pickup_address = props.prefill.pickup_address ?? '';
    }
});

const deliveryOptions = [
    { value: 'antar_jemput', label: 'Antar Jemput', fee: 10000, icon: 'fas fa-truck', description: 'Kurir menjemput cucian kotor & mengantar cucian bersih.' },
    { value: 'jemput',       label: 'Jemput Saja',  fee: 5000, icon: 'fas fa-motorcycle', description: 'Kurir menjemput cucian kotor, Anda ambil sendiri ke outlet.' },
    { value: 'antar',        label: 'Antar Saja',   fee: 5000, icon: 'fas fa-box-open', description: 'Anda antar ke outlet, kurir mengantar cucian bersih.' },
];

const weightOptions = [
    { value: '<3', label: '< 3 kg (Sedikit)', helper: '≈ 5–8 potong pakaian' },
    { value: '3-5', label: '3 – 5 kg (Sedang)', helper: '≈ 1 keranjang kecil' },
    { value: '5-10', label: '5 – 10 kg (Banyak)', helper: '≈ 1 kantong besar' },
    { value: '>10', label: '> 10 kg (Besar)', helper: 'Gorden, sprei, atau jumlah masif' },
];

const paymentOptions = [
    { value: 'cash', label: 'Bayar Tunai', icon: 'fas fa-money-bill-wave' },
    { value: 'transfer', label: 'Transfer Bank/QRIS', icon: 'fas fa-qrcode' },
];

// Date Generation
const pickupDates = computed(() => {
    const dates = [];
    const today = new Date();
    // Gunakan 'id-ID' untuk format indonesia
    const formatter = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' });

    for (let i = 0; i < 4; i++) {
        const d = new Date(today);
        d.setDate(today.getDate() + i);

        let label = '';
        if (i === 0) label = `Hari Ini (${formatter.format(d)})`;
        else if (i === 1) label = `Besok (${formatter.format(d)})`;
        else label = formatter.format(d);

        // ISO format YYYY-MM-DD in local time
        const offset = d.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(d - offset)).toISOString().split('T')[0];

        dates.push({ label, value: localISOTime, dateObj: d });
    }
    return dates;
});

// Time Slots
const allTimeSlots = [
    { value: '08:00', label: '08:00 – 10:00', startHour: 8 },
    { value: '10:00', label: '10:00 – 12:00', startHour: 10 },
    { value: '13:00', label: '13:00 – 15:00', startHour: 13 },
    { value: '15:00', label: '15:00 – 17:00', startHour: 15 },
];

const availableTimeSlots = computed(() => {
    const today = new Date();
    const offset = today.getTimezoneOffset() * 60000;
    const todayStr = (new Date(today - offset)).toISOString().split('T')[0];
    const currentHour = today.getHours();

    return allTimeSlots.map(slot => {
        let isDisabled = false;

        // Disable past slots if today (buffer 1 hour before slot starts)
        if (form.pickup_date === todayStr) {
            if (currentHour >= slot.startHour - 1) {
                isDisabled = true;
            }
        }

        return {
            ...slot,
            disabled: isDisabled
        };
    });
});

watch(() => form.pickup_date, () => {
    const selectedSlot = availableTimeSlots.value.find(s => s.value === form.pickup_time);
    if (!selectedSlot || selectedSlot.disabled) {
        form.pickup_time = '';
    }
});

const currentService = computed(() => {
    return props.services.find(s => s.id === form.service_id) || props.services[0];
});

const isKgService = computed(() => {
    return ['/kg', 'kg'].includes(currentService.value?.unit?.toLowerCase() || '');
});

const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const extraOptions = computed(() => {
    const expressPrice = (currentService.value.price || 0) * 0.5;
    const isExpressDisabled = (currentService.value.name?.toLowerCase() || '').includes('bedcover');

    return [
        {
            value: 'express',
            label: 'Express (24 Jam)',
            desc: 'Diprioritaskan dalam pengerjaan',
            tooltip: 'Express = diprioritaskan dalam pengerjaan. Tidak berlaku untuk item berat (seperti Bedcover).',
            priceText: `+ ${formatRupiah(expressPrice)}${isKgService.value ? '/kg' : '/item'}`,
            priceValue: expressPrice,
            disabled: isExpressDisabled
        },
        {
            value: 'treatment',
            label: 'Treatment Khusus',
            desc: 'Noda berat / Luntur / Dll',
            tooltip: 'Harap pisahkan pakaian yang mudah luntur atau perlu perlakuan khusus, karena pihak laundry tidak bertanggung jawab atas kerusakan pakaian.',
            priceText: isKgService.value ? '+ Rp 2.000/kg' : '+ Rp 5.000/item',
            priceValue: isKgService.value ? 2000 : 5000,
            disabled: false
        },
        {
            value: 'own_perfume',
            label: 'Pewangi Sendiri',
            desc: 'Bawa pewangi Anda sendiri',
            tooltip: 'Silakan siapkan dan serahkan pewangi khusus milik Anda kepada kurir kami.',
            priceText: 'Gratis',
            priceValue: 0,
            disabled: false
        }
    ];
});

const totalExtraCostPerUnit = computed(() => {
    return extraOptions.value
        .filter(opt => form.extra_services.includes(opt.value))
        .reduce((sum, opt) => sum + opt.priceValue, 0);
});

const combinedServicePrice = computed(() => {
    return (currentService.value.price || 0) + totalExtraCostPerUnit.value;
});

const estimatedKG = computed(() => {
    if (!form.estimated_weight) return { min: 0, max: 0 };
    const map = { '<3': 3, '3-5': 5, '5-10': 10, '>10': 15 };
    const minMap = { '<3': 1, '3-5': 3, '5-10': 5, '>10': 10 };
    return {
        min: minMap[form.estimated_weight] || 0,
        max: map[form.estimated_weight] || 0
    };
});

const estimatedDeliveryFee = computed(() => {
    if (form.delivery_type === 'antar_jemput') return 10000;
    if (form.delivery_type === 'jemput' || form.delivery_type === 'antar') return 5000;
    return 0;
});

const calculatedServiceCostText = computed(() => {
    if (!isKgService.value) {
        return formatRupiah(combinedServicePrice.value * (form.item_qty || 1));
    }
    const minCost = combinedServicePrice.value * estimatedKG.value.min;
    const maxCost = combinedServicePrice.value * estimatedKG.value.max;
    return `${formatRupiah(minCost)} - ${formatRupiah(maxCost)}`;
});

const totalEstimatedCostText = computed(() => {
    if (!isKgService.value) {
        return formatRupiah((combinedServicePrice.value * (form.item_qty || 1)) + estimatedDeliveryFee.value);
    }
    const minTotal = (combinedServicePrice.value * estimatedKG.value.min) + estimatedDeliveryFee.value;
    const maxTotal = (combinedServicePrice.value * estimatedKG.value.max) + estimatedDeliveryFee.value;
    return `${formatRupiah(minTotal)} - ${formatRupiah(maxTotal)}`;
});

const isSubmitDisabled = computed(() => {
    if (form.processing) return true;
    if (!form.pickup_date || !form.pickup_time || !form.pickup_address) return true;
    if (isKgService.value && !form.estimated_weight) return true;
    if (!isKgService.value && (!form.item_qty || form.item_qty < 1)) return true;
    return false;
});

function submit() {
    form.post(route('pelanggan.pesan.simpan'));
}
</script>

<template>
    <Head title="Buat Pesanan" />

    <AppLayout>
        <div class="pt-20 lg:pt-28 max-w-2xl mx-auto pb-32 lg:pb-16 px-4 sm:px-0 space-y-5">

            <!-- Back -->
            <Link href="/#layanan" class="flex items-center text-[11px] font-black text-gray-400 hover:text-[#E30613] uppercase tracking-widest transition-colors group">
                <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                Kembali ke Layanan
            </Link>

            <!-- Services Header & Options -->
<section class="space-y-4 pt-2">
    <h2 class="text-sm font-semibold text-gray-900 border-b pb-2">
        Pilih Layanan
    </h2>

    <!-- Horizontal Scroll Container -->
    <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
        <button
            v-for="srv in services"
            :key="srv.id"
            type="button"
            @click="form.service_id = srv.id"
            class="min-w-[140px] flex-shrink-0 border rounded-lg p-3 text-left transition-all"
            :class="form.service_id === srv.id
                ? 'border-[#E30613] bg-red-50'
                : 'border-gray-200 bg-white hover:border-gray-300'"
        >

            <!-- Top: Title -->
            <div class="flex items-start justify-between">
                <h3 class="text-sm font-semibold leading-tight"
                    :class="form.service_id === srv.id ? 'text-[#E30613]' : 'text-gray-800'">
                    {{ srv.name }}
                </h3>

                <i v-if="form.service_id === srv.id"
                    class="fas fa-check text-[11px] text-[#E30613]"></i>
            </div>

            <!-- Optional Description -->
            <p class="text-[11px] text-gray-500 mt-1 line-clamp-2">
                {{ srv.description || 'Layanan laundry profesional' }}
            </p>

            <!-- Price -->
            <div class="mt-3 text-sm font-semibold text-gray-900">
                {{ formatRupiah(srv.price) }}
                <span class="text-[11px] text-gray-400 font-normal">
                    /{{ srv.unit }}
                </span>
            </div>
        </button>
    </div>

    <!-- Notes -->
    <div class="pt-2">
        <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">
            Catatan Layanan (Opsional)
        </label>

        <textarea
            v-model="form.laundry_notes"
            rows="2"
            placeholder="Contoh: Pisahkan baju putih, fokus noda kerah..."
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all text-gray-800"
        ></textarea>
    </div>
</section>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Delivery Type -->
                <section class="space-y-3">
                    <h2 class="text-sm font-bold text-gray-900 border-b pb-2 mb-4">Metode Pengiriman</h2>

                    <div class="flex flex-col gap-3">
                        <label v-for="opt in deliveryOptions" :key="opt.value"
                            class="flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all"
                            :class="form.delivery_type === opt.value ? 'border-[#E30613] bg-red-50 shadow-sm' : 'border-gray-200 hover:border-gray-300 bg-white text-gray-600'">
                            <input type="radio" v-model="form.delivery_type" :value="opt.value" class="sr-only">

                            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 transition-colors"
                                :class="form.delivery_type === opt.value ? 'bg-[#E30613] text-white' : 'bg-gray-100 text-gray-400'">
                                <i :class="[opt.icon, 'text-lg']"></i>
                            </div>

                            <div class="flex-1">
                                <h3 class="text-[13px] font-bold leading-tight" :class="form.delivery_type === opt.value ? 'text-[#E30613]' : 'text-gray-700'">{{ opt.label }}</h3>
                                <p class="text-[10px] mt-0.5 leading-snug" :class="form.delivery_type === opt.value ? 'text-red-500/80' : 'text-gray-400'">{{ opt.description }}</p>
                                <p class="text-[11px] mt-1" :class="form.delivery_type === opt.value ? 'text-red-600 font-bold' : 'text-gray-500'">Ongkir: {{ formatRupiah(opt.fee) }}</p>
                            </div>

                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0"
                                :class="form.delivery_type === opt.value ? 'border-[#E30613]' : 'border-gray-300'">
                                <div v-if="form.delivery_type === opt.value" class="w-2.5 h-2.5 bg-[#E30613] rounded-full"></div>
                            </div>
                        </label>
                    </div>
                </section>

                <!-- Time and Address -->
                <section class="space-y-4 bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-gray-900 border-b pb-2">Detail Penjemputan</h2>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-gray-500 uppercase tracking-wide">Pilih Tanggal <span class="text-[#E30613]">*</span></label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <label v-for="dt in pickupDates" :key="dt.value"
                                    class="flex items-center justify-center py-2 px-3 rounded-lg border text-xs font-bold cursor-pointer transition-all text-center"
                                    :class="form.pickup_date === dt.value ? 'bg-[#E30613] text-white border-[#E30613] shadow-sm' : 'bg-gray-50 border-gray-200 text-gray-600 hover:border-gray-300'">
                                    <input type="radio" v-model="form.pickup_date" :value="dt.value" class="sr-only">
                                    {{ dt.label }}
                                </label>
                            </div>
                        </div>

                        <div v-if="form.pickup_date" class="space-y-2">
                            <label class="text-[11px] font-black text-gray-500 uppercase tracking-wide">Pilih Jam (Time Slot) <span class="text-[#E30613]">*</span></label>
                            <div class="grid grid-cols-2 gap-2">
                                <label v-for="slot in availableTimeSlots" :key="slot.value"
                                    class="flex items-center justify-center py-2 px-3 rounded-lg border text-xs font-bold transition-all text-center"
                                    :class="[
                                        slot.disabled ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed opacity-60' : 'cursor-pointer',
                                        !slot.disabled && form.pickup_time === slot.value ? 'bg-[#E30613] text-white border-[#E30613] shadow-sm' : '',
                                        !slot.disabled && form.pickup_time !== slot.value ? 'bg-gray-50 border-gray-200 text-gray-600 hover:border-gray-300' : ''
                                    ]">
                                    <input type="radio" v-model="form.pickup_time" :value="slot.value" :disabled="slot.disabled" class="sr-only">
                                    {{ slot.label }}
                                </label>
                            </div>
                            <p v-if="availableTimeSlots.every(s => s.disabled)" class="text-[10px] text-[#E30613] font-medium mt-1">
                                Semua jadwal pada tanggal ini sudah tidak tersedia atau telah lewat.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-black text-gray-500 uppercase tracking-wide">Alamat Penjemputan <span class="text-[#E30613]">*</span></label>
                        <div class="relative">
                            <i class="fas fa-map-marker-alt absolute top-3 left-3 text-gray-400"></i>
                            <textarea v-model="form.pickup_address" rows="2" placeholder="Detail alamat..." required
                                class="w-full border border-gray-200 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all resize-none text-gray-800"></textarea>
                        </div>
                        <div v-if="form.errors.pickup_address" class="text-[11px] text-[#E30613] font-bold">{{ form.errors.pickup_address }}</div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-black text-gray-500 uppercase tracking-wide">Catatan Kurir (Opsional / Patokan Rumah)</label>
                        <div class="relative">
                            <i class="fas fa-motorcycle absolute top-3 left-3 text-gray-400"></i>
                            <textarea v-model="form.courier_notes" rows="2" placeholder="Cth: Rumah pagar hijau muda no 2 dari depan..."
                                class="w-full border border-gray-200 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all resize-none text-gray-800"></textarea>
                        </div>
                    </div>
                </section>

                <!-- Layanan Ekstra -->
                <section class="space-y-4">
                    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm space-y-3">
                        <div class="flex items-center justify-between border-b pb-2">
                            <h2 class="text-sm font-bold text-gray-900">Layanan Ekstra (Opsional)</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2">
                            <label v-for="opt in extraOptions" :key="opt.value"
                                class="relative flex flex-col items-start gap-2 p-3 rounded-lg border-2 cursor-pointer transition-all"
                                :class="[
                                    opt.disabled ? 'opacity-50 cursor-not-allowed bg-gray-50 border-gray-100' :
                                    form.extra_services.includes(opt.value) ? 'border-[#E30613] bg-red-50/20 shadow-sm' : 'border-gray-200 hover:border-gray-300 bg-white'
                                ]">
                                <input type="checkbox" v-model="form.extra_services" :value="opt.value" :disabled="opt.disabled" class="sr-only">

                                <div class="flex justify-between w-full items-start">
                                    <h4 class="text-[13px] font-bold text-gray-800" :class="form.extra_services.includes(opt.value) ? 'text-[#E30613]' : ''">{{ opt.label }}</h4>
                                    <!-- Custom checkbox -->
                                    <div class="w-4 h-4 rounded border flex items-center justify-center shrink-0 mt-0.5"
                                        :class="form.extra_services.includes(opt.value) ? 'bg-[#E30613] border-[#E30613] text-white' : 'border-gray-300 bg-white'">
                                        <i v-if="form.extra_services.includes(opt.value)" class="fas fa-check text-[10px]"></i>
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-500 leading-tight pr-4">{{ opt.desc }}</p>

                                <div class="mt-auto pt-2 flex w-full justify-between items-center">
                                    <span class="text-[11px] font-black" :class="opt.priceValue > 0 ? 'text-[#E30613]' : 'text-[#22C55E]'">{{ opt.priceText }}</span>

                                    <!-- Tooltip / Info Icon -->
                                    <div class="group relative inline-block text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-question-circle"></i>
                                        <div class="absolute bottom-full right-0 lg:left-1/2 lg:-translate-x-1/2 mb-2 hidden group-hover:block w-56 p-2.5 bg-gray-900 border border-gray-700 text-white text-[10px] text-center rounded-lg shadow-xl z-20 before:content-[''] before:absolute before:top-full before:right-2 lg:before:left-1/2 lg:before:-translate-x-1/2 before:-mt-[1px] before:border-4 before:border-transparent before:border-t-gray-900 leading-relaxed font-medium">
                                            {{ opt.tooltip }}
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </section>

                <!-- Preferences & Estimates -->
                <section class="space-y-4">
                    <!-- Dynamic Weight or Qty -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm space-y-3">
                        <template v-if="isKgService">
                            <h2 class="text-sm font-bold text-gray-900 border-b pb-2">Estimasi Berat <span class="text-[#E30613]">*</span></h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <label v-for="wt in weightOptions" :key="wt.value"
                                    class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
                                    :class="form.estimated_weight === wt.value ? 'border-[#E30613] bg-red-50/30' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" v-model="form.estimated_weight" :value="wt.value" required class="mt-1 text-[#E30613] focus:ring-[#E30613]">
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-800">{{ wt.label }}</h4>
                                        <p class="text-[11px] text-gray-500 mt-0.5">{{ wt.helper }}</p>
                                    </div>
                                </label>
                            </div>
                        </template>
                        <template v-else>
                            <h2 class="text-sm font-bold text-gray-900 border-b pb-2">Jumlah Item ({{ currentService.unit.replace('/', '') }}) <span class="text-[#E30613]">*</span></h2>
                            <div class="flex items-center gap-4 pt-2">
                                <button type="button" @click="form.item_qty = Math.max(1, form.item_qty - 1)" class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center font-bold text-gray-700 hover:bg-gray-200 transition-colors">-</button>
                                <input type="number" v-model="form.item_qty" min="1" class="w-20 py-2 text-center border-gray-200 rounded-lg font-bold text-gray-800 focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613]" />
                                <button type="button" @click="form.item_qty++" class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center font-bold text-gray-700 hover:bg-gray-200 transition-colors">+</button>
                            </div>
                            <p class="text-[11px] text-gray-500 mt-3 leading-relaxed">
                                Harga layanan adalah <strong class="text-gray-800">{{ formatRupiah(currentService.price) }}</strong> per item. Biaya layanan akan dihitungkan secara pasti.
                            </p>
                        </template>
                    </div>

                    <!-- Payment Preference -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm space-y-3">
                        <h2 class="text-sm font-bold text-gray-900 border-b pb-2">Preferensi Pembayaran <span class="text-[#E30613]">*</span></h2>
                        <div class="grid grid-cols-2 gap-3">
                            <label v-for="pay in paymentOptions" :key="pay.value"
                                class="flex flex-col items-center gap-2 p-3 rounded-lg border-2 cursor-pointer transition-all text-center"
                                :class="form.payment_preference === pay.value ? 'border-[#E30613] bg-[#E30613] text-white shadow-sm' : 'border-gray-200 hover:border-gray-300 text-gray-700 bg-white'">
                                <input type="radio" v-model="form.payment_preference" :value="pay.value" class="sr-only">
                                <i :class="[pay.icon, 'text-xl']"></i>
                                <span class="text-xs font-bold">{{ pay.label }}</span>
                            </label>
                        </div>
                    </div>
                </section>

                <!-- Warning Reminder -->
                <div class="bg-amber-50 border-l-4 border-amber-500 rounded-r-lg p-4 flex gap-3 text-amber-900 shadow-sm">
                    <i class="fas fa-exclamation-triangle mt-0.5"></i>
                    <div>
                        <h4 class="text-xs font-bold font-mono uppercase tracking-wide">Penting</h4>
                        <p class="text-[11px] mt-1 pr-2 opacity-90 leading-relaxed">
                            Berat final beserta total biaya sebenarnya (termasuk ongkir jika ada) baru akan ditentukan <span class="font-bold">setelah proses penimbangan secara aktual</span> oleh kurir atau saat tiba di outlet kami. Pembayaran dilakukan setelah finalisasi biaya.
                        </p>
                    </div>
                </div>

                <!-- Order Summary -->
                <section v-if="form.service_id && form.delivery_type" class="bg-gray-50 border border-gray-200 p-4 rounded-xl shadow-inner space-y-3">
                    <h2 class="text-xs font-black uppercase text-gray-500 tracking-wider">Ringkasan Order</h2>
                    <ul class="text-xs space-y-2 text-gray-700 font-medium">
                        <li class="flex justify-between border-b border-gray-200 pb-2">
                            <span class="text-gray-500">Layanan</span>
                            <span class="font-bold text-right">{{ services.find(s => s.id === form.service_id)?.name }}</span>
                        </li>
                        <li class="flex justify-between border-b border-gray-200 pb-2">
                            <span class="text-gray-500">Metode</span>
                            <span class="font-bold text-right">{{ deliveryOptions.find(d => d.value === form.delivery_type)?.label }}</span>
                        </li>
                        <li v-if="form.pickup_date || form.pickup_time" class="flex justify-between border-b border-gray-200 pb-2">
                            <span class="text-gray-500">Penjemputan</span>
                            <span class="font-bold text-right">
                                <span v-if="form.pickup_date">{{ pickupDates.find(d => d.value === form.pickup_date)?.label }}</span>
                                <span v-if="form.pickup_time"> @ {{ availableTimeSlots.find(s => s.value === form.pickup_time)?.label }}</span>
                            </span>
                        </li>
                        <li v-if="isKgService && form.estimated_weight" class="flex justify-between border-b border-gray-200 pb-2">
                            <span class="text-gray-500">Estimasi Berat</span>
                            <span class="font-bold text-right">{{ weightOptions.find(w => w.value === form.estimated_weight)?.label }}</span>
                        </li>
                        <li v-if="!isKgService && form.item_qty" class="flex justify-between border-b border-gray-200 pb-2">
                            <span class="text-gray-500">Jumlah Item</span>
                            <span class="font-bold text-right">{{ form.item_qty }} {{ currentService.unit.replace('/', '') }}</span>
                        </li>
                        <li v-if="form.extra_services.length" class="flex justify-between border-b border-gray-200 pb-2">
                            <span class="text-gray-500">Ekstra</span>
                            <span class="font-bold text-right text-[#E30613]">{{ form.extra_services.length }} Layanan Tambahan</span>
                        </li>
                        <li v-if="form.payment_preference" class="flex justify-between border-b border-gray-200 pb-2">
                            <span class="text-gray-500">Pembayaran</span>
                            <span class="font-bold text-right uppercase">{{ form.payment_preference }}</span>
                        </li>
                    </ul>

                    <!-- Cost Estimations -->
                    <div v-if="(isKgService && form.estimated_weight) || (!isKgService && form.item_qty)" class="mt-4 pt-3 border-t-2 border-dashed border-gray-300 space-y-2">
                        <h3 class="text-[11px] font-black uppercase text-gray-500 tracking-wider mb-2">Rincian Biaya</h3>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">
                                Total Layanan Das + Ekstra
                                <span v-if="isKgService">(~{{ estimatedKG.min }}-{{ estimatedKG.max }} kg)</span>
                                <span v-else>({{ form.item_qty }} item)</span>
                            </span>
                            <span class="font-bold text-gray-800">{{ calculatedServiceCostText }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Ongkos Kirim (Estimasi KM)</span>
                            <span class="font-bold text-gray-800">{{ formatRupiah(estimatedDeliveryFee) }}</span>
                        </div>
                        <div class="flex justify-between text-sm pt-2 mt-2 border-t border-gray-200">
                            <span class="font-black text-gray-800">Total Biaya <span v-if="isKgService" class="text-gray-400 font-medium text-[10px] uppercase">(Estimasi)</span></span>
                            <span class="font-black text-[#E30613]">{{ totalEstimatedCostText }}</span>
                        </div>
                    </div>

                    <!-- Hard Warning Note for Estimations -->
                    <div v-if="isKgService && form.estimated_weight" class="mt-4 bg-red-50 text-[#E30613] p-3 rounded-lg border border-red-100 flex gap-2 items-start shadow-sm">
                        <i class="fas fa-exclamation-circle mt-0.5 text-[14px]"></i>
                        <p class="text-[10px] leading-relaxed font-bold uppercase tracking-tight">
                            PERHATIAN: Nominal di atas HANYALAH ESTIMASI RENTANG. Total biaya FIX baru akan muncul SETELAH kurir atau outlet menimbang ulang berat sebenarnya!
                        </p>
                    </div>

                    <!-- Note for Exact items -->
                    <div v-if="!isKgService" class="mt-4 bg-blue-50 text-blue-800 p-3 rounded-lg border border-blue-100 flex gap-2 items-start shadow-sm">
                        <i class="fas fa-info-circle mt-0.5 text-[14px]"></i>
                        <p class="text-[10px] leading-relaxed font-bold uppercase tracking-tight">
                            Harga layanan sudah PASTI berdasarkan jumlah item. Namun ongkos kirim akan dikonfirmasi manual oleh kurir kami.
                        </p>
                    </div>
                </section>

                <!-- Errors -->
                <div v-if="form.hasErrors" class="bg-red-50 border border-red-200 rounded-lg p-4 text-xs text-red-600 font-bold">
                    Mohon periksa kembali isian formulir Anda.
                </div>

                <!-- Submit Button Desktop -->
                <button type="submit"
                    :disabled="isSubmitDisabled"
                    class="hidden lg:flex w-full py-4 mt-6 rounded-xl font-bold text-white text-sm shadow-lg transition-all active:scale-95 items-center justify-center gap-2"
                    :class="isSubmitDisabled ? 'bg-gray-300 cursor-not-allowed' : 'bg-[#E30613] hover:bg-black hover:shadow-xl'">
                    <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                    <span v-if="!form.processing">Simpan & Lanjutkan Pesanan</span>
                </button>
            </form>
        </div>

        <!-- Mobile Fixed Action Bar (Gojek / Grab Style) -->
        <div class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 px-10 pt-3 pb-[calc(1rem+env(safe-area-inset-bottom))] shadow-[0_-4px_15px_rgba(0,0,0,0.05)] lg:hidden flex justify-between items-center rounded-t-2xl">
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5" v-if="isKgService && !form.estimated_weight">Belum Dihitung</p>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5" v-else-if="isKgService && form.estimated_weight">Total Estimasi</p>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5" v-else>Total Harga</p>

                <p class="text-[17px] font-black text-gray-900 leading-none">
                    <span v-if="isKgService && !form.estimated_weight">---</span>
                    <span v-else>{{ totalEstimatedCostText }}</span>
                </p>
            </div>

            <button type="button" @click="submit"
                :disabled="isSubmitDisabled"
                class="px-8 py-3.5 rounded-full font-bold text-white text-sm shadow-md transition-all active:scale-95 flex items-center justify-center gap-2"
                :class="isSubmitDisabled ? 'bg-gray-300 cursor-not-allowed text-gray-500 shadow-none' : 'bg-primary hover:bg-primary/80 shadow-[0_4px_14px_rgba(22,163,74,0.3)]'">
                <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                <span v-if="!form.processing">Pesan</span>
            </button>
        </div>
    </AppLayout>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.25s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
