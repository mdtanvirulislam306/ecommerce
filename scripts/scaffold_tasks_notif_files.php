<?php

declare(strict_types=1);

$root = dirname(__DIR__);

function put(string $path, string $contents): void
{
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
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

function moduleJson(string $name, string $code, string $desc): string
{
    return json_encode([
        'name' => $name,
        'code' => $code,
        'version' => '1.0.0',
        'description' => $desc,
        'is_core' => false,
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
    $postOpts = $hasFile
        ? ", { forceFormData: true, preserveScroll: true, onSuccess: () => { showModal.value = false; } }"
        : ", { preserveScroll: true, onSuccess: () => { showModal.value = false; } }";

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
    $formatExtra = $c['format_extra'] ?? '';
    $formOptions = $c['form_options'] ?? null;
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

class {$entity} extends Model
{
    protected \$table = '{$table}';

    protected \$fillable = [
        {$fillStr},
    ];{$castBlock}
}

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
        $createLines[] = isset($create[$f]) ? "            '{$f}' => {$create[$f]}," : "            '{$f}' => \$data['{$f}'] ?? null,";
    }
    $createBody = implode("\n", $createLines);
    $updateLines = [];
    foreach ($fillable as $f) {
        $updateLines[] = "            '{$f}' => array_key_exists('{$f}', \$data) ? \$data['{$f}'] : \$row->{$f},";
    }
    $updateBody = implode("\n", $updateLines);
    $formatLines = ["            'id' => \$row->id,"];
    foreach ($fillable as $f) {
        if (isset($casts[$f]) && str_contains((string) $casts[$f], 'datetime')) {
            $formatLines[] = "            '{$f}' => \$row->{$f}?->toIso8601String(),";
        } elseif (isset($casts[$f]) && str_contains((string) $casts[$f], "'date'")) {
            $formatLines[] = "            '{$f}' => \$row->{$f}?->toDateString(),";
        } else {
            $formatLines[] = "            '{$f}' => \$row->{$f},";
        }
    }
    if ($formatExtra !== '') {
        $formatLines[] = $formatExtra;
    }
    $formatBody = implode("\n", $formatLines);
    $formOptMethod = $formOptions ? "\n\n    public function formOptions(): array\n    {\n{$formOptions}\n    }" : '';

    put("{$base}/Services/{$entity}Service.php", <<<PHP
<?php

namespace Modules\\{$mod}\\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\\{$mod}\\Models\\{$entity};

class {$entity}Service extends Service
{
    public function listPaginated(?string \$search = null, int \$perPage = 25, array \$filters = []): LengthAwarePaginator
    {
        \$perPage = in_array(\$perPage, [10, 25, 50, 100], true) ? \$perPage : 25;

        return {$entity}::query()
            ->when(\$filters['assigned_to'] ?? null, fn (\$q, \$id) => \$q->where('assigned_to', \$id))
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
{$createBody}
        ]);
    }

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

// ================= TASKS =================
$tasks = "{$root}/modules/Tasks";
put("{$tasks}/module.json", moduleJson('Tasks', 'tasks', 'Personal and team tasks'));
put("{$tasks}/Providers/TasksServiceProvider.php", provider('Tasks', 'tasks'));
put("{$tasks}/Database/migrations/2026_09_03_950001_create_tasks_table.php", mig(<<<'PHP'
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('status', 30)->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['assigned_to', 'status']);
        });
PHP, 'tasks'));

entity($root, [
    'module' => 'Tasks', 'code' => 'tasks', 'entity' => 'Task', 'plural' => 'AllTasks',
    'table' => 'tasks', 'route' => 'all-tasks', 'prop' => 'tasks',
    'fillable' => ['title', 'description', 'due_at', 'status', 'assigned_to', 'created_by'],
    'casts' => ['due_at' => "'datetime'"],
    'search' => ['title', 'description', 'status'],
    'rules' => [
        'title' => "['required', 'string', 'max:255']",
        'description' => "['nullable', 'string']",
        'due_at' => "['nullable', 'date']",
        'status' => "['required', 'string', 'max:30']",
        'assigned_to' => "['nullable', 'exists:users,id']",
    ],
    'create' => ['status' => "\$data['status'] ?? 'open'", 'created_by' => 'auth()->id()'],
    'columns' => ['title' => 'Title', 'status' => 'Status', 'due_at' => 'Due'],
    'fields' => [
        'title' => ['label' => 'Title'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'due_at' => ['label' => 'Due at', 'type' => 'datetime-local'],
        'status' => ['label' => 'Status', 'default' => "'open'"],
        'assigned_to' => ['label' => 'Assigned to (user id)', 'type' => 'number', 'default' => 'null'],
    ],
]);

put("{$tasks}/Http/Controllers/MyTaskController.php", <<<'PHP'
<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tasks\Services\TaskService;

class MyTaskController extends Controller
{
    public function index(Request $request, TaskService $tasks): Response
    {
        return Inertia::render('Tasks/MyTasks/Index', [
            'tasks' => $tasks->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
                ['assigned_to' => $request->user()?->id],
            ),
            'filters' => ['search' => $request->string('search')->toString()],
            'options' => [],
        ]);
    }
}

PHP);

// Copy vue for my tasks from all tasks with different route - write dedicated simpler page
$allTasksVue = file_get_contents("{$tasks}/Resources/js/Pages/AllTasks/Index.vue");
$myTasksVue = str_replace('tasks.all-tasks', 'tasks.my-tasks', $allTasksVue);
$myTasksVue = str_replace('AllTasks', 'My Tasks', $myTasksVue);
put("{$tasks}/Resources/js/Pages/MyTasks/Index.vue", $myTasksVue);

put("{$tasks}/Http/Controllers/TaskCalendarController.php", <<<'PHP'
<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TaskCalendarController extends Controller
{
    public function index(): Response
    {
        $items = DB::table('tasks')
            ->whereNotNull('due_at')
            ->orderBy('due_at')
            ->limit(100)
            ->get(['id', 'title', 'due_at', 'status']);

        return Inertia::render('Tasks/Calendar/Index', [
            'items' => $items,
        ]);
    }
}

PHP);

put("{$tasks}/Resources/js/Pages/Calendar/Index.vue", <<<'VUE'
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ items: { type: Array, default: () => [] } });
</script>
<template>
    <Head title="Task Calendar" />
    <AdminLayout title="Task Calendar">
        <section class="admin-card">
            <ul class="divide-y divide-gray-100 text-sm">
                <li v-for="row in items" :key="row.id" class="flex justify-between py-2">
                    <span class="font-medium text-brand-navy">{{ row.title }}</span>
                    <span class="text-gray-500">{{ row.due_at }} · {{ row.status }}</span>
                </li>
                <li v-if="!items.length" class="py-6 text-center text-gray-500">No dated tasks.</li>
            </ul>
        </section>
    </AdminLayout>
</template>
VUE);

put("{$tasks}/Http/Controllers/TaskTimelineController.php", <<<'PHP'
<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TaskTimelineController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Tasks/Timeline/Index', [
            'items' => DB::table('tasks')->orderByDesc('created_at')->limit(50)->get(['id', 'title', 'status', 'created_at']),
        ]);
    }
}

PHP);

put("{$tasks}/Resources/js/Pages/Timeline/Index.vue", <<<'VUE'
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ items: { type: Array, default: () => [] } });
</script>
<template>
    <Head title="Activity Timeline" />
    <AdminLayout title="Activity Timeline">
        <section class="admin-card">
            <ul class="divide-y divide-gray-100 text-sm">
                <li v-for="row in items" :key="row.id" class="flex justify-between py-2">
                    <span class="font-medium text-brand-navy">{{ row.title }}</span>
                    <span class="text-gray-500">{{ row.created_at }}</span>
                </li>
            </ul>
        </section>
    </AdminLayout>
</template>
VUE);

put("{$tasks}/Http/Controllers/TaskCreateController.php", <<<'PHP'
<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tasks\Http\Requests\StoreTaskRequest;
use Modules\Tasks\Services\TaskService;

class TaskCreateController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Tasks/Create/Index');
    }

    public function store(StoreTaskRequest $request, TaskService $tasks): RedirectResponse
    {
        $tasks->create($request->validated());

        return redirect()->route('tasks.my-tasks.index')->with('success', 'Task created.');
    }
}

PHP);

put("{$tasks}/Resources/js/Pages/Create/Index.vue", <<<'VUE'
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
VUE);

put("{$tasks}/Routes/web.php", <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Http\Controllers\MyTaskController;
use Modules\Tasks\Http\Controllers\TaskCalendarController;
use Modules\Tasks\Http\Controllers\TaskController;
use Modules\Tasks\Http\Controllers\TaskCreateController;
use Modules\Tasks\Http\Controllers\TaskTimelineController;

Route::get('/my-tasks', [MyTaskController::class, 'index'])->name('my-tasks.index');
Route::get('/calendar', [TaskCalendarController::class, 'index'])->name('calendar');
Route::get('/timeline', [TaskTimelineController::class, 'index'])->name('timeline');
Route::get('/create', [TaskCreateController::class, 'create'])->name('create');
Route::post('/create', [TaskCreateController::class, 'store'])->name('create.store');

Route::prefix('all-tasks')->name('all-tasks.')->group(function () {
    Route::get('/', [TaskController::class, 'index'])->name('index');
    Route::post('/', [TaskController::class, 'store'])->name('store');
    Route::put('/{task}', [TaskController::class, 'update'])->name('update');
    Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
});

PHP);

echo "Tasks done\n";

// ================= NOTIFICATIONS =================
$notif = "{$root}/modules/Notifications";
put("{$notif}/module.json", moduleJson('Notifications', 'notifications', 'Notification center, templates, and channel preferences'));
put("{$notif}/Providers/NotificationsServiceProvider.php", provider('Notifications', 'notifications'));

put("{$notif}/Database/migrations/2026_09_03_960001_create_notification_templates_table.php", mig(<<<'PHP'
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('channel', 40)->default('email');
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
PHP, 'notification_templates'));

put("{$notif}/Database/migrations/2026_09_03_960002_create_user_notifications_table.php", mig(<<<'PHP'
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('channel', 40)->default('in_app');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });
PHP, 'user_notifications'));

put("{$notif}/Database/migrations/2026_09_03_960003_create_notification_channel_preferences_table.php", mig(<<<'PHP'
        Schema::create('notification_channel_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel', 40);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'channel']);
        });
PHP, 'notification_channel_preferences'));

entity($root, [
    'module' => 'Notifications', 'code' => 'notifications', 'entity' => 'NotificationTemplate', 'plural' => 'Templates',
    'table' => 'notification_templates', 'route' => 'templates', 'prop' => 'templates', 'param' => 'notificationTemplate',
    'fillable' => ['name', 'channel', 'subject', 'body', 'is_active'],
    'casts' => ['is_active' => "'boolean'"],
    'search' => ['name', 'channel', 'subject'],
    'rules' => [
        'name' => "['required', 'string', 'max:255']",
        'channel' => "['required', 'string', 'max:40']",
        'subject' => "['nullable', 'string', 'max:255']",
        'body' => "['nullable', 'string']",
        'is_active' => "['boolean']",
    ],
    'create' => ['is_active' => "\$data['is_active'] ?? true", 'channel' => "\$data['channel'] ?? 'email'"],
    'columns' => ['name' => 'Name', 'channel' => 'Channel', 'subject' => 'Subject', 'is_active' => 'Active'],
    'fields' => [
        'name' => ['label' => 'Name'],
        'channel' => ['label' => 'Channel', 'default' => "'email'"],
        'subject' => ['label' => 'Subject'],
        'body' => ['label' => 'Body', 'type' => 'textarea'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

put("{$notif}/Models/UserNotification.php", <<<'PHP'
<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $table = 'user_notifications';

    protected $fillable = ['user_id', 'title', 'body', 'channel', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}

PHP);

put("{$notif}/Services/NotificationCenterService.php", <<<'PHP'
<?php

namespace Modules\Notifications\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Notifications\Models\UserNotification;

class NotificationCenterService extends Service
{
    public function listForUser(int $userId, int $perPage = 25): LengthAwarePaginator
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (UserNotification $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'channel' => $n->channel,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String(),
            ]);
    }

    public function markRead(UserNotification $notification): void
    {
        $notification->update(['read_at' => now()]);
    }
}

PHP);

put("{$notif}/Http/Controllers/NotificationCenterController.php", <<<'PHP'
<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Notifications\Models\UserNotification;
use Modules\Notifications\Services\NotificationCenterService;

class NotificationCenterController extends Controller
{
    public function index(Request $request, NotificationCenterService $svc): Response
    {
        return Inertia::render('Notifications/Center/Index', [
            'notifications' => $svc->listForUser((int) $request->user()->id),
        ]);
    }

    public function markRead(UserNotification $userNotification, NotificationCenterService $svc): RedirectResponse
    {
        abort_unless($userNotification->user_id === auth()->id(), 403);
        $svc->markRead($userNotification);

        return back()->with('success', 'Marked as read.');
    }
}

PHP);

put("{$notif}/Resources/js/Pages/Center/Index.vue", <<<'VUE'
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import { Head, router } from '@inertiajs/vue3';
defineProps({ notifications: { type: Object, required: true } });
</script>
<template>
    <Head title="Notification Center" />
    <AdminLayout title="Notification Center">
        <section class="admin-card">
            <ul class="divide-y divide-gray-100 text-sm">
                <li v-for="row in notifications.data" :key="row.id" class="flex items-start justify-between gap-3 py-3">
                    <div>
                        <p class="font-medium text-brand-navy">{{ row.title }}</p>
                        <p class="text-gray-500">{{ row.body }}</p>
                    </div>
                    <button v-if="!row.read_at" type="button" class="text-xs text-brand-orange" @click="router.post(route('notifications.center.read', row.id))">Mark read</button>
                    <span v-else class="text-xs text-gray-400">Read</span>
                </li>
            </ul>
            <TablePagination :paginator="notifications" class="mt-4" />
        </section>
    </AdminLayout>
</template>
VUE);

put("{$notif}/Models/ChannelPreference.php", <<<'PHP'
<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelPreference extends Model
{
    protected $table = 'notification_channel_preferences';

    protected $fillable = ['user_id', 'channel', 'enabled'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}

PHP);

put("{$notif}/Services/ChannelPreferenceService.php", <<<'PHP'
<?php

namespace Modules\Notifications\Services;

use App\Core\Support\Service;
use Modules\Notifications\Models\ChannelPreference;

class ChannelPreferenceService extends Service
{
    public function forUser(int $userId): array
    {
        $channels = ['email', 'sms', 'whatsapp', 'push'];
        $existing = ChannelPreference::query()->where('user_id', $userId)->get()->keyBy('channel');

        return collect($channels)->map(fn ($channel) => [
            'channel' => $channel,
            'enabled' => $existing->get($channel)?->enabled ?? true,
        ])->all();
    }

    /** @param  array<int, array{channel: string, enabled: bool}>  $prefs */
    public function save(int $userId, array $prefs): void
    {
        foreach ($prefs as $pref) {
            ChannelPreference::query()->updateOrCreate(
                ['user_id' => $userId, 'channel' => $pref['channel']],
                ['enabled' => (bool) ($pref['enabled'] ?? true)],
            );
        }
    }
}

PHP);

foreach (['email', 'sms', 'whatsapp', 'push'] as $ch) {
    $studly = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $ch)));
    put("{$notif}/Http/Controllers/{$studly}ChannelController.php", <<<PHP
<?php

namespace Modules\\Notifications\\Http\\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\\Notifications\\Services\\ChannelPreferenceService;

class {$studly}ChannelController extends Controller
{
    public function edit(Request \$request, ChannelPreferenceService \$prefs): Response
    {
        return Inertia::render('Notifications/Channels/Index', [
            'channel' => '{$ch}',
            'preferences' => \$prefs->forUser((int) \$request->user()->id),
        ]);
    }

    public function update(Request \$request, ChannelPreferenceService \$prefs): RedirectResponse
    {
        \$data = \$request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.channel' => ['required', 'string'],
            'preferences.*.enabled' => ['boolean'],
        ]);
        \$prefs->save((int) \$request->user()->id, \$data['preferences']);

        return back()->with('success', 'Channel preferences saved.');
    }
}

PHP);
}

put("{$notif}/Resources/js/Pages/Channels/Index.vue", <<<'VUE'
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    channel: { type: String, required: true },
    preferences: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash);
const form = useForm({ preferences: props.preferences.map((p) => ({ ...p })) });
const submit = () => form.put(route(`notifications.${props.channel}.update`), { preserveScroll: true });
</script>
<template>
    <Head :title="`${channel} notifications`" />
    <AdminLayout :title="`${channel} channel`">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm text-brand-navy">{{ flash.success }}</div>
        <form class="admin-card max-w-lg space-y-3" @submit.prevent="submit">
            <label v-for="(pref, idx) in form.preferences" :key="pref.channel" class="flex items-center gap-2 text-sm">
                <Checkbox v-model:checked="form.preferences[idx].enabled" />
                {{ pref.channel }}
            </label>
            <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>
        </form>
    </AdminLayout>
</template>
VUE);

put("{$notif}/Routes/web.php", <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\EmailChannelController;
use Modules\Notifications\Http\Controllers\NotificationCenterController;
use Modules\Notifications\Http\Controllers\NotificationTemplateController;
use Modules\Notifications\Http\Controllers\PushChannelController;
use Modules\Notifications\Http\Controllers\SmsChannelController;
use Modules\Notifications\Http\Controllers\WhatsappChannelController;

Route::get('/center', [NotificationCenterController::class, 'index'])->name('center');
Route::post('/center/{userNotification}/read', [NotificationCenterController::class, 'markRead'])->name('center.read');

Route::prefix('templates')->name('templates.')->group(function () {
    Route::get('/', [NotificationTemplateController::class, 'index'])->name('index');
    Route::post('/', [NotificationTemplateController::class, 'store'])->name('store');
    Route::put('/{notificationTemplate}', [NotificationTemplateController::class, 'update'])->name('update');
    Route::delete('/{notificationTemplate}', [NotificationTemplateController::class, 'destroy'])->name('destroy');
});

Route::get('/email', [EmailChannelController::class, 'edit'])->name('email.edit');
Route::put('/email', [EmailChannelController::class, 'update'])->name('email.update');
Route::get('/sms', [SmsChannelController::class, 'edit'])->name('sms.edit');
Route::put('/sms', [SmsChannelController::class, 'update'])->name('sms.update');
Route::get('/whatsapp', [WhatsappChannelController::class, 'edit'])->name('whatsapp.edit');
Route::put('/whatsapp', [WhatsappChannelController::class, 'update'])->name('whatsapp.update');
Route::get('/push', [PushChannelController::class, 'edit'])->name('push.edit');
Route::put('/push', [PushChannelController::class, 'update'])->name('push.update');

PHP);

echo "Notifications done\n";

// ================= FILES =================
$files = "{$root}/modules/Files";
put("{$files}/module.json", moduleJson('Files', 'files', 'Media library and documents'));
put("{$files}/Providers/FilesServiceProvider.php", provider('Files', 'files'));

put("{$files}/Database/migrations/2026_09_03_970001_create_media_library_items_table.php", mig(<<<'PHP'
        Schema::create('media_library_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('disk', 40)->default('public');
            $table->string('path');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
PHP, 'media_library_items'));

put("{$files}/Database/migrations/2026_09_03_970002_create_documents_table.php", mig(<<<'PHP'
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('disk', 40)->default('public');
            $table->string('path');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
PHP, 'documents'));

put("{$files}/Models/MediaLibraryItem.php", <<<'PHP'
<?php

namespace Modules\Files\Models;

use Illuminate\Database\Eloquent\Model;

class MediaLibraryItem extends Model
{
    protected $fillable = ['name', 'disk', 'path', 'mime', 'size', 'uploaded_by'];
}

PHP);

put("{$files}/Models/Document.php", <<<'PHP'
<?php

namespace Modules\Files\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['title', 'disk', 'path', 'mime', 'size', 'uploaded_by'];
}

PHP);

put("{$files}/Services/MediaLibraryService.php", <<<'PHP'
<?php

namespace Modules\Files\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Files\Models\MediaLibraryItem;

class MediaLibraryService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return MediaLibraryItem::query()
            ->when($search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (MediaLibraryItem $row) => [
                'id' => $row->id,
                'name' => $row->name,
                'path' => $row->path,
                'url' => Storage::disk($row->disk)->url($row->path),
                'mime' => $row->mime,
                'size' => $row->size,
            ]);
    }

    public function upload(UploadedFile $file, ?int $userId = null): MediaLibraryItem
    {
        $path = $file->store('media', 'public');

        return MediaLibraryItem::query()->create([
            'name' => $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $userId,
        ]);
    }

    public function delete(MediaLibraryItem $item): void
    {
        Storage::disk($item->disk)->delete($item->path);
        $item->delete();
    }
}

PHP);

put("{$files}/Services/DocumentService.php", <<<'PHP'
<?php

namespace Modules\Files\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Files\Models\Document;

class DocumentService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Document::query()
            ->when($search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Document $row) => [
                'id' => $row->id,
                'title' => $row->title,
                'path' => $row->path,
                'url' => Storage::disk($row->disk)->url($row->path),
                'mime' => $row->mime,
                'size' => $row->size,
            ]);
    }

    public function upload(string $title, UploadedFile $file, ?int $userId = null): Document
    {
        $path = $file->store('documents', 'public');

        return Document::query()->create([
            'title' => $title,
            'disk' => 'public',
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $userId,
        ]);
    }

    public function delete(Document $document): void
    {
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }
}

PHP);

put("{$files}/Http/Requests/StoreMediaRequest.php", <<<'PHP'
<?php

namespace Modules\Files\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'max:10240']];
    }
}

PHP);

put("{$files}/Http/Requests/StoreDocumentRequest.php", <<<'PHP'
<?php

namespace Modules\Files\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:20480'],
        ];
    }
}

PHP);

put("{$files}/Http/Controllers/MediaLibraryController.php", <<<'PHP'
<?php

namespace Modules\Files\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Files\Http\Requests\StoreMediaRequest;
use Modules\Files\Models\MediaLibraryItem;
use Modules\Files\Services\MediaLibraryService;

class MediaLibraryController extends Controller
{
    public function index(Request $request, MediaLibraryService $media): Response
    {
        return Inertia::render('Files/MediaLibrary/Index', [
            'items' => $media->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function store(StoreMediaRequest $request, MediaLibraryService $media): RedirectResponse
    {
        $media->upload($request->file('file'), $request->user()?->id);

        return back()->with('success', 'Media uploaded.');
    }

    public function destroy(MediaLibraryItem $mediaLibraryItem, MediaLibraryService $media): RedirectResponse
    {
        $media->delete($mediaLibraryItem);

        return back()->with('success', 'Media deleted.');
    }
}

PHP);

put("{$files}/Http/Controllers/DocumentController.php", <<<'PHP'
<?php

namespace Modules\Files\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Files\Http\Requests\StoreDocumentRequest;
use Modules\Files\Models\Document;
use Modules\Files\Services\DocumentService;

class DocumentController extends Controller
{
    public function index(Request $request, DocumentService $documents): Response
    {
        return Inertia::render('Files/Documents/Index', [
            'documents' => $documents->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
            ),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function store(StoreDocumentRequest $request, DocumentService $documents): RedirectResponse
    {
        $documents->upload($request->string('title')->toString(), $request->file('file'), $request->user()?->id);

        return back()->with('success', 'Document uploaded.');
    }

    public function destroy(Document $document, DocumentService $documents): RedirectResponse
    {
        $documents->delete($document);

        return back()->with('success', 'Document deleted.');
    }
}

PHP);

put("{$files}/Resources/js/Pages/MediaLibrary/Index.vue", <<<'VUE'
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({ items: { type: Object, required: true }, filters: { type: Object, default: () => ({}) } });
const flash = computed(() => usePage().props.flash);
const search = ref(props.filters.search ?? '');
const form = useForm({ file: null });
const onFile = (e) => { form.file = e.target.files[0]; };
const submit = () => form.post(route('files.media-library.store'), { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() });
watch(search, (value) => router.get(route('files.media-library.index'), { search: value || undefined }, { preserveState: true, replace: true }));
</script>
<template>
    <Head title="Media Library" />
    <AdminLayout title="Media Library">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search…" />
            <input type="file" @change="onFile" />
            <PrimaryButton type="button" :disabled="form.processing || !form.file" @click="submit">Upload</PrimaryButton>
        </div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Name</th><th class="pb-2">MIME</th><th class="pb-2">Size</th><th /></tr></thead>
                <tbody>
                    <tr v-for="row in items.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2"><a :href="row.url" target="_blank" class="text-brand-orange">{{ row.name }}</a></td>
                        <td class="py-2">{{ row.mime }}</td>
                        <td class="py-2">{{ row.size }}</td>
                        <td class="py-2 text-right"><button type="button" class="text-xs text-red-600" @click="router.delete(route('files.media-library.destroy', row.id))">Delete</button></td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="items" class="mt-4" />
        </section>
    </AdminLayout>
</template>
VUE);

put("{$files}/Resources/js/Pages/Documents/Index.vue", <<<'VUE'
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TablePagination from '@/Components/Admin/TablePagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({ documents: { type: Object, required: true }, filters: { type: Object, default: () => ({}) } });
const flash = computed(() => usePage().props.flash);
const search = ref(props.filters.search ?? '');
const form = useForm({ title: '', file: null });
const onFile = (e) => { form.file = e.target.files[0]; };
const submit = () => form.post(route('files.documents.store'), { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() });
watch(search, (value) => router.get(route('files.documents.index'), { search: value || undefined }, { preserveState: true, replace: true }));
</script>
<template>
    <Head title="Documents" />
    <AdminLayout title="Documents">
        <div v-if="flash?.success" class="mb-4 rounded-lg bg-brand-teal/10 px-4 py-3 text-sm">{{ flash.success }}</div>
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <TextInput v-model="search" type="search" class="w-64" placeholder="Search…" />
            <div><InputLabel value="Title" /><TextInput v-model="form.title" class="mt-1" /></div>
            <input type="file" @change="onFile" />
            <PrimaryButton type="button" :disabled="form.processing || !form.file || !form.title" @click="submit">Upload</PrimaryButton>
        </div>
        <section class="admin-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Title</th><th class="pb-2">MIME</th><th class="pb-2">Size</th><th /></tr></thead>
                <tbody>
                    <tr v-for="row in documents.data" :key="row.id" class="border-t border-gray-50">
                        <td class="py-2"><a :href="row.url" target="_blank" class="text-brand-orange">{{ row.title }}</a></td>
                        <td class="py-2">{{ row.mime }}</td>
                        <td class="py-2">{{ row.size }}</td>
                        <td class="py-2 text-right"><button type="button" class="text-xs text-red-600" @click="router.delete(route('files.documents.destroy', row.id))">Delete</button></td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="documents" class="mt-4" />
        </section>
    </AdminLayout>
</template>
VUE);

put("{$files}/Http/Controllers/AttachmentsController.php", <<<'PHP'
<?php

namespace Modules\Files\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AttachmentsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Files/Attachments/Index');
    }
}

PHP);

put("{$files}/Resources/js/Pages/Attachments/Index.vue", <<<'VUE'
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>
<template>
    <Head title="Attachments" />
    <AdminLayout title="Attachments">
        <div class="admin-card text-sm text-gray-600">
            Attachments are stored with Media Library and Documents.
            <div class="mt-3 space-x-3">
                <Link :href="route('files.media-library.index')" class="text-brand-orange">Media library</Link>
                <Link :href="route('files.documents.index')" class="text-brand-orange">Documents</Link>
            </div>
        </div>
    </AdminLayout>
</template>
VUE);

put("{$files}/Http/Controllers/StorageController.php", <<<'PHP'
<?php

namespace Modules\Files\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class StorageController extends Controller
{
    public function index(): Response
    {
        $media = Schema::hasTable('media_library_items') ? (int) DB::table('media_library_items')->sum('size') : 0;
        $docs = Schema::hasTable('documents') ? (int) DB::table('documents')->sum('size') : 0;

        return Inertia::render('Files/Storage/Index', [
            'stats' => [
                'media_bytes' => $media,
                'document_bytes' => $docs,
                'total_bytes' => $media + $docs,
            ],
        ]);
    }
}

PHP);

put("{$files}/Resources/js/Pages/Storage/Index.vue", overviewVue('Storage'));

put("{$files}/Routes/web.php", <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use Modules\Files\Http\Controllers\AttachmentsController;
use Modules\Files\Http\Controllers\DocumentController;
use Modules\Files\Http\Controllers\MediaLibraryController;
use Modules\Files\Http\Controllers\StorageController;

Route::prefix('media-library')->name('media-library.')->group(function () {
    Route::get('/', [MediaLibraryController::class, 'index'])->name('index');
    Route::post('/', [MediaLibraryController::class, 'store'])->name('store');
    Route::delete('/{mediaLibraryItem}', [MediaLibraryController::class, 'destroy'])->name('destroy');
});

Route::prefix('documents')->name('documents.')->group(function () {
    Route::get('/', [DocumentController::class, 'index'])->name('index');
    Route::post('/', [DocumentController::class, 'store'])->name('store');
    Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
});

Route::get('/attachments', [AttachmentsController::class, 'index'])->name('attachments');
Route::get('/storage', [StorageController::class, 'index'])->name('storage');

PHP);

echo "Files done\n";
