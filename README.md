PSX Dependency
===

## About

A simple and fast PSR-11 compatible DI container with features to autowire, tag
and inject services. Instead of YAML or config files services are simply defined
at a class by adding i.e. a `getMyService` method.

## Usage

### Container

It is possible to extend the `Container` class. All `getXXX` methods are service 
definitions which can be accessed if a new container is created.

```php
<?php

class MyContainer extends \PSX\Dependency\Container
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

$reader = new SimpleAnnotationReader();
$reader->addNamespace('PSX\Dependency\Annotation');

$container = new MyContainer();
$inspector = new ContainerInspector($container, $reader);

$autowireResolver = new AutowireResolver($container, $inspector);

$service = $autowireResolver->getObject(AutowireService::class);
```

The autowire resolver checks all arguments of the constructor of the `AutowireService`
class and tries to resolve each type based on the return type of the method
definitions in the container. Please take a look at test cases to see a complete
example.

### Tags

The following example shows how to get services which are annotated by a
specific tag:

```php
<?php

$reader = new SimpleAnnotationReader();
$reader->addNamespace('PSX\Dependency\Annotation');

$container = new MyContainer();
$inspector = new ContainerInspector($container, $reader);

$tagResolver = new TagResolver($container, $inspector);

$services = $tagResolver->getServicesByTag('my_tag');
```

To tag you service you need to add the `@Tag` annotation to your service
definition method. Then it is possible to use tag resolver to receive all
services which have added a specific tag.

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

The object builder needs a doctrine annotation reader and cache instance. All
defined services are cached in production so that we only parse the annotations
once.

```php
<?php

$container = new MyContainer();
$reader = new \Doctrine\Common\Annotations\SimpleAnnotationReader();
$reader->addNamespace('PSX\Dependency\Annotation');
$cache = new \PSX\Cache\Pool(new \Doctrine\Common\Cache\ArrayCache());
$debug = false;

$builder = new \PSX\Dependency\ObjectBuilder(
    $container,
    $reader,
    $cache,
    $debug
);

$controller = $builder->getObject(MyController::class);

```

### Factory

It is also possible to set services on a container in the "Pimple" way. Through
this you can easily extend or overwrite existing containers.

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


