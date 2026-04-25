<script setup>
</script>

<template>
<section id="kontak" class="relative py-20 px-6 bg-primary text-white overflow-hidden">

    <div class="absolute inset-0 opacity-10 pointer-events-none z-0">
        <div class="absolute top-0 left-10 w-32 h-32 bg-white rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-10 w-40 h-40 bg-white rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 max-w-4xl mx-auto text-center">

        <p class="text-xs font-bold text-secondary uppercase tracking-widest mb-3">
            Bantuan
        </p>

        <h2 class="text-3xl font-bold mb-4">
            Butuh Bantuan?
        </h2>

        <p class="text-white/80 mb-10 max-w-xl mx-auto leading-relaxed">
            Tim kami siap membantu Anda terkait pesanan, pembayaran, atau kendala lainnya.
        </p>

        <div class="grid sm:grid-cols-2 gap-4 max-w-xl mx-auto">

            <a href="https://wa.me/6281234567890"
               target="_blank"
               class="flex items-center justify-center gap-4 px-6 py-4 rounded-2xl border border-white/20 bg-white/5 hover:bg-white/10 hover:border-white/40 transition-all duration-300 group">

                <div class="w-10 h-10 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fab fa-whatsapp text-xl"></i>
                </div>

                <div class="text-left">
                    <p class="text-sm font-semibold text-white">
                        Chat WhatsApp
                    </p>
                    <p class="text-xs text-white/60 mt-0.5">
                        Respon cepat
                    </p>
                </div>
            </a>

            <a href="mailto:halo@laundrykita.com"
               class="flex items-center justify-center gap-4 px-6 py-4 rounded-2xl border border-white/20 bg-white/5 hover:bg-white/10 hover:border-white/40 transition-all duration-300 group">

                <div class="w-10 h-10 rounded-full bg-white/10 text-white flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-envelope text-lg"></i>
                </div>

                <div class="text-left">
                    <p class="text-sm font-semibold text-white">
                        Kirim Email
                    </p>
                    <p class="text-xs text-white/60 mt-0.5">
                        Untuk pertanyaan umum
                    </p>
                </div>
            </a>

        </div>

        <div class="mt-12">
            <a href="#faq"
               class="inline-flex items-center gap-2 text-sm text-secondary font-semibold hover:text-white transition-colors">
               Lihat pertanyaan umum
               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>

    </div>
</section>
</template>
