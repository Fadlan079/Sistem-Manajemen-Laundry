<script setup>
</script>

<template>
<section id="kontak" class="py-20 px-6 bg-primary text-white">
    <div class="max-w-5xl mx-auto">

        <div class="text-center mb-12">
            <p class="text-sm font-semibold text-secondary uppercase tracking-wider mb-2">
                Pusat Bantuan
            </p>
            <h2 class="text-3xl md:text-4xl font-bold mb-4">
                Ada yang bisa kami bantu?
            </h2>
            <p class="text-white/70 text-lg max-w-xl mx-auto leading-relaxed">
                Tim dukungan kami siap membantu Anda untuk menyelesaikan kendala pesanan, pembayaran, atau pertanyaan lainnya.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Outlet Dr Sutomo -->
            <a href="https://wa.me/6285186869231"
               target="_blank"
               rel="noopener noreferrer"
               class="flex flex-col items-center justify-center gap-3 p-6 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl transition-all duration-300 group">
                <div class="w-12 h-12 bg-[#25D366] rounded-full flex items-center justify-center text-white shadow-lg shadow-green-500/20 group-hover:scale-110 transition-transform">
                    <i class="fab fa-whatsapp text-2xl"></i>
                </div>
                <div class="text-center">
                    <h3 class="font-bold text-lg">Outlet Dr Sutomo</h3>
                    <p class="text-white/60 text-sm">Hubungi via WhatsApp</p>
                </div>
            </a>

            <!-- Outlet Suryanata -->
            <a href="https://wa.me/6285117629231"
               target="_blank"
               rel="noopener noreferrer"
               class="flex flex-col items-center justify-center gap-3 p-6 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl transition-all duration-300 group">
                <div class="w-12 h-12 bg-[#25D366] rounded-full flex items-center justify-center text-white shadow-lg shadow-green-500/20 group-hover:scale-110 transition-transform">
                    <i class="fab fa-whatsapp text-2xl"></i>
                </div>
                <div class="text-center">
                    <h3 class="font-bold text-lg">Outlet Suryanata</h3>
                    <p class="text-white/60 text-sm">Hubungi via WhatsApp</p>
                </div>
            </a>

            <!-- Outlet AWS -->
            <a href="https://wa.me/6285117269231"
               target="_blank"
               rel="noopener noreferrer"
               class="flex flex-col items-center justify-center gap-3 p-6 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl transition-all duration-300 group">
                <div class="w-12 h-12 bg-[#25D366] rounded-full flex items-center justify-center text-white shadow-lg shadow-green-500/20 group-hover:scale-110 transition-transform">
                    <i class="fab fa-whatsapp text-2xl"></i>
                </div>
                <div class="text-center">
                    <h3 class="font-bold text-lg">Outlet AWS</h3>
                    <p class="text-white/60 text-sm">Hubungi via WhatsApp</p>
                </div>
            </a>
        </div>

        <div class="mt-14 text-center border-t border-white/10 pt-8">
            <p class="text-white/60 text-sm mb-3">
                Ingin mencari jawaban sendiri?
            </p>
            <a href="#faq"
               class="inline-flex items-center gap-2 text-sm text-secondary hover:text-white font-medium transition-colors">
                Lihat Pertanyaan Umum (FAQ)
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>

    </div>
</section>
</template>
