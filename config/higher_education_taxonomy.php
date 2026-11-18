<?php

return [
    'profiles' => [
        'cdacc' => [
            'label' => 'CDACC',
            'accent' => '#15803d',
            'icon' => 'fas fa-seedling',
            'labels' => [
                'awarding_body' => 'Awarding Body',
                'department' => 'Department',
                'programme' => 'Programme and Level',
                'unit' => 'Unit',
            ],
            'period_fields' => ['module'],
            'group_types' => ['common' => 'Common Units', 'major' => 'Major Units', 'elective' => 'Elective Units'],
        ],
        'tvet' => [
            'label' => 'TVET',
            'accent' => '#0f766e',
            'icon' => 'fas fa-tools',
            'labels' => ['awarding_body' => 'Awarding Body', 'department' => 'Trade Area', 'programme' => 'Programme and KNQF Level', 'unit' => 'Unit'],
            'period_fields' => ['module'],
            'group_types' => ['common' => 'Common Units', 'major' => 'Major Units', 'elective' => 'Elective Units'],
        ],
        'certificate' => [
            'label' => 'Certificate',
            'accent' => '#b45309',
            'icon' => 'fas fa-certificate',
            'labels' => ['awarding_body' => 'Institution / Awarding Body', 'department' => 'School', 'programme' => 'Certificate Programme', 'unit' => 'Unit'],
            'period_fields' => ['module', 'semester'],
            'group_types' => ['common' => 'Common Units', 'major' => 'Major Units', 'elective' => 'Elective Units'],
        ],
        'diploma' => [
            'label' => 'Diploma',
            'accent' => '#0369a1',
            'icon' => 'fas fa-file-alt',
            'labels' => ['awarding_body' => 'Institution / Awarding Body', 'department' => 'School', 'programme' => 'Diploma Programme', 'unit' => 'Unit'],
            'period_fields' => ['study_year', 'semester'],
            'group_types' => ['common' => 'Common Units', 'major' => 'Major Units', 'elective' => 'Elective Units'],
        ],
        'undergraduate' => [
            'label' => 'Undergraduate',
            'accent' => '#4338ca',
            'icon' => 'fas fa-university',
            'labels' => ['awarding_body' => 'University', 'department' => 'Faculty or School', 'programme' => 'Degree Programme', 'unit' => 'Unit'],
            'period_fields' => ['study_year', 'semester'],
            'group_types' => [
                'university_common' => 'University Common Units',
                'faculty_common' => 'Faculty Common Units',
                'core' => 'Core Units',
                'specialization' => 'Specialization Units',
                'elective' => 'Elective Units',
                'research' => 'Research / Project',
            ],
        ],
        'professional' => [
            'label' => 'Professional',
            'accent' => '#7e22ce',
            'icon' => 'fas fa-award',
            'labels' => ['awarding_body' => 'Professional Body', 'department' => 'Qualification', 'programme' => 'Stage / Level', 'unit' => 'Paper'],
            'period_fields' => ['stage', 'section'],
            'group_types' => ['paper' => 'Papers'],
        ],
    ],
];
