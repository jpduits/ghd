<?php

const ANALYSABILITY_RANKING_INDEX = 77;
const CHANGEABILITY_RANKING_INDEX = 78;
const TESTABILITY_RANKING_INDEX = 79;
const OVERALL_RANKING_INDEX = 80;
const PERIOD_START_DATE = 6;
const PERIOD_END_DATE = 7;
const OSS_CATEGORY = 13;
const FULL_NAME = 0;

// grids
const DELTA = 0.3;

$dimensions = [
    '2019-01-01' => [
        'analysability' => [
            'x' => DELTA,
            'y' => 38.4 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA
        ],
        'changeability' => [
            'x' => 4.8 + DELTA,
            'y' => 38.4 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'testability' => [
            'x' => 9.6 + DELTA,
            'y' => 38.4 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'overall' => [
            'x' => 14.4 + DELTA,
            'y' => 38.4 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]
    ],
    '2019-07-02' => [
        'analysability' => [
            'x' => DELTA,
            'y' => 33.6 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA,
        ],
        'changeability' => [
            'x' => 4.8 + DELTA,
            'y' => 33.6 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'testability' => [
            'x' => 9.6 + DELTA,
            'y' => 33.6 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'overall' => [
            'x' => 14.4 + DELTA,
            'y' => 33.6 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]
    ],
    '2019-12-31' => [
        'analysability' => [
            'x' => DELTA,
            'y' => 28.8 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA,
        ],
        'changeability' => [
            'x' => 4.8 + DELTA,
            'y' => 28.8 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'testability' => [
            'x' => 9.6 + DELTA,
            'y' => 28.8 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'overall' => [
            'x' => 14.4 + DELTA,
            'y' => 28.8 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]
    ],
    '2020-06-30' => [
        'analysability' => [
            'x' => DELTA,
            'y' => 24 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA,
        ],
        'changeability' => [
            'x' => 4.8 + DELTA,
            'y' => 24 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'testability' => [
            'x' => 9.6 + DELTA,
            'y' => 24 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'overall' => [
            'x' => 14.4 + DELTA,
            'y' => 24 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]
    ],
    '2020-12-29' => [
        'analysability' => [
            'x' => DELTA,
            'y' => 19.2 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA,
        ],
        'changeability' => [
            'x' => 4.8 + DELTA,
            'y' => 19.2 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'testability' => [
            'x' => 9.6 + DELTA,
            'y' => 19.2 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'overall' => [
            'x' => 14.4 + DELTA,
            'y' => 19.2 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]

    ],
    '2021-06-29' => [
        'analysability' => [
            'x' => DELTA,
            'y' => 14.4 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA,
        ],
        'changeability' => [
            'x' => 4.8 + DELTA,
            'y' => 14.4 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'testability' => [
            'x' => 9.6 + DELTA,
            'y' => 14.4 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'overall' => [
            'x' => 14.4 + DELTA,
            'y' => 14.4 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]

    ],
    '2021-12-28' => [
        'analysability' => [
            'x' => DELTA,
            'y' => 9.6 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA,
        ],
        'changeability' => [
            'x' => 4.8 + DELTA,
            'y' => 9.6 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'testability' => [
            'x' => 9.6 + DELTA,
            'y' => 9.6 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'overall' => [
            'x' => 14.4 + DELTA,
            'y' => 9.6 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]

    ],
    '2022-06-28' => [
        'analysability' => [
            'x' => DELTA,
            'y' => 4.8 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA
        ],
        'changeability' => [
            'x' => 4.8 + DELTA,
            'y' => 4.8 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'testability' => [
            'x' => 9.6 + DELTA,
            'y' => 4.8 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'overall' => [
            'x' => 14.4 + DELTA,
            'y' => 4.8 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]
    ]
];

const AVAILABLE_OSS_CATEGORIES = ['Stagnant', 'Attractive', 'Fluctuating', 'Terminal'];

$csvFile = 'dump.csv';

$filteredData = [];

$selectedOssCategory = 'Stagnant'; // refactor to argument + validations


$selectedRankings = [
    ['column' => constant('ANALYSABILITY_RANKING_INDEX'), 'dimension' => 'analysability'],
    ['column' => constant('CHANGEABILITY_RANKING_INDEX'), 'dimension' => 'changeability'],
    ['column' => constant('TESTABILITY_RANKING_INDEX'), 'dimension' => 'testability'],
    ['column' => constant('OVERALL_RANKING_INDEX'), 'dimension' => 'overall']
];


$availableDates = array_keys($dimensions);

$count = [];

if (($handle = fopen($csvFile, 'r')) !== false) {

    $row = 1;
    $headers = [];
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {

        if ($row == 1) {
            $headers = $data;
        }
        else {

            // x, y, xdelta, ydelta, value
            if ($data[OSS_CATEGORY] == $selectedOssCategory) {

                $ossCategory = $data[OSS_CATEGORY];
                $startDate = $data[PERIOD_START_DATE];

                // we hebben het juist record, nu alle 4 de maintainability rankings parsen
                foreach ($selectedRankings as $selectedRankingData) {
                    $selectedRanking = $selectedRankingData['dimension'];
                    $selectedColumn = $selectedRankingData['column'];

                    $count[$startDate][$selectedRanking] = ($count[$startDate][$selectedRanking] ?? 0) + 1;


                    $filteredData[$startDate][$selectedRanking][] = [
                        'x' => $dimensions[$startDate][$selectedRanking]['x'],
                        'y' => $dimensions[$startDate][$selectedRanking]['y'],
                        'x-delta' => DELTA,
                        'y-delta' => DELTA,
                        'ranking' => $data[$selectedColumn],
                        'full_name' => $data[FULL_NAME],
                        'oss_category' => $data[OSS_CATEGORY],
                        'count' => $count[$startDate][$selectedRanking],
                        'start_date' => $startDate,
                        'end_date' => $data[PERIOD_END_DATE]
                    ];


                    $dimensions[$startDate][$selectedRanking]['x'] += 2 * DELTA;
                    if ((round($dimensions[$startDate][$selectedRanking]['x'], 2)) > (round($dimensions[$startDate][$selectedRanking]['maxX'], 2))) {
                        $dimensions[$startDate][$selectedRanking]['x'] = $dimensions[$startDate][$selectedRanking]['minX'];
                        $dimensions[$startDate][$selectedRanking]['y'] -= 2 * DELTA;
                    }

                }

            }

        }

        $row++;
    }

    fclose($handle);
}
else {
    echo 'Kan het bestand niet openen.';
}


foreach ($availableDates as $s) {

    foreach ($selectedRankings as $selectedRankingData) {

        $selectedRanking  = $selectedRankingData['dimension'];
        // hack for sorting rankings and keep coordinates for grid
        $rankings = array_column($filteredData[$s][$selectedRanking], 'ranking');
        rsort($rankings);


        foreach ($filteredData[$s][$selectedRanking] as $key => $record) {
            echo "{$record['x']} {$record['y']} {$record['x-delta']} {$record['y-delta']} {$rankings[$key]} {$record['count']} {$record['start_date']} {$record['end_date']}\n";
        }

    }
}
