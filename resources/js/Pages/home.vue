<script setup>
import AppLayout from '@/Layouts/app.vue'
import HeroSection from '@/Components/sections/hero.vue'
import AboutSection from '@/Components/sections/about.vue'
import LayananSection from '@/Components/sections/layanan.vue'
import CaraKerjaSection from '@/Components/sections/cara-kerja.vue'
import ContactSection from '@/Components/sections/contact.vue'
import FaqSection from '@/Components/sections/faq.vue'

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    serviceList: {
        type: Array,
        default: () => []
    }
});
</script>

<template>
    <AppLayout>
        <HeroSection />
        <!-- <AboutSection /> -->
        <LayananSection :services="serviceList" />
        <CaraKerjaSection />
        <ContactSection />
        <FaqSection />
    </AppLayout>
</template>
