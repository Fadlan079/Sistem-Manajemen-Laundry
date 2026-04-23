<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    services: {
        type: Array,
        default: () => []
    }
});

const currentIndex = ref(1); // Default to middle item (index 1)
const popularIndex = ref(1); // Default to middle item (index 1)
const expandedFeatures = ref([]);

const popularServicesAll = computed(() => {
    return [...props.services]
        .sort((a, b) => (b.orders_count || 0) - (a.orders_count || 0));
});

const nextSlide = () => {
    if (currentIndex.value + 1 < props.services.length) {
        currentIndex.value += 1;
    }
};

const prevSlide = () => {
    if (currentIndex.value > 0) {
        currentIndex.value -= 1;
    }
};

const nextPopular = () => {
    if (popularIndex.value + 1 < popularServicesAll.value.length) {
        popularIndex.value += 1;
    }
};

const prevPopular = () => {
    if (popularIndex.value > 0) {
        popularIndex.value -= 1;
    }
};

const toggleFeatures = (id) => {
    if (expandedFeatures.value.includes(id)) {
        expandedFeatures.value = [];
    } else {
        expandedFeatures.value = [id];
    }
};

// Styles mapped to index to retain the beautiful original aesthetic
const getStyle = (idx) => {
    const styles = [
        // 1. Primary
        { card: 'bg-white border border-border shadow-sm hover:shadow-xl', iconBox: 'bg-primary/10 text-primary', title: 'text-gray-900', desc: 'text-muted', listIcon: 'text-success', listText: 'text-gray-900', priceBorder: 'border-border', price: 'text-primary', btn: 'bg-primary text-white border-transparent hover:brightness-110' },
        // 2. Blue
        { card: 'bg-white border border-border shadow-sm hover:shadow-xl', iconBox: 'bg-blue-100 text-blue-600', title: 'text-gray-900', desc: 'text-muted', listIcon: 'text-success', listText: 'text-gray-900', priceBorder: 'border-border', price: 'text-primary', btn: 'border-2 border-primary text-primary hover:bg-primary hover:text-white' },
        // 3. Purple
        { card: 'bg-white border border-border shadow-sm hover:shadow-xl', iconBox: 'bg-purple-100 text-purple-600', title: 'text-gray-900', desc: 'text-muted', listIcon: 'text-success', listText: 'text-gray-900', priceBorder: 'border-border', price: 'text-primary', btn: 'border-2 border-primary text-primary hover:bg-primary hover:text-white' },
        // 4. Dark/Fast
        { card: 'bg-gray-900 border border-gray-800 shadow-xl', iconBox: 'bg-secondary/10 text-secondary', title: 'text-white', desc: 'text-gray-400', listIcon: 'text-secondary', listText: 'text-gray-300', priceBorder: 'border-gray-800', price: 'text-secondary', btn: 'bg-secondary text-primary font-bold hover:brightness-110' },
    ];
    return styles[idx % styles.length];
};

const page = usePage();

function selectService(service) {
    const user = page.props.auth?.user;
    if (!user) {
        window.location.href = route('login');
        return;
    }
    window.location.href = route('pelanggan.pesan', service.id);
}

function formatRupiah(val) {
    if (!val) return 'Rp 0';
    return 'Rp' + parseFloat(val).toLocaleString('id-ID');
}
</script>

<template>
    <div id="layanan" class="font-sans text-text bg-bg overflow-x-hidden">
        <div id="kategori" class="py-12 md:py-24 max-w-7xl mx-auto overflow-hidden">
            
            <div v-if="popularServicesAll.length > 0" class="mb-16 md:mb-24 px-4 md:px-8">
                <div class="text-center mb-8 md:mb-16">
                    <span class="bg-primary/10 text-primary px-3 py-1 rounded-full text-[8px] md:text-xs font-bold uppercase tracking-widest mb-2 md:mb-4 inline-block">Populer</span>
                    <h2 class="text-xl md:text-4xl font-bold text-gray-900 mb-2 md:mb-4 tracking-tight">Layanan <span class="text-primary">Terfavorit</span></h2>
                    <p class="text-muted text-[10px] md:text-sm max-w-2xl mx-auto">Pilihan terbaik untuk hasil pencucian yang maksimal dan cepat.</p>
                </div>

                <div class="max-w-5xl mx-auto relative group/pop">
                    <div class="absolute top-1/2 -left-2 -right-2 md:-left-8 md:-right-8 flex justify-between items-center z-30 pointer-events-none -translate-y-1/2">
                        <button @click="prevPopular" 
                            :disabled="popularIndex === 0"
                            class="w-8 h-8 md:w-10 md:h-10 bg-white border border-border shadow-lg rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all focus:outline-none disabled:opacity-30 disabled:cursor-not-allowed pointer-events-auto">
                            <i class="fa-solid fa-chevron-left text-xs"></i>
                        </button>
                        <button @click="nextPopular" 
                            :disabled="popularIndex === popularServicesAll.length - 1"
                            class="w-8 h-8 md:w-10 md:h-10 bg-white border border-border shadow-lg rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all focus:outline-none disabled:opacity-30 disabled:cursor-not-allowed pointer-events-auto">
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </button>
                    </div>

                    <div class="overflow-visible md:px-4">
                        <div class="flex transition-transform duration-500 ease-out" 
                            :style="{ 
                                transform: `translateX(calc(-${popularIndex} * var(--card-width) + (100% - var(--card-width)) / 2))` 
                            }"
                            style="--card-width: 85%; @media (min-width: 768px) { --card-width: 33.333% }">
                            
                            <div v-for="(service, idx) in popularServicesAll" :key="'pop-' + service.id"
                                class="w-[85%] md:w-1/3 flex-shrink-0 px-1.5 md:px-4 py-8 transition-all duration-500">
                                
                                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden transition-all duration-300 hover:shadow-md hover:border-gray-300 flex flex-col h-full">

                                    <!-- Image -->
                                    <div class="w-full h-40 bg-gray-100 overflow-hidden">
                                        <img 
                                            v-if="service.image_url" 
                                            :src="service.image_url" 
                                            :alt="service.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                                            <i class="fas fa-box text-2xl"></i>
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="p-4 flex flex-col flex-1">

                                        <h3 class="text-sm md:text-base font-semibold text-gray-900 mb-1 line-clamp-1">
                                            {{ service.name }}
                                        </h3>

                                        <p class="text-xs md:text-sm text-gray-500 line-clamp-2 mb-3">
                                            {{ service.description || 'Layanan laundry profesional.' }}
                                        </p>

                                        <div class="mt-auto">
                                            <div class="text-base md:text-lg font-bold text-gray-900">
                                                {{ formatRupiah(service.price) }}
                                                <span v-if="service.unit" class="text-xs text-gray-400 font-medium">
                                                    /{{ service.unit }}
                                                </span>
                                            </div>

                                            <button 
                                                @click="selectService(service)"
                                                class="mt-3 w-full py-2 text-xs md:text-sm font-semibold bg-primary text-white rounded-md hover:bg-primary-hover transition"
                                            >
                                                Pilih Layanan
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative pt-12 border-t border-gray-100 px-4 md:px-8">
                <div class="text-center mb-8 md:mb-12">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-2 md:mb-4 tracking-tight">Katalog <span class="text-primary">Layanan</span></h2>
                    <p class="text-muted text-[10px] md:text-sm max-w-2xl mx-auto">Temukan berbagai pilihan layanan lainnya untuk kebutuhan Anda.</p>
                </div>

                <div class="max-w-4xl mx-auto relative group/all md:px-4">
                    <div class="absolute top-1/2 -left-2 -right-2 md:-left-8 md:-right-8 flex justify-between items-center z-30 pointer-events-none -translate-y-1/2">
                        <button @click="prevSlide" 
                            :disabled="currentIndex === 0"
                            class="w-8 h-8 md:w-10 md:h-10 bg-white border border-border shadow-lg rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all focus:outline-none disabled:opacity-30 disabled:cursor-not-allowed pointer-events-auto">
                            <i class="fa-solid fa-chevron-left text-xs"></i>
                        </button>
                        <button @click="nextSlide" 
                            :disabled="currentIndex === services.length - 1"
                            class="w-8 h-8 md:w-10 md:h-10 bg-white border border-border shadow-lg rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all focus:outline-none disabled:opacity-30 disabled:cursor-not-allowed pointer-events-auto">
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </button>
                    </div>

                    <div class="overflow-visible">
                        <div class="flex transition-transform duration-500 ease-out"
                             :style="{ 
                                transform: `translateX(calc(-${currentIndex} * var(--card-width-all) + (100% - var(--card-width-all)) / 2))` 
                             }"
                             style="--card-width-all: 85%; @media (min-width: 768px) { --card-width-all: 33.333% }">
                             
                            <div v-for="(service, idx) in services" :key="'all-' + service.id"
                                class="w-[85%] md:w-1/3 flex-shrink-0 px-1.5 md:px-4 py-6 transition-all duration-500">
                                
                                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden transition-all duration-300 hover:shadow-md hover:border-gray-300 flex flex-col h-full">

                                    <!-- Image -->
                                    <div class="w-full h-40 bg-gray-100 overflow-hidden">
                                        <img 
                                            v-if="service.image_url" 
                                            :src="service.image_url" 
                                            :alt="service.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                                            <i class="fas fa-box text-2xl"></i>
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="p-4 flex flex-col flex-1">

                                        <h3 class="text-sm md:text-base font-semibold text-gray-900 mb-1 line-clamp-1">
                                            {{ service.name }}
                                        </h3>

                                        <p class="text-xs md:text-sm text-gray-500 line-clamp-2 mb-3">
                                            {{ service.description || 'Layanan laundry profesional.' }}
                                        </p>

                                        <div class="mt-auto">
                                            <div class="text-base md:text-lg font-bold text-gray-900">
                                                {{ formatRupiah(service.price) }}
                                                <span v-if="service.unit" class="text-xs text-gray-400 font-medium">
                                                    /{{ service.unit }}
                                                </span>
                                            </div>

                                            <button 
                                                @click="selectService(service)"
                                                class="mt-3 w-full py-2 text-xs md:text-sm font-semibold bg-primary text-white rounded-md hover:bg-primary-hover transition"
                                            >
                                                Pilih Layanan
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="services.length === 0" class="py-12 text-center text-muted italic border-2 border-dashed border-border rounded-2xl mt-8">
                    Belum ada layanan yang ditawarkan.
                </div>
                
                <div class="mt-8 flex justify-center gap-1.5">
                    <button v-for="(s, i) in services" :key="i"
                        @click="currentIndex = i"
                        :class="i === currentIndex ? 'bg-primary w-5 md:w-6' : 'bg-gray-200 w-1.5 md:w-2 hover:bg-gray-300'"
                        class="h-1.5 md:h-2 rounded-full transition-all duration-300">
                    </button>
                </div>
            </div>

        </div>
    </div>
</template>
