<?php

$identity = [
    'first_name' => ['label' => 'First name', 'required' => true],
    'last_name' => ['label' => 'Last name', 'required' => true],
    'gender' => ['label' => 'Gender', 'required' => true, 'type' => 'select_from_array', 'options' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other']],
    'date_of_birth' => ['label' => 'Date of birth', 'required' => true, 'type' => 'date'],
];
$address = [
    'address_line_1' => ['label' => 'Home address'],
    'city' => ['label' => 'City'],
    'state' => ['label' => 'State / province'],
    'postal_code' => ['label' => 'Postal code'],
    'country' => ['label' => 'Country'],
    'nationality' => ['label' => 'Nationality'],
    'state_of_origin' => ['label' => 'State of origin'],
    'lga' => ['label' => 'Local government area'],
];

return [
    'parent' => [
        'title' => 'Parent / Guardian Details',
        'fields' => array_merge($identity, [
            'email' => ['label' => 'Email', 'required' => true, 'type' => 'email'],
            'alt_email' => ['label' => 'Alternative email', 'type' => 'email'],
            'phone' => ['label' => 'Phone', 'required' => true],
            'alt_phone' => ['label' => 'Alternative phone'],
            'Occupation' => ['label' => 'Occupation', 'required' => true],
        ], array_replace($address, [
            'city' => ['label' => 'City', 'required' => true],
            'state' => ['label' => 'State / province', 'required' => true],
            'nationality' => ['label' => 'Nationality', 'required' => true],
        ])),
    ],
    'student' => [
        'title' => 'Student Details',
        'fields' => array_merge($identity, $address),
    ],
    'pickup' => [
        'title' => 'Authorized Pickup / Drop-off Contacts',
        'fields' => [
            'first_name' => ['label' => 'First name', 'required' => true],
            'last_name' => ['label' => 'Last name', 'required' => true],
            'relationship' => ['label' => 'Relationship to child', 'required' => true],
            'email' => ['label' => 'Email', 'required' => true, 'type' => 'email'],
            'phone' => ['label' => 'Phone', 'required' => true],
            'address' => ['label' => 'Home address', 'required' => true],
        ],
    ],
];
