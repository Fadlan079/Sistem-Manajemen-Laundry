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
        <div class="bg-[#E30613] pt-24 lg:pt-32 pb-24 lg:pb-32 relative overflow-hidden">
            <!-- Back Button -->
            <button 
                @click="goBack" 
                class="absolute top-[110px] lg:top-[140px] left-6 w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center transition-all z-30 backdrop-blur-sm border border-white/20 shadow-lg active:scale-95"
            >
                <i class="fas fa-arrow-left"></i>
            </button>

            <div class="absolute inset-0 opacity-20 pointer-events-none z-0">
                <div class="absolute top-10 left-10 w-12 h-12 bg-white rounded-full opacity-50"></div>
                <div class="absolute top-20 right-1/4 w-8 h-8 bg-white rounded-full opacity-50"></div>
                <div class="absolute bottom-10 right-10 w-24 h-24 bg-white rounded-full opacity-30"></div>
            </div>

            <div class="relative z-10 max-w-2xl mx-auto px-6 text-center text-white">
                <h1 class="text-3xl font-bold mb-2">Lacak Pesanan</h1>
                <p class="text-sm opacity-90">Pantau status cucian Anda secara real-time</p>
            </div>

            <!-- Curved Bottom -->
            <div class="absolute bottom-0 left-0 w-full z-10 leading-none pointer-events-none">
                <svg class="block w-full h-12 sm:h-16 lg:h-20" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="fill-[#FFE800]" d="M0,128L80,144C160,160,320,192,480,197.3C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                </svg>
                <svg class="absolute bottom-0 left-0 w-full h-8 sm:h-10 lg:h-12" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="fill-gray-50" d="M0,64L80,90.7C160,117,320,171,480,186.7C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                </svg>
            </div>
        </div>

        <div class="bg-gray-50 min-h-screen pb-24">
            <div class="max-w-md mx-auto px-4 mt-6 relative z-20">

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
