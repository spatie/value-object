<?php

namespace Spatie\DataTransferObject\Casters;

use ArrayAccess;
use LogicException;
use Spatie\DataTransferObject\Caster;
use Traversable;

class ArrayCaster implements Caster
{
    public function __construct(
        private string $type,
        private string $itemType,
    ) {
    }

    public function cast(mixed $value): array | ArrayAccess
    {
        if ($this->type == 'array') {
            return $this->castArray($value);
        }

        if (is_subclass_of($this->type, ArrayAccess::class)) {
            return $this->castArrayAccess($value);
        }

        throw new LogicException("Caster [ArrayCaster] may only be used to cast arrays or objects that implement ArrayAccess.");
    }

    private function castArray(mixed $value): array
    {
        return $this->mapInto(destination: [], items: $value);
    }

    private function castArrayAccess(mixed $value): ArrayAccess
    {
        if (! is_subclass_of($this->type, Traversable::class)) {
            throw new LogicException("Caster [ArrayCaster] may only be used to cast ArrayAccess objects that are traversable.");
        }

        return $this->mapInto(destination: new $this->type(), items: $value);
    }

    private function castItem(mixed $data)
    {
        if ($data instanceof $this->itemType) {
            return $data;
        }

        if (is_array($data)) {
            return new $this->itemType(...$data);
        }

        throw new LogicException(
            "Caster [ArrayCaster] each item must be an array or an instance of the specified item type [{$this->itemType}]."
        );
    }

    private function mapInto(array | Traversable $destination, mixed $items): array | ArrayAccess
    {
        foreach ($items as $key => $item) {
            $destination[$key] = $this->castItem($item);
        }

        return $destination;
    }
}
