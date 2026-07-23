<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->name('*.php');

return \Kloostermanw\CodingStandards\PhpCsFixer::configure(__DIR__)
    ->setFinder($finder);
