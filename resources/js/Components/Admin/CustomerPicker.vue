<script setup>
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import { computed, watch } from 'vue';

const props = defineProps({
    customers: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Select CRM customer…' },
});

const customerId = defineModel({ default: '' });
const emit = defineEmits(['picked']);

const options = computed(() =>
    props.customers.map((customer) => ({
        ...customer,
        label: customer.code ? `${customer.name} (${customer.code})` : customer.name,
    })),
);

watch(customerId, (id) => {
    const match = props.customers.find((customer) => String(customer.id) === String(id));
    emit('picked', match ?? null);
});
</script>

<template>
    <SearchableSelect
        v-model="customerId"
        :options="options"
        label-key="label"
        :placeholder="placeholder"
        search-placeholder="Search customers…"
    />
</template>
