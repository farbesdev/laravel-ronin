<?php

use Illuminate\Support\Str;
use Ronin\Models\Role;

$factory->define(Role::class, function(Faker\Generator $faker) {
    $name = $faker->unique()->jobTitle;

    return [
        'name'        => $name,
        'slug'        => Str::slug($name),
        'description' => $faker->sentence,
        'special'     => null,
    ];
});