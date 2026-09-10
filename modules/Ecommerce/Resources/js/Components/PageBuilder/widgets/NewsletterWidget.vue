<script setup>
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    widget: { type: Object, required: true },
    editing: { type: Boolean, default: false },
    pageSlug: { type: String, default: '' },
});

const form = useForm({
    type: 'newsletter',
    widget_id: props.widget.id,
    email: '',
});

const submit = () => {
    if (props.editing || !props.pageSlug) {
        return;
    }
    form.post(route('shop.pages.forms.store', props.pageSlug), {
        preserveScroll: true,
        onSuccess: () => form.reset('email'),
    });
};
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-gray-50 px-6 py-8 text-center">
        <h2 class="text-2xl font-semibold text-brand-navy">{{ widget.settings?.heading }}</h2>
        <form class="mx-auto mt-4 flex max-w-md gap-2" @submit.prevent="submit">
            <input
                v-model="form.email"
                type="email"
                required
                :placeholder="widget.settings?.placeholder || 'Email address'"
                class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm"
                :disabled="editing"
            />
            <button
                type="submit"
                class="rounded-lg bg-brand-orange px-4 py-2 text-sm font-semibold text-white hover:bg-brand-orange-dark disabled:opacity-50"
                :disabled="editing || form.processing"
            >
                {{ widget.settings?.button_label || 'Subscribe' }}
            </button>
        </form>
        <InputError class="mt-2" :message="form.errors.email" />
        <p v-if="editing" class="mt-2 text-xs text-gray-400">Forms submit on the published page.</p>
    </div>
</template>
