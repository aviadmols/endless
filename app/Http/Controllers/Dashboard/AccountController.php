<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        [$countryCode, $local] = PhoneNumber::split($request->user()->phone);

        return view('dashboard.account', [
            'user' => $request->user(),
            'countries' => config('endless.phone.countries'),
            'countryCode' => $countryCode,
            'phoneLocal' => $local,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $phone = PhoneNumber::normalize($request->input('phone'), $request->input('country_code'));

        $request->merge(['phone_e164' => $phone, 'email' => mb_strtolower(trim((string) $request->input('email')))]);

        $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'phone_e164' => ['nullable', 'string', Rule::unique('users', 'phone')->ignore($user->id)],
        ], [
            'phone_e164.unique' => 'מספר הטלפון כבר בשימוש בחשבון אחר.',
        ], [
            'first_name' => 'שם פרטי', 'last_name' => 'שם משפחה', 'email' => 'אימייל', 'phone' => 'טלפון', 'phone_e164' => 'טלפון',
        ]);

        if ($request->filled('phone') && ! $phone) {
            return back()->withInput()->withErrors(['phone' => 'מספר הטלפון אינו תקין.']);
        }

        $user->fill([
            'first_name' => $request->string('first_name')->trim(),
            'last_name' => $request->string('last_name')->trim() ?: null,
            'email' => $request->input('email'),
            'phone' => $phone,
        ]);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        if ($user->isDirty('phone')) {
            $user->phone_verified_at = null;
        }
        $user->save();

        return back()->with('status', 'פרטי החשבון נשמרו.');
    }
}
