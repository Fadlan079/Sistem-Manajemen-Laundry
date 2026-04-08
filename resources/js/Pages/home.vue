<script setup>
import AppLayout from '@/Layouts/app.vue'
import HeroSection from '@/Components/sections/hero.vue'
import AboutSection from '@/Components/sections/about.vue'
import LayananSection from '@/Components/sections/layanan.vue'
import ContactSection from '@/Components/sections/contact.vue'

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <AppLayout>
        <HeroSection />
        <!-- <AboutSection /> -->
        <LayananSection />
        <ContactSection />
    </AppLayout>
</template>
