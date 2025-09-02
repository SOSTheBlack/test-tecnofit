#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Script para extrair e exibir informações de cobertura de testes
 * A partir do arquivo clover.xml gerado pelo PHPUnit
 */

if ($argc < 2) {
    echo "Usage: php extract-coverage.php <path-to-clover.xml>\n";
    exit(1);
}

$cloverFile = $argv[1];

if (!file_exists($cloverFile)) {
    echo "❌ Coverage file not found: {$cloverFile}\n";
    exit(1);
}

try {
    $xml = simplexml_load_file($cloverFile);
    
    if (!$xml) {
        echo "❌ Could not parse coverage file\n";
        exit(1);
    }

    echo "📊 **COVERAGE REPORT**\n";
    echo str_repeat("=", 50) . "\n";

    // Métricas do projeto
    if ($xml->project && $xml->project->metrics) {
        $metrics = $xml->project->metrics;
        
        $elements = (int)$metrics['elements'];
        $covered = (int)$metrics['coveredelements'];
        $statements = (int)$metrics['statements'];
        $coveredstatements = (int)$metrics['coveredstatements'];
        $methods = (int)$metrics['methods'];
        $coveredmethods = (int)$metrics['coveredmethods'];
        $classes = (int)$metrics['classes'];
        $files = (int)$metrics['files'];

        // Coverage total
        if ($elements > 0) {
            $coverage = round(($covered / $elements) * 100, 2);
            $status = $coverage >= 80 ? "✅" : ($coverage >= 60 ? "⚠️" : "❌");
            echo "{$status} Total Coverage: {$coverage}% ({$covered}/{$elements} elements)\n";
        }

        // Statement coverage
        if ($statements > 0) {
            $stmtCoverage = round(($coveredstatements / $statements) * 100, 2);
            $status = $stmtCoverage >= 80 ? "✅" : ($stmtCoverage >= 60 ? "⚠️" : "❌");
            echo "{$status} Statement Coverage: {$stmtCoverage}% ({$coveredstatements}/{$statements})\n";
        }

        // Method coverage
        if ($methods > 0) {
            $methodCoverage = round(($coveredmethods / $methods) * 100, 2);
            $status = $methodCoverage >= 80 ? "✅" : ($methodCoverage >= 60 ? "⚠️" : "❌");
            echo "{$status} Method Coverage: {$methodCoverage}% ({$coveredmethods}/{$methods})\n";
        }

        echo "\n📈 **SUMMARY**\n";
        echo "Files: {$files}\n";
        echo "Classes: {$classes}\n";
        echo "Methods: {$methods}\n";
        echo "Statements: {$statements}\n";
    }

    // Listar arquivos com baixa cobertura
    echo "\n🔍 **FILES WITH LOW COVERAGE**\n";
    $lowCoverageFiles = [];
    
    if ($xml->project && $xml->project->file) {
        foreach ($xml->project->file as $file) {
            $fileName = (string)$file['name'];
            $metrics = $file->metrics;
            
            if ($metrics) {
                $fileStatements = (int)$metrics['statements'];
                $fileCoveredStatements = (int)$metrics['coveredstatements'];
                
                if ($fileStatements > 0) {
                    $fileCoverage = round(($fileCoveredStatements / $fileStatements) * 100, 2);
                    
                    if ($fileCoverage < 80) {
                        $lowCoverageFiles[] = [
                            'file' => basename($fileName),
                            'coverage' => $fileCoverage,
                            'statements' => $fileStatements,
                            'covered' => $fileCoveredStatements
                        ];
                    }
                }
            }
        }
    }

    if (empty($lowCoverageFiles)) {
        echo "🎉 All files have good coverage (≥80%)\n";
    } else {
        // Ordenar por cobertura (menor primeiro)
        usort($lowCoverageFiles, function($a, $b) {
            return $a['coverage'] <=> $b['coverage'];
        });

        foreach (array_slice($lowCoverageFiles, 0, 10) as $file) {
            $status = $file['coverage'] >= 60 ? "⚠️" : "❌";
            echo sprintf(
                "%s %-30s %6.2f%% (%d/%d)\n",
                $status,
                $file['file'],
                $file['coverage'],
                $file['covered'],
                $file['statements']
            );
        }
        
        if (count($lowCoverageFiles) > 10) {
            $remaining = count($lowCoverageFiles) - 10;
            echo "... and {$remaining} more files with low coverage\n";
        }
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "💡 Target: ≥80% coverage for production readiness\n";
    
} catch (Exception $e) {
    echo "❌ Error parsing coverage file: " . $e->getMessage() . "\n";
    exit(1);
}
