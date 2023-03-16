<?php

namespace RQuintin\ExtendedRelations;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait ExtendedRelations
{
    /**
     * @param string $related
     * @param string|string[]|null $foreignId
     * @return Collection
     */
    public function extendedHasMany(string $related, string|array $foreignId = null): Collection
    {
        $className = explode('\\', get_called_class());
        $fIds = $foreignId ?? $this->foreignId ?? Str::snake(end($className)) . '_' . $this->primaryKey;
        if(is_string($fIds)) $fIds = [$fIds];

        $query = $related::query();

        $first = true;
        $primaryKey = $this->primaryKey;

        foreach($fIds as $fId)
        {
            if($first)
            {
                $query->where($fId, '=', $this->$primaryKey);
                $first = !$first;
            } else $query->orWhere($fId, '=', $this->$primaryKey);
        }

        return $query->get();
    }
}
