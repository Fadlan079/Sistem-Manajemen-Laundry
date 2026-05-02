<script setup>
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    services: {
        type: Array,
        default: () => []
    }
});

const popularIndex = ref(0);
const expandedFeatures = ref([]);
const activeTab = ref('Semua');
const categoryIndices = ref({});
const containerRefs = ref({});

const getCatIndex = (catName) => categoryIndices.value[catName] || 0;
const setCatIndex = (catName, idx) => { categoryIndices.value[catName] = idx; };

const setContainerRef = (el, name) => {
    if (el) containerRefs.value[name] = el;
};

const scrollNext = (name) => {
    const container = containerRefs.value[name];
    if (container) {
        const itemWidth = container.firstElementChild?.offsetWidth || 0;
        container.scrollBy({ left: itemWidth, behavior: 'smooth' });
    }
};

const scrollPrev = (name) => {
    const container = containerRefs.value[name];
    if (container) {
        const itemWidth = container.firstElementChild?.offsetWidth || 0;
        container.scrollBy({ left: -itemWidth, behavior: 'smooth' });
    }
};

const scrollToIdx = (name, index) => {
    const container = containerRefs.value[name];
    if (container) {
        const itemWidth = container.firstElementChild?.offsetWidth || 0;
        container.scrollTo({ left: itemWidth * index, behavior: 'smooth' });
    }
    if (name === 'popular') {
        popularIndex.value = index;
    } else {
        setCatIndex(name, index);
    }
};

const handleScroll = (e, name) => {
    const container = e.target;
    const itemWidth = container.firstElementChild?.offsetWidth || 1;
    const index = Math.round(container.scrollLeft / itemWidth);
    if (name === 'popular') {
        popularIndex.value = index;
    } else {
        setCatIndex(name, index);
    }
};

const popularServicesAll = computed(() => {
    return [...props.services]
        .sort((a, b) => {
            if ((b.rating || 0) !== (a.rating || 0)) {
                return (b.rating || 0) - (a.rating || 0);
            }
            return (b.reviews_count || 0) - (a.reviews_count || 0);
        })
        .slice(0, 3);
});

const groupedServices = computed(() => {
    const groups = {};
    props.services.forEach(service => {
        const catName = service.category || 'Lainnya';
        if (!groups[catName]) {
            groups[catName] = [];
        }
        groups[catName].push(service);
    });
    return groups;
});

const categoriesList = computed(() => {
    const cats = [{ name: 'Semua', count: props.services.length }];

    const onSaleCount = props.services.filter(s => s.is_discount_today).length;
    if (onSaleCount > 0) {
        cats.push({ name: 'On sale', count: onSaleCount });
    }

    Object.entries(groupedServices.value).forEach(([name, services]) => {
        cats.push({ name, count: services.length });
    });
    return cats;
});

const filteredServices = computed(() => {
    if (activeTab.value === 'Semua') {
        return props.services;
    }
    if (activeTab.value === 'On sale') {
        return props.services.filter(s => s.is_discount_today);
    }
    return groupedServices.value[activeTab.value] || [];
});


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
                    <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-[8px] md:text-xs font-bold uppercase tracking-widest mb-2 md:mb-4 inline-block shadow-sm">Top Rated</span>
                    <h2 class="text-xl md:text-4xl font-bold text-gray-900 mb-2 md:mb-4 tracking-tight">Layanan <span class="text-primary">Rating Tertinggi</span></h2>
                    <p class="text-muted text-[10px] md:text-sm max-w-2xl mx-auto">3 layanan dengan ulasan terbaik dari pelanggan kami.</p>
                </div>

                <div class="max-w-5xl mx-auto relative group/pop">
                    <div class="hidden absolute top-1/2 -left-8 -right-8 justify-between items-center z-30 pointer-events-none -translate-y-1/2">
                        <button @click="scrollPrev('popular')"
                            class="w-8 h-8 md:w-10 md:h-10 bg-white border border-border shadow-lg rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all focus:outline-none pointer-events-auto">
                            <i class="fa-solid fa-chevron-left text-xs"></i>
                        </button>
                        <button @click="scrollNext('popular')"
                            class="w-8 h-8 md:w-10 md:h-10 bg-white border border-border shadow-lg rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all focus:outline-none pointer-events-auto">
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </button>
                    </div>

                    <div class="overflow-visible md:px-4">
                        <div class="flex overflow-x-auto snap-x snap-mandatory scrollbar-hide scroll-smooth pb-4"
                            :ref="el => setContainerRef(el, 'popular')"
                            @scroll="(e) => handleScroll(e, 'popular')">

                            <div v-for="(service, idx) in popularServicesAll" :key="'pop-' + service.id"
                                class="w-[85%] md:w-1/3 flex-shrink-0 snap-start px-1.5 md:px-4 py-8 transition-all duration-500">

                                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden transition-all duration-300 hover:shadow-md hover:border-gray-300 flex flex-col h-full">

                                    <!-- Image -->
                                    <div class="w-full h-40 bg-gray-100 overflow-hidden relative">
                                        <!-- Rank Badge -->
                                        <div class="absolute top-3 right-3 z-20">
                                            <!-- Crown for Rank 1 -->
                                            <div v-if="idx === 0" class="absolute -top-3.5 left-1/2 -translate-x-1/2 z-30 drop-shadow-md">
                                                <i class="fas fa-crown text-yellow-500 text-base"></i>
                                            </div>
                                            <div :class="[
                                                'flex flex-col items-center justify-center w-10 h-10 rounded-xl shadow-lg border-2 backdrop-blur-sm transition-transform hover:scale-110',
                                                idx === 0 ? 'bg-yellow-400/95 border-yellow-200 text-yellow-950' :
                                                idx === 1 ? 'bg-gray-200/95 border-white text-gray-800' :
                                                'bg-orange-200/95 border-orange-100 text-orange-900'
                                            ]">
                                                <span class="text-lg font-black leading-none">{{ idx + 1 }}</span>
                                            </div>
                                        </div>

                                        <!-- Discount Badge -->
                                        <div v-if="service.is_discount_today" class="absolute top-3 left-3 z-10">
                                            <div class="bg-primary text-white px-2 py-1 rounded-md text-[10px] font-black flex items-center gap-1 shadow-sm border border-yellow-400/50">
                                                <i class="fas fa-tag"></i>
                                                DISKON {{ Math.round(service.discount_percentage) }}%
                                            </div>
                                        </div>

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

                                        <!-- Rating -->
                                        <div class="flex items-center gap-1 text-[11px] mb-2">
                                            <i class="fas fa-star text-yellow-400 text-[10px]"></i>
                                            <span class="font-bold text-gray-700">{{ service.rating || 0 }}</span>
                                            <span class="text-gray-400">({{ service.reviews_count || 0 }})</span>
                                        </div>

                                        <p class="text-xs md:text-sm text-gray-500 line-clamp-2 mb-3">
                                            {{ service.description || 'Layanan laundry profesional.' }}
                                        </p>

                                        <div class="mt-auto">
                                            <div class="flex flex-col">
                                                <div v-if="service.is_discount_today" class="flex items-center gap-1.5">
                                                    <span class="text-xs text-gray-400 line-through decoration-red-400/50">{{ formatRupiah(service.price) }}</span>
                                                    <span class="text-[10px] px-1 bg-red-50 text-red-500 font-bold rounded">-{{ Math.round(service.discount_percentage) }}%</span>
                                                </div>
                                                <div class="text-base md:text-lg font-bold text-gray-900">
                                                    {{ formatRupiah(service.discounted_price) }}
                                                    <span v-if="service.unit" class="text-xs text-gray-400 font-medium">
                                                        {{ service.unit }}
                                                    </span>
                                                </div>
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

                <!-- Filter Tabs -->
                <div class="max-w-4xl mx-auto mb-10 px-4 relative">
                    <!-- Right shadow indicator for scroll -->
                    <div class="absolute -right-1 top-0 bottom-0 w-12 bg-gradient-to-l from-bg to-transparent pointer-events-none z-10"></div>
                    <div class="flex overflow-x-auto flex-nowrap pb-2 -mx-4 px-4 md:mx-0 md:px-0 gap-2 snap-x scrollbar-hide relative">
                        <button v-for="tab in categoriesList" :key="tab.name"
                            @click="activeTab = tab.name"
                            :class="[
                                'whitespace-nowrap snap-center shrink-0 px-4 py-2 rounded-full text-[10px] md:text-xs font-bold transition-all border flex items-center gap-2',
                                activeTab === tab.name ? 'bg-primary text-white border-primary shadow-md' : 'bg-white text-gray-500 border-gray-200 hover:border-primary/50 hover:text-primary'
                            ]"
                        >
                            <span v-if="tab.name === 'Semua'" class="flex items-center gap-1.5">
                                SEMUA
                                <span class="flex items-center justify-center min-w-[18px] h-[18px] bg-secondary text-yellow-950 rounded-full text-[9px] font-black leading-none shadow-sm px-1">{{ tab.count }}</span>
                            </span>
                            <span v-else-if="tab.name === 'On sale'" class="flex items-center gap-1.5">
                                <i class="fas fa-fire text-orange-500"></i>
                                ON SALE
                                <span class="flex items-center justify-center min-w-[18px] h-[18px] bg-secondary text-yellow-950 rounded-full text-[9px] font-black leading-none shadow-sm px-1">{{ tab.count }}</span>
                            </span>
                            <span v-else class="flex items-center gap-1.5">
                                {{ tab.name.toUpperCase() }}
                                <span class="flex items-center justify-center min-w-[18px] h-[18px] bg-secondary text-yellow-950 rounded-full text-[9px] font-black leading-none shadow-sm px-1">{{ tab.count }}</span>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Tab SEMUA View -->
                <div v-if="activeTab === 'Semua'" class="max-w-6xl mx-auto md:px-4">
                    <div v-for="(categoryServices, categoryName) in groupedServices" :key="categoryName" class="mb-12 md:mb-16 last:mb-0 relative group/all">
                        <div class="mb-6 flex items-center gap-3 px-4 md:px-0">
                            <div class="h-8 w-1.5 bg-primary rounded-full"></div>
                            <h3 class="text-xl md:text-2xl font-bold text-gray-800 tracking-tight">{{ categoryName }}</h3>
                        </div>

                        <div v-if="categoryServices.length > 0" class="relative">
                            <div class="hidden md:flex absolute top-1/2 -left-8 -right-8 justify-between items-center z-30 pointer-events-none -translate-y-1/2">
                                <button @click="scrollPrev(categoryName)"
                                    class="w-8 h-8 md:w-10 md:h-10 bg-white border border-border shadow-lg rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all focus:outline-none pointer-events-auto">
                                    <i class="fa-solid fa-chevron-left text-xs"></i>
                                </button>
                                <button @click="scrollNext(categoryName)"
                                    class="w-8 h-8 md:w-10 md:h-10 bg-white border border-border shadow-lg rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all focus:outline-none pointer-events-auto">
                                    <i class="fa-solid fa-chevron-right text-xs"></i>
                                </button>
                            </div>

                            <div class="overflow-visible">
                                <div class="flex overflow-x-auto snap-x snap-mandatory scrollbar-hide scroll-smooth pb-4 pt-2"
                                    :ref="el => setContainerRef(el, categoryName)"
                                    @scroll="(e) => handleScroll(e, categoryName)">

                                    <div v-for="(service, idx) in categoryServices" :key="'all-cat-' + service.id"
                                        class="w-[85%] md:w-1/4 flex-shrink-0 snap-start px-1.5 md:px-4 transition-all duration-500">

                                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden transition-all duration-300 hover:shadow-md hover:border-gray-300 flex flex-col h-full">

                                            <!-- Image -->
                                            <div class="w-full h-40 bg-gray-100 overflow-hidden relative">
                                                <!-- Discount Badge -->
                                                <div v-if="service.is_discount_today" class="absolute top-3 left-3 z-10">
                                                    <div class="bg-primary text-white px-2 py-1 rounded-md text-[10px] font-black flex items-center gap-1 shadow-sm border border-yellow-400/50">
                                                        <i class="fas fa-tag"></i>
                                                        DISKON {{ Math.round(service.discount_percentage) }}%
                                                    </div>
                                                </div>
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

                                                <!-- Rating -->
                                                <div class="flex items-center gap-1 text-[11px] mb-2">
                                                    <i class="fas fa-star text-yellow-400 text-[10px]"></i>
                                                    <span class="font-bold text-gray-700">{{ service.rating || 0 }}</span>
                                                    <span class="text-gray-400">({{ service.reviews_count || 0 }})</span>
                                                </div>

                                                <p class="text-xs md:text-sm text-gray-500 line-clamp-2 mb-3">
                                                    {{ service.description || 'Layanan laundry profesional.' }}
                                                </p>
                                                <div class="mt-auto">
                                                    <div class="flex flex-col">
                                                        <div v-if="service.is_discount_today" class="flex items-center gap-1.5">
                                                            <span class="text-xs text-gray-400 line-through decoration-red-400/50">{{ formatRupiah(service.price) }}</span>
                                                            <span class="text-[10px] px-1 bg-red-50 text-red-500 font-bold rounded">-{{ Math.round(service.discount_percentage) }}%</span>
                                                        </div>
                                                        <div class="text-base md:text-lg font-bold text-gray-900">
                                                            {{ formatRupiah(service.discounted_price) }}
                                                            <span v-if="service.unit" class="text-xs text-gray-400 font-medium">
                                                                {{ service.unit }}
                                                            </span>
                                                        </div>
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

                            <!-- Dots Pagination -->
                            <div class="mt-4 flex justify-center gap-1.5 flex-wrap px-4">
                                <button v-for="(s, i) in categoryServices" :key="'dot-' + categoryName + '-' + i"
                                    @click="scrollToIdx(categoryName, i)"
                                    :class="[
                                        i === getCatIndex(categoryName) ? 'bg-primary w-5 md:w-6' : 'bg-gray-200 w-1.5 md:w-2 hover:bg-gray-300',
                                        i > 0 && i >= categoryServices.length - 3 ? 'md:hidden' : ''
                                    ]"
                                    class="h-1.5 md:h-2 rounded-full transition-all duration-300">
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab SPECIFIC CATEGORY View (Carousel) -->
                <div v-else class="max-w-6xl mx-auto relative group/all md:px-4">
                    <div v-if="filteredServices.length > 0">
                        <div class="hidden md:flex absolute top-1/2 -left-8 -right-8 justify-between items-center z-30 pointer-events-none -translate-y-1/2">
                            <button @click="scrollPrev(activeTab)"
                                class="w-8 h-8 md:w-10 md:h-10 bg-white border border-border shadow-lg rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all focus:outline-none pointer-events-auto">
                                <i class="fa-solid fa-chevron-left text-xs"></i>
                            </button>
                            <button @click="scrollNext(activeTab)"
                                class="w-8 h-8 md:w-10 md:h-10 bg-white border border-border shadow-lg rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all focus:outline-none pointer-events-auto">
                                <i class="fa-solid fa-chevron-right text-xs"></i>
                            </button>
                        </div>

                        <div class="overflow-visible">
                            <div class="flex overflow-x-auto snap-x snap-mandatory scrollbar-hide scroll-smooth pb-4 pt-2"
                                :ref="el => setContainerRef(el, activeTab)"
                                @scroll="(e) => handleScroll(e, activeTab)">

                                <div v-for="(service, idx) in filteredServices" :key="'cat-serv-' + service.id"
                                    class="w-[85%] md:w-1/4 flex-shrink-0 snap-start px-1.5 md:px-4 transition-all duration-500">

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

                                            <!-- Rating -->
                                            <div class="flex items-center gap-1 text-[11px] mb-2">
                                                <i class="fas fa-star text-yellow-400 text-[10px]"></i>
                                                <span class="font-bold text-gray-700">{{ service.rating || 0 }}</span>
                                                <span class="text-gray-400">({{ service.reviews_count || 0 }})</span>
                                            </div>

                                            <p class="text-xs md:text-sm text-gray-500 line-clamp-2 mb-3">
                                                {{ service.description || 'Layanan laundry profesional.' }}
                                            </p>
                                            <div class="mt-auto">
                                                <div class="flex flex-col">
                                                    <div v-if="service.is_discount_today" class="flex items-center gap-1.5">
                                                        <span class="text-xs text-gray-400 line-through decoration-red-400/50">{{ formatRupiah(service.price) }}</span>
                                                        <span class="text-[10px] px-1 bg-red-50 text-red-500 font-bold rounded">-{{ Math.round(service.discount_percentage) }}%</span>
                                                    </div>
                                                    <div class="text-base md:text-lg font-bold text-gray-900">
                                                        {{ formatRupiah(service.discounted_price) }}
                                                        <span v-if="service.unit" class="text-xs text-gray-400 font-medium">
                                                            {{ service.unit }}
                                                        </span>
                                                    </div>
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

                        <!-- Dots Pagination -->
                        <div class="mt-4 flex justify-center gap-1.5 flex-wrap px-4">
                            <button v-for="(s, i) in filteredServices" :key="'dot-' + i"
                                @click="scrollToIdx(activeTab, i)"
                                :class="[
                                    i === getCatIndex(activeTab) ? 'bg-primary w-5 md:w-6' : 'bg-gray-200 w-1.5 md:w-2 hover:bg-gray-300',
                                    i > 0 && i >= filteredServices.length - 3 ? 'md:hidden' : ''
                                ]"
                                class="h-1.5 md:h-2 rounded-full transition-all duration-300">
                            </button>
                        </div>
                    </div>

                    <div v-else class="py-16 px-4 text-center border-2 border-dashed border-gray-200 rounded-3xl mt-8 bg-gray-50/50 mx-4">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-folder-open text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-gray-900 font-bold mb-1">Belum Ada Layanan</h3>
                        <p class="text-gray-500 text-sm">Silahkan tambahkan layanan untuk kategori ini.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
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
