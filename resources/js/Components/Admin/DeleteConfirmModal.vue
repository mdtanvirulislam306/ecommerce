<script setup>
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Delete this item?' },
    message: {
        type: String,
        default: 'This action cannot be undone. The item will be permanently removed.',
    },
    itemName: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Delete' },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <Modal :show="show" max-width="sm" @close="emit('close')">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-50 ring-1 ring-red-100"
                >
                    <svg
                        class="h-5 w-5 text-red-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-brand-navy">
                        {{ title }}
                    </h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-gray-500">
                        {{ message }}
                    </p>
                    <p
                        v-if="itemName"
                        class="mt-3 truncate rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-brand-navy"
                    >
                        {{ itemName }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
                <SecondaryButton
                    type="button"
                    class="w-full sm:w-auto"
                    :disabled="processing"
                    @click="emit('close')"
                >
                    Cancel
                </SecondaryButton>
                <DangerButton
                    type="button"
                    class="w-full sm:w-auto"
                    :class="{ 'opacity-60': processing }"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    {{ processing ? 'Deleting…' : confirmLabel }}
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
