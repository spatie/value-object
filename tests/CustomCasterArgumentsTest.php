<?php

namespace Spatie\DataTransferObject\Tests;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use Illuminate\Support\Collection;
use Iterator;
use IteratorAggregate;
use LogicException;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Casters\ArrayCaster;
use Spatie\DataTransferObject\DataTransferObject;

class CustomCasterArgumentsTest extends TestCase
{
    /** @test */
    public function test_generic_array_caster_on_array_type()
    {
        $bar = new Bar(
            [
                'collectionOfFoo' => [
                    ['name' => 'a'],
                    new Foo(name: 'b'),
                    ['name' => 'c'],
                ],
                'collectionOfBaz' => [
                    ['name' => 'a'],
                    new Baz(name: 'b'),
                    ['name' => 'c'],
                ],
            ]
        );

        $this->assertIsArray($bar->collectionOfFoo);
        $this->assertCount(3, $bar->collectionOfFoo);
        $this->assertContainsOnlyInstancesOf(Foo::class, $bar->collectionOfFoo);

        $this->assertIsArray($bar->collectionOfBaz);
        $this->assertCount(3, $bar->collectionOfBaz);
        $this->assertContainsOnlyInstancesOf(Baz::class, $bar->collectionOfBaz);
    }

    public function test_generic_array_caster_on_invalid_type()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Caster [ArrayCaster] may only be used to cast arrays or objects that implement ArrayAccess.');

        new BarIllogical(
            [
                'collectionOfFoo' => [
                    ['name' => 'a'],
                    new Foo(name: 'b'),
                    ['name' => 'c'],
                ],
                'collectionOfBaz' => [
                    ['name' => 'a'],
                    new Baz(name: 'b'),
                    ['name' => 'c'],
                ],
            ]
        );
    }

    /**
     * @see https://github.com/spatie/data-transfer-object/issues/216
     */
    public function test_casting_an_empty_array_object_will_not_add_ghost_value()
    {
        $object = new BarJr([
            'collectionOfFoo' => new Collection(),
            'collectionOfBaz' => new Collection(),
        ]);

        $this->assertInstanceOf(Collection::class, $object->collectionOfFoo);
        $this->assertEmpty($object->collectionOfFoo);

        $this->assertInstanceOf(Collection::class, $object->collectionOfBaz);
        $this->assertEmpty($object->collectionOfBaz);
    }

    public function test_it_cannot_cast_array_access_without_traversable()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Caster [ArrayCaster] may only be used to cast ArrayAccess objects that are traversable.');

        new DTOWithArrayAccessImplementation(
            [
                'collectionOfFoo' => [
                    ['name' => 'a'],
                    ['name' => 'b'],
                    ['name' => 'c'],
                ],
                'collectionOfBaz' => [
                    ['name' => 'a'],
                    ['name' => 'b'],
                    ['name' => 'c'],
                ],
            ]
        );
    }

    public function test_it_can_cast_iterator_aggregate()
    {
        $object = new DTOWithArrayAccessIteratorAggregate(
            [
                'collectionOfFoo' => [
                    ['name' => 'a'],
                    ['name' => 'b'],
                    ['name' => 'c'],
                ],
                'collectionOfBaz' => [
                    ['name' => 'a'],
                    ['name' => 'b'],
                    ['name' => 'c'],
                    ['name' => 'd'],
                ],
            ]
        );

        $this->assertInstanceOf(ArrayAccessIteratorAggregate::class, $object->collectionOfFoo);
        $this->assertContainsOnlyInstancesOf(Foo::class, $object->collectionOfFoo);
        $this->assertCount(3, $object->collectionOfFoo);

        $this->assertInstanceOf(ArrayAccessIteratorAggregate::class, $object->collectionOfBaz);
        $this->assertContainsOnlyInstancesOf(Baz::class, $object->collectionOfBaz);
        $this->assertCount(4, $object->collectionOfBaz);
    }

    public function test_it_can_cast_iterator()
    {
        $object = new DTOWithArrayAccessIterator(
            [
                'collectionOfFoo' => [
                    ['name' => 'a'],
                    ['name' => 'b'],
                    ['name' => 'c'],
                ],
                'collectionOfBaz' => [
                    ['name' => 'a'],
                    ['name' => 'b'],
                    ['name' => 'c'],
                    ['name' => 'd'],
                ],
            ]
        );

        $this->assertInstanceOf(ArrayAccessIterator::class, $object->collectionOfFoo);
        $this->assertContainsOnlyInstancesOf(Foo::class, $object->collectionOfFoo);
        $this->assertCount(3, $object->collectionOfFoo);

        $this->assertInstanceOf(ArrayAccessIterator::class, $object->collectionOfBaz);
        $this->assertContainsOnlyInstancesOf(Baz::class, $object->collectionOfBaz);
        $this->assertCount(4, $object->collectionOfBaz);
    }

    public function test_it_can_cast_array_object()
    {
        $object = new DTOWithArrayObject(
            [
                'collectionOfFoo' => [
                    ['name' => 'a'],
                    ['name' => 'b'],
                    ['name' => 'c'],
                ],
                'collectionOfBaz' => [
                    ['name' => 'a'],
                    ['name' => 'b'],
                    ['name' => 'c'],
                    ['name' => 'd'],
                ],
            ]
        );

        $this->assertInstanceOf(ArrayObject::class, $object->collectionOfFoo);
        $this->assertContainsOnlyInstancesOf(Foo::class, $object->collectionOfFoo);
        $this->assertCount(3, $object->collectionOfFoo);

        $this->assertInstanceOf(ArrayObject::class, $object->collectionOfBaz);
        $this->assertContainsOnlyInstancesOf(Baz::class, $object->collectionOfBaz);
        $this->assertCount(4, $object->collectionOfBaz);
    }

    public function test_it_throws_exception_when_casting_non_array()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            "Caster [ArrayCaster] each item must be an array or an instance of the specified item type [Spatie\DataTransferObject\Tests\Foo]."
        );

        new DTOWithArrayObject(
            [
                'collectionOfFoo' => [
                    ['name' => 'a'],
                    1,
                    ['name' => 'c'],
                ],
                'collectionOfBaz' => [
                    ['name' => 'a'],
                    ['name' => 'b'],
                    ['name' => 'c'],
                    ['name' => 'd'],
                ],
            ]
        );
    }

    public function test_that_array_keys_get_cast()
    {
        $object = new Bar(
            [
                'collectionOfFoo' => [
                    'one' => ['name' => 'a'],
                    'two' => ['name' => 'b'],
                    'three' => ['name' => 'c'],
                ],
                'collectionOfBaz' => [
                    ['name' => 'a'],
                    ['name' => 'b'],
                    ['name' => 'c'],
                    ['name' => 'd'],
                ],
            ]
        );

        $this->assertArrayHasKey('one', $object->collectionOfFoo);
        $this->assertArrayHasKey('two', $object->collectionOfFoo);
        $this->assertArrayHasKey('three', $object->collectionOfFoo);
        $this->assertInstanceOf(Foo::class, $object->collectionOfFoo['one']);
        $this->assertInstanceOf(Foo::class, $object->collectionOfFoo['two']);
        $this->assertInstanceOf(Foo::class, $object->collectionOfFoo['three']);
    }

    public function test_that_array_object_keys_get_cast()
    {
        $object = new DTOWithArrayObject(
            [
                'collectionOfFoo' => [
                    'one' => ['name' => 'a'],
                    'two' => ['name' => 'b'],
                    'three' => ['name' => 'c'],
                ],
                'collectionOfBaz' => [
                    ['name' => 'a'],
                    ['name' => 'b'],
                    ['name' => 'c'],
                    ['name' => 'd'],
                ],
            ]
        );

        $this->assertArrayHasKey('one', $object->collectionOfFoo);
        $this->assertArrayHasKey('two', $object->collectionOfFoo);
        $this->assertArrayHasKey('three', $object->collectionOfFoo);
        $this->assertInstanceOf(Foo::class, $object->collectionOfFoo['one']);
        $this->assertInstanceOf(Foo::class, $object->collectionOfFoo['two']);
        $this->assertInstanceOf(Foo::class, $object->collectionOfFoo['three']);
    }
}

class Bar extends DataTransferObject
{
    /** @var \Spatie\DataTransferObject\Tests\Foo[] */
    #[CastWith(ArrayCaster::class, itemType: Foo::class)]
    public array $collectionOfFoo;

    /** @var \Spatie\DataTransferObject\Tests\Baz[] */
    #[CastWith(ArrayCaster::class, itemType: Baz::class)]
    public array $collectionOfBaz;
}

class BarJr extends DataTransferObject
{
    /** @var \Spatie\DataTransferObject\Tests\Foo[] */
    #[CastWith(ArrayCaster::class, itemType: Foo::class)]
    public Collection $collectionOfFoo;

    /** @var \Spatie\DataTransferObject\Tests\Baz[] */
    #[CastWith(ArrayCaster::class, itemType: Baz::class)]
    public Collection $collectionOfBaz;
}

class BarIllogical extends DataTransferObject
{
    /** @var \Spatie\DataTransferObject\Tests\Foo[] */
    #[CastWith(ArrayCaster::class, itemType: Foo::class)]
    public string $collectionOfFoo;

    /** @var \Spatie\DataTransferObject\Tests\Baz[] */
    #[CastWith(ArrayCaster::class, itemType: Baz::class)]
    public int $collectionOfBaz;
}

class Foo extends DataTransferObject
{
    public string $name;
}

class Baz extends DataTransferObject
{
    public string $name;
}

class ArrayAccessImplementation implements ArrayAccess
{
    public function __construct(protected array $state = [])
    {
    }

    public function offsetExists($offset)
    {
        return key_exists($offset, $this->state);
    }

    public function offsetGet($offset)
    {
        return $this->state[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (! empty($offset)) {
            $this->state[$offset] = $value;
        } else {
            $this->state[] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->state[$offset]);
    }
}

class ArrayAccessIteratorAggregate extends ArrayAccessImplementation implements IteratorAggregate
{
    public function getIterator()
    {
        return new ArrayIterator($this->state);
    }
}

class ArrayAccessIterator extends ArrayAccessImplementation implements Iterator
{
    public function current()
    {
        return current($this->state);
    }

    public function key()
    {
        return key($this->state);
    }

    public function next()
    {
        return next($this->state);
    }

    public function rewind()
    {
        return reset($this->state);
    }

    public function valid()
    {
        return isset($this->state[$this->key()]);
    }
}

class DTOWithArrayAccessImplementation extends DataTransferObject
{
    /** @var \Spatie\DataTransferObject\Tests\Foo[] */
    #[CastWith(ArrayCaster::class, itemType: Foo::class)]
    public ArrayAccessImplementation $collectionOfFoo;

    /** @var \Spatie\DataTransferObject\Tests\Baz[] */
    #[CastWith(ArrayCaster::class, itemType: Baz::class)]
    public ArrayAccessImplementation $collectionOfBaz;
}

class DTOWithArrayAccessIteratorAggregate extends DataTransferObject
{
    /** @var \Spatie\DataTransferObject\Tests\Foo[] */
    #[CastWith(ArrayCaster::class, itemType: Foo::class)]
    public ArrayAccessIteratorAggregate $collectionOfFoo;

    /** @var \Spatie\DataTransferObject\Tests\Baz[] */
    #[CastWith(ArrayCaster::class, itemType: Baz::class)]
    public ArrayAccessIteratorAggregate $collectionOfBaz;
}

class DTOWithArrayAccessIterator extends DataTransferObject
{
    /** @var \Spatie\DataTransferObject\Tests\Foo[] */
    #[CastWith(ArrayCaster::class, itemType: Foo::class)]
    public ArrayAccessIterator $collectionOfFoo;

    /** @var \Spatie\DataTransferObject\Tests\Baz[] */
    #[CastWith(ArrayCaster::class, itemType: Baz::class)]
    public ArrayAccessIterator $collectionOfBaz;
}

class DTOWithArrayObject extends DataTransferObject
{
    /** @var \Spatie\DataTransferObject\Tests\Foo[] */
    #[CastWith(ArrayCaster::class, itemType: Foo::class)]
    public ArrayObject $collectionOfFoo;

    /** @var \Spatie\DataTransferObject\Tests\Baz[] */
    #[CastWith(ArrayCaster::class, itemType: Baz::class)]
    public ArrayObject $collectionOfBaz;
}
