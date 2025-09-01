<?php

declare(strict_types=1);

/**
 * PHP CS Fixer Configuration for Tecnofit PIX API
 * 
 * This configuration applies PSR-12 standards and additional formatting rules
 * to maintain code quality and consistency across the project.
 * 
 * Note: Test files are excluded from CS Fixer as they often require different
 * formatting patterns (data providers, mocks, descriptive method names, etc.)
 * and excluding them is a common best practice in the PHP community.
 */

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/app')
    ->in(__DIR__ . '/config')
    ->in(__DIR__ . '/migrations')
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache')
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => [
            'arrays' => true,
            'arguments' => true,
            'parameters' => true,
        ],
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'single_trait_insert_per_statement' => true,
    ])
    ->setFinder($finder);
