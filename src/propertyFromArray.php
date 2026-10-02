<?php

namespace Pachel\TableAdmin\Traits;

trait propertyFromArray
{
    protected function _setProperty($data)
    {
        if (is_object($data) || is_array($data)) {
            $data = (array) $data;
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }
}