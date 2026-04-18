<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    services: {
        type: Array,
        default: () => []
    }
});

const visibleCount = ref(4);

const expandedFeatures = ref([]);

const toggleFeatures = (id) => {
    const index = expandedFeatures.value.indexOf(id);
    if (index > -1) {
        expandedFeatures.value.splice(index, 1);
    } else {
        expandedFeatures.value.push(id);
    }
};

const visibleServices = computed(() => {
    return props.services.slice(0, visibleCount.value);
});

const loadMore = () => {
    visibleCount.value += 8;
};

const showLess = () => {
    visibleCount.value = 4;
    // Optional: scroll back to the top of the grid smoothly
    const section = document.getElementById('kategori');
    if (section) section.scrollIntoView({ behavior: 'smooth' });
};

const toggleFaq = (id) => {
    active.value = active.value === id ? null : id;
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
<div id="kategori" class="py-24 px-8 max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4 tracking-tight">Pilih <span class="text-primary">Layanan Terbaik</span> Untuk Pakaian Anda</h2>
                <p class="text-muted max-w-2xl mx-auto">Kami menyediakan berbagai kategori layanan profesional yang dikerjakan dengan hati dan teknologi terkini.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div v-for="(service, idx) in visibleServices" :key="service.id"
                    :class="getStyle(idx).card"
                    class="rounded-2xl p-8 hover:-translate-y-2 transition-all duration-300 flex flex-col items-center text-center group relative overflow-hidden">

                    <div v-if="service.tag" class="absolute top-4 right-4 bg-secondary text-primary font-bold text-[10px] uppercase px-3 py-1 rounded-full animate-pulse">
                        {{ service.tag }}
                    </div>

                    <div :class="getStyle(idx).iconBox" class="w-16 h-16 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i v-if="service.icon" :class="[service.icon, 'text-3xl']"></i>
                        <svg v-else class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>

                    <h3 :class="getStyle(idx).title" class="text-md font-bold mb-3">{{ service.name }}</h3>
                    <p :class="getStyle(idx).desc" class="text-xs mb-6">{{ service.description || 'Layanan cucilan profesional kami untuk Anda.' }}</p>

                    <div :class="getStyle(idx).priceBorder" class="w-full pb-6 mb-6 border-b">
                        <div :class="getStyle(idx).price" class="font-bold text-2xl mb-4">
                            {{ formatRupiah(service.price) }}<span v-if="service.unit" class="text-xs opacity-70 font-normal"> {{ service.unit }}</span>
                        </div>
                        <button @click="selectService(service)" :class="getStyle(idx).btn" class="w-full py-3 rounded-lg font-semibold transition-all">Pilih Layanan</button>
                    </div>

                    <div v-if="service.features && service.features.length > 0" class="w-full text-left">
                        <button @click="toggleFeatures(service.id)" class="flex items-center justify-between w-full text-xs font-bold text-muted hover:text-primary transition-colors py-2 uppercase tracking-wide">
                            Detail Fitur
                            <i :class="['fa-solid transition-transform duration-300', expandedFeatures.includes(service.id) ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                        </button>
                        <div v-show="expandedFeatures.includes(service.id)" class="mt-4 pb-2">
                            <ul class="text-sm text-left w-full space-y-3">
                                <li v-for="(feat, fIdx) in service.features" :key="fIdx" :class="getStyle(idx).listText" class="flex items-center gap-2">
                                    <svg :class="getStyle(idx).listIcon" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    {{ feat }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div v-if="services.length === 0" class="col-span-full py-12 text-center text-muted italic border-2 border-dashed border-border rounded-2xl">
                    Belum ada layanan yang ditawarkan.
                </div>
            </div>

            <div v-if="services.length > 4" class="mt-12 flex flex-wrap items-center justify-center gap-4">
                <button v-if="visibleCount > 4" @click="showLess" class="px-6 py-3 bg-white border-2 border-border text-muted font-bold rounded-xl hover:bg-gray-50 hover:text-gray-900 transition-all shadow-sm hover:shadow-md active:scale-95 group">
                    <i class="fa-solid fa-arrow-up mr-2 group-hover:-translate-y-1 transition-transform"></i> Tampilkan Lebih Sedikit
                </button>
                <button v-if="visibleCount < services.length" @click="loadMore" class="px-8 py-3 bg-white border-2 border-primary text-primary font-bold rounded-xl hover:bg-primary hover:text-white transition-all shadow-sm hover:shadow-md active:scale-95 group">
                    Tampilkan Lebih Banyak <i class="fa-solid fa-arrow-down ml-2 group-hover:translate-y-1 transition-transform"></i>
                </button>
            </div>
        </div>

        <div id="area" class="py-24 px-8 bg-container border-y border-border">
            <div class="max-w-7xl mx-auto flex flex-col lg:flex-row items-center gap-16">
                <div class="lg:w-1/2">
                    <div class="w-20 h-20 bg-primary/10 rounded-3xl flex items-center justify-center text-primary mb-8 transform -rotate-6">
                        <svg class="w-10 h-10" fill="none" stroke="currenAtColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">Area Layanan & <span class="text-primary">Operasional</span></h2>
                    <p class="text-muted mb-8 leading-relaxed">Kami melayani dengan sepenuh hati untuk wilayah <span class="font-bold text-gray-900">Samarinda</span> dan sekitarnya. Kenyamanan Anda adalah prioritas kami.</p>

                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="w-6 h-6 bg-success/20 text-success rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900">Wilayah Cakupan</h4>
                                <p class="text-sm text-muted">Samarinda Kota, Samarinda Ulu, Samarinda Ilir, dan sekitarnya.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-6 h-6 bg-success/20 text-success rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900">Estimasi Pickup</h4>
                                <p class="text-sm text-muted">Kurir kami akan sampai dalam waktu 30–60 menit setelah pemesanan.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-6 h-6 bg-success/20 text-success rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900">Ongkir Fleksibel</h4>
                                <p class="text-sm text-muted">Gratis biaya antar jemput untuk radius 3km dari outlet kami.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lg:w-1/2 w-full">
                    <div class="bg-white p-2 rounded-2xl shadow-xl border border-border overflow-hidden rotate-2">
                        <div class="h-80 bg-gray-100 rounded-xl relative flex items-center justify-center border-2 border-dashed border-border group cursor-help">
                            <div class="text-center px-8 group-hover:scale-105 transition-transform">
                                <svg class="w-12 h-12 text-primary/30 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                                <span class="text-muted font-medium text-sm">Visual Peta Samarinda</span>
                                <p class="text-[10px] text-muted mt-2 uppercase tracking-widest">Melayani dengan Sepenuh Hati</p>
                            </div>
                            <div class="absolute bottom-4 right-4 bg-primary text-white text-[10px] font-bold px-3 py-1 rounded-md uppercase tracking-widest shadow-lg">Outlet HiWash</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
