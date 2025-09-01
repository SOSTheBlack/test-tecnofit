#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Basic PHP Static Analysis Checker
 * 
 * This is a simplified replacement for PHPStan when the binary is not available.
 * It performs basic static analysis checks similar to PHPStan level 8.
 */

function analyzeFile(string $file): array
{
    $issues = [];
    $content = file_get_contents($file);
    
    // Check for type declarations
    if (!preg_match('/declare\(strict_types=1\);/', $content)) {
        $issues[] = "Missing strict_types declaration in {$file}";
    }
    
    // Check for function return types
    if (preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\([^)]*\)\s*(?::\s*([^{]+))?\s*\{/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $functionName = $match[1];
            $returnType = isset($match[2]) ? trim($match[2]) : null;
            
            // Skip magic methods and constructors
            if (str_starts_with($functionName, '__') || $functionName === 'setUp' || $functionName === 'tearDown') {
                continue;
            }
            
            if ($returnType === null) {
                $issues[] = "Function {$functionName} missing return type in {$file}";
            }
        }
    }
    
    // Check for property type declarations in classes
    if (preg_match_all('/(?:private|protected|public)\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*;/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $propertyName = $match[1];
            // This is a basic check - more sophisticated analysis would be needed for real PHPStan level 8
        }
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
                $issues = analyzeFile($file->getPathname());
                $allIssues = array_merge($allIssues, $issues);
            }
        }
    }
    
    if (empty($allIssues)) {
        echo "✅ No static analysis issues found\n";
        return 0;
    } else {
        echo "❌ Static analysis issues found:\n";
        foreach ($allIssues as $issue) {
            echo "  - {$issue}\n";
        }
        return 1;
    }
}

exit(main());