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

namespace PSX\Dependency\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use PSX\Dependency\Container;
use PSX\Dependency\Exception\NotFoundException;

/**
 * Most tests are taken from the symfony di container test
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class ContainerTest extends TestCase
{
    public function testSet()
    {
        $sc = new Container();

        $this->assertFalse($sc->has('foo'));

        $sc->set('foo', $foo = new \stdClass());

        $this->assertTrue($sc->has('foo'));
        $this->assertEquals($foo, $sc->get('foo'));
    }

    public function testSetFactory()
    {
        $sc = new Container();
        $sc->setParameter('bar', 'foo');
        $sc->set('foo', function(Container $c){
            $service = new \stdClass();
            $service->parameter = $c->getParameter('bar');
            return $service;
        });

        $this->assertTrue($sc->has('foo'));

        $service = $sc->get('foo');

        $this->assertTrue($sc->has('foo'));
        $this->assertInstanceOf(\stdClass::class, $service);
        $this->assertEquals('foo', $service->parameter);
        $this->assertSame($service, $sc->get('foo'));
    }

    public function testSetMethod()
    {
        $sc = new Playground\ProjectServiceContainer();

        $this->assertTrue($sc->has('bar'));
        $this->assertInstanceOf(\stdClass::class, $sc->get('bar'));
    }

    public function testSetWithNullResetTheService()
    {
        $sc = new Container();
        $sc->set('foo', null);
        $this->assertFalse($sc->has('foo'));
    }

    public function testGet()
    {
        $sc = new Playground\ProjectServiceContainer();
        $sc->set('foo', $foo = new \stdClass());
        $this->assertEquals($foo, $sc->get('foo'), '->get() returns the service for the given id');
        $this->assertEquals($sc->__bar, $sc->get('bar'), '->get() returns the service for the given id');
        $this->assertEquals($sc->__foo_bar, $sc->get('fooBar'), '->get() returns the service if a get*Method() is defined');
        $this->assertEquals($sc->__foo_bar, $sc->get('foo_bar'), '->get() returns the service if a get*Method() is defined');

        $sc->set('bar', $bar = new \stdClass());
        $this->assertEquals($bar, $sc->get('bar'), '->get() prefers to return a service defined with set() than one defined with a getXXXMethod()');
    }

    public function testGetThrowServiceNotFoundException()
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $sc = new Container();
        $sc->get('foo');
    }

    public function testGetSetParameter()
    {
        $sc = new Container();
        $sc->setParameter('bar', 'foo');
        $this->assertEquals('foo', $sc->getParameter('bar'), '->setParameter() sets the value of a new parameter');

        $sc->setParameter('foo', 'baz');
        $this->assertEquals('baz', $sc->getParameter('foo'), '->setParameter() overrides previously set parameter');

        $sc->setParameter('Foo', 'baz1');
        $this->assertEquals('baz1', $sc->getParameter('foo'), '->setParameter() converts the key to lowercase');
        $this->assertEquals('baz1', $sc->getParameter('FOO'), '->getParameter() converts the key to lowercase');
    }

    public function testInvalidGetParameter()
    {
        $this->expectException(NotFoundException::class);

        $sc = new Container();
        $sc->getParameter('foobar');
    }

    public function testHas()
    {
        $sc = new Playground\ProjectServiceContainer();
        $sc->set('foo', new \stdClass());

        $this->assertFalse($sc->has('foo1'), '->has() returns false if the service does not exist');
        $this->assertTrue($sc->has('foo'), '->has() returns true if the service exists');
        $this->assertTrue($sc->has('bar'), '->has() returns true if a get*Method() is defined');
        $this->assertTrue($sc->has('fooBar'), '->has() returns true if a get*Method() is defined');
        $this->assertTrue($sc->has('foo_bar'), '->has() returns true if a get*Method() is defined');
    }

    public function testInitialized()
    {
        $sc = new Playground\ProjectServiceContainer();

        $this->assertFalse($sc->initialized('foo_bar'));

        $sc->get('foo_bar');

        $this->assertTrue($sc->initialized('foo_bar'));
    }

    /**
     * @dataProvider dataForNormalizeName
     */
    public function testNormalizeName($name, $expected)
    {
        $this->assertEquals($expected, Container::normalizeName($name));
    }

    public function dataForNormalizeName()
    {
        return array(
            array('FooBar', 'FooBar'),
            array('foo_bar', 'FooBar'),
        );
    }

    /**
     * @dataProvider dataForUnderscore
     */
    public function testUnderscore($name, $expected)
    {
        $this->assertEquals($expected, Container::underscore($name));
    }

    public function dataForUnderscore()
    {
        return array(
            array('FooBar', 'foo_bar'),
            array('Foo_Bar', 'foo.bar'),
            array('Foo_BarBaz', 'foo.bar_baz'),
            array('FooBar_BazQux', 'foo_bar.baz_qux'),
            array('_Foo', '.foo'),
            array('Foo_', 'foo.'),
        );
    }
}
