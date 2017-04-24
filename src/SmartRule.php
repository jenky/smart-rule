<?php

namespace Jenky\SmartRule;

trait SmartRule
{
    /**
     * Get the validation rules.
     * TODO: add callback argurment.
     *
     * @param  mixed $class
     * @param  array $constraints
     * @return array
     */
    public function rules($class, array $constraints = [])
    {
        if (! $constraints) {
            $constraints = [debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[1]['function']];
        }

        if (is_string($class) && class_exists($class)) {
            $class = new $class($constraints);
        }

        return $class->getRules();
    }
}
