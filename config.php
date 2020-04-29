<?php

return [
    'discovery' => [],
    'types' => [
        'atom' => [
            'route' => '/atom',
            'title' => 'Atom Feed',
            'collections' => [],
            'name_fields' => [],
            'author_field' => 'author',
            'custom_content' => false,
        ],
        'json' => [
            'route' => '/json',
            'title' => 'JSON Feed',
            'collections' => [],
            'name_fields' => [],
            'author_field' => 'author',
            'custom_content' => false,
        ]
    ]
];
