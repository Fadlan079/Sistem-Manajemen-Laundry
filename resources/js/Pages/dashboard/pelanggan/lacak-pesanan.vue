<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';
import jsQR from 'jsqr';

const form = useForm({
    invoice: ''
});

const fileInput = ref(null);

const triggerFileInput = () => {
    fileInput.value.click();
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement("canvas");
            const context = canvas.getContext("2d");
            canvas.width = img.width;
            canvas.height = img.height;
            context.drawImage(img, 0, 0, img.width, img.height);
            const imageData = context.getImageData(0, 0, img.width, img.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height);

            if (code && code.data) {
                form.invoice = code.data;
                form.post(route('pelanggan.lacak.post'));
            } else {
                alert('QR Code tidak ditemukan pada gambar. Silakan coba gambar lain yang lebih jelas.');
            }
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
};

const searchOrder = () => {
    if (!form.invoice) return;
    form.post(route('pelanggan.lacak.post'));
};
</script>

<template>
    <Head title="Lacak Pesanan" />

    <AppLayout>
        <div class="pt-20 lg:pt-28 max-w-xl mx-auto px-4 pb-12">

            <Link :href="route('pelanggan.aktivitas')" class="flex items-center text-[11px] font-black text-gray-400 hover:text-[#E30613] uppercase tracking-widest transition-colors mb-2 group">
                <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                Kembali ke Aktivitas
            </Link>

            <p class="text-gray-500 font-medium mb-6 text-[13px] leading-relaxed pr-8">Masukkan atau scan nomor invoice untuk melacak pesanan Anda.</p>

            <div v-if="$page.props.flash?.error" class="mb-5 bg-red-50 text-red-600 px-4 py-3 rounded border border-red-100 text-[11px] font-bold tracking-wide">
                {{ $page.props.flash.error }}
            </div>

            <div class="space-y-4">

                <div class="relative group">
                    <input type="text" v-model="form.invoice" placeholder="#INV-00123" @keyup.enter="searchOrder"
                        class="w-full text-center border border-gray-200 rounded-xl py-4 text-sm font-black text-gray-800 placeholder-gray-300 shadow-sm focus:ring-[#E30613] focus:border-[#E30613] transition-all" />

                    <button @click="triggerFileInput" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 w-11 h-11 bg-red-50 hover:bg-red-100 text-[#E30613] rounded-lg flex items-center justify-center border border-red-100 transition-colors" title="Scan QR atau Foto">
                        <i class="fas fa-qrcode text-xl"></i>
                    </button>
                    <!-- Decorative edges for QR placeholder in input based on design -->
                    <div class="absolute right-[8px] top-1/2 -translate-y-1/2 w-11 h-11 pointer-events-none">
                        <span class="absolute top-1 left-1 border-t-2 border-l-2 border-[#E30613] w-2.5 h-2.5 rounded-tl-sm"></span>
                        <span class="absolute top-1 right-1 border-t-2 border-r-2 border-[#E30613] w-2.5 h-2.5 rounded-tr-sm"></span>
                        <span class="absolute bottom-1 left-1 border-b-2 border-l-2 border-[#E30613] w-2.5 h-2.5 rounded-bl-sm"></span>
                        <span class="absolute bottom-1 right-1 border-b-2 border-r-2 border-[#E30613] w-2.5 h-2.5 rounded-br-sm"></span>
                    </div>
                </div>

                <div class="mt-4">
                    <button @click="searchOrder" :disabled="form.processing" type="button" class="w-full bg-gray-900 hover:bg-black text-white py-4 rounded-xl font-bold text-[13px] tracking-wide shadow-md flex items-center justify-center transition-colors active:scale-95 disabled:opacity-50">
                        <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                        Cari
                    </button>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 mt-6 flex gap-3 text-gray-600 text-[11px] font-semibold items-start border border-gray-100">
                    <i class="fas fa-lightbulb mt-0.5 text-gray-400 text-base"></i>
                    <p class="leading-relaxed">Masukkan nomor invoice Anda lalu klik tombol Cari, atau gunakan ikon scan di bagian kanan kotak input untuk mengidentifikasi gambar QR secara langsung.</p>
                </div>
            </div>

            <!-- Hidden input for file/camera upload -->
            <input type="file" accept="image/*" capture="environment" ref="fileInput" class="hidden" @change="handleFileUpload">
        </div>
    </AppLayout>
</template>
