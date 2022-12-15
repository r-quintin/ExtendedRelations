ExtendedRelations
==============
[![Latest Stable Version](http://poser.pugx.org/r-quintin/extended-relations/v)](https://packagist.org/packages/r-quintin/extended-relations)
[![Total Downloads](http://poser.pugx.org/r-quintin/extended-relations/downloads)](https://packagist.org/packages/r-quintin/extended-relations)
[![License](http://poser.pugx.org/r-quintin/extended-relations/license)](https://packagist.org/packages/r-quintin/extended-relations)


## Installation
```
$ composer require r-quintin/extended-relations
```

## Simple usage
```php
  class Drivre extends ExtendedModel
  {
    /**
     * Relations on this model
     *
     * @var string|string[]|null
     */
      protected string|array|null $relationships = ['vehicles'];
      
    /**
     * Loaded relations in serialization
     *
     * @var string|array|null
     */
      protected string|array|null $loads = ['vehicles'];
  }
```

## Documentation
For setup, usage guidance, and all other docs - please consult the [Project Wiki](https://github.com/r-quintin/ExtendedRelations/wiki).


## License
ExtendedRelations is open-sourced software licensed under the [MIT license](LICENSE).
