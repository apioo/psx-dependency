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

use PHPUnit\Framework\TestCase;
use PSX\Dependency\AutowireResolver;
use PSX\Dependency\AutowireResolverInterface;
use PSX\Dependency\Inspector\ContainerInspector;
use PSX\Dependency\TypeResolver;

/**
 * AutowireResolverTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    http://phpsx.org
 */
class AutowireResolverTest extends TestCase
{
    public function testGetObject()
    {
        $autowireResolver = $this->newAutowireResolver();

        /** @var Playground\AutowireService $service */
        $service = $autowireResolver->getObject(Playground\AutowireService::class);

        $this->assertInstanceOf(Playground\AutowireService::class, $service);
        $this->assertInstanceOf(Playground\FooService::class, $service->getFoo());
        $this->assertInstanceOf(Playground\BarService::class, $service->getBar());
    }

    public function testGetObjectNoConstructor()
    {
        $autowireResolver = $this->newAutowireResolver();

        /** @var Playground\BarService $service */
        $service = $autowireResolver->getObject(Playground\BarService::class);

        $this->assertInstanceOf(Playground\BarService::class, $service);
    }

    private function newAutowireResolver(): AutowireResolverInterface
    {
        $container = new Playground\MyContainer();
        $inspector = new ContainerInspector($container);
        $typeResolver = new TypeResolver($container, $inspector);

        return new AutowireResolver($typeResolver);
    }
}