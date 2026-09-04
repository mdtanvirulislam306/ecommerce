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

// ---------- Settings routes ----------
put("{$root}/modules/Settings/Routes/web.php", <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\ApiSettingsController;
use Modules\Settings\Http\Controllers\AreasController;
use Modules\Settings\Http\Controllers\AuditLogController;
use Modules\Settings\Http\Controllers\BranchesController;
use Modules\Settings\Http\Controllers\BusinessProfileController;
use Modules\Settings\Http\Controllers\CurrencyController;
use Modules\Settings\Http\Controllers\DocumentTemplatesController;
use Modules\Settings\Http\Controllers\GeneralSettingsController;
use Modules\Settings\Http\Controllers\IntegrationsController;
use Modules\Settings\Http\Controllers\LocalizationController;
use Modules\Settings\Http\Controllers\ModulesController;
use Modules\Settings\Http\Controllers\NotificationsController;
use Modules\Settings\Http\Controllers\NumberingSeriesController;
use Modules\Settings\Http\Controllers\PaymentMethodController;
use Modules\Settings\Http\Controllers\RegionsController;
use Modules\Settings\Http\Controllers\RoleController;
use Modules\Settings\Http\Controllers\ShippingMethodController;
use Modules\Settings\Http\Controllers\SubscriptionController;
use Modules\Settings\Http\Controllers\SystemController;
use Modules\Settings\Http\Controllers\TaxRateController;
use Modules\Settings\Http\Controllers\UserListController;
use Modules\Settings\Http\Controllers\WarehousesController;

Route::get('/general', [GeneralSettingsController::class, 'edit'])->name('general.edit');
Route::put('/general', [GeneralSettingsController::class, 'update'])->name('general.update');

Route::get('/business/company', [BusinessProfileController::class, 'edit'])->name('business.company.edit');
Route::put('/business/company', [BusinessProfileController::class, 'update'])->name('business.company.update');
Route::get('/business/branches', [BranchesController::class, 'index'])->name('business.branches');
Route::get('/business/regions', [RegionsController::class, 'index'])->name('business.regions');
Route::get('/business/areas', [AreasController::class, 'index'])->name('business.areas');

Route::get('/warehouses', [WarehousesController::class, 'index'])->name('warehouses');
Route::get('/users', [UserListController::class, 'index'])->name('users.index');
Route::get('/modules', [ModulesController::class, 'index'])->name('modules');
Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription');
Route::get('/document-templates', [DocumentTemplatesController::class, 'index'])->name('document-templates');
Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications');
Route::get('/system', [SystemController::class, 'index'])->name('system');

Route::get('/localization', [LocalizationController::class, 'edit'])->name('localization.edit');
Route::put('/localization', [LocalizationController::class, 'update'])->name('localization.update');
Route::get('/integrations', [IntegrationsController::class, 'edit'])->name('integrations.edit');
Route::put('/integrations', [IntegrationsController::class, 'update'])->name('integrations.update');
Route::get('/api', [ApiSettingsController::class, 'edit'])->name('api.edit');
Route::put('/api', [ApiSettingsController::class, 'update'])->name('api.update');

Route::prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::put('/{role}', [RoleController::class, 'update'])->name('update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
});

Route::prefix('numbering')->name('numbering.')->group(function () {
    Route::get('/', [NumberingSeriesController::class, 'index'])->name('index');
    Route::post('/', [NumberingSeriesController::class, 'store'])->name('store');
    Route::put('/{numberingSeries}', [NumberingSeriesController::class, 'update'])->name('update');
    Route::delete('/{numberingSeries}', [NumberingSeriesController::class, 'destroy'])->name('destroy');
});

Route::prefix('tax')->name('tax.')->group(function () {
    Route::get('/', [TaxRateController::class, 'index'])->name('index');
    Route::post('/', [TaxRateController::class, 'store'])->name('store');
    Route::put('/{taxRate}', [TaxRateController::class, 'update'])->name('update');
    Route::delete('/{taxRate}', [TaxRateController::class, 'destroy'])->name('destroy');
});

Route::prefix('currency')->name('currency.')->group(function () {
    Route::get('/', [CurrencyController::class, 'index'])->name('index');
    Route::post('/', [CurrencyController::class, 'store'])->name('store');
    Route::put('/{currency}', [CurrencyController::class, 'update'])->name('update');
    Route::delete('/{currency}', [CurrencyController::class, 'destroy'])->name('destroy');
});

Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
    Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
    Route::post('/', [PaymentMethodController::class, 'store'])->name('store');
    Route::put('/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('update');
    Route::delete('/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('destroy');
});

Route::prefix('shipping-methods')->name('shipping-methods.')->group(function () {
    Route::get('/', [ShippingMethodController::class, 'index'])->name('index');
    Route::post('/', [ShippingMethodController::class, 'store'])->name('store');
    Route::put('/{shippingMethod}', [ShippingMethodController::class, 'update'])->name('update');
    Route::delete('/{shippingMethod}', [ShippingMethodController::class, 'destroy'])->name('destroy');
});

Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
    Route::get('/', [AuditLogController::class, 'index'])->name('index');
    Route::post('/', [AuditLogController::class, 'store'])->name('store');
    Route::put('/{auditLog}', [AuditLogController::class, 'update'])->name('update');
    Route::delete('/{auditLog}', [AuditLogController::class, 'destroy'])->name('destroy');
});

PHP);

echo "Settings routes written\n";

// ---------- REPORTS ----------
$rep = "{$root}/modules/Reports";
put("{$rep}/module.json", moduleJson('Reports', 'reports', 'Cross-module operational dashboards'));
put("{$rep}/Providers/ReportsServiceProvider.php", provider('Reports', 'reports'));

put("{$rep}/Services/ReportDashboardService.php", <<<'PHP'
<?php

namespace Modules\Reports\Services;

use App\Core\Support\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportDashboardService extends Service
{
    public function overview(): array
    {
        return [
            'sales_orders' => $this->countIf('sales_orders'),
            'purchase_orders' => $this->countIf('purchase_orders'),
            'products' => $this->countIf('products'),
            'customers' => $this->countIf('customers'),
            'online_orders' => $this->countIf('online_orders'),
            'pos_sales' => $this->countIf('pos_sales'),
            'employees' => $this->countIf('hrm_employees'),
            'tickets' => $this->countIf('support_tickets'),
        ];
    }

    public function sales(): array
    {
        return [
            'orders' => $this->countIf('sales_orders'),
            'confirmed' => $this->countWhere('sales_orders', 'status', 'confirmed'),
            'cancelled' => $this->countWhere('sales_orders', 'status', 'cancelled'),
            'total_amount' => $this->sumIf('sales_orders', 'grand_total'),
        ];
    }

    public function purchase(): array
    {
        return [
            'orders' => $this->countIf('purchase_orders'),
            'received' => $this->countWhere('purchase_orders', 'status', 'received'),
            'total_amount' => $this->sumIf('purchase_orders', 'grand_total'),
        ];
    }

    public function inventory(): array
    {
        return [
            'warehouses' => $this->countIf('warehouses'),
            'stock_rows' => $this->countIf('stock_levels'),
            'movements' => $this->countIf('stock_movements'),
            'on_hand' => $this->sumIf('stock_levels', 'quantity'),
        ];
    }

    public function crm(): array
    {
        return [
            'leads' => $this->countIf('leads'),
            'customers' => $this->countIf('customers'),
            'activities' => $this->countIf('crm_activities'),
        ];
    }

    public function ecommerce(): array
    {
        return [
            'orders' => $this->countIf('online_orders'),
            'pending' => $this->countWhere('online_orders', 'status', 'pending'),
            'confirmed' => $this->countWhere('online_orders', 'status', 'confirmed'),
        ];
    }

    public function pos(): array
    {
        return [
            'sales' => $this->countIf('pos_sales'),
            'sessions' => $this->countIf('pos_sessions'),
            'total_amount' => $this->sumIf('pos_sales', 'grand_total'),
        ];
    }

    public function accounting(): array
    {
        return [
            'accounts' => $this->countIf('accounts'),
            'journals' => $this->countIf('journal_entries'),
        ];
    }

    public function hr(): array
    {
        return [
            'employees' => $this->countIf('hrm_employees'),
            'attendance' => $this->countIf('hrm_attendances'),
            'leave' => $this->countIf('hrm_leaves'),
            'payroll' => $this->countIf('hrm_payrolls'),
        ];
    }

    public function custom(): array
    {
        return ['note' => 'Custom report builder lands in a later phase.', 'saved_reports' => 0];
    }

    private function countIf(string $table): int
    {
        return Schema::hasTable($table) ? (int) DB::table($table)->count() : 0;
    }

    private function countWhere(string $table, string $column, mixed $value): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return (int) DB::table($table)->where($column, $value)->count();
    }

    private function sumIf(string $table, string $column): float
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return (float) DB::table($table)->sum($column);
    }
}

PHP);

$reportPages = ['overview', 'sales', 'purchase', 'inventory', 'crm', 'ecommerce', 'pos', 'accounting', 'hr', 'custom'];
$methods = '';
$routes = '';
foreach ($reportPages as $page) {
    $title = ucwords(str_replace('-', ' ', $page));
    $folder = str_replace(' ', '', ucwords(str_replace('-', ' ', $page)));
    put("{$rep}/Resources/js/Pages/{$folder}/Index.vue", overviewVue("Reports — {$title}"));
    $methods .= "    public function {$page}(ReportDashboardService \$reports): Response\n    {\n        return Inertia::render('Reports/{$folder}/Index', [\n            'stats' => \$reports->{$page}(),\n        ]);\n    }\n\n";
    $routes .= "Route::get('/{$page}', [ReportController::class, '{$page}'])->name('{$page}');\n";
}

put("{$rep}/Http/Controllers/ReportController.php", <<<PHP
<?php

namespace Modules\\Reports\\Http\\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\\Reports\\Services\\ReportDashboardService;

class ReportController extends Controller
{
{$methods}}

PHP);

put("{$rep}/Routes/web.php", <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use Modules\\Reports\\Http\\Controllers\\ReportController;

{$routes}
PHP);

echo "Reports done\n";

// ---------- WORKFLOW ----------
$wf = "{$root}/modules/Workflow";
put("{$wf}/module.json", moduleJson('Workflow', 'workflow', 'Approvals, automation rules, and scheduled tasks'));
put("{$wf}/Providers/WorkflowServiceProvider.php", provider('Workflow', 'workflow'));

put("{$wf}/Database/migrations/2026_09_03_940001_create_workflows_table.php", mig(<<<'PHP'
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('definition')->nullable();
            $table->timestamps();
        });
PHP, 'workflows'));

put("{$wf}/Database/migrations/2026_09_03_940002_create_approval_requests_table.php", mig(<<<'PHP'
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status', 30)->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
PHP, 'approval_requests'));

put("{$wf}/Database/migrations/2026_09_03_940003_create_approval_policies_table.php", mig(<<<'PHP'
        Schema::create('approval_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('entity_type', 80)->nullable();
            $table->json('steps')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
PHP, 'approval_policies'));

put("{$wf}/Database/migrations/2026_09_03_940004_create_automation_rules_table.php", mig(<<<'PHP'
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event', 120)->nullable();
            $table->json('conditions')->nullable();
            $table->json('actions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
PHP, 'automation_rules'));

put("{$wf}/Database/migrations/2026_09_03_940005_create_scheduled_tasks_table.php", mig(<<<'PHP'
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cron', 80)->default('0 * * * *');
            $table->string('handler', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
PHP, 'scheduled_tasks'));

put("{$wf}/Database/migrations/2026_09_03_940006_create_workflow_logs_table.php", mig(<<<'PHP'
        Schema::create('workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 80)->nullable();
            $table->string('message');
            $table->string('level', 20)->default('info');
            $table->json('context')->nullable();
            $table->timestamps();
        });
PHP, 'workflow_logs'));

put("{$wf}/Http/Controllers/WorkflowOverviewController.php", <<<'PHP'
<?php

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowOverviewController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Workflow/Overview/Index', [
            'stats' => [
                'workflows' => DB::table('workflows')->count(),
                'pending_approvals' => DB::table('approval_requests')->where('status', 'pending')->count(),
                'automation_rules' => DB::table('automation_rules')->count(),
                'scheduled_tasks' => DB::table('scheduled_tasks')->count(),
            ],
        ]);
    }
}

PHP);
put("{$wf}/Resources/js/Pages/Overview/Index.vue", overviewVue('Workflow Overview'));

entity($root, [
    'module' => 'Workflow', 'code' => 'workflow', 'entity' => 'WorkflowDefinition', 'plural' => 'Workflows',
    'table' => 'workflows', 'route' => 'workflows', 'prop' => 'workflows', 'param' => 'workflowDefinition',
    'fillable' => ['name', 'trigger', 'is_active', 'definition'],
    'casts' => ['is_active' => "'boolean'", 'definition' => "'array'"],
    'search' => ['name', 'trigger'],
    'rules' => [
        'name' => "['required', 'string', 'max:255']",
        'trigger' => "['nullable', 'string', 'max:80']",
        'is_active' => "['boolean']",
        'definition' => "['nullable']",
    ],
    'create' => [
        'is_active' => "\$data['is_active'] ?? true",
        'definition' => "is_string(\$data['definition'] ?? null) ? json_decode(\$data['definition'], true) : (\$data['definition'] ?? [])",
    ],
    'format_extra' => "            'definition_json' => json_encode(\$row->definition ?? []),",
    'columns' => ['name' => 'Name', 'trigger' => 'Trigger', 'is_active' => 'Active'],
    'fields' => [
        'name' => ['label' => 'Name'],
        'trigger' => ['label' => 'Trigger'],
        'definition' => ['label' => 'Definition JSON', 'type' => 'textarea', 'default' => "'{}'", 'edit' => "row.definition_json || '{}'"],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

entity($root, [
    'module' => 'Workflow', 'code' => 'workflow', 'entity' => 'ApprovalRequest', 'plural' => 'ApprovalRequests',
    'table' => 'approval_requests', 'route' => 'approval-requests', 'prop' => 'requests', 'param' => 'approvalRequest',
    'fillable' => ['title', 'status', 'requested_by', 'approver_id', 'notes'],
    'search' => ['title', 'status'],
    'rules' => [
        'title' => "['required', 'string', 'max:255']",
        'status' => "['required', 'string', 'max:30']",
        'notes' => "['nullable', 'string']",
    ],
    'create' => ['status' => "\$data['status'] ?? 'pending'"],
    'columns' => ['title' => 'Title', 'status' => 'Status'],
    'fields' => [
        'title' => ['label' => 'Title'],
        'status' => ['label' => 'Status', 'default' => "'pending'"],
        'notes' => ['label' => 'Notes', 'type' => 'textarea'],
    ],
]);

entity($root, [
    'module' => 'Workflow', 'code' => 'workflow', 'entity' => 'ApprovalPolicy', 'plural' => 'ApprovalPolicies',
    'table' => 'approval_policies', 'route' => 'approval-policies', 'prop' => 'policies', 'param' => 'approvalPolicy',
    'fillable' => ['name', 'entity_type', 'steps', 'is_active'],
    'casts' => ['steps' => "'array'", 'is_active' => "'boolean'"],
    'search' => ['name', 'entity_type'],
    'rules' => [
        'name' => "['required', 'string', 'max:255']",
        'entity_type' => "['nullable', 'string', 'max:80']",
        'steps' => "['nullable']",
        'is_active' => "['boolean']",
    ],
    'create' => [
        'is_active' => "\$data['is_active'] ?? true",
        'steps' => "is_string(\$data['steps'] ?? null) ? json_decode(\$data['steps'], true) : (\$data['steps'] ?? [])",
    ],
    'format_extra' => "            'steps_json' => json_encode(\$row->steps ?? []),",
    'columns' => ['name' => 'Name', 'entity_type' => 'Entity', 'is_active' => 'Active'],
    'fields' => [
        'name' => ['label' => 'Name'],
        'entity_type' => ['label' => 'Entity type'],
        'steps' => ['label' => 'Steps JSON', 'type' => 'textarea', 'default' => "'[]'", 'edit' => "row.steps_json || '[]'"],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

entity($root, [
    'module' => 'Workflow', 'code' => 'workflow', 'entity' => 'AutomationRule', 'plural' => 'Automation',
    'table' => 'automation_rules', 'route' => 'automation', 'prop' => 'rules', 'param' => 'automationRule',
    'fillable' => ['name', 'event', 'conditions', 'actions', 'is_active'],
    'casts' => ['conditions' => "'array'", 'actions' => "'array'", 'is_active' => "'boolean'"],
    'search' => ['name', 'event'],
    'rules' => [
        'name' => "['required', 'string', 'max:255']",
        'event' => "['nullable', 'string', 'max:120']",
        'conditions' => "['nullable']",
        'actions' => "['nullable']",
        'is_active' => "['boolean']",
    ],
    'create' => [
        'is_active' => "\$data['is_active'] ?? true",
        'conditions' => "is_string(\$data['conditions'] ?? null) ? json_decode(\$data['conditions'], true) : (\$data['conditions'] ?? [])",
        'actions' => "is_string(\$data['actions'] ?? null) ? json_decode(\$data['actions'], true) : (\$data['actions'] ?? [])",
    ],
    'format_extra' => "            'conditions_json' => json_encode(\$row->conditions ?? []),\n            'actions_json' => json_encode(\$row->actions ?? []),",
    'columns' => ['name' => 'Name', 'event' => 'Event', 'is_active' => 'Active'],
    'fields' => [
        'name' => ['label' => 'Name'],
        'event' => ['label' => 'Event'],
        'conditions' => ['label' => 'Conditions JSON', 'type' => 'textarea', 'default' => "'{}'", 'edit' => "row.conditions_json || '{}'"],
        'actions' => ['label' => 'Actions JSON', 'type' => 'textarea', 'default' => "'{}'", 'edit' => "row.actions_json || '{}'"],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

// business-rules alias page -> same as automation for nav
put("{$wf}/Http/Controllers/BusinessRulesController.php", <<<'PHP'
<?php

namespace Modules\Workflow\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Response;

class BusinessRulesController extends AutomationRuleController
{
    public function index(Request $request, \Modules\Workflow\Services\AutomationRuleService $svc): Response
    {
        $response = parent::index($request, $svc);
        // Re-render with same props under business-rules label via Automation page
        return $response;
    }
}

PHP);

entity($root, [
    'module' => 'Workflow', 'code' => 'workflow', 'entity' => 'ScheduledTask', 'plural' => 'ScheduledTasks',
    'table' => 'scheduled_tasks', 'route' => 'scheduled-tasks', 'prop' => 'tasks', 'param' => 'scheduledTask',
    'fillable' => ['name', 'cron', 'handler', 'is_active', 'last_run_at'],
    'casts' => ['is_active' => "'boolean'", 'last_run_at' => "'datetime'"],
    'search' => ['name', 'handler', 'cron'],
    'rules' => [
        'name' => "['required', 'string', 'max:255']",
        'cron' => "['required', 'string', 'max:80']",
        'handler' => "['nullable', 'string', 'max:120']",
        'is_active' => "['boolean']",
    ],
    'create' => ['is_active' => "\$data['is_active'] ?? true", 'cron' => "\$data['cron'] ?? '0 * * * *'"],
    'columns' => ['name' => 'Name', 'cron' => 'Cron', 'handler' => 'Handler', 'is_active' => 'Active'],
    'fields' => [
        'name' => ['label' => 'Name'],
        'cron' => ['label' => 'Cron', 'default' => "'0 * * * *'"],
        'handler' => ['label' => 'Handler'],
        'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'default' => 'true'],
    ],
]);

entity($root, [
    'module' => 'Workflow', 'code' => 'workflow', 'entity' => 'WorkflowLog', 'plural' => 'Logs',
    'table' => 'workflow_logs', 'route' => 'logs', 'prop' => 'logs', 'param' => 'workflowLog',
    'fillable' => ['source', 'message', 'level', 'context'],
    'casts' => ['context' => "'array'"],
    'search' => ['source', 'message', 'level'],
    'rules' => [
        'source' => "['nullable', 'string', 'max:80']",
        'message' => "['required', 'string', 'max:500']",
        'level' => "['required', 'string', 'max:20']",
        'context' => "['nullable']",
    ],
    'create' => [
        'level' => "\$data['level'] ?? 'info'",
        'context' => "is_string(\$data['context'] ?? null) ? json_decode(\$data['context'], true) : (\$data['context'] ?? [])",
    ],
    'format_extra' => "            'created_at' => \$row->created_at?->toDateTimeString(),",
    'columns' => ['source' => 'Source', 'level' => 'Level', 'message' => 'Message', 'created_at' => 'When'],
    'fields' => [
        'source' => ['label' => 'Source'],
        'level' => ['label' => 'Level', 'default' => "'info'"],
        'message' => ['label' => 'Message', 'type' => 'textarea'],
    ],
]);

put("{$wf}/Routes/web.php", <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use Modules\Workflow\Http\Controllers\ApprovalPolicyController;
use Modules\Workflow\Http\Controllers\ApprovalRequestController;
use Modules\Workflow\Http\Controllers\AutomationRuleController;
use Modules\Workflow\Http\Controllers\BusinessRulesController;
use Modules\Workflow\Http\Controllers\ScheduledTaskController;
use Modules\Workflow\Http\Controllers\WorkflowDefinitionController;
use Modules\Workflow\Http\Controllers\WorkflowLogController;
use Modules\Workflow\Http\Controllers\WorkflowOverviewController;

Route::get('/overview', [WorkflowOverviewController::class, 'index'])->name('overview');

Route::prefix('workflows')->name('workflows.')->group(function () {
    Route::get('/', [WorkflowDefinitionController::class, 'index'])->name('index');
    Route::post('/', [WorkflowDefinitionController::class, 'store'])->name('store');
    Route::put('/{workflowDefinition}', [WorkflowDefinitionController::class, 'update'])->name('update');
    Route::delete('/{workflowDefinition}', [WorkflowDefinitionController::class, 'destroy'])->name('destroy');
});

Route::prefix('approval-requests')->name('approval-requests.')->group(function () {
    Route::get('/', [ApprovalRequestController::class, 'index'])->name('index');
    Route::post('/', [ApprovalRequestController::class, 'store'])->name('store');
    Route::put('/{approvalRequest}', [ApprovalRequestController::class, 'update'])->name('update');
    Route::delete('/{approvalRequest}', [ApprovalRequestController::class, 'destroy'])->name('destroy');
});

Route::prefix('approval-policies')->name('approval-policies.')->group(function () {
    Route::get('/', [ApprovalPolicyController::class, 'index'])->name('index');
    Route::post('/', [ApprovalPolicyController::class, 'store'])->name('store');
    Route::put('/{approvalPolicy}', [ApprovalPolicyController::class, 'update'])->name('update');
    Route::delete('/{approvalPolicy}', [ApprovalPolicyController::class, 'destroy'])->name('destroy');
});

Route::prefix('automation')->name('automation.')->group(function () {
    Route::get('/', [AutomationRuleController::class, 'index'])->name('index');
    Route::post('/', [AutomationRuleController::class, 'store'])->name('store');
    Route::put('/{automationRule}', [AutomationRuleController::class, 'update'])->name('update');
    Route::delete('/{automationRule}', [AutomationRuleController::class, 'destroy'])->name('destroy');
});

Route::get('/business-rules', [BusinessRulesController::class, 'index'])->name('business-rules');

Route::prefix('scheduled-tasks')->name('scheduled-tasks.')->group(function () {
    Route::get('/', [ScheduledTaskController::class, 'index'])->name('index');
    Route::post('/', [ScheduledTaskController::class, 'store'])->name('store');
    Route::put('/{scheduledTask}', [ScheduledTaskController::class, 'update'])->name('update');
    Route::delete('/{scheduledTask}', [ScheduledTaskController::class, 'destroy'])->name('destroy');
});

Route::prefix('logs')->name('logs.')->group(function () {
    Route::get('/', [WorkflowLogController::class, 'index'])->name('index');
    Route::post('/', [WorkflowLogController::class, 'store'])->name('store');
    Route::put('/{workflowLog}', [WorkflowLogController::class, 'update'])->name('update');
    Route::delete('/{workflowLog}', [WorkflowLogController::class, 'destroy'])->name('destroy');
});

PHP);

echo "Workflow done\n";
