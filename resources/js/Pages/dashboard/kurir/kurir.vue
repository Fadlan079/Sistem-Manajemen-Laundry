<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/dashboard.vue';
import TugasJemput from './TugasJemput.vue';

const user = computed(() => usePage().props.auth.user);

const activeTab = computed(() => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        return params.get('tab') || 'overview';
    }
    return 'overview';
});
</script>

<template>
    <Head :title="activeTab === 'tugas' ? 'Pickups' : 'Dashboard Kurir'" />

    <DashboardLayout :title="activeTab === 'tugas' ? 'Tugas Jemput' : 'Overview'">
        
        <div v-if="activeTab === 'tugas'">
            <TugasJemput />
        </div>

        <div v-else class="flex flex-col lg:flex-row gap-8 animate-in fade-in duration-700">
            
            <div class="flex-1">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    <div class="p-8 rounded-[2rem] text-white shadow-xl shadow-red-100 relative overflow-hidden" 
                         style="background: linear-gradient(135deg, #bb0100 0%, #ff5540 100%);">
                        <p class="text-[10px] font-bold opacity-80 uppercase tracking-widest">Total Jemputan Hari Ini</p>
                        <h2 class="text-6xl font-black italic mt-2 tracking-tighter">24</h2>
                        <div class="mt-6 flex items-center space-x-2 text-[10px] font-bold bg-white/20 px-3 py-1.5 rounded-full w-fit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                            <span>+12% vs Kemarin</span>
                        </div>
                    </div>

                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border-l-[6px]" style="border-color: #ffd709;">
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Selesai</p>
                        <h2 class="text-6xl font-black italic tracking-tighter text-slate-800 mt-2">18</h2>
                        <div class="mt-6">
                            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-[#ffd709] h-full w-[75%]"></div>
                            </div>
                            <p class="text-[9px] font-black text-slate-400 mt-2 uppercase text-center">75% Target Tercapai</p>
                        </div>
                    </div>

                    <div class="bg-white p-8 rounded-[2rem] shadow-sm">
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Tersisa</p>
                        <h2 class="text-6xl font-black italic tracking-tighter mt-2" style="color: #bb0100;">06</h2>
                        <p class="text-[10px] font-bold flex items-center mt-6 uppercase tracking-wider" style="color: #bb0100;">
                            <svg class="mr-1" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            Estimasi 2 jam lagi
                        </p>
                    </div>
                </div>

                <div class="mb-6 flex justify-between items-center px-2">
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight">Tugas Berikutnya</h3>
                    <button class="text-[#bb0100] text-xs font-bold uppercase hover:underline tracking-widest">Lihat Semua Rute</button>
                </div>

                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-50 flex items-center justify-between group hover:shadow-md transition-all">
                    <div class="flex items-center space-x-6 min-w-0">
                        <div class="w-20 h-20 rounded-[2.2rem] bg-slate-100 flex items-center justify-center flex-shrink-0">
                             <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6z"></path><path d="M3 10h18"></path><circle cx="12" cy="15" r="3"></circle><path d="M6 3h12"></path></svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center space-x-2">
                                <h4 class="text-xl font-bold text-slate-800 truncate uppercase tracking-tight">Rajawa</h4>
                                <span class="bg-[#bb0100] text-white text-[8px] px-2 py-0.5 rounded-full font-black flex-shrink-0">PRIORITAS</span>
                            </div>
                            <div class="flex items-center text-slate-400 text-xs mt-1 italic">
                                <svg class="mr-1" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <span class="truncate">Jl. baru. dekat sungai mahakam</span>
                            </div>
                        </div>
                    </div>
                    <button class="bg-[#ffd709] text-[#453900] font-black px-10 py-4 rounded-2xl text-sm shadow-lg shadow-yellow-100 hover:scale-105 transition-transform flex-shrink-0 uppercase tracking-tighter">
                        Mulai Jemput
                    </button>
                </div>
            </div>

            <div class="w-full lg:w-80 space-y-6">
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-50">
                    <h3 class="font-bold text-slate-800 mb-8 flex items-center uppercase text-[10px] tracking-widest opacity-60">
                        <svg class="mr-2" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        Log Aktivitas
                    </h3>
                    
                    <div class="space-y-8 relative">
                        <div class="absolute left-[11px] top-2 bottom-2 w-0.5 bg-slate-50"></div>
                        
                        <div class="relative flex items-start space-x-4 pl-8">
                            <div class="absolute left-0 w-6 h-6 rounded-full bg-green-500 border-4 border-white shadow-sm flex items-center justify-center z-10">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-800">Jemputan Selesai</p>
                                <p class="text-[10px] text-slate-400 truncate tracking-tight">Ibu Maya - 5.2kg</p>
                                <p class="text-[10px] text-slate-300 mt-1 uppercase font-bold tracking-tighter">13:45 WIB</p>
                            </div>
                        </div>

                        <div class="relative flex items-start space-x-4 pl-8">
                            <div class="absolute left-0 w-6 h-6 rounded-full bg-slate-300 border-4 border-white shadow-sm flex items-center justify-center z-10">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-800">Menuju Lokasi</p>
                                <p class="text-[10px] text-slate-400 truncate tracking-tight">Bpk. Joni - Jl. Bangka</p>
                                <p class="text-[10px] text-slate-300 mt-1 uppercase font-bold tracking-tighter">13:10 WIB</p>
                            </div>
                        </div>
                    </div>

                    <button class="w-full mt-10 py-3 border border-dashed border-slate-200 rounded-2xl text-[10px] font-bold text-slate-400 hover:bg-slate-50 transition-colors uppercase tracking-widest">
                        Muat Lebih Banyak
                    </button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.animate-in {
    animation: fadeIn 0.6s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
