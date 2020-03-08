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

namespace PSX\Dependency\Tests;

use Doctrine\Common\Annotations\SimpleAnnotationReader;
use PHPUnit\Framework\TestCase;
use PSX\Dependency\Inspector\ContainerInspector;
use PSX\Dependency\TagResolver;
use PSX\Dependency\TagResolverInterface;
use PSX\Dependency\TypeResolver;
use PSX\Dependency\TypeResolverInterface;
use PSX\V8\Tests\Object\Foo;

/**
 * TypeResolverTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    http://phpsx.org
 */
class TypeResolverTest extends TestCase
{
    public function testGetServiceByType()
    {
        $typeResolver = $this->newTypeResolver();

        $service = $typeResolver->getServiceByType(FooService::class);
        $this->assertInstanceOf(FooService::class, $service);

        $service = $typeResolver->getServiceByType(BarService::class);
        $this->assertInstanceOf(BarService::class, $service);
    }

    private function newTypeResolver(): TypeResolverInterface
    {
        $reader = new SimpleAnnotationReader();
        $reader->addNamespace('PSX\Dependency\Annotation');

        $container = new MyContainer();
        $inspector = new ContainerInspector($container, $reader);

        return new TypeResolver($container, $inspector);
    }
}