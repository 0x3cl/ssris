<?php

$officers = [
    'Richelle Jem Jobog',
    'Zailla Payag',
    'Evangeline Flor Manalang',
    'Merlita Odi',
    'Julius Leaño Jr., PhD',
];

$conformes = [
    'Ms. Richelle Jem R. Jobog',
    'Ms. Zailla F. Payag',
    'Ms. Evangeline Flor P. Manalang',
    'Ms. Merlita I. Odi',
    'Dr. Julius L. Leaño Jr., PhD',
];

return [
    'signatories' => [
        'prepared_by' => $officers,
        'noted_by' => $officers,
        'conforme_primary' => $conformes,
        'conforme_secondary' => $conformes,
        'conforme_optional' => $conformes,
    ],
];
