<?php

use App\Service;
use Faker\Generator as Faker;

$factory->define(Service::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'price' => rand(10, 99),
    ];
});
