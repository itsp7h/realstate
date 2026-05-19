<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormConfig extends Model
{
    protected $fillable = ['name', 'form_type', 'config_type', 'fields', 'is_active'];

    protected $casts = [
        'fields'    => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the active field list for a given form_type + config_type.
     * Returns array of field objects, or null if no config exists.
     */
    public static function getFields(string $formType, string $configType): ?array
    {
        $config = static::where('form_type', $formType)
            ->where('config_type', $configType)
            ->first();

        return $config ? $config->fields : null;
    }

    /**
     * Returns just the visible field names as a flat array.
     */
    public static function getVisibleFieldNames(string $formType, string $configType): ?array
    {
        $fields = static::getFields($formType, $configType);

        if ($fields === null) {
            return null;
        }

        return collect($fields)
            ->filter(fn($field) => !empty($field['visible']))
            ->pluck('name')
            ->values()
            ->all();
    }
}
