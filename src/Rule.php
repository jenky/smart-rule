<?php

namespace Jenky\SmartRule;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

abstract class Rule
{
    /**
     * Indicates if the preset is guessed by the called method.
     *
     * @var bool
     */
    public $guess = true;

    /**
     * @var array
     */
    protected $presets = [];

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
     * @param  array $presets
     * @return void
     */
    public function __construct(array $presets = [])
    {
        $this->registerMacro();
        $this->presets = $presets;

        $rules = $this->rules();

        if (! is_array($rules)) {
            throw new InvalidArgumentException('Rules must be an array.');
        }

        $this->rules = new Collection($rules);
    }

    /**
     * Get the default validation rules.
     *
     * @return array
     */
    abstract protected function rules();

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
     * Set the presets.
     *
     * @param  mixed $presets
     * @return $this
     */
    public function setPresets($presets)
    {
        $presets = is_array($presets) ? $presets : func_get_args();

        $this->presets = $presets;

        return $this;
    }

    /**
     * Get the presets.
     *
     * @return array
     */
    public function getPresets()
    {
        return $this->presets;
    }

    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function getRules()
    {
        foreach ($this->presets as $method) {
            if (method_exists($this, $method)) {
                $this->{$method}();
            }
        }

        return $this->rules;
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
