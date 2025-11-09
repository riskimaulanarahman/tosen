<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Outlet;
use App\Jobs\SendOtpEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        
        $employees = User::where('role', 'employee')
            ->whereHas('outlet', function($query) use ($user) {
                $query->where('owner_id', $user->id);
            })
            ->with('outlet')
            ->orderBy('name')
            ->paginate(10);

        $outlets = Outlet::where('owner_id', $user->id)
            ->orderBy('name')
            ->get();

        return inertia('Employee/Index', [
            'employees' => $employees,
            'outlets' => $outlets,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        
        $outlets = Outlet::where('owner_id', $user->id)
            ->orderBy('name')
            ->get();

        return inertia('Employee/Create', [
            'outlets' => $outlets,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'outlet_id' => [
                'required',
                'integer',
                Rule::exists('outlets', 'id')->where(function ($query) use ($user) {
                    $query->where('owner_id', $user->id);
                }),
            ],
        ], [
            'outlet_id.exists' => 'Selected outlet is invalid.',
        ]);

        // Get outlet information
        $outlet = Outlet::find($validated['outlet_id']);
        
        // Generate readable temporary password
        $tempPassword = 'Temp@' . strtoupper(\Str::random(8)) . rand(10, 99);

        // Create employee with temporary password
        $employee = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'outlet_id' => $validated['outlet_id'],
            'role' => 'employee',
            'email_verified_at' => null, // Will be verified later
            'password' => Hash::make($tempPassword),
        ]);

        // Generate and send OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Delete any existing OTP for this email
        DB::table('email_verifications')->where('email', $validated['email'])->delete();
        
        // Store OTP in email_verifications table with expiration
        DB::table('email_verifications')->insert([
            'email' => $validated['email'],
            'token' => $otp,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send welcome email with OTP, password, and outlet info
        SendOtpEmail::dispatch(
            $validated['email'], 
            $otp, 
            $tempPassword, 
            $outlet->name, 
            $outlet->address, 
            $user->name
        );

        return redirect()->route('employees.index')
            ->with('success', "Employee {$validated['name']} created successfully. Welcome email with login credentials has been sent to {$validated['email']}.");
    }

    /**
     * Display the specified resource.
     */
    public function show(User $employee)
    {
        $user = Auth::user();
        
        // Ensure owner can only view employees from their outlets
        if (!$employee->outlet || $employee->outlet->owner_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $employee->load('outlet', 'attendances');

        return inertia('Employee/Show', [
            'employee' => $employee,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $employee)
    {
        $user = Auth::user();
        
        // Ensure owner can only edit employees from their outlets
        if (!$employee->outlet || $employee->outlet->owner_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $outlets = Outlet::where('owner_id', $user->id)
            ->orderBy('name')
            ->get();

        return inertia('Employee/Edit', [
            'employee' => $employee,
            'outlets' => $outlets,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $employee)
    {
        $user = Auth::user();
        
        // Ensure owner can only update employees from their outlets
        if (!$employee->outlet || $employee->outlet->owner_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employee->id),
            ],
            'outlet_id' => [
                'required',
                'integer',
                Rule::exists('outlets', 'id')->where(function ($query) use ($user) {
                    $query->where('owner_id', $user->id);
                }),
            ],
        ], [
            'outlet_id.exists' => 'Selected outlet is invalid.',
        ]);

        // Only update password if provided
        if (!empty($request->password)) {
            $validated['password'] = Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

        $employee->update($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $employee)
    {
        $user = Auth::user();
        
        // Ensure owner can only delete employees from their outlets
        if (!$employee->outlet || $employee->outlet->owner_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Check if employee has attendance records
        if ($employee->attendances()->count() > 0) {
            return back()->withErrors(['cannot_delete' => 'Cannot delete employee with attendance records.']);
        }

        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }

    /**
     * Resend welcome email to employee
     */
    public function resendEmail(User $employee)
    {
        $user = Auth::user();
        
        // Ensure owner can only resend to employees from their outlets
        if (!$employee->outlet || $employee->outlet->owner_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Generate new temporary password
        $tempPassword = 'Temp@' . strtoupper(\Str::random(8)) . rand(10, 99);
        
        // Update employee password
        $employee->update([
            'password' => Hash::make($tempPassword),
            'email_verified_at' => null, // Reset verification
        ]);

        // Generate new OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Delete any existing OTP for this email
        DB::table('email_verifications')->where('email', $employee->email)->delete();
        
        // Store OTP in email_verifications table with expiration
        DB::table('email_verifications')->insert([
            'email' => $employee->email,
            'token' => $otp,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send welcome email with new credentials
        SendOtpEmail::dispatch(
            $employee->email, 
            $otp, 
            $tempPassword, 
            $employee->outlet->name, 
            $employee->outlet->address, 
            $user->name
        );

        return back()->with('success', "Welcome email resent to {$employee->email} with new login credentials.");
    }

    /**
     * Bulk assign employees to outlet
     */
    public function bulkAssign(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'integer|exists:users,id',
            'outlet_id' => [
                'required',
                'integer',
                Rule::exists('outlets', 'id')->where(function ($query) use ($user) {
                    $query->where('owner_id', $user->id);
                }),
            ],
        ]);

        User::whereIn('id', $validated['employee_ids'])
            ->where('role', 'employee')
            ->whereHas('outlet', function($query) use ($user) {
                $query->where('owner_id', $user->id);
            })
            ->update(['outlet_id' => $validated['outlet_id']]);

        return redirect()->route('employees.index')
            ->with('success', 'Employees assigned successfully.');
    }
}
