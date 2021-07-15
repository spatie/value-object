# Data transfer objects with batteries included

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/data-transfer-object.svg?style=flat-square)](https://packagist.org/packages/spatie/data-transfer-object)
![Test](https://github.com/spatie/data-transfer-object/workflows/Test/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/data-transfer-object.svg?style=flat-square)](https://packagist.org/packages/spatie/data-transfer-object)

## Installation

You can install the package via composer:

```bash
composer require spatie/data-transfer-object
```

* **Note**: v3 of this package only supports `php:^8.0`. If you're looking for the older version, check out [v2](https://github.com/spatie/data-transfer-object/tree/v2).

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/data-transfer-object.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/data-transfer-object)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Usage

The goal of this package is to make constructing objects from arrays of (serialized) data as easy as possible. Here's what a DTO looks like:

```php
use Spatie\DataTransferObject\DataTransferObject;

class MyDTO extends DataTransferObject
{
    public OtherDTO $otherDTO;
    
    public OtherDTOCollection $collection;
    
    #[CastWith(ComplexObjectCaster::class)]
    public ComplexObject $complexObject;
    
    public ComplexObjectWithCast $complexObjectWithCast;
    
    #[NumberBetween(1, 100)]
    public int $a;
}
```

You could construct this DTO like so:

```php
$dto = new MyDTO(
    a: 5,
    collection: [
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
    ],
    complexObject: [
        'name' => 'test',
    ],
    complexObjectWithCast: [
        'name' => 'test',
    ],
    otherDTO: ['id' => 5],
);
```

Let's discuss all possibilities one by one.

## Named arguments

Constructing a DTO can be done with named arguments. It's also possible to still use the old array notation. This example is equivalent to the one above.

```php
$dto = new MyDTO([
    'a' => 5,
    'collection' => [
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
    ],
    'complexObject' => [
        'name' => 'test',
    ],
    'complexObjectWithCast' => [
        'name' => 'test',
    ],
    'otherDTO' => ['id' => 5],
]);
```

## Value casts

If a DTO has a property that is another DTO or a DTO collection, the package will take care of automatically casting arrays of data to those DTOs:

```php
$dto = new MyDTO(
    collection: [ // This will become an object of class OtherDTOCollection
        ['id' => 1],
        ['id' => 2], // Each item will be an instance of OtherDTO
        ['id' => 3],
    ],
    otherDTO: ['id' => 5], // This data will be cast to OtherDTO
);
```

### Custom casters

You can build your own caster classes, which will take whatever input they are given, and will cast that input to the desired result.

Take a look at the `ComplexObject`:

```php
class ComplexObject
{
    public string $name;
}
```

And its caster `ComplexObjectCaster`:

```php
use Spatie\DataTransferObject\Caster;

class ComplexObjectCaster implements Caster
{
    /**
     * @param array|mixed $value
     *
     * @return mixed
     */
    public function cast(mixed $value): ComplexObject
    {
        return new ComplexObject(
            name: $value['name']
        );
    }
}
```

### Class-specific casters

Instead of specifying which caster should be used for each property, you can also define that caster on the target class itself:

```php
class MyDTO extends DataTransferObject
{
    public ComplexObjectWithCast $complexObjectWithCast;
}
```

```php
#[CastWith(ComplexObjectWithCastCaster::class)]
class ComplexObjectWithCast
{
    public string $name;
}
```

### Default casters

It's possible to define default casters on a DTO class itself. These casters will be used whenever a property with a given type is encountered within the DTO class.

```php
#[
    DefaultCast(DateTimeImmutable::class, DateTimeImmutableCaster::class),
    DefaultCast(MyEnum::class, EnumCaster::class),
]
abstract class BaseDataTransferObject extends DataTransferObject
{
    public MyEnum $status; // EnumCaster will be used
    
    public DateTimeImmutable $date; // DateTimeImmutableCaster will be used
}
```

### Using custom caster arguments

Any caster can be passed custom arguments, the built-in `ArrayCaster` implementation is such an example:

```php
class ArrayCaster implements Caster
{
    public function __construct(
        private array $types,
        private string $itemType,
    ) {
    }

    public function cast(mixed $value): array | ArrayAccess
    {
        foreach ($this->types as $type) {
            if ($type == 'array') {
                return $this->castArray($value);
            }

            if (is_subclass_of($type, ArrayAccess::class)) {
                return $this->castArrayAccess($type, $value);
            }
        }

        throw new LogicException("Caster [ArrayCaster] may only be used to cast arrays or objects that implement ArrayAccess.");
    }

    private function castArray(array $value): array
    {
        return array_map([$this, 'makeItem'], $value);
    }

    private function castArrayAccess(string $type, array $value): ArrayAccess
    {
        $arrayAccess = new $type();

        foreach ($this->castArray($value) as $item) {
            $arrayAccess[] = $item;
        }

        return $arrayAccess;
    }

    private function makeItem(mixed $data): mixed
    {
        if ($data instanceof $this->itemType) {
            return $data;
        }

        if (is_array($data)) {
            return new $this->itemType(...$data);
        }

        throw new LogicException(
            'Caster [ArrayCaster] requires each item to be either an array or an instance of the specified class.'
        );
    }
}
```

Note that you don't need to use named arguments to pass input as generic caster arguments, though they do make it more clear:

```php
    /** @var \Spatie\DataTransferObject\Tests\Foo[] */
    #[CastWith(ArrayCaster::class, itemType: Foo::class)]
    public array $collectionWithNamedArguments;
    
    /** @var \Spatie\DataTransferObject\Tests\Foo[] */
    #[CastWith(ArrayCaster::class, Foo::class)]
    public array $collectionWithoutNamedArguments;
```

Also note that the first argument passed to the caster constructor is always the array with type(s) of the value being casted.
All other arguments will be the ones passed as extra arguments in the `CastWith` attribute.

## Validation

This package doesn't offer any specific validation functionality, but it does give you a way to build your own validation attributes. For example, `NumberBetween` is a user-implemented validation attribute:

```php
class MyDTO extends DataTransferObject
{
    #[NumberBetween(1, 100)]
    public int $a;
}
```

It works like this under the hood:

```php
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class NumberBetween implements Validator
{
    public function __construct(
        private int $min,
        private int $max
    ) {
    }

    public function validate(mixed $value): ValidationResult
    {
        if ($value < $this->min) {
            return ValidationResult::invalid("Value should be greater than or equal to {$this->min}");
        }

        if ($value > $this->max) {
            return ValidationResult::invalid("Value should be less than or equal to {$this->max}");
        }

        return ValidationResult::valid();
    }
}
```

## Strict DTOs

The previous version of this package added the `FlexibleDataTransferObject` class which allowed you to ignore properties that didn't exist on the DTO. This behaviour has been changed, all DTOs are flexible now by default, but you can make them strict by using the `#[Strict]` attribute:


```php
class NonStrictDto extends DataTransferObject
{
    public string $name;
}

// This works
new NonStrictDto(
    name: 'name',
    unknown: 'unknown'
);
```

```php
use \Spatie\DataTransferObject\Attributes\Strict;

#[Strict]
class StrictDto extends DataTransferObject
{
    public string $name;
}

// This throws a \Spatie\DataTransferObject\Exceptions\UnknownProperties exception
new StrictDto(
    name: 'name',
    unknown: 'unknown'
);
```

## Helper functions

There are also some helper functions provided for working with multiple properties at once.

```php
$postData->all();

$postData
    ->only('title', 'body')
    ->toArray();
    
$postData
    ->except('author')
    ->toArray();
```

Note that `all()` will simply return all properties, while `toArray()` will cast nested DTOs to arrays as well. 

You can chain the `except()` and `only()` methods:

```php
$postData
    ->except('title')
    ->except('body')
    ->toArray();
```

It's important to note that `except()` and `only()` are immutable, they won't change the original data transfer object.

## Immutable DTOs and cloning

This package doesn't force immutable objects since PHP doesn't support them, but you're always encouraged to keep your DTOs immutable. To help you, there's a `clone` method on every DTO which accepts data to override:

```php
$clone = $original->clone(other: ['name' => 'a']);
```

Note that no data in `$original` is changed.

## Collections of DTOs

This version removes the `DataTransferObjectCollection` class. Instead you can use simple casters and you own collection classes.

Here's an example of casting a collection of DTOs to an array of DTOs:

```php
class Bar extends DataTransferObject
{
    /** @var \Spatie\DataTransferObject\Tests\Foo[] */
    #[CastWith(FooArrayCaster::class)]
    public array $collectionOfFoo;
}

class Foo extends DataTransferObject
{
    public string $name;
}
```

```php
class FooArrayCaster implements Caster
{
    public function cast(mixed $value): array
    {
        if (! is_array($value)) {
            throw new Exception("Can only cast arrays to Foo");
        }

        return array_map(
            fn (array $data) => new Foo(...$data),
            $value
        );
    }
}
```

If you don't want the redundant typehint, or want extended collection functionality; you could create your own collection classes using any collection implementation. In this example, we use Laravel's:

```php
class Bar extends DataTransferObject
{
    #[CastWith(FooCollectionCaster::class)]
    public CollectionOfFoo $collectionOfFoo;
}

class Foo extends DataTransferObject
{
    public string $name;
}
```

```php
use Illuminate\Support\Collection;

class CollectionOfFoo extends Collection
{
    // Add the correct return type here for static analyzers to know which type of array this is 
    public function offsetGet($key): Foo
    {
        return parent::offsetGet($key);
    }
}
```

```php
class FooCollectionCaster implements Caster
{
    public function cast(mixed $value): CollectionOfFoo
    {
        return new CollectionOfFoo(array_map(
            fn (array $data) => new Foo(...$data),
            $value
        ));
    }
}
```

## Simple arrays of DTOs

For a simple array of DTOs, or an object that implements PHP's built-in `ArrayAccess`, consider using the `ArrayCaster` which requires an item type to be provided:

```php
class Bar extends DataTransferObject
{
    /** @var \Spatie\DataTransferObject\Tests\Foo[] */
    #[CastWith(ArrayCaster::class, itemType: Foo::class)]
    public array $collectionOfFoo;
}
```

## Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Kruikstraat 22, 2018 Antwerp, Belgium.

We publish all received postcards [on our company website](https://spatie.be/en/opensource/postcards).

## External tools

- [json2dto](https://json2dto.atymic.dev): a GUI to convert JSON objects to DTO classes (with nesting support). Also provides a [CLI tool](https://github.com/atymic/json2dto#cli-tool) for local usage.
- [Data Transfer Object Factory](https://github.com/anteris-dev/data-transfer-object-factory): Intelligently generates a DTO instance using the correct content for your properties based on its name and type.

## Credits

- [Brent Roose](https://github.com/brendt)
- [All Contributors](../../contributors)

Our `Arr` class contains functions copied from Laravels `Arr` helper.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
