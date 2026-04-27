<script setup>
import { ref } from 'vue';

const active = ref(null);

const toggleFaq = (id) => {
    active.value = active.value === id ? null : id;
};
</script>

<template>
    <div id="layanan" class="font-sans text-text bg-bg overflow-x-hidden">
        <div id="faq" class="py-24 px-8 bg-white border-t border-gray-100">
            <div class="max-w-3xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4 tracking-tight">Pertanyaan <span class="text-primary">Umum</span></h2>
                    <p class="text-gray-500">Bingung? Kami punya jawabannya untuk Anda.</p>
                </div>

                <div class="w-full">
                    <!-- Item 1 -->
                    <div class="border-b border-gray-200">
                        <button @click="toggleFaq(1)" class="w-full flex justify-between items-center py-5 text-left focus:outline-none group">
                            <span class="text-lg text-gray-800 font-medium transition-colors" :class="active === 1 ? 'font-semibold text-primary' : 'group-hover:text-primary'">
                                Bagaimana jika saya tidak punya timbangan di rumah?
                            </span>
                            <svg class="h-5 w-5 text-gray-400 transition-transform duration-300" :class="active === 1 ? 'rotate-180 text-primary' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0 overflow-hidden"
                            enter-to-class="max-h-40 opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-40 opacity-100"
                            leave-to-class="max-h-0 opacity-0 overflow-hidden"
                        >
                            <div v-if="active === 1" class="pb-5 text-gray-600 leading-relaxed overflow-hidden">
                                Tenang saja! Kurir kami selalu membawa timbangan digital portabel yang akurat. Cucian Anda akan langsung ditimbang di depan Anda saat penjemputan agar beratnya transparan dan terdata otomatis di sistem.
                            </div>
                        </transition>
                    </div>

                    <!-- Item 2 -->
                    <div class="border-b border-gray-200">
                        <button @click="toggleFaq(2)" class="w-full flex justify-between items-center py-5 text-left focus:outline-none group">
                            <span class="text-lg text-gray-800 font-medium transition-colors" :class="active === 2 ? 'font-semibold text-primary' : 'group-hover:text-primary'">
                                Apakah saya bisa bayar tunai (Cash) ke kurir?
                            </span>
                            <svg class="h-5 w-5 text-gray-400 transition-transform duration-300" :class="active === 2 ? 'rotate-180 text-primary' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0 overflow-hidden"
                            enter-to-class="max-h-40 opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-40 opacity-100"
                            leave-to-class="max-h-0 opacity-0 overflow-hidden"
                        >
                            <div v-if="active === 2" class="pb-5 text-gray-600 leading-relaxed overflow-hidden">
                                Tentu bisa! Kami mendukung pembayaran tunai saat penjemputan. Kurir akan menginput jumlah yang Anda bayar ke aplikasi, dan Anda akan menerima konfirmasi pembayaran real-time melalui dashboard akun Anda.
                            </div>
                        </transition>
                    </div>

                    <!-- Item 3 -->
                    <div class="border-b border-gray-200">
                        <button @click="toggleFaq(3)" class="w-full flex justify-between items-center py-5 text-left focus:outline-none group">
                            <span class="text-lg text-gray-800 font-medium transition-colors" :class="active === 3 ? 'font-semibold text-primary' : 'group-hover:text-primary'">
                                Bagaimana cara saya tahu status cucian saya sudah sampai mana?
                            </span>
                            <svg class="h-5 w-5 text-gray-400 transition-transform duration-300" :class="active === 3 ? 'rotate-180 text-primary' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0 overflow-hidden"
                            enter-to-class="max-h-40 opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-40 opacity-100"
                            leave-to-class="max-h-0 opacity-0 overflow-hidden"
                        >
                            <div v-if="active === 3" class="pb-5 text-gray-600 leading-relaxed overflow-hidden">
                                Sistem kami mencatat setiap tahap prosesnya. Anda bisa login ke web ini dan masuk ke menu "Pesanan Saya" untuk melihat apakah cucian sedang dijemput, dicuci, disetrika, atau siap diantar kembali.
                            </div>
                        </transition>
                    </div>

                    <!-- Item 4 -->
                    <div class="border-b border-gray-200">
                        <button @click="toggleFaq(4)" class="w-full flex justify-between items-center py-5 text-left focus:outline-none group">
                            <span class="text-lg text-gray-800 font-medium transition-colors" :class="active === 4 ? 'font-semibold text-primary' : 'group-hover:text-primary'">
                                Apakah cucian saya akan dicampur dengan cucian orang lain?
                            </span>
                            <svg class="h-5 w-5 text-gray-400 transition-transform duration-300" :class="active === 4 ? 'rotate-180 text-primary' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0 overflow-hidden"
                            enter-to-class="max-h-40 opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-40 opacity-100"
                            leave-to-class="max-h-0 opacity-0 overflow-hidden"
                        >
                            <div v-if="active === 4" class="pb-5 text-gray-600 leading-relaxed overflow-hidden">
                                Tidak akan. Kami menjamin keamanan dan higienitas dengan kebijakan "Satu Pelanggan, Satu Mesin". Pakaian Anda diproses secara eksklusif dalam satu siklus cuci tanpa dicampur dengan milik pelanggan lain.
                            </div>
                        </transition>
                    </div>

                    <!-- Item 5 -->
                    <div class="border-b border-gray-200">
                        <button @click="toggleFaq(5)" class="w-full flex justify-between items-center py-5 text-left focus:outline-none group">
                            <span class="text-lg text-gray-800 font-medium transition-colors" :class="active === 5 ? 'font-semibold text-primary' : 'group-hover:text-primary'">
                                Bagaimana jika ada pakaian yang rusak atau hilang?
                            </span>
                            <svg class="h-5 w-5 text-gray-400 transition-transform duration-300" :class="active === 5 ? 'rotate-180 text-primary' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0 overflow-hidden"
                            enter-to-class="max-h-40 opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-40 opacity-100"
                            leave-to-class="max-h-0 opacity-0 overflow-hidden"
                        >
                            <div v-if="active === 5" class="pb-5 text-gray-600 leading-relaxed overflow-hidden">
                                Kami melakukan pengecekan detail saat penjemputan. Namun, jika terjadi hal yang tidak diinginkan karena kelalaian kami, kami menyediakan garansi ganti rugi sesuai syarat dan ketentuan yang tertera di sistem.
                            </div>
                        </transition>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>
