<?php
// Example of a generic page with the internal adapter and the basic form.
// When created, it can be modified in the admin board.

return [
    'facet_limit' => 10,
    'facets' => [
        'dcterms:title' => [
            'enabled' => 0,
            'weight' => 0,
            'display' => [
                'label' => 'Title',
            ],
        ],
        'dcterms:subject' => [
            'enabled' => 1,
            'weight' => 0,
            'display' => [
                'label' => 'Subject',
            ],
        ],
        'dcterms:type' => [
            'enabled' => 1,
            'weight' => 1,
            'display' => [
                'label' => 'Type',
            ],
        ],
        'dcterms:creator' => [
            'enabled' => 1,
            'weight' => 2,
            'display' => [
                'label' => 'Creator',
            ],
        ],
        'dcterms:date' => [
            'enabled' => 1,
            'weight' => 3,
            'display' => [
                'label' => 'Date',
            ],
        ],
        'dcterms:language' => [
            'enabled' => 1,
            'weight' => 4,
            'display' => [
                'label' => 'Language',
            ],
        ],
    ],
    'sort_fields' => [
        'dcterms:title asc' => [
            'enabled' => 1,
            'weight' => 0,
            'display' => [
                'label' => 'Title Asc',
            ],
        ],
        'dcterms:title desc' => [
            'enabled' => 1,
            'weight' => 1,
            'display' => [
                'label' => 'Title Desc',
            ],
        ],
        'dcterms:creator asc' => [
            'enabled' => 1,
            'weight' => 2,
            'display' => [
                'label' => 'Creator Asc',
            ],
        ],
        'dcterms:creator desc' => [
            'enabled' => 1,
            'weight' => 3,
            'display' => [
                'label' => 'Creator Desc',
            ],
        ],
        'dcterms:date asc' => [
            'enabled' => 1,
            'weight' => 4,
            'display' => [
                'label' => 'Date Asc',
            ],
        ],
        'dcterms:title desc' => [
            'enabled' => 1,
            'weight' => 5,
            'display' => [
                'label' => 'Date Desc',
            ],
        ],
    ],
];
