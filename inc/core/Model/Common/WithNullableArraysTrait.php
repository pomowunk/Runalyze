<?php

namespace Runalyze\Model\Common;

use Runalyze\Model\Activity\Entity;

trait WithNullableArraysTrait
{
    public function ensureArraysToBeNotNull()
    {
        foreach (Entity::allDatabaseProperties() as $key) {
            if ($this->isArray($key) && null === $this->get($key)) {
                $this->set($key, []);
            }
        }
    }

    public function ensureArraysToBeNullIfEmpty()
    {
        foreach (Entity::allDatabaseProperties() as $key) {
            if ($this->isArray($key) && empty($this->get($key))) {
                $this->set($key, null);
            }
        }
    }
}
