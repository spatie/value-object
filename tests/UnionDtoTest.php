<?php

namespace Spatie\DataTransferObject\Tests;

use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\Tests\Dummy\RoundingCaster;

class UnionDtoTest extends TestCase
{
    /** @test */
    public function union_types_are_allowed()
    {
        $dto = new UnionDto(foo: 1);

        $this->assertEquals(1, $dto->foo);
    }

    /** @test */
    public function union_types_rounding_float()
    {
        $dto = new UnionDtoWithCast(bar: 123.456);

        $this->assertEquals(123.46, $dto->bar);
    }

    /** @test */
    public function union_types_rounding_integer()
    {
        $dto = new UnionDtoWithCast(bar: 123);

        $this->assertIsInt($dto->bar);
        $this->assertEquals(123, $dto->bar);
    }

    /** @test */
    public function complex_union_types_fallback()
    {
        $dto = new ComplexUnionDto([
            'baz' => ['value' => 3],
        ]);

        $this->assertInstanceOf(Dto1::class, $dto->baz);
        $this->assertEquals(3, $dto->baz->value);
    }

    /** @test */
    public function complex_union_types_force()
    {
        $dto = new ComplexUnionDto(
            baz: new Dto2(value: 3),
        );

        $this->assertInstanceOf(Dto2::class, $dto->baz);
        $this->assertEquals(3, $dto->baz->value);
    }
}

class UnionDto extends DataTransferObject
{
    public string | int $foo;
}

class UnionDtoWithCast extends DataTransferObject
{
    #[CastWith(RoundingCaster::class)]
    public float | int $bar;
}

class ComplexUnionDto extends DataTransferObject
{
    public Dto1 | Dto2 $baz;
}

class Dto1 extends DataTransferObject
{
    public int $value = 1;
}

class Dto2 extends DataTransferObject
{
    public int $value = 2;
}
