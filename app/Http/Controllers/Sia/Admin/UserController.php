<?php

namespace App\Http\Controllers\Sia\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Sia/Admin/Users/Index', [
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email', 'role', 'is_active', 'last_login_at'])->map(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role, 'is_active' => (bool) $u->is_active,
                'last_login_at' => $u->last_login_at ? \Illuminate\Support\Carbon::parse($u->last_login_at)->setTimezone(config('sia.timezone'))->format('d/m/Y H.i') : null,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', 'in:super_admin,admin_humas'],
            'password' => ['required', Password::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $user = new User();
        $user->forceFill([
            'name' => $data['name'], 'email' => $data['email'], 'role' => $data['role'],
            'is_active' => true, 'password' => Hash::make($data['password']), 'email_verified_at' => now(),
        ])->save();
        AuditLog::record('create', 'administrator', 'Menambah akun ' . $user->email . ' (' . $user->role . ').');

        return back()->with('success', 'Akun administrator dibuat.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:super_admin,admin_humas'],
            'is_active' => ['boolean'],
            'password' => ['nullable', Password::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $isSelf = $user->id === $request->user()->id;
        if ($isSelf && (($data['role'] ?? null) !== 'super_admin' || ! ($data['is_active'] ?? true))) {
            return back()->with('error', 'Anda tidak dapat menurunkan peran atau menonaktifkan akun sendiri.');
        }
        if ($user->role === 'super_admin' && $data['role'] !== 'super_admin'
            && User::where('role', 'super_admin')->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'Harus ada minimal satu Super Admin aktif.');
        }

        $user->forceFill([
            'name' => $data['name'], 'email' => $data['email'], 'role' => $data['role'], 'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        AuditLog::record('update', 'administrator', 'Memperbarui akun ' . $user->email . (! empty($data['password']) ? ' (kata sandi diatur ulang)' : '') . '.');

        return back()->with('success', 'Akun diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }
        if ($user->role === 'super_admin' && User::where('role', 'super_admin')->count() <= 1) {
            return back()->with('error', 'Harus ada minimal satu Super Admin.');
        }
        AuditLog::record('delete', 'administrator', 'Menghapus akun ' . $user->email . '.');
        $user->delete();

        return back()->with('success', 'Akun dihapus.');
    }
}
