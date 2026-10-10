<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:100'],
            'surname'         => ['nullable', 'string', 'max:100'],
            'email'           => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password'        => ['nullable', 'string', 'min:8', 'confirmed'],
            'current_password'=> ['required_with:password', 'current_password'],
        ]);

        if (($validated['password'] ?? null) === null) {
            unset($validated['password'], $validated['current_password']);
        }

        $user->update($validated);

        return redirect()->route('admin.profile')->with('success', 'Profiliniz güncellendi.');
    }
}
