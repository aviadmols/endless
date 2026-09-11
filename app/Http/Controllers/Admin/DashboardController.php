<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MemoryStatus;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Memorial;
use App\Models\Memory;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use App\Services\Sms\SmsManager;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(SettingsRepository $settings, SmsManager $sms): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'memorials' => Memorial::count(),
                'users' => User::count(),
                'memories' => Memory::count(),
                'pending' => Memory::where('status', MemoryStatus::Pending->value)->count(),
                'leads' => Lead::where('handled', false)->count(),
            ],
            'latestMemorials' => Memorial::with('owner')->withCount('memories')->latest()->limit(8)->get(),
            'latestLeads' => Lead::latest()->limit(5)->get(),
            'mailReady' => $settings->mailConfigured(),
            'smsReady' => $sms->isConfigured(),
        ]);
    }
}
