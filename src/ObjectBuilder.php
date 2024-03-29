<?php
/*
 * PSX is an open source PHP framework to develop RESTful APIs.
 * For the current version and information visit <https://phpsx.org>
 *
 * Copyright 2010-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace PSX\Dependency;

use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use PSX\Dependency\Attribute\Inject;
use PSX\Dependency\Exception\InvalidConfigurationException;
use ReflectionClass;

/**
 * ObjectBuilder
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class ObjectBuilder implements ObjectBuilderInterface
{
    private TypeResolverInterface $typeResolver;
    private CacheItemPoolInterface $cache;
    private bool $debug;

    public function __construct(TypeResolverInterface $typeResolver, CacheItemPoolInterface $cache, bool $debug)
    {
        $this->typeResolver = $typeResolver;
        $this->cache = $cache;
        $this->debug = $debug;
    }

    /**
     * @throws Exception\NotFoundException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function getObject(string $className, array $constructorArguments = [], ?string $instanceOf = null): object
    {
        $class = new ReflectionClass($className);

        if ($class->getConstructor() === null) {
            $object = $class->newInstanceArgs([]);
        } else {
            $object = $class->newInstanceArgs($constructorArguments);
        }

        if ($instanceOf !== null && !$object instanceof $instanceOf) {
            throw new InvalidArgumentException('Class ' . $className . ' must be an instanceof ' . $instanceOf);
        }

        // if we are not in debug mode we can cache the dependency attributes
        // of each class so we do not need to parse the annotations
        if (!$this->debug) {
            $item = $this->cache->getItem(md5(__CLASS__ . $className));

            if ($item->isHit()) {
                $properties = $item->get();
            } else {
                $properties = $this->getProperties($class);

                $item->set($properties);
                $this->cache->save($item);
            }
        } else {
            $properties = $this->getProperties($class);
        }

        foreach ($properties as $propertyName => $serviceId) {
            $service = $this->typeResolver->getServiceByType($serviceId);

            $property = $class->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue($object, $service);
        }

        return $object;
    }

    private function getProperties(ReflectionClass $class): array
    {
        $properties = $class->getProperties();
        $result     = [];

        foreach ($properties as $property) {
            $service = $this->getServiceId($property);
            if ($service === null) {
                continue;
            }

            if (!empty($service)) {
                $result[$property->getName()] = $service;
                continue;
            }

            $service = $this->getPropertyType($property);
            if (!empty($service)) {
                $result[$property->getName()] = $service;
                continue;
            }

            $result[$property->getName()] = $property->getName();
        }

        return $result;
    }

    private function getPropertyType(\ReflectionProperty $property): ?string
    {
        $type = $property->getType();
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        return null;
    }

    private function getServiceId(\ReflectionProperty $property): ?string
    {
        $attributes = $property->getAttributes(Inject::class);
        foreach ($attributes as $attribute) {
            return $attribute->getArguments()[0] ?? '';
        }

        return null;
    }
}
