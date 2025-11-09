<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\Attendance;
use App\Http\Requests\StoreOutletRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OutletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Outlet::where('owner_id', auth()->id())
            ->withCount(['employees', 'attendances']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('address', 'like', '%' . $request->search . '%');
            });
        }

        $outlets = $query->latest()->paginate(10);

        // Append operational attributes to each outlet
        $outlets->getCollection()->each(function ($outlet) {
            $outlet->append([
                'operational_start_time_formatted',
                'operational_end_time_formatted',
                'formatted_operational_hours',
                'operational_status',
                'formatted_work_days',
                'tolerance_settings'
            ]);
        });

        return inertia('Outlet/Index', [
            'outlets' => $outlets,
            'filters' => [
                'search' => $request->get('search', ''),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Outlet/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOutletRequest $request)
    {
        $validated = $request->validated();
        $validated['owner_id'] = Auth::id();

        Outlet::create($validated);

        return redirect()->route('outlets.index')
            ->with('success', 'Outlet created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Outlet $outlet)
    {
        // Ensure owner can only view their own outlets
        if ($outlet->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $outlet->load(['employees', 'attendances' => function ($query) {
            $query->with('user')->orderBy('created_at', 'desc')->take(50);
        }]);

        // Append operational attributes
        $outlet->append([
            'operational_start_time_formatted',
            'operational_end_time_formatted',
            'formatted_operational_hours',
            'operational_status',
            'formatted_work_days',
            'tolerance_settings'
        ]);

        return inertia('Outlet/Show', [
            'outlet' => $outlet,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Outlet $outlet)
    {
        // Ensure owner can only edit their own outlets
        if ($outlet->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return inertia('Outlet/Edit', [
            'outlet' => $outlet,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreOutletRequest $request, Outlet $outlet)
    {
        // Ensure owner can only update their own outlets
        if ($outlet->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validated();
        $outlet->update($validated);

        return redirect()->route('outlets.index')
            ->with('success', 'Outlet updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Outlet $outlet)
    {
        // Ensure owner can only delete their own outlets
        if ($outlet->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Check if there are employees assigned to this outlet
        if ($outlet->employees()->count() > 0) {
            return back()->withErrors(['cannot_delete' => 'Cannot delete outlet with assigned employees.']);
        }

        $outlet->delete();

        return redirect()->route('outlets.index')
            ->with('success', 'Outlet deleted successfully.');
    }
}
