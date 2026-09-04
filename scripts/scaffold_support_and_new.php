<?php

declare(strict_types=1);

$root = dirname(__DIR__);

function put(string $path, string $contents): void
{
    $dir = dirname($path);
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($path, $contents);
    echo 'OK '.substr($path, strlen(dirname(__DIR__)) + 1).PHP_EOL;
}

function mig(string $body, string $table): string
{
    return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
{$body}
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};

PHP;
}

function provider(string $name, string $code): string
{
    return <<<PHP
<?php

namespace Modules\\{$name}\\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class {$name}ServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        \$this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }

    public static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'module:{$code}'])
            ->prefix('{$code}')
            ->name('{$code}.')
            ->group(__DIR__.'/../Routes/web.php');
    }
}

PHP;
}

function moduleJson(string $name, string $code, string $desc, bool $core = false): string
{
    $c = $core ? 'true' : 'false';

    return json_encode([
        'name' => $name,
        'code' => $code,
        'version' => '1.0.0',
        'description' => $desc,
        'is_core' => $core,
        'dependencies' => [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
}

function overviewVue(string $title): string
{
    return <<<VUE
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ stats: { type: Object, default: () => ({}) } });
</script>
<template>
    <Head title="{$title}" />
    <AdminLayout title="{$title}">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="(value, key) in stats" :key="key" class="admin-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ String(key).replaceAll('_', ' ') }}</p>
                <p class="mt-1 text-2xl font-semibold text-brand-navy">{{ value }}</p>
            </div>
        </div>
    </AdminLayout>
</template>
VUE;
}

function crudVue(string $title, string $prop, string $routePrefix, array $fields, array $columns, bool $checkbox = false): string
{
    $formDefaults = $openEdit = $inputs = [];
    foreach ($fields as $name => $meta) {
        $default = $meta['default'] ?? "''";
        $formDefaults[] = "    {$name}: {$default},";
        $openEdit[] = '    form.'.$name.' = '.($meta['edit'] ?? "row.{$name} ?? {$default}").';';
        $label = $meta['label'] ?? ucfirst(str_replace('_', ' ', $name));
        $type = $meta['type'] ?? 'text';
        if ($type === 'textarea') {
            $inputs[] = "<div><InputLabel value=\"{$label}\" /><textarea v-model=\"form.{$name}\" class=\"mt-1 block w-full rounded-md border-gray-300 text-sm\" rows=\"3\" /><InputError :message=\"form.errors.{$name}\" /></div>";
        } elseif ($type === 'checkbox') {
            $checkbox = true;
            $inputs[] = "<label class=\"flex items-center gap-2 text-sm\"><Checkbox v-model:checked=\"form.{$name}\" /> {$label}</label>";
        } elseif ($type === 'select') {
            $opts = $meta['options'] ?? '[]';
            $inputs[] = "<div><InputLabel value=\"{$label}\" /><select v-model=\"form.{$name}\" class=\"mt-1 block w-full rounded-md border-gray-300 text-sm\"><option value=\"\">—</option><option v-for=\"opt in {$opts}\" :key=\"opt.value ?? opt.id ?? opt\" :value=\"opt.value ?? opt.id ?? opt\">{{ opt.label ?? opt.name ?? opt }}</option></select><InputError :message=\"form.errors.{$name}\" /></div>";
        } elseif ($type === 'file') {
            $inputs[] = "<div><InputLabel value=\"{$label}\" /><input type=\"file\" class=\"mt-1 block w-full text-sm\" @change=\"form.{$name} = \$event.target.files[0]\" /><InputError :message=\"form.errors.{$name}\" /></div>";
        } else {
            $inputType = in_array($type, ['number', 'date', 'time', 'datetime-local', 'email'], true) ? $type : 'text';
            $inputs[] = "<div><InputLabel value=\"{$label}\" /><TextInput v-model=\"form.{$name}\" type=\"{$inputType}\" class=\"mt-1 block w-full\" /><InputError :message=\"form.errors.{$name}\" /></div>";
        }
    }
    $th = $td = '';
    foreach ($columns as $col => $label) {
        $th .= "<th class=\"pb-2\">{$label}</th>";
        $td .= "<td class=\"py-2\">{{ row.{$col} ?? '—' }}</td>";
    }
    $ci = $checkbox ? "import Checkbox from '@/Components/Checkbox.vue';\n" : '';
    $fd = implode("\n", $formDefaults);
    $oe = implode("\n", $openEdit);
    $in = implode("\n", $inputs);
    $forceFormData = str_contains(json_encode($fields), 'file') ? "\nform.forceFormData = true;" : '';

    return <<<VUE
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
{$ci}import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    {$prop}: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const search = ref(props.filters.search ?? '');
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);

const form = useForm({
{$fd}
});{$forceFormData}

const deleteForm = useForm({});

const openCreate = () => { editing.value = null; form.reset(); form.clearErrors(); showModal.value = true; };
const openEdit = (row) => {
    editing.value = row;
{$oe}
    form.clearErrors();
    showModal.value = true;
};
const submit = () => {
    if (editing.value) {
        form.put(route('{$routePrefix}.update', editing.value.id), { preserveScroll: true, onSuccess: () => { showModal.value = false; } });
    } else {
        form.post(route('{$routePrefix}.store'), { preserveScroll: true, onSuccess: () => { showModal.value = false; } });
    }
};
watch(search, (value) => {
    router.get(route('{$routePrefix}.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>
<template>
    <Head title="{$title}" />
    <AdminLayout title="{$title}">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search…" />
            <PrimaryButton type="button" @click="openCreate">Add</PrimaryButton>
        </div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr>{$th}<th class="pb-2" /></tr></thead>
                <tbody>
                    <tr v-for="row in {$prop}.data" :key="row.id" class="border-t border-gray-50">
                        {$td}
                        <td class="py-2 text-right space-x-2">
                            <button type="button" class="text-xs text-brand-orange" @click="openEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-600" @click="deleteTarget = row">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="{$prop}" class="mt-4" />
        </section>
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6 space-y-3">
                <h2 class="text-lg font-semibold text-brand-navy">{{ editing ? 'Edit' : 'Add' }}</h2>
                {$in}
                <div class="flex justify-end gap-2 pt-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>
        <DeleteConfirmModal :show="!!deleteTarget" title="Delete this record?" :processing="deleteForm.processing" @close="deleteTarget = null" @confirm="deleteForm.delete(route('{$routePrefix}.destroy', deleteTarget.id), { onSuccess: () => (deleteTarget = null) })" />
    </AdminLayout>
</template>
VUE;
}

function settingsFormVue(string $title, string $routeName, array $fields): string
{
    $defaults = $inputs = [];
    foreach ($fields as $name => $label) {
        $defaults[] = "    {$name}: props.settings?.{$name} ?? '',";
        $inputs[] = "<div><InputLabel value=\"{$label}\" /><TextInput v-model=\"form.{$name}\" class=\"mt-1 block w-full\" /><InputError :message=\"form.errors.{$name}\" /></div>";
    }

    return <<<VUE
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const props = defineProps({ settings: { type: Object, default: () => ({}) } });
const flash = computed(() => usePage().props.flash);
const form = useForm({
{$defaults}
});
const submit = () => form.put(route('{$routeName}'), { preserveScroll: true });
</script>
<template>
    <Head title="{$title}" />
    <AdminLayout title="{$title}">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <form class="admin-card max-w-2xl space-y-4" @submit.prevent="submit">
            {$inputs}
            <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
        </form>
    </AdminLayout>
</template>
VUE;
}

/**
 * Write a lean CRUD entity (model+service+controller+requests+vue).
 *
 * @param  array<string,mixed>  $c
 */
function entity(string $root, array $c): void
{
    $mod = $c['module'];
    $code = $c['code'];
    $entity = $c['entity'];
    $plural = $c['plural'];
    $table = $c['table'];
    $route = $c['route'];
    $prop = $c['prop'];
    $param = $c['param'] ?? lcfirst($entity);
    $fillable = $c['fillable'];
    $casts = $c['casts'] ?? [];
    $search = $c['search'];
    $rules = $c['rules'];
    $rulesUpdate = $c['rules_update'] ?? $rules;
    $create = $c['create'] ?? [];
    $with = $c['with'] ?? null;
    $formatExtra = $c['format_extra'] ?? '';
    $formOptions = $c['form_options'] ?? null;
    $relationMethods = $c['relation_methods'] ?? '';
    $relationImports = $c['relation_imports'] ?? '';
    $columns = $c['columns'];
    $fields = $c['fields'];

    $base = "{$root}/modules/{$mod}";
    $fillStr = implode(",\n        ", array_map(fn ($f) => "'{$f}'", $fillable));
    $castBlock = '';
    if ($casts) {
        $lines = [];
        foreach ($casts as $k => $v) {
            $lines[] = "            '{$k}' => {$v},";
        }
        $castBlock = "\n\n    protected function casts(): array\n    {\n        return [\n".implode("\n", $lines)."\n        ];\n    }";
    }

    put("{$base}/Models/{$entity}.php", <<<PHP
<?php

namespace Modules\\{$mod}\\Models;

use Illuminate\Database\Eloquent\Model;
{$relationImports}
class {$entity} extends Model
{
    protected \$table = '{$table}';

    protected \$fillable = [
        {$fillStr},
    ];{$castBlock}
{$relationMethods}}

PHP);

    $ruleFn = function (array $rules): string {
        $o = [];
        foreach ($rules as $f => $r) {
            $o[] = "            '{$f}' => {$r},";
        }

        return implode("\n", $o);
    };

    foreach ([['Store', $rules], ['Update', $rulesUpdate]] as [$prefix, $r]) {
        put("{$base}/Http/Requests/{$prefix}{$entity}Request.php", <<<PHP
<?php

namespace Modules\\{$mod}\\Http\\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$prefix}{$entity}Request extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
{$ruleFn($r)}
        ];
    }
}

PHP);
    }

    $searchOrs = [];
    foreach ($search as $i => $col) {
        $m = $i === 0 ? 'where' : 'orWhere';
        $searchOrs[] = "\$inner->{$m}('{$col}', 'like', \"%{\$search}%\");";
    }
    $searchBody = implode("\n                ", $searchOrs);

    $createLines = [];
    foreach ($fillable as $f) {
        $createLines[] = isset($create[$f])
            ? "            '{$f}' => {$create[$f]},"
            : "            '{$f}' => \$data['{$f}'] ?? null,";
    }
    $updateLines = [];
    foreach ($fillable as $f) {
        $updateLines[] = "            '{$f}' => array_key_exists('{$f}', \$data) ? \$data['{$f}'] : \$row->{$f},";
    }
    $formatLines = ["            'id' => \$row->id,"];
    foreach ($fillable as $f) {
        if (isset($casts[$f]) && str_contains((string) $casts[$f], 'date') && ! str_contains((string) $casts[$f], 'datetime')) {
            $formatLines[] = "            '{$f}' => \$row->{$f}?->toDateString(),";
        } elseif (isset($casts[$f]) && str_contains((string) $casts[$f], 'datetime')) {
            $formatLines[] = "            '{$f}' => \$row->{$f}?->toIso8601String(),";
        } else {
            $formatLines[] = "            '{$f}' => \$row->{$f},";
        }
    }
    if ($formatExtra) {
        $formatLines[] = $formatExtra;
    }

    $withLine = $with ? "\n            ->with([{$with}])" : '';
    $formOptMethod = $formOptions
        ? "\n\n    public function formOptions(): array\n    {\n{$formOptions}\n    }"
        : '';

    put("{$base}/Services/{$entity}Service.php", <<<PHP
<?php

namespace Modules\\{$mod}\\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\\{$mod}\\Models\\{$entity};

class {$entity}Service extends Service
{
    public function listPaginated(?string \$search = null, int \$perPage = 25): LengthAwarePaginator
    {
        \$perPage = in_array(\$perPage, [10, 25, 50, 100], true) ? \$perPage : 25;

        return {$entity}::query(){$withLine}
            ->when(\$search, fn (\$q, \$search) => \$q->where(function (\$inner) use (\$search) {
                {$searchBody}
            }))
            ->orderByDesc('id')
            ->paginate(\$perPage)
            ->withQueryString()
            ->through(fn ({$entity} \$row) => \$this->format(\$row));
    }

    /** @param  array<string, mixed>  \$data */
    public function create(array \$data): {$entity}
    {
        return {$entity}::query()->create([
{$createLines}
        ]);
    }

    /** @param  array<string, mixed>  \$data */
    public function update({$entity} \$row, array \$data): {$entity}
    {
        \$row->update([
{$updateLines}
        ]);

        return \$row->fresh();
    }

    public function delete({$entity} \$row): void
    {
        \$row->delete();
    }

    /** @return array<string, mixed> */
    public function format({$entity} \$row): array
    {
        return [
{$formatLines}
        ];
    }{$formOptMethod}
}

PHP);

    put("{$base}/Http/Controllers/{$entity}Controller.php", <<<PHP
<?php

namespace Modules\\{$mod}\\Http\\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\\{$mod}\\Http\\Requests\\Store{$entity}Request;
use Modules\\{$mod}\\Http\\Requests\\Update{$entity}Request;
use Modules\\{$mod}\\Models\\{$entity};
use Modules\\{$mod}\\Services\\{$entity}Service;

class {$entity}Controller extends Controller
{
    public function index(Request \$request, {$entity}Service \$svc): Response
    {
        return Inertia::render('{$mod}/{$plural}/Index', [
            '{$prop}' => \$svc->listPaginated(
                \$request->string('search')->trim()->toString() ?: null,
                \$request->integer('per_page', 25),
            ),
            'filters' => [
                'search' => \$request->string('search')->toString(),
                'per_page' => \$request->integer('per_page', 25),
            ],
            'options' => method_exists(\$svc, 'formOptions') ? \$svc->formOptions() : [],
        ]);
    }

    public function store(Store{$entity}Request \$request, {$entity}Service \$svc): RedirectResponse
    {
        \$svc->create(\$request->validated());

        return back()->with('success', 'Saved.');
    }

    public function update(Update{$entity}Request \$request, {$entity} \${$param}, {$entity}Service \$svc): RedirectResponse
    {
        \$svc->update(\${$param}, \$request->validated());

        return back()->with('success', 'Updated.');
    }

    public function destroy({$entity} \${$param}, {$entity}Service \$svc): RedirectResponse
    {
        \$svc->delete(\${$param});

        return back()->with('success', 'Deleted.');
    }
}

PHP);

    put("{$base}/Resources/js/Pages/{$plural}/Index.vue", crudVue($plural, $prop, "{$code}.{$route}", $fields, $columns));
}

// ===================== SUPPORT =====================
$sup = "{$root}/modules/Support";

put("{$sup}/Database/migrations/2026_09_03_920010_create_support_categories_table.php", mig(<<<'PHP'
        Schema::create('support_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 80)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
PHP, 'support_categories'));

put("{$sup}/Database/migrations/2026_09_03_920011_create_support_canned_responses_table.php", mig(<<<'PHP'
        Schema::create('support_canned_responses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->foreignId('category_id')->nullable()->constrained('support_categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
PHP, 'support_canned_responses'));

put("{$sup}/Database/migrations/2026_09_03_920012_add_category_id_to_support_tickets.php", <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('priority')->constrained('support_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};

PHP);

entity($root, [
    'module' => 'Support', 'code' => 'support', 'entity' => 'SupportCategory', 'plural' => 'Categories',
    'table' => 'support_categories', 'route' => 'categories', 'prop' => 'categories', 'param' => 'supportCategory',
    'fillable' => ['name', 'slug', 'is_active'],
    'casts' => ['is_active' => "'boolean'"],
    'search' => ['name', 'slug'],
    'rules' => [
        'name' => "['required', 'string', 'max:255']",
        'slug' => "['required', 'string', 'max:80', 'unique:support_categories,slug']",
        'is_active' => "['boolean']",
    ],
    'rules_update' => [
        'name' => "['required', 'string', 'max:255']",
        'slug' => "['required', 'string', 'max:80']",
        'is_active' => "['boolean']",
    ],
    'create' => ['is_active' => "\$data['is_active'] ?? true"],
    'columns' => ['name' => 'Name', 'slug' => 'Slug', 'is_active' => 'Active'],
    'fields' => [
        'name' => ['label' => 'Name'],
        'slug' => ['label' => 'Slug'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

entity($root, [
    'module' => 'Support', 'code' => 'support', 'entity' => 'CannedResponse', 'plural' => 'CannedResponses',
    'table' => 'support_canned_responses', 'route' => 'canned-responses', 'prop' => 'responses', 'param' => 'cannedResponse',
    'fillable' => ['title', 'body', 'category_id', 'is_active'],
    'casts' => ['is_active' => "'boolean'"],
    'search' => ['title', 'body'],
    'rules' => [
        'title' => "['required', 'string', 'max:255']",
        'body' => "['required', 'string']",
        'category_id' => "['nullable', 'exists:support_categories,id']",
        'is_active' => "['boolean']",
    ],
    'create' => ['is_active' => "\$data['is_active'] ?? true"],
    'with' => "'category:id,name'",
    'relation_imports' => "use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;\n",
    'relation_methods' => "\n    public function category(): BelongsTo\n    {\n        return \$this->belongsTo(SupportCategory::class, 'category_id');\n    }\n",
    'format_extra' => "            'category_name' => \$row->category?->name,",
    'form_options' => "        return ['categories' => \\Modules\\Support\\Models\\SupportCategory::query()->orderBy('name')->get(['id','name'])->map(fn (\$c) => ['id' => \$c->id, 'name' => \$c->name])->all()];",
    'columns' => ['title' => 'Title', 'category_name' => 'Category', 'is_active' => 'Active'],
    'fields' => [
        'title' => ['label' => 'Title'],
        'body' => ['label' => 'Body', 'type' => 'textarea'],
        'category_id' => ['label' => 'Category', 'type' => 'select', 'options' => 'options.categories', 'default' => 'null'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

// Extend TicketService for my/unassigned filters - write dedicated controllers
put("{$sup}/Http/Controllers/MyTicketController.php", <<<'PHP'
<?php

namespace Modules\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Support\Enums\TicketStatus;
use Modules\Support\Services\TicketService;

class MyTicketController extends Controller
{
    public function index(Request $request, TicketService $tickets): Response
    {
        return Inertia::render('Support/Tickets/Index', [
            'tickets' => $tickets->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->filled('status') ? TicketStatus::tryFrom($request->string('status')->toString()) : null,
                $request->integer('per_page', 25),
                createdBy: $request->user()?->id,
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
            'statuses' => collect(TicketStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ])->all(),
            'pageTitle' => 'My Tickets',
        ]);
    }
}

PHP);

put("{$sup}/Http/Controllers/UnassignedTicketController.php", <<<'PHP'
<?php

namespace Modules\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Support\Enums\TicketStatus;
use Modules\Support\Services\TicketService;

class UnassignedTicketController extends Controller
{
    public function index(Request $request, TicketService $tickets): Response
    {
        return Inertia::render('Support/Tickets/Index', [
            'tickets' => $tickets->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->filled('status') ? TicketStatus::tryFrom($request->string('status')->toString()) : null,
                $request->integer('per_page', 25),
                unassignedOnly: true,
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
            'statuses' => collect(TicketStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ])->all(),
            'pageTitle' => 'Unassigned Tickets',
        ]);
    }
}

PHP);

put("{$sup}/Http/Controllers/SupportReportController.php", <<<'PHP'
<?php

namespace Modules\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SupportReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Support/Reports/Index', [
            'stats' => [
                'total' => DB::table('support_tickets')->count(),
                'open' => DB::table('support_tickets')->where('status', 'open')->count(),
                'unassigned' => DB::table('support_tickets')->whereNull('assigned_to')->count(),
                'resolved' => DB::table('support_tickets')->where('status', 'resolved')->count(),
                'categories' => DB::table('support_categories')->count(),
                'canned_responses' => DB::table('support_canned_responses')->count(),
            ],
        ]);
    }
}

PHP);

put("{$sup}/Resources/js/Pages/Reports/Index.vue", overviewVue('Support Reports'));

put("{$sup}/Routes/web.php", <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use Modules\Support\Http\Controllers\CannedResponseController;
use Modules\Support\Http\Controllers\MyTicketController;
use Modules\Support\Http\Controllers\SupportCategoryController;
use Modules\Support\Http\Controllers\SupportOverviewController;
use Modules\Support\Http\Controllers\SupportReportController;
use Modules\Support\Http\Controllers\TicketController;
use Modules\Support\Http\Controllers\UnassignedTicketController;

Route::get('/overview', [SupportOverviewController::class, 'index'])->name('overview');
Route::get('/reports', [SupportReportController::class, 'index'])->name('reports');
Route::get('/my-tickets', [MyTicketController::class, 'index'])->name('my-tickets');
Route::get('/unassigned', [UnassignedTicketController::class, 'index'])->name('unassigned');

Route::prefix('tickets')->name('tickets.')->group(function () {
    Route::get('/', [TicketController::class, 'index'])->name('index');
    Route::post('/', [TicketController::class, 'store'])->name('store');
    Route::put('/{supportTicket}', [TicketController::class, 'update'])->name('update');
    Route::delete('/{supportTicket}', [TicketController::class, 'destroy'])->name('destroy');
});

Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [SupportCategoryController::class, 'index'])->name('index');
    Route::post('/', [SupportCategoryController::class, 'store'])->name('store');
    Route::put('/{supportCategory}', [SupportCategoryController::class, 'update'])->name('update');
    Route::delete('/{supportCategory}', [SupportCategoryController::class, 'destroy'])->name('destroy');
});

Route::prefix('canned-responses')->name('canned-responses.')->group(function () {
    Route::get('/', [CannedResponseController::class, 'index'])->name('index');
    Route::post('/', [CannedResponseController::class, 'store'])->name('store');
    Route::put('/{cannedResponse}', [CannedResponseController::class, 'update'])->name('update');
    Route::delete('/{cannedResponse}', [CannedResponseController::class, 'destroy'])->name('destroy');
});

PHP);

put("{$sup}/module.json", moduleJson('Support', 'support', 'Support tickets, categories, and canned responses'));

echo "Support scaffolded\n";
