<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';

const user = computed(() => usePage().props.auth.user);

// Data Statistik untuk Cards
const stats = [
    { label: 'Total Pesanan', value: '1,284', trend: '+12%', icon: 'fa- box-archive', color: 'text-blue-600', bg: 'bg-blue-500/10' },
    { label: 'Total Pengguna', value: '852', trend: '+5%', icon: 'fa-users', color: 'text-emerald-600', bg: 'bg-emerald-500/10' },
    { label: 'Pesanan Hari Ini', value: '42', trend: 'Baru', icon: 'fa-clock', color: 'text-amber-600', bg: 'bg-amber-500/10' },
    { label: 'Pendapatan', value: 'Rp 4.2M', trend: '+18%', icon: 'fa-wallet', color: 'text-rose-600', bg: 'bg-rose-500/10' },
];
</script>

<template>
    <Head title="Dashboard Overview - Hi Wash Laundry" />

    <DashboardLayout title="Overview">
        <div class="space-y-10 pb-20">
            <header class="relative">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div class="space-y-4">
                        <h1 class="text-4xl md:text-5xl font-bold tracking-tighter text-text leading-none">
                            Dashboard <span class="text-muted font-medium">Status</span>
                        </h1>
                        <p class="text-muted font-medium max-w-xl">
                            Selamat datang kembali, <span class="text-text font-bold">{{ user?.name || 'Administrator' }}</span>. Pantau performa operasional laundry Anda secara real-time.
                        </p>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="(item, index) in stats" :key="index" 
                    class="bg-surface border border-border p-6 rounded-sm shadow-sm relative group hover:border-primary/50 transition-all">
                    <div class="absolute top-3 right-3 text-[8px] font-mono font-bold text-muted/50 uppercase tracking-tighter">
                        #Snapshot_{{ index + 1 }}
                    </div>
                    
                    <div class="flex items-center gap-4 mb-4">
                        <div :class="[item.bg, item.color]" class="w-10 h-10 rounded-full flex items-center justify-center text-sm transition-transform group-hover:scale-110">
                            <i :class="['fa-solid', item.icon]"></i>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted">{{ item.label }}</span>
                    </div>

                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-black text-text tracking-tighter">{{ item.value }}</h3>
                        <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">{{ item.trend }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-surface border-2 border-dashed border-border rounded-xl p-8 relative overflow-hidden">
                <div class="flex items-center justify-between mb-10 border-b border-dashed border-border pb-4">
                    <h3 class="text-xs font-bold uppercase tracking-[0.3em] text-text flex items-center gap-3">
                        <i class="fa-solid fa-chart-column text-primary"></i>
                        Tren Engagement & Order
                    </h3>
                    <div class="flex gap-2">
                        <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
                        <span class="text-[9px] font-mono text-muted uppercase tracking-widest italic">Live Data Tracking</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-8">
                    <div v-for="i in 4" :key="i" class="h-40 bg-container/40 border border-border rounded-lg p-4 flex flex-col justify-end gap-2 relative group overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-t from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="flex items-end gap-1 h-20">
                            <div class="flex-1 bg-border group-hover:bg-primary/40 transition-colors" :style="{ height: Math.random() * 100 + '%' }"></div>
                            <div class="flex-1 bg-border group-hover:bg-primary/60 transition-colors" :style="{ height: Math.random() * 100 + '%' }"></div>
                            <div class="flex-1 bg-border group-hover:bg-primary/40 transition-colors" :style="{ height: Math.random() * 100 + '%' }"></div>
                        </div>
                        <p class="text-[9px] font-bold text-muted uppercase tracking-tighter">Metric Activity {{ i }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold italic font-serif text-text border-l-4 border-primary pl-4 uppercase tracking-tighter">
                        Antrean Pesanan Terbaru
                    </h3>
                    <button class="text-[10px] font-black uppercase tracking-widest text-primary hover:underline">
                        Lihat Semua <i class="fa-solid fa-arrow-right ml-1"></i>
                    </button>
                </div>

                <div class="bg-surface border border-border rounded-sm divide-y divide-border shadow-lg">
                    <div v-if="false" class="p-12 text-center space-y-4">
                        </div>

                    <div v-for="i in 3" :key="i" class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-container/30 transition-colors group">
                        <div class="flex items-center gap-5">
                            <div class="w-12 h-12 bg-container border border-border flex flex-col items-center justify-center font-mono text-[10px] font-bold uppercase">
                                <span class="text-primary">INV</span>
                                <span>#00{{ i }}</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-text group-hover:text-primary transition-colors">Pelanggan Umum - Cuci Kilat {{ i }}</h4>
                                <p class="text-[11px] text-muted font-medium italic">Estimasi selesai: Hari ini, 18:00 WIB</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="text-right hidden sm:block">
                                <p class="text-[10px] font-bold uppercase text-muted tracking-widest">Status</p>
                                <p class="text-xs font-bold text-text">SEDANG DIPROSES</p>
                            </div>
                            <button class="w-10 h-10 border border-border flex items-center justify-center text-muted hover:bg-text hover:text-white transition-all rounded-sm">
                                <i class="fa-solid fa-arrow-right-long"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.font-serif {
    font-family: 'DM Serif Display', serif;
}
.font-mono {
    font-family: 'Space Mono', monospace;
}
</style>