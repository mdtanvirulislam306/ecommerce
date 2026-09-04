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
