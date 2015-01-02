<?php

return Symfony\CS\Config\Config::create()
    // use PSR-2 level:
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->exclude('tests/fixtures/php')
            ->in(__DIR__)
    )
;
