<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';

const props = defineProps({
    auth: Object,
    banners: Array,
    categories: Array,
    activeOrdersCount: Number,
    recentOrders: Array,
    discountedServices: Array,
});

const currentBanner = ref(0);

// Auto-slide banner
onMounted(() => {
    if (props.banners.length > 1) {
        setInterval(() => {
            currentBanner.value = (currentBanner.value + 1) % props.banners.length;
        }, 5000);
    }
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

// Map category to icons (using the names from the database or default icons)
const getCategoryIcon = (name) => {
    const n = name.toLowerCase();
    if (n.includes('harian')) return 'fas fa-shopping-basket';
    if (n.includes('satuan')) return 'fas fa-shirt';
    if (n.includes('bedding') || n.includes('besar')) return 'fas fa-bed';
    if (n.includes('khusus') || n.includes('perawatan')) return 'fas fa-user-tie';
    return 'fas fa-tshirt';
};

// Map category to color variants
const getCategoryColor = (index) => {
    const colors = [
        'bg-blue-50 text-blue-500',
        'bg-orange-50 text-orange-500',
        'bg-purple-50 text-purple-500',
        'bg-green-50 text-green-500'
    ];
    return colors[index % colors.length];
};

const handleCategoryClick = (categoryName) => {
    // Navigate to daftar-layanan with category filter
    router.visit(route('pelanggan.daftar-layanan'), {
        data: { category: categoryName }
    });
};

const goToCreateOrder = (serviceId) => {
    router.visit(route('pelanggan.pesan', { service: serviceId }));
};
</script>

<template>
    <Head title="Beranda" />

    <AppLayout>
        <!-- Top Search & Info Bar (Fixed-ish look) -->
        <div class="bg-[#E30613] pt-24 pb-32 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10 pointer-events-none z-0">
                <div class="absolute top-10 left-10 w-32 h-32 bg-white rounded-full blur-3xl"></div>
                <div class="absolute bottom-10 right-10 w-40 h-40 bg-white rounded-full blur-3xl"></div>
            </div>

            <div class="relative z-10 max-w-xl mx-auto px-6">
                <!-- Greeting -->
                <div class="flex items-center justify-between text-white mb-6">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">Selamat Datang,</p>
                        <h1 class="text-xl font-black tracking-tight">{{ auth.user.name.split(' ')[0] }} 👋</h1>
                    </div>
                    <div class="flex gap-3">
                        <Link :href="route('pelanggan.keranjang')" class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-xl flex items-center justify-center transition-all backdrop-blur-sm relative">
                            <i class="fas fa-shopping-cart text-sm"></i>
                            <span v-if="$page.props.cartCount > 0" class="absolute -top-1 -right-1 w-4 h-4 bg-[#FFE800] text-[#E30613] text-[8px] font-black rounded-full flex items-center justify-center border-2 border-[#E30613]">
                                {{ $page.props.cartCount }}
                            </span>
                        </Link>
                        <Link :href="route('notifications.index')" class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-xl flex items-center justify-center transition-all backdrop-blur-sm relative">
                            <i class="fas fa-bell text-sm"></i>
                            <span v-if="$page.props.unreadNotificationsCount > 0" class="absolute -top-1 -right-1 w-4 h-4 bg-[#FFE800] text-[#E30613] text-[8px] font-black rounded-full flex items-center justify-center border-2 border-[#E30613]">
                                {{ $page.props.unreadNotificationsCount }}
                            </span>
                        </Link>
                    </div>
                </div>

                <!-- Search Input Trigger -->
                <div @click="router.visit(route('pelanggan.daftar-layanan'))" class="bg-white rounded-2xl p-4 flex items-center gap-3 shadow-xl cursor-pointer group hover:bg-gray-50 transition-all">
                    <i class="fas fa-search text-gray-300 group-hover:text-[#E30613] transition-colors"></i>
                    <span class="text-sm text-gray-400 font-medium">Cari layanan laundry terbaik...</span>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 min-h-screen -mt-20 relative z-20 rounded-t-[40px] pb-32">
            <div class="max-w-xl mx-auto px-4 pt-8">
                
                <!-- 1. Banner Slideshow -->
                <div v-if="banners.length > 0" class="relative rounded-3xl overflow-hidden shadow-lg mb-8 aspect-[21/9]">
                    <div class="absolute inset-0 flex transition-transform duration-700 ease-in-out" :style="{ transform: `translateX(-${currentBanner * 100}%)` }">
                        <div v-for="banner in banners" :key="banner.id" class="min-w-full h-full relative">
                            <img :src="banner.image_url" class="w-full h-full object-cover">
                        </div>
                    </div>
                    <!-- Dots indicator -->
                    <div v-if="banners.length > 1" class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5">
                        <div v-for="(b, i) in banners" :key="b.id" 
                            class="h-1.5 rounded-full transition-all duration-300" 
                            :class="currentBanner === i ? 'w-6 bg-[#FFE800]' : 'w-1.5 bg-white/50'">
                        </div>
                    </div>
                </div>

                <!-- 2. Categories Section -->
                <div class="mb-10">
                    <div class="flex items-center justify-between mb-5 px-1">
                        <h3 class="text-[13px] font-black text-gray-900 uppercase tracking-widest">Kategori Layanan</h3>
                    </div>
                    <div class="grid grid-cols-4 gap-3">
                        <button 
                            v-for="(cat, index) in categories" 
                            :key="cat.id"
                            @click="handleCategoryClick(cat.name)"
                            class="flex flex-col items-center gap-3 group active:scale-95 transition-all"
                        >
                            <div :class="['w-14 h-14 sm:w-16 sm:h-16 rounded-2xl flex items-center justify-center text-xl sm:text-2xl transition-all shadow-sm group-hover:shadow-md border border-gray-100', getCategoryColor(index)]">
                                <i :class="getCategoryIcon(cat.name)"></i>
                            </div>
                            <span class="text-[10px] sm:text-[11px] font-bold text-gray-600 text-center uppercase tracking-tight leading-none h-6 flex items-center justify-center">
                                {{ cat.name }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- 3. Active Orders & Promo Card (Unified like image) -->
                <div class="mb-10">
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 relative overflow-hidden group">
                        <!-- Red border left accent -->
                        <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-[#E30613]"></div>

                        <div class="flex items-center justify-between relative z-10">
                            <!-- Left: Active Order Info -->
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-red-50 rounded-2xl flex items-center justify-center text-[#E30613] relative">
                                    <i class="fas fa-box-open text-2xl"></i>
                                    <div v-if="activeOrdersCount > 0" class="absolute -top-1 -right-1 w-6 h-6 bg-[#E30613] text-white text-[10px] font-black rounded-full flex items-center justify-center border-4 border-white">
                                        {{ activeOrdersCount }}
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-gray-400 text-[10px] font-black uppercase tracking-widest">Pesanan Aktif</h4>
                                    <p class="text-xl font-black text-gray-900 leading-none mt-0.5">
                                        {{ activeOrdersCount > 0 ? activeOrdersCount + ' Pesanan' : 'Belum Ada' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Right: Promo Text & Link -->
                            <div class="text-right">
                                <p class="text-[10px] font-black text-[#E30613] uppercase tracking-widest mb-1">Promo Khusus</p>
                                <p class="text-[11px] font-bold text-gray-500 max-w-[120px] ml-auto leading-tight mb-3">
                                    Diskon s/d 20% menantimu!
                                </p>
                                <button 
                                    @click="router.visit(route('pelanggan.daftar-layanan'), { data: { category: 'On sale' } })"
                                    class="bg-[#E30613] text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-lg shadow-red-500/20 active:scale-95"
                                >
                                    Gunakan Sekarang
                                </button>
                            </div>
                        </div>

                        <!-- Abstract background element -->
                        <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-gray-50 rounded-full group-hover:scale-110 transition-transform -z-0"></div>
                    </div>
                </div>

                <!-- 4. Recent Order History -->
                <div class="mb-10">
                    <div class="flex items-center justify-between mb-5 px-1">
                        <h3 class="text-[13px] font-black text-gray-900 uppercase tracking-widest">Riwayat Terakhir</h3>
                        <Link :href="route('pelanggan.aktivitas')" class="text-[10px] font-black text-[#E30613] uppercase tracking-widest flex items-center gap-1 hover:gap-2 transition-all">
                            Lihat Semua <i class="fas fa-chevron-right text-[8px]"></i>
                        </Link>
                    </div>

                    <div v-if="recentOrders.length > 0" class="space-y-4">
                        <Link 
                            v-for="order in recentOrders" 
                            :key="order.dbId"
                            :href="route('pelanggan.aktivitas.detail', order.dbId)"
                            class="bg-white rounded-2xl p-4 flex items-center gap-4 shadow-sm border border-gray-50 hover:border-red-100 transition-all active:scale-[0.98] group"
                        >
                            <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:bg-red-50 group-hover:text-[#E30613] transition-colors overflow-hidden">
                                <img v-if="order.service_image" :src="order.service_image" class="w-full h-full object-cover">
                                <i v-else class="fas fa-shopping-basket"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <h5 class="text-sm font-black text-gray-900 tracking-tight">{{ order.invoice }}</h5>
                                    <span :class="['text-[8px] font-black px-2 py-0.5 rounded-full uppercase tracking-widest border', 
                                        order.badgeColor === 'green' ? 'bg-green-50 text-green-600 border-green-100' : 
                                        order.badgeColor === 'red' ? 'bg-red-50 text-red-600 border-red-100' : 
                                        'bg-blue-50 text-blue-600 border-blue-100']">
                                        {{ order.badgeText }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ order.service }}</p>
                                    <p class="text-xs font-black text-gray-900">{{ formatPrice(order.total_price) }}</p>
                                </div>
                                <p class="text-[9px] text-gray-300 font-bold mt-1 uppercase">{{ order.date }}</p>
                            </div>
                        </Link>
                    </div>
                    <div v-else class="text-center py-10 bg-white rounded-3xl border border-dashed border-gray-200">
                        <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 mx-auto mb-3">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Belum Ada Riwayat Pesanan</p>
                    </div>
                </div>

                <!-- 5. Discounts Slider Section -->
                <div v-if="discountedServices.length > 0">
                    <div class="flex items-center justify-between mb-5 px-1">
                        <h3 class="text-[13px] font-black text-gray-900 uppercase tracking-widest">Penawaran Menarik</h3>
                    </div>

                    <div class="flex overflow-x-auto gap-4 pb-4 scrollbar-hide no-scrollbar snap-x">
                        <div 
                            v-for="service in discountedServices" 
                            :key="service.id"
                            class="min-w-[85%] sm:min-w-[300px] bg-white rounded-3xl p-5 shadow-md border border-gray-50 flex items-center gap-4 snap-start relative overflow-hidden group"
                        >
                            <!-- Image/Thumbnail -->
                            <div class="w-24 h-24 rounded-2xl overflow-hidden bg-gray-100 shrink-0">
                                <img v-if="service.image_url" :src="service.image_url" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                <div v-else class="w-full h-full flex items-center justify-center text-gray-300 text-2xl">
                                    <i class="fas fa-tag"></i>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="flex-1 py-1">
                                <div class="flex items-center gap-1.5 mb-1.5">
                                    <span class="px-2 py-0.5 bg-red-100 text-[#E30613] text-[8px] font-black rounded uppercase tracking-widest">
                                        Hemat {{ Math.round(service.discount_percentage) }}%
                                    </span>
                                </div>
                                <h4 class="text-sm font-black text-gray-900 leading-tight mb-1">{{ service.name }}</h4>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-xs font-black text-[#E30613]">{{ formatPrice(service.discounted_price) }}</span>
                                    <span class="text-[9px] text-gray-400 line-through font-bold">{{ formatPrice(service.price) }}</span>
                                </div>
                                <button 
                                    @click="goToCreateOrder(service.id)"
                                    class="w-full py-2 bg-black text-white rounded-xl text-[9px] font-black uppercase tracking-[0.1em] hover:bg-[#E30613] transition-all active:scale-95"
                                >
                                    Ambil Promo
                                </button>
                            </div>

                            <!-- Background element -->
                            <div class="absolute -right-8 -top-8 w-20 h-20 bg-[#FFE800]/10 rounded-full -z-0"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
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
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
