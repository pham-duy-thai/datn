<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\News;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function overview(): JsonResponse
    {
        $appointmentsByStatus = Appointment::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => [
                'total_patients' => User::where('role', 'patient')->count(),
                'total_doctors' => Doctor::count(),
                'total_receptionists' => User::where('role', 'receptionist')->count(),
                'total_departments' => Department::count(),
                'total_services' => Service::count(),
                'total_appointments' => Appointment::count(),
                'today_appointments' => Appointment::whereDate('appointment_date', now()->toDateString())->count(),
                'completed_appointments' => Appointment::where('status', 'completed')->count(),
                'cancelled_appointments' => Appointment::where('status', 'cancelled')->count(),
                'pending_appointments' => Appointment::where('status', 'pending')->count(),
                'confirmed_appointments' => Appointment::where('status', 'confirmed')->count(),
                'total_medical_records' => MedicalRecord::count(),
                'total_contacts' => Contact::count(),
                'unread_contacts' => Contact::where('status', 'new')->count(),
                'total_news' => News::count(),
                'published_news' => News::where('status', 'published')->count(),
                'appointments_by_status' => $appointmentsByStatus,
            ],
        ]);
    }
}
