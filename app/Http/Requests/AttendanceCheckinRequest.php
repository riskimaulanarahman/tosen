<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class AttendanceCheckinRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authenticated users can check in
    }

    /**
     * Get validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0', // GPS accuracy in meters
            'selfie' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png',
                'max:2048', // 2MB max
                'dimensions:min_width=200,min_height=200', // Reduced minimum requirements
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Koordinat lokasi diperlukan untuk check-in.',
            'latitude.numeric' => 'Format latitude tidak valid.',
            'latitude.between' => 'Latitude harus antara -90 dan 90 derajat.',
            'longitude.required' => 'Koordinat lokasi diperlukan untuk check-in.',
            'longitude.numeric' => 'Format longitude tidak valid.',
            'longitude.between' => 'Longitude harus antara -180 dan 180 derajat.',
            'accuracy.numeric' => 'Akurasi GPS harus berupa angka.',
            'accuracy.min' => 'Akurasi GPS tidak boleh negatif.',
            'selfie.required' => 'Selfie photo is required for check-in.',
            'selfie.file' => 'Please upload a valid image file.',
            'selfie.image' => 'Selfie must be an image file.',
            'selfie.mimes' => 'Selfie must be a JPEG or PNG image.',
            'selfie.max' => 'Selfie image must be less than 2MB.',
            'selfie.dimensions' => 'Selfie must be at least 300x300 pixels.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            
            // Simplified validation - only check for existing attendance
            // Outlet assignment and GPS validation handled in controller
            
            // Check if user already checked in today and hasn't checked out
            $existingAttendance = \App\Models\Attendance::where('user_id', $user->id)
                ->whereDate('check_in_time', today())
                ->whereNull('check_out_time')
                ->first();

            if ($existingAttendance) {
                $validator->errors()->add('attendance', 'Anda sudah check-in hari ini. Silakan check-out terlebih dahulu.');
            }
        });
    }
}
