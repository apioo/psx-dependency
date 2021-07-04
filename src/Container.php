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

/**
 * A simple and fast PSR container implementation
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    http://phpsx.org
 */
class Container implements ContainerInterface
{
    /**
     * @var array
     */
    private $factories;

    /**
     * @var array
     */
    private $services;

    /**
     * @var array
     */
    private $parameters;

    public function __construct()
    {
        $this->factories  = [];
        $this->services   = [];
        $this->parameters = [];
    }

    /**
     * @param string $id
     * @param mixed $object
     */
    public function set($id, $object)
    {
        $name = self::normalizeName($id);

        if ($object instanceof \Closure) {
            $this->factories[$name] = $object;
        } else {
            $this->services[$name] = $object;
        }
    }

    /**
     * @param string $id
     * @return mixed
     * @throws NotFoundException
     */
    public function get($id)
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

    /**
     * @param string $id
     * @return boolean
     */
    public function has($id)
    {
        $name = self::normalizeName($id);

        return isset($this->services[$name]) || isset($this->factories[$name]) || method_exists($this, 'get' . $name);
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function initialized($name)
    {
        $name = self::normalizeName($name);

        return isset($this->services[$name]);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        $name = strtolower($name);

        $this->parameters[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getParameter($name)
    {
        $name = strtolower($name);

        if ($this->hasParameter($name)) {
            return $this->parameters[$name];
        } else {
            throw new NotFoundException('Parameter ' . $name . ' not set');
        }
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function hasParameter($name)
    {
        $name = strtolower($name);

        return isset($this->parameters[$name]);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function normalizeName($name)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    /**
     * @param string $id
     * @return string
     */
    public static function underscore($id)
    {
        return strtolower(preg_replace(array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), array('\\1_\\2', '\\1_\\2'), strtr($id, '_', '.')));
    }
}
