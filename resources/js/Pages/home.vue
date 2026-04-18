<script setup>
import AppLayout from '@/Layouts/app.vue'
import HeroSection from '@/Components/sections/hero.vue'
import AboutSection from '@/Components/sections/about.vue'
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
});
</script>

<template>
    <AppLayout>
        <HeroSection :banners="banners" />
        <!-- <AboutSection /> -->
        <LayananSection :services="serviceList" />
        <UlasanSection :reviews="reviews" :averageRating="averageRating" :totalReviews="totalReviews" :ratingStats="ratingStats" />
        <CaraKerjaSection />
        <ContactSection />
        <FaqSection />
        <Footer />
    </AppLayout>
</template>
