#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Basic PHP Code Style Checker
 * 
 * This is a simplified replacement for PHP CS Fixer when the binary is not available.
 * It performs basic syntax checking and some simple PSR-12 compliance checks.
 */

function checkFile(string $file): array
{
    $issues = [];
    $content = file_get_contents($file);
    
    // Check if file has strict_types declaration
    if (!str_contains($content, 'declare(strict_types=1);')) {
        $issues[] = "Missing strict_types declaration in {$file}";
    }
    
    // Check for PHP opening tag
    if (!str_starts_with($content, '<?php')) {
        $issues[] = "File should start with <?php in {$file}";
    }
    
    // Basic syntax check
    $output = [];
    $returnCode = 0;
    exec("php -l {$file} 2>&1", $output, $returnCode);
    
    if ($returnCode !== 0) {
        $issues[] = "Syntax error in {$file}: " . implode("\n", $output);
    }
    
    return $issues;
}

function main(): int
{
    $directories = ['app'];
    $allIssues = [];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            echo "Directory {$dir} not found\n";
            continue;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $issues = checkFile($file->getPathname());
                $allIssues = array_merge($allIssues, $issues);
            }
        }
    }
    
    if (empty($allIssues)) {
        echo "✅ No code style issues found\n";
        return 0;
    } else {
        echo "❌ Code style issues found:\n";
        foreach ($allIssues as $issue) {
            echo "  - {$issue}\n";
        }
        return 1;
    }
}

exit(main());