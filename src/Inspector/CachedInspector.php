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

namespace PSX\Dependency\Inspector;

use Psr\Cache\CacheItemPoolInterface;
use PSX\Dependency\InspectorInterface;

/**
 * CachedInspector
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    http://phpsx.org
 */
class CachedInspector implements InspectorInterface
{
    private InspectorInterface $inspector;
    private CacheItemPoolInterface $cache;

    public function __construct(InspectorInterface $inspector, CacheItemPoolInterface $cache)
    {
        $this->inspector = $inspector;
        $this->cache     = $cache;
    }

    /**
     * @inheritDoc
     */
    public function getServiceIds(): array
    {
        return $this->cachedCall(__FUNCTION__, __METHOD__);
    }

    /**
     * @inheritDoc
     */
    public function getTypedServiceIds(): array
    {
        return $this->cachedCall(__FUNCTION__, __METHOD__);
    }

    /**
     * @inheritDoc
     */
    public function getTaggedServiceIds(): array
    {
        return $this->cachedCall(__FUNCTION__, __METHOD__);
    }
    
    private function cachedCall(string $methodName, string $cacheKey)
    {
        $item = $this->cache->getItem(md5($cacheKey));
        if ($item->isHit()) {
            return $item->get();
        }

        $result = $this->inspector->$methodName();

        $item->set($result);
        $this->cache->save($item);

        return $result;
    }
}
