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

namespace PSX\Dependency;

use PSX\Dependency\Exception\NotFoundException;

/**
 * TypeResolverInterface
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    http://phpsx.org
 */
interface TypeResolverInterface 
{
    /**
     * Returns a service based on a specific return type
     * 
     * @throws NotFoundException
     */
    public function getServiceByType(string $class);

    /**
     * Adds a factory resolver which allows to resolve classes by a factory.
     * This provides a way to i.e. resolve repositories from a table manager.
     * The closure receives the class name and the container
     */
    public function addFactoryResolver(string $interface, \Closure $resolver): void;
}
