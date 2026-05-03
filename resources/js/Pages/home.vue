<script setup>
import AppLayout from '@/Layouts/app.vue'
import HeroSection from '@/Components/sections/hero.vue'
import WilayahSection from '@/Components/sections/wilayah-layanan.vue'
import LayananSection from '@/Components/sections/layanan.vue'
import CaraKerjaSection from '@/Components/sections/cara-kerja.vue'
import ContactSection from '@/Components/sections/contact.vue'
import FaqSection from '@/Components/sections/faq.vue'
import Footer from '@/Components/footer.vue';
import UlasanSection from '@/Components/sections/ulasan.vue';


defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    serviceList: { type: Array, default: () => [] },
    banners:     { type: Array, default: () => [] },
    reviews:       { type: Array,  default: () => [] },
    averageRating: { type: Number, default: 0 },
    totalReviews:  { type: Number, default: 0 },
    ratingStats:   { type: Array,  default: () => [] },
    stats:         { type: Object, default: () => ({ totalOrders: 0, totalCustomers: 0, happyCustomerPct: 100 }) }
});
</script>

<template>
    <AppLayout>
        <!-- Repeating Watermark Background -->
        <div class="fixed inset-0 z-0 pointer-events-none select-none opacity-[0.15]"
            style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='150' height='100' xmlns='http://www.w3.org/2000/svg'%3E%3Ctext x='50%25' y='50%25' font-size='14' fill='black' font-family='sans-serif' font-weight='900' text-anchor='middle' dominant-baseline='middle' transform='rotate(-20 75 50)'%3EPROTOTYPE%3C/text%3E%3C/svg%3E&quot;); background-repeat: repeat;">
        </div>

        <!-- Responsive Prototype Ribbon -->
        <div class="fixed top-0 right-0 z-[9999] pointer-events-none select-none overflow-hidden w-24 h-24 sm:w-32 sm:h-32">
            <div class="absolute top-5 -right-8 sm:top-7 sm:-right-8 bg-[#E30613] text-white px-8 sm:px-10 py-1 sm:py-1.5 rotate-45 shadow-xl border-y border-white/20">
                <span class="text-[8px] sm:text-[10px] font-black uppercase tracking-widest text-center block w-full">
                    PROTOTYPE
                </span>
            </div>
        </div>
        <HeroSection :banners="banners" :stats="stats" :averageRating="averageRating" :totalReviews="totalReviews" />
        <!-- <AboutSection /> -->
        <LayananSection :services="serviceList" />
        <UlasanSection :reviews="reviews" :averageRating="averageRating" :totalReviews="totalReviews" :ratingStats="ratingStats" />
        <WilayahSection/>
        <CaraKerjaSection />
        <ContactSection />
        <FaqSection />
        <Footer />
    </AppLayout>
</template>
