<script setup>
import Sidebar from '@/Components/Sidebar.vue';
import Topbar from '@/Components/Topbar.vue';

defineProps({
    title: {
        type: String,
        default: 'Admin Console'
    }
});
</script>

<template>
    <div class="min-h-screen flex bg-container font-sans text-text">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col ml-64 min-h-screen">
            <!-- Topbar (Sticky) -->
            <Topbar :title="title" />

            <!-- Page Content -->
            <main class="flex-1 p-8 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
