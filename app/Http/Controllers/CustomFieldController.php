<?php

namespace App\Http\Controllers;

use App\Models\CustomFieldDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomFieldController extends Controller
{
    /**
     * POST /custom-fields — store a new field definition
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'form_type'   => ['required', 'in:building,unit'],
            'label'       => ['required', 'string', 'max:255'],
            'field_type'  => ['required', 'in:text,number,date,select,textarea'],
            'options'     => ['nullable', 'array'],
            'options.*'   => ['string', 'max:255'],
            'is_required' => ['boolean'],
        ]);

        // Generate snake_case name from label
        $name = Str::slug(str_replace(' ', '_', $data['label']), '_');

        // Ensure unique within form_type
        $base = $name;
        $i = 1;
        while (CustomFieldDefinition::where('form_type', $data['form_type'])->where('name', $name)->exists()) {
            $name = $base . '_' . $i++;
        }

        $maxOrder = CustomFieldDefinition::where('form_type', $data['form_type'])->max('sort_order') ?? 0;

        $def = CustomFieldDefinition::create([
            'form_type'   => $data['form_type'],
            'name'        => $name,
            'label'       => $data['label'],
            'field_type'  => $data['field_type'],
            'options'     => $data['options'] ?? null,
            'is_required' => $data['is_required'] ?? false,
            'sort_order'  => $maxOrder + 1,
            'is_active'   => true,
        ]);

        return response()->json(['success' => true, 'name' => $name, 'id' => $def->id]);
    }

    /**
     * DELETE /custom-fields/{customField} — soft delete (set is_active = false)
     */
    public function destroy(CustomFieldDefinition $customField): JsonResponse
    {
        $customField->update(['is_active' => false]);
        return response()->json(['success' => true]);
    }
}
