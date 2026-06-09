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
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $path = $request->file('image')->store('medical-images/'.$request->user()->id, 'public');

        $medicalImage = MedicalImage::create([
            'user_id' => $request->user()->id,
            'modality' => $data['modality'],
            'body_part' => $data['body_part'] ?? null,
            'image_path' => $path,
            'note' => $data['note'],
            'analysis_status' => 'pending',
        ]);

        $medicalImageAi->analyze($medicalImage);

        return redirect()
            ->route('account.show')
            ->with('current_medical_image_id', $medicalImage->id)
            ->with('success', 'Gemini đã trả lời câu hỏi dựa trên ảnh. Kết quả chỉ mang tính tham khảo và cần bác sĩ xác nhận.');
    }
}
