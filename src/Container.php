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

use Psr\Container\ContainerInterface;
use PSX\Dependency\Exception\NotFoundException;

/**
 * A simple and fast PSR container implementation
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    http://phpsx.org
 */
class Container implements ContainerInterface
{
    private array $factories;
    private array $services;
    private array $parameters;

    public function __construct()
    {
        $this->factories  = [];
        $this->services   = [];
        $this->parameters = [];
    }

    public function set(string $id, $object)
    {
        $name = self::normalizeName($id);

        if ($object instanceof \Closure) {
            $this->factories[$name] = $object;
        } else {
            $this->services[$name] = $object;
        }
    }

    /**
     * @throws NotFoundException
     */
    public function get(string $id)
    {
        $name = self::normalizeName($id);

        if (!isset($this->services[$name])) {
            if (isset($this->factories[$name])) {
                $this->services[$name] = call_user_func_array($this->factories[$name], [$this]);
            } elseif (method_exists($this, $method = 'get' . $name)) {
                $this->services[$name] = $this->$method();
            } else {
                throw new NotFoundException('Service ' . $name . ' not defined');
            }
        }

        return $this->services[$name];
    }

    public function has(string $id): bool
    {
        $name = self::normalizeName($id);

        return isset($this->services[$name]) || isset($this->factories[$name]) || method_exists($this, 'get' . $name);
    }

    public function initialized(string $name): bool
    {
        $name = self::normalizeName($name);

        return isset($this->services[$name]);
    }

    public function setParameter(string $name, $value)
    {
        $name = strtolower($name);

        $this->parameters[$name] = $value;
    }

    public function getParameter(string $name)
    {
        $name = strtolower($name);

        if ($this->hasParameter($name)) {
            return $this->parameters[$name];
        } else {
            throw new NotFoundException('Parameter ' . $name . ' not set');
        }
    }

    public function hasParameter(string $name): bool
    {
        $name = strtolower($name);

        return isset($this->parameters[$name]);
    }

    public static function normalizeName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    public static function underscore(string $id): string
    {
        return strtolower(preg_replace(array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), array('\\1_\\2', '\\1_\\2'), strtr($id, '_', '.')));
    }
}
