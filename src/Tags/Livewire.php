<?php

namespace Jonassiewertsen\Livewire\Tags;

use Illuminate\Support\Str;
use Statamic\Tags\Tags;

class Livewire extends Tags
{
    /**
     * This will load your Livewire component in the antlers view
     *
     * {{ livewire:your-component-name }}
     *
     * The code has been copied shameless from /Livewire/LivewireBladeDirectives
     * A few small changes have been made to output the dom correctly.
     *
     * @param $expression
     * @return mixed
     */
    public function wildcard($expression)
    {
        /**
         * Fetching all parameters from our livewire tag, to mount them as livewire parameters.
         */
        $parameters = $this->params;

        /**
         * Let the Livewire magic happen.
         */
        $lastArg = trim(last(explode(',', $expression)));

        if (Str::startsWith($lastArg, 'key(') && Str::endsWith($lastArg, ')')) {
            $cachedKey = Str::replaceFirst('key(', '', Str::replaceLast(')', '', $lastArg));
            $args      = explode(',', $expression);
            array_pop($args);
            $expression = implode(',', $args);
        } else {
            $cachedKey = "'" . Str::random(7) . "'";
        }

        if (! isset($_instance)) {
            $html = \Livewire\Livewire::mount($expression, $parameters->toArray());
        } elseif ($_instance->childHasBeenRendered($cachedKey)) {
            $componentId  = $_instance->getRenderedChildComponentId($cachedKey);
            $componentTag = $_instance->getRenderedChildComponentTagName($cachedKey);
            $html         = \Livewire\Livewire::dummyMount($componentId, $componentTag);
            $_instance->preserveRenderedChild($cachedKey);
        } else {
            $response = $html = \Livewire\Livewire::mount($expression, $parameters->toArray());
            // TODO: how to get $response->id() with v3
            // $html     = $response->html();
            // $_instance->logRenderedChild($cachedKey, $response->id(), \Livewire\Livewire::getRootElementTagName($html));
        }
        return $html;
    }

    /**
     * Sharing State Between Livewire And Alpine via entangle.
     * https://laravel-livewire.com/docs/2.x/alpine-js#extracting-blade-components
     *
     * * The method is a small variation from /Livewire/LivewireBladeDirectives
     * A few small changes had to be made to get the correct output.
     *
     * {{ livewire:entangle property='showDropdown' }}
     */
    public function entangle(): string
    {
        $expression = $this->params->get('property');
        $instanceId = $this->context['_instance']->id;

        if ((object) $expression instanceof \Livewire\WireDirective)
        {
            $value = $expression->value();
            $modifier = $expression->hasModifier('defer') ? '.defer' : '';

            return "window.Livewire.find('{$instanceId}').entangle('{$value}'){$modifier}";
        }

        return "window.Livewire.find('{$instanceId}').entangle('{$expression}')";
    }

    /**
     * Accessing the Livewire component.
     *
     * The method is a small variation from /Livewire/LivewireBladeDirectives
     * A few small changes had to be made to get the correct output.
     *
     * {{ livewire:this set="('name', 'Jack')" }}
     */
    public function this(): string
    {
        $instanceId = $this->context['_instance']->id;
        $action = $this->params->take(1)->toArray();
        $method = key($action);
        $parameters = reset($action);

        return "window.Livewire.find('{$instanceId}').{$method}{$parameters}";
    }

    /**
     * Loading the livewire styles in antlers style
     *
     * {{ livewire:styles }}
     */
    public function styles(): string
    {
        return \Livewire\Livewire::styles();
    }

    /**
     * Loading the livewire styles in antlers style
     *
     * {{ livewire:scripts }}
     */
    public function scripts(): string
    {
        return \Livewire\Livewire::scripts();
    }
}
