<?php

$finder = PhpCsFixer\Finder::create()->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PSR2' => true,
        '@PhpCsFixer' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'concat_space' => ['spacing' => 'one'],
        'yoda_style' => false,
        'no_superfluous_phpdoc_tags' => false,
        'blank_line_before_statement' => ['statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try', 'while', 'switch', 'if', 'for', 'foreach', 'do']],
        'phpdoc_to_comment' => false,
        'phpdoc_no_alias_tag' => ['replacements' => ['type' => 'var', 'link' => 'see']]
    ])
    ->setFinder($finder);
