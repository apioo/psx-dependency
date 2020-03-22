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

namespace PSX\Dependency\Compiler;

use Doctrine\Common\Annotations\Reader;
use PhpParser;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use Psr\Container\ContainerInterface;
use PSX\Dependency\CompilerInterface;
use PSX\Dependency\Inspector\ContainerInspector;
use PSX\Dependency\IntrospectableContainerInterface;

/**
 * PhpCompiler
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    http://phpsx.org
 */
class PhpCompiler implements CompilerInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var string|null
     */
    private $class;

    /**
     * @var string|null
     */
    private $namespace;

    /**
     * @param Reader $reader
     * @param string|null $class
     * @param string|null $namespace
     */
    public function __construct(Reader $reader, ?string $class = null, ?string $namespace = null)
    {
        $this->reader = $reader;
        $this->class = $class;
        $this->namespace = $namespace;
    }

    public function compile(ContainerInterface $container): string
    {
        if (!$container instanceof IntrospectableContainerInterface) {
            throw new \RuntimeException('Provided container is not introspectable, your container must implement ' . IntrospectableContainerInterface::class);
        }

        $methods = $container->getServiceMethods();
        $result  = [];

        $inspector = new ContainerInspector($container, $this->reader);

        $ids   = $inspector->getServiceIds();
        $types = $inspector->getTypedServiceIds();
        $tags  = $inspector->getTaggedServiceIds();

        foreach ($methods as $name => $method) {
            $result[] = $this->compileMethod($method);
        }

        $class = new \ReflectionClass(get_class($container));

        return $this->compileContainer(
            $this->namespace ?? $class->getNamespaceName(),
            $this->class ?? $class->getShortName(),
            $result,
            $ids,
            $types,
            $tags
        );
    }

    private function compileMethod(\ReflectionMethod $method): string
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse(file_get_contents($method->getFileName()));

        $nodeTraverser = new PhpParser\NodeTraverser();
        $nodeTraverser->addVisitor(new PhpParser\NodeVisitor\NameResolver());
        $ast = $nodeTraverser->traverse($ast);

        $astNamespace = $this->findFirstOfType($ast, PhpParser\Node\Stmt\Namespace_::class);
        if ($astNamespace === null) {
            throw new \RuntimeException('Found no namespace');
        }

        $astClass = $this->findFirstOfType($astNamespace->stmts, PhpParser\Node\Stmt\Class_::class);
        if ($astClass === null) {
            $astClass = $this->findFirstOfType($astNamespace->stmts, PhpParser\Node\Stmt\Trait_::class);
        }

        if ($astClass === null) {
            throw new \RuntimeException('Found no class');
        }

        $astMethod = $this->findMethod($astClass->stmts, $method->getName());

        $code = (new PrettyPrinter\Standard())->prettyPrintFile([$astMethod]);
        return $this->trimOpeningTag($code);
    }

    private function findFirstOfType(array $stmts, string $class)
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof $class) {
                return $stmt;
            }
        }

        return null;
    }

    private function findMethod(array $stmts, string $methodName): ClassMethod
    {
        foreach ($stmts as $classMethod) {
            if (!$classMethod instanceof ClassMethod) {
                continue;
            }

            if ((string) $classMethod->name === $methodName) {
                return $classMethod;
            }
        }

        throw new \RuntimeException('Could not find method in class');
    }

    private function trimOpeningTag(string $code)
    {
        return ltrim(substr($code, 5));
    }

    private function compileContainer(string $namespace, string $class, array $methods, array $serviceIds, array $types, array $tags): string
    {
        $methods = implode("\n", $methods);
        $serviceIds = var_export($serviceIds, true);
        $types = var_export($types, true);
        $tags = var_export($tags, true);

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
class {$class} extends \PSX\Dependency\Container implements \PSX\Dependency\InspectorInterface
{
private static \$SERVICE_IDS = {$serviceIds};
private static \$TYPED_SERVICE_IDS = {$types};
private static \$TAG_SERVICE_IDS = {$tags};
{$methods}
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
