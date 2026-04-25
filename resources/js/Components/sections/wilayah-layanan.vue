<script setup>
import { ref } from 'vue';

const activeOutletIndex = ref(0);

const outlets = [
    {
        name: 'HiWash Dr. Sutomo',
        address: 'Jl. Dr. Sutomo, Samarinda',
        description: 'Pusat Operasional Utama',
        mapUrl: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.67834773323!2d117.14464117472343!3d-0.4799365995153566!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2df67ff6ee82193d%3A0x72bbf60022c3f827!2sHiWash%20Laundry%20Dr.%20Sutomo!5e0!3m2!1sid!2sid!4v1776516347085!5m2!1sid!2sid'
    },
    {
        name: 'HiWash AWS',
        address: 'Jl. Abdul Wahab Syahranie, Samarinda',
        description: 'Cabang Strategis Utara',
        mapUrl: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.6883489149845!2d117.13731307472358!3d-0.4624727995329888!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2df6792f4fdd2749%3A0xaa5d7877bc9497ba!2sHiWash%20Laundry%20AWS!5e0!3m2!1sid!2sid!4v1777086082992!5m2!1sid!2sid'
    },
    {
        name: 'HiWash Suryanata',
        address: 'Jl. Pangeran Suryanata, Samarinda',
        description: 'Cabang Wilayah Barat',
        mapUrl: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.68450713449!2d117.11682587714017!3d-0.46925809830433507!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2df67f6c9f3df755%3A0xad48184fc3d816d5!2sHiWash%20Laundry%20Suryanata!5e0!3m2!1sid!2sid!4v1777086051004!5m2!1sid!2sid'
    }
];
</script>

<template>
<section id="area" class="py-20 px-6 bg-gray-50">
    <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center">

        <!-- LEFT CONTENT -->
        <div>
            <!-- Title -->
            <p class="text-xs font-semibold text-primary uppercase tracking-widest mb-3">
                Area Layanan
            </p>

            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 leading-tight mb-5">
                Cakupan Operasional <br />
                <span class="text-primary">Laundry Kami</span>
            </h2>

            <p class="text-gray-500 mb-8 leading-relaxed max-w-lg">
                Kami melayani wilayah <span class="font-semibold text-gray-800">Samarinda</span>
                dengan sistem antar-jemput yang cepat melalui 3 outlet strategis kami.
            </p>

            <!-- TABS LIST -->
            <div class="space-y-3">
                <button
                    v-for="(outlet, index) in outlets"
                    :key="index"
                    @click="activeOutletIndex = index"
                    class="w-full text-left p-4 rounded-2xl transition-all duration-300 border flex items-center gap-4 group"
                    :class="activeOutletIndex === index
                        ? 'bg-white border-primary shadow-md'
                        : 'bg-transparent border-gray-100 hover:border-gray-200 hover:bg-white/50'"
                >
                    <div
                        class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                        :class="activeOutletIndex === index ? 'bg-primary text-white' : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200'"
                    >
                        <i class="fas fa-location-dot"></i>
                    </div>
                    <div>
                        <p
                            class="font-bold text-sm transition-colors"
                            :class="activeOutletIndex === index ? 'text-gray-900' : 'text-gray-600'"
                        >
                            {{ outlet.name }}
                        </p>
                        <p class="text-[11px] text-gray-400 mt-0.5">
                            {{ outlet.address }}
                        </p>
                    </div>
                    <div v-if="activeOutletIndex === index" class="ml-auto text-primary animate-pulse">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </div>
                </button>
            </div>
        </div>

        <!-- RIGHT MAP -->
        <div class="relative">
            <div class="rounded-2xl overflow-hidden border border-gray-200 shadow-xl bg-white p-2">
                <div class="rounded-xl overflow-hidden h-[300px] sm:h-[420px] relative bg-gray-100">
                    <!-- Loading overlay for smooth transition -->
                    <div class="absolute inset-0 flex items-center justify-center bg-gray-50/50 z-0">
                        <div class="w-8 h-8 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
                    </div>

                    <iframe
                        :key="outlets[activeOutletIndex].mapUrl"
                        :src="outlets[activeOutletIndex].mapUrl"
                        class="w-full h-full border-0 relative z-10"
                        loading="lazy"
                    ></iframe>
                </div>
            </div>

            <!-- SMALL LABEL -->
            <div class="absolute -top-3 -right-3 bg-primary text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg z-20">
                {{ outlets[activeOutletIndex].description }}
            </div>
        </div>

    </div>
</section>
</template>
