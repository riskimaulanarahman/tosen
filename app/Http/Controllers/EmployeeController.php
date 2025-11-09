<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;

class EmployeeController extends Controller
{
    /**
     * Store a newly created employee in storage.
     */
    public function store(Request $request, Outlet $outlet)
    {
        // Verify that the authenticated user owns this outlet
        if ($outlet->owner_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $employee = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'employee',
            'outlet_id' => $outlet->id,
            'email_verified_at' => null, // Employee needs to verify email
        ]);

        return redirect()->back()
            ->with('success', 'Employee registered successfully! Please inform the employee that they need to verify their email address before logging in. They should check their email for an OTP code or visit the verification page to complete the setup.')
            ->with('employee', $employee);
    }
}
