<?php
/*
 * PSX is a open source PHP framework to develop RESTful APIs.
 * For the current version and informations visit <http://phpsx.org>
 *
 * Copyright 2010-2020 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace PSX\Dependency\Inspector;

use JetBrains\PhpStorm\Pure;
use Psr\Container\ContainerInterface;
use PSX\Dependency\Attribute\Tag;
use PSX\Dependency\Container;
use PSX\Dependency\InspectorInterface;

/**
 * Service which inspects the DI container and returns additional information
 * about the defined service methods
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    http://phpsx.org
 */
class ContainerInspector implements InspectorInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getServiceIds(): array
    {
        if ($this->container instanceof InspectorInterface) {
            // in this case the container is already compiled
            return $this->container->getServiceIds();
        }

        $methods  = $this->getServiceMethods();
        $services = array_keys($methods);

        sort($services);

        return $services;
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getTypedServiceIds(): array
    {
        if ($this->container instanceof InspectorInterface) {
            // in this case the container is already compiled
            return $this->container->getTypedServiceIds();
        }

        $methods = $this->getServiceMethods();
        $types   = [];

        foreach ($methods as $name => $method) {
            $type = $this->getReturnTypeForMethod($method);
            if ($type === null) {
                continue;
            }

            $class = new \ReflectionClass($type);
            if ($class->isInterface()) {
                // in case the return type is an interface we need to get also
                // the class of the concrete implementation, this allows us to
                // resolve concrete type-hints in case multiple implementations
                // exist for an interface
                $instance = $this->container->get($name);
                $types[get_class($instance)] = $name;
            }

            $types[$class->getName()] = $name;
        }

        return $types;
    }

    public function getTaggedServiceIds(): array
    {
        if ($this->container instanceof InspectorInterface) {
            // in this case the container is already compiled
            return $this->container->getTaggedServiceIds();
        }

        $methods = $this->getServiceMethods();
        $tags    = [];

        foreach ($methods as $name => $method) {
            $tag = $this->getTag($method);
            if (!empty($tag)) {
                if (!isset($tags[$tag])) {
                    $tags[$tag] = [];
                }

                $tags[$tag][] = $name;
            }
        }

        return $tags;
    }

    public function getServiceMethods(): array
    {
        $services  = [];
        $reserved  = ['get', 'getParameter', 'getServiceMethods', 'getServiceIds', 'getTypedServiceIds', 'getTaggedServiceIds'];
        $container = new \ReflectionClass(get_class($this->container));

        foreach ($container->getMethods() as $method) {
            if (!in_array($method->name, $reserved) && preg_match('/^get(.+)$/', $method->name, $match)) {
                $name = Container::underscore($match[1]);
                $services[$name] = $method;
            }
        }

        return $services;
    }

    #[Pure]
    private function getTag(\ReflectionMethod $method): ?string
    {
        $attributes = $method->getAttributes(Tag::class);
        foreach ($attributes as $attribute) {
            return $attribute->getArguments()[0] ?? null;
        }

        return null;
    }

    #[Pure]
    private function getReturnTypeForMethod(\ReflectionMethod $method): ?string
    {
        $returnType = $method->getReturnType();
        if ($returnType instanceof \ReflectionNamedType) {
            return $returnType->getName();
        }

        return null;
    }
}
