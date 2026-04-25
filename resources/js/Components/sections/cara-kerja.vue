<template>
<div class="py-24 px-6 bg-white relative overflow-hidden">
  <!-- Decorative background elements -->
  <div class="absolute top-0 right-0 w-64 h-64 bg-red-50 rounded-full blur-3xl opacity-50 -mr-32 -mt-32"></div>
  <div class="absolute bottom-0 left-0 w-64 h-64 bg-yellow-50 rounded-full blur-3xl opacity-50 -ml-32 -mb-32"></div>

  <div class="max-w-5xl mx-auto relative z-10">

    <!-- HEADER -->
    <div class="text-center mb-16">
      <p class="text-[#E30613] font-black uppercase tracking-[0.3em] text-[10px] mb-3">Panduan Penggunaan</p>
      <h2 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tight">
        Cara Pesan di Aplikasi <span class="text-primary">HiWash</span>
      </h2>
      <p class="text-gray-500 mt-4 text-sm max-w-xl mx-auto leading-relaxed">
        Ikuti 5 langkah mudah berikut untuk memesan layanan laundry profesional kami langsung dari genggaman Anda.
      </p>
    </div>

    <!-- STEPS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-8 relative">
      
      <!-- Horizontal line for desktop -->
      <div class="hidden md:block absolute top-10 left-[10%] right-[10%] h-[2px] bg-gradient-to-r from-transparent via-primary/30 to-transparent"></div>

      <!-- Vertical line for mobile -->
      <div class="md:hidden absolute left-1/2 top-0 bottom-0 w-[2px] bg-gradient-to-b from-transparent via-primary/20 to-transparent -translate-x-1/2"></div>

      <!-- STEP 1 -->
      <div class="flex flex-col items-center text-center group">
        <div class="z-10 w-16 h-16 flex items-center justify-center rounded-2xl bg-white border-2 border-gray-100 text-gray-400 group-hover:border-primary group-hover:text-primary transition-all duration-500 shadow-sm mb-4 relative">
          <span class="absolute -top-2 -right-2 w-6 h-6 bg-gray-100 group-hover:bg-primary text-[10px] font-black text-gray-400 group-hover:text-white rounded-full flex items-center justify-center border-2 border-white transition-colors">1</span>
          <i class="fas fa-tshirt text-2xl"></i>
        </div>
        <h3 class="font-black text-gray-900 text-sm uppercase tracking-tight">Pilih Layanan</h3>
        <p class="text-xs text-gray-500 mt-2 leading-relaxed">
          Pilih jenis laundry yang Anda butuhkan (Kiloan/Satuan).
        </p>
      </div>

      <!-- STEP 2 -->
      <div class="flex flex-col items-center text-center group">
        <div class="z-10 w-16 h-16 flex items-center justify-center rounded-2xl bg-white border-2 border-gray-100 text-gray-400 group-hover:border-primary group-hover:text-primary transition-all duration-500 shadow-sm mb-4 relative">
          <span class="absolute -top-2 -right-2 w-6 h-6 bg-gray-100 group-hover:bg-primary text-[10px] font-black text-gray-400 group-hover:text-white rounded-full flex items-center justify-center border-2 border-white transition-colors">2</span>
          <i class="fas fa-truck text-2xl"></i>
        </div>
        <h3 class="font-black text-gray-900 text-sm uppercase tracking-tight">Metode Kirim</h3>
        <p class="text-xs text-gray-500 mt-2 leading-relaxed">
          Pilih Antar-Jemput atau antar sendiri ke outlet kami.
        </p>
      </div>

      <!-- STEP 3 -->
      <div class="flex flex-col items-center text-center group">
        <div class="z-10 w-16 h-16 flex items-center justify-center rounded-2xl bg-white border-2 border-gray-100 text-gray-400 group-hover:border-primary group-hover:text-primary transition-all duration-500 shadow-sm mb-4 relative">
          <span class="absolute -top-2 -right-2 w-6 h-6 bg-gray-100 group-hover:bg-primary text-[10px] font-black text-gray-400 group-hover:text-white rounded-full flex items-center justify-center border-2 border-white transition-colors">3</span>
          <i class="fas fa-calendar-check text-2xl"></i>
        </div>
        <h3 class="font-black text-gray-900 text-sm uppercase tracking-tight">Jadwal & Alamat</h3>
        <p class="text-xs text-gray-500 mt-2 leading-relaxed">
          Tentukan waktu penjemputan dan lokasi alamat Anda.
        </p>
      </div>

      <!-- STEP 4 -->
      <div class="flex flex-col items-center text-center group">
        <div class="z-10 w-16 h-16 flex items-center justify-center rounded-2xl bg-white border-2 border-gray-100 text-gray-400 group-hover:border-primary group-hover:text-primary transition-all duration-500 shadow-sm mb-4 relative">
          <span class="absolute -top-2 -right-2 w-6 h-6 bg-gray-100 group-hover:bg-primary text-[10px] font-black text-gray-400 group-hover:text-white rounded-full flex items-center justify-center border-2 border-white transition-colors">4</span>
          <i class="fas fa-clipboard-list text-2xl"></i>
        </div>
        <h3 class="font-black text-gray-900 text-sm uppercase tracking-tight">Detail Laundry</h3>
        <p class="text-xs text-gray-500 mt-2 leading-relaxed">
          Masukkan estimasi berat atau jumlah item & layanan ekstra.
        </p>
      </div>

      <!-- STEP 5 -->
      <div class="flex flex-col items-center text-center group">
        <div class="z-10 w-16 h-16 flex items-center justify-center rounded-2xl bg-white border-2 border-gray-100 text-gray-400 group-hover:border-primary group-hover:text-primary transition-all duration-500 shadow-sm mb-4 relative">
          <span class="absolute -top-2 -right-2 w-6 h-6 bg-gray-100 group-hover:bg-primary text-[10px] font-black text-gray-400 group-hover:text-white rounded-full flex items-center justify-center border-2 border-white transition-colors">5</span>
          <i class="fas fa-wallet text-2xl"></i>
        </div>
        <h3 class="font-black text-gray-900 text-sm uppercase tracking-tight">Bayar & Pesan</h3>
        <p class="text-xs text-gray-500 mt-2 leading-relaxed">
          Pilih metode pembayaran dan konfirmasi pesanan Anda.
        </p>
      </div>

    </div>

    <!-- CTA -->
    <div class="mt-20 text-center">
        <a href="/login" class="inline-flex items-center gap-3 px-8 py-4 bg-primary hover:bg-black text-white rounded-2xl font-black text-sm uppercase tracking-widest transition-all shadow-xl shadow-red-500/20 active:scale-95">
            Mulai Pesan Sekarang <i class="fas fa-arrow-right"></i>
        </a>
    </div>

  </div>
</div>
</template>
