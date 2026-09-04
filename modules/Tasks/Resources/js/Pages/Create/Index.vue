<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ title: '', description: '', due_at: '', status: 'open', assigned_to: null });
const submit = () => form.post(route('tasks.create.store'));
</script>
<template>
    <Head title="Create Task" />
    <AdminLayout title="Create Task">
        <form class="admin-card max-w-xl space-y-3" @submit.prevent="submit">
            <div><InputLabel value="Title" /><TextInput v-model="form.title" class="mt-1 block w-full" /><InputError :message="form.errors.title" /></div>
            <div><InputLabel value="Description" /><textarea v-model="form.description" class="mt-1 block w-full rounded-md border-gray-300 text-sm" rows="4" /></div>
            <div><InputLabel value="Due at" /><TextInput v-model="form.due_at" type="datetime-local" class="mt-1 block w-full" /></div>
            <PrimaryButton type="submit" :disabled="form.processing">Create</PrimaryButton>
        </form>
    </AdminLayout>
</template>