ExtendedRelations
==============
[![Latest Stable Version](http://poser.pugx.org/r-quintin/extended-relations/v)](https://packagist.org/packages/r-quintin/extended-relations)
[![License](http://poser.pugx.org/r-quintin/extended-relations/license)](https://packagist.org/packages/r-quintin/extended-relations)


The ExtendedRelations package for Laravel made relations for you and loads it automatically in array serialization.

## Installation
```
$ composer require r-quintin/extended-relations
```

## Simple usage
```php
use RQuintin\ExtendedRelations\ExtendedModel;

class Driver extends ExtendedModel
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
