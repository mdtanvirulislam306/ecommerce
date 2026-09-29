<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DeleteConfirmModal from '@/Components/Admin/DeleteConfirmModal.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    roles: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const openModules = ref(new Set());

const catalog = computed(() => props.options.permission_catalog ?? []);

const form = useForm({
    name: '',
    slug: '',
    description: '',
    permissions: [],
});

const deleteForm = useForm({});

const selected = computed(() => new Set(form.permissions));

const keysOf = (areas) => areas.flatMap((area) => area.permissions.map((p) => p.key));

const countSelected = (keys) => keys.filter((key) => selected.value.has(key)).length;

const toggleKeys = (keys, checked) => {
    const next = new Set(form.permissions);
    keys.forEach((key) => (checked ? next.add(key) : next.delete(key)));
    form.permissions = [...next];
};

const toggleKey = (key) => toggleKeys([key], !selected.value.has(key));

const toggleModuleOpen = (code) => {
    const next = new Set(openModules.value);
    next.has(code) ? next.delete(code) : next.add(code);
    openModules.value = next;
};

const allKeys = computed(() => catalog.value.flatMap((mod) => keysOf(mod.areas)));

const permissionSummary = (row) => {
    const count = row.permissions?.length ?? 0;
    return count === 0 ? 'No access' : `${count} permission${count === 1 ? '' : 's'}`;
};

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    openModules.value = new Set();
    showModal.value = true;
};
const openEdit = (row) => {
    editing.value = row;
    form.name = row.name ?? '';
    form.slug = row.slug ?? '';
    form.description = row.description ?? '';
    form.permissions = [...(row.permissions ?? [])];
    form.clearErrors();
    openModules.value = new Set();
    showModal.value = true;
};
const submit = () => {
    if (editing.value) {
        form.put(route('settings.roles.update', editing.value.id), { preserveScroll: true, onSuccess: () => { showModal.value = false; } });
    } else {
        form.post(route('settings.roles.store'), { preserveScroll: true, onSuccess: () => { showModal.value = false; } });
    }
};
watch(search, (value) => {
    router.get(route('settings.roles.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>
<template>
    <Head title="Roles" />
    <AdminLayout title="Roles">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search…" />
            <PrimaryButton type="button" @click="openCreate">Add</PrimaryButton>
        </div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Name</th><th class="pb-2">Slug</th><th class="pb-2">Access</th><th class="pb-2">Users</th><th class="pb-2">Description</th><th class="pb-2" /></tr></thead>
                <tbody>
                    <tr v-for="row in roles.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2">{{ row.name ?? '—' }}</td>
                        <td class="py-2">{{ row.slug ?? '—' }}</td>
                        <td class="py-2 text-gray-600">{{ permissionSummary(row) }}</td>
                        <td class="py-2 text-gray-600">{{ row.users_count ?? 0 }}</td>
                        <td class="py-2">{{ row.description ?? '—' }}</td>
                        <td class="py-2 text-right space-x-2">
                            <button type="button" class="text-xs text-brand-orange" @click="openEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-600" @click="deleteTarget = row">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="roles" class="mt-4" />
        </section>
        <Modal :show="showModal" max-width="4xl" @close="showModal = false">
            <div class="p-6 space-y-3">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit role' : 'Add role' }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><InputLabel value="Name" /><TextInput v-model="form.name" type="text" class="mt-1 block w-full" /><InputError :message="form.errors.name" /></div>
                    <div><InputLabel value="Slug" /><TextInput v-model="form.slug" type="text" class="mt-1 block w-full" /><InputError :message="form.errors.slug" /></div>
                </div>
                <div><InputLabel value="Description" /><textarea v-model="form.description" class="mt-1 block w-full rounded-md border-gray-300 text-sm" rows="2" /><InputError :message="form.errors.description" /></div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <InputLabel value="Permissions" />
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-gray-500">{{ form.permissions.length }} / {{ allKeys.length }} selected</span>
                            <button type="button" class="text-brand-teal-dark hover:underline" @click="toggleKeys(allKeys, true)">Select all</button>
                            <button type="button" class="text-gray-500 hover:underline" @click="toggleKeys(allKeys, false)">Clear</button>
                        </div>
                    </div>
                    <p v-if="!catalog.length" class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-500">No modules are enabled in your plan.</p>
                    <div v-else class="max-h-[50vh] divide-y divide-gray-100 overflow-y-auto rounded-lg border border-gray-200">
                        <div v-for="mod in catalog" :key="mod.code">
                            <div class="flex items-center gap-3 bg-gray-50 px-3 py-2">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 text-brand-teal focus:ring-brand-teal"
                                    :checked="countSelected(keysOf(mod.areas)) === keysOf(mod.areas).length"
                                    :indeterminate="countSelected(keysOf(mod.areas)) > 0 && countSelected(keysOf(mod.areas)) < keysOf(mod.areas).length"
                                    @change="toggleKeys(keysOf(mod.areas), $event.target.checked)"
                                />
                                <button type="button" class="flex flex-1 items-center justify-between text-left text-sm font-medium text-brand-navy" @click="toggleModuleOpen(mod.code)">
                                    <span>{{ mod.name }}</span>
                                    <span class="flex items-center gap-2 text-xs font-normal text-gray-500">
                                        {{ countSelected(keysOf(mod.areas)) }} / {{ keysOf(mod.areas).length }}
                                        <svg class="h-4 w-4 transition-transform" :class="openModules.has(mod.code) ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </span>
                                </button>
                            </div>
                            <div v-if="openModules.has(mod.code)" class="divide-y divide-gray-50">
                                <div v-for="area in mod.areas" :key="area.code" class="flex flex-wrap items-start gap-x-4 gap-y-1 px-3 py-2 pl-10">
                                    <label class="flex w-44 shrink-0 items-center gap-2 text-sm text-gray-700">
                                        <input
                                            type="checkbox"
                                            class="rounded border-gray-300 text-brand-teal focus:ring-brand-teal"
                                            :checked="countSelected(keysOf([area])) === area.permissions.length"
                                            :indeterminate="countSelected(keysOf([area])) > 0 && countSelected(keysOf([area])) < area.permissions.length"
                                            @change="toggleKeys(keysOf([area]), $event.target.checked)"
                                        />
                                        {{ area.name }}
                                    </label>
                                    <div class="flex flex-1 flex-wrap gap-x-4 gap-y-1">
                                        <label v-for="permission in area.permissions" :key="permission.key" class="flex items-center gap-1.5 text-xs text-gray-600">
                                            <input
                                                type="checkbox"
                                                class="rounded border-gray-300 text-brand-teal focus:ring-brand-teal"
                                                :checked="selected.has(permission.key)"
                                                @change="toggleKey(permission.key)"
                                            />
                                            {{ permission.name }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <InputError :message="form.errors.permissions" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>
        <DeleteConfirmModal :show="!!deleteTarget" title="Delete this record?" :processing="deleteForm.processing" @close="deleteTarget = null" @confirm="deleteForm.delete(route('settings.roles.destroy', deleteTarget.id), { onSuccess: () => (deleteTarget = null) })" />
    </AdminLayout>
</template>
