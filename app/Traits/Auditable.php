<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            AuditLog::record('created', class_basename($model), $model->getKey(), $model->auditName());
        });

        static::updated(function ($model) {
            $changed = $model->getChanges();
            unset($changed['updated_at']);
            if (empty($changed)) return;

            $changes = [];
            foreach ($changed as $key => $new) {
                $changes[$key] = ['from' => $model->getOriginal($key), 'to' => $new];
            }
            AuditLog::record('updated', class_basename($model), $model->getKey(), $model->auditName(), $changes);
        });

        static::deleted(function ($model) {
            AuditLog::record('deleted', class_basename($model), $model->getKey(), $model->auditName());
        });
    }

    public function auditName(): string
    {
        return $this->property_name
            ?? $this->property_code
            ?? $this->floor_name
            ?? $this->unit_name
            ?? $this->name
            ?? $this->lease_agreement_no
            ?? "#{$this->getKey()}";
    }
}
