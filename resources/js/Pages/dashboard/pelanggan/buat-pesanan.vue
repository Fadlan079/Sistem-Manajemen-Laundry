<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';
import { onMounted, computed, watch, ref } from 'vue';

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
    },
    addresses: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    service_id:         props.service.id,
    delivery_type:      'jemput',
    pickup_address:     props.addresses.find(a => a.is_default)?.address ?? props.auth?.user?.address ?? '',
    pickup_date:        '',
    pickup_time:        '',
    laundry_notes:      '',
    courier_notes:      '',
    payment_preference: 'cash',
    estimated_weight:   '',
    item_qty:           1,
    extra_services:     [],
    use_discount:       props.service.is_discount_today ?? false,
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
    const basePrice = form.use_discount ? (currentService.value.discounted_price || currentService.value.price) : (currentService.value.price || 0);
    return basePrice + totalExtraCostPerUnit.value;
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

// Step Logic
const currentStep = ref(1);
const totalSteps = 5;
const isCancelModalOpen = ref(false);

const cancelForm = useForm({
    reason: '',
    customReason: ''
});

const cancellationReasons = [
    'Harga terlalu mahal',
    'Ingin ganti layanan',
    'Jadwal tidak tersedia',
    'Masalah teknis',
    'Hanya mencoba aplikasi',
    'Alasan lainnya'
];

const confirmCancel = () => {
    isCancelModalOpen.value = true;
};

const submitCancel = () => {
    // In this context, canceling means discarding the draft and going back
    // We could potentially log the reason if there was a route for it
    isCancelModalOpen.value = false;
    window.history.back();
};

const steps = [
    { id: 1, name: 'Layanan' },
    { id: 2, name: 'Pengiriman' },
    { id: 3, name: 'Jadwal & Alamat' },
    { id: 4, name: 'Detail Laundry' },
    { id: 5, name: 'Pembayaran' },
];

const isStepValid = computed(() => {
    if (currentStep.value === 1) return !!form.service_id;
    if (currentStep.value === 2) return !!form.delivery_type;
    if (currentStep.value === 3) return form.pickup_date && form.pickup_time && form.pickup_address;
    if (currentStep.value === 4) {
        if (isKgService.value) return !!form.estimated_weight;
        return form.item_qty >= 1;
    }
    if (currentStep.value === 5) return !!form.payment_preference;
    return false;
});

const nextStep = () => {
    if (isStepValid.value && currentStep.value < totalSteps) {
        currentStep.value++;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

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

function addToCart() {
    form.post(route('pelanggan.keranjang.tambah'), {
        onSuccess: () => {
            // Optional: showing a toast or similar if not redirecting
        }
    });
}

const goBack = () => {
    confirmCancel();
};
</script>

<template>
    <Head title="Buat Pesanan" />

    <AppLayout>
        <!-- Red Header Section -->
        <div class="bg-[#E30613] pt-24 lg:pt-32 pb-24 lg:pb-32 relative overflow-hidden">
            <!-- Back Button -->
            <button
                @click="currentStep === 1 ? goBack() : prevStep()"
                class="absolute top-[110px] lg:top-[140px] left-6 w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center transition-all z-30 backdrop-blur-sm border border-white/20 shadow-lg active:scale-95"
            >
                <i class="fas fa-arrow-left"></i>
            </button>

            <div class="absolute inset-0 opacity-20 pointer-events-none z-0">
                <div class="absolute top-10 left-10 w-12 h-12 bg-white rounded-full opacity-50"></div>
                <div class="absolute top-20 right-1/4 w-8 h-8 bg-white rounded-full opacity-50"></div>
                <div class="absolute bottom-10 right-10 w-24 h-24 bg-white rounded-full opacity-30"></div>
            </div>

            <div class="relative z-10 max-w-2xl mx-auto px-6 text-center text-white">
                <h1 class="text-3xl font-bold mb-2">Buat Pesanan</h1>
                <p class="text-sm opacity-90">Lengkapi detail pesanan laundry Anda</p>
            </div>

            <!-- Curved Bottom -->
            <div class="absolute bottom-0 left-0 w-full z-10 leading-none pointer-events-none">
                <svg class="block w-full h-12 sm:h-16 lg:h-20" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="fill-[#FFE800]" d="M0,128L80,144C160,160,320,192,480,197.3C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                </svg>
                <svg class="absolute bottom-0 left-0 w-full h-8 sm:h-10 lg:h-12" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="fill-gray-50" d="M0,64L80,90.7C160,117,320,171,480,186.7C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                </svg>
            </div>
        </div>

        <!-- Stepper Design -->
        <div class="max-w-2xl mx-auto px-4 mt-6 mb-8 relative z-20">
            <div class="flex items-center justify-between relative px-2">
                <!-- Progress Line Background -->
                <div class="absolute top-4 left-0 right-0 h-0.5 bg-gray-200 -z-0 mx-10">
                    <!-- Active Progress Line -->
                    <div
                        class="h-full bg-[#E30613] transition-all duration-500 ease-in-out"
                        :style="{ width: `${((currentStep - 1) / (totalSteps - 1)) * 100}%` }"
                    ></div>
                </div>
.

                <div v-for="step in steps" :key="step.id" class="relative z-10 flex flex-col items-center">
                    <div
                        class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-black transition-all duration-500 border-2"
                        :class="[
                            currentStep === step.id ? 'bg-white border-[#E30613] text-[#E30613] scale-110 shadow-lg' :
                            currentStep > step.id ? 'bg-[#E30613] border-[#E30613] text-white' :
                            'bg-gray-100 border-gray-200 text-gray-400'
                        ]"
                    >
                        <i v-if="currentStep > step.id" class="fas fa-check"></i>
                        <span v-else>{{ step.id }}</span>
                    </div>
                    <span
                        class="text-[9px] font-black mt-2 uppercase tracking-tight text-center max-w-[60px] leading-tight transition-colors duration-300"
                        :class="currentStep >= step.id ? 'text-[#E30613]' : 'text-gray-400'"
                    >
                        {{ step.name }}
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 min-h-screen pb-32 lg:pb-16">
            <div class="max-w-2xl mx-auto px-4 mt-2 relative z-20 space-y-5">

                <form @submit.prevent="submit" class="space-y-6">

                <!-- STEP 1: LAYANAN -->
                <div v-show="currentStep === 1" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <!-- Services Header & Options -->
                    <section class="space-y-4 pt-2">
                        <div class="flex items-center justify-between border-b pb-2">
                            <h2 class="text-sm font-bold text-gray-900">
                                1. Layanan Terpilih
                            </h2>
                            <Link href="/pelanggan/daftar-layanan" class="text-[10px] font-bold text-[#E30613] hover:underline uppercase tracking-wide">Ubah Layanan <i class="fas fa-chevron-right ml-1"></i></Link>
                        </div>
                    <div class="pb-2">
                        <div class="w-full border-2 border-[#E30613] bg-red-50/30 rounded-xl p-4 text-left shadow-sm relative overflow-hidden">
                            <!-- Decorative background -->
                            <div class="absolute -right-4 -bottom-4 opacity-10">
                                <i class="fas fa-tshirt text-6xl text-[#E30613]"></i>
                            </div>

                            <div class="relative z-10">
                                <!-- Top: Title -->
                                <div class="flex items-start justify-between">
                                    <div>
                                        <span v-if="currentService.category" class="inline-block px-2 py-0.5 bg-red-100 text-[#E30613] rounded text-[9px] font-black uppercase tracking-wider mb-1.5">
                                            {{ currentService.category }}
                                        </span>
                                        <h3 class="text-base font-black text-gray-900 leading-tight">
                                            {{ currentService.name }}
                                        </h3>
                                    </div>
                                    <i class="fas fa-check-circle text-xl text-[#E30613]"></i>
                                </div>

                                <!-- Optional Description -->
                                <p class="text-xs text-gray-600 mt-2 leading-relaxed pr-8">
                                    {{ currentService.description || 'Layanan laundry profesional dengan penanganan terbaik.' }}
                                </p>

                                <!-- Price -->
                                <div class="mt-4 flex flex-col">
                                    <div v-if="currentService.is_discount_today && form.use_discount" class="flex items-center gap-1.5 mb-0.5">
                                        <span class="text-xs text-gray-400 line-through decoration-red-400">{{ formatRupiah(currentService.price) }}</span>
                                        <span class="text-[9px] px-1 bg-red-100 text-red-600 font-black rounded">-20%</span>
                                    </div>
                                    <div class="flex items-end">
                                        <span class="text-lg font-black text-[#E30613]">{{ formatRupiah(form.use_discount ? currentService.discounted_price : currentService.price) }}</span>
                                        <span class="text-xs text-gray-500 font-bold ml-1 mb-0.5">/{{ currentService.unit }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Discount Selection -->
                    <div v-if="currentService.is_discount_today" class="pt-2">
                        <div
                            @click="form.use_discount = !form.use_discount"
                            class="w-full border-2 rounded-xl p-4 flex items-center justify-between cursor-pointer transition-all shadow-sm"
                            :class="form.use_discount ? 'border-[#FFE800] bg-yellow-50/50 shadow-md' : 'border-gray-100 bg-white opacity-80'"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg" :class="form.use_discount ? 'bg-[#FFE800] text-gray-900' : 'bg-gray-100 text-gray-400'">
                                    <i class="fas fa-percent"></i>
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-gray-900 uppercase tracking-tight">Gunakan Diskon Hari Ini</h4>
                                    <p class="text-[10px] text-gray-500 font-bold">Hemat 20% khusus untuk layanan {{ currentService.name }}</p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-12 h-6 rounded-full relative transition-all duration-300"
                                    :class="form.use_discount ? 'bg-[#FFE800]' : 'bg-gray-200'"
                                >
                                    <div
                                        class="absolute top-1 w-4 h-4 rounded-full bg-white transition-all duration-300 shadow-sm"
                                        :class="form.use_discount ? 'left-7' : 'left-1'"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="pt-2">
                        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm space-y-3">
                            <div class="flex items-center gap-2 text-[#E30613] border-b border-dashed pb-2">
                                <i class="fas fa-edit"></i>
                                <label class="text-[11px] font-black text-gray-500 uppercase tracking-wide">
                                    Catatan Layanan (Opsional)
                                </label>
                            </div>

                            <textarea
                                v-model="form.laundry_notes"
                                rows="2"
                                placeholder="Contoh: Pisahkan baju putih, fokus noda kerah..."
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all text-gray-800"
                            ></textarea>
                        </div>
                    </div>
                </section>
                </div>

                <!-- STEP 2: PENGIRIMAN -->
                <div v-show="currentStep === 2" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <section class="space-y-4">
                        <h2 class="text-sm font-bold text-gray-900 border-b pb-2">
                            2. Metode Pengiriman
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label
                                v-for="opt in deliveryOptions"
                                :key="opt.value"
                                class="relative block border-2 rounded-xl p-4 cursor-pointer transition-all"
                                :class="form.delivery_type === opt.value
                                    ? 'border-[#E30613] bg-red-50/20 shadow-md'
                                    : 'border-gray-100 hover:border-gray-200 bg-white shadow-sm'"
                            >
                                <input
                                    type="radio"
                                    v-model="form.delivery_type"
                                    :value="opt.value"
                                    class="hidden"
                                />

                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-xl mb-1" :class="form.delivery_type === opt.value ? 'bg-[#E30613] text-white' : 'bg-gray-100 text-gray-400'">
                                        <i :class="opt.icon"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black text-gray-900 uppercase">
                                            {{ opt.label }}
                                        </p>
                                        <p class="text-[9px] text-gray-500 mt-1 leading-tight h-8 line-clamp-2">
                                            {{ opt.description }}
                                        </p>
                                    </div>

                                    <p class="text-xs font-black text-[#E30613] pt-2 border-t border-dashed w-full">
                                        {{ formatRupiah(opt.fee) }}
                                    </p>
                                </div>

                                <!-- Checkmark -->
                                <div v-if="form.delivery_type === opt.value" class="absolute top-2 right-2">
                                    <i class="fas fa-check-circle text-[#E30613]"></i>
                                </div>
                            </label>
                        </div>
                    </section>
                </div>

                <!-- STEP 3: JADWAL & ALAMAT -->
                <div v-show="currentStep === 3" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <section class="space-y-4">
                        <div class="flex items-center justify-between border-b pb-2">
                            <h2 class="text-sm font-bold text-gray-900">3. Jadwal & Alamat Penjemputan</h2>
                            <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm space-y-5">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-gray-700">
                                    <i class="far fa-calendar-alt text-[#E30613]"></i>
                                    <label class="text-[11px] font-black text-gray-500 uppercase tracking-wide">Pilih Tanggal <span class="text-[#E30613]">*</span></label>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <label v-for="dt in pickupDates" :key="dt.value"
                                        class="flex items-center justify-center py-2.5 px-3 rounded-lg border-2 text-[11px] font-black cursor-pointer transition-all text-center"
                                        :class="form.pickup_date === dt.value ? 'bg-white border-[#E30613] text-[#E30613] shadow-md' : 'bg-gray-50 border-gray-100 text-gray-400 hover:border-gray-200'">
                                        <input type="radio" v-model="form.pickup_date" :value="dt.value" class="sr-only">
                                        {{ dt.label }}
                                    </label>
                                </div>
                            </div>

                            <div v-if="form.pickup_date" class="space-y-2 pt-2 border-t border-dashed">
                                <div class="flex items-center gap-2 text-gray-700">
                                    <i class="far fa-clock text-[#E30613]"></i>
                                    <label class="text-[11px] font-black text-gray-500 uppercase tracking-wide">Pilih Jam <span class="text-[#E30613]">*</span></label>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <label v-for="slot in availableTimeSlots" :key="slot.value"
                                        class="flex items-center justify-center py-2.5 px-3 rounded-lg border-2 text-[11px] font-black transition-all text-center"
                                        :class="[
                                            slot.disabled ? 'bg-gray-100 text-gray-300 border-gray-50 cursor-not-allowed opacity-60' : 'cursor-pointer',
                                            !slot.disabled && form.pickup_time === slot.value ? 'bg-white border-[#E30613] text-[#E30613] shadow-md' : '',
                                            !slot.disabled && form.pickup_time !== slot.value ? 'bg-gray-50 border-gray-100 text-gray-400 hover:border-gray-200' : ''
                                        ]">
                                        <input type="radio" v-model="form.pickup_time" :value="slot.value" :disabled="slot.disabled" class="sr-only">
                                        {{ slot.label }}
                                    </label>
                                </div>
                            </div>

                            <div class="space-y-2 pt-2 border-t border-dashed">
                                <div class="flex flex-col gap-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 text-gray-700">
                                            <i class="fas fa-map-marker-alt text-[#E30613]"></i>
                                            <label class="text-[11px] font-black text-gray-500 uppercase tracking-wide">Alamat Penjemputan <span class="text-[#E30613]">*</span></label>
                                        </div>
                                        <Link v-if="addresses.length < 3" :href="route('profile.edit')" class="text-[9px] font-black text-[#E30613] uppercase tracking-widest hover:underline">+ Tambah Baru</Link>
                                    </div>

                                    <!-- Quick Address Selector -->
                                    <div v-if="addresses.length > 0" class="flex overflow-x-auto pb-1 gap-2 mb-1 scrollbar-hide no-scrollbar">
                                        <button
                                            v-for="addr in addresses"
                                            :key="addr.id"
                                            type="button"
                                            @click="form.pickup_address = addr.address"
                                            class="px-3 py-1.5 rounded-lg border-2 text-[10px] font-black transition-all flex items-center gap-2 shadow-sm active:scale-95 shrink-0 whitespace-nowrap"
                                            :class="form.pickup_address === addr.address
                                                ? 'bg-red-50 border-[#E30613] text-[#E30613]'
                                                : 'bg-white border-gray-100 text-gray-400 hover:border-gray-200'"
                                        >
                                            <i :class="addr.label.toLowerCase().includes('rumah') ? 'fas fa-home' : (addr.label.toLowerCase().includes('kantor') ? 'fas fa-building' : 'fas fa-map-pin')"></i>
                                            {{ addr.label }}
                                            <span v-if="addr.is_default" class="w-1.5 h-1.5 rounded-full bg-[#E30613]"></span>
                                        </button>
                                    </div>
                                </div>
                                <textarea v-model="form.pickup_address" rows="3" placeholder="Contoh: Jl. Merdeka No. 123, Semarang..." required
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-bold focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all resize-none text-gray-800 placeholder:font-normal placeholder:text-gray-300"></textarea>
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest"><i class="fas fa-info-circle mr-1"></i> Pastikan alamat sudah benar dan detail.</p>
                            </div>

                            <div class="space-y-2 pt-2 border-t border-dashed">
                                <div class="flex items-center gap-2 text-gray-700">
                                    <i class="fas fa-comment-alt text-[#E30613]"></i>
                                    <label class="text-[11px] font-black text-gray-500 uppercase tracking-wide">Catatan Kurir (Opsional)</label>
                                </div>
                                <input v-model="form.courier_notes" type="text" placeholder="Contoh: Rumah cat putih, pagar hitam..."
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-bold focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all text-gray-800 placeholder:font-normal placeholder:text-gray-300" />
                            </div>
                        </div>
                    </section>
                </div>

                <!-- STEP 4: DETAIL LAUNDRY -->
                <div v-show="currentStep === 4" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <section class="space-y-4">
                        <div class="flex items-center justify-between border-b pb-2">
                            <h2 class="text-sm font-bold text-gray-900">4. Detail Laundry</h2>
                        </div>

                        <!-- Dynamic Weight or Qty -->
                        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm space-y-4">
                            <template v-if="isKgService">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-[11px] font-black text-gray-500 uppercase tracking-wide">Estimasi Berat <span class="text-[#E30613]">*</span></label>
                                    <span class="px-2 py-0.5 bg-red-100 text-[#E30613] rounded text-[8px] font-black uppercase tracking-widest">Wajib</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label v-for="wt in weightOptions" :key="wt.value"
                                        class="flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
                                        :class="form.estimated_weight === wt.value ? 'border-[#E30613] bg-red-50/20 shadow-sm' : 'border-gray-100 hover:border-gray-200 bg-white'">
                                        <input type="radio" v-model="form.estimated_weight" :value="wt.value" required class="mt-1 text-[#E30613] focus:ring-[#E30613]">
                                        <div>
                                            <h4 class="text-[11px] font-black text-gray-900 uppercase tracking-tight" :class="form.estimated_weight === wt.value ? 'text-[#E30613]' : ''">{{ wt.label }}</h4>
                                            <p class="text-[10px] text-gray-500 mt-0.5 font-bold">{{ wt.helper }}</p>
                                        </div>
                                    </label>
                                </div>
                            </template>
                            <template v-else>
                                <label class="text-[11px] font-black text-gray-500 uppercase tracking-wide block">Jumlah Item ({{ currentService.unit.replace('/', '') }}) <span class="text-[#E30613]">*</span></label>
                                <div class="flex items-center gap-4 pt-2">
                                    <button type="button" @click="form.item_qty = Math.max(1, form.item_qty - 1)" class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center font-bold text-gray-700 hover:bg-gray-200 transition-colors">-</button>
                                    <input type="number" v-model="form.item_qty" min="1" class="w-20 py-2 text-center border-gray-100 rounded-lg font-black text-gray-900 focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613]" />
                                    <button type="button" @click="form.item_qty++" class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center font-bold text-gray-700 hover:bg-gray-200 transition-colors">+</button>
                                </div>
                            </template>
                        </div>

                        <!-- Layanan Ekstra -->
                        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="text-[11px] font-black text-gray-500 uppercase tracking-wide">Layanan Ekstra (Opsional)</label>
                                <span v-if="form.extra_services.length" class="px-2 py-0.5 bg-red-100 text-[#E30613] rounded text-[8px] font-black uppercase tracking-widest">{{ form.extra_services.length }} Dipilih</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label v-for="opt in extraOptions" :key="opt.value"
                                    class="relative flex flex-col items-start gap-2 p-3 rounded-lg border-2 cursor-pointer transition-all"
                                    :class="[
                                        opt.disabled ? 'opacity-50 cursor-not-allowed bg-gray-50 border-gray-100' :
                                        form.extra_services.includes(opt.value) ? 'border-[#E30613] bg-red-50/20 shadow-sm' : 'border-gray-100 hover:border-gray-200 bg-white'
                                    ]">
                                    <input type="checkbox" v-model="form.extra_services" :value="opt.value" :disabled="opt.disabled" class="sr-only">

                                    <div class="flex justify-between w-full items-start">
                                        <h4 class="text-[11px] font-black text-gray-900 uppercase" :class="form.extra_services.includes(opt.value) ? 'text-[#E30613]' : ''">{{ opt.label }}</h4>
                                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0"
                                            :class="form.extra_services.includes(opt.value) ? 'bg-[#E30613] border-[#E30613] text-white' : 'border-gray-200 bg-white'">
                                            <i v-if="form.extra_services.includes(opt.value)" class="fas fa-check text-[8px]"></i>
                                        </div>
                                    </div>
                                    <p class="text-[9px] text-gray-400 font-bold leading-tight">{{ opt.desc }}</p>

                                    <div class="mt-2 pt-2 flex w-full justify-between items-center border-t border-dashed">
                                        <span class="text-[10px] font-black" :class="opt.priceValue > 0 ? 'text-[#E30613]' : 'text-[#22C55E]'">{{ opt.priceValue > 0 ? '+ ' + formatRupiah(opt.priceValue) : 'Gratis' }}</span>
                                        <div class="group relative inline-block text-gray-300">
                                            <i class="fas fa-info-circle text-[10px]"></i>
                                            <div class="absolute bottom-full right-0 mb-2 hidden group-hover:block w-48 p-2 bg-black text-white text-[9px] rounded shadow-xl z-30 font-bold uppercase tracking-tighter">
                                                {{ opt.tooltip }}
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- STEP 5: PEMBAYARAN & KONFIRMASI -->
                <div v-show="currentStep === 5" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-10">
                    <section class="space-y-4">
                        <h2 class="text-sm font-bold text-gray-900 border-b pb-2">
                            5. Metode Pembayaran
                        </h2>

                        <div class="grid grid-cols-2 gap-3">
                            <label
                                v-for="pay in paymentOptions"
                                :key="pay.value"
                                class="border-2 rounded-xl p-4 text-center cursor-pointer transition-all flex flex-col items-center gap-2"
                                :class="form.payment_preference === pay.value
                                    ? 'border-[#E30613] bg-red-50/20 text-[#E30613] shadow-md'
                                    : 'border-gray-100 text-gray-400 bg-white shadow-sm hover:border-gray-200'"
                            >
                                <input
                                    type="radio"
                                    v-model="form.payment_preference"
                                    :value="pay.value"
                                    class="hidden"
                                />
                                <i :class="pay.icon" class="text-lg"></i>
                                <p class="text-[10px] font-black uppercase tracking-tight">
                                    {{ pay.label }}
                                </p>
                            </label>
                        </div>
                    </section>

                    <!-- Warning Reminder -->
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3 text-amber-900 shadow-sm">
                        <i class="fas fa-exclamation-triangle mt-0.5"></i>
                        <div>
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-amber-800">Penting</h4>
                            <p class="text-[10px] mt-1 pr-2 font-bold leading-relaxed">
                                Berat final beserta total biaya sebenarnya baru akan ditentukan <span class="text-[#E30613]">setelah proses penimbangan secara aktual</span> oleh kurir.
                            </p>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <section class="bg-white border border-gray-100 p-5 rounded-2xl shadow-xl space-y-4 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-[0.03] pointer-events-none">
                            <i class="fas fa-receipt text-9xl"></i>
                        </div>

                        <h2 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] border-b pb-3">Ringkasan Pesanan</h2>
                        <ul class="text-[11px] space-y-3 text-gray-700 font-bold">
                            <li class="flex justify-between items-center">
                                <span class="text-gray-400 font-black uppercase tracking-tighter">Layanan</span>
                                <span class="text-right text-gray-900">{{ services.find(s => s.id === form.service_id)?.name }}</span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-gray-400 font-black uppercase tracking-tighter">Metode</span>
                                <span class="text-right text-gray-900">{{ deliveryOptions.find(d => d.value === form.delivery_type)?.label }}</span>
                            </li>
                            <li class="flex justify-between items-start">
                                <span class="text-gray-400 font-black uppercase tracking-tighter shrink-0">Waktu</span>
                                <div class="text-right flex flex-col">
                                    <span class="text-gray-900" v-if="form.pickup_date">{{ pickupDates.find(d => d.value === form.pickup_date)?.label }}</span>
                                    <span class="text-[#E30613] text-[10px]" v-if="form.pickup_time">{{ availableTimeSlots.find(s => s.value === form.pickup_time)?.label }}</span>
                                </div>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-gray-400 font-black uppercase tracking-tighter">Ekstra</span>
                                <span class="text-right" :class="form.extra_services.length ? 'text-[#E30613]' : 'text-gray-900'">{{ form.extra_services.length ? form.extra_services.length + ' Dipilih' : '-' }}</span>
                            </li>
                        </ul>

                        <!-- Cost Estimations -->
                        <div class="mt-4 pt-4 border-t-2 border-dashed border-gray-100 space-y-3">
                            <div class="flex justify-between text-[11px]">
                                <span class="text-gray-400 font-black uppercase tracking-tighter">Subtotal</span>
                                <span class="font-bold text-gray-800">{{ calculatedServiceCostText }}</span>
                            </div>
                            <div class="flex justify-between text-[11px]">
                                <span class="text-gray-400 font-black uppercase tracking-tighter">Ongkos Kirim</span>
                                <span class="font-bold text-gray-800">{{ formatRupiah(estimatedDeliveryFee) }}</span>
                            </div>
                            <div class="flex justify-between items-end pt-3 mt-2 border-t border-gray-100">
                                <div>
                                    <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Estimasi</span>
                                    <span class="text-[8px] text-[#E30613] font-black uppercase">*Dikonfirmasi saat jemput</span>
                                </div>
                                <span class="text-xl font-black text-[#E30613] tracking-tighter">{{ totalEstimatedCostText }}</span>
                            </div>
                        </div>
                    </section>
                </div>

                    <!-- Submit Button Desktop -->
                    <div class="hidden lg:flex gap-4 pt-6">
                        <button v-if="currentStep > 1" type="button" @click="prevStep"
                            class="flex-1 py-4 rounded-xl font-black uppercase tracking-widest text-gray-400 border border-gray-200 hover:bg-gray-50 transition-all active:scale-95 text-xs">
                            Sebelumnya
                        </button>
                        <button v-if="currentStep < totalSteps" type="button" @click="nextStep"
                            :disabled="!isStepValid"
                            class="flex-[2] py-4 rounded-xl font-black uppercase tracking-widest text-white shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2 text-xs"
                            :class="!isStepValid ? 'bg-gray-200 cursor-not-allowed' : 'bg-[#E30613] hover:bg-black'">
                            Lanjut ke {{ steps[currentStep] ? steps[currentStep].name : 'Langkah Selanjutnya' }} <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                        <button v-if="currentStep === totalSteps" type="submit"
                            :disabled="isSubmitDisabled"
                            class="flex-[2] py-4 rounded-xl font-black uppercase tracking-widest text-white shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2 text-xs"
                            :class="isSubmitDisabled ? 'bg-gray-200 cursor-not-allowed' : 'bg-[#E30613] hover:bg-black shadow-red-500/20'">
                            <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                            <span v-if="!form.processing">Pesan Sekarang</span>
                        </button>

                        <button v-if="currentStep === totalSteps" type="button" @click="addToCart"
                            :disabled="isSubmitDisabled"
                            class="flex-1 py-4 rounded-xl font-black uppercase tracking-widest text-[#E30613] border-2 border-[#E30613] hover:bg-red-50 transition-all active:scale-95 flex items-center justify-center gap-2 text-xs"
                            :class="isSubmitDisabled ? 'opacity-50 cursor-not-allowed' : ''">
                            <i class="fas fa-cart-plus"></i>
                            <span>Keranjang</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Mobile Fixed Action Bar -->
        <div class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-100 px-6 pt-4 pb-[calc(1.2rem+env(safe-area-inset-bottom))] shadow-[0_-15px_30px_rgba(0,0,0,0.05)] lg:hidden flex justify-between items-center rounded-t-[2rem]">
            <div class="pl-2">
                <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-0.5" v-if="currentStep < 4">Estimasi</p>
                <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-0.5" v-else-if="isKgService">Total Estimasi</p>
                <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-0.5" v-else>Total Harga</p>

                <p class="text-[16px] font-black text-gray-900 leading-tight tracking-tighter">
                    <span v-if="isKgService && !form.estimated_weight">---</span>
                    <span v-else>{{ totalEstimatedCostText }}</span>
                </p>
            </div>

            <div class="flex gap-2">
                <button v-if="currentStep > 1" type="button" @click="prevStep"
                    class="w-11 h-11 rounded-full border-2 border-gray-50 flex items-center justify-center text-gray-400 active:scale-90 transition-all">
                    <i class="fas fa-chevron-left text-sm"></i>
                </button>

                <button v-if="currentStep < totalSteps" type="button" @click="nextStep"
                    :disabled="!isStepValid"
                    class="px-7 py-3 rounded-full font-black text-white text-xs uppercase tracking-widest shadow-md transition-all active:scale-95 flex items-center justify-center gap-2"
                    :class="!isStepValid ? 'bg-gray-100 text-gray-300 shadow-none' : 'bg-[#E30613] shadow-red-500/20'">
                    Lanjut
                </button>

                <button v-if="currentStep === totalSteps" type="button" @click="submit"
                    :disabled="isSubmitDisabled"
                    class="px-7 py-3 rounded-full font-black text-white text-xs uppercase tracking-widest shadow-md transition-all active:scale-95 flex items-center justify-center gap-2"
                    :class="isSubmitDisabled ? 'bg-gray-200 text-gray-500 shadow-none' : 'bg-[#E30613] shadow-red-500/20'">
                    <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                    <span v-if="!form.processing">Pesan</span>
                </button>

                <button v-if="currentStep === totalSteps" type="button" @click="addToCart"
                    :disabled="isSubmitDisabled"
                    class="w-11 h-11 rounded-full border-2 border-[#E30613] flex items-center justify-center text-[#E30613] active:scale-90 transition-all"
                    :class="isSubmitDisabled ? 'opacity-50' : ''">
                    <i class="fas fa-cart-plus text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Cancellation Modal (Discard Draft) -->
        <Teleport to="body">
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="isCancelModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center px-4">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="isCancelModalOpen = false"></div>
                    
                    <div class="relative bg-white w-full max-w-sm rounded-[2.5rem] overflow-hidden shadow-2xl animate-in zoom-in-95 duration-300">
                        <div class="h-2 bg-[#E30613]"></div>
                        
                        <div class="p-8 space-y-6">
                            <div class="text-center space-y-2">
                                <div class="w-16 h-16 bg-red-50 text-[#E30613] rounded-full flex items-center justify-center mx-auto mb-4 border border-red-100">
                                    <i class="fas fa-exclamation-circle text-2xl"></i>
                                </div>
                                <h2 class="text-xl font-black text-gray-900 tracking-tight">Batalkan Pesanan?</h2>
                                <p class="text-xs text-gray-500 font-bold leading-relaxed">Sayang sekali Anda harus membatalkan. Beritahu kami alasannya agar kami bisa lebih baik lagi.</p>
                            </div>

                            <div class="space-y-3">
                                <div v-for="reason in cancellationReasons" :key="reason"
                                    @click="cancelForm.reason = reason"
                                    class="p-3 rounded-xl border-2 transition-all cursor-pointer flex items-center justify-between"
                                    :class="cancelForm.reason === reason ? 'border-[#E30613] bg-red-50/30' : 'border-gray-100 bg-gray-50/50 hover:border-gray-200'"
                                >
                                    <span class="text-[11px] font-bold text-gray-700">{{ reason }}</span>
                                    <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                                        :class="cancelForm.reason === reason ? 'bg-[#E30613] border-[#E30613] text-white' : 'border-gray-300 bg-white'">
                                        <i v-if="cancelForm.reason === reason" class="fas fa-check text-[8px]"></i>
                                    </div>
                                </div>
                                
                                <!-- Custom Reason Textarea -->
                                <textarea 
                                    v-if="cancelForm.reason === 'Alasan lainnya'"
                                    v-model="cancelForm.customReason"
                                    rows="2"
                                    placeholder="Tulis alasan Anda di sini..."
                                    class="w-full border-2 border-gray-100 rounded-xl px-3 py-2 text-[11px] font-bold focus:ring-0 focus:border-[#E30613] outline-none transition-all mt-2"
                                ></textarea>
                            </div>

                            <div class="flex gap-3">
                                <button 
                                    @click="isCancelModalOpen = false"
                                    class="flex-1 py-3.5 bg-gray-100 text-gray-500 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-gray-200 transition-all active:scale-95"
                                >
                                    Kembali
                                </button>
                                <button 
                                    @click="submitCancel"
                                    :disabled="!cancelForm.reason || (cancelForm.reason === 'Alasan lainnya' && !cancelForm.customReason)"
                                    class="flex-[2] py-3.5 bg-[#E30613] text-white rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-red-500/20 hover:bg-black transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Ya, Batalkan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.25s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }

/* Hide scrollbar for Chrome, Safari and Opera */
.no-scrollbar::-webkit-scrollbar,
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

/* Hide scrollbar for IE, Edge and Firefox */
.no-scrollbar,
.scrollbar-hide {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
</style>
