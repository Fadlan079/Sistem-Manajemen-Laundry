<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

// ── Banner data from Inertia shared props (passed from home route) ─────
const props = defineProps({
    banners: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({ totalOrders: 0, totalCustomers: 0, happyCustomerPct: 100 })
    },
    averageRating: {
        type: Number,
        default: 0
    },
    totalReviews: {
        type: Number,
        default: 0
    }
});

// Helper to format large numbers
function formatNumber(num) {
    if (num >= 1000) {
        return (num / 1000).toFixed(num % 1000 === 0 ? 0 : 1) + 'k';
    }
    return num;
}

// Only display active banners
const activeBanners = computed(() => props.banners.filter(b => b.is_active));

// ── Slider state ──────────────────────────────────────────────────────
const currentIndex = ref(0);
let autoplayTimer = null;

function next() {
    if (activeBanners.value.length <= 1) return;
    currentIndex.value = (currentIndex.value + 1) % activeBanners.value.length;
}

function prev() {
    if (activeBanners.value.length <= 1) return;
    currentIndex.value = (currentIndex.value - 1 + activeBanners.value.length) % activeBanners.value.length;
}

function goTo(index) {
    currentIndex.value = index;
    resetAutoplay();
}

function resetAutoplay() {
    clearInterval(autoplayTimer);
    if (activeBanners.value.length > 1) {
        autoplayTimer = setInterval(next, 4000);
    }
}

onMounted(() => {
    resetAutoplay();
});

onUnmounted(() => {
    clearInterval(autoplayTimer);
});
</script>

<template>
  <div id="hero-section" class="w-full font-sans text-text bg-bg">

    <div class="relative bg-primary text-white overflow-hidden pt-28 pb-36 lg:pt-32 lg:pb-48 flex flex-col justify-center">

      <div class="absolute inset-0 opacity-20 pointer-events-none z-0">
        <div class="absolute top-10 left-10 w-12 h-12 bg-surface rounded-full opacity-50"></div>
        <div class="absolute top-40 right-1/4 w-8 h-8 bg-surface rounded-full opacity-50"></div>
        <div class="absolute bottom-20 left-1/3 w-16 h-16 bg-surface rounded-full opacity-30"></div>
      </div>

      <div class="relative z-10 flex flex-col lg:flex-row items-center max-w-screen-2xl mx-auto px-6 lg:px-8 gap-10 lg:gap-12 w-full">

        <div class="w-full lg:w-1/2 text-left">
          <h1 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-bold leading-tight mb-3 lg:mb-4 tracking-tighter">
            Laundry Beres,<br/>
            <span class="text-secondary text-shadow">Hidup Jadi Ringan</span>
          </h1>
          <p class="text-sm sm:text-base lg:text-lg mb-6 lg:mb-8 max-w-md mx-auto lg:mx-0 opacity-90 leading-relaxed">
            HiWash Laundry siap membantu Anda dengan layanan cepat, bersih, wangi, dan bisa dilacak real-time secara transparan.
          </p>

          <div class="grid grid-cols-2 lg:flex lg:flex-wrap items-center justify-start gap-3 lg:gap-4">
            <Link :href="route('pelanggan.daftar-layanan')" class="flex items-center justify-center gap-2 px-4 sm:px-6 lg:px-8 py-3 lg:py-4 bg-secondary text-primary text-xs sm:text-sm lg:text-base font-bold rounded-xl hover:brightness-110 active:scale-95 transition-all shadow-lg">
              <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
              <span class="whitespace-nowrap">Pesan Sekarang</span>
            </Link>

            <Link :href="route('pelanggan.lacak')" class="flex items-center justify-center gap-2 px-4 sm:px-6 lg:px-8 py-3 lg:py-4 border-2 border-white/30 text-xs sm:text-sm lg:text-base font-bold rounded-xl hover:bg-white/10 hover:border-white transition-all text-white">
              <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
              <span class="whitespace-nowrap">Lacak Pesanan</span>
            </Link>
          </div>
        </div>

        <div class="w-full lg:w-1/2 relative flex justify-center z-20">
          <div class="w-full max-w-[320px] sm:max-w-md lg:max-w-[480px] xl:max-w-lg aspect-[4/3] sm:aspect-video rounded-2xl md:rounded-3xl border border-white/20 shadow-2xl relative overflow-hidden group">
            <template v-if="activeBanners.length > 0">
              <div class="relative w-full h-full">
                <transition-group name="banner-fade" tag="div" class="w-full h-full">
                  <img
                    v-for="(banner, idx) in activeBanners"
                    :key="banner.id"
                    v-show="idx === currentIndex"
                    :src="banner.image_url"
                    :alt="`Banner ${idx + 1}`"
                    class="absolute inset-0 w-full h-full object-cover transition-opacity duration-700"
                  />
                </transition-group>
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent pointer-events-none"></div>
              </div>

              <template v-if="activeBanners.length > 1">
                <button @click="prev(); resetAutoplay()" class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center transition-all opacity-0 group-hover:opacity-100" aria-label="Previous">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button @click="next(); resetAutoplay()" class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center transition-all opacity-0 group-hover:opacity-100" aria-label="Next">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
              </template>
            </template>

            <template v-else>
              <div class="absolute inset-0 bg-white/10 backdrop-blur-xl flex flex-col items-center justify-center gap-2">
                <div class="absolute inset-0 bg-gradient-to-tr from-primary/20 to-transparent group-hover:scale-110 transition-transform duration-700"></div>
                <svg class="w-10 h-10 lg:w-12 lg:h-12 text-white/60 z-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M6.75 6.75h.008v.008H6.75V6.75z"/>
                </svg>
                <span class="text-white font-bold text-lg lg:text-xl drop-shadow-lg z-10">HiWash Experience</span>
                <span class="text-white/60 text-xs z-10">Banner belum tersedia</span>
              </div>
            </template>
          </div>
        </div>
      </div>

      <div class="absolute bottom-0 left-0 w-full z-10 leading-none pointer-events-none">
        <svg class="block w-full h-20 sm:h-24 md:h-28 lg:h-32" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path class="fill-secondary" d="M0,128L80,144C160,160,320,192,480,197.3C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
        </svg>
        <svg class="absolute bottom-0 left-0 w-full h-14 sm:h-16 md:h-20 lg:h-24" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path class="fill-bg" d="M0,64L80,90.7C160,117,320,171,480,186.7C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
        </svg>
      </div>
    </div>

    <div class="relative z-20 w-full bg-bg py-10 lg:py-16">
      <div class="max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-4 items-center">
            
            <!-- Rating -->
            <div class="flex flex-col items-center md:items-start text-center md:text-left group transition-all duration-300">
                <div class="flex items-center gap-1 mb-2">
                    <i v-for="i in 5" :key="i" class="fas fa-star text-sm" :class="i <= Math.round(averageRating) ? 'text-yellow-400' : 'text-gray-200'"></i>
                </div>
                <div class="flex flex-col">
                    <span class="text-2xl lg:text-3xl font-black text-gray-900 leading-tight">{{ averageRating || '0.0' }} / 5</span>
                    <span class="text-[11px] lg:text-xs text-gray-400 font-bold">({{ totalReviews || 0 }} ulasan)</span>
                </div>
            </div>

            <!-- Pesanan Selesai -->
            <div class="flex items-center gap-4 group justify-center md:justify-start border-l-0 md:border-l border-gray-100 md:pl-8">
                <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-2xl bg-red-50 flex items-center justify-center text-red-500 transition-all group-hover:scale-110 group-hover:rotate-3 shadow-sm">
                    <i class="fas fa-shopping-bag text-xl"></i>
                </div>
                <div class="flex flex-col text-left">
                    <span class="text-xl lg:text-2xl font-black text-gray-900 leading-tight">{{ formatNumber(stats.totalOrders) }}+</span>
                    <span class="text-[10px] lg:text-xs text-gray-500 font-bold uppercase tracking-wider">Pesanan Selesai</span>
                </div>
            </div>

            <!-- Pelanggan Puas -->
            <div class="flex items-center gap-4 group justify-center md:justify-start border-l-0 md:border-l border-gray-100 md:pl-8">
                <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-2xl bg-red-50 flex items-center justify-center text-red-500 transition-all group-hover:scale-110 group-hover:rotate-3 shadow-sm">
                    <i class="fas fa-smile text-xl"></i>
                </div>
                <div class="flex flex-col text-left">
                    <span class="text-xl lg:text-2xl font-black text-gray-900 leading-tight">{{ stats.happyCustomerPct }}%</span>
                    <span class="text-[10px] lg:text-xs text-gray-500 font-bold uppercase tracking-wider">Pelanggan Puas</span>
                </div>
            </div>

            <!-- Pelanggan Terdaftar -->
            <div class="flex items-center gap-4 group justify-center md:justify-start border-l-0 md:border-l border-gray-100 md:pl-8">
                <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-2xl bg-red-50 flex items-center justify-center text-red-500 transition-all group-hover:scale-110 group-hover:rotate-3 shadow-sm">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div class="flex flex-col text-left">
                    <span class="text-xl lg:text-2xl font-black text-gray-900 leading-tight">{{ formatNumber(stats.totalCustomers) }}+</span>
                    <span class="text-[10px] lg:text-xs text-gray-500 font-bold uppercase tracking-wider">Pelanggan Terdaftar</span>
                </div>
            </div>

        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.banner-fade-enter-active,
.banner-fade-leave-active {
  transition: opacity 0.6s ease;
}
.banner-fade-enter-from,
.banner-fade-leave-to {
  opacity: 0;
}
</style>
