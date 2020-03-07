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
use PSX\Dependency\InspectorInterface;

/**
 * InspectorTestCase
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    http://phpsx.org
 */
abstract class InspectorTestCase extends TestCase
{
    public function testGetServiceIds()
    {
        $inspector = $this->newInspector(new MyContainer());

        $expect = [
            'bar_service',
            'foo_service'
        ];

        $this->assertEquals($expect, $inspector->getServiceIds());
    }

    public function testGetTypedServiceIds()
    {
        $inspector = $this->newInspector(new MyContainer());

        $expect = [
            'PSX\Dependency\Tests\FooService' => 'foo_service',
            'PSX\Dependency\Tests\BarService' => 'bar_service'
        ];

        $this->assertEquals($expect, $inspector->getTypedServiceIds());
    }

    public function testGetTaggedServiceIds()
    {
        $inspector = $this->newInspector(new MyContainer());

        $expect = [
            'my_tag' => ['bar_service']
        ];

        $this->assertEquals($expect, $inspector->getTaggedServiceIds());
    }

    abstract protected function newInspector(ContainerInterface $container): InspectorInterface;
}
