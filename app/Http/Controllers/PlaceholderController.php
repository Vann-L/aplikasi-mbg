<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PlaceholderController extends Controller
{
    /**
     * Render a placeholder page for a module that is not built yet.
     */
    public function __invoke(Request $request): View
    {
        return view('placeholder', [
            'label' => $request->route('label', 'Halaman'),
        ]);
    }
}
