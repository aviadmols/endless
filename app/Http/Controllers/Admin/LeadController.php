<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(): View
    {
        return view('admin.leads.index', [
            'leads' => Lead::latest()->paginate(30),
        ]);
    }

    public function toggleHandled(Lead $lead): RedirectResponse
    {
        $lead->update(['handled' => ! $lead->handled]);

        return back()->with('status', $lead->handled ? 'הפנייה סומנה כטופלה.' : 'הפנייה סומנה כפתוחה.');
    }
}
