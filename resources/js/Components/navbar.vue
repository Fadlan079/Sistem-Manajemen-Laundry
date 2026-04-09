<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: false
    },
    canRegister: {
        type: Boolean,
        default: false
    },
});
</script>

<template>
  <nav class="fixed top-0 left-0 right-0 z-50 bg-primary/90 backdrop-blur-md border-b border-white/10">
    <div class="flex justify-between items-center px-6 py-3 max-w-screen-2xl mx-auto text-white">
      <Link :href="route('home')" class="flex items-center opacity-90 hover:opacity-100 transition">
        <img src="logo.png" alt="HiWash Logo" class="h-8 w-8 rounded-full">
      </Link>

      <div class="hidden lg:flex items-center gap-8 text-[13px] font-medium tracking-wide">
        <Link :href="route('home')" class="text-secondary transition-colors">Beranda</Link>
        <a href="#layanan" class="text-white/70 hover:text-white transition-colors">Layanan</a>
        <a href="#kontak" class="text-white/70 hover:text-white transition-colors">Kontak</a>
        <a href="#lacak" class="text-white/70 hover:text-white transition-colors">FAQ</a>
      </div>

      <div class="flex items-center gap-5 text-[13px] font-medium">
        <button aria-label="Search" class="text-white/70 hover:text-white transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </button>

        <template v-if="canLogin">
          <Link v-if="$page.props.auth.user" :href="route('dashboard')"
            class="px-4 py-1.5 rounded border border-white/20 hover:bg-white hover:text-primary transition-all duration-300">
            Dashboard
          </Link>
          <template v-else>
            <Link :href="route('login')" class="text-white/70 hover:text-white transition">Masuk</Link>
            <Link v-if="canRegister" :href="route('register')"
              class="px-4 py-1.5 rounded bg-secondary text-primary font-semibold hover:bg-secondary/90 transition-all">
              Daftar
            </Link>
          </template>
        </template>
      </div>
    </div>
  </nav>
</template>
