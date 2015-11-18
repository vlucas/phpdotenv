<?php

use Symfony\CS\Config\Config;
use Symfony\CS\FixerInterface;
use Symfony\CS\Finder\DefaultFinder;

$fixers = [
    '-psr0',
    'long_array_syntax',
];

return Config::create()
    ->finder(DefaultFinder::create()->in(__DIR__))
    ->fixers($fixers)
    ->level(FixerInterface::PSR2_LEVEL)
    ->setUsingCache(true);
