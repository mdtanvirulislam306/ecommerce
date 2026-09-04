<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12">
        <Link :href="route('login')" class="mb-8">
            <img
                src="/images/logo.jpg"
                alt="Budget and Bazar"
                class="h-20 w-auto object-contain"
            />
        </Link>

        <div class="w-full max-w-md rounded-lg border border-gray-200 bg-white p-8 shadow-card">
            <slot />
        </div>
    </div>
</template>
