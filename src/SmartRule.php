<?php

namespace Jenky\SmartRule;

trait SmartRule
{
    /**
     * Get the validation rules.
     *
     * @param  \Jenky\SmartRule\Rule $rule
     * @param  callable|null $callback
     * @return array
     */
    public function rules(Rule $rule, callable $callback = null)
    {
        if (! $rule->getPresets() && $rule->guess) {
            $rule->setPresets(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[1]['function']);
        }

        $rules = $rule->getRules();

        if ($callback) {
            $rules = $callback($rules);
        }

        return $rules->toArray();
    }
}
