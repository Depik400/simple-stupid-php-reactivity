```php
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
```

output: 
hello world

hello world2

hello world

hello world2

hello world3
