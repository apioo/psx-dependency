<?php
/*
 * PSX is a open source PHP framework to develop RESTful APIs.
 * For the current version and informations visit <http://phpsx.org>
 *
 * Copyright 2010-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Doctrine\Common\Annotations\Reader;
use Psr\Container\ContainerInterface;
use PSX\Dependency\Annotation\Tag;
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
    /**
     * @var \Psr\Container\ContainerInterface
     */
    protected $container;

    /**
     * @var \Doctrine\Common\Annotations\Reader
     */
    protected $reader;

    /**
     * @param \Psr\Container\ContainerInterface $container
     * @param \Doctrine\Common\Annotations\Reader $reader
     */
    public function __construct(ContainerInterface $container, Reader $reader)
    {
        $this->container = $container;
        $this->reader    = $reader;
    }

    public function getServiceIds(): array
    {
        $methods  = $this->getServiceMethods();
        $services = array_keys($methods);

        sort($services);

        return $services;
    }

    public function getTypedServiceIds(): array
    {
        $methods = $this->getServiceMethods();
        $types   = [];

        foreach ($methods as $name => $method) {
            $type = null;
            try {
                $type = $this->getReturnTypeForMethod($method);
            } catch (\ReflectionException $e) {
                // method does not exist
            }

            if ($type !== null) {
                $types[$type] = $name;
            }
        }

        return $types;
    }

    public function getTaggedServiceIds(): array
    {
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

    private function getServiceMethods()
    {
        $services  = [];
        $reserved  = ['get', 'getParameter'];
        $container = new \ReflectionClass($this->container);

        foreach ($container->getMethods() as $method) {
            if (!in_array($method->name, $reserved) && preg_match('/^get(.+)$/', $method->name, $match)) {
                $name = Container::underscore($match[1]);
                $services[$name] = $method;
            }
        }

        return $services;
    }

    /**
     * @param \ReflectionMethod $method
     * @return string|null
     */
    private function getTag(\ReflectionMethod $method): ?string
    {
        $doc = $method->getDocComment();
        if (!empty($doc)) {
            $tag = $this->reader->getMethodAnnotation($method, Tag::class);
            if ($tag instanceof Tag) {
                return $tag->getTag();
            }
        }

        return null;
    }

    private function getReturnTypeForMethod(\ReflectionMethod $method): ?string
    {
        $returnType = $method->getReturnType();
        if ($returnType instanceof \ReflectionNamedType) {
            return $returnType->getName();
        }

        $comment = $method->getDocComment();
        if (!empty($comment)) {
            $type = self::getAnnotationValue($comment, 'return');
            if ($type !== null && class_exists($type)) {
                return ltrim($type, '\\');
            }
        }

        return null;
    }

    private function getAnnotationValue(string $comment, string $annotation): ?string
    {
        preg_match('/@' . $annotation . ' ([a-zA-Z0-9_\\x7f-\\xff\\x5c]+)/', $comment, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        return null;
    }
}
