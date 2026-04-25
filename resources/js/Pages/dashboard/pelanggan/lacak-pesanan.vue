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
        <div class="pt-24 max-w-md mx-auto px-4 pb-12">

            <!-- Back -->
            <button
                @click="goBack"
                class="text-xs text-gray-500 hover:text-gray-800 flex items-center gap-2 mb-6"
            >
                <i class="fas fa-arrow-left text-[10px]"></i>
                Kembali
            </button>

            <!-- Title -->
            <div class="mb-6">
                <h1 class="text-xl font-semibold text-gray-900">
                    Lacak Pesanan
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Masukkan nomor invoice atau scan QR
                </p>
            </div>

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
                        class="w-full border border-gray-300 focus:border-gray-900 focus:ring-0 px-4 py-3 text-sm text-gray-900 outline-none rounded-xl"
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
                    class="w-full bg-primary text-white py-3 text-sm font-medium hover:bg-primary-hover transition disabled:opacity-50 rounded-xl"
                >
                    <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                    Cari Pesanan
                </button>

            </div>

            <!-- Info -->
            <div class="mt-6 text-xs text-gray-500 border-t pt-4 leading-relaxed">
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
    </AppLayout>
</template>
