<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

// ── Banner data from Inertia shared props (passed from home route) ─────
const props = defineProps({
    banners: {
        type: Array,
        default: () => [],
    },
});

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
  <div id="hero-section" class="min-h-screen flex flex-col font-sans text-text bg-bg relative">
    <!-- Main Hero Body -->
    <div class="relative flex-1 bg-primary text-white overflow-hidden flex items-center pt-28 pb-32 lg:pb-48">
      <div class="absolute inset-0 opacity-20 pointer-events-none">
        <div class="absolute top-10 left-10 w-12 h-12 bg-surface rounded-full opacity-50"></div>
        <div class="absolute top-40 right-1/4 w-8 h-8 bg-surface rounded-full opacity-50"></div>
        <div class="absolute bottom-20 left-1/3 w-16 h-16 bg-surface rounded-full opacity-30"></div>
      </div>

      <!-- Compact spacing -->
      <div class="relative z-10 flex flex-col lg:flex-row items-center max-w-screen-2xl mx-auto px-6 py-4 lg:px-8 gap-8 lg:gap-12 w-full">
        <div class="w-full lg:w-1/2 text-left">
          <h1 class="text-3xl sm:text-4xl lg:text-4xl xl:text-5xl 2xl:text-6xl font-bold leading-tight mb-3 tracking-tighter">
            Laundry Beres,<br/>
            <span class="text-secondary text-shadow">Hidup Jadi Ringan</span>
          </h1>
          <p class="text-sm sm:text-base lg:text-lg mb-5 lg:mb-8 max-w-md mx-auto lg:mx-0 opacity-90 leading-relaxed">
            HiWash Laundry siap membantu Anda dengan layanan cepat, bersih, wangi, dan bisa dilacak real-time secara transparan.
          </p>
            <div class="grid grid-cols-2 lg:flex lg:flex-wrap items-center justify-start gap-3 lg:gap-4">
            <Link :href="route('pelanggan.pesan')" class="flex items-center justify-center gap-2 px-4 sm:px-6 lg:px-8 py-2.5 sm:py-3 lg:py-4 bg-secondary text-primary text-xs sm:text-sm lg:text-base font-bold rounded-xl hover:brightness-110 active:scale-95 transition-all shadow-lg">
                <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="whitespace-nowrap">Order Sekarang</span>
            </Link>

            <Link :href="route('pelanggan.lacak')" class="flex items-center justify-center gap-2 px-4 sm:px-6 lg:px-8 py-2.5 sm:py-3 lg:py-4 border-2 border-white/30 text-xs sm:text-sm lg:text-base font-bold rounded-xl hover:bg-white/10 hover:border-white transition-all text-white">
                <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span class="whitespace-nowrap">Lacak Pesanan</span>
            </Link>
            </div>
        </div>

        <!-- ── Banner Slider / Fallback ──────────────────────────────── -->
        <div class="w-full lg:w-1/2 relative flex justify-center mt-6 lg:mt-0 z-20">
          <div class="w-full max-w-[280px] sm:max-w-md lg:max-w-[480px] xl:max-w-lg aspect-[4/3] sm:aspect-video rounded-2xl md:rounded-3xl border-4 border-white/20 shadow-2xl relative overflow-hidden group">

            <!-- ═══ CASE 1: Has active banners → show slider ═══ -->
            <template v-if="activeBanners.length > 0">
              <!-- Slides -->
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

                <!-- Gradient overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent pointer-events-none"></div>
              </div>

              <!-- Navigation arrows (only when > 1) -->
              <template v-if="activeBanners.length > 1">
                <button
                  @click="prev(); resetAutoplay()"
                  class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center transition-all opacity-0 group-hover:opacity-100"
                  aria-label="Previous"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button
                  @click="next(); resetAutoplay()"
                  class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center transition-all opacity-0 group-hover:opacity-100"
                  aria-label="Next"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>

                <!-- Dots indicator -->
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 flex gap-1.5">
                  <button
                    v-for="(_, idx) in activeBanners"
                    :key="idx"
                    @click="goTo(idx)"
                    :class="[
                      'transition-all rounded-full',
                      idx === currentIndex
                        ? 'w-5 h-2 bg-white'
                        : 'w-2 h-2 bg-white/50 hover:bg-white/80',
                    ]"
                    :aria-label="`Go to slide ${idx + 1}`"
                  ></button>
                </div>

                <!-- Slide counter -->
                <div class="absolute top-3 right-3 z-20 text-[10px] font-mono font-bold text-white/80 bg-black/30 px-2 py-0.5 rounded-full backdrop-blur-sm">
                  {{ currentIndex + 1 }} / {{ activeBanners.length }}
                </div>
              </template>
            </template>

            <!-- ═══ CASE 2: No banners → fallback placeholder ═══ -->
            <template v-else>
              <div class="absolute inset-0 bg-white/10 backdrop-blur-xl flex flex-col items-center justify-center gap-3">
                <div class="absolute inset-0 bg-gradient-to-tr from-primary/20 to-transparent group-hover:scale-110 transition-transform duration-700"></div>
                <svg class="w-12 h-12 text-white/40 z-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M6.75 6.75h.008v.008H6.75V6.75z"/>
                </svg>
                <span class="text-white font-bold text-xl drop-shadow-lg z-10">HiWash Experience</span>
                <span class="text-white/60 text-xs z-10">Banner belum tersedia</span>
              </div>
            </template>

          </div>
        </div>
        <!-- ── /Banner Slider ─────────────────────────────────────── -->
      </div>

      <!-- Waves -->
      <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-none z-0 pointer-events-none">
        <svg class="absolute bottom-0 w-full h-16 lg:h-32" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path class="fill-secondary" d="M0,128L80,144C160,160,320,192,480,197.3C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
        </svg>
        <svg class="relative w-full h-12 lg:h-20" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path class="fill-bg" d="M0,64L80,90.7C160,117,320,171,480,186.7C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
        </svg>
      </div>
    </div>

    <!-- Quick Features -->
    <div class="relative z-20 max-w-7xl mx-auto px-6 lg:px-8 -mt-20 lg:-mt-28 mb-12 lg:mb-10">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white rounded-2xl shadow-xl p-4 lg:p-6 flex flex-col items-center text-center border border-border hover:-translate-y-1 transition-all">
            <div class="w-10 h-10 lg:w-12 lg:h-12 bg-primary/5 text-primary rounded-xl flex items-center justify-center mb-3">
              <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7h-3v7h.05a2.5 2.5 0 004.9 0H17a1 1 0 001-1V9l-2-2h-2z"></path></svg>
            </div>
            <h3 class="font-bold text-xs lg:text-sm text-gray-900 mb-1">Layanan Antar Jemput</h3>
            <p class="text-[10px] text-muted hidden sm:block">Kami jemput dan antar langsung ke lokasi Anda.</p>
          </div>
          <div class="bg-white rounded-2xl shadow-xl p-4 lg:p-6 flex flex-col items-center text-center border border-border hover:-translate-y-1 transition-all">
            <div class="w-10 h-10 lg:w-12 lg:h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-3">
              <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="font-bold text-xs lg:text-sm text-gray-900 mb-1">Cepat &amp; Tepat</h3>
            <p class="text-[10px] text-muted hidden sm:block">Pengerjaan kilat dengan standar kualitas tertinggi.</p>
          </div>
          <div class="bg-white rounded-2xl shadow-xl p-4 lg:p-6 flex flex-col items-center text-center border border-border hover:-translate-y-1 transition-all">
            <div class="w-10 h-10 lg:w-12 lg:h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center mb-3">
              <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            <h3 class="font-bold text-xs lg:text-sm text-gray-900 mb-1">Aman &amp; Terpercaya</h3>
            <p class="text-[10px] text-muted hidden sm:block">Barang Anda kami jaga dengan proteksi ekstra.</p>
          </div>
          <div class="bg-white rounded-2xl shadow-xl p-4 lg:p-6 flex flex-col items-center text-center border border-border hover:-translate-y-1 transition-all">
            <div class="w-10 h-10 lg:w-12 lg:h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center mb-3">
              <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
            </div>
            <h3 class="font-bold text-xs lg:text-sm text-gray-900 mb-1">Update Real-time</h3>
            <p class="text-[10px] text-muted hidden sm:block">Pantau status cucian secara langsung via WhatsApp.</p>
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
