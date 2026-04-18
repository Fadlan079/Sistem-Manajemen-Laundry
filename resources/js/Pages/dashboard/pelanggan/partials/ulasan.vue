<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';
import { ref } from 'vue';

const props = defineProps({
    auth: Object,
    order: {
        type: Object,
        default: () => ({
            dbId: 0,
            invoice: '#INV-00102',
            service: 'Cuci Kiloan',
            date: 'Rabu, 17 April 2024'
        })
    }
});

const form = useForm({
    rating: 0,
    comment: ''
});

function submitReview() {
    // Ensure routing handles it (assume endpoint exists or will be added)
    // form.post(route('pelanggan.aktivitas.ulasan.post', props.order.dbId));
    alert('Fungsi kirim ulasan berhasil diremulasikan untuk ' + form.rating + ' bintang.');
}
</script>

<template>
    <Head title="Beri Ulasan" />

    <AppLayout>
        <div class="pt-20 lg:pt-28 max-w-xl mx-auto pb-32 px-4">

            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <Link :href="route('pelanggan.aktivitas')" class="flex items-center text-[11px] font-black text-gray-400 hover:text-[#E30613] uppercase tracking-widest transition-colors mb-2 group">
                    <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                    Kembali ke Aktivitas
                </Link>
            </div>

            <div class="space-y-6">
                <!-- Invoice & Badge -->
                <div class="flex flex-col gap-2">
                    <p class="font-black text-gray-900 tracking-tight">{{ order.invoice }}</p>
                    <div class="inline-flex w-max items-center gap-1.5 px-3 py-1 rounded-full bg-green-50 text-green-600 border border-green-100 text-[10px] font-bold uppercase tracking-wider">
                        <i class="fas fa-check-circle"></i>
                        Selesai
                    </div>
                </div>

                <!-- Card Body -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-[#f4f7f9] flex items-center justify-center rounded-lg border border-gray-100 px-2 py-3">
                            <i class="fas fa-shopping-basket text-2xl text-[#829ab1]"></i>
                        </div>

                        <div class="flex-1">
                            <h3 class="font-black text-gray-900 text-[15px]">{{ order.service }}</h3>
                            <p class="text-[12px] font-medium text-gray-500 mt-1">
                                {{ order.date }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Rating Section -->
                <div class="pt-6 flex flex-col items-center">
                    <h2 class="text-[17px] font-black text-gray-900 mb-6 tracking-tight">Bagaimana pengalaman Anda?</h2>

                    <div class="flex gap-2 mb-6">
                        <button v-for="i in 5" :key="i" @click="form.rating = i" type="button" class="transition-transform active:scale-95 outline-none">
                            <i class="fa-star text-[42px] transition-colors"
                               :class="i <= form.rating ? 'fas text-[#FFE800]' : 'fas text-gray-200'"></i>
                        </button>
                    </div>

                    <div class="w-full">
                        <textarea
                            v-model="form.comment"
                            placeholder="Tulis Ulasan (Opsional)"
                            class="w-full bg-white border border-gray-200 rounded-xl p-4 text-[13px] font-medium text-gray-800 placeholder-gray-400 focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] transition-all outline-none min-h-[120px] resize-none shadow-sm"
                        ></textarea>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button
                        @click="submitReview"
                        :disabled="form.rating === 0 || form.processing"
                        class="w-full py-4 rounded-xl font-bold text-white text-[15px] tracking-wide shadow-md transition-all active:scale-[0.98] disabled:opacity-50"
                        :class="form.rating > 0 && !form.processing ? 'bg-[#E30613] hover:bg-black' : 'bg-gray-300 cursor-not-allowed shadow-none'"
                    >
                        <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                        Kirim
                    </button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
