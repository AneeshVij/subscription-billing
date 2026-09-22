<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    public function index()
    {
        $merchants = Merchant::withCount([
            'customers',
            'plans',
            'usageEvents',
        ])
        ->latest()
        ->get();

        return view('merchants.index', compact('merchants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        Merchant::create($validated);

        return redirect()
            ->route('merchants.index')
            ->with('success', 'Merchant created successfully.');
    }
}