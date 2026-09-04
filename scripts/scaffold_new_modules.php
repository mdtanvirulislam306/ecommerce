<?php

declare(strict_types=1);

/**
 * Scaffold Settings, Reports, Workflow, Tasks, Notifications, Files modules.
 */
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

function crudVue(string $title, string $prop, string $routePrefix, array $fields, array $columns): string
{
    $formDefaults = $openEdit = $inputs = [];
    $checkbox = false;
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
            $inputs[] = "<div><InputLabel value=\"{$label}\" /><input type=\"file\" class=\"mt-1 block w-full text-sm\" @change=\"onFile(\$event, '{$name}')\" /><InputError :message=\"form.errors.{$name}\" /></div>";
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
    $hasFile = str_contains(json_encode($fields), '"file"');
    $fileHelper = $hasFile ? "\nconst onFile = (e, key) => { form[key] = e.target.files[0]; };\n" : '';
    $postOpts = $hasFile ? ', { forceFormData: true, preserveScroll: true, onSuccess: () => { showModal.value = false; } }' : ', { preserveScroll: true, onSuccess: () => { showModal.value = false; } }';

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
});
{$fileHelper}
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
        form.put(route('{$routePrefix}.update', editing.value.id){$postOpts});
    } else {
        form.post(route('{$routePrefix}.store'){$postOpts});
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
    $d = implode("\n", $defaults);
    $i = implode("\n", $inputs);

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
{$d}
});
const submit = () => form.put(route('{$routeName}'), { preserveScroll: true });
</script>
<template>
    <Head title="{$title}" />
    <AdminLayout title="{$title}">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <form class="admin-card max-w-2xl space-y-4" @submit.prevent="submit">
            {$i}
            <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
        </form>
    </AdminLayout>
</template>
VUE;
}

/**
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
    $customCreate = $c['custom_create'] ?? null;
    $skipUpdateVue = $c['skip_update'] ?? false;

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
    $createBody = implode("\n", $createLines);

    $updateLines = [];
    foreach ($fillable as $f) {
        $updateLines[] = "            '{$f}' => array_key_exists('{$f}', \$data) ? \$data['{$f}'] : \$row->{$f},";
    }
    $updateBody = implode("\n", $updateLines);

    $formatLines = ["            'id' => \$row->id,"];
    foreach ($fillable as $f) {
        if (isset($casts[$f]) && str_contains((string) $casts[$f], "'date'") && ! str_contains((string) $casts[$f], 'datetime')) {
            $formatLines[] = "            '{$f}' => \$row->{$f}?->toDateString(),";
        } elseif (isset($casts[$f]) && str_contains((string) $casts[$f], 'datetime')) {
            $formatLines[] = "            '{$f}' => \$row->{$f}?->toIso8601String(),";
        } else {
            $formatLines[] = "            '{$f}' => \$row->{$f},";
        }
    }
    if ($formatExtra !== '') {
        $formatLines[] = $formatExtra;
    }
    $formatBody = implode("\n", $formatLines);

    $withLine = $with ? "\n            ->with([{$with}])" : '';
    $formOptMethod = $formOptions
        ? "\n\n    public function formOptions(): array\n    {\n{$formOptions}\n    }"
        : '';

    $createMethod = $customCreate ?? <<<PHP
    /** @param  array<string, mixed>  \$data */
    public function create(array \$data): {$entity}
    {
        return {$entity}::query()->create([
{$createBody}
        ]);
    }
PHP;

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

{$createMethod}

    /** @param  array<string, mixed>  \$data */
    public function update({$entity} \$row, array \$data): {$entity}
    {
        \$row->update([
{$updateBody}
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
{$formatBody}
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

function routeCrud(string $ctrl, string $prefix, string $param): string
{
    return <<<PHP
Route::prefix('{$prefix}')->name('{$prefix}.')->group(function () {
    Route::get('/', [{$ctrl}::class, 'index'])->name('index');
    Route::post('/', [{$ctrl}::class, 'store'])->name('store');
    Route::put('/{{$param}}', [{$ctrl}::class, 'update'])->name('update');
    Route::delete('/{{$param}}', [{$ctrl}::class, 'destroy'])->name('destroy');
});

PHP;
}

// ===================== SETTINGS =====================
$set = "{$root}/modules/Settings";
put("{$set}/module.json", moduleJson('Settings', 'settings', 'Shop settings, users, roles, and configuration', true));
put("{$set}/Providers/SettingsServiceProvider.php", provider('Settings', 'settings'));

put("{$set}/Database/migrations/2026_09_03_930001_create_setting_values_table.php", mig(<<<'PHP'
        Schema::create('setting_values', function (Blueprint $table) {
            $table->id();
            $table->string('group', 60)->default('general');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
            $table->index('group');
        });
PHP, 'setting_values'));

put("{$set}/Database/migrations/2026_09_03_930002_create_roles_tables.php", <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug', 80)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};

PHP);

put("{$set}/Database/migrations/2026_09_03_930003_create_numbering_series_table.php", mig(<<<'PHP'
        Schema::create('numbering_series', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->string('prefix', 20)->nullable();
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedTinyInteger('pad_length')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
PHP, 'numbering_series'));

put("{$set}/Database/migrations/2026_09_03_930004_create_tax_rates_table.php", mig(<<<'PHP'
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->decimal('rate', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
PHP, 'tax_rates'));

put("{$set}/Database/migrations/2026_09_03_930005_create_currencies_table.php", mig(<<<'PHP'
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->string('symbol', 10)->nullable();
            $table->decimal('exchange_rate', 14, 6)->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
PHP, 'currencies'));

put("{$set}/Database/migrations/2026_09_03_930006_create_payment_methods_table.php", mig(<<<'PHP'
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });
PHP, 'payment_methods'));

put("{$set}/Database/migrations/2026_09_03_930007_create_shipping_methods_table.php", mig(<<<'PHP'
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->decimal('flat_rate', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
PHP, 'shipping_methods'));

put("{$set}/Database/migrations/2026_09_03_930008_create_audit_logs_table.php", mig(<<<'PHP'
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
PHP, 'audit_logs'));

// SettingValue model + service
put("{$set}/Models/SettingValue.php", <<<'PHP'
<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SettingValue extends Model
{
    protected $fillable = ['group', 'key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = Cache::remember("setting_value:{$key}", 300, fn () => static::query()->where('key', $key)->first());
        if ($row === null) {
            return $default;
        }
        $decoded = json_decode((string) $row->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $row->value;
    }

    public static function setValue(string $key, mixed $value, string $group = 'general'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'value' => is_string($value) ? $value : json_encode($value)],
        );
        Cache::forget("setting_value:{$key}");
    }

    public static function groupValues(string $group): array
    {
        return static::query()->where('group', $group)->pluck('value', 'key')->map(function ($value) {
            $decoded = json_decode((string) $value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        })->all();
    }
}

PHP);

put("{$set}/Services/SettingService.php", <<<'PHP'
<?php

namespace Modules\Settings\Services;

use App\Core\Support\Service;
use Modules\Settings\Models\SettingValue;

class SettingService extends Service
{
    public function getGroup(string $group): array
    {
        return SettingValue::groupValues($group);
    }

    /** @param  array<string, mixed>  $data */
    public function saveGroup(string $group, array $data): void
    {
        foreach ($data as $key => $value) {
            SettingValue::setValue((string) $key, $value, $group);
        }
    }
}

PHP);

// Generic settings page controller
put("{$set}/Http/Controllers/GeneralSettingsController.php", <<<'PHP'
<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\SettingService;

class GeneralSettingsController extends Controller
{
    public function edit(SettingService $settings): Response
    {
        return Inertia::render('Settings/General/Index', [
            'settings' => array_merge([
                'shop_name' => '',
                'timezone' => 'Asia/Dhaka',
                'locale' => 'en',
                'default_currency' => 'BDT',
            ], $settings->getGroup('general')),
        ]);
    }

    public function update(Request $request, SettingService $settings): RedirectResponse
    {
        $data = $request->validate([
            'shop_name' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'locale' => ['nullable', 'string', 'max:20'],
            'default_currency' => ['nullable', 'string', 'max:10'],
        ]);
        $settings->saveGroup('general', $data);

        return back()->with('success', 'General settings saved.');
    }
}

PHP);

put("{$set}/Resources/js/Pages/General/Index.vue", settingsFormVue('General Settings', 'settings.general.update', [
    'shop_name' => 'Shop name',
    'timezone' => 'Timezone',
    'locale' => 'Locale',
    'default_currency' => 'Default currency',
]));

put("{$set}/Http/Controllers/BusinessProfileController.php", <<<'PHP'
<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\SettingService;

class BusinessProfileController extends Controller
{
    public function edit(SettingService $settings): Response
    {
        return Inertia::render('Settings/Business/Company', [
            'settings' => array_merge([
                'company_name' => '',
                'legal_name' => '',
                'email' => '',
                'phone' => '',
                'address' => '',
                'tax_id' => '',
            ], $settings->getGroup('business')),
        ]);
    }

    public function update(Request $request, SettingService $settings): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:1000'],
            'tax_id' => ['nullable', 'string', 'max:80'],
        ]);
        $settings->saveGroup('business', $data);

        return back()->with('success', 'Business profile saved.');
    }
}

PHP);

put("{$set}/Resources/js/Pages/Business/Company.vue", settingsFormVue('Company Profile', 'settings.business.company.update', [
    'company_name' => 'Company name',
    'legal_name' => 'Legal name',
    'email' => 'Email',
    'phone' => 'Phone',
    'address' => 'Address',
    'tax_id' => 'Tax ID',
]));

// Simple placeholder pages for branches/regions/areas
foreach ([
    ['Branches', 'branches', 'Branches'],
    ['Regions', 'regions', 'Regions'],
    ['Areas', 'areas', 'Areas'],
    ['Warehouses', 'warehouses', 'Warehouses'],
    ['Modules', 'modules', 'Module Manager'],
    ['Subscription', 'subscription', 'Subscription & Billing'],
    ['DocumentTemplates', 'document-templates', 'Document Templates'],
    ['System', 'system', 'System Settings'],
    ['Notifications', 'notifications', 'Notification Settings'],
] as [$folder, $route, $title]) {
    put("{$set}/Resources/js/Pages/{$folder}/Index.vue", <<<VUE
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>
<template>
    <Head title="{$title}" />
    <AdminLayout title="{$title}">
        <div class="admin-card space-y-3">
            <p class="text-sm text-gray-600">{$title} is managed here. Core configuration lives under General and Business profile.</p>
            <Link href="/admin/billing/plans" class="text-sm text-brand-orange">Open plans & modules</Link>
        </div>
    </AdminLayout>
</template>
VUE);
    put("{$set}/Http/Controllers/{$folder}Controller.php", <<<PHP
<?php

namespace Modules\\Settings\\Http\\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class {$folder}Controller extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/{$folder}/Index');
    }
}

PHP);
}

put("{$set}/Http/Controllers/UserListController.php", <<<'PHP'
<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\UserListService;

class UserListController extends Controller
{
    public function index(Request $request, UserListService $users): Response
    {
        return Inertia::render('Settings/Users/Index', [
            'users' => $users->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
        ]);
    }
}

PHP);

put("{$set}/Services/UserListService.php", <<<'PHP'
<?php

namespace Modules\Settings\Services;

use App\Core\Support\Service;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserListService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return User::query()
            ->when($search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toDateString(),
            ]);
    }
}

PHP);

put("{$set}/Resources/js/Pages/Users/Index.vue", <<<'VUE'
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});
const search = ref(props.filters.search ?? '');
watch(search, (value) => {
    router.get(route('settings.users.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>
<template>
    <Head title="Users" />
    <AdminLayout title="Users">
        <div class="mb-4"><TextInput v-model="search" type="search" class="w-64" placeholder="Search users…" /></div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Name</th><th class="pb-2">Email</th><th class="pb-2">Joined</th></tr></thead>
                <tbody>
                    <tr v-for="row in users.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2 font-medium text-brand-navy">{{ row.name }}</td>
                        <td class="py-2">{{ row.email }}</td>
                        <td class="py-2">{{ row.created_at }}</td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="users" class="mt-4" />
        </section>
    </AdminLayout>
</template>
VUE);

entity($root, [
    'module' => 'Settings', 'code' => 'settings', 'entity' => 'Role', 'plural' => 'Roles',
    'table' => 'roles', 'route' => 'roles', 'prop' => 'roles',
    'fillable' => ['name', 'slug', 'description'],
    'search' => ['name', 'slug'],
    'rules' => [
        'name' => "['required', 'string', 'max:255']",
        'slug' => "['required', 'string', 'max:80', 'unique:roles,slug']",
        'description' => "['nullable', 'string']",
    ],
    'rules_update' => [
        'name' => "['required', 'string', 'max:255']",
        'slug' => "['required', 'string', 'max:80']",
        'description' => "['nullable', 'string']",
    ],
    'columns' => ['name' => 'Name', 'slug' => 'Slug', 'description' => 'Description'],
    'fields' => [
        'name' => ['label' => 'Name'],
        'slug' => ['label' => 'Slug'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
    ],
]);

entity($root, [
    'module' => 'Settings', 'code' => 'settings', 'entity' => 'NumberingSeries', 'plural' => 'Numbering',
    'table' => 'numbering_series', 'route' => 'numbering', 'prop' => 'series', 'param' => 'numberingSeries',
    'fillable' => ['name', 'code', 'prefix', 'next_number', 'pad_length', 'is_active'],
    'casts' => ['is_active' => "'boolean'"],
    'search' => ['name', 'code', 'prefix'],
    'rules' => [
        'name' => "['required', 'string', 'max:255']",
        'code' => "['required', 'string', 'max:40', 'unique:numbering_series,code']",
        'prefix' => "['nullable', 'string', 'max:20']",
        'next_number' => "['required', 'integer', 'min:1']",
        'pad_length' => "['required', 'integer', 'min:1', 'max:12']",
        'is_active' => "['boolean']",
    ],
    'rules_update' => [
        'name' => "['required', 'string', 'max:255']",
        'code' => "['required', 'string', 'max:40']",
        'prefix' => "['nullable', 'string', 'max:20']",
        'next_number' => "['required', 'integer', 'min:1']",
        'pad_length' => "['required', 'integer', 'min:1', 'max:12']",
        'is_active' => "['boolean']",
    ],
    'create' => ['is_active' => "\$data['is_active'] ?? true", 'next_number' => "\$data['next_number'] ?? 1", 'pad_length' => "\$data['pad_length'] ?? 5"],
    'columns' => ['name' => 'Name', 'code' => 'Code', 'prefix' => 'Prefix', 'next_number' => 'Next'],
    'fields' => [
        'name' => ['label' => 'Name'],
        'code' => ['label' => 'Code'],
        'prefix' => ['label' => 'Prefix'],
        'next_number' => ['label' => 'Next number', 'type' => 'number', 'default' => '1'],
        'pad_length' => ['label' => 'Pad length', 'type' => 'number', 'default' => '5'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

entity($root, [
    'module' => 'Settings', 'code' => 'settings', 'entity' => 'TaxRate', 'plural' => 'Tax',
    'table' => 'tax_rates', 'route' => 'tax', 'prop' => 'taxes', 'param' => 'taxRate',
    'fillable' => ['name', 'code', 'rate', 'is_active'],
    'casts' => ['is_active' => "'boolean'"],
    'search' => ['name', 'code'],
    'rules' => [
        'name' => "['required', 'string', 'max:255']",
        'code' => "['required', 'string', 'max:40', 'unique:tax_rates,code']",
        'rate' => "['required', 'numeric', 'min:0']",
        'is_active' => "['boolean']",
    ],
    'rules_update' => [
        'name' => "['required', 'string', 'max:255']",
        'code' => "['required', 'string', 'max:40']",
        'rate' => "['required', 'numeric', 'min:0']",
        'is_active' => "['boolean']",
    ],
    'create' => ['is_active' => "\$data['is_active'] ?? true", 'rate' => "\$data['rate'] ?? 0"],
    'columns' => ['name' => 'Name', 'code' => 'Code', 'rate' => 'Rate %', 'is_active' => 'Active'],
    'fields' => [
        'name' => ['label' => 'Name'],
        'code' => ['label' => 'Code'],
        'rate' => ['label' => 'Rate', 'type' => 'number', 'default' => '0'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

entity($root, [
    'module' => 'Settings', 'code' => 'settings', 'entity' => 'Currency', 'plural' => 'Currency',
    'table' => 'currencies', 'route' => 'currency', 'prop' => 'currencies',
    'fillable' => ['name', 'code', 'symbol', 'exchange_rate', 'is_default', 'is_active'],
    'casts' => ['is_active' => "'boolean'", 'is_default' => "'boolean'"],
    'search' => ['name', 'code'],
    'rules' => [
        'name' => "['required', 'string', 'max:255']",
        'code' => "['required', 'string', 'max:10', 'unique:currencies,code']",
        'symbol' => "['nullable', 'string', 'max:10']",
        'exchange_rate' => "['required', 'numeric', 'min:0']",
        'is_default' => "['boolean']",
        'is_active' => "['boolean']",
    ],
    'rules_update' => [
        'name' => "['required', 'string', 'max:255']",
        'code' => "['required', 'string', 'max:10']",
        'symbol' => "['nullable', 'string', 'max:10']",
        'exchange_rate' => "['required', 'numeric', 'min:0']",
        'is_default' => "['boolean']",
        'is_active' => "['boolean']",
    ],
    'create' => ['is_active' => "\$data['is_active'] ?? true", 'is_default' => "\$data['is_default'] ?? false", 'exchange_rate' => "\$data['exchange_rate'] ?? 1"],
    'columns' => ['name' => 'Name', 'code' => 'Code', 'symbol' => 'Symbol', 'exchange_rate' => 'Rate'],
    'fields' => [
        'name' => ['label' => 'Name'],
        'code' => ['label' => 'Code'],
        'symbol' => ['label' => 'Symbol'],
        'exchange_rate' => ['label' => 'Exchange rate', 'type' => 'number', 'default' => '1'],
        'is_default' => ['label' => 'Default', 'type' => 'checkbox', 'default' => 'false'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

entity($root, [
    'module' => 'Settings', 'code' => 'settings', 'entity' => 'PaymentMethod', 'plural' => 'PaymentMethods',
    'table' => 'payment_methods', 'route' => 'payment-methods', 'prop' => 'methods', 'param' => 'paymentMethod',
    'fillable' => ['name', 'code', 'is_active', 'config'],
    'casts' => ['is_active' => "'boolean'", 'config' => "'array'"],
    'search' => ['name', 'code'],
    'rules' => [
        'name' => "['required', 'string', 'max:255']",
        'code' => "['required', 'string', 'max:40', 'unique:payment_methods,code']",
        'is_active' => "['boolean']",
        'config' => "['nullable']",
    ],
    'rules_update' => [
        'name' => "['required', 'string', 'max:255']",
        'code' => "['required', 'string', 'max:40']",
        'is_active' => "['boolean']",
        'config' => "['nullable']",
    ],
    'create' => [
        'is_active' => "\$data['is_active'] ?? true",
        'config' => "is_string(\$data['config'] ?? null) ? json_decode(\$data['config'], true) : (\$data['config'] ?? [])",
    ],
    'format_extra' => "            'config_json' => json_encode(\$row->config ?? []),",
    'columns' => ['name' => 'Name', 'code' => 'Code', 'is_active' => 'Active'],
    'fields' => [
        'name' => ['label' => 'Name'],
        'code' => ['label' => 'Code'],
        'config' => ['label' => 'Config JSON', 'type' => 'textarea', 'default' => "'{}'", 'edit' => "row.config_json || '{}'"],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

entity($root, [
    'module' => 'Settings', 'code' => 'settings', 'entity' => 'ShippingMethod', 'plural' => 'ShippingMethods',
    'table' => 'shipping_methods', 'route' => 'shipping-methods', 'prop' => 'methods', 'param' => 'shippingMethod',
    'fillable' => ['name', 'code', 'flat_rate', 'is_active'],
    'casts' => ['is_active' => "'boolean'"],
    'search' => ['name', 'code'],
    'rules' => [
        'name' => "['required', 'string', 'max:255']",
        'code' => "['required', 'string', 'max:40', 'unique:shipping_methods,code']",
        'flat_rate' => "['required', 'numeric', 'min:0']",
        'is_active' => "['boolean']",
    ],
    'rules_update' => [
        'name' => "['required', 'string', 'max:255']",
        'code' => "['required', 'string', 'max:40']",
        'flat_rate' => "['required', 'numeric', 'min:0']",
        'is_active' => "['boolean']",
    ],
    'create' => ['is_active' => "\$data['is_active'] ?? true", 'flat_rate' => "\$data['flat_rate'] ?? 0"],
    'columns' => ['name' => 'Name', 'code' => 'Code', 'flat_rate' => 'Flat rate', 'is_active' => 'Active'],
    'fields' => [
        'name' => ['label' => 'Name'],
        'code' => ['label' => 'Code'],
        'flat_rate' => ['label' => 'Flat rate', 'type' => 'number', 'default' => '0'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

put("{$set}/Http/Controllers/LocalizationController.php", <<<'PHP'
<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\SettingService;

class LocalizationController extends Controller
{
    public function edit(SettingService $settings): Response
    {
        return Inertia::render('Settings/Localization/Index', [
            'settings' => array_merge([
                'language' => 'en',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'first_day_of_week' => '0',
            ], $settings->getGroup('localization')),
        ]);
    }

    public function update(Request $request, SettingService $settings): RedirectResponse
    {
        $data = $request->validate([
            'language' => ['nullable', 'string', 'max:20'],
            'date_format' => ['nullable', 'string', 'max:40'],
            'time_format' => ['nullable', 'string', 'max:40'],
            'first_day_of_week' => ['nullable', 'string', 'max:5'],
        ]);
        $settings->saveGroup('localization', $data);

        return back()->with('success', 'Localization saved.');
    }
}

PHP);

put("{$set}/Resources/js/Pages/Localization/Index.vue", settingsFormVue('Localization', 'settings.localization.update', [
    'language' => 'Language',
    'date_format' => 'Date format',
    'time_format' => 'Time format',
    'first_day_of_week' => 'First day of week (0=Sun)',
]));

put("{$set}/Http/Controllers/IntegrationsController.php", <<<'PHP'
<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\SettingService;

class IntegrationsController extends Controller
{
    public function edit(SettingService $settings): Response
    {
        return Inertia::render('Settings/Integrations/Index', [
            'settings' => array_merge([
                'stripe_key' => '',
                'sms_api_key' => '',
                'google_maps_key' => '',
            ], $settings->getGroup('integrations')),
        ]);
    }

    public function update(Request $request, SettingService $settings): RedirectResponse
    {
        $data = $request->validate([
            'stripe_key' => ['nullable', 'string', 'max:255'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'google_maps_key' => ['nullable', 'string', 'max:255'],
        ]);
        $settings->saveGroup('integrations', $data);

        return back()->with('success', 'Integrations saved.');
    }
}

PHP);

put("{$set}/Resources/js/Pages/Integrations/Index.vue", settingsFormVue('Integrations', 'settings.integrations.update', [
    'stripe_key' => 'Stripe key',
    'sms_api_key' => 'SMS API key',
    'google_maps_key' => 'Google Maps key',
]));

put("{$set}/Http/Controllers/ApiSettingsController.php", <<<'PHP'
<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\SettingService;

class ApiSettingsController extends Controller
{
    public function edit(SettingService $settings): Response
    {
        return Inertia::render('Settings/Api/Index', [
            'settings' => array_merge([
                'api_token' => '',
                'webhook_url' => '',
                'webhook_secret' => '',
            ], $settings->getGroup('api')),
        ]);
    }

    public function update(Request $request, SettingService $settings): RedirectResponse
    {
        $data = $request->validate([
            'api_token' => ['nullable', 'string', 'max:255'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);
        $settings->saveGroup('api', $data);

        return back()->with('success', 'API settings saved.');
    }
}

PHP);

put("{$set}/Resources/js/Pages/Api/Index.vue", settingsFormVue('API & Webhooks', 'settings.api.update', [
    'api_token' => 'API token',
    'webhook_url' => 'Webhook URL',
    'webhook_secret' => 'Webhook secret',
]));

entity($root, [
    'module' => 'Settings', 'code' => 'settings', 'entity' => 'AuditLog', 'plural' => 'AuditLogs',
    'table' => 'audit_logs', 'route' => 'audit-logs', 'prop' => 'logs', 'param' => 'auditLog',
    'fillable' => ['user_id', 'action', 'subject_type', 'subject_id', 'properties', 'ip_address'],
    'casts' => ['properties' => "'array'"],
    'search' => ['action', 'subject_type', 'ip_address'],
    'rules' => [
        'action' => "['required', 'string', 'max:80']",
        'subject_type' => "['nullable', 'string', 'max:120']",
        'subject_id' => "['nullable', 'integer']",
        'properties' => "['nullable']",
        'ip_address' => "['nullable', 'string', 'max:45']",
    ],
    'format_extra' => "            'created_at' => \$row->created_at?->toDateTimeString(),",
    'columns' => ['action' => 'Action', 'subject_type' => 'Subject', 'ip_address' => 'IP', 'created_at' => 'When'],
    'fields' => [
        'action' => ['label' => 'Action'],
        'subject_type' => ['label' => 'Subject type'],
        'subject_id' => ['label' => 'Subject ID', 'type' => 'number', 'default' => 'null'],
        'ip_address' => ['label' => 'IP'],
    ],
]);

// Settings routes - write at end of this section after all entities
echo "Settings entities done (routes later)\n";

file_put_contents("{$root}/scripts/_settings_routes_marker.txt", 'ok');
