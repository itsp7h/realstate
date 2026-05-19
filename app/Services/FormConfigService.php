<?php

namespace App\Services;

use App\Models\FormConfig;
use App\Models\CustomFieldDefinition;

class FormConfigService
{
    /**
     * Master field definitions for each form type.
     * Each entry: ['name', 'label', 'required', 'section']
     */
    protected array $masterFields = [
        'building' => [
            ['name' => 'property_name',      'label' => 'Property Name',        'required' => true,  'section' => 'Property Info'],
            ['name' => 'property_code',       'label' => 'Property Code',        'required' => true,  'section' => 'Property Info'],
            ['name' => 'type_of_ownership',   'label' => 'Type of Ownership',    'required' => false, 'section' => 'Property Info'],
            ['name' => 'property_type',       'label' => 'Property Type',        'required' => false, 'section' => 'Property Info'],
            ['name' => 'land_lord_name',      'label' => 'Land Lord Name',       'required' => false, 'section' => 'Property Info'],
            ['name' => 'building_no',         'label' => 'Building No.',         'required' => false, 'section' => 'Address'],
            ['name' => 'road',                'label' => 'Road',                 'required' => false, 'section' => 'Address'],
            ['name' => 'block',               'label' => 'Block',                'required' => false, 'section' => 'Address'],
            ['name' => 'area',                'label' => 'Area',                 'required' => false, 'section' => 'Address'],
            ['name' => 'city',                'label' => 'City',                 'required' => false, 'section' => 'Address'],
            ['name' => 'total_no_of_blocks',  'label' => 'Total No. of Blocks',  'required' => false, 'section' => 'Capacity'],
            ['name' => 'total_no_of_floors',  'label' => 'Total No. of Floors',  'required' => false, 'section' => 'Capacity'],
            ['name' => 'total_no_of_units',   'label' => 'Total No. of Units',   'required' => false, 'section' => 'Capacity'],
        ],
        'unit' => [
            // Property Level
            ['name' => 'property_name',               'label' => 'Property Name',               'required' => true,  'section' => 'Property Info'],
            ['name' => 'property_code',               'label' => 'Property Code',               'required' => true,  'section' => 'Property Info'],
            ['name' => 'type_of_ownership',           'label' => 'Type of Ownership',           'required' => false, 'section' => 'Property Info'],
            ['name' => 'property_type',               'label' => 'Property Type',               'required' => false, 'section' => 'Property Info'],
            ['name' => 'land_lord_name',              'label' => 'Land Lord Name',              'required' => false, 'section' => 'Property Info'],
            // Address
            ['name' => 'building_no',                 'label' => 'Building No.',                'required' => false, 'section' => 'Address'],
            ['name' => 'road',                        'label' => 'Road',                        'required' => false, 'section' => 'Address'],
            ['name' => 'block',                       'label' => 'Block',                       'required' => false, 'section' => 'Address'],
            ['name' => 'area',                        'label' => 'Area',                        'required' => false, 'section' => 'Address'],
            ['name' => 'city',                        'label' => 'City',                        'required' => false, 'section' => 'Address'],
            // Block Level
            ['name' => 'total_no_of_blocks',          'label' => 'Total No. of Blocks',         'required' => false, 'section' => 'Block Details'],
            ['name' => 'block_name',                  'label' => 'Block Name',                  'required' => false, 'section' => 'Block Details'],
            ['name' => 'block_code',                  'label' => 'Block Code',                  'required' => false, 'section' => 'Block Details'],
            ['name' => 'building_no_2',               'label' => 'Building No. (Block Ref)',     'required' => false, 'section' => 'Block Details'],
            // Floor Level
            ['name' => 'total_no_of_floors',          'label' => 'Total No. of Floors',         'required' => false, 'section' => 'Floor Details'],
            ['name' => 'floor_name',                  'label' => 'Floor Name',                  'required' => false, 'section' => 'Floor Details'],
            ['name' => 'floor_code',                  'label' => 'Floor Code',                  'required' => false, 'section' => 'Floor Details'],
            // Unit Level
            ['name' => 'total_no_of_units',           'label' => 'Total No. of Units',          'required' => false, 'section' => 'Unit Details'],
            ['name' => 'unit_name',                   'label' => 'Unit Name',                   'required' => true,  'section' => 'Unit Details'],
            ['name' => 'description',                 'label' => 'Description',                 'required' => false, 'section' => 'Unit Details'],
            ['name' => 'unit_type',                   'label' => 'Unit Type',                   'required' => false, 'section' => 'Unit Details'],
            ['name' => 'creation_date',               'label' => 'Creation Date',               'required' => false, 'section' => 'Unit Details'],
            ['name' => 'unit_condition',              'label' => 'Unit Condition',              'required' => false, 'section' => 'Unit Details'],
            ['name' => 'view',                        'label' => 'View',                        'required' => false, 'section' => 'Unit Details'],
            ['name' => 'no_of_parkings_foc',          'label' => 'No. of Parkings (FOC)',       'required' => false, 'section' => 'Unit Details'],
            // Area & Pricing
            ['name' => 'area_unit',                   'label' => 'Area Unit',                   'required' => false, 'section' => 'Area & Pricing'],
            ['name' => 'area_inside',                 'label' => 'Area (Inside)',               'required' => false, 'section' => 'Area & Pricing'],
            ['name' => 'area_terrace',                'label' => 'Area (Terrace)',              'required' => false, 'section' => 'Area & Pricing'],
            ['name' => 'rate_per_area_unit',          'label' => 'Rate per Area Unit',          'required' => false, 'section' => 'Area & Pricing'],
            ['name' => 'rent_per_month',              'label' => 'Rent per Month',              'required' => false, 'section' => 'Area & Pricing'],
            ['name' => 'security_deposit_amount',     'label' => 'Security Deposit Amount',     'required' => false, 'section' => 'Area & Pricing'],
            // Legal
            ['name' => 'municipality_nos',            'label' => 'Municipality Nos.',           'required' => false, 'section' => 'Legal'],
            // Utilities
            ['name' => 'electricity_installation_date', 'label' => 'Electricity Installation Date', 'required' => false, 'section' => 'Utilities'],
            ['name' => 'electricity_meter_no',        'label' => 'Electricity Meter No.',       'required' => false, 'section' => 'Utilities'],
            ['name' => 'water_installation_date',     'label' => 'Water Installation Date',     'required' => false, 'section' => 'Utilities'],
            ['name' => 'water_meter_no',              'label' => 'Water Meter No.',             'required' => false, 'section' => 'Utilities'],
            ['name' => 'electricity_ac_no',           'label' => 'Electricity A/c No.',         'required' => false, 'section' => 'Utilities'],
        ],
    ];

    /**
     * Returns visible fields for the form UI.
     * If no config saved, returns all master fields (with visible=true).
     * Always appends any custom fields not yet in the saved config (new ones default to visible=true).
     */
    public function getFormFields(string $formType): array
    {
        $saved = FormConfig::getFields($formType, 'form');

        if ($saved !== null) {
            // Append any custom fields not yet tracked in saved config
            $savedNames     = array_column($saved, 'name');
            $customFields   = CustomFieldDefinition::getForForm($formType);
            $extraCustom    = $customFields->filter(fn($def) => !in_array($def->name, $savedNames));

            $merged = $saved;
            foreach ($extraCustom as $def) {
                $merged[] = [
                    'name'     => $def->name,
                    'label'    => $def->label,
                    'required' => $def->is_required,
                    'section'  => 'Custom Fields',
                    'custom'   => true,
                    'visible'  => true,
                ];
            }
            return $merged;
        }

        // Default: all fields visible
        return array_map(fn($f) => array_merge($f, ['visible' => true]), $this->getAllFields($formType));
    }

    /**
     * Returns fields for export/import (template).
     * If no config saved, returns all master fields (with visible=true).
     */
    public function getTemplateFields(string $formType): array
    {
        $saved = FormConfig::getFields($formType, 'template');

        if ($saved !== null) {
            return $saved;
        }

        return array_map(fn($f) => array_merge($f, ['visible' => true]), $this->getAllFields($formType));
    }

    /**
     * Returns the master field definition for a form type (used to build the picker UI).
     * Appends active custom field definitions at the end.
     */
    public function getAllFields(string $formType): array
    {
        $builtin = $this->masterFields[$formType] ?? [];

        $customDefs = CustomFieldDefinition::getForForm($formType);
        $custom = $customDefs->map(fn($def) => [
            'name'     => $def->name,
            'label'    => $def->label,
            'required' => $def->is_required,
            'section'  => 'Custom Fields',
            'custom'   => true,
        ])->all();

        return array_merge($builtin, $custom);
    }

    /**
     * Save (upsert) a form config.
     */
    public function save(string $formType, string $configType, string $name, array $fields): FormConfig
    {
        return FormConfig::updateOrCreate(
            ['form_type' => $formType, 'config_type' => $configType],
            ['name' => $name, 'fields' => $fields, 'is_active' => true]
        );
    }
}
