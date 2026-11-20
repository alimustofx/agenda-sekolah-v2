<?php

namespace App\Http\Controllers\Sia;

use App\Http\Controllers\Controller;
use App\Services\Sia\DisplayDataService;
use Inertia\Inertia;

class DisplayController extends Controller
{
    public function __construct(private DisplayDataService $data)
    {
    }

    public function index()
    {
        return Inertia::render('Sia/Display/Index', ['initial' => $this->data->build()]);
    }

    public function data()
    {
        return response()->json($this->data->build())->header('Cache-Control', 'no-store');
    }
}
