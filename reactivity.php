<?php

$currentEffect = null;
$itemsMap = new WeakMap;

function track($obj, $property)
{
    global $currentEffect;
    global $itemsMap;
    if ($currentEffect) {
        $map = $itemsMap[$obj] ?? [];
        $hash = spl_object_hash($currentEffect);
        $map[$property][$hash] = $currentEffect;
        $itemsMap[$obj] = $map;
    }
}

function trigger($obj, $property, $oldValue, $newValue)
{
    global $itemsMap;
    $map = $itemsMap[$obj] ?? [];
    if ($map[$property] ?? false) {
        foreach ($map[$property] as $fn) {
            $fn();
        }
    }
}

function watch($what, $cb)
{
    global $currentEffect;
    $effect = function () use (&$currentEffect, $what, $cb) {
        $currentEffect = $cb;
        $what();
        $currentEffect = null;
    };
    $effect();
}

function ref($item)
{
    return new class ($item) {
        private $wrapped = [];
        public function __construct(protected readonly object $proxy)
        {
            $reflector = (new ReflectionClass($this->proxy))->getProperties();
            /** @var ReflectionProperty $property */
            foreach ($reflector as $property) {
                if (is_object($property->getValue($this->proxy))) {
                    $this->wrapped[$property->getName()] = new static($property->getValue($this->proxy));
                }
            }
        }
        public function __get($name)
        {
            $value = $this->wrapped[$name] ?? $this->proxy->$name ?? null;
            track($this->proxy, $name);
            return $value;
        }

        public function __set($name, $value)
        {
            trigger($this->proxy, $name, $this->proxy->$name ?? null, $value);
            if ($this->wrapped[$name] ?? false) {
                $this->wrapped[$name] = $value;
            }
            $this->proxy->$name = $value;

            return;
        }
    };
}

$std = new class {
    public $test;
};
$std->test = new class {
    public $a = 1;
};
$a = ref($std);




watch(fn() => $a->test->a, function () {
    echo 'hello world', PHP_EOL;
});

watch(fn() => $a->test->a, function () {
    echo 'hello world2', PHP_EOL;
});

watch(fn() => $a->test, function () {
    echo 'hello world3', PHP_EOL;
});


$a->test->a = 1;
$a->test = new stdClass;

// $a->helloWorld = 1;

// $a= null;
