<?php

namespace Ouzo\OpenApi;

use ReflectionClass;

class InternalClass
{
    public function __construct(
        private ReflectionClass $reflectionClass,
        private array $properties,
        private ?array $discriminators,
        private ?ReflectionClass $ref = null
    )
    {
    }

    public function getReflectionClass(): ReflectionClass
    {
        return $this->reflectionClass;
    }

    /** @return InternalProperty[] */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /** @return InternalDiscriminator[]|null */
    public function getDiscriminators(): ?array
    {
        return $this->discriminators;
    }

    public function getRef(): ?ReflectionClass
    {
        return $this->ref;
    }
}
