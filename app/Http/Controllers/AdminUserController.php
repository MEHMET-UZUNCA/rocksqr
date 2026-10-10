<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->orderBy('surname')->orderBy('id')->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'surname'   => ['nullable', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'role'      => ['required', 'in:admin,personel'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'Kullanıcı oluşturuldu.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $isSelf = $user->id === (int) auth()->id();

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'surname'   => ['nullable', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password'  => ['nullable', 'string', 'min:8', 'confirmed'],
            'role'      => [Rule::requiredIf(! $isSelf), 'in:admin,personel'],
            'is_active' => ['boolean'],
        ]);

        if ($validated['password'] === null) {
            unset($validated['password']);
        }

        if ($isSelf) {
            // Kendi rolü/durumu formdan değiştirilemez
            $validated['role'] = $user->role;
            $validated['is_active'] = $user->is_active;
        } else {
            $validated['is_active'] = $request->boolean('is_active');

            if ($user->isAdmin() && ($validated['role'] !== 'admin' || ! $validated['is_active'])) {
                $remainingAdmins = User::where('role', 'admin')
                    ->where('is_active', true)
                    ->where('id', '!=', $user->id)
                    ->count();
                if ($remainingAdmins === 0) {
                    return redirect()->route('admin.users.index')
                        ->with('error', 'Son yöneticinin yetkisi düşürülemez veya hesabı devre dışı bırakılamaz.');
                }
            }
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'Kullanıcı güncellendi.');
    }

    public function destroy(User $user)
    {
        if ($user->id === (int) auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'Kendi hesabınızı silemezsiniz.');
        }
        if (User::count() <= 1) {
            return redirect()->route('admin.users.index')->with('error', 'Son kullanıcı silinemez.');
        }
        if ($user->isAdmin()
            && User::where('role', 'admin')->where('is_active', true)->where('id', '!=', $user->id)->count() === 0) {
            return redirect()->route('admin.users.index')->with('error', 'Son yönetici silinemez.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Kullanıcı silindi.');
    }
}
