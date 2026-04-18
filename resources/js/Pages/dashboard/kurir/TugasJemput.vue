<template>
    <div class="animate-in fade-in duration-700">
        <div class="mb-8">
            <h3 class="text-2xl font-black text-slate-800 tracking-tight">Tugas Penjemputan</h3>
            <p class="text-slate-400 text-sm">Berikut adalah daftar cucian yang harus diambil hari ini.</p>
        </div>

        <div class="space-y-4">
            <div v-for="n in 3" :key="n" class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-50 flex items-center justify-between group hover:shadow-md transition-all">
                <div class="flex items-center space-x-6 min-w-0">
                    <div class="w-20 h-20 rounded-[2.2rem] bg-red-50 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 24 24" fill="none" stroke="#bb0100" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6z"></path>
                            <path d="M3 10h18"></path>
                            <circle cx="12" cy="15" r="3"></circle>
                            <path d="M6 3h12"></path>
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center space-x-2">
                            <h4 class="text-xl font-bold text-slate-800 truncate uppercase tracking-tight">Pelanggan #{{ n }}</h4>
                            <span class="bg-[#bb0100] text-white text-[8px] px-2 py-0.5 rounded-full font-black flex-shrink-0">PRIORITAS</span>
                        </div>
                        <div class="flex items-center text-slate-400 text-xs mt-1 italic">
                            <svg class="mr-1 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span class="truncate text-red-500 font-medium">Jl. Perjuangan No. {{ n }}2, Samarinda</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-3 flex-shrink-0">
                    <button class="bg-[#ffd709] text-[#453900] font-black px-8 py-4 rounded-2xl text-sm shadow-lg shadow-yellow-100 hover:scale-105 transition-transform uppercase tracking-tighter">
                        Mulai Jemput
                    </button>
                    <button class="w-12 h-12 flex items-center justify-center rounded-2xl bg-slate-50 text-slate-300 hover:bg-slate-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
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