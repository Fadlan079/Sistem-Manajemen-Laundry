<script setup>
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/dashboard.vue';
import { ref } from 'vue';

const props = defineProps({
    auth: Object,
});

// Minimal Dummy Payment Data for UI Demo
const pendingPayments = ref([
    { id: 'INV-12345', orderId: 'ORD-12345', service: 'Cuci Komplit', date: '14 Apr 2026', total: 35000, status: 'Belum Lunas' }
]);

const paymentHistory = ref([
    { id: 'INV-12300', orderId: 'ORD-12300', service: 'Setrika Saja', date: '10 Apr 2026', total: 15000, method: 'QRIS', status: 'Lunas' },
    { id: 'INV-12250', orderId: 'ORD-12250', service: 'Cuci Kilat', date: '05 Apr 2026', total: 20000, method: 'Transfer Bank', status: 'Lunas' }
]);

const paymentMethods = ref([
    { id: 'qris', name: 'QRIS', desc: 'Scan via GoPay, OVO, Dana', icon: 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z' },
    { id: 'transfer', name: 'Transfer Bank', desc: 'Selesai dalam 5-10 menit', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
    { id: 'tunai', name: 'Tunai di Outlet', desc: 'Bayar saat ambil cucian', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' }
]);

const selectedPaymentMethod = ref('qris');

function formatRupiah(val) {
    if (!val) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val);
}

function processPayment(invoiceId) {
    alert(`Mensimulasikan pembayaran untuk tagihan ${invoiceId} menggunakan metode ${selectedPaymentMethod.value.toUpperCase()}...`);
}
</script>

<template>
    <Head title="Keuangan & Pembayaran" />

    <DashboardLayout title="Pembayaran">
        <div class="max-w-7xl mx-auto pb-12 space-y-8">
            
            <!-- Page Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Informasi Pembayaran</h1>
                <p class="text-muted">Kelola tagihan pesanan, riwayat transaksi, dan pilihan metode pembayaran.</p>
            </div>

            <!-- Pending Payments & Methods (Grid) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Tagihan Menunggu -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-border border-t-4 border-t-red-500 overflow-hidden">
                    <div class="p-6 border-b border-border bg-gray-50 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900">Tagihan Menunggu Pembayaran</h2>
                    </div>
                    
                    <div class="p-6">
                        <div v-if="pendingPayments.length === 0" class="text-center py-8 text-muted">
                            <svg class="w-12 h-12 text-muted/30 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p>Bagus! Semua tagihan Anda sudah lunas.</p>
                        </div>
                        
                        <div v-else class="space-y-4">
                            <div v-for="invoice in pendingPayments" :key="invoice.id" class="border border-red-200 bg-red-50/30 rounded-xl p-5 relative overflow-hidden group">
                                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded-full uppercase tracking-wider">{{ invoice.status }}</span>
                                            <span class="text-xs font-semibold text-muted">{{ invoice.date }}</span>
                                        </div>
                                        <h3 class="font-bold text-gray-900">Invoice: {{ invoice.id }}</h3>
                                        <p class="text-sm text-gray-600">Pesanan: {{ invoice.orderId }} ({{ invoice.service }})</p>
                                    </div>
                                    <div class="text-left sm:text-right flex flex-col sm:items-end w-full sm:w-auto">
                                        <p class="text-sm text-muted mb-1 font-semibold uppercase tracking-widest hidden sm:block">Total Bayar</p>
                                        <p class="text-2xl font-black text-red-600 mb-3">{{ formatRupiah(invoice.total) }}</p>
                                        <button @click="processPayment(invoice.id)" class="w-full sm:w-auto px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-bold shadow-md hover:shadow-lg transition">Bayar Sekarang</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metode Pembayaran List -->
                <div class="bg-gray-50 rounded-2xl border border-border p-6 flex flex-col">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Metode Pembayaran</h2>
                    <p class="text-sm text-muted mb-6">Pilih metode favorit Anda untuk menyelesaikan tagihan aktif dengan aman.</p>
                    
                    <div class="space-y-3 flex-1">
                        <label v-for="method in paymentMethods" :key="method.id" 
                               class="flex items-center gap-4 bg-white p-4 rounded-xl border-2 cursor-pointer transition-all"
                               :class="selectedPaymentMethod === method.id ? 'border-primary ring-2 ring-primary/20' : 'border-transparent hover:border-border shadow-sm'">
                            <input type="radio" :value="method.id" v-model="selectedPaymentMethod" class="sr-only" />
                            
                            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 transition-colors"
                                 :class="selectedPaymentMethod === method.id ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="method.icon"></path></svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 text-sm">{{ method.name }}</h4>
                                <p class="text-xs text-muted">{{ method.desc }}</p>
                            </div>
                            
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                 :class="selectedPaymentMethod === method.id ? 'border-primary' : 'border-gray-300'">
                                <div v-if="selectedPaymentMethod === method.id" class="w-2.5 h-2.5 rounded-full bg-primary"></div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Riwayat Transaksi -->
            <section class="bg-white rounded-2xl shadow-sm border border-border overflow-hidden">
                <div class="p-6 border-b border-border flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900">Riwayat Transaksi (Lunas)</h2>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-border text-xs uppercase tracking-widest text-muted font-bold">
                                <th class="p-4 pl-6 font-medium">Invoice ID</th>
                                <th class="p-4 font-medium">Layanan</th>
                                <th class="p-4 font-medium">Metode</th>
                                <th class="p-4 font-medium">Tanggal</th>
                                <th class="p-4 pr-6 font-medium text-right">Nominal Lunas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/50 text-sm">
                            <tr v-if="paymentHistory.length === 0">
                                <td colspan="5" class="p-8 text-center text-muted">Belum ada riwayat pembayaran yang lunas.</td>
                            </tr>
                            <tr v-for="payment in paymentHistory" :key="payment.id" class="hover:bg-gray-50 transition-colors">
                                <td class="p-4 pl-6 font-bold text-gray-900">{{ payment.id }}</td>
                                <td class="p-4">
                                    <span class="block font-semibold text-gray-900">{{ payment.service }}</span>
                                    <span class="text-xs text-muted block mt-0.5">{{ payment.orderId }}</span>
                                </td>
                                <td class="p-4 text-gray-700">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 border border-gray-200">
                                        {{ payment.method }}
                                    </span>
                                </td>
                                <td class="p-4 text-muted">{{ payment.date }}</td>
                                <td class="p-4 pr-6 text-right font-black text-success">{{ formatRupiah(payment.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            
        </div>
    </DashboardLayout>
</template>