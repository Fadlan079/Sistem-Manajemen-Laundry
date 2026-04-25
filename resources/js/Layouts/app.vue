<script setup>
import { Head } from '@inertiajs/vue3';
import Navbar from '@/Components/navbar.vue';

defineProps({
    hideBottomNav: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
  <div class="min-h-screen flex flex-col bg-bg text-text">

    <Head title="HiWash" />

    <header>
      <Navbar :canLogin="$page.props.canLogin" :canRegister="$page.props.canRegister" :hideBottomNav="hideBottomNav" />
    </header>

    <main class="flex-1">
      <slot />
    </main>
  </div>
</template>
