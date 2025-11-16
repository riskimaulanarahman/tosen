<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Mail\SyncFlowTrialRequest;
use App\Mail\SyncFlowDemoRequest;

class SyncFlowController extends Controller
{
    /**
     * Handle trial form submission
     */
    public function submitTrial(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'employees' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:100'
        ], [
            'name.required' => 'Nama lengkap harus diisi',
            'email.required' => 'Email perusahaan harus diisi',
            'email.email' => 'Format email tidak valid',
            'company.required' => 'Nama perusahaan harus diisi'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store trial request
            $trialRequest = \App\Models\SyncFlowTrial::create([
                'name' => $request->name,
                'email' => $request->email,
                'company' => $request->company,
                'phone' => $request->phone,
                'employees' => $request->employees,
                'industry' => $request->industry,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'pending'
            ]);

            // Send confirmation email to user
            Mail::to($request->email)->send(new SyncFlowTrialRequest($trialRequest));

            // Send notification to sales team
            Mail::to('sales@syncflow.id')->send(new SyncFlowTrialRequest($trialRequest, true));

            // Log the submission
            Log::info('SyncFlow trial request submitted', [
                'email' => $request->email,
                'company' => $request->company,
                'id' => $trialRequest->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Terima kasih! Permintaan trial Anda telah diterima. Kami akan menghubungi Anda dalam 24 jam untuk setup akun Anda.',
                'data' => $trialRequest
            ]);

        } catch (\Exception $e) {
            Log::error('SyncFlow trial submission error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Maaf, terjadi kesalahan. Silakan coba lagi atau hubungi support kami.'
            ], 500);
        }
    }

    /**
     * Handle demo form submission
     */
    public function submitDemo(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'employees' => 'required|string|max:50',
            'preferred_date' => 'required|date|after:today',
            'preferred_time' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000'
        ], [
            'name.required' => 'Nama lengkap harus diisi',
            'email.required' => 'Email perusahaan harus diisi',
            'email.email' => 'Format email tidak valid',
            'company.required' => 'Nama perusahaan harus diisi',
            'phone.required' => 'Nomor telepon harus diisi',
            'employees.required' => 'Jumlah karyawan harus diisi',
            'preferred_date.required' => 'Tanggal demo harus diisi',
            'preferred_date.after' => 'Tanggal demo harus setelah hari ini'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store demo request
            $demoRequest = \App\Models\SyncFlowDemo::create([
                'name' => $request->name,
                'email' => $request->email,
                'company' => $request->company,
                'phone' => $request->phone,
                'employees' => $request->employees,
                'preferred_date' => $request->preferred_date,
                'preferred_time' => $request->preferred_time,
                'notes' => $request->notes,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'pending'
            ]);

            // Send confirmation email to user
            Mail::to($request->email)->send(new SyncFlowDemoRequest($demoRequest));

            // Send notification to sales team
            Mail::to('sales@syncflow.id')->send(new SyncFlowDemoRequest($demoRequest, true));

            // Log the submission
            Log::info('SyncFlow demo request submitted', [
                'email' => $request->email,
                'company' => $request->company,
                'preferred_date' => $request->preferred_date,
                'id' => $demoRequest->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Terima kasih! Permintaan demo Anda telah diterima. Tim kami akan menghubungi Anda untuk konfirmasi jadwal.',
                'data' => $demoRequest
            ]);

        } catch (\Exception $e) {
            Log::error('SyncFlow demo submission error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Maaf, terjadi kesalahan. Silakan coba lagi atau hubungi support kami.'
            ], 500);
        }
    }

    /**
     * Get trial requests (for admin)
     */
    public function getTrialRequests()
    {
        $requests = \App\Models\SyncFlowTrial::orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Get demo requests (for admin)
     */
    public function getDemoRequests()
    {
        $requests = \App\Models\SyncFlowDemo::orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Update trial request status
     */
    public function updateTrialStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,contacted,converted,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status'
            ], 422);
        }

        try {
            $trial = \App\Models\SyncFlowTrial::findOrFail($id);
            $trial->status = $request->status;
            $trial->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Update demo request status
     */
    public function updateDemoStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,scheduled,completed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status'
            ], 422);
        }

        try {
            $demo = \App\Models\SyncFlowDemo::findOrFail($id);
            $demo->status = $request->status;
            $demo->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }
}
