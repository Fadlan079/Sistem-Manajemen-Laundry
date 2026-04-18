<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';
import { onMounted } from 'vue';

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
    service_id:     props.service.id,
    delivery_type:  'jemput',
    pickup_address: props.auth?.user?.address ?? '',
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
    { value: 'antar_jemput', label: 'Antar Jemput', icon: 'fas fa-truck' },
    { value: 'jemput',       label: 'Jemput Saja',  icon: 'fas fa-motorcycle' },
    { value: 'antar',        label: 'Antar Saja',   icon: 'fas fa-box-open' },
];

function submit() {
    form.post(route('pelanggan.pesan.simpan'));
}
</script>

<template>
    <Head title="Buat Pesanan" />

    <AppLayout>
        <div class="pt-20 lg:pt-28 max-w-2xl mx-auto pb-16 px-4 sm:px-0 space-y-5">

            <!-- Back -->
            <Link href="/#layanan" class="flex items-center text-[11px] font-black text-gray-400 hover:text-[#E30613] uppercase tracking-widest transition-colors group">
                <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                Kembali ke Layanan
            </Link>

            <!-- Services Header & Options -->
            <section class="space-y-3 pt-2">
                <h2 class="text-sm font-bold text-gray-900 border-b pb-2 mb-4">Pilih Layanan</h2>
                <div class="grid grid-cols-3 md:grid-cols-4 gap-3">
                    <button v-for="srv in services" :key="srv.id" type="button" @click="form.service_id = srv.id"
                        class="relative flex flex-col items-center justify-center p-3 rounded-lg border-2 transition-all overflow-hidden"
                        :class="form.service_id === srv.id ? 'border-[#E30613] bg-red-50/20' : 'border-gray-200 hover:border-gray-300 bg-white'">

                        <div class="w-12 h-12 rounded-lg overflow-hidden mb-2 flex items-center justify-center bg-gray-100 border border-gray-200">
                            <img v-if="srv.image_url" :src="srv.image_url" :alt="srv.name" class="w-full h-full object-cover" />
                            <i v-else class="fas fa-tshirt text-2xl" :class="form.service_id === srv.id ? 'text-[#E30613]' : 'text-gray-400'"></i>
                        </div>

                        <span class="text-xs font-bold text-center leading-tight whitespace-nowrap" :class="form.service_id === srv.id ? 'text-[#E30613]' : 'text-gray-700'">{{ srv.name }}</span>

                        <!-- Checkmark Indicator -->
                        <div v-if="form.service_id === srv.id" class="absolute -top-1 -right-1 w-6 h-6 bg-[#E30613] rounded-bl-xl rounded-tr text-white flex items-center justify-center shadow-sm">
                            <i class="fas fa-check text-[10px]"></i>
                        </div>
                    </button>
                </div>
            </section>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Delivery Type -->
                <section class="space-y-3">
                    <h2 class="text-sm font-bold text-gray-900 border-b pb-2 mb-4">Metode Pengambilan</h2>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <label v-for="opt in deliveryOptions" :key="opt.value"
                            class="flex flex-col items-center justify-center gap-2 p-3 rounded-lg border-2 cursor-pointer transition-all"
                            :class="form.delivery_type === opt.value ? 'border-[#E30613] bg-[#E30613] text-white shadow-md' : 'border-gray-200 hover:border-gray-300 bg-white text-gray-600'">
                            <input type="radio" v-model="form.delivery_type" :value="opt.value" class="sr-only">
                            <i :class="[opt.icon, 'text-xl']"></i>
                            <span class="text-xs font-bold text-center leading-tight whitespace-nowrap">{{ opt.label }}</span>
                        </label>
                    </div>

                    <!-- Address (always required since all options involve courier) -->
                    <div class="space-y-2 mt-4 bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                        <label class="text-xs font-black text-gray-700 uppercase tracking-wide">Alamat Penjemputan</label>
                        <div class="relative">
                            <i class="fas fa-map-marker-alt absolute top-3 left-4 text-gray-400"></i>
                            <textarea v-model="form.pickup_address" rows="3" placeholder="Contoh: Jl. Ahmad Yani No. 12, Samarinda Kota"
                                class="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-3 text-sm focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] outline-none transition-all resize-none"></textarea>
                        </div>
                        <div v-if="form.errors.pickup_address" class="text-[11px] text-[#E30613] font-bold">{{ form.errors.pickup_address }}</div>
                    </div>
                </section>

                <!-- Info Banner -->
                <section class="bg-blue-50 rounded-lg border border-blue-100 p-4 flex gap-3 text-blue-800">
                    <i class="fas fa-info-circle mt-0.5 opacity-80"></i>
                    <div class="text-xs leading-relaxed space-y-1">
                        <p class="font-bold">Total Harga Belum Ditentukan</p>
                        <p class="opacity-90">Berat cucian (KG) dan total biaya akhir akan dikonfirmasi lebih lanjut oleh kurir kami saat proses penjemputan maupun setibanya di outlet. Pembayaran dilakukan setelahnya.</p>
                    </div>
                </section>

                <!-- Errors -->
                <div v-if="form.hasErrors" class="bg-red-50 border border-red-200 rounded-lg p-4 text-xs text-red-600 font-bold">
                    Mohon periksa kembali isian formulir Anda.
                </div>

                <!-- Submit -->
                <button type="submit"
                    :disabled="form.processing"
                    class="w-full py-4 mt-6 rounded-xl font-bold text-white text-sm shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2"
                    :class="form.processing ? 'bg-gray-300 cursor-not-allowed' : 'bg-[#E30613] hover:bg-black hover:shadow-xl'">
                    <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                    <span v-if="!form.processing">Lanjutkan</span>
                </button>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.25s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
