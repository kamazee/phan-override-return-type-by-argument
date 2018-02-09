<?php

return [
    'directory_list' => ['src'],
    'simplify_ast' => false,
    'plugins' => [
        __DIR__ . '/../../../plugin.php',
    ],
    'include_analysis_file_list' => ['src/example.php'],
];
