<?php

namespace App\Http\Controllers;

use App\Models\CustomFieldDefinition;
use App\Models\FormConfig;
use App\Services\FormConfigService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FormConfigController extends Controller
{
    public function __construct(protected FormConfigService $service) {}

    public function index(): View
    {
        $configs = [
            'building' => [
                'form'     => FormConfig::getFields('building', 'form'),
                'template' => FormConfig::getFields('building', 'template'),
            ],
            'unit' => [
                'form'     => FormConfig::getFields('unit', 'form'),
                'template' => FormConfig::getFields('unit', 'template'),
            ],
        ];

        return view('form-configs.index', compact('configs'));
    }

    public function edit(string $formType, string $configType): View
    {
        $this->validateParams($formType, $configType);

        $masterFields = $this->service->getAllFields($formType);
        $savedFields  = FormConfig::getFields($formType, $configType);

        // Merge saved visible state onto master field list, preserving master order
        // but also appending any previously saved fields that may have been reordered.
        if ($savedFields !== null) {
            // Build a lookup of saved state by name
            $savedMap = collect($savedFields)->keyBy('name');

            // Start with master fields, applying saved visible state
            $mergedByName = collect($masterFields)->map(function ($field) use ($savedMap) {
                if ($savedMap->has($field['name'])) {
                    $saved = $savedMap->get($field['name']);
                    return array_merge($field, ['visible' => (bool) ($saved['visible'] ?? true)]);
                }
                return array_merge($field, ['visible' => true]);
            });

            // Re-order to match saved order for fields that exist in saved config
            $savedOrder = collect($savedFields)->pluck('name')->values()->all();
            $fields = $mergedByName->sortBy(function ($field) use ($savedOrder) {
                $pos = array_search($field['name'], $savedOrder);
                return $pos === false ? PHP_INT_MAX : $pos;
            })->values()->all();
        } else {
            $fields = array_map(fn($f) => array_merge($f, ['visible' => true]), $masterFields);
        }

        $title           = ucfirst($formType) . ' ' . ucfirst($configType);
        $customFieldDefs = CustomFieldDefinition::getForForm($formType);

        return view('form-configs.edit', compact('formType', 'configType', 'fields', 'title', 'customFieldDefs'));
    }

    public function update(Request $request, string $formType, string $configType): RedirectResponse
    {
        $this->validateParams($formType, $configType);

        $request->validate([
            'fields'          => ['required', 'string'],
        ]);

        $decoded = json_decode($request->input('fields'), true);

        if (!is_array($decoded)) {
            return back()->withErrors(['fields' => 'Invalid fields data.']);
        }

        // Validate each item
        foreach ($decoded as $item) {
            if (!isset($item['name']) || !is_string($item['name'])) {
                return back()->withErrors(['fields' => 'Each field must have a name.']);
            }
        }

        $name = ucfirst($formType) . ' ' . ucfirst($configType) . ' Config';
        $this->service->save($formType, $configType, $name, $decoded);

        return redirect()->route('form-configs.index')
            ->with('success', ucfirst($formType) . ' ' . ucfirst($configType) . ' configuration saved successfully.');
    }

    private function validateParams(string $formType, string $configType): void
    {
        abort_if(!in_array($formType, ['building', 'unit']), 404);
        abort_if(!in_array($configType, ['form', 'template']), 404);
    }
}
