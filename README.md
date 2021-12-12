PSX Dependency
===

## About

A simple and fast PSR-11 compatible DI container with features to autowire, tag
and inject services. Instead of YAML or config files services are simply defined
at a class by adding i.e. a `getMyService` method.

## Usage

### Container

It is possible to extend the `Container` class. All `getXXX` methods are service 
definitions which can be accessed through the `get` method.

```php
<?php

use PSX\Dependency\Container;
use PSX\Dependency\Tests\Playground\FooService;
use PSX\Dependency\Tests\Playground\BarService;

class MyContainer extends Container
{
    public function getFooService(): FooService
    {
        return new FooService();
    }
    
    /**
     * @Tag("my_tag")
     */
    public function getBarService(): BarService
    {
        return new BarService($this->get('foo_service'));
    }
} 

```

### Autowire

The following example shows how you can use the autowiring feature:

```php
<?php

use PSX\Dependency\Inspector\ContainerInspector;
use PSX\Dependency\TypeResolver;
use PSX\Dependency\AutowireResolver;
use PSX\Dependency\Tests\Playground\MyContainer;
use PSX\Dependency\Tests\Playground\AutowireService;

$reader = new SimpleAnnotationReader();
$reader->addNamespace('PSX\Dependency\Annotation');

$container = new MyContainer();
$inspector = new ContainerInspector($container, $reader);
$typeResolver = new TypeResolver($container, $inspector);
$autowireResolver = new AutowireResolver($typeResolver);

$service = $autowireResolver->getObject(AutowireService::class);
```

The autowire resolver checks all arguments of the constructor of the `AutowireService`
class and tries to resolve each type based on the return type of the method
definitions in the container. Please take a look at test cases to see a complete
example.

It is also possible to provide a factory resolver which allow to resolve i.e.
repository classes:

```php
<?php

use Psr\Container\ContainerInterface;
use PSX\Dependency\TypeResolver;
use PSX\Dependency\AutowireResolver;
use PSX\Dependency\Tests\Playground\RepositoryInterface;

$typeResolver = new TypeResolver(...);
$autowireResolver = new AutowireResolver($typeResolver);

$typeResolver->addFactoryResolver(RepositoryInterface::class, function (string $class, ContainerInterface $container): RepositoryInterface {
    return $container->get('table_manager')->getRepository($class);
});

// this now allows to use the MyRepository class as a type-hint at a service and
// the autowire resolver injects the fitting service through the defined resolver
$repository = $autowireResolver->getObject(MyService::class);

```

### Tags

The following example shows how to get services which are annotated by a
specific tag:

```php
<?php

use PSX\Dependency\Inspector\ContainerInspector;
use PSX\Dependency\TagResolver;
use PSX\Dependency\Tests\Playground\MyContainer;

$reader = new SimpleAnnotationReader();
$reader->addNamespace('PSX\Dependency\Annotation');

$container = new MyContainer();
$inspector = new ContainerInspector($container, $reader);
$tagResolver = new TagResolver($container, $inspector);

$services = $tagResolver->getServicesByTag('my_tag');
```

To tag you service you need to add the `@Tag` annotation to your service
definition method. Then it is possible to use the tag resolver to receive all
services which have added a specific tag.

### Compiler

If you have created a large container it is possible to compile this container
into an optimized class which improves the performance. 

```php

use PSX\Dependency\Compiler\PhpCompiler;
use PSX\Dependency\Tests\Playground\MyContainer;

$reader = new SimpleAnnotationReader();
$reader->addNamespace('PSX\Dependency\Annotation');

$compiler = new PhpCompiler($reader, 'Container', __NAMESPACE__);
$container = new MyContainer();

// contains the compiled DI container
$code = $compiler->compile($container);

```

### Object builder

The object builder resolves properties with an `@Inject` annotation and tries
to inject the fitting service to the property. If no explicit service name was 
provided the property name is used. Note usually it is recommended to use simple
constructor injection, this class is designed for cases where this is not 
feasible.

```php
<?php

class MyController
{
    /**
     * @Inject 
     */
    protected $fooService;

    /**
     * @Inject("bar_service")
     */
    protected $baz;

    public function doSomething()
    {
        $this->fooService->power();
    }
}

```

The object builder can use a cache instance to cache all defined service keys in production.

```php
<?php

use PSX\Dependency\ObjectBuilder;
use PSX\Dependency\Tests\Playground\MyContainer;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

$container = new MyContainer();
$reader = new SimpleAnnotationReader();
$reader->addNamespace('PSX\Dependency\Annotation');
$cache = new ArrayAdapter();
$debug = false;

$builder = new ObjectBuilder(
    $container,
    $reader,
    $cache,
    $debug
);

$controller = $builder->getObject(MyController::class);

```

### Factory

It is also possible to set services on a container in the "Pimple" way. Through
this you can easily extend or overwrite existing containers. Note it is not
possible to use theses services for autowiring. In general it is recommended
to create a custom container and extend from the default container to add new
services.

```php
<?php

use Psr\Container\ContainerInterface;

$container = new \PSX\Dependency\Container();

$container->set('foo_service', function(ContainerInterface $c){
    return new FooService();
});

$container->set('bar_service', function(ContainerInterface $c){
    return new BarService($c->get('foo_service'));
});

```


