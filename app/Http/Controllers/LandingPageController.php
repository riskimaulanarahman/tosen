<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    /**
     * Display the TOSEN-TOGA Presence landing page.
     */
    public function index()
    {
        return inertia('LandingPage/Index');
    }

    /**
     * Handle trial request from landing page.
     */
    public function requestTrial(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'company_name' => 'required|string|max:255',
            'employee_count' => 'required|integer|min:1',
            'business_type' => 'required|string|max:100',
        ]);

        // TODO: Implement trial request logic
        // - Save to database
        // - Send confirmation email
        // - Notify sales team

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih! Tim kami akan menghubungi Anda dalam 1x24 jam untuk setup akun trial.'
        ]);
    }

    /**
     * Handle demo request from landing page.
     */
    public function requestDemo(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'company_name' => 'required|string|max:255',
            'employee_count' => 'required|integer|min:1',
            'preferred_date' => 'required|date|after:today',
            'preferred_time' => 'required|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        // TODO: Implement demo request logic
        // - Save to database
        // - Send confirmation email
        // - Create calendar event
        // - Notify sales team

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih! Demo telah dijadwalkan. Tim kami akan mengirim link dan detail demo via email.'
        ]);
    }
}
