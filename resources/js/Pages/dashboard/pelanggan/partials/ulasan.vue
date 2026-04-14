<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/dashboard.vue';
import { ref } from 'vue';

const props = defineProps({
    order: {
        type: Object,
        default: () => ({
            id: 'ORD-12345',
            service: 'Setrika', // Dinamis berdasarkan layanan
            items: '5 kg',
            date: '14 Apr 2026, 08:30 AM',
            price: 35000,
            fee: 2000,
            total: 37000,
        })
    }
});

const rating = ref(0);
const comment = ref('');
const selectedCompliments = ref([]);
const isDetailExpanded = ref(false); // State untuk expand detail

const compliments = [
    { id: 1, label: 'Wangi & Bersih', icon: 'fas fa-sparkles' },
    { id: 2, label: 'Rapih Banget', icon: 'fas fa-shirt' },
    { id: 3, label: 'Selesai Cepat', icon: 'fas fa-bolt-lightning' },
    { id: 4, label: 'Kurir Ramah', icon: 'fas fa-motorcycle' },
    { id: 5, label: 'Layanan Mantap', icon: 'fas fa-thumbs-up' },
];

function toggleCompliment(id) {
    if (selectedCompliments.value.includes(id)) {
        selectedCompliments.value = selectedCompliments.value.filter(i => i !== id);
    } else {
        selectedCompliments.value.push(id);
    }
}

function formatRupiah(val) {
    return 'Rp' + new Intl.NumberFormat('id-ID').format(val);
}
</script>

<template>
    <Head title="Beri Ulasan" />

    <DashboardLayout title="Beri Ulasan">
        <div class="max-w-xl lg:max-w-4xl mx-auto pb-12 bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden flex flex-col">
            
            <div class="p-5 flex items-center justify-between border-b border-gray-50 bg-gray-50/30">
                <Link :href="route('pelanggan.aktivitas')" class="text-gray-400 hover:text-[#E30613] transition-colors">
                    <i class="fas fa-xmark text-xl"></i>
                </Link>
                <div class="text-center">
                    <h1 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Pesanan Selesai</h1>
                    <p class="text-xs font-bold text-gray-900">{{ order.id }}</p>
                </div>
                <button class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-headset text-lg"></i>
                </button>
            </div>

            <div class="flex flex-col lg:flex-row">
                
                <div class="lg:w-1/2 p-8 lg:p-12 flex flex-col items-center justify-center lg:border-r lg:border-gray-50">
                    <h2 class="text-2xl font-black text-gray-900 text-center mb-8 leading-tight">Bagaimana<br>kualitas layanan kami?</h2>

                    <div class="relative mb-6">
                        <div class="w-28 h-28 rounded-lg bg-green-50 border-4 border-white shadow-sm flex items-center justify-center">
                            <i class="fas fa-concierge-bell text-4xl text-[#22C55E]"></i>
                        </div>
                    </div>
                    
                    <p class="font-black text-xl text-gray-800 mb-8 uppercase tracking-tighter">{{ order.service }}</p>

                    <div class="flex gap-3 mb-4">
                        <button v-for="i in 5" :key="i" @click="rating = i" class="transition-transform active:scale-90 outline-none">
                            <i class="fa-star text-4xl transition-colors" 
                               :class="i <= rating ? 'fas text-yellow-400' : 'far text-gray-200'"></i>
                        </button>
                    </div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ rating > 0 ? 'Terima kasih atas penilaiannya!' : 'Ketuk bintang untuk menilai' }}</p>
                </div>

                <div class="lg:w-1/2 p-8 lg:bg-gray-50/20">
                    <div class="w-full">
                        <p class="font-black text-xs text-gray-500 uppercase tracking-widest mb-6">Apa yang membuat Anda terkesan?</p>
                        
                        <div class="grid grid-cols-3 gap-3 mb-8">
                            <button 
                                v-for="item in compliments" 
                                :key="item.id"
                                @click="toggleCompliment(item.id)"
                                class="flex flex-col items-center gap-2 group outline-none"
                            >
                                <div class="w-12 h-12 rounded border-2 flex items-center justify-center text-lg transition-all"
                                     :class="selectedCompliments.includes(item.id) ? 'bg-[#22C55E] border-[#22C55E] text-white' : 'bg-white border-gray-100 text-gray-300'">
                                    <i :class="item.icon"></i>
                                </div>
                                <span class="text-[9px] font-black text-center leading-tight uppercase tracking-tighter"
                                      :class="selectedCompliments.includes(item.id) ? 'text-[#22C55E]' : 'text-gray-400'">
                                    {{ item.label }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="w-full mb-6">
                        <textarea 
                            v-model="comment"
                            placeholder="Tuliskan saran atau pujian Anda di sini..."
                            class="w-full bg-white border border-gray-200 rounded-lg p-4 text-sm focus:ring-1 focus:ring-[#22C55E] focus:border-[#22C55E] transition-all outline-none min-h-[100px] resize-none shadow-sm"
                        ></textarea>
                    </div>

                    <div class="w-full bg-white border border-gray-100 rounded-lg overflow-hidden mb-6 shadow-sm">
                        <div @click="isDetailExpanded = !isDetailExpanded" class="p-4 flex justify-between items-center cursor-pointer hover:bg-gray-50">
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Pembayaran</span>
                                <p class="text-base font-black text-gray-900 leading-tight">{{ formatRupiah(order.total) }}</p>
                            </div>
                            <div class="flex items-center gap-2 text-[#E30613] text-[10px] font-black uppercase tracking-tighter">
                                {{ isDetailExpanded ? 'Tutup' : 'Detail' }}
                                <i class="fas transition-transform duration-300" :class="isDetailExpanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </div>
                        </div>

                        <div v-show="isDetailExpanded" class="px-4 pb-4 pt-2 border-t border-gray-50 space-y-2 bg-gray-50/50">
                            <div class="flex justify-between text-[11px] font-bold">
                                <span class="text-gray-500 uppercase">Harga Layanan</span>
                                <span class="text-gray-800">{{ formatRupiah(order.price) }}</span>
                            </div>
                            <div class="flex justify-between text-[11px] font-bold">
                                <span class="text-gray-500 uppercase">Biaya Penanganan</span>
                                <span class="text-gray-800">{{ formatRupiah(order.fee) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button 
                            class="w-full py-4 rounded font-black text-white text-xs uppercase tracking-[0.2em] shadow-lg transition-all active:scale-[0.98]"
                            :class="rating > 0 ? 'bg-[#E30613] shadow-red-100' : 'bg-gray-200 cursor-not-allowed text-gray-400'"
                            :disabled="rating === 0"
                        >
                            Kirim Ulasan Sekarang
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </DashboardLayout>
</template>