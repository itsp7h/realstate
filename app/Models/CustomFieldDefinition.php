<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Models\Building;
use App\Models\PropertyUnit;

class CustomFieldDefinition extends Model
{
    protected $fillable = [
        'form_type',
        'name',
        'label',
        'field_type',
        'options',
        'is_required',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'options'     => 'array',
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
    ];

    protected static function booted(): void
    {
        // When a custom field is deactivated or deleted, scrub its value
        // from every related record's custom_fields JSON column.
        static::updating(function (self $def) {
            if ($def->isDirty('is_active') && ! $def->is_active) {
                static::scrubFromRecords($def->form_type, $def->name);
            }
        });

        static::deleting(function (self $def) {
            static::scrubFromRecords($def->form_type, $def->name);
        });
    }

    private static function scrubFromRecords(string $formType, string $fieldName): void
    {
        $model = $formType === 'building' ? Building::class : PropertyUnit::class;

        $model::whereNotNull('custom_fields')
            ->get()
            ->each(function ($record) use ($fieldName) {
                $fields = $record->custom_fields ?? [];
                if (array_key_exists($fieldName, $fields)) {
                    unset($fields[$fieldName]);
                    $record->updateQuietly(['custom_fields' => empty($fields) ? null : $fields]);
                }
            });
    }

    /**
     * Scope: active definitions for a given form type, ordered by sort_order.
     */
    public function scopeForForm(Builder $query, string $formType): Builder
    {
        return $query->where('form_type', $formType)
                     ->where('is_active', true)
                     ->orderBy('sort_order');
    }

    /**
     * Returns all active definitions for a form type as a Collection.
     */
    public static function getForForm(string $formType): Collection
    {
        return static::forForm($formType)->get();
    }
}
