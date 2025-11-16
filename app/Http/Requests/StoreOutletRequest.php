<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreOutletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authenticated owners can create outlets
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|min:2',
            'address' => 'required|string|max:1000|min:5',
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
                'regex:/^-?\d{1,3}\.\d{1,6}$/'
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
                'regex:/^-?\d{1,3}\.\d{1,6}$/'
            ],
            'radius' => 'required|integer|min:10|max:1000',
            'operational_start_time' => 'required|date_format:H:i',
            'operational_end_time' => [
                'required',
                'date_format:H:i',
                function (string $attribute, $value, $fail) {
                    $start = $this->input('operational_start_time');

                    if (!$start) {
                        return;
                    }

                    try {
                        $startTime = Carbon::createFromFormat('H:i', $start);
                        $endTime = Carbon::createFromFormat('H:i', $value);
                    } catch (\Exception $e) {
                        return;
                    }

                    if ($startTime->equalTo($endTime)) {
                        $fail('Waktu selesai tidak boleh sama dengan waktu mulai.');
                    }
                },
            ],
            'work_days' => 'required|array|min:1',
            'work_days.*' => 'required|integer|between:1,7',
            'timezone' => 'required|string|timezone',
            'late_tolerance_minutes' => 'required|integer|min:0',
            'early_checkout_tolerance' => 'required|integer|min:0',
            'grace_period_minutes' => 'required|integer|min:0',
            'overtime_threshold_minutes' => 'required|integer|min:0',
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
            'name.required' => 'Nama outlet wajib diisi.',
            'name.string' => 'Nama outlet harus berupa string.',
            'name.max' => 'Nama outlet tidak boleh lebih dari 255 karakter.',
            'name.min' => 'Nama outlet minimal 2 karakter.',
            
            'address.required' => 'Alamat wajib diisi.',
            'address.string' => 'Alamat harus berupa string.',
            'address.max' => 'Alamat tidak boleh lebih dari 1000 karakter.',
            'address.min' => 'Alamat minimal 5 karakter.',
            
            'latitude.required' => 'Latitude wajib diisi.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus antara -90 dan 90 derajat.',
            'latitude.regex' => 'Latitude maksimal 6 desimal.',
            
            'longitude.required' => 'Longitude wajib diisi.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus antara -180 dan 180 derajat.',
            'longitude.regex' => 'Longitude maksimal 6 desimal.',
            
            'radius.required' => 'Radius geofence wajib diisi.',
            'radius.integer' => 'Radius harus berupa angka bulat.',
            'radius.min' => 'Radius minimal 10 meter.',
            'radius.max' => 'Radius maksimal 1000 meter.',
            
            'operational_start_time.required' => 'Waktu mulai operasional wajib diisi.',
            'operational_start_time.date_format' => 'Format waktu mulai tidak valid (HH:MM).',
            
            'operational_end_time.required' => 'Waktu selesai operasional wajib diisi.',
            'operational_end_time.date_format' => 'Format waktu selesai tidak valid (HH:MM).',
            
            'work_days.required' => 'Hari kerja wajib dipilih.',
            'work_days.array' => 'Hari kerja harus berupa array.',
            'work_days.min' => 'Pilih minimal 1 hari kerja.',
            'work_days.*.required' => 'Hari kerja wajib dipilih.',
            'work_days.*.integer' => 'Hari kerja tidak valid.',
            'work_days.*.between' => 'Hari kerja tidak valid.',
            
            'timezone.required' => 'Timezone wajib dipilih.',
            'timezone.string' => 'Timezone harus berupa string.',
            'timezone.timezone' => 'Timezone tidak valid.',
            
            'late_tolerance_minutes.required' => 'Toleransi keterlambatan wajib diisi.',
            'late_tolerance_minutes.integer' => 'Toleransi keterlambatan harus angka.',
            'late_tolerance_minutes.min' => 'Toleransi keterlambatan minimal 0 menit.',
            'late_tolerance_minutes.max' => 'Toleransi keterlambatan maksimal 60 menit.',
            
            'early_checkout_tolerance.required' => 'Toleransi check-out awal wajib diisi.',
            'early_checkout_tolerance.integer' => 'Toleransi check-out awal harus angka.',
            'early_checkout_tolerance.min' => 'Toleransi check-out awal minimal 0 menit.',
            'early_checkout_tolerance.max' => 'Toleransi check-out awal maksimal 60 menit.',
            
            'grace_period_minutes.required' => 'Masa tenggang wajib diisi.',
            'grace_period_minutes.integer' => 'Masa tenggang harus angka.',
            'grace_period_minutes.min' => 'Masa tenggang minimal 0 menit.',
            'grace_period_minutes.max' => 'Masa tenggang maksimal 30 menit.',
            
            'overtime_threshold_minutes.required' => 'Threshold lembur wajib diisi.',
            'overtime_threshold_minutes.integer' => 'Threshold lembur harus angka.',
            'overtime_threshold_minutes.min' => 'Threshold lembur minimal 0 menit.',
            'overtime_threshold_minutes.max' => 'Threshold lembur maksimal 240 menit.',
        ];
    }
}
