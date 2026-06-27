<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/demo',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->append([
        new SplFileInfo(__DIR__ . '/bin/phpmysqlgrid-assets'),
        new SplFileInfo(__DIR__ . '/bin/lint-syntax'),
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        'single_quote' => false,
        'braces_position' => [
            'classes_opening_brace' => 'same_line',
            'functions_opening_brace' => 'same_line',
            'control_structures_opening_brace' => 'same_line',
            'anonymous_functions_opening_brace' => 'same_line',
            'anonymous_classes_opening_brace' => 'same_line',
        ],
        'line_ending' => true,
        'indentation_type' => true,
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof' => true,
    ])
    ->setFinder($finder);
