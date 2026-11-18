<?php

return [
    'profiles' => [
        'cdacc' => [
            'label' => 'CDACC',
            'accent' => '#15803d',
            'icon' => 'fas fa-seedling',
            'labels' => [
                'department' => 'Department',
                'programme' => 'Programme and Level',
                'unit' => 'Unit',
            ],
        ],
        'tvet' => [
            'label' => 'TVET',
            'accent' => '#0f766e',
            'icon' => 'fas fa-tools',
            'labels' => ['department' => 'Trade Area', 'programme' => 'Programme and KNQF Level', 'unit' => 'Unit'],
        ],
        'certificate' => [
            'label' => 'Certificate',
            'accent' => '#b45309',
            'icon' => 'fas fa-certificate',
            'labels' => ['department' => 'School', 'programme' => 'Programme', 'unit' => 'Module'],
        ],
        'diploma' => [
            'label' => 'Diploma',
            'accent' => '#0369a1',
            'icon' => 'fas fa-file-alt',
            'labels' => ['department' => 'School', 'programme' => 'Programme', 'unit' => 'Unit'],
        ],
        'undergraduate' => [
            'label' => 'Undergraduate',
            'accent' => '#4338ca',
            'icon' => 'fas fa-university',
            'labels' => ['department' => 'Faculty or School', 'programme' => 'Degree Programme', 'unit' => 'Unit'],
        ],
        'professional' => [
            'label' => 'Professional',
            'accent' => '#7e22ce',
            'icon' => 'fas fa-award',
            'labels' => ['department' => 'Professional Body', 'programme' => 'Qualification and Stage', 'unit' => 'Paper'],
        ],
    ],
];
