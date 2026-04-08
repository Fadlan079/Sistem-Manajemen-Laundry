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
  <nav class="relative z-10 flex justify-between items-center px-8 py-6 max-w-screen-2xl mx-auto">
    <div class="flex items-center gap-2">
      <div class="w-10 h-10 bg-secondary rounded-full flex items-center justify-center text-primary font-bold text-xl">
        Hi
      </div>
      <span class="text-2xl font-extrabold tracking-tight">Wash<br/><span class="text-lg font-semibold leading-none">Laundry</span></span>
    </div>

    <div class="hidden lg:flex items-center gap-6 text-sm font-medium">
      <Link :href="route('home')" class="border-b-2 border-secondary pb-1">Beranda</Link>
      <a href="#layanan" class="hover:text-secondary/80 transition">Layanan</a>
      <a href="#tentang" class="hover:text-secondary/80 transition">Tentang</a>
      <a href="#kontak" class="hover:text-secondary/80 transition">Kontak</a>
      <a href="#lacak" class="hover:text-secondary/80 transition">Lacak Pesanan</a>
    </div>

    <div class="flex items-center gap-4 text-sm font-medium">
      <button aria-label="Search">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
      </button>
      <template v-if="canLogin">
        <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="px-5 py-2 rounded-md border border-white hover:bg-surface hover:text-primary transition">Dashboard</Link>
        <template v-else>
          <Link :href="route('login')" class="px-5 py-2 rounded-md border border-white hover:bg-surface hover:text-primary transition">Masuk</Link>
          <Link v-if="canRegister" :href="route('register')" class="px-5 py-2 rounded-md bg-secondary text-text hover:brightness-95 transition">Daftar</Link>
        </template>
      </template>
    </div>
  </nav>
</template>
