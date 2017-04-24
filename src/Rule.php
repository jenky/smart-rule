<?php

namespace Jenky\SmartRule;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

abstract class Rule
{
    /**
     * @var array
     */
    protected $constraints = [];

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $rules;

    /**
     * @var bool
     */
    protected static $macroInjected = false;

    /**
     * Create a new class instance.
     *
     * @param  array $constraints
     * @return void
     */
    public function __construct(array $constraints = [])
    {
        $this->registerMacro();
        $this->constraints = $constraints;
        $this->rules = new Collection($this->rules());
    }

    /**
     * Get the default validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [];
    }

    /**
     * Register collection "replace" macro.
     *
     * @return void
     */
    protected function registerMacro()
    {
        if (static::$macroInjected) {
            return;
        }

        $self = $this;

        Collection::macro('replace', function ($key, $value) use ($self) {
            if (! $this->get($key)) {
                return;
            }

            if (is_callable($value)) {
                $val = $value($self->parseRules($this->get($key)));
                if ($val instanceof Collection) {
                    $value = $val->values()->implode('|');
                }
            }

            $this->put($key, $value);

            return $this;
        });

        static::$macroInjected = true;
    }

    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function getRules()
    {
        foreach ($this->constraints as $method) {
            if (method_exists($this, $method)) {
                $this->{$method}();
            }
        }

        return $this->rules->toArray();
    }

    /**
     * Parse the validation rules to a collection.
     *
     * @param  mixed $rules
     * @return \Illuminate\Support\Collection
     */
    public function parseRules($rules)
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $parsed = [];

        foreach ($rules as $rule) {
            if (strpos($rule, ':') !== false) {
                $ruleName = Arr::first(explode(':', $rule));
            } else {
                $ruleName = $rule;
            }

            $parsed[$ruleName] = $rule;
        }

        return new Collection($parsed);
    }

    /**
     * Handle dynamic method calls.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this->rules, $method) || in_array($method, ['replace'])) {
            $this->rules = call_user_func_array([$this->rules, $method], $arguments);
            return $this;
        }
    }
}
