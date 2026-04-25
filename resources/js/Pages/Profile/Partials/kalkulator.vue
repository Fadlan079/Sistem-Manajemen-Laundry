<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    services: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['back']);

const mode = ref('kiloan'); // 'kiloan' or 'satuan'
const selectedServiceId = ref(null);
const quantity = ref(1);
const isDropdownOpen = ref(false);

const toggleDropdown = () => isDropdownOpen.value = !isDropdownOpen.value;
const closeDropdown = () => isDropdownOpen.value = false;

const selectService = (id) => {
    selectedServiceId.value = id;
    closeDropdown();
};

const kiloanServices = computed(() => {
    return props.services.filter(s => s.unit && s.unit.toLowerCase().includes('kg'));
});

const satuanServices = computed(() => {
    return props.services.filter(s => s.unit && (s.unit.toLowerCase().includes('pcs') || s.unit.toLowerCase().includes('pasang')));
});

const currentServices = computed(() => {
    return mode.value === 'kiloan' ? kiloanServices.value : satuanServices.value;
});

const selectedService = computed(() => {
    return currentServices.value.find(s => s.id === selectedServiceId.value);
});

const totalPrice = computed(() => {
    if (!selectedService.value) return 0;
    const price = selectedService.value.discounted_price || selectedService.value.price;
    return price * quantity.value;
});

// Reset selection when mode changes
watch(mode, () => {
    selectedServiceId.value = currentServices.value.length > 0 ? currentServices.value[0].id : null;
    quantity.value = mode.value === 'kiloan' ? 1 : 1;
    closeDropdown();
});

// Set initial selection
if (currentServices.value.length > 0) {
    selectedServiceId.value = currentServices.value[0].id;
}

const formatPrice = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Tab Selector -->
        <div class="flex bg-white p-1 rounded-xl shadow-sm border border-gray-100">
            <button
                @click="mode = 'kiloan'"
                :class="[
                    'flex-1 py-2.5 text-xs font-black uppercase tracking-widest rounded-lg transition-all duration-300',
                    mode === 'kiloan' ? 'bg-[#E30613] text-white shadow-sm' : 'text-gray-400 hover:text-gray-600'
                ]"
            >
                <i class="fas fa-weight-hanging mr-2"></i> Kiloan
            </button>
            <button
                @click="mode = 'satuan'"
                :class="[
                    'flex-1 py-2.5 text-xs font-black uppercase tracking-widest rounded-lg transition-all duration-300',
                    mode === 'satuan' ? 'bg-[#E30613] text-white shadow-sm' : 'text-gray-400 hover:text-gray-600'
                ]"
            >
                <i class="fas fa-shirt mr-2"></i> Satuan
            </button>
        </div>

        <!-- Calculator Card -->
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
            <!-- Card Header Design -->
            <div class="bg-[#E30613] p-5 relative overflow-hidden">
                <h3 class="text-white font-black text-lg flex items-center gap-3 relative z-10">
                    <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calculator"></i>
                    </div>
                    Hitung Estimasi
                </h3>
            </div>

            <div class="p-6 space-y-6">
                <!-- Service Selection -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Pilih Layanan</label>
                    
                    <div class="relative">
                        <!-- Custom Select Trigger -->
                        <button
                            @click="toggleDropdown"
                            type="button"
                            class="w-full bg-gray-50 rounded-xl px-5 py-4 flex items-center justify-between border border-transparent hover:border-gray-200 transition-all group"
                        >
                            <div class="flex flex-col items-start">
                                <span class="text-sm font-bold text-gray-800">{{ selectedService?.name || 'Pilih layanan...' }}</span>
                                <span v-if="selectedService" class="text-[10px] font-medium text-gray-400">
                                    {{ formatPrice(selectedService.discounted_price || selectedService.price) }}{{ selectedService.unit }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span v-if="selectedService?.is_discount_today" class="px-2 py-0.5 bg-red-100 text-red-600 text-[9px] font-black rounded-lg">PROMO</span>
                                <i :class="['fas fa-chevron-down text-xs text-gray-300 transition-transform duration-300', isDropdownOpen ? 'rotate-180' : '']"></i>
                            </div>
                        </button>

                        <!-- Custom Dropdown Menu -->
                        <transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 translate-y-2"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 translate-y-2"
                        >
                            <div v-if="isDropdownOpen" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 z-50 overflow-hidden max-h-[300px] overflow-y-auto">
                                <div class="p-2 space-y-1">
                                    <button
                                        v-for="service in currentServices"
                                        :key="service.id"
                                        @click="selectService(service.id)"
                                        :class="[
                                            'w-full flex items-center justify-between p-3 rounded-lg text-left transition-all',
                                            selectedServiceId === service.id ? 'bg-red-50' : 'hover:bg-gray-50'
                                        ]"
                                    >
                                        <div class="flex flex-col">
                                            <span :class="['text-xs font-bold', selectedServiceId === service.id ? 'text-[#E30613]' : 'text-gray-700']">
                                                {{ service.name }}
                                            </span>
                                            <span class="text-[10px] text-gray-400">
                                                {{ formatPrice(service.discounted_price || service.price) }}{{ service.unit }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span v-if="service.is_discount_today" class="text-[8px] font-black text-red-500 bg-red-50 px-1.5 py-0.5 rounded border border-red-100 uppercase tracking-tighter">Hemat {{ Math.round(service.discount_percentage) }}%</span>
                                            <i v-if="selectedServiceId === service.id" class="fas fa-check text-[#E30613] text-[10px]"></i>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </transition>

                        <!-- Click Away Overlay -->
                        <div v-if="isDropdownOpen" @click="closeDropdown" class="fixed inset-0 z-40"></div>
                    </div>
                </div>

                <!-- Quantity Input -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">
                        {{ mode === 'kiloan' ? 'Berat (KG)' : 'Jumlah (PCS)' }}
                    </label>
                    <div class="flex items-center gap-3">
                        <button
                            @click="quantity = Math.max(1, quantity - (mode === 'kiloan' ? 0.5 : 1))"
                            class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-[#E30613] transition-all active:scale-95 border border-gray-100"
                        >
                            <i class="fas fa-minus"></i>
                        </button>
                        
                        <div class="flex-1 relative">
                            <input
                                v-model.number="quantity"
                                type="number"
                                step="any"
                                class="w-full bg-gray-50 border-none rounded-xl py-3.5 text-center text-lg font-black text-gray-900 focus:ring-2 focus:ring-[#FFE800] transition-all"
                            >
                            <span class="absolute right-4 inset-y-0 flex items-center text-[10px] font-black text-gray-300 uppercase tracking-widest pointer-events-none">
                                {{ mode === 'kiloan' ? 'KG' : 'PCS' }}
                            </span>
                        </div>

                        <button
                            @click="quantity += (mode === 'kiloan' ? 0.5 : 1)"
                            class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-[#E30613] transition-all active:scale-95 border border-gray-100"
                        >
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>

                <!-- Result Area -->
                <div class="mt-6 pt-6 border-t border-dashed border-gray-200">
                    <div class="bg-[#FFE800]/5 rounded-2xl p-5 border border-dashed border-[#FFE800]/50 relative overflow-hidden">
                        <div class="absolute -right-6 -bottom-6 opacity-5 rotate-12">
                            <i class="fas fa-receipt text-7xl"></i>
                        </div>
                        
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-xs font-black text-gray-400 uppercase tracking-widest">Detail Estimasi</span>
                            <span class="px-3 py-1 bg-white rounded-full text-[9px] font-black text-[#E30613] shadow-sm uppercase tracking-tighter border border-gray-100">
                                {{ selectedService?.category || 'Laundry' }}
                            </span>
                        </div>

                        <div class="flex items-end justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-bold text-gray-600">
                                        {{ quantity }} {{ mode === 'kiloan' ? 'kg' : 'pcs' }} × {{ formatPrice(selectedService?.discounted_price || selectedService?.price || 0) }}
                                    </p>
                                    <span v-if="selectedService?.is_discount_today" class="px-2 py-0.5 bg-red-100 text-red-600 text-[10px] font-black rounded-lg animate-pulse">
                                        Hemat {{ Math.round(selectedService.discount_percentage) }}%
                                    </span>
                                </div>
                                
                                <div class="flex flex-col">
                                    <p v-if="selectedService?.is_discount_today" class="text-xs font-bold text-gray-400 line-through -mb-1">
                                        {{ formatPrice(selectedService.price * quantity) }}
                                    </p>
                                    <p class="text-2xl font-black text-[#E30613]">
                                        {{ formatPrice(totalPrice) }}
                                    </p>
                                </div>
                            </div>
                            <div class="relative">
                                <i class="fas fa-coins text-3xl text-[#FFE800] drop-shadow-sm"></i>
                                <div v-if="selectedService?.is_discount_today" class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center border-2 border-white shadow-sm">
                                    <i class="fas fa-percent text-[8px]"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-center text-[10px] font-medium text-gray-400 px-4">
                    * Harga di atas merupakan estimasi. Biaya akhir akan ditentukan setelah penimbangan atau pengecekan item secara langsung oleh tim kami.
                </p>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex items-center justify-between group cursor-pointer hover:bg-gray-50 transition-all">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-50 text-green-500 rounded-lg flex items-center justify-center group-hover:bg-green-500 group-hover:text-white transition-all">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div>
                    <p class="text-xs font-black text-gray-800">Siap untuk pesan?</p>
                    <p class="text-[10px] text-gray-400">Klik untuk langsung memilih layanan</p>
                </div>
            </div>
            <button @click="$inertia.visit(route('pelanggan.daftar-layanan'))" class="w-8 h-8 rounded-full border border-gray-100 flex items-center justify-center text-gray-300 group-hover:text-green-500 group-hover:border-green-200 transition-all">
                <i class="fas fa-arrow-right text-xs"></i>
            </button>
        </div>
    </div>
</template>

<style scoped>
/* Hide spin buttons */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type=number] {
  -moz-appearance: textfield;
}
</style>