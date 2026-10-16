<?php

return [
    'education_levels' => [
        ['slug' => 'pre-primary', 'label' => 'Pre-Primary', 'source_slugs' => ['pre-primary']],
        ['slug' => 'lower-primary', 'label' => 'Lower Primary', 'source_slugs' => ['lower-primary']],
        ['slug' => 'upper-primary', 'label' => 'Upper Primary', 'source_slugs' => ['upper-primary']],
        ['slug' => 'junior-school', 'label' => 'Junior School', 'source_slugs' => ['junior-school']],
        ['slug' => 'senior-school-cbc', 'label' => 'Senior School (CBC)', 'source_slugs' => ['senior-school-cbc']],
        ['slug' => 'high-school', 'label' => 'High School', 'source_slugs' => ['high-school']],
        ['slug' => 'tvet', 'label' => 'TVET', 'source_slugs' => ['tvet']],
        ['slug' => 'certificate-courses', 'label' => 'Certificate Courses', 'source_slugs' => ['certificate-courses']],
        ['slug' => 'diploma-courses', 'label' => 'Diploma Courses', 'source_slugs' => ['diploma-courses']],
        ['slug' => 'undergraduate', 'label' => 'Undergraduate', 'source_slugs' => ['undergraduate']],
        ['slug' => 'professional-courses', 'label' => 'Professional Courses', 'source_slugs' => ['professional-courses']],
        ['slug' => 'teacher-resources', 'label' => 'Teacher Resources', 'source_slugs' => ['teacher-resources']],
    ],

    /*
    |--------------------------------------------------------------------------
    | Canonical school classes, grades, and main examinations
    |--------------------------------------------------------------------------
    |
    | Every entry is a peer in its education level. Keep the intended display
    | order here; all admin, instructor, browser, and API trees inherit it.
    |
    */
    'class_grades_by_level' => [
        'pre-primary' => [
            ['slug' => 'pp1', 'label' => 'PP1'],
            ['slug' => 'pp2', 'label' => 'PP2'],
        ],
        'lower-primary' => [
            ['slug' => 'grade-1', 'label' => 'Grade 1'],
            ['slug' => 'grade-2', 'label' => 'Grade 2'],
            ['slug' => 'grade-3', 'label' => 'Grade 3'],
        ],
        'upper-primary' => [
            ['slug' => 'grade-4', 'label' => 'Grade 4'],
            ['slug' => 'grade-5', 'label' => 'Grade 5'],
            ['slug' => 'grade-6', 'label' => 'Grade 6'],
            ['slug' => 'kpsea', 'label' => 'KPSEA'],
        ],
        'junior-school' => [
            ['slug' => 'grade-7', 'label' => 'Grade 7'],
            ['slug' => 'grade-8', 'label' => 'Grade 8'],
            ['slug' => 'grade-9', 'label' => 'Grade 9'],
            ['slug' => 'kjsea', 'label' => 'KJSEA – National Assessment'],
        ],
        'senior-school-cbc' => [
            ['slug' => 'grade-10', 'label' => 'Grade 10'],
            ['slug' => 'grade-11', 'label' => 'Grade 11'],
            ['slug' => 'grade-12', 'label' => 'Grade 12'],
            ['slug' => 'kcbe', 'label' => 'KCBE – National Assessment'],
        ],
        'high-school' => [
            ['slug' => 'form-1', 'label' => 'Form 1'],
            ['slug' => 'form-2', 'label' => 'Form 2'],
            ['slug' => 'form-3', 'label' => 'Form 3'],
            ['slug' => 'form-4', 'label' => 'Form 4'],
            ['slug' => 'form-5', 'label' => 'Form 5'],
            ['slug' => 'kcse', 'label' => 'KCSE – National Assessment'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Canonical subjects by education level
    |--------------------------------------------------------------------------
    |
    | Every class, grade, and main exam in a level uses the same subject source.
    |
    */
    'subjects_by_level' => [
        'pre-primary' => [],
        'lower-primary' => [
            ['slug' => 'mathematics', 'name' => 'Mathematics'],
            ['slug' => 'english', 'name' => 'English'],
            ['slug' => 'kiswahili', 'name' => 'Kiswahili'],
            ['slug' => 'environmental-activities', 'name' => 'Environmental Activities'],
            ['slug' => 'creative-arts', 'name' => 'Creative Arts'],
            ['slug' => 'religious-education', 'name' => 'Religious Education'],
        ],
        'upper-primary' => [
            ['slug' => 'mathematics', 'name' => 'Mathematics'],
            ['slug' => 'english', 'name' => 'English'],
            ['slug' => 'kiswahili', 'name' => 'Kiswahili'],
            ['slug' => 'science-and-technology', 'name' => 'Science & Technology'],
            ['slug' => 'social-studies', 'name' => 'Social Studies'],
            ['slug' => 'agriculture', 'name' => 'Agriculture'],
            ['slug' => 'creative-arts', 'name' => 'Creative Arts'],
            ['slug' => 'religious-education', 'name' => 'Religious Education'],
        ],
        'junior-school' => [
            ['slug' => 'mathematics', 'name' => 'Mathematics'],
            ['slug' => 'english', 'name' => 'English'],
            ['slug' => 'kiswahili', 'name' => 'Kiswahili'],
            ['slug' => 'integrated-science', 'name' => 'Integrated Science'],
            ['slug' => 'social-studies', 'name' => 'Social Studies'],
            ['slug' => 'pre-technical-studies', 'name' => 'Pre-Technical Studies'],
            ['slug' => 'agriculture', 'name' => 'Agriculture'],
            ['slug' => 'computer-studies', 'name' => 'Computer Studies'],
            ['slug' => 'cre-ire-hre', 'name' => 'CRE/IRE/HRE'],
            ['slug' => 'business-studies', 'name' => 'Business Studies'],
            ['slug' => 'creative-arts', 'name' => 'Creative Arts'],
        ],
        'senior-school-cbc' => [
            ['slug' => 'stem', 'name' => 'STEM'],
            ['slug' => 'social-sciences', 'name' => 'Social Sciences'],
            ['slug' => 'arts-and-sports', 'name' => 'Arts & Sports'],
            ['slug' => 'languages', 'name' => 'Languages'],
        ],
        'high-school' => [
            ['slug' => 'english', 'name' => 'English'],
            ['slug' => 'kiswahili', 'name' => 'Kiswahili'],
            ['slug' => 'mathematics', 'name' => 'Mathematics'],
            ['slug' => 'biology', 'name' => 'Biology'],
            ['slug' => 'chemistry', 'name' => 'Chemistry'],
            ['slug' => 'physics', 'name' => 'Physics'],
            ['slug' => 'history-and-government', 'name' => 'History and Government'],
            ['slug' => 'geography', 'name' => 'Geography'],
            ['slug' => 'christian-religious-education-cre', 'name' => 'Christian Religious Education (CRE)'],
            ['slug' => 'islamic-religious-education-ire', 'name' => 'Islamic Religious Education (IRE)'],
            ['slug' => 'hindu-religious-education-hre', 'name' => 'Hindu Religious Education (HRE)'],
            ['slug' => 'business-studies', 'name' => 'Business Studies'],
            ['slug' => 'agriculture', 'name' => 'Agriculture'],
            ['slug' => 'computer-studies', 'name' => 'Computer Studies'],
            ['slug' => 'home-science', 'name' => 'Home Science'],
            ['slug' => 'art-and-design', 'name' => 'Art and Design'],
            ['slug' => 'music', 'name' => 'Music'],
            ['slug' => 'french', 'name' => 'French'],
            ['slug' => 'german', 'name' => 'German'],
            ['slug' => 'arabic', 'name' => 'Arabic'],
            ['slug' => 'chinese', 'name' => 'Chinese'],
            ['slug' => 'kenya-sign-language', 'name' => 'Kenya Sign Language'],
            ['slug' => 'building-construction', 'name' => 'Building Construction'],
            ['slug' => 'power-mechanics', 'name' => 'Power Mechanics'],
            ['slug' => 'electricity', 'name' => 'Electricity'],
            ['slug' => 'metalwork', 'name' => 'Metalwork'],
            ['slug' => 'woodwork', 'name' => 'Woodwork'],
            ['slug' => 'aviation-technology', 'name' => 'Aviation Technology'],
        ],
    ],
];
