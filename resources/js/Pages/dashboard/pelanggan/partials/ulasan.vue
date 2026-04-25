<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';

const props = defineProps({
    auth: Object,
    alreadyReviewed: {
        type: Boolean,
        default: false
    },
    order: {
        type: Object,
        default: () => ({
            dbId: 0,
            service_id: null,
            invoice: '#INV-00000',
            service: 'Layanan Laundry',
            date: '-'
        })
    }
});

const form = useForm({
    rating: 0,
    comment: ''
});

function submitReview() {
    if (form.rating === 0) return;
    form.post(route('pelanggan.aktivitas.ulasan.post', props.order.dbId), {
        preserveScroll: true,
    });
}

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head title="Beri Ulasan" />

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

            <div class="relative z-10 max-w-xl mx-auto px-6 text-center text-white">
                <h1 class="text-3xl font-bold mb-2">Beri Ulasan</h1>
                <p class="text-sm opacity-90">Bagikan pengalaman Anda menggunakan layanan kami</p>
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

        <div class="bg-gray-50 min-h-screen pb-32">
            <div class="max-w-xl mx-auto px-4 mt-6 relative z-20">

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
                                <p class="text-[12px] font-medium text-gray-500 mt-1">{{ order.date }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Already Reviewed State -->
                    <div v-if="alreadyReviewed" class="pt-6 flex flex-col items-center gap-4 text-center pb-20">
                        <div class="w-16 h-16 rounded-full bg-green-50 flex items-center justify-center">
                            <i class="fas fa-check-circle text-3xl text-green-500"></i>
                        </div>
                        <h2 class="text-[17px] font-black text-gray-900 tracking-tight">Ulasan Sudah Dikirim</h2>
                        <p class="text-sm text-gray-500">Anda sudah memberikan ulasan untuk pesanan ini. Terima kasih!</p>
                        <Link :href="route('pelanggan.aktivitas')"
                            class="mt-2 inline-flex items-center gap-2 bg-[#E30613] text-white px-6 py-3 rounded-xl text-sm font-bold transition-colors hover:bg-black">
                            <i class="fas fa-arrow-left"></i> Kembali ke Aktivitas
                        </Link>
                    </div>

                    <!-- Rating Form -->
                    <template v-else>
                        <div class="pt-6 flex flex-col items-center">
                            <h2 class="text-[17px] font-black text-gray-900 mb-2 tracking-tight">Bagaimana pengalaman Anda?</h2>
                            <p class="text-[12px] text-gray-400 mb-6">Rating untuk layanan <span class="font-bold text-gray-700">{{ order.service }}</span></p>

                            <div class="flex gap-2 mb-6">
                                <button v-for="i in 5" :key="i" @click="form.rating = i" type="button"
                                    class="transition-all active:scale-90 outline-none hover:scale-110">
                                    <i class="fa-star text-[42px] transition-colors"
                                       :class="i <= form.rating ? 'fas text-[#FFE800] drop-shadow-sm' : 'far text-gray-200'"></i>
                                </button>
                            </div>

                            <!-- Rating label -->
                            <div class="mb-6 h-6">
                                <span v-if="form.rating === 1" class="text-xs font-bold text-red-500 uppercase tracking-widest">Sangat Buruk</span>
                                <span v-else-if="form.rating === 2" class="text-xs font-bold text-orange-400 uppercase tracking-widest">Kurang Memuaskan</span>
                                <span v-else-if="form.rating === 3" class="text-xs font-bold text-yellow-500 uppercase tracking-widest">Cukup Baik</span>
                                <span v-else-if="form.rating === 4" class="text-xs font-bold text-blue-500 uppercase tracking-widest">Baik</span>
                                <span v-else-if="form.rating === 5" class="text-xs font-bold text-green-500 uppercase tracking-widest">Sangat Memuaskan!</span>
                            </div>

                            <div class="w-full">
                                <textarea
                                    v-model="form.comment"
                                    placeholder="Tulis Ulasan (Opsional) — ceritakan pengalaman Anda..."
                                    class="w-full bg-white border border-gray-200 rounded-xl p-4 text-[13px] font-medium text-gray-800 placeholder-gray-400 focus:ring-1 focus:ring-[#E30613] focus:border-[#E30613] transition-all outline-none min-h-[120px] resize-none shadow-sm"
                                ></textarea>
                                <div v-if="form.errors.comment" class="text-[11px] text-[#E30613] font-bold mt-1">{{ form.errors.comment }}</div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-6 pb-20">
                            <button
                                @click="submitReview"
                                :disabled="form.rating === 0 || form.processing"
                                class="w-full py-4 rounded-xl font-bold text-white text-[15px] tracking-wide shadow-md transition-all active:scale-[0.98] disabled:opacity-50"
                                :class="form.rating > 0 && !form.processing ? 'bg-[#E30613] hover:bg-black' : 'bg-gray-300 cursor-not-allowed shadow-none'"
                            >
                                <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                <span v-if="form.rating === 0">Pilih Rating Dulu</span>
                                <span v-else>Kirim Ulasan</span>
                            </button>
                            <p v-if="form.errors.rating" class="text-center text-[11px] text-[#E30613] font-bold mt-2">{{ form.errors.rating }}</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
