<script setup>
import Navbar from '@/Components/navbar.vue';
import Footer from '@/Components/footer.vue'; // Pastikan footer diimpor jika belum
</script>

<template>
  <div class="min-h-screen flex flex-col bg-bg text-text">

    <header class="bg-primary text-white">
      <Navbar :canLogin="$page.props.canLogin" :canRegister="$page.props.canRegister" />
    </header>

    <main class="flex-1">
      <slot />
    </main>

    <Footer />

  </div>
</template>
