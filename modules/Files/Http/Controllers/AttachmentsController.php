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
