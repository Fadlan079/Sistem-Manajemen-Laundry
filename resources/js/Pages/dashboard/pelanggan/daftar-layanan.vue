<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';

const props = defineProps({
    auth: Object,
    services: Array,
    categories: Array,
});

const searchQuery = ref('');
const activeCategory = ref('Semua');
const showFilter = ref(false);

const filterOptions = ref({
    minPrice: '',
    maxPrice: '',
    selectedRange: null, // Index of priceRanges
});

const priceRanges = [
    { label: '< 5.000', min: 0, max: 4999 },
    { label: '5.000 - 15.000', min: 5000, max: 15000 },
    { label: '20.000 - 40.000', min: 20000, max: 40000 },
    { label: '> 40.000', min: 40001, max: null },
];

// Format Price
const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

// Toggle Filter Modal
const toggleFilter = () => {
    showFilter.value = !showFilter.value;
};

// Reset Filter
const resetFilter = () => {
    filterOptions.value.minPrice = '';
    filterOptions.value.maxPrice = '';
    filterOptions.value.selectedRange = null;
    showFilter.value = false;
};

const applyFilter = () => {
    showFilter.value = false;
};

// Compute filtered services
const filteredServices = computed(() => {
    let result = props.services;

    // Filter by Category
    if (activeCategory.value !== 'Semua') {
        result = result.filter(s => s.category === activeCategory.value);
    }

    // Filter by Search Query
    if (searchQuery.value.trim() !== '') {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(s => 
            s.name.toLowerCase().includes(query) || 
            (s.description && s.description.toLowerCase().includes(query))
        );
    }

    // Filter by Price Range (Predefined)
    if (filterOptions.value.selectedRange !== null) {
        const range = priceRanges[filterOptions.value.selectedRange];
        if (range.min !== null) {
            result = result.filter(s => s.price >= range.min);
        }
        if (range.max !== null) {
            result = result.filter(s => s.price <= range.max);
        }
    } else {
        // Fallback to manual if still exists (optional, but keep for compatibility)
        if (filterOptions.value.minPrice !== '' && filterOptions.value.minPrice !== null) {
            result = result.filter(s => s.price >= Number(filterOptions.value.minPrice));
        }
        if (filterOptions.value.maxPrice !== '' && filterOptions.value.maxPrice !== null) {
            result = result.filter(s => s.price <= Number(filterOptions.value.maxPrice));
        }
    }

    return result;
});

// Get Badge Info (Popular, Fast, etc based on data)
const getBadge = (service) => {
    if (service.orders_count > 10) return { text: 'POPULER', color: 'text-yellow-600', bg: 'bg-yellow-100', icon: 'fas fa-star' };
    if (service.category === 'Perawatan Khusus') return { text: 'PREMIUM', color: 'text-purple-600', bg: 'bg-purple-100', icon: 'fas fa-gem' };
    if (service.name.toLowerCase().includes('express')) return { text: '15 - 24 JAM', color: 'text-orange-600', bg: 'bg-orange-100', icon: 'fas fa-clock' };
    return { text: '1 - 2 HARI', color: 'text-red-600', bg: 'bg-red-50', icon: 'fas fa-clock' };
};

const selectService = (id) => {
    router.visit(route('pelanggan.pesan', { service: id }));
};
</script>

<template>
    <Head title="Daftar Layanan" />

    <AppLayout>
        <!-- Red Header Section (mimicking the mobile app look under the navbar) -->
        <div class="bg-[#E30613] pt-20 lg:pt-28 pb-10 relative overflow-hidden">
            <div class="absolute inset-0 opacity-20 pointer-events-none z-0">
                <div class="absolute top-10 left-10 w-12 h-12 bg-white rounded-full opacity-50"></div>
                <div class="absolute top-20 right-1/4 w-8 h-8 bg-white rounded-full opacity-50"></div>
                <div class="absolute bottom-10 right-10 w-24 h-24 bg-white rounded-full opacity-30"></div>
            </div>

            <div class="relative z-10 max-w-2xl mx-auto px-6 text-center text-white">
                <h1 class="text-3xl font-bold mb-2">Layanan Kami</h1>
                <p class="text-sm opacity-90">Pilih layanan terbaik sesuai kebutuhan Anda</p>
            </div>
            
            <!-- Curved bottom edge to match design -->
            <div class="absolute bottom-0 left-0 right-0 z-10 translate-y-px">
                <svg viewBox="0 0 1440 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-8 sm:h-12 lg:h-16 preserve-3d" preserveAspectRatio="none">
                    <!-- Thicker Yellow accent curve -->
                    <path d="M0,35 C320,85 1120,85 1440,35 L1440,100 L0,100 Z" fill="#FFD700"></path>
                    <!-- Main content background curve -->
                    <path d="M0,50 C320,100 1120,100 1440,50 L1440,100 L0,100 Z" fill="#f9fafb"></path>
                </svg>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-gray-50 min-h-screen pb-24">
            <div class="max-w-3xl mx-auto px-4 mt-6 relative z-20">
                
                <!-- Search & Filter Bar -->
                <div class="flex gap-3 mb-6">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Cari layanan laundry..." 
                            class="w-full pl-11 pr-4 py-3.5 bg-white border border-gray-200 rounded-2xl text-sm focus:ring-[#E30613] focus:border-[#E30613] shadow-sm transition-all"
                        >
                    </div>
                    <button 
                        @click="toggleFilter"
                        class="w-12 h-[50px] shrink-0 bg-white border border-gray-200 rounded-2xl flex items-center justify-center text-[#E30613] shadow-sm hover:bg-red-50 transition-colors active:scale-95"
                    >
                        <i class="fas fa-sliders-h"></i>
                    </button>
                </div>

                <!-- Categories Horizontal Scroll -->
                <div class="flex overflow-x-auto gap-2.5 pb-4 scrollbar-hide snap-x relative">
                    <!-- Red shadow for right overflow -->
                    <div class="absolute right-0 top-0 bottom-4 w-8 bg-gradient-to-l from-gray-50 to-transparent pointer-events-none"></div>
                    
                    <button 
                        @click="activeCategory = 'Semua'"
                        :class="[
                            'px-5 py-2.5 rounded-full text-xs font-bold whitespace-nowrap snap-start transition-all shadow-sm flex items-center gap-2 border',
                            activeCategory === 'Semua' 
                                ? 'bg-[#E30613] text-white border-[#E30613]' 
                                : 'bg-white text-gray-600 border-gray-200 hover:border-red-200'
                        ]"
                    >
                        Semua
                    </button>
                    <button 
                        v-for="cat in categories" 
                        :key="cat"
                        @click="activeCategory = cat"
                        :class="[
                            'px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap snap-start transition-all shadow-sm flex items-center gap-2 border',
                            activeCategory === cat 
                                ? 'bg-[#E30613] text-white border-[#E30613]' 
                                : 'bg-white text-gray-600 border-gray-200 hover:border-red-200'
                        ]"
                    >
                        <!-- Dynamic icons based on category -->
                        <i v-if="cat.toLowerCase().includes('kering')" class="fas fa-wind text-gray-400"></i>
                        <i v-else-if="cat.toLowerCase().includes('setrika')" class="fas fa-stroopwafel text-gray-400"></i>
                        <i v-else-if="cat.toLowerCase().includes('saja')" class="fas fa-tshirt text-gray-400"></i>
                        {{ cat }}
                    </button>
                </div>

                <!-- Services List -->
                <div class="mt-2 space-y-4">
                    <div v-if="filteredServices.length === 0" class="py-12 text-center bg-white rounded-3xl border border-dashed border-gray-200">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-box-open text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-gray-900 font-bold mb-1">Layanan tidak ditemukan</h3>
                        <p class="text-gray-500 text-xs">Coba sesuaikan filter atau pencarian Anda.</p>
                    </div>

                    <!-- Service Card -->
                    <div 
                        v-for="service in filteredServices" 
                        :key="service.id"
                        class="bg-white rounded-2xl p-4 flex gap-4 shadow-sm border border-gray-100 relative group hover:shadow-md transition-shadow"
                    >
                        <!-- Content -->
                        <div class="flex-1 flex flex-col justify-between min-w-0">
                            <div>
                                <!-- Badge -->
                                <div 
                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider mb-2"
                                    :class="[getBadge(service).bg, getBadge(service).color]"
                                >
                                    <i :class="getBadge(service).icon"></i>
                                    {{ getBadge(service).text }}
                                </div>
                                
                                <h3 class="font-bold text-gray-900 text-[15px] leading-tight mb-1 truncate">{{ service.name }}</h3>
                                <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed pr-2 mb-2">
                                    {{ service.description || 'Layanan profesional dengan standar kebersihan tinggi.' }}
                                </p>
                                
                                <!-- Rating -->
                                <div class="flex items-center gap-1 text-[11px] mb-3">
                                    <i class="fas fa-star text-yellow-400 text-[10px]"></i>
                                    <span class="font-bold text-gray-700">{{ service.rating || 0 }}</span>
                                    <span class="text-gray-400">({{ service.reviews_count || 0 }})</span>
                                </div>
                            </div>

                            <div class="flex items-end justify-between mt-auto">
                                <div>
                                    <span class="text-[15px] font-black text-[#E30613]">{{ formatPrice(service.price) }}</span>
                                    <span class="text-[10px] text-gray-400 font-medium ml-0.5">{{ service.unit }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Image & Button Area -->
                        <div class="flex flex-col justify-between w-28 shrink-0 relative">
                            <!-- Image Container -->
                            <div class="w-full h-20 rounded-xl overflow-hidden bg-gray-100 mb-2">
                                <img 
                                    v-if="service.image_url" 
                                    :src="service.image_url" 
                                    :alt="service.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                >
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-tshirt text-gray-300 text-2xl"></i>
                                </div>
                            </div>
                            
                            <!-- Tambah Button -->
                            <button 
                                @click="selectService(service.id)"
                                class="w-full py-1.5 px-2 bg-white border-2 border-red-100 text-[#E30613] text-[11px] font-black rounded-xl hover:bg-red-50 hover:border-red-200 active:scale-95 transition-all shadow-sm flex items-center justify-center gap-1"
                            >
                                Tambah <i class="fas fa-plus text-[9px]"></i>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Filter Modal -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div v-if="showFilter" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showFilter = false"></div>
                
                <!-- Modal Content -->
                <div class="bg-white w-full sm:w-96 rounded-t-3xl sm:rounded-3xl p-6 relative z-10 shadow-2xl">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-900">Filter Pencarian</h3>
                        <button @click="showFilter = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="space-y-5 mb-8">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-4 uppercase tracking-wide">Estimasi Harga</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button 
                                    v-for="(range, index) in priceRanges" 
                                    :key="index"
                                    @click="filterOptions.selectedRange = filterOptions.selectedRange === index ? null : index"
                                    :class="[
                                        'py-3.5 px-2 text-[11px] font-black rounded-xl border-2 transition-all flex items-center justify-center text-center',
                                        filterOptions.selectedRange === index 
                                            ? 'bg-red-50 border-[#E30613] text-[#E30613] shadow-sm' 
                                            : 'bg-white border-gray-100 text-gray-500 hover:border-red-100'
                                    ]"
                                >
                                    {{ range.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button 
                            @click="resetFilter"
                            class="flex-1 py-3 text-sm font-bold text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 active:scale-95 transition-all"
                        >
                            Reset
                        </button>
                        <button 
                            @click="applyFilter"
                            class="flex-[2] py-3 text-sm font-bold text-white bg-[#E30613] rounded-xl hover:bg-red-700 shadow-md active:scale-95 transition-all"
                        >
                            Terapkan Filter
                        </button>
                    </div>
                </div>
            </div>
        </transition>

    </AppLayout>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>