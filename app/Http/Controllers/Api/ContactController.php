<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $contacts = Contact::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($contacts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'status' => ['sometimes', Rule::in(['new', 'read', 'replied'])],
        ]);

        $data['status'] = $data['status'] ?? 'new';

        return response()->json(['data' => Contact::create($data)], 201);
    }

    public function show(Contact $contact): JsonResponse
    {
        return response()->json(['data' => $contact]);
    }

    public function update(Request $request, Contact $contact): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['sometimes', 'required', 'string'],
            'status' => ['sometimes', Rule::in(['new', 'read', 'replied'])],
        ]);

        $contact->update($data);

        return response()->json(['data' => $contact->fresh()]);
    }

    public function updateStatus(Request $request, Contact $contact): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'read', 'replied'])],
        ]);

        $contact->update($data);

        return response()->json(['data' => $contact->fresh()]);
    }

    public function destroy(Contact $contact): JsonResponse
    {
        $contact->delete();

        return response()->json(['message' => 'Đã xóa liên hệ.']);
    }
}
