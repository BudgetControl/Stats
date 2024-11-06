<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Trait;

trait ObjectUtils {
    
    /**
     * Filters the object based on the provided key name.
     *
     * @param string $keyName The name of the key to filter the object by.
     * @return mixed The filtered result based on the key name.
     */
    public function filter(string $keyName) {
        return function ($object) use ($keyName) {
            return $object->$keyName;
        };
    }
}