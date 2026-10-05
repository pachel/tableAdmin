<?php

namespace Pachel\TableAdmin\Models;

trait getParrent
{
    protected  function getParent()
    {
        // Csak az utolsó 2 keretet kérjük le a hatékonyság érdekében
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        // A [0] index a ChildClass::__construct
        // Az [1] index a hívó kontextus (ha osztályból történt a hívás)

        $callerClass = $trace[2]['class'] ?? null;

        if ($callerClass) {
            return basename($callerClass);
        } else {
            return null;
        }

    }
}