<script setup>
</script>

<template>
<section id="kontak" class="py-20 px-6 bg-primary text-white">
    <div class="max-w-3xl mx-auto">

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

        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">

            <a href="https://wa.me/6281234567890"
               target="_blank"
               rel="noopener noreferrer"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-4 bg-[#25D366] hover:bg-[#20bd5a] text-white rounded-lg font-semibold transition-all duration-300 shadow-lg shadow-green-500/20">
                <i class="fab fa-whatsapp text-xl"></i>
                <span>Chat via WhatsApp</span>
            </a>

            <a href="mailto:halo@laundrykita.com"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-4 bg-transparent hover:bg-white/5 border border-white/30 text-white rounded-lg font-semibold transition-all duration-300">
                <i class="fas fa-envelope text-lg opacity-80"></i>
                <span>Kirim Email</span>
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
