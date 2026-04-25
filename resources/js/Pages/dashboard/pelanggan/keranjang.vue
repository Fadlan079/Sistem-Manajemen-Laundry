<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';
import Modal from '@/Components/Modal.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    cartItems: Array,
});

const selectedItems = ref([]);

const toggleSelect = (id) => {
    if (selectedItems.value.includes(id)) {
        selectedItems.value = selectedItems.value.filter(item => item !== id);
    } else {
        selectedItems.value.push(id);
    }
};

const selectAll = () => {
    if (selectedItems.value.length === props.cartItems.length) {
        selectedItems.value = [];
    } else {
        selectedItems.value = props.cartItems.map(item => item.id);
    }
};

const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const checkoutForm = useForm({
    order_ids: [],
});

const handleCheckout = () => {
    if (selectedItems.value.length === 0) return;
    
    checkoutForm.order_ids = selectedItems.value;
    checkoutForm.post(route('pelanggan.keranjang.checkout'), {
        onSuccess: () => {
            selectedItems.value = [];
        }
    });
};

const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const removeItem = (id) => {
    itemToDelete.value = id;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (itemToDelete.value) {
        router.delete(route('pelanggan.keranjang.hapus', itemToDelete.value), {
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
            }
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    itemToDelete.value = null;
};

const totalSelectedPrice = computed(() => {
    return props.cartItems
        .filter(item => selectedItems.value.includes(item.id))
        .reduce((sum, item) => sum + item.total_price, 0);
});

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head title="Keranjang Pesanan" />

    <AppLayout>
        <!-- Red Header Section -->
        <div class="bg-[#E30613] pt-28 lg:pt-36 pb-24 lg:pb-32 relative overflow-hidden">
            <!-- Back Button -->
            <button 
                @click="goBack" 
                class="absolute top-16 lg:top-24 left-4 lg:left-8 w-9 h-9 lg:w-11 lg:h-11 bg-white/20 hover:bg-white/30 text-white rounded-full flex items-center justify-center transition-all z-30 backdrop-blur-md border border-white/30 shadow-lg active:scale-95"
            >
                <i class="fas fa-arrow-left text-sm lg:text-base"></i>
            </button>

            <div class="absolute inset-0 opacity-20 pointer-events-none z-0">
                <div class="absolute top-10 left-10 w-12 h-12 bg-white rounded-full opacity-50"></div>
                <div class="absolute bottom-10 right-10 w-24 h-24 bg-white rounded-full opacity-30"></div>
            </div>

            <div class="relative z-10 max-w-4xl mx-auto px-6 text-center text-white">
                <h1 class="text-3xl font-bold mb-2">Keranjang Pesanan</h1>
                <p class="text-sm opacity-90">Kelola pesanan Anda sebelum checkout</p>
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

        <div class="bg-gray-50 min-h-[60vh] pb-32 pt-8">
            <div class="max-w-4xl mx-auto px-4">
                
                <div v-if="cartItems.length > 0" class="space-y-4">
                    <!-- Select All Header -->
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button 
                                @click="selectAll"
                                class="w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all"
                                :class="selectedItems.length === cartItems.length ? 'bg-[#E30613] border-[#E30613] text-white' : 'border-gray-200 bg-white'"
                            >
                                <i v-if="selectedItems.length === cartItems.length" class="fas fa-check text-[10px]"></i>
                            </button>
                            <span class="text-sm font-bold text-gray-700">Pilih Semua ({{ cartItems.length }})</span>
                        </div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Maksimal 3 Pesanan</p>
                    </div>

                    <!-- Cart Items List -->
                    <div v-for="item in cartItems" :key="item.id" 
                        class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex gap-4 transition-all hover:shadow-md"
                        :class="selectedItems.includes(item.id) ? 'ring-2 ring-red-100 border-red-200' : ''"
                    >
                        <!-- Checkbox -->
                        <div class="flex items-center">
                            <button 
                                @click="toggleSelect(item.id)"
                                class="w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all shrink-0"
                                :class="selectedItems.includes(item.id) ? 'bg-[#E30613] border-[#E30613] text-white' : 'border-gray-200 bg-white'"
                            >
                                <i v-if="selectedItems.includes(item.id)" class="fas fa-check text-[10px]"></i>
                            </button>
                        </div>

                        <!-- Service Image -->
                        <div class="w-20 h-20 rounded-2xl bg-gray-50 overflow-hidden shrink-0 border border-gray-100">
                            <img v-if="item.image" :src="item.image" class="w-full h-full object-cover">
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                                <i class="fas fa-shirt text-2xl"></i>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-sm font-black text-gray-900 truncate">{{ item.service_name }}</h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[10px] px-2 py-0.5 bg-gray-100 rounded-md text-gray-500 font-bold">
                                            {{ item.is_kg ? 'Kiloan' : 'Satuan' }}
                                        </span>
                                        <span v-if="item.estimated_weight" class="text-[10px] text-gray-400">
                                            Est: {{ item.estimated_weight }}
                                        </span>
                                    </div>
                                </div>
                                <button @click="removeItem(item.id)" class="text-gray-300 hover:text-red-500 transition-colors p-1">
                                    <i class="far fa-trash-can text-sm"></i>
                                </button>
                            </div>

                            <div class="mt-3 flex items-end justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5 text-[10px] text-gray-400">
                                        <i class="fas fa-map-marker-alt text-[8px]"></i>
                                        <span class="truncate max-w-[150px]">{{ item.pickup_address }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[10px] text-gray-400">
                                        <i class="far fa-calendar text-[8px]"></i>
                                        <span>{{ item.pickup_date }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter mb-0.5">Estimasi Biaya</p>
                                    <p class="text-sm font-black text-[#E30613]">{{ formatRupiah(item.total_price) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-20 bg-white rounded-3xl shadow-sm border border-gray-100">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-basket-shopping text-3xl text-gray-200"></i>
                    </div>
                    <h2 class="text-xl font-black text-gray-900 mb-2">Keranjang Kosong</h2>
                    <p class="text-sm text-gray-500 mb-8 max-w-xs mx-auto">Anda belum menambahkan layanan apapun ke keranjang.</p>
                    <Link :href="route('pelanggan.daftar-layanan')" class="bg-[#E30613] text-white px-8 py-3 rounded-full font-black text-xs uppercase tracking-widest shadow-lg shadow-red-500/20 hover:bg-black transition-all active:scale-95">
                        Pesan Sekarang
                    </Link>
                </div>

            </div>
        </div>

        <!-- Checkout Bottom Bar -->
        <div v-if="cartItems.length > 0" class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-100 px-6 pt-4 pb-[calc(1.2rem+env(safe-area-inset-bottom))] shadow-[0_-15px_30px_rgba(0,0,0,0.05)] flex justify-between items-center rounded-t-[2rem] lg:max-w-4xl lg:mx-auto lg:left-1/2 lg:-translate-x-1/2">
            <div class="pl-2">
                <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-0.5">Total Terpilih ({{ selectedItems.length }})</p>
                <p class="text-[18px] font-black text-gray-900 leading-tight tracking-tighter">
                    {{ formatRupiah(totalSelectedPrice) }}
                </p>
            </div>

            <button 
                @click="handleCheckout"
                :disabled="selectedItems.length === 0 || checkoutForm.processing"
                class="px-8 py-3 rounded-full font-black text-white text-xs uppercase tracking-widest shadow-md transition-all active:scale-95 flex items-center justify-center gap-2"
                :class="selectedItems.length === 0 || checkoutForm.processing ? 'bg-gray-100 text-gray-300 shadow-none' : 'bg-[#E30613] shadow-red-500/20'"
            >
                <i v-if="checkoutForm.processing" class="fas fa-spinner fa-spin"></i>
                <span v-else>Checkout</span>
            </button>
        </div>

        <!-- Professional Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-red-50 rounded-full">
                    <i class="fas fa-trash-can text-2xl text-[#E30613]"></i>
                </div>
                
                <h3 class="text-xl font-black text-center text-gray-900 mb-2">Hapus Pesanan?</h3>
                <p class="text-sm text-center text-gray-500 mb-8 leading-relaxed">
                    Apakah Anda yakin ingin menghapus pesanan ini dari keranjang? Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button 
                        @click="closeDeleteModal"
                        class="flex-1 py-3.5 rounded-xl font-bold text-sm text-gray-500 bg-gray-100 hover:bg-gray-200 transition-all active:scale-95"
                    >
                        Batal
                    </button>
                    <button 
                        @click="confirmDelete"
                        class="flex-1 py-3.5 rounded-xl font-bold text-sm text-white bg-[#E30613] hover:bg-black shadow-lg shadow-red-500/20 transition-all active:scale-95"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </Modal>

    </AppLayout>
</template>
