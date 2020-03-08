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

namespace PSX\Dependency;

use Psr\Container\ContainerInterface;

/**
 * AutowireResolver
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    http://phpsx.org
 */
class AutowireResolver implements AutowireResolverInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var InspectorInterface
     */
    private $inspector;

    public function __construct(ContainerInterface $container, InspectorInterface $inspector)
    {
        $this->container = $container;
        $this->inspector = $inspector;
    }

    /**
     * @inheritDoc
     */
    public function getObject(string $class)
    {
        $types       = $this->inspector->getTypedServiceIds();
        $reflection  = $this->newReflection($class);
        $constructor = $reflection->getConstructor();

        if (!$constructor instanceof \ReflectionMethod) {
            // in case there is no constructor
            return $reflection->newInstanceArgs();
        }

        $parameters = $constructor->getParameters();
        $arguments  = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType) {
                $serviceId = $types[$type->getName()] ?? null;

                if ($serviceId === null) {
                    throw new AutowiredException('Could not find type ' . $type->getName() . ' in container');
                }

                $arguments[] = $this->container->get($serviceId);
            } else {
                // in case we have no type-hint we try to resolve the dependency
                // based on the name
                $arguments[] = $this->container->get($parameter->getName());
            }
        }

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * @param string $class
     * @return \ReflectionClass
     * @throws AutowiredException
     */
    private function newReflection(string $class): \ReflectionClass
    {
        try {
            return new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new AutowiredException('Provided class ' . $class . ' does not exist', 0, $e);
        }
    }
}
