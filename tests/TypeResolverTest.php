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
use Psr\Container\ContainerInterface;
use PSX\Dependency\Inspector\ContainerInspector;
use PSX\Dependency\Tests\Playground\RepositoryInterface;
use PSX\Dependency\TypeResolver;

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

        $service = $typeResolver->getServiceByType(Playground\FooService::class);
        $this->assertInstanceOf(Playground\FooService::class, $service);

        $service = $typeResolver->getServiceByType(Playground\BarService::class);
        $this->assertInstanceOf(Playground\BarService::class, $service);
    }

    public function testGetServiceByTypeFactoryResolver()
    {
        $typeResolver = $this->newTypeResolver();
        
        $typeResolver->addFactoryResolver(Playground\RepositoryInterface::class, function (string $class, ContainerInterface $container): Playground\RepositoryInterface {
            $this->assertEquals(Playground\MyRepository::class, $class);

            return $container->get('table_manager')->getRepository($class);
        });

        /** @var RepositoryInterface $service */
        $service = $typeResolver->getServiceByType(Playground\MyRepository::class);
        $this->assertInstanceOf(Playground\MyRepository::class, $service);
        $this->assertEquals(Playground\MyRepository::class, $service->getClass());
    }

    private function newTypeResolver(): TypeResolver
    {
        $container = new Playground\MyContainer();
        $inspector = new ContainerInspector($container);

        return new TypeResolver($container, $inspector);
    }
}