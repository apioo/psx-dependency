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

namespace PSX\Dependency\Tests\Compiler;

use PHPUnit\Framework\TestCase;
use PSX\Dependency\Compiler\PhpCompiler;
use PSX\Dependency\CompilerInterface;
use PSX\Dependency\Tests\Playground\BarService;
use PSX\Dependency\Tests\Playground\FooService;
use PSX\Dependency\Tests\Playground\MyContainer;
use PSX\Dependency\Tests\Playground\TableManager;

/**
 * PhpCompilerTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class PhpCompilerTest extends TestCase
{
    public function testCompile()
    {
        $container = new MyContainer();
        $compiler  = $this->newCompiler();

        $code = $compiler->compile($container);

        $file = __DIR__ . '/Container.php';
        file_put_contents($file, $code);

        include_once $file;

        $container = new Container();

        $this->assertInstanceOf(FooService::class, $container->get('foo_service'));
        $this->assertInstanceOf(BarService::class, $container->get('bar_service'));
        $this->assertInstanceOf(TableManager::class, $container->get('table_manager'));
    }

    protected function newCompiler(): CompilerInterface
    {
        return new PhpCompiler('Container', __NAMESPACE__);
    }
}
