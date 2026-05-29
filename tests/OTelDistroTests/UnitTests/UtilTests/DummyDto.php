<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests;

use OTelDistroTests\UnitTests\Util\CloneUtil;

class DummyDto implements DummyBaseInterfaceForTests
{
    public int $intVal = 123;
    public string $stringVal = 'dummy';

    /** @var array<array-key, mixed> */
    public array $mapArray = [];

    /** @var list<mixed> */
    public array $listArray = [];

    public ?object $nullableObject = null;

    /**
     * @return $this
     */
    public function setInt(int $intVal): self
    {
        $this->intVal = $intVal;
        return $this;
    }

    /**
     * @return $this
     */
    public function setString(string $stringVal): self
    {
        $this->stringVal = $stringVal;
        return $this;
    }

    /**
     * @param array<array-key, mixed> $mapArray
     *
     * @return $this
     */
    public function setMapArray(array $mapArray): self
    {
        $this->mapArray = $mapArray;
        return $this;
    }

    /**
     * @param list<mixed> $listArray
     *
     * @return $this
     */
    public function setListArray(array $listArray): self
    {
        $this->listArray = $listArray;
        return $this;
    }

    /**
     * @param ?object $nullableObject
     *
     * @return $this
     */
    public function setNullableObject(?object $nullableObject): self
    {
        $this->nullableObject = $nullableObject;
        return $this;
    }

    public function __clone(): void
    {
        foreach (get_object_vars($this) as $propName => $thisPropValue) {
            $this->$propName = CloneUtil::deepClone($thisPropValue);
        }
    }
}
