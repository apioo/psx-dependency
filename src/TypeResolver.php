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

use Psr\Container\ContainerInterface;

/**
 * TypeResolverInterface
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class TypeResolver implements TypeResolverInterface
{
    private ContainerInterface $container;
    private InspectorInterface $inspector;
    private array $types;
    private array $resolvers;

    public function __construct(ContainerInterface $container, InspectorInterface $inspector)
    {
        $this->container = $container;
        $this->inspector = $inspector;
        $this->types = [];
        $this->resolvers = [];
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getServiceByType(string $class)
    {
        if (!$this->types) {
            $this->types = $this->inspector->getTypedServiceIds();
        }

        if (isset($this->types[$class])) {
            return $this->container->get($this->types[$class]);
        } elseif ($resolver = $this->getResolverForClass($class)) {
            return $resolver($class, $this->container);
        } else {
            return $this->container->get($class);
        }
    }

    public function addFactoryResolver(string $interface, \Closure $resolver): void
    {
        $this->resolvers[$interface] = $resolver;
    }

    private function getResolverForClass(string $class): ?\Closure
    {
        foreach ($this->resolvers as $interface => $resolver) {
            if (in_array($interface, class_implements($class))) {
                return $resolver;
            }
        }

        return null;
    }
}
