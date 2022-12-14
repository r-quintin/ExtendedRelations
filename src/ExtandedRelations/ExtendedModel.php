<?php

namespace ExtendedRelations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

abstract class ExtendedModel extends Model
{
    /**
     * @var bool
     */
    public bool $isChild;

    /**
     * @var bool
     */
    public bool $childForeigns = false;

    /**
     * @var string|string[]
     */
    protected string|array|null $foreignId = null;

    /**
     * Relations on this model
     *
     * @var string|string[]|null
     */
    protected string|array|null $relationships = null;

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
     * @param bool $isChild
     */
    public function __construct(array $attributes = [], bool $isChild = false)
    {
        $this->isChild = $isChild;

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

        if(in_array($method, $relations))
        {
            $inArray = false;
            $path = new RecursiveDirectoryIterator(app_path() . '/Models');
            $modelPathAbsolute = '';

            foreach(new RecursiveIteratorIterator($path) as $file)
            {
                $fileNames = explode('/', explode('.', $file)[0]);

                if(end($fileNames) == ucfirst(Str::singular($method)))
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

            if($inArray)
                return $this->belongsTo('App\Models\\' . $modelPath . ucfirst($method), $foreignId ?? $this->foreignId ?? Str::snake($method) . '_' . $this->primaryKey);
            else
                return $this->extendedHasMany('App\Models\\' . $modelPath . ucfirst(Str::singular($method)));
        } else return parent::__call($method, $parameters);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        if(!$this->isChild)
        {
            if($this->relationships != null && $this->loads != null)
            {
                $relations = is_string($this->relationships) ? [$this->relationships] : $this->relationships;
                $loads = is_string($this->loads) ? [$this->loads] : $this->loads;

                foreach($relations as $relation)
                    if(in_array($relation, $loads))
                        $this->loadChild($relation);
            }

            return parent::toArray();
        }

        return $this->attributesToArray();
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
            $child->isChild = true;

        if($isBelong) $children = $children[0];

        $this->setAttribute($relation, $children);
    }

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
