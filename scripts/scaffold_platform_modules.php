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
    echo 'Wrote '.str_replace(dirname(__DIR__).DIRECTORY_SEPARATOR, '', $path).PHP_EOL;
}

function mig(string $upBody, string $downTable): string
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
{$upBody}
    }

    public function down(): void
    {
        Schema::dropIfExists('{$downTable}');
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
    $coreJson = $core ? 'true' : 'false';

    return <<<JSON
{
  "name": "{$name}",
  "code": "{$code}",
  "version": "1.0.0",
  "description": "{$desc}",
  "is_core": {$coreJson},
  "dependencies": []
}

JSON;
}

function crudService(string $ns, string $class, string $model, string $tableSearchCols, array $createMap): string
{
    $searchWhen = '';
    $cols = explode(',', $tableSearchCols);
    $ors = [];
    foreach ($cols as $c) {
        $c = trim($c);
        $ors[] = "\$inner->orWhere('{$c}', 'like', \"%{\$search}%\");";
    }
    // fix first orWhere
    if ($ors !== []) {
        $ors[0] = str_replace('orWhere', 'where', $ors[0]);
    }
    $orBody = implode("\n                    ", $ors);

    $createLines = [];
    foreach ($createMap as $k => $expr) {
        $createLines[] = "            '{$k}' => {$expr},";
    }
    $createBody = implode("\n", $createLines);

    $formatLines = [];
    foreach (array_keys($createMap) as $k) {
        $formatLines[] = "            '{$k}' => \$row->{$k},";
    }
    $formatBody = implode("\n", $formatLines);

    return <<<PHP
<?php

namespace {$ns};

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use {$ns}\\..\\Models\\PLACEHOLDER;

PHP;
}

// --------- Shared helpers for generating lean CRUD stacks ---------

/**
 * @param  array<string,mixed>  $cfg
 */
function writeCrudStack(string $root, array $cfg): void
{
    $mod = $cfg['module']; // Hrm
    $code = $cfg['code']; // hrm
    $entity = $cfg['entity']; // Department
    $plural = $cfg['plural']; // Departments
    $table = $cfg['table'];
    $route = $cfg['route']; // departments
    $prop = $cfg['prop']; // departments
    $fillable = $cfg['fillable'];
    $casts = $cfg['casts'] ?? [];
    $search = $cfg['search'];
    $rulesStore = $cfg['rules_store'];
    $rulesUpdate = $cfg['rules_update'] ?? $rulesStore;
    $columns = $cfg['columns'];
    $fields = $cfg['fields'];
    $createDefaults = $cfg['create'] ?? [];
    $relations = $cfg['relations'] ?? '';
    $with = $cfg['with'] ?? '';
    $formatExtra = $cfg['format_extra'] ?? '';
    $formOptions = $cfg['form_options'] ?? '';
    $routeParam = $cfg['route_param'] ?? lcfirst($entity);

    $base = "{$root}/modules/{$mod}";
    $modelNs = "Modules\\{$mod}\\Models";
    $svcNs = "Modules\\{$mod}\\Services";
    $ctrlNs = "Modules\\{$mod}\\Http\\Controllers";
    $reqNs = "Modules\\{$mod}\\Http\\Requests";

    // Model
    $fillStr = implode(",\n        ", array_map(fn ($f) => "'{$f}'", $fillable));
    $castBlock = '';
    if ($casts !== []) {
        $lines = [];
        foreach ($casts as $k => $v) {
            $lines[] = "            '{$k}' => {$v},";
        }
        $castBlock = "\n\n    protected function casts(): array\n    {\n        return [\n".implode("\n", $lines)."\n        ];\n    }";
    }

    put("{$base}/Models/{$entity}.php", <<<PHP
<?php

namespace {$modelNs};

use Illuminate\Database\Eloquent\Model;
{$relations}
class {$entity} extends Model
{
    protected \$table = '{$table}';

    protected \$fillable = [
        {$fillStr},
    ];{$castBlock}
}

PHP);

    // Requests
    $ruleLines = static function (array $rules): string {
        $out = [];
        foreach ($rules as $f => $r) {
            $out[] = "            '{$f}' => {$r},";
        }

        return implode("\n", $out);
    };

    put("{$base}/Http/Requests/Store{$entity}Request.php", <<<PHP
<?php

namespace {$reqNs};

use Illuminate\Foundation\Http\FormRequest;

class Store{$entity}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
{$ruleLines($rulesStore)}
        ];
    }
}

PHP);

    put("{$base}/Http/Requests/Update{$entity}Request.php", <<<PHP
<?php

namespace {$reqNs};

use Illuminate\Foundation\Http\FormRequest;

class Update{$entity}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
{$ruleLines($rulesUpdate)}
        ];
    }
}

PHP);

    // Service
    $searchOrs = [];
    foreach ($search as $i => $col) {
        $method = $i === 0 ? 'where' : 'orWhere';
        $searchOrs[] = "\$inner->{$method}('{$col}', 'like', \"%{\$search}%\");";
    }
    $searchBody = implode("\n                    ", $searchOrs);

    $createAssign = [];
    foreach ($fillable as $f) {
        if (isset($createDefaults[$f])) {
            $createAssign[] = "            '{$f}' => {$createDefaults[$f]},";
        } else {
            $createAssign[] = "            '{$f}' => \$data['{$f}'] ?? null,";
        }
    }
    $createBody = implode("\n", $createAssign);

    $updateAssign = [];
    foreach ($fillable as $f) {
        $updateAssign[] = "            '{$f}' => \$data['{$f}'] ?? \$row->{$f},";
    }
    $updateBody = implode("\n", $updateAssign);

    $formatFields = [];
    foreach ($fillable as $f) {
        if (isset($casts[$f]) && str_contains($casts[$f], 'date')) {
            $formatFields[] = "            '{$f}' => \$row->{$f}?->toDateString(),";
        } elseif (isset($casts[$f]) && str_contains($casts[$f], 'datetime')) {
            $formatFields[] = "            '{$f}' => \$row->{$f}?->toIso8601String(),";
        } elseif (isset($casts[$f]) && str_contains($casts[$f], 'array') || isset($casts[$f]) && str_contains($casts[$f], 'AsArrayObject')) {
            $formatFields[] = "            '{$f}' => \$row->{$f},";
        } else {
            $formatFields[] = "            '{$f}' => \$row->{$f},";
        }
    }
    $formatBody = implode("\n", $formatFields);

    $withLine = $with !== '' ? "\n            ->with([{$with}])" : '';
    $formOptionsMethod = $formOptions !== '' ? "\n\n    public function formOptions(): array\n    {\n{$formOptions}\n    }" : '';

    put("{$base}/Services/{$entity}Service.php", <<<PHP
<?php

namespace {$svcNs};

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use {$modelNs}\\{$entity};

class {$entity}Service extends Service
{
    public function listPaginated(?string \$search = null, int \$perPage = 25): LengthAwarePaginator
    {
        \$perPage = in_array(\$perPage, [10, 25, 50, 100], true) ? \$perPage : 25;

        return {$entity}::query(){$withLine}
            ->when(\$search, fn (\$query, \$search) => \$query->where(function (\$inner) use (\$search) {
                {$searchBody}
            }))
            ->orderByDesc('id')
            ->paginate(\$perPage)
            ->withQueryString()
            ->through(fn ({$entity} \$row) => \$this->format(\$row));
    }

    /**
     * @param  array<string, mixed>  \$data
     */
    public function create(array \$data): {$entity}
    {
        return {$entity}::query()->create([
{$createBody}
        ]);
    }

    /**
     * @param  array<string, mixed>  \$data
     */
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

    /**
     * @return array<string, mixed>
     */
    public function format({$entity} \$row): array
    {
        return [
            'id' => \$row->id,
{$formatBody}
{$formatExtra}
        ];
    }{$formOptionsMethod}
}

PHP);

    // Controller — use explicit binding variable name matching route param
    put("{$base}/Http/Controllers/{$entity}Controller.php", <<<PHP
<?php

namespace {$ctrlNs};

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use {$reqNs}\\Store{$entity}Request;
use {$reqNs}\\Update{$entity}Request;
use {$modelNs}\\{$entity};
use {$svcNs}\\{$entity}Service;

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

        return back()->with('success', '{$entity} created.');
    }

    public function update(Update{$entity}Request \$request, {$entity} \${$routeParam}, {$entity}Service \$svc): RedirectResponse
    {
        \$svc->update(\${$routeParam}, \$request->validated());

        return back()->with('success', '{$entity} updated.');
    }

    public function destroy({$entity} \${$routeParam}, {$entity}Service \$svc): RedirectResponse
    {
        \$svc->delete(\${$routeParam});

        return back()->with('success', '{$entity} deleted.');
    }
}

PHP);

    // Vue
    $formDefaults = [];
    $openEdit = [];
    $inputs = [];
    $needsCheckbox = false;
    foreach ($fields as $name => $meta) {
        $default = $meta['default'] ?? "''";
        $formDefaults[] = "    {$name}: {$default},";
        $coerce = $meta['edit'] ?? "row.{$name} ?? {$default}";
        $openEdit[] = "    form.{$name} = {$coerce};";
        $label = $meta['label'] ?? ucfirst(str_replace('_', ' ', $name));
        $type = $meta['type'] ?? 'text';
        if ($type === 'textarea') {
            $inputs[] = "                <div>\n                    <InputLabel value=\"{$label}\" />\n                    <textarea v-model=\"form.{$name}\" class=\"mt-1 block w-full rounded-md border-gray-300 text-sm\" rows=\"3\" />\n                    <InputError :message=\"form.errors.{$name}\" />\n                </div>";
        } elseif ($type === 'checkbox') {
            $needsCheckbox = true;
            $inputs[] = "                <label class=\"flex items-center gap-2 text-sm\">\n                    <Checkbox v-model:checked=\"form.{$name}\" />\n                    {$label}\n                </label>";
        } elseif ($type === 'select') {
            $opts = $meta['options'] ?? '[]';
            $inputs[] = "                <div>\n                    <InputLabel value=\"{$label}\" />\n                    <select v-model=\"form.{$name}\" class=\"mt-1 block w-full rounded-md border-gray-300 text-sm\">\n                        <option value=\"\">—</option>\n                        <option v-for=\"opt in {$opts}\" :key=\"opt.value ?? opt.id ?? opt\" :value=\"opt.value ?? opt.id ?? opt\">{{ opt.label ?? opt.name ?? opt }}</option>\n                    </select>\n                    <InputError :message=\"form.errors.{$name}\" />\n                </div>";
        } else {
            $inputType = in_array($type, ['number', 'date', 'time', 'datetime-local', 'email'], true) ? $type : 'text';
            $inputs[] = "                <div>\n                    <InputLabel value=\"{$label}\" />\n                    <TextInput v-model=\"form.{$name}\" type=\"{$inputType}\" class=\"mt-1 block w-full\" />\n                    <InputError :message=\"form.errors.{$name}\" />\n                </div>";
        }
    }
    $th = $td = '';
    foreach ($columns as $col => $label) {
        $th .= "                        <th class=\"pb-2\">{$label}</th>\n";
        $td .= "                        <td class=\"py-2\">{{ row.{$col} ?? '—' }}</td>\n";
    }
    $checkboxImport = $needsCheckbox ? "import Checkbox from '@/Components/Checkbox.vue';\n" : '';
    $formDefaultsStr = implode("\n", $formDefaults);
    $openEditStr = implode("\n", $openEdit);
    $inputsStr = implode("\n", $inputs);

    put("{$base}/Resources/js/Pages/{$plural}/Index.vue", <<<VUE
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
{$checkboxImport}import { Head, router, useForm, usePage } from '@inertiajs/vue3';
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
{$formDefaultsStr}
});

const deleteForm = useForm({});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (row) => {
    editing.value = row;
{$openEditStr}
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('{$code}.{$route}.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('{$code}.{$route}.store'), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    }
};

watch(search, (value) => {
    router.get(route('{$code}.{$route}.index'), { search: value || undefined }, { preserveState: true, replace: true });
});
</script>

<template>
    <Head title="{$plural}" />

    <AdminLayout title="{$plural}">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">
            {{ flash.success }}
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search…" />
            <PrimaryButton type="button" @click="openCreate">Add</PrimaryButton>
        </div>

        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr>
{$th}                        <th class="pb-2" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in {$prop}.data" :key="row.id" class="border-t border-gray-50">
{$td}                        <td class="py-2 text-right space-x-2">
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
{$inputsStr}
                <div class="flex justify-end gap-2 pt-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="submit">Save</PrimaryButton>
                </div>
            </div>
        </Modal>

        <DeleteConfirmModal
            :show="!!deleteTarget"
            title="Delete this record?"
            :processing="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="deleteForm.delete(route('{$code}.{$route}.destroy', deleteTarget.id), { onSuccess: () => (deleteTarget = null) })"
        />
    </AdminLayout>
</template>
VUE);
}

// ================= HRM migrations =================
$hrm = "{$root}/modules/Hrm";

put("{$hrm}/Database/migrations/2026_09_03_910010_create_hrm_departments_table.php", mig(<<<'PHP'
        Schema::create('hrm_departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
PHP, 'hrm_departments'));

put("{$hrm}/Database/migrations/2026_09_03_910011_create_hrm_designations_table.php", mig(<<<'PHP'
        Schema::create('hrm_designations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->foreignId('department_id')->nullable()->constrained('hrm_departments')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
PHP, 'hrm_designations'));

put("{$hrm}/Database/migrations/2026_09_03_910012_create_hrm_attendances_table.php", mig(<<<'PHP'
        Schema::create('hrm_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('status', 30)->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'date']);
        });
PHP, 'hrm_attendances'));

put("{$hrm}/Database/migrations/2026_09_03_910013_create_hrm_leaves_table.php", mig(<<<'PHP'
        Schema::create('hrm_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('type', 40);
            $table->date('from_date');
            $table->date('to_date');
            $table->string('status', 30)->default('pending');
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });
PHP, 'hrm_leaves'));

put("{$hrm}/Database/migrations/2026_09_03_910014_create_hrm_payrolls_table.php", mig(<<<'PHP'
        Schema::create('hrm_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hrm_employees')->cascadeOnDelete();
            $table->string('period', 20);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'period']);
        });
PHP, 'hrm_payrolls'));

put("{$hrm}/Database/migrations/2026_09_03_910015_create_hrm_salary_structures_table.php", mig(<<<'PHP'
        Schema::create('hrm_salary_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('employee_id')->nullable()->constrained('hrm_employees')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('hrm_designations')->nullOnDelete();
            $table->json('components')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
PHP, 'hrm_salary_structures'));

writeCrudStack($root, [
    'module' => 'Hrm', 'code' => 'hrm', 'entity' => 'Department', 'plural' => 'Departments',
    'table' => 'hrm_departments', 'route' => 'departments', 'prop' => 'departments',
    'fillable' => ['code', 'name', 'is_active', 'notes'],
    'casts' => ['is_active' => "'boolean'"],
    'search' => ['name', 'code'],
    'rules_store' => [
        'code' => "['required', 'string', 'max:40', 'unique:hrm_departments,code']",
        'name' => "['required', 'string', 'max:255']",
        'is_active' => "['boolean']",
        'notes' => "['nullable', 'string']",
    ],
    'rules_update' => [
        'code' => "['required', 'string', 'max:40']",
        'name' => "['required', 'string', 'max:255']",
        'is_active' => "['boolean']",
        'notes' => "['nullable', 'string']",
    ],
    'create' => ['is_active' => "\$data['is_active'] ?? true"],
    'columns' => ['code' => 'Code', 'name' => 'Name', 'is_active' => 'Active'],
    'fields' => [
        'code' => ['label' => 'Code'],
        'name' => ['label' => 'Name'],
        'notes' => ['label' => 'Notes', 'type' => 'textarea'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

writeCrudStack($root, [
    'module' => 'Hrm', 'code' => 'hrm', 'entity' => 'Designation', 'plural' => 'Designations',
    'table' => 'hrm_designations', 'route' => 'designations', 'prop' => 'designations',
    'fillable' => ['code', 'name', 'department_id', 'is_active'],
    'casts' => ['is_active' => "'boolean'"],
    'search' => ['name', 'code'],
    'rules_store' => [
        'code' => "['required', 'string', 'max:40', 'unique:hrm_designations,code']",
        'name' => "['required', 'string', 'max:255']",
        'department_id' => "['nullable', 'exists:hrm_departments,id']",
        'is_active' => "['boolean']",
    ],
    'create' => ['is_active' => "\$data['is_active'] ?? true"],
    'with' => "'department:id,name'",
    'relations' => "use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;\n",
    'format_extra' => "            'department_name' => \$row->department?->name,",
    'form_options' => "        return [\n            'departments' => \\Modules\\Hrm\\Models\\Department::query()->orderBy('name')->get(['id', 'name'])->map(fn (\$d) => ['id' => \$d->id, 'name' => \$d->name])->all(),\n        ];",
    'columns' => ['code' => 'Code', 'name' => 'Name', 'department_name' => 'Department', 'is_active' => 'Active'],
    'fields' => [
        'code' => ['label' => 'Code'],
        'name' => ['label' => 'Name'],
        'department_id' => ['label' => 'Department', 'type' => 'select', 'options' => 'options.departments', 'default' => 'null'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

// Add BelongsTo method to Designation model manually after
$desigModel = file_get_contents("{$hrm}/Models/Designation.php");
if (! str_contains($desigModel, 'function department')) {
    $desigModel = str_replace(
        "}\n",
        "\n    public function department(): BelongsTo\n    {\n        return \$this->belongsTo(Department::class);\n    }\n}\n",
        $desigModel
    );
    file_put_contents("{$hrm}/Models/Designation.php", $desigModel);
}

writeCrudStack($root, [
    'module' => 'Hrm', 'code' => 'hrm', 'entity' => 'Attendance', 'plural' => 'Attendance',
    'table' => 'hrm_attendances', 'route' => 'attendance', 'prop' => 'attendances',
    'route_param' => 'attendance',
    'fillable' => ['employee_id', 'date', 'check_in', 'check_out', 'status', 'notes'],
    'casts' => ['date' => "'date'"],
    'search' => ['status', 'notes'],
    'rules_store' => [
        'employee_id' => "['required', 'exists:hrm_employees,id']",
        'date' => "['required', 'date']",
        'check_in' => "['nullable', 'date_format:H:i']",
        'check_out' => "['nullable', 'date_format:H:i']",
        'status' => "['required', 'string', 'max:30']",
        'notes' => "['nullable', 'string']",
    ],
    'create' => ['status' => "\$data['status'] ?? 'present'"],
    'with' => "'employee:id,name,code'",
    'relations' => "use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;\n",
    'format_extra' => "            'employee_name' => \$row->employee?->name,\n            'date' => \$row->date?->toDateString(),",
    'form_options' => "        return [\n            'employees' => \\Modules\\Hrm\\Models\\Employee::query()->orderBy('name')->get(['id', 'name'])->map(fn (\$e) => ['id' => \$e->id, 'name' => \$e->name])->all(),\n            'statuses' => [\n                ['value' => 'present', 'label' => 'Present'],\n                ['value' => 'absent', 'label' => 'Absent'],\n                ['value' => 'late', 'label' => 'Late'],\n                ['value' => 'half_day', 'label' => 'Half day'],\n            ],\n        ];",
    'columns' => ['employee_name' => 'Employee', 'date' => 'Date', 'check_in' => 'In', 'check_out' => 'Out', 'status' => 'Status'],
    'fields' => [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'options' => 'options.employees', 'default' => 'null'],
        'date' => ['label' => 'Date', 'type' => 'date'],
        'check_in' => ['label' => 'Check in', 'type' => 'time'],
        'check_out' => ['label' => 'Check out', 'type' => 'time'],
        'status' => ['label' => 'Status', 'type' => 'select', 'options' => 'options.statuses', 'default' => "'present'"],
        'notes' => ['label' => 'Notes', 'type' => 'textarea'],
    ],
]);

$attModel = file_get_contents("{$hrm}/Models/Attendance.php");
if (! str_contains($attModel, 'function employee')) {
    $attModel = str_replace(
        "}\n",
        "\n    public function employee(): BelongsTo\n    {\n        return \$this->belongsTo(Employee::class);\n    }\n}\n",
        $attModel
    );
    file_put_contents("{$hrm}/Models/Attendance.php", $attModel);
}

writeCrudStack($root, [
    'module' => 'Hrm', 'code' => 'hrm', 'entity' => 'Leave', 'plural' => 'Leave',
    'table' => 'hrm_leaves', 'route' => 'leave', 'prop' => 'leaves',
    'route_param' => 'leave',
    'fillable' => ['employee_id', 'type', 'from_date', 'to_date', 'status', 'reason'],
    'casts' => ['from_date' => "'date'", 'to_date' => "'date'"],
    'search' => ['type', 'status', 'reason'],
    'rules_store' => [
        'employee_id' => "['required', 'exists:hrm_employees,id']",
        'type' => "['required', 'string', 'max:40']",
        'from_date' => "['required', 'date']",
        'to_date' => "['required', 'date', 'after_or_equal:from_date']",
        'status' => "['required', 'string', 'max:30']",
        'reason' => "['nullable', 'string']",
    ],
    'create' => ['status' => "\$data['status'] ?? 'pending'"],
    'with' => "'employee:id,name,code'",
    'relations' => "use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;\n",
    'format_extra' => "            'employee_name' => \$row->employee?->name,\n            'from_date' => \$row->from_date?->toDateString(),\n            'to_date' => \$row->to_date?->toDateString(),",
    'form_options' => "        return [\n            'employees' => \\Modules\\Hrm\\Models\\Employee::query()->orderBy('name')->get(['id', 'name'])->map(fn (\$e) => ['id' => \$e->id, 'name' => \$e->name])->all(),\n            'types' => ['Annual', 'Sick', 'Unpaid', 'Casual'],\n            'statuses' => [\n                ['value' => 'pending', 'label' => 'Pending'],\n                ['value' => 'approved', 'label' => 'Approved'],\n                ['value' => 'rejected', 'label' => 'Rejected'],\n            ],\n        ];",
    'columns' => ['employee_name' => 'Employee', 'type' => 'Type', 'from_date' => 'From', 'to_date' => 'To', 'status' => 'Status'],
    'fields' => [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'options' => 'options.employees', 'default' => 'null'],
        'type' => ['label' => 'Type', 'type' => 'select', 'options' => 'options.types', 'default' => "'Annual'"],
        'from_date' => ['label' => 'From', 'type' => 'date'],
        'to_date' => ['label' => 'To', 'type' => 'date'],
        'status' => ['label' => 'Status', 'type' => 'select', 'options' => 'options.statuses', 'default' => "'pending'"],
        'reason' => ['label' => 'Reason', 'type' => 'textarea'],
    ],
]);

$leaveModel = file_get_contents("{$hrm}/Models/Leave.php");
if (! str_contains($leaveModel, 'function employee')) {
    $leaveModel = str_replace(
        "}\n",
        "\n    public function employee(): BelongsTo\n    {\n        return \$this->belongsTo(Employee::class);\n    }\n}\n",
        $leaveModel
    );
    file_put_contents("{$hrm}/Models/Leave.php", $leaveModel);
}

writeCrudStack($root, [
    'module' => 'Hrm', 'code' => 'hrm', 'entity' => 'Payroll', 'plural' => 'Payroll',
    'table' => 'hrm_payrolls', 'route' => 'payroll', 'prop' => 'payrolls',
    'route_param' => 'payroll',
    'fillable' => ['employee_id', 'period', 'amount', 'status', 'notes'],
    'search' => ['period', 'status'],
    'rules_store' => [
        'employee_id' => "['required', 'exists:hrm_employees,id']",
        'period' => "['required', 'string', 'max:20']",
        'amount' => "['required', 'numeric', 'min:0']",
        'status' => "['required', 'string', 'max:30']",
        'notes' => "['nullable', 'string']",
    ],
    'create' => ['status' => "\$data['status'] ?? 'draft'", 'amount' => "\$data['amount'] ?? 0"],
    'with' => "'employee:id,name,code'",
    'relations' => "use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;\n",
    'format_extra' => "            'employee_name' => \$row->employee?->name,",
    'form_options' => "        return [\n            'employees' => \\Modules\\Hrm\\Models\\Employee::query()->orderBy('name')->get(['id', 'name'])->map(fn (\$e) => ['id' => \$e->id, 'name' => \$e->name])->all(),\n            'statuses' => [\n                ['value' => 'draft', 'label' => 'Draft'],\n                ['value' => 'paid', 'label' => 'Paid'],\n                ['value' => 'cancelled', 'label' => 'Cancelled'],\n            ],\n        ];",
    'columns' => ['employee_name' => 'Employee', 'period' => 'Period', 'amount' => 'Amount', 'status' => 'Status'],
    'fields' => [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'options' => 'options.employees', 'default' => 'null'],
        'period' => ['label' => 'Period (YYYY-MM)'],
        'amount' => ['label' => 'Amount', 'type' => 'number', 'default' => '0'],
        'status' => ['label' => 'Status', 'type' => 'select', 'options' => 'options.statuses', 'default' => "'draft'"],
        'notes' => ['label' => 'Notes', 'type' => 'textarea'],
    ],
]);

$payModel = file_get_contents("{$hrm}/Models/Payroll.php");
if (! str_contains($payModel, 'function employee')) {
    $payModel = str_replace(
        "}\n",
        "\n    public function employee(): BelongsTo\n    {\n        return \$this->belongsTo(Employee::class);\n    }\n}\n",
        $payModel
    );
    file_put_contents("{$hrm}/Models/Payroll.php", $payModel);
}

writeCrudStack($root, [
    'module' => 'Hrm', 'code' => 'hrm', 'entity' => 'SalaryStructure', 'plural' => 'SalaryStructure',
    'table' => 'hrm_salary_structures', 'route' => 'salary-structure', 'prop' => 'structures',
    'route_param' => 'salaryStructure',
    'fillable' => ['name', 'employee_id', 'designation_id', 'components', 'is_active'],
    'casts' => ['components' => "'array'", 'is_active' => "'boolean'"],
    'search' => ['name'],
    'rules_store' => [
        'name' => "['required', 'string', 'max:255']",
        'employee_id' => "['nullable', 'exists:hrm_employees,id']",
        'designation_id' => "['nullable', 'exists:hrm_designations,id']",
        'components' => "['nullable']",
        'is_active' => "['boolean']",
    ],
    'create' => [
        'is_active' => "\$data['is_active'] ?? true",
        'components' => "is_string(\$data['components'] ?? null) ? json_decode(\$data['components'], true) : (\$data['components'] ?? [])",
    ],
    'with' => "'employee:id,name', 'designation:id,name'",
    'relations' => "use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;\n",
    'format_extra' => "            'employee_name' => \$row->employee?->name,\n            'designation_name' => \$row->designation?->name,\n            'components_json' => json_encode(\$row->components ?? []),",
    'form_options' => "        return [\n            'employees' => \\Modules\\Hrm\\Models\\Employee::query()->orderBy('name')->get(['id', 'name'])->map(fn (\$e) => ['id' => \$e->id, 'name' => \$e->name])->all(),\n            'designations' => \\Modules\\Hrm\\Models\\Designation::query()->orderBy('name')->get(['id', 'name'])->map(fn (\$d) => ['id' => \$d->id, 'name' => \$d->name])->all(),\n        ];",
    'columns' => ['name' => 'Name', 'employee_name' => 'Employee', 'designation_name' => 'Designation', 'is_active' => 'Active'],
    'fields' => [
        'name' => ['label' => 'Name'],
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'options' => 'options.employees', 'default' => 'null'],
        'designation_id' => ['label' => 'Designation', 'type' => 'select', 'options' => 'options.designations', 'default' => 'null'],
        'components' => ['label' => 'Components (JSON)', 'type' => 'textarea', 'default' => "'[]'", 'edit' => "row.components_json || '[]'"],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

$ssModel = file_get_contents("{$hrm}/Models/SalaryStructure.php");
if (! str_contains($ssModel, 'function employee')) {
    $ssModel = str_replace(
        "}\n",
        "\n    public function employee(): BelongsTo\n    {\n        return \$this->belongsTo(Employee::class);\n    }\n\n    public function designation(): BelongsTo\n    {\n        return \$this->belongsTo(Designation::class);\n    }\n}\n",
        $ssModel
    );
    file_put_contents("{$hrm}/Models/SalaryStructure.php", $ssModel);
}

// HR reports controller + vue
put("{$hrm}/Http/Controllers/HrmReportController.php", <<<'PHP'
<?php

namespace Modules\Hrm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HrmReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Hrm/Reports/Index', [
            'stats' => [
                'employees' => DB::table('hrm_employees')->count(),
                'active_employees' => DB::table('hrm_employees')->where('is_active', true)->count(),
                'departments' => DB::table('hrm_departments')->count(),
                'attendance_today' => DB::table('hrm_attendances')->whereDate('date', now()->toDateString())->count(),
                'pending_leave' => DB::table('hrm_leaves')->where('status', 'pending')->count(),
                'payroll_drafts' => DB::table('hrm_payrolls')->where('status', 'draft')->count(),
            ],
        ]);
    }
}

PHP);

put("{$hrm}/Resources/js/Pages/Reports/Index.vue", <<<'VUE'
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ stats: { type: Object, required: true } });
</script>

<template>
    <Head title="HR Reports" />
    <AdminLayout title="HR Reports">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="(value, key) in stats" :key="key" class="admin-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ String(key).replaceAll('_', ' ') }}</p>
                <p class="mt-1 text-2xl font-semibold text-brand-navy">{{ value }}</p>
            </div>
        </div>
    </AdminLayout>
</template>
VUE);

put("{$hrm}/Routes/web.php", <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use Modules\Hrm\Http\Controllers\AttendanceController;
use Modules\Hrm\Http\Controllers\DepartmentController;
use Modules\Hrm\Http\Controllers\DesignationController;
use Modules\Hrm\Http\Controllers\EmployeeController;
use Modules\Hrm\Http\Controllers\HrmOverviewController;
use Modules\Hrm\Http\Controllers\HrmReportController;
use Modules\Hrm\Http\Controllers\LeaveController;
use Modules\Hrm\Http\Controllers\PayrollController;
use Modules\Hrm\Http\Controllers\SalaryStructureController;

Route::get('/overview', [HrmOverviewController::class, 'index'])->name('overview');
Route::get('/reports', [HrmReportController::class, 'index'])->name('reports');

Route::prefix('employees')->name('employees.')->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::post('/', [EmployeeController::class, 'store'])->name('store');
    Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
    Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
});

Route::prefix('departments')->name('departments.')->group(function () {
    Route::get('/', [DepartmentController::class, 'index'])->name('index');
    Route::post('/', [DepartmentController::class, 'store'])->name('store');
    Route::put('/{department}', [DepartmentController::class, 'update'])->name('update');
    Route::delete('/{department}', [DepartmentController::class, 'destroy'])->name('destroy');
});

Route::prefix('designations')->name('designations.')->group(function () {
    Route::get('/', [DesignationController::class, 'index'])->name('index');
    Route::post('/', [DesignationController::class, 'store'])->name('store');
    Route::put('/{designation}', [DesignationController::class, 'update'])->name('update');
    Route::delete('/{designation}', [DesignationController::class, 'destroy'])->name('destroy');
});

Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('index');
    Route::post('/', [AttendanceController::class, 'store'])->name('store');
    Route::put('/{attendance}', [AttendanceController::class, 'update'])->name('update');
    Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])->name('destroy');
});

Route::prefix('leave')->name('leave.')->group(function () {
    Route::get('/', [LeaveController::class, 'index'])->name('index');
    Route::post('/', [LeaveController::class, 'store'])->name('store');
    Route::put('/{leave}', [LeaveController::class, 'update'])->name('update');
    Route::delete('/{leave}', [LeaveController::class, 'destroy'])->name('destroy');
});

Route::prefix('payroll')->name('payroll.')->group(function () {
    Route::get('/', [PayrollController::class, 'index'])->name('index');
    Route::post('/', [PayrollController::class, 'store'])->name('store');
    Route::put('/{payroll}', [PayrollController::class, 'update'])->name('update');
    Route::delete('/{payroll}', [PayrollController::class, 'destroy'])->name('destroy');
});

Route::prefix('salary-structure')->name('salary-structure.')->group(function () {
    Route::get('/', [SalaryStructureController::class, 'index'])->name('index');
    Route::post('/', [SalaryStructureController::class, 'store'])->name('store');
    Route::put('/{salaryStructure}', [SalaryStructureController::class, 'update'])->name('update');
    Route::delete('/{salaryStructure}', [SalaryStructureController::class, 'destroy'])->name('destroy');
});

PHP);

put("{$hrm}/module.json", moduleJson('Hrm', 'hrm', 'Employees, attendance, leave, and payroll'));

echo "HRM done\n";
