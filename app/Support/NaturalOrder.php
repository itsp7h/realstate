<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Human ordering for names that carry numbers.
 *
 * ORDER BY in SQL is lexicographic, so a floor list came out "Floor 1, Floor
 * 10, Floor 2 … Floor 9" — correct by byte, wrong to anyone reading it. SQLite
 * has no natural-sort collation to fix that in the query, so the ordering
 * happens here, on the collection, with strnatcasecmp.
 *
 * Only for sets a request loads in full — exports and documents. A paginated
 * index must keep sorting in SQL, or page 2 would re-sort a different slice.
 */
class NaturalOrder
{
    /**
     * Sort a collection naturally by one or more keys, in the order given.
     *
     * Blank values sort last regardless of direction: an unnamed floor is an
     * incomplete record, and leading the list with a run of dashes buries the
     * rows someone opened the document to read.
     */
    public static function sort(Collection $rows, string ...$keys): Collection
    {
        return $rows->sort(function ($a, $b) use ($keys) {
            foreach ($keys as $key) {
                $left  = trim((string) data_get($a, $key));
                $right = trim((string) data_get($b, $key));

                if ($left === '' || $right === '') {
                    if ($left === $right) {
                        continue;
                    }

                    return $left === '' ? 1 : -1;
                }

                if ($cmp = strnatcasecmp($left, $right)) {
                    return $cmp;
                }
            }

            return 0;
        })->values();
    }

    /**
     * Sort the loaded relations of every model in a collection, in place.
     *
     * `['floors' => ['floor_name'], 'units' => ['unit_name']]` re-orders each
     * building's floors and units. A relation that was never eager-loaded is
     * skipped rather than lazily fetched — an export that loads 65 units in one
     * query should not fire 18 more here.
     */
    public static function relations(Collection $models, array $keysByRelation): Collection
    {
        foreach ($models as $model) {
            foreach ($keysByRelation as $relation => $keys) {
                if (! $model->relationLoaded($relation)) {
                    continue;
                }

                $model->setRelation($relation, static::sort($model->getRelation($relation), ...$keys));
            }
        }

        return $models;
    }
}
