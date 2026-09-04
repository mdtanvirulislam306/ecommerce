<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Http\Requests\ImportProductsRequest;
use Modules\Catalog\Services\ProductImportExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportExportController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Catalog/ImportExport/Index', [
            'headers' => app(ProductImportExportService::class)->headers(),
            'statusOptions' => collect(ProductStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'importResult' => $request->session()->get('importResult'),
        ]);
    }

    public function export(Request $request, ProductImportExportService $service): StreamedResponse
    {
        $status = $request->filled('status')
            ? ProductStatus::tryFrom($request->string('status')->toString())
            : null;

        $filename = 'products-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($service, $status) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $service->headers());

            foreach ($service->exportRows($status) as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function template(ProductImportExportService $service): StreamedResponse
    {
        return response()->streamDownload(function () use ($service) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $service->headers());
            fputcsv($handle, [
                'SKU-001',
                'Sample T-Shirt',
                'simple',
                'draft',
                'not_published',
                'Short product description',
                'INT-001',
                '1234567890123',
                'Nike',
                'men-t-shirt',
                'pcs',
                't-shirt',
                '0',
                'Sample T-Shirt',
                'Meta description for SEO',
            ]);
            fclose($handle);
        }, 'products-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(ImportProductsRequest $request, ProductImportExportService $service): RedirectResponse
    {
        $result = $service->import(
            $request->file('file'),
            $request->boolean('update_existing'),
        );

        $message = "{$result['created']} created, {$result['updated']} updated";

        if ($result['skipped'] > 0) {
            $message .= ", {$result['skipped']} skipped";
        }

        if ($result['errors'] !== []) {
            $message .= '. '.count($result['errors']).' row(s) had errors.';
        }

        return back()->with([
            'success' => $message,
            'importResult' => $result,
        ]);
    }
}
