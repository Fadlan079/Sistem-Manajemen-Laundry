<script setup>
import { computed } from 'vue';

const props = defineProps({
    reviews:       { type: Array,  default: () => [] },
    averageRating: { type: Number, default: 0 },
    totalReviews:  { type: Number, default: 0 },
    ratingStats:   { type: Array,  default: () => [] },
});

// Rounded display average
const displayAverage = computed(() => {
    return props.averageRating > 0 ? props.averageRating.toFixed(1) : '—';
});

// How many full stars to show in the summary
const fullStars = computed(() => Math.round(props.averageRating));

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    return parts.length > 1
        ? (parts[0][0] + parts[1][0]).toUpperCase()
        : name.substring(0, 2).toUpperCase();
}
</script>

<template>
    <div id="ulasan" class="bg-gray-50 py-16 px-6 font-sans">
        <div class="max-w-7xl mx-auto">

            <!-- Section Title -->
            <div class="mb-8">
                <p class="text-xs font-black text-primary uppercase tracking-[3px] mb-2">Kepuasan Pelanggan</p>
                <h2 class="text-2xl lg:text-3xl font-extrabold text-gray-950 tracking-tight">Apa Kata Mereka?</h2>
            </div>

            <!-- Stats Card -->
<div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 mb-10 lg:max-w-md lg:ml-0 mx-auto">
    <div class="flex flex-col sm:flex-row items-center gap-6">

        <div class="text-center shrink-0 sm:border-r sm:pr-6 border-gray-100">
            <h1 class="text-5xl font-black text-gray-950 leading-none">{{ displayAverage }}</h1>
            <div class="flex items-center justify-center gap-0.5 my-2">
                <template v-for="i in 5" :key="i">
                    <svg class="w-4 h-4 transition-colors"
                         :class="i <= fullStars ? 'text-secondary' : 'text-gray-200'"
                         fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </template>
            </div>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">{{ totalReviews }} penilaian</p>
        </div>

        <div class="flex-1 w-full space-y-1.5">
            <div v-if="ratingStats.length > 0" v-for="stat in ratingStats" :key="stat.stars" class="flex items-center gap-3">
                <div class="flex items-center gap-1 w-10 shrink-0">
                    <span class="text-[10px] font-black text-gray-500">{{ stat.stars }}</span>
                    <svg class="w-2.5 h-2.5 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
                <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-secondary rounded-full transition-all duration-700"
                         :style="{ width: stat.percentage }"></div>
                </div>
                <span class="text-[9px] font-bold text-gray-400 w-6 text-right">{{ stat.count }}</span>
            </div>

            <div v-else class="text-xs text-gray-400 font-medium py-2 text-center">
                Belum ada ulasan.
            </div>
        </div>
    </div>
</div>

            <!-- Reviews List -->
            <template v-if="reviews.length > 0">
                <p class="text-xs text-gray-400 mb-5 uppercase tracking-widest font-bold">Ulasan jujur dari pengguna aplikasi</p>
                <div class="flex overflow-x-auto gap-5 pb-4 -mx-6 px-6 no-scrollbar snap-x snap-mandatory">
                    <div v-for="review in reviews" :key="review.id"
                        class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 min-w-[300px] w-[320px] snap-center shrink-0 hover:shadow-md transition-shadow duration-300">

                        <!-- User Info -->
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-red-700 text-white font-bold flex items-center justify-center text-xs shadow-sm shrink-0">
                                {{ getInitials(review.name) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 leading-none truncate">{{ review.name }}</p>
                                <div class="flex items-center gap-0.5 mt-1">
                                    <template v-for="i in 5" :key="i">
                                        <svg class="w-3 h-3"
                                             :class="i <= review.rating ? 'text-secondary' : 'text-gray-200'"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Review Text -->
                        <p class="text-sm text-gray-600 leading-relaxed mb-4 line-clamp-4">
                            {{ review.comment ? `"${review.comment}"` : '(Tidak ada komentar)' }}
                        </p>

                        <!-- Footer -->
                        <div class="pt-3 border-t border-gray-50 flex items-center justify-between gap-2">
                            <span class="text-[10px] font-bold text-gray-400 bg-gray-50 px-2 py-1 rounded-lg truncate">
                                <i class="fas fa-tag mr-1 text-primary/50"></i>{{ review.service }}
                            </span>
                        </div>
                        <p class="text-[10px] text-gray-300 mt-2">{{ review.created_at }}</p>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center py-16 text-center text-gray-400">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                    <i class="fas fa-star text-2xl text-gray-300"></i>
                </div>
                <p class="font-bold text-sm">Belum Ada Ulasan</p>
                <p class="text-xs mt-1">Jadilah yang pertama memberikan ulasan!</p>
            </div>

        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.line-clamp-4 {
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
