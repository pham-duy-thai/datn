<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MedicalImage;
use App\Services\MedicalImageAiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MedicalImageController extends Controller
{
    public function store(Request $request, MedicalImageAiService $medicalImageAi): RedirectResponse
    {
        abort_unless($request->user()->role === 'patient', 403);

        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'modality' => ['required', Rule::in(['xray', 'ct', 'mri', 'ultrasound', 'endoscopy'])],
            'body_part' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $path = $request->file('image')->store('medical-images/'.$request->user()->id, 'public');

        $medicalImage = MedicalImage::create([
            'user_id' => $request->user()->id,
            'modality' => $data['modality'],
            'body_part' => $data['body_part'] ?? null,
            'image_path' => $path,
            'note' => $data['note'] ?? null,
            'analysis_status' => 'pending',
        ]);

        $medicalImageAi->analyze($medicalImage);

        return redirect()
            ->route('account.show')
            ->with('success', 'Đã tải ảnh y tế lên. Kết quả AI chỉ hỗ trợ sàng lọc và cần bác sĩ xác nhận.');
    }
}
