<?php

$f = file_get_contents('C:/Users/jclus/.cursor/projects/c-projects-MolecularH2Water/agent-tools/f4dc26b7-d3bb-4073-b409-237d0ff5a523.txt');
$j = json_decode($f, true);

if (is_array($j)) {
    echo 'keys: '.implode(', ', array_keys($j)).PHP_EOL;
    echo 'result: '.($j['result'] ?? 'n/a').PHP_EOL;
    echo 'tests: '.($j['tests'] ?? 'n/a').' failed: '.($j['failed'] ?? 'n/a').PHP_EOL;

    if (isset($j['tests']) && is_array($j['tests'])) {
        foreach ($j['tests'] as $test) {
            if (! is_array($test)) {
                continue;
            }
            $status = $test['status'] ?? $test['result'] ?? '';
            if ($status === 'failed') {
                echo 'FAILED: '.($test['name'] ?? 'unknown').PHP_EOL;
                echo substr((string) ($test['message'] ?? ''), 0, 800).PHP_EOL;
            }
        }
    }
}

// Pest JSON may nest differently
if (preg_match_all('/"name":"((?:\\\\.|[^"\\\\])+)","file":"((?:\\\\.|[^"\\\\])+)"(?:,"line":\d+)?,"message":"((?:\\\\.|[^"\\\\])+)"/', $f, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
        echo 'name: '.stripcslashes($match[1]).PHP_EOL;
        echo 'message: '.substr(stripcslashes($match[3]), 0, 500).PHP_EOL.PHP_EOL;
    }
}
