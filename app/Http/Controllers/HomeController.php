<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadRequest;
use App\Mail\LeadReceivedMail;
use App\Models\Lead;
use App\Services\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home');
    }

    /**
     * The "חייל השריון" landing page — the memorial-page pitch we send to bereaved
     * families. Its copy lives in the `landing` settings group.
     */
    public function shiryon(SettingsRepository $settings): View
    {
        $landing = $settings->group('landing');
        $features = json_decode((string) ($landing['features'] ?? '[]'), true) ?: [];
        $howList = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) ($landing['how_list'] ?? '')))));

        return view('shiryon', [
            'landing' => $landing,
            'features' => $features,
            'howList' => $howList,
        ]);
    }

    public function storeLead(LeadRequest $request, SettingsRepository $settings): RedirectResponse
    {
        $lead = Lead::create([
            'name' => $request->string('name')->trim(),
            'email' => $request->string('email')->lower()->trim(),
            'phone' => $request->input('phone'),
            'message' => $request->input('message'),
            'source' => 'home',
            'ip' => $request->ip(),
        ]);

        $adminEmail = $settings->get('general.contact_email');
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new LeadReceivedMail($lead));
            } catch (Throwable) {
                // Never block the visitor because the admin mailbox is misconfigured.
            }
        }

        return back()->with('lead_sent', true)->withFragment('contact');
    }
}
