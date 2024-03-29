<?php

namespace RQuintin\ExtendedRelations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

abstract class ExtendedModel extends Model
{
    use ExtendedRelations;

    /**
     * @var string|null
     */
    public string|null $childToExclude;

    /**
     * @var bool
     */
    public bool $childForeignIds = false;

    /**
     * @var array<string, string|string[]>
     */
    protected array|null $foreignIds = null;

    /**
     * Relations on this model
     *
     * @var string|string[]|null
     */
    protected string|array|null $relationships = null;

    /**
     * Table name of relations (used for pivot table)
     *
     * @var array<string, string>|null
     */
    protected array|null $tableRelationships = null;
    
    /**
     * Name of your relationships
     *
     * @var array<string, string>|null
     */
    protected array|null $castRelationships = null;

    /**
     * Loaded relations in serialization
     *
     * @var string|array|null
     */
    protected string|array|null $loads = null;


    /**
     * If this model is a child he doesn't load relations
     *
     * @param array $attributes
     * @param string|null $childToExclude
     */
    public function __construct(array $attributes = [], string $childToExclude = null)
    {
        $this->childToExclude = $childToExclude;

        parent::__construct($attributes);
    }

    /**
     * Call relations methods
     *
     * @param $method
     * @param $parameters
     * @return BelongsTo|Collection|mixed
     */
    public function __call($method, $parameters)
    {
        $relations = is_string($this->relationships) ? [$this->relationships] : $this->relationships;

        if($this->relationships != null && in_array($method, $relations))
        {
            $inArray = false;
            $path = new RecursiveDirectoryIterator(app_path() . '/Models');
            $modelPathAbsolute = '';
            $modelName = $this->castRelationships != null ? array_key_exists($method, $this->castRelationships) ? $this->castRelationships[$method] : $method : $method;

            foreach(new RecursiveIteratorIterator($path) as $file)
            {
                $fileNames = explode('/', explode('.', $file)[0]);

                if(end($fileNames) == ucfirst(Str::singular($modelName)))
                    $modelPathAbsolute = $file->getPathname();
            }

            $modelPath = '';
            $registerPath = false;
            foreach(explode('/', $modelPathAbsolute) as $pathPart)
            {
                if($registerPath && !str_contains($pathPart, '.php'))
                    $modelPath .= $pathPart . '\\';

                if($pathPart == 'Models')
                    $registerPath = true;
            }

            foreach($relations as $relation)
                if (Str::singular($relation) == $method)
                    $inArray = true;

            $fIds = null;
            if($this->foreignIds != null)
                if(array_key_exists($method, $this->foreignIds))
                    $fIds = $this->foreignIds[$method];

            if($inArray) {
                return $this->belongsTo('App\Models\\' . $modelPath . ucfirst($modelName), $fIds ?? Str::snake($method) . '_' . $this->primaryKey);
            } else {
                //Pivot table
                $modelRelated = 'App\Models\\' . $modelPath . ucfirst(Str::singular($modelName));
                $modelInstance = app()->make($modelRelated);
                $relationships = $modelInstance->relationships;
                $relationToFind = explode('\\', get_called_class());
                $relationToFind = $relationToFind[array_key_last($relationToFind)];
                $relationToFind = strtolower(Str::plural($relationToFind));
                $isPivot = false;

                foreach ($relationships as $relation) {
                    if ($relation == $relationToFind) $isPivot = true;
                }

                if ($isPivot) {
                    $relation = strtolower(Str::plural($modelName));
                    $table = $this->tableRelationships != null && array_key_exists($relation, $this->tableRelationships) ? $this->tableRelationships[$relation] : null;

                    return $this->belongsToMany($modelRelated, $table, $fIds)->get();
                } else {
                    return $this->extendedHasMany($modelRelated, $fIds);
                }
            }
        } else return parent::__call($method, $parameters);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        if($this->relationships != null && $this->loads != null)
        {
            $relations = is_string($this->relationships) ? [$this->relationships] : $this->relationships;
            $loads = is_string($this->loads) ? [$this->loads] : $this->loads;

            foreach($relations as $relation)
                if(in_array($relation, $loads)) {
                    if($this->childToExclude != $relation) $this->loadChild($relation);
                }
        }

        return parent::toArray();
    }

    /**
     * Set relation in this attributes
     *
     * @param $relation
     * @return void
     */
    public function loadChild($relation): void
    {
        $children = $this->$relation();
        $isBelong = false;

        if($children instanceof BelongsTo)
        {
            $isBelong = true;
            $children = [$children->get()->first()];
        }

        foreach($children as $child)
        {
            $classNameSplited = explode('/', get_called_class());
            $child->childToExclude = $classNameSplited[array_key_last($classNameSplited)];

            if(!$this->childForeignIds)
            {
                foreach($child->getAttributes() as $key => $value)
                {
                    if($key != $child->primaryKey)
                    {
                        $attributeNames = explode('_', $key);

                        if(end($attributeNames) == $child->primaryKey)
                            $child->makeHidden($key);
                    }
                }
            }
        }

        if($isBelong) $children = $children[0];

        $this->makeHidden(Str::snake($relation) . '_' . $this->primaryKey);

        $this->setAttribute($relation, $children);
    }
}
