<script setup>
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/app.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import QrcodeVue from 'qrcode.vue';

// Confetti loader
const loadConfetti = () => {
    return new Promise((resolve) => {
        if (window.confetti) return resolve(window.confetti);
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js';
        script.onload = () => resolve(window.confetti);
        document.head.appendChild(script);
    });
};


const props = defineProps({
    auth: Object,
    order: {
        type: Object,
        required: true
    }
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const steps = computed(() => {
    let s = ['Dibuat', 'Antri'];
    if (props.order.hasPickup) s.push('Dijemput');
    s.push('Diproses', 'Selesai');
    if (props.order.hasDelivery) s.push('Diantar');
    s.push('Diterima');
    return s;
});

const isExpanded = ref(false);
const isChangingPaymentMethod = ref(false);
const isSuccessModalOpen = ref(false);
const isCancelModalOpen = ref(false);
const successType = ref('payment'); // 'payment' or 'order'

const cancelForm = useForm({
    reason: '',
    customReason: ''
});

const cancellationReasons = [
    'Salah pilih layanan',
    'Ingin mengubah jadwal penjemputan',
    'Menemukan harga lebih murah',
    'Hanya ingin mencoba aplikasi',
    'Alasan lainnya'
];

const triggerSuccess = async (type = 'payment') => {
    successType.value = type;
    isSuccessModalOpen.value = true;
    const confetti = await loadConfetti();
    if (confetti) {
        const duration = 3 * 1000;
        const animationEnd = Date.now() + duration;
        const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 };

        const randomInRange = (min, max) => Math.random() * (max - min) + min;

        const interval = setInterval(function() {
            const timeLeft = animationEnd - Date.now();
            if (timeLeft <= 0) return clearInterval(interval);
            const particleCount = 50 * (timeLeft / duration);
            confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } });
            confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } });
        }, 250);
    }
};

const countdownText = ref('');
let countdownInterval = null;

const updateCountdown = () => {
    if (!props.order.pickup_timestamp || props.order.dbStatus !== 'pending') return;

    // Normalizing DB timestamp syntax 'YYYY-MM-DD HH:mm:ss' to 'YYYY-MM-DDT... '
    let rawTime = props.order.pickup_timestamp;
    if (rawTime && !rawTime.includes('T')) rawTime = rawTime.replace(' ', 'T');

    const targetDate = new Date(rawTime);
    const now = new Date();
    const diffMs = targetDate - now;

    if (diffMs <= 0) {
        countdownText.value = 'Waktu penjemputan telah tiba!';
        return;
    }

    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
    // const diffSecs = Math.floor((diffMs % (1000 * 60)) / 1000);
    // Not displaying seconds to avoid jitter, just Hour and Min is sufficient
    countdownText.value = `${diffHours} Jam ${diffMins} Menit`;
};

onMounted(() => {
    if (props.order.dbStatus === 'pending' && props.order.pickup_timestamp) {
        updateCountdown();
        countdownInterval = setInterval(updateCountdown, 60000); // per minute
    }

    // Trigger success celebration if redirected with success flash (for new orders)
    if (flash.value.success && flash.value.success.toLowerCase().includes('berhasil dibuat')) {
        triggerSuccess('order');
    }
});

onUnmounted(() => {
    if (countdownInterval) clearInterval(countdownInterval);
});

const isSnapLoading = ref(false);
const snapError = ref('');

// Dynamically load Midtrans Snap.js
function loadSnapScript() {
    return new Promise((resolve, reject) => {
        if (window.snap) { resolve(); return; }
        const script = document.createElement('script');
        script.src = import.meta.env.VITE_MIDTRANS_SNAP_URL;
        script.setAttribute('data-client-key', import.meta.env.VITE_MIDTRANS_CLIENT_KEY);
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

const isCheckingStatus = ref(false);

async function checkPaymentStatus() {
    isCheckingStatus.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const res = await fetch(route('pelanggan.aktivitas.midtrans.status', props.order.dbId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await res.json();
        if (data.status === 'paid') {
            await triggerSuccess();
            setTimeout(() => {
                router.reload({ only: ['order'] });
            }, 3000);
        } else {
            // Show toast or message that it's still pending
            alert(data.message || 'Pembayaran belum diterima.');
        }
    } catch (e) {
        console.error(e);
    } finally {
        isCheckingStatus.value = false;
    }
}

async function bayarMidtrans() {
    isSnapLoading.value = true;
    snapError.value = '';
    try {
        await loadSnapScript();

        // Fetch snap token from backend
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const res = await fetch(route('pelanggan.aktivitas.midtrans.token', props.order.dbId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!res.ok) {
            const err = await res.json();
            snapError.value = err.error ?? 'Gagal mendapatkan token pembayaran.';
            return;
        }

        const data = await res.json();

        window.snap.pay(data.snap_token, {
            onSuccess: async function(result) {
                // Call backend callback to mark as paid
                const cbRes = await fetch(route('pelanggan.aktivitas.midtrans.callback', props.order.dbId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                
                if (cbRes.ok) {
                    await triggerSuccess();
                    setTimeout(() => {
                        router.reload({ only: ['order'] });
                    }, 3000);
                }
            },
            onPending: function(result) {
                // Pending (e.g. VA waiting payment) - just reload
                router.reload({ only: ['order'] });
            },
            onError: function(result) {
                snapError.value = 'Pembayaran gagal atau dibatalkan. Silakan coba lagi.';
            },
            onClose: function() {
                // user closed popup - allow manual check
                checkPaymentStatus();
            }
        });
    } catch (e) {
        snapError.value = 'Terjadi kesalahan. Silakan coba lagi.';
        console.error(e);
    } finally {
        isSnapLoading.value = false;
    }
}

const payForm = useForm({
    method: props.order.paymentMethodRaw || 'cash'
});

const isCOD = computed(() => {
    const m = (payForm.method || '').toLowerCase();
    return m === 'cash' || m === 'cod' || m === 'tunai';
});

const getPaymentLabel = (val) => {
    const m = (val || '').toLowerCase();
    if (m === 'cash' || m === 'cod' || m === 'tunai') return 'Bayar Tunai ke Kurir';
    if (m === 'transfer') return 'Transfer Bank (Otomatis)';
    if (m === 'ewallet' || m === 'e-wallet') return 'E-Wallet (Otomatis)';
    return val;
};

function konfirmasiBayar() {
    payForm.post(route('pelanggan.aktivitas.bayar', props.order.dbId));
}

function submitPembatalan() {
    const finalReason = cancelForm.reason === 'Alasan lainnya' ? cancelForm.customReason : cancelForm.reason;
    cancelForm.transform((data) => ({
        ...data,
        reason: finalReason
    })).post(route('pelanggan.aktivitas.batal', props.order.dbId), {
        onSuccess: () => {
            isCancelModalOpen.value = false;
        }
    });
}

function batalkanPesanan() {
    isCancelModalOpen.value = true;
}

function getStepIndex() {
    const status = props.order.dbStatus;
    if (status === 'dibatalkan') return 0;
    
    const statusToLabel = {
        'dibuat': 'Dibuat',
        'antri': 'Antri',
        'dijemput': 'Dijemput',
        'diproses': 'Diproses',
        'selesai': 'Selesai',
        'diantar': 'Diantar',
        'diterima': 'Diterima'
    };
    
    const label = statusToLabel[status];
    const idx = steps.value.indexOf(label);
    return idx === -1 ? (status === 'diterima' ? steps.value.length - 1 : 0) : idx;
}

function formatRupiah(val) {
    if (!val) return 'Rp0';
    return 'Rp' + new Intl.NumberFormat('id-ID').format(val);
}

function downloadQR() {
    // Cari canvas di dalam ID invoiceQR
    const canvas = document.querySelector('#invoiceQR canvas');
    if (canvas) {
        const url = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.href = url;
        link.download = `QR-${props.order.invoice}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

const goBack = () => {
    router.visit(route('pelanggan.aktivitas'));
};

const isCopied = ref(false);
function copyInvoice() {
    navigator.clipboard.writeText(props.order.invoice).then(() => {
        isCopied.value = true;
        setTimeout(() => {
            isCopied.value = false;
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy!', err);
    });
}

const isPrintLoading = ref(false);
async function cetakNota() {
    isPrintLoading.value = true;

    // Get QR code canvas data URL
    const qrCanvas = document.querySelector('#invoiceQR canvas');
    const qrDataUrl = qrCanvas ? qrCanvas.toDataURL('image/png') : '';

    const o = props.order;
    const totalText = estimatedTotalCostText.value;
    const serviceText = estimatedServiceCostText.value;
    const now = new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });

    const html = `
    <div style="font-family: 'Courier New', Courier, monospace; max-width: 320px; margin: 0 auto; padding: 20px; background: white; color: #111;">

      <!-- Header -->
      <div style="text-align: center; border-bottom: 2px dashed #ddd; padding-bottom: 14px; margin-bottom: 14px;">
        <div style="font-size: 22px; font-weight: 900; letter-spacing: -1px; color: #E30613;">Hi Wash</div>
        <div style="font-size: 9px; color: #666; font-weight: bold; text-transform: uppercase; letter-spacing: 3px; margin-top: 2px;">Laundry & Dry Cleaning</div>
        <div style="font-size: 8px; color: #888; margin-top: 6px; line-height: 1.5;">Jl. Contoh No.1, Kota Anda<br/>Telp: 0812-3456-7890</div>
      </div>

      <!-- Invoice number & date -->
      <div style="border-bottom: 1px dashed #ddd; padding-bottom: 10px; margin-bottom: 10px;">
        <div style="display: flex; justify-content: space-between; font-size: 9px; color: #555; margin-bottom: 4px;">
          <span style="font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">No. Invoice</span>
          <span style="font-weight: 900; color: #E30613;">${o.invoice}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 9px; color: #555; margin-bottom: 4px;">
          <span style="font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">Pelanggan</span>
          <span style="font-weight: bold; color: #111;">${o.customerName ?? o.customer ?? '-'}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 9px; color: #555;">
          <span style="font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">Dicetak</span>
          <span style="font-weight: bold; color: #111;">${now}</span>
        </div>
      </div>

      <!-- Service detail -->
      <div style="border-bottom: 1px dashed #ddd; padding-bottom: 10px; margin-bottom: 10px;">
        <div style="font-size: 8px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; color: #888; margin-bottom: 6px;">Detail Layanan</div>
        <div style="font-size: 10px; font-weight: bold; color: #111; margin-bottom: 4px;">${o.service}</div>
        ${o.pickup_address ? `<div style="font-size: 8px; color: #555; margin-top: 4px;"><span style="font-weight:bold;">Alamat Jemput:</span> ${o.pickup_address}</div>` : ''}
        ${o.pickup_date_text ? `<div style="font-size: 8px; color: #555; margin-top: 3px;"><span style="font-weight:bold;">Jadwal Jemput:</span> ${o.pickup_date_text}</div>` : ''}
        ${o.laundry_notes ? `<div style="font-size: 8px; color: #E30613; margin-top: 4px; font-style: italic;">Catatan: ${o.laundry_notes}</div>` : ''}
      </div>

      <!-- Courier info -->
      ${o.pickup_courier || o.delivery_courier ? `
      <div style="border-bottom: 1px dashed #ddd; padding-bottom: 10px; margin-bottom: 10px;">
        <div style="font-size: 8px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; color: #888; margin-bottom: 6px;">Informasi Kurir</div>
        ${o.pickup_courier ? `
          <div style="font-size: 9px; color: #111; margin-bottom: 4px;">
            <div style="font-weight: bold; color: #555; text-transform: uppercase; font-size: 7px; letter-spacing: 1px;">Kurir Jemput</div>
            <div style="display: flex; justify-content: space-between; margin-top: 2px;">
              <span>${o.pickup_courier.name}</span>
              <span style="font-weight: bold; color: #16a34a;">${o.pickup_courier.phone}</span>
            </div>
          </div>
        ` : ''}
        ${o.delivery_courier ? `
          <div style="font-size: 9px; color: #111; margin-bottom: 4px; ${o.pickup_courier ? 'margin-top: 8px;' : ''}">
            <div style="font-weight: bold; color: #555; text-transform: uppercase; font-size: 7px; letter-spacing: 1px;">Kurir Antar</div>
            <div style="display: flex; justify-content: space-between; margin-top: 2px;">
              <span>${o.delivery_courier.name}</span>
              <span style="font-weight: bold; color: #2563eb;">${o.delivery_courier.phone}</span>
            </div>
          </div>
        ` : ''}
      </div>
      ` : ''}

      <!-- Cost breakdown -->
      <div style="border-bottom: 2px dashed #ddd; padding-bottom: 10px; margin-bottom: 10px;">
        <div style="font-size: 8px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; color: #888; margin-bottom: 6px;">Rincian Biaya</div>
        <div style="display: flex; justify-content: space-between; font-size: 9px; color: #444; margin-bottom: 4px;">
          <span>Biaya Layanan</span>
          <span style="font-weight: bold;">${serviceText}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 9px; color: #444; margin-bottom: 4px;">
          <span>Ongkos Kirim <span style="font-size: 7px; color: #888;">(${o.delivery_type_label})</span></span>
          <span style="font-weight: bold;">${formatRupiah(o.fee)}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 900; color: #111; margin-top: 6px; padding-top: 6px; border-top: 1px solid #eee;">
          <span>TOTAL</span>
          <span style="color: #E30613;">${totalText}</span>
        </div>
      </div>

      <!-- Payment status -->
      <div style="border-bottom: 1px dashed #ddd; padding-bottom: 10px; margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; font-size: 9px;">
          <span style="font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #555;">Metode Bayar</span>
          <span style="font-weight: bold; color: #111;">${o.paymentMethod ?? '-'}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 9px; margin-top: 4px;">
          <span style="font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #555;">Status Bayar</span>
          <span style="font-weight: 900; color: ${o.paymentStatus === 'PAID' ? '#16a34a' : '#E30613'}">${o.paymentStatus === 'PAID' ? 'LUNAS' : 'BELUM BAYAR'}</span>
        </div>
      </div>

      <!-- QR Code -->
      ${qrDataUrl ? `
      <div style="text-align: center; margin-bottom: 14px;">
        <img src="${qrDataUrl}" style="width: 90px; height: 90px;" />
        <div style="font-size: 8px; color: #888; margin-top: 4px; letter-spacing: 1px;">Scan untuk verifikasi</div>
      </div>` : ''}

      <!-- Footer -->
      <div style="text-align: center; font-size: 8px; color: #aaa; line-height: 1.8;">
        <div style="font-weight: 900; color: #555; margin-bottom: 2px;">Terima kasih atas kepercayaan Anda!</div>
        <div>Nota ini adalah bukti pembayaran yang sah.</div>
        <div style="margin-top: 4px; letter-spacing: 2px;">★ HI WASH ★</div>
      </div>
    </div>
    `;

    const el = document.createElement('div');
    el.innerHTML = html;
    document.body.appendChild(el);

    const html2pdf = (await import('html2pdf.js')).default;
    await html2pdf()
        .set({
            margin: [5, 5],
            filename: `Nota-${o.invoice}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, logging: false },
            jsPDF: { unit: 'mm', format: [80, 200], orientation: 'portrait' },
        })
        .from(el)
        .save();

    document.body.removeChild(el);
    isPrintLoading.value = false;
}

const estimatedKG = computed(() => {
    if (!props.order.isKg || props.order.isCalculated || !props.order.estimated_weight_option) return { min: 0, max: 0 };
    const key = props.order.estimated_weight_option;
    const minMap = { 'kurang_dari_3': 1, '3_sampai_5': 3, '5_sampai_10': 5, 'lebih_dari_10': 10 };
    const maxMap = { 'kurang_dari_3': 3, '3_sampai_5': 5, '5_sampai_10': 10, 'lebih_dari_10': 15 };
    return {
        min: minMap[key] || 0,
        max: maxMap[key] || 0
    };
});

const estimatedServiceCostText = computed(() => {
    if (!props.order.isKg || props.order.isCalculated) return formatRupiah(props.order.price);
    const minCost = props.order.service_price * estimatedKG.value.min;
    const maxCost = props.order.service_price * estimatedKG.value.max;
    return `${formatRupiah(minCost)} - ${formatRupiah(maxCost)}`;
});

const estimatedTotalCostText = computed(() => {
    if (!props.order.isKg || props.order.isCalculated) return formatRupiah(props.order.total);
    
    let extraSum = 0;
    if (props.order.extras) {
        props.order.extras.forEach(e => extraSum += e.price);
    }
    const finalUnitPrice = props.order.service_price + extraSum - (props.order.discount_amount || 0);

    const minTot = (finalUnitPrice * estimatedKG.value.min) + props.order.fee;
    const maxTot = (finalUnitPrice * estimatedKG.value.max) + props.order.fee;
    return `${formatRupiah(minTot)} - ${formatRupiah(maxTot)}`;
});
</script>

<template>
    <Head title="Detail Pesanan" />

    <AppLayout :hideBottomNav="order.paymentStatus === 'UNPAID'">
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

            <div class="relative z-10 max-w-2xl mx-auto px-6 text-center text-white">
                <h1 class="text-3xl font-bold mb-1">Detail Pesanan</h1>
                <p class="text-xs opacity-80 font-medium tracking-widest uppercase">{{ order.invoice }}</p>
            </div>

            <!-- Curved Bottom -->
            <div class="absolute bottom-0 left-0 w-full z-10 leading-none pointer-events-none">
                <svg class="block w-full h-12 sm:h-16 lg:h-20" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="fill-[#FFE800]" d="M0,128L80,144C160,160,320,192,480,197.3C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                </svg>
                <svg class="absolute bottom-0 left-0 w-full h-8 sm:h-10 lg:h-12" preserveAspectRatio="none" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="fill-white" d="M0,64L80,90.7C160,117,320,171,480,186.7C640,203,800,181,960,154.7C1120,128,1280,96,1360,80L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                </svg>
            </div>
        </div>

        <!-- Stepper Design -->
        <div class="max-w-2xl mx-auto px-4 mt-6 mb-8 relative z-20">
            <div class="flex items-center justify-between relative px-2">
                <!-- Progress Line Background -->
                <div class="absolute top-4 left-0 right-0 h-0.5 bg-gray-200 -z-0 mx-10">
                    <!-- Active Progress Line -->
                    <div
                        class="h-full bg-[#E30613] transition-all duration-500 ease-in-out"
                        :style="{ width: `${Math.min(100, (getStepIndex() / (steps.length - 1)) * 100)}%` }"
                    ></div>
                </div>

                <div v-for="(step, index) in steps" :key="index" class="relative z-10 flex flex-col items-center">
                    <div
                        class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-black transition-all duration-500 border-2"
                        :class="[
                            getStepIndex() === index ? 'bg-white border-[#E30613] text-[#E30613] scale-110 shadow-lg' :
                            getStepIndex() > index ? 'bg-[#E30613] border-[#E30613] text-white' :
                            'bg-gray-100 border-gray-200 text-gray-400'
                        ]"
                    >
                        <i v-if="getStepIndex() > index" class="fas fa-check"></i>
                        <span v-else>{{ index + 1 }}</span>
                    </div>
                    <span
                        class="text-[9px] font-black mt-2 uppercase tracking-tight text-center max-w-[60px] leading-tight transition-colors duration-300"
                        :class="getStepIndex() >= index ? 'text-[#E30613]' : 'text-gray-400'"
                    >
                        {{ step }}
                    </span>
                </div>
            </div>
        </div>

        <div class="max-w-2xl mx-auto pb-32 lg:pb-12 space-y-5 px-4 sm:px-0 mt-4">

            <!-- Status Badge Section -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center justify-center gap-4 overflow-hidden relative">
                <div v-if="order.dbStatus === 'dibuat'" class="flex flex-col items-center gap-2">
                    <div class="flex items-center gap-2 bg-blue-50 text-blue-600 font-bold py-1.5 px-4 rounded-full border border-blue-100 text-[10px] uppercase tracking-widest">
                        <i class="fas fa-info-circle"></i> Pesanan Berhasil Dibuat
                    </div>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Menunggu Konfirmasi Admin</p>
                </div>
                <div v-else-if="order.dbStatus === 'antri'" class="flex flex-col items-center gap-2">
                    <div class="flex items-center gap-2 bg-amber-50 text-amber-600 font-bold py-1.5 px-4 rounded-full border border-amber-100 text-[10px] uppercase tracking-widest">
                        <i class="fas fa-hourglass-half"></i> Pesanan Masuk Antrean
                    </div>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest text-center px-4">Operator telah menerima pesanan Anda</p>
                </div>
                <div v-else-if="order.dbStatus === 'dijemput'" class="flex items-center gap-2 bg-emerald-50 text-emerald-600 font-bold py-1.5 px-4 rounded-full border border-emerald-100 text-[10px] uppercase tracking-widest">
                    <i class="fas fa-motorcycle animate-pulse"></i> Kurir Sedang Di Jalan
                </div>
                <div v-else-if="order.dbStatus === 'diproses'" class="flex items-center gap-2 bg-blue-50 text-blue-600 font-bold py-1.5 px-4 rounded-full border border-blue-100 text-[10px] uppercase tracking-widest">
                    <i class="fas fa-tshirt animate-bounce"></i> Pakaian Sedang Diproses
                </div>
                <div v-else-if="order.dbStatus === 'selesai' && order.paymentStatus === 'UNPAID'" class="flex flex-col items-center gap-2">
                    <div class="flex items-center gap-2 bg-yellow-50 text-yellow-600 font-bold py-1.5 px-4 rounded-full border border-yellow-100 text-[10px] uppercase tracking-widest">
                        <i class="fas fa-exclamation-circle animate-pulse"></i> Menunggu Pembayaran
                    </div>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Siapkan Tunai / Bayar via Sistem</p>
                </div>
                <div v-else-if="order.dbStatus === 'selesai' && order.paymentStatus === 'PAID'" class="flex items-center gap-2 bg-emerald-50 text-emerald-600 font-bold py-1.5 px-4 rounded-full border border-emerald-100 text-[10px] uppercase tracking-widest">
                    <i class="fas fa-box"></i> {{ order.hasDelivery ? 'Pesanan Siap Diantar' : 'Siap Diambil di Outlet' }}
                </div>
                <div v-else-if="order.dbStatus === 'diantar'" class="flex items-center gap-2 bg-purple-50 text-purple-600 font-bold py-1.5 px-4 rounded-full border border-purple-100 text-[10px] uppercase tracking-widest">
                    <i class="fas fa-motorcycle animate-pulse"></i> Kurir Sedang Mengantar
                </div>
                <div v-else-if="order.dbStatus === 'diterima'" class="flex items-center gap-2 bg-[#E30613]/10 text-[#E30613] font-bold py-1.5 px-4 rounded-full border border-[#E30613]/20 text-[10px] uppercase tracking-widest">
                    <i class="fas fa-check-double"></i> Pesanan Telah Diterima
                </div>
                <div v-else-if="order.dbStatus === 'dibatalkan'" class="flex items-center gap-2 bg-red-50 text-red-600 font-bold py-1.5 px-4 rounded-full border border-red-100 text-[10px] uppercase tracking-widest">
                    <i class="fas fa-times-circle"></i> Pesanan Dibatalkan
                </div>
            </div>

            <!-- Flash success -->
            <div v-if="flash.success" class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm font-bold text-green-700 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500"></i>
                {{ flash.success }}
            </div>

            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <!-- Service Header -->
                <div class="p-5 border-b border-gray-100 bg-gray-50/30">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-[#E30613] text-white rounded-xl shadow-lg shadow-red-500/20 flex items-center justify-center text-xl shrink-0">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <!-- Status badge -->
                            <span v-if="order.isKg && !order.isCalculated"
                                class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-widest text-blue-600 bg-blue-50 border border-blue-100 px-2 py-0.5 rounded-full mb-1.5">
                                <i class="fas fa-clock"></i> Menunggu Penimbangan
                            </span>
                            <span v-else-if="order.isKg && order.isCalculated"
                                class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full mb-1.5">
                                <i class="fas fa-check"></i> Sudah Ditimbang
                            </span>
                            <span v-else
                                class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-widest text-gray-500 bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-full mb-1.5">
                                <i class="fas fa-box"></i> Per Satuan / Item
                            </span>

                            <!-- Info rows -->
                            <div class="space-y-1.5 mt-1">
                                <!-- Jenis Layanan -->
                                <div class="flex items-baseline gap-2">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest shrink-0 w-28">Jenis Layanan</span>
                                    <span class="text-[13px] font-black text-gray-900 leading-tight">{{ order.service }}</span>
                                </div>

                                <!-- Berat: jika sudah ditimbang tampilkan actual_weight, jika belum tampilkan estimasi -->
                                <div v-if="order.isKg" class="flex items-baseline gap-2">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest shrink-0 w-28">
                                        {{ order.isCalculated ? 'Berat Aktual' : 'Estimasi Berat' }}
                                    </span>
                                    <span v-if="order.isCalculated && order.actual_weight"
                                        class="text-[13px] font-black text-[#E30613]">
                                        {{ order.actual_weight }} kg
                                    </span>
                                    <span v-else-if="!order.isCalculated && order.estimated_weight"
                                        class="text-[13px] font-bold text-gray-700">
                                        {{ order.estimated_weight }}
                                    </span>
                                    <span v-else class="text-[12px] text-gray-400 italic">Belum ditentukan</span>
                                </div>

                                <!-- Jumlah item jika PCS -->
                                <div v-else-if="order.items_qty > 0" class="flex items-baseline gap-2">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest shrink-0 w-28">Jumlah</span>
                                    <span class="text-[13px] font-black text-gray-900">
                                        {{ order.items_qty }} {{ order.unit.replace('/', '') }}
                                    </span>
                                </div>

                                <!-- Diskon -->
                                <div v-if="order.use_discount && order.discount_amount > 0" class="flex items-baseline gap-2">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest shrink-0 w-28">Diskon</span>
                                    <span class="text-[12px] font-black text-emerald-600">
                                        - {{ formatRupiah(order.discount_amount) }}
                                    </span>
                                </div>

                                <!-- Layanan Ekstra -->
                                <div v-if="order.extras && order.extras.length > 0" class="flex items-start gap-2">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest shrink-0 w-28 mt-0.5">Layanan Ekstra</span>
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="extra in order.extras" :key="extra.type"
                                            class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full border"
                                            :class="extra.type === 'own_perfume' ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : 'text-[#E30613] bg-red-50 border-red-100'">
                                            <i :class="extra.type === 'express' ? 'fas fa-bolt' : extra.type === 'treatment' ? 'fas fa-magic' : 'fas fa-spray-can'"></i>
                                            {{ extra.label }}
                                            <template v-if="extra.price > 0">(+{{ formatRupiah(extra.price) }})</template>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-5 space-y-4">
                    <div v-if="order.laundry_notes" class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                        <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center shrink-0">
                            <i class="fas fa-clipboard-list text-gray-400 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <span class="font-black text-gray-400 text-[10px] uppercase tracking-widest block mb-1">Catatan Layanan</span>
                            <span class="font-bold text-gray-700 block leading-relaxed italic text-xs">"{{ order.laundry_notes }}"</span>
                        </div>
                    </div>

                    <div v-if="order.pickup_address" class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                        <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center shrink-0">
                            <i class="fas fa-map-marker-alt text-gray-400 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <span class="font-black text-gray-400 text-[10px] uppercase tracking-widest block mb-1">Alamat Penjemputan</span>
                            <span class="font-bold text-gray-700 block leading-relaxed text-xs">{{ order.pickup_address }}</span>
                            
                            <!-- Catatan Kurir -->
                            <div v-if="order.courier_notes" class="mt-2.5 bg-gray-50 border border-gray-100 rounded-xl p-3 text-[10px] text-gray-600 flex gap-2 shadow-sm w-full">
                                <i class="fas fa-comment-dots text-gray-400 mt-0.5 shrink-0"></i>
                                <span class="leading-relaxed"><strong class="font-black text-gray-900 uppercase text-[9px] mr-1">Catatan Kurir:</strong> {{ order.courier_notes }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 items-start text-xs border-b border-gray-50 pb-4">
                        <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center shrink-0">
                            <i class="fas fa-calendar-alt text-gray-400 text-sm"></i>
                        </div>
                        <div>
                            <span class="font-black text-gray-400 text-[10px] uppercase tracking-widest block mb-1">Jadwal Penjemputan</span>
                            <span class="font-bold text-gray-700 text-xs">{{ order.pickup_date_text || order.date }}</span>
                        </div>
                    </div>

                    <!-- Kurir Jemput Section -->
                    <div v-if="order.pickup_courier" class="flex gap-4 items-center text-xs border-b border-gray-50 pb-4">
                        <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 border border-emerald-100 shrink-0">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-0.5">Kurir Penjemputan</span>
                            <span class="font-black text-gray-900 block text-[13px]">{{ order.pickup_courier.name }}</span>
                            <span class="font-bold text-emerald-600 block mt-0.5 text-[10px]">{{ order.pickup_courier.phone }}</span>
                        </div>
                        <a :href="'tel:' + order.pickup_courier.phone" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest transition-colors shadow-md active:scale-95 shrink-0">
                            Hubungi
                        </a>
                    </div>

                    <!-- Kurir Antar Section -->
                    <div v-if="order.delivery_courier" class="flex gap-4 items-center text-xs border-b border-gray-50 pb-4">
                        <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 border border-blue-100 shrink-0">
                            <i class="fas fa-motorcycle"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-0.5">Kurir Pengantaran</span>
                            <span class="font-black text-gray-900 block text-[13px]">{{ order.delivery_courier.name }}</span>
                            <span class="font-bold text-blue-600 block mt-0.5 text-[10px]">{{ order.delivery_courier.phone }}</span>
                        </div>
                        <a :href="'tel:' + order.delivery_courier.phone" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest transition-colors shadow-md active:scale-95 shrink-0">
                            Hubungi
                        </a>
                    </div>

                    <!-- Nomor Invoice & QR (Pusat & Besar) -->
                    <div class="bg-gray-50/50 p-8 rounded-2xl border border-gray-100 flex flex-col items-center justify-center space-y-5">
                        <div class="relative">
                            <div class="p-3 bg-white rounded-2xl shadow-sm border border-gray-200">
                                <QrcodeVue :value="order.invoice" :size="140" level="H" />
                            </div>
                            <!-- Small Floating Download Button -->
                            <button @click="downloadQR" 
                                class="absolute -top-2 -right-2 w-9 h-9 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-400 hover:text-[#E30613] hover:border-[#E30613] shadow-sm transition-all active:scale-90"
                                title="Download QR">
                                <i class="fas fa-download text-xs"></i>
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Nomor Invoice</p>
                            <div class="flex items-center justify-center gap-2">
                                <code class="text-sm font-black text-gray-900 tracking-tight">{{ order.invoice }}</code>
                                <button @click="copyInvoice" 
                                    :class="[isCopied ? 'text-green-500' : 'text-gray-400 hover:text-[#E30613]', 'transition-colors p-1']"
                                    :title="isCopied ? 'Tersalin!' : 'Salin Invoice'">
                                    <i :class="isCopied ? 'fas fa-check' : 'far fa-copy text-[10px]'"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Hidden Rendered QR for Download -->
                        <div class="hidden" id="invoiceQR">
                            <QrcodeVue :value="order.invoice" :size="500" level="H" />
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t-2 border-dashed border-gray-200 space-y-3">
                        <h3 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-3">Rincian Biaya</h3>

                        <!-- Biaya Layanan -->
                        <div class="flex justify-between text-xs font-bold">
                            <span class="text-gray-500 uppercase tracking-tighter">
                                Biaya Layanan
                                <!-- KG belum ditimbang: tampilkan range estimasi -->
                                <span v-if="order.isKg && !order.isCalculated && order.estimated_weight" class="text-gray-300 normal-case font-normal"><br>~{{ estimatedKG.min }}&ndash;{{ estimatedKG.max }} kg</span>
                                <!-- KG sudah ditimbang: tampilkan berat aktual -->
                                <span v-else-if="order.isKg && order.isCalculated && order.actual_weight" class="text-xs text-primary font-black ml-1">({{ order.actual_weight }} kg)</span>
                                <!-- PCS -->
                                <span v-else-if="!order.isKg && order.items_qty > 0" class="text-gray-300">({{ order.items_qty }} {{ order.unit.replace('/', '') }})</span>
                            </span>
                            <span class="text-gray-900">{{ estimatedServiceCostText }}</span>
                        </div>

                        <!-- Harga per satuan -->
                        <div v-if="order.isCalculated" class="text-[9px] text-gray-400 -mt-2 font-black uppercase tracking-widest">
                            {{ formatRupiah(order.service_price) }}{{ order.unit }}
                        </div>

                        <!-- Biaya Ekstra (per baris) -->
                        <template v-if="order.extras && order.extras.filter(e => e.price > 0).length > 0">
                            <div v-for="extra in order.extras.filter(e => e.price > 0)" :key="extra.type"
                                class="flex justify-between text-xs font-bold">
                                <span class="text-gray-500 uppercase tracking-tighter flex items-center gap-1">
                                    <i :class="extra.type === 'express' ? 'fas fa-bolt' : 'fas fa-magic'" class="text-gray-300"></i>
                                    {{ extra.label }}
                                </span>
                                <span class="text-gray-700">+ {{ formatRupiah(extra.price) }}{{ order.isKg ? order.unit : '/item' }}</span>
                            </div>
                        </template>

                        <!-- Diskon -->
                        <div v-if="order.use_discount && order.discount_amount > 0" class="flex justify-between text-xs font-bold">
                            <span class="text-emerald-600 uppercase tracking-tighter flex items-center gap-1">
                                <i class="fas fa-percent text-emerald-400"></i> Diskon
                            </span>
                            <span v-if="!order.isCalculated" class="text-emerald-600">- {{ formatRupiah(order.discount_amount) }}{{ order.unit }}</span>
                            <span v-else class="text-emerald-600">- {{ formatRupiah(order.discount_amount * order.items_qty) }}</span>
                        </div>

                        <!-- Ongkos Kirim -->
                        <div class="flex justify-between text-xs font-bold pt-1">
                            <span class="text-gray-500 uppercase tracking-tighter">Ongkos Kirim <span class="text-gray-300">({{ order.delivery_type_label }})</span></span>
                            <span class="text-gray-900">{{ formatRupiah(order.fee) }}</span>
                        </div>

                        <!-- Total Biaya -->
                        <div class="flex justify-between items-end pt-4 mt-2 border-t border-gray-100">
                            <div>
                                <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Biaya</span>
                                <span v-if="order.isKg && !order.isCalculated" class="text-[8px] text-blue-500 font-black uppercase tracking-tighter leading-none italic">*Estimasi</span>
                            </div>
                            <span class="text-2xl font-black text-[#E30613] tracking-tighter leading-none">{{ estimatedTotalCostText }}</span>
                        </div>
                    </div>

                    <!-- Not Calculated Note -->
                    <template v-if="order.isKg && !order.isCalculated">
                        <div v-if="order.dbStatus !== 'dibatalkan'" class="bg-blue-50/50 border border-blue-100 rounded-xl p-4 mt-4 flex gap-3 text-blue-700 text-[11px] items-start shadow-sm">
                            <i class="fas fa-lightbulb mt-0.5 text-base"></i>
                            <p class="leading-relaxed font-bold">Berat & harga layanan di atas masih berupa perkiraan. Harga akhir akan ditentukan <span class="text-blue-900 underline">setelah pesanan dijemput</span> dan ditimbang.</p>
                        </div>

                        <button
                            v-if="order.dbStatus === 'pending' && order.paymentStatus === 'UNPAID'"
                            @click="batalkanPesanan"
                            class="w-full mt-4 py-3.5 rounded-xl font-black uppercase tracking-widest text-[#E30613] text-[10px] bg-red-50 hover:bg-[#E30613] hover:text-white border border-red-100 transition-all shadow-sm active:scale-95">
                            Batalkan Pesanan
                        </button>
                    </template>
                    <template v-else-if="order.dbStatus === 'pending' && order.paymentStatus === 'UNPAID'">
                        <!-- Even if calculated (PCS), if still pending allow cancellation -->
                         <button
                            @click="batalkanPesanan"
                            class="w-full mt-4 py-3.5 rounded-xl font-black uppercase tracking-widest text-[#E30613] text-[10px] bg-red-50 hover:bg-[#E30613] hover:text-white border border-red-100 transition-all shadow-sm active:scale-95">
                            Batalkan Pesanan
                        </button>
                    </template>

                    <!-- Cancel Reason Display -->
                    <div v-if="order.dbStatus === 'dibatalkan' && order.cancel_reason" class="bg-red-50 border border-red-100 rounded-xl p-4 mt-4 flex gap-3 text-red-700 text-[11px] items-start shadow-sm">
                        <i class="fas fa-exclamation-triangle mt-0.5 text-base"></i>
                        <div class="space-y-1">
                            <p class="font-black uppercase tracking-widest text-[9px] text-red-400">Alasan Pembatalan:</p>
                            <p class="leading-relaxed font-bold">{{ order.cancel_reason }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Show Payment Section Always -->
            <section class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 space-y-5">
                <div v-if="order.paymentStatus === 'UNPAID'" class="space-y-4">
                    <h3 class="font-black text-sm text-gray-900 border-b border-gray-100 pb-2">Informasi Pembayaran</h3>

                    <!-- View Mode (Satu Box) -->
                    <div v-if="!isChangingPaymentMethod" class="flex items-center justify-between border-2 border-red-100 bg-red-50 p-4 rounded-xl shadow-sm transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center border border-red-200 text-[#E30613] shadow-sm shrink-0">
                                <i :class="['cash', 'cod'].includes(payForm.method) ? 'fas fa-money-bill-wave' : (payForm.method === 'transfer' ? 'fas fa-credit-card' : 'fas fa-qrcode')" class="text-lg"></i>
                            </div>
                            <div>
                                <p class="text-[9px] uppercase font-black tracking-[0.1em] text-[#E30613] mb-0.5">Pilihan Anda</p>
                                <p class="text-[13px] font-bold text-gray-900 leading-tight drop-shadow-sm">{{ getPaymentLabel(payForm.method) }}</p>
                            </div>
                        </div>
                        <button @click="isChangingPaymentMethod = true" class="text-[10px] font-bold text-[#E30613] bg-white border border-red-200 px-3 py-2 rounded-lg shadow-sm hover:bg-gray-50 uppercase tracking-widest transition-colors flex items-center gap-1.5 shrink-0">
                            <i class="fas fa-edit"></i> Ganti
                        </button>
                    </div>

                    <!-- Edit Mode (Radio Buttons) -->
                    <div v-else class="space-y-2 relative border-2 border-gray-100 rounded-xl p-4 bg-gray-50/50">
                        <div class="flex justify-between items-center mb-3">
                           <span class="text-xs font-black text-gray-700 uppercase tracking-wide">Pilih Metode Baru</span>
                           <button @click="isChangingPaymentMethod = false" class="text-gray-400 hover:text-[#E30613] text-[10px] font-bold transition-colors">
                               <i class="fas fa-times"></i> Batal
                           </button>
                        </div>

                        <label class="flex items-center gap-3 border-2 p-3 rounded-lg cursor-pointer transition-colors"
                            :class="payForm.method === 'cash' || payForm.method === 'cod' ? 'border-[#E30613] bg-red-50/30' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <input type="radio" v-model="payForm.method" value="cash" class="w-4 h-4 text-[#E30613] focus:ring-[#E30613]">
                            <span class="text-xs font-bold text-gray-800">Bayar Tunai ke Kurir (COD)</span>
                        </label>

                        <label class="flex items-center gap-3 border-2 p-3 rounded-lg cursor-pointer transition-colors"
                            :class="payForm.method === 'transfer' ? 'border-[#E30613] bg-red-50/30' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <input type="radio" v-model="payForm.method" value="transfer" class="w-4 h-4 text-[#E30613] focus:ring-[#E30613]">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-gray-800">Transfer Bank</span>
                                <span class="text-[9px] text-emerald-600 font-bold">Verifikasi Otomatis</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 border-2 p-3 rounded-lg cursor-pointer transition-colors"
                            :class="payForm.method === 'ewallet' || payForm.method === 'e-wallet' ? 'border-[#E30613] bg-red-50/30' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <input type="radio" v-model="payForm.method" value="ewallet" class="w-4 h-4 text-[#E30613] focus:ring-[#E30613]">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-gray-800">E-Wallet (QRIS)</span>
                                <span class="text-[9px] text-emerald-600 font-bold">Verifikasi Otomatis</span>
                            </div>
                        </label>
                        
                        <div class="pt-2 text-right">
                           <button @click="isChangingPaymentMethod = false" class="text-[10px] bg-gray-900 text-white px-3 py-1.5 rounded-lg font-bold shadow-sm hover:bg-black transition-colors">Terapkan</button>
                        </div>
                    </div>

                    <!-- Snap Error -->
                    <div v-if="snapError" class="bg-red-50 border border-red-200 text-red-600 rounded-lg p-3 mt-3 text-xs font-bold flex gap-2 items-center">
                        <i class="fas fa-exclamation-circle"></i> {{ snapError }}
                    </div>

                    <!-- Desktop Payment Button (Online) -->
                    <template v-if="order.isCalculated && !isCOD">
                        <button
                            @click="bayarMidtrans"
                            :disabled="isSnapLoading || isCheckingStatus"
                            class="hidden lg:flex w-full mt-4 py-3 rounded-lg font-bold text-white text-xs bg-[#E30613] shadow-md hover:bg-black transition-colors justify-center items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                            <i v-if="isSnapLoading" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-credit-card"></i>
                            {{ isSnapLoading ? 'Memuat...' : 'Bayar Sekarang' }}
                        </button>

                        <!-- Desktop Check Status Button -->
                        <button
                            @click="checkPaymentStatus"
                            :disabled="isCheckingStatus || isSnapLoading"
                            class="hidden lg:flex w-full mt-2 py-3 rounded-lg font-bold text-[#E30613] text-xs bg-white border border-[#E30613] shadow-sm hover:bg-red-50 transition-colors justify-center items-center gap-2 disabled:opacity-60">
                            <i v-if="isCheckingStatus" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-sync-alt"></i>
                            {{ isCheckingStatus ? 'Mengecek...' : 'Cek Status Pembayaran' }}
                        </button>
                    </template>

                    <!-- Desktop Cash/COD Message -->
                    <template v-else-if="order.isCalculated">
                        <div class="hidden lg:flex flex-col items-center gap-3 mt-4 p-4 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-400 shadow-sm">
                                <i class="fas fa-hand-holding-dollar text-lg"></i>
                            </div>
                            <div class="text-center">
                                <p class="text-xs font-black text-gray-900 uppercase tracking-wide">Bayar Tunai</p>
                                <p class="text-[10px] text-gray-500 font-medium leading-relaxed mt-1">Silakan siapkan uang tunai sesuai tagihan. Kurir akan mengonfirmasi pembayaran Anda.</p>
                            </div>
                        </div>
                    </template>
                    <button v-else
                        disabled
                        class="hidden lg:flex w-full mt-4 py-3 rounded-lg font-bold text-white text-xs bg-gray-400 cursor-not-allowed justify-center items-center">
                        Menunggu Penimbangan Kurir
                    </button>

                    <div class="bg-blue-50/50 rounded-lg p-3 mt-4 flex gap-3 text-blue-700 text-[11px] items-start">
                        <i class="fas fa-lightbulb mt-0.5 text-base"></i>
                        <p class="leading-relaxed">Anda bisa bayar via Tunai <strong class="font-bold">saat penjemputan atau pengantaran</strong> oleh kurir kami, atau selesaikan sekarang via Transfer / E-Wallet.</p>
                    </div>
                </div>

                <div v-else class="space-y-4">
                    <div class="flex items-center justify-between border-b pb-4">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Status Pembayaran</p>
                        <span class="bg-[#22C55E] text-white text-[9px] px-2.5 py-1 rounded font-black uppercase tracking-widest">
                            SUDAH DIBAYAR
                        </span>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-500 mb-1">Metode Pembayaran</p>
                        <p class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ order.paymentMethod }}</p>
                    </div>
                    <button @click="cetakNota" :disabled="isPrintLoading"
                        class="w-full bg-gray-900 py-3 rounded-lg font-bold text-white text-xs mt-2 flex justify-center items-center gap-2 hover:bg-black transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                        <i v-if="isPrintLoading" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-file-pdf"></i>
                        {{ isPrintLoading ? 'Membuat PDF...' : 'Unduh Nota PDF' }}
                    </button>
                </div>
            </section>

            <section v-if="order.review" class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Penilaian Layanan Anda</p>
                    <div class="flex gap-0.5">
                        <template v-for="i in 5" :key="i">
                            <i class="fas fa-star text-sm" :class="i <= order.review.rating ? 'text-[#FFE800]' : 'text-gray-200'"></i>
                        </template>
                    </div>
                </div>

                <div>
                    <p v-if="order.review.comment" class="text-xs text-gray-600 leading-relaxed bg-gray-50 border border-gray-100 p-3 rounded-lg italic">
                        "{{ order.review.comment }}"
                    </p>
                    <p v-else class="text-xs text-gray-500 bg-gray-50 border border-gray-100 p-3 rounded-lg italic">
                        Tidak ada komentar tambahan.
                    </p>
                    <p class="text-[10px] text-gray-400 font-medium mt-3 text-right">{{ order.review.date }}</p>
                </div>
            </section>

            <!-- Add review button if finished but not reviewed -->
            <section v-else-if="order.dbStatus === 'selesai'" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 text-center space-y-4">
                <div class="w-12 h-12 bg-yellow-50 text-yellow-500 rounded-full flex items-center justify-center mx-auto border border-yellow-100">
                    <i class="fas fa-star text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 text-sm">Bagaimana Layanan Kami?</h3>
                    <p class="text-[11px] text-gray-500 mt-1">Berikan penilaian Anda untuk membantu kami meningkatkan kualitas layanan.</p>
                </div>
                <Link :href="route('pelanggan.aktivitas.ulasan', order.dbId)"
                    class="inline-block w-full py-3 rounded-lg bg-[#E30613] text-white font-bold text-xs hover:bg-black transition-colors shadow-md uppercase tracking-widest">
                    Beri Ulasan Sekarang
                </Link>
            </section>

        </div>

        <!-- Mobile Fixed Action Bar (Payment) -->
        <div v-if="order.paymentStatus === 'UNPAID'" class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 px-5 pt-3 pb-[calc(1rem+env(safe-area-inset-bottom))] shadow-[0_-4px_15px_rgba(0,0,0,0.05)] lg:hidden flex justify-between items-center rounded-t-2xl">
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-0.5">
                    {{ order.isCalculated ? 'Total Tagihan' : 'Batas Estimasi' }}
                </p>
                <p class="text-[17px] font-black text-gray-900 leading-none">
                    {{ estimatedTotalCostText }}
                </p>
            </div>

            <!-- Calculated: Pay Now via Midtrans -->
            <div v-if="order.isCalculated" class="flex-1 flex justify-end">
                <div v-if="order.dbStatus === 'dibatalkan'" class="flex items-center gap-3 bg-red-50 px-4 py-2.5 rounded-2xl border border-red-100">
                    <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-red-400 shadow-sm shrink-0">
                        <i class="fas fa-times"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-red-900 uppercase tracking-wide">Dibatalkan</p>
                        <p class="text-[8px] text-red-500 font-medium leading-tight">Pesanan ini tidak aktif</p>
                    </div>
                </div>
                <div v-else-if="!isCOD" class="flex flex-col gap-2 items-end">
                    <button type="button" @click="bayarMidtrans"
                        :disabled="isSnapLoading || isCheckingStatus"
                        class="px-8 py-3.5 rounded-full font-bold text-white text-sm shadow-md transition-all active:scale-95 flex items-center justify-center gap-2"
                        :class="isSnapLoading || isCheckingStatus ? 'bg-gray-300 cursor-not-allowed text-gray-500 shadow-none' : 'bg-[#E30613] hover:bg-[#B7050F] shadow-[0_4px_14px_rgba(227,6,19,0.3)]'">
                        <i v-if="isSnapLoading" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-credit-card"></i>
                        <span v-if="!isSnapLoading">Bayar Sekarang</span>
                    </button>
                    <button type="button" @click="checkPaymentStatus"
                        :disabled="isCheckingStatus || isSnapLoading"
                        class="text-[10px] font-black text-[#E30613] uppercase tracking-widest flex items-center gap-1 px-2 py-1">
                        <i v-if="isCheckingStatus" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-sync-alt"></i>
                        Cek Status
                    </button>
                </div>
                
                <!-- COD Mobile State -->
                <div v-else class="flex items-center gap-3 bg-gray-50 px-4 py-2.5 rounded-2xl border border-gray-100">
                    <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-gray-400 shadow-sm shrink-0">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-900 uppercase tracking-wide">Bayar Tunai</p>
                        <p class="text-[8px] text-gray-500 font-medium leading-tight">Berikan ke kurir</p>
                    </div>
                </div>
            </div>
            
            <!-- Not Calculated: Wait for courier -->
            <template v-else>
                <button v-if="order.dbStatus === 'dibatalkan'" type="button" disabled
                    class="px-6 py-3.5 rounded-full font-bold text-red-500 text-xs bg-red-50 border border-red-100 cursor-not-allowed shadow-none flex items-center justify-center gap-2">
                    <i class="fas fa-times-circle"></i> Pesanan Dibatalkan
                </button>
                <button v-else type="button" disabled
                    class="px-6 py-3.5 rounded-full font-bold text-white text-xs bg-gray-400 cursor-not-allowed shadow-none flex items-center justify-center gap-2">
                    Tunggu Finalisasi
                </button>
            </template>
        </div>
    </AppLayout>

    <!-- Success Modal -->
    <Teleport to="body">
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isSuccessModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center px-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="isSuccessModalOpen = false"></div>
                
                <div class="relative bg-white w-full max-w-sm rounded-[2.5rem] overflow-hidden shadow-2xl animate-in zoom-in-95 duration-300">
                    <!-- Top accent -->
                    <div class="h-2 bg-gradient-to-r from-[#E30613] via-[#FFE800] to-[#E30613]"></div>
                    
                    <div class="p-8 text-center space-y-6">
                        <!-- Success Icon Wrapper -->
                        <div class="relative mx-auto w-24 h-24">
                            <div class="absolute inset-0 bg-emerald-100 rounded-full animate-ping opacity-20"></div>
                            <div class="relative w-24 h-24 bg-emerald-500 rounded-full flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                                <i class="fas fa-check text-4xl animate-in slide-in-from-bottom-2 duration-500"></i>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-2xl font-black text-gray-900 tracking-tight">
                                {{ successType === 'payment' ? 'Pembayaran Berhasil!' : 'Pesanan Berhasil!' }}
                            </h2>
                            <p class="text-xs text-gray-500 font-bold mt-2 leading-relaxed px-4">
                                {{ successType === 'payment' 
                                    ? 'Terima kasih! Pembayaran Anda telah kami terima. Pesanan akan segera diproses.' 
                                    : 'Pesanan Anda telah berhasil dibuat! Kurir kami akan segera menjemput laundry Anda sesuai jadwal.' 
                                }}
                            </p>
                        </div>

                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 text-left">
                            <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">
                                <span>No. Invoice</span>
                                <span>Metode</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[13px] font-black text-[#E30613] tracking-tighter">{{ order.invoice }}</span>
                                <span class="text-[11px] font-black text-gray-900 uppercase tracking-tight">{{ order.paymentMethod }}</span>
                            </div>
                        </div>

                        <button 
                            @click="isSuccessModalOpen = false"
                            class="w-full py-4 bg-gray-900 text-white rounded-2xl font-black uppercase tracking-[0.2em] text-[11px] shadow-lg hover:bg-black transition-all active:scale-95"
                        >
                            {{ successType === 'payment' ? 'Tutup & Lihat Detail' : 'Siap, Saya Tunggu!' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>

    <!-- Cancellation Modal -->
    <Teleport to="body">
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isCancelModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center px-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="isCancelModalOpen = false"></div>
                
                <div class="relative bg-white w-full max-w-sm rounded-[2.5rem] overflow-hidden shadow-2xl animate-in zoom-in-95 duration-300">
                    <div class="h-2 bg-[#E30613]"></div>
                    
                    <div class="p-8 space-y-6">
                        <div class="text-center space-y-2">
                            <div class="w-16 h-16 bg-red-50 text-[#E30613] rounded-full flex items-center justify-center mx-auto mb-4 border border-red-100">
                                <i class="fas fa-exclamation-circle text-2xl"></i>
                            </div>
                            <h2 class="text-xl font-black text-gray-900 tracking-tight">Batalkan Pesanan?</h2>
                            <p class="text-xs text-gray-500 font-bold leading-relaxed">Sayang sekali Anda harus membatalkan. Beritahu kami alasannya agar kami bisa lebih baik lagi.</p>
                        </div>

                        <div class="space-y-3">
                            <div v-for="reason in cancellationReasons" :key="reason"
                                @click="cancelForm.reason = reason"
                                class="p-3 rounded-xl border-2 transition-all cursor-pointer flex items-center justify-between"
                                :class="cancelForm.reason === reason ? 'border-[#E30613] bg-red-50/30' : 'border-gray-50 bg-gray-50/50 hover:border-gray-200'"
                            >
                                <span class="text-[11px] font-bold text-gray-700">{{ reason }}</span>
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                                    :class="cancelForm.reason === reason ? 'bg-[#E30613] border-[#E30613] text-white' : 'border-gray-300 bg-white'">
                                    <i v-if="cancelForm.reason === reason" class="fas fa-check text-[8px]"></i>
                                </div>
                            </div>
                            
                            <!-- Custom Reason Textarea -->
                            <textarea 
                                v-if="cancelForm.reason === 'Alasan lainnya'"
                                v-model="cancelForm.customReason"
                                rows="2"
                                placeholder="Tulis alasan Anda di sini..."
                                class="w-full border-2 border-gray-100 rounded-xl px-3 py-2 text-[11px] font-bold focus:ring-0 focus:border-[#E30613] outline-none transition-all mt-2"
                            ></textarea>
                        </div>

                        <div class="flex gap-3">
                            <button 
                                @click="isCancelModalOpen = false"
                                class="flex-1 py-3.5 bg-gray-100 text-gray-500 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-gray-200 transition-all active:scale-95"
                            >
                                Kembali
                            </button>
                            <button 
                                @click="submitPembatalan"
                                :disabled="!cancelForm.reason || (cancelForm.reason === 'Alasan lainnya' && !cancelForm.customReason) || cancelForm.processing"
                                class="flex-[2] py-3.5 bg-[#E30613] text-white rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-red-500/20 hover:bg-black transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <i v-if="cancelForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                Ya, Batalkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>
