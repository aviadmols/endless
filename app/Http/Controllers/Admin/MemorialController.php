<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Memorial;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemorialController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $memorials = Memorial::query()
            ->with('owner')
            ->withCount(['memories', 'images'])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%")
                        ->orWhereHas('owner', fn ($o) => $o->where('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")->orWhere('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.memorials.index', compact('memorials', 'q'));
    }

    public function edit(Memorial $memorial): View
    {
        $memorial->load('owner')->loadCount(['memories', 'images']);
        $log = ActivityLog::where('memorial_id', $memorial->id)->latest()->limit(15)->get();

        return view('admin.memorials.edit', compact('memorial', 'log'));
    }

    public function update(Request $request, Memorial $memorial): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('memorials', 'slug')->ignore($memorial->id)],
            'visibility' => ['required', Rule::in(['private', 'unlisted'])],
            'require_approval' => ['nullable', 'boolean'],
            'notify_owner' => ['nullable', 'boolean'],
        ], [], ['slug' => 'כתובת', 'visibility' => 'פרטיות']);

        $memorial->fill([
            'slug' => $data['slug'],
            'visibility' => $data['visibility'],
            'require_approval' => $request->boolean('require_approval'),
            'notify_owner' => $request->boolean('notify_owner'),
        ])->save();

        ActivityLog::record('admin.memorial.updated', $memorial);

        return back()->with('status', 'העמוד עודכן.');
    }

    public function destroy(Memorial $memorial): RedirectResponse
    {
        ActivityLog::record('admin.memorial.deleted', $memorial);
        $memorial->delete();

        return redirect()->route('admin.memorials.index')->with('status', 'העמוד הועבר לארכיון.');
    }

    /** Admin "login as" the memorial owner to edit the page through the regular dashboard. */
    public function loginAs(Request $request, Memorial $memorial): RedirectResponse
    {
        $admin = $request->user();
        $owner = $memorial->owner;
        abort_unless($owner, 404);

        $request->session()->put('impersonator_id', $admin->id);
        Auth::login($owner);
        $request->session()->regenerate();
        $request->session()->put('impersonator_id', $admin->id);
        ActivityLog::record('admin.impersonate', $memorial, $admin, ['owner_id' => $owner->id]);

        return redirect()->route('dashboard.index');
    }

    public function stopImpersonating(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull('impersonator_id');
        $admin = $adminId ? User::find($adminId) : null;
        abort_unless($admin?->is_admin, 403);

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.memorials.index');
    }
}
