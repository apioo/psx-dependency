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

namespace PSX\Dependency\Compiler;

use Psr\Container\ContainerInterface;
use PSX\Dependency\CompilerInterface;
use PSX\Dependency\Inspector\ContainerInspector;

/**
 * PhpCompiler
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class PhpCompiler implements CompilerInterface
{
    private string $class;
    private ?string $namespace;

    public function __construct(string $class, ?string $namespace = null)
    {
        $this->class = $class;
        $this->namespace = $namespace;
    }

    /**
     * @throws \ReflectionException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function compile(ContainerInterface $container): string
    {
        $inspector = new ContainerInspector($container);

        $ids   = $inspector->getServiceIds();
        $types = $inspector->getTypedServiceIds();
        $tags  = $inspector->getTaggedServiceIds();
        $class = new \ReflectionClass(get_class($container));

        return $this->compileContainer(
            '\\' . $class->getName(),
            $ids,
            $types,
            $tags
        );
    }

    private function compileContainer(string $parent, array $serviceIds, array $types, array $tags): string
    {
        $serviceIds = var_export($serviceIds, true);
        $types = var_export($types, true);
        $tags = var_export($tags, true);

        $namespace = $this->namespace;
        if (!empty($namespace)) {
            $namespace = 'namespace ' . $namespace . ';';
        }

        return <<<PHP
<?php
/*
This file was automatically generated and contains the compiled DI container.
Please do not modify this file.
*/
{$namespace}
class {$this->class} extends {$parent} implements \PSX\Dependency\InspectorInterface
{
private static \$SERVICE_IDS = {$serviceIds};
private static \$TYPED_SERVICE_IDS = {$types};
private static \$TAG_SERVICE_IDS = {$tags};
public function getServiceIds(): array
{
    return self::\$SERVICE_IDS;
}
public function getTypedServiceIds(): array
{
    return self::\$TYPED_SERVICE_IDS;
}
public function getTaggedServiceIds(): array
{
    return self::\$TAG_SERVICE_IDS;
}
}

PHP;
    }
}
