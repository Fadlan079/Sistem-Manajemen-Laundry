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

const goBack = () => {
    window.history.back();
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
            context.drawImage(img, 0, 0);

            const imageData = context.getImageData(0, 0, img.width, img.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height);

            if (code && code.data) {
                form.invoice = code.data;
                form.post(route('pelanggan.lacak.post'));
            } else {
                alert('QR tidak terbaca. Gunakan gambar yang lebih jelas.');
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
        <!-- Red Header Section -->
        <div class="bg-[#E30613] pt-20 lg:pt-28 pb-10 relative overflow-hidden">
            <div class="absolute inset-0 opacity-20 pointer-events-none z-0">
                <div class="absolute top-10 left-10 w-12 h-12 bg-white rounded-full opacity-50"></div>
                <div class="absolute top-20 right-1/4 w-8 h-8 bg-white rounded-full opacity-50"></div>
                <div class="absolute bottom-10 right-10 w-24 h-24 bg-white rounded-full opacity-30"></div>
            </div>

            <div class="relative z-10 max-w-2xl mx-auto px-6 text-center text-white">
                <h1 class="text-3xl font-bold mb-2">Lacak Pesanan</h1>
                <p class="text-sm opacity-90">Pantau status cucian Anda secara real-time</p>
            </div>
            
            <!-- Curved bottom edge to match design -->
            <div class="absolute bottom-0 left-0 right-0 z-10 translate-y-px">
                <svg viewBox="0 0 1440 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-8 sm:h-12 lg:h-16 preserve-3d" preserveAspectRatio="none">
                    <!-- Thicker Yellow accent curve -->
                    <path d="M0,35 C320,85 1120,85 1440,35 L1440,100 L0,100 Z" fill="#FFD700"></path>
                    <!-- Main content background curve -->
                    <path d="M0,50 C320,100 1120,100 1440,50 L1440,100 L0,100 Z" fill="#f9fafb"></path>
                </svg>
            </div>
        </div>

        <div class="bg-gray-50 min-h-screen pb-24">
            <div class="max-w-md mx-auto px-4 mt-6 relative z-20">

                <!-- Back -->
                <button
                    @click="goBack"
                    class="text-xs text-gray-500 hover:text-gray-800 flex items-center gap-2 mb-6"
                >
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    Kembali
                </button>

                <!-- Error -->
                <div
                    v-if="$page.props.flash?.error"
                    class="mb-4 border border-red-200 bg-red-50 text-red-600 px-3 py-2 text-sm rounded-xl"
                >
                    {{ $page.props.flash.error }}
                </div>

                <!-- Form -->
                <div class="space-y-3">

                    <!-- Input -->
                    <div class="relative">
                        <input
                            v-model="form.invoice"
                            type="text"
                            placeholder="INV-00123"
                            @keyup.enter="searchOrder"
                            class="w-full border border-gray-300 focus:border-gray-900 focus:ring-0 px-4 py-3 text-sm text-gray-900 outline-none rounded-xl shadow-sm"
                        />

                        <!-- QR Button -->
                        <button
                            @click="triggerFileInput"
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-900"
                        >
                            <i class="fas fa-qrcode text-lg"></i>
                        </button>
                    </div>

                    <!-- Button -->
                    <button
                        @click="searchOrder"
                        :disabled="form.processing"
                        class="w-full bg-primary text-white py-3.5 text-sm font-bold hover:bg-primary-hover transition disabled:opacity-50 rounded-xl shadow-md active:scale-95"
                    >
                        <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                        Cari Pesanan
                    </button>

                </div>

                <!-- Info -->
                <div class="mt-8 text-[11px] text-gray-400 border-t border-dashed pt-6 leading-relaxed text-center">
                    Gunakan nomor invoice dari riwayat pesanan atau scan QR untuk pencarian otomatis.
                </div>

                <!-- Hidden Input -->
                <input
                    type="file"
                    accept="image/*"
                    capture="environment"
                    ref="fileInput"
                    class="hidden"
                    @change="handleFileUpload"
                >
            </div>
        </div>
    </AppLayout>
</template>
