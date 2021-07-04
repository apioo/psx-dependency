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
     * @var TypeResolverInterface
     */
    private $typeResolver;

    public function __construct(TypeResolverInterface $typeResolver)
    {
        $this->typeResolver = $typeResolver;
    }

    /**
     * @inheritDoc
     */
    public function getObject(string $class)
    {
        $reflection  = $this->newReflection($class);
        $constructor = $reflection->getConstructor();

        if (!$constructor instanceof \ReflectionMethod) {
            // in case there is no constructor
            return $reflection->newInstance();
        }

        $parameters = $constructor->getParameters();
        $arguments  = [];

        foreach ($parameters as $parameter) {
            if (!$parameter->isOptional()) {
                $type = $parameter->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $arguments[] = $this->typeResolver->getServiceByType($type->getName());
                } else {
                    $arguments[] = $this->typeResolver->getServiceByType($parameter->getName());
                }
            } else {
                // if the parameter is optional we simply pass the default value
                $arguments[] = $parameter->getDefaultValue();
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
