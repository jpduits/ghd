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
        'attractive' => [
            'x' => DELTA,
            'y' => 38.4 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA
        ],
        'fluctuating' => [
            'x' => 4.8 + DELTA,
            'y' => 38.4 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'stagnant' => [
            'x' => 9.6 + DELTA,
            'y' => 38.4 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'terminal' => [
            'x' => 14.4 + DELTA,
            'y' => 38.4 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]
    ],
    '2019-07-02' => [
        'attractive' => [
            'x' => DELTA,
            'y' => 33.6 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA,
        ],
        'fluctuating' => [
            'x' => 4.8 + DELTA,
            'y' => 33.6 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'stagnant' => [
            'x' => 9.6 + DELTA,
            'y' => 33.6 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'terminal' => [
            'x' => 14.4 + DELTA,
            'y' => 33.6 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]
    ],
    '2019-12-31' => [
        'attractive' => [
            'x' => DELTA,
            'y' => 28.8 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA,
        ],
        'fluctuating' => [
            'x' => 4.8 + DELTA,
            'y' => 28.8 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'stagnant' => [
            'x' => 9.6 + DELTA,
            'y' => 28.8 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'terminal' => [
            'x' => 14.4 + DELTA,
            'y' => 28.8 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]
    ],
    '2020-06-30' => [
        'attractive' => [
            'x' => DELTA,
            'y' => 24 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA,
        ],
        'fluctuating' => [
            'x' => 4.8 + DELTA,
            'y' => 24 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'stagnant' => [
            'x' => 9.6 + DELTA,
            'y' => 24 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'terminal' => [
            'x' => 14.4 + DELTA,
            'y' => 24 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]
    ],
    '2020-12-29' => [
        'attractive' => [
            'x' => DELTA,
            'y' => 19.2 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA,
        ],
        'fluctuating' => [
            'x' => 4.8 + DELTA,
            'y' => 19.2 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'stagnant' => [
            'x' => 9.6 + DELTA,
            'y' => 19.2 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'terminal' => [
            'x' => 14.4 + DELTA,
            'y' => 19.2 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]

    ],
    '2021-06-29' => [
        'attractive' => [
            'x' => DELTA,
            'y' => 14.4 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA,
        ],
        'fluctuating' => [
            'x' => 4.8 + DELTA,
            'y' => 14.4 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'stagnant' => [
            'x' => 9.6 + DELTA,
            'y' => 14.4 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'terminal' => [
            'x' => 14.4 + DELTA,
            'y' => 14.4 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]

    ],
    '2021-12-28' => [
        'attractive' => [
            'x' => DELTA,
            'y' => 9.6 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA,
        ],
        'fluctuating' => [
            'x' => 4.8 + DELTA,
            'y' => 9.6 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'stagnant' => [
            'x' => 9.6 + DELTA,
            'y' => 9.6 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'terminal' => [
            'x' => 14.4 + DELTA,
            'y' => 9.6 - DELTA,
            'minX' => 14.4 + DELTA,
            'maxX' => (4 * 4.8) - DELTA
        ]

    ],
    '2022-06-28' => [
        'attractive' => [
            'x' => DELTA,
            'y' => 4.8 - DELTA,
            'minX' => DELTA,
            'maxX' => 4.8 - DELTA
        ],
        'fluctuating' => [
            'x' => 4.8 + DELTA,
            'y' => 4.8 - DELTA,
            'minX' => 4.8 + DELTA,
            'maxX' => (2 * 4.8) - DELTA
        ],
        'stagnant' => [
            'x' => 9.6 + DELTA,
            'y' => 4.8 - DELTA,
            'minX' => 9.6 + DELTA,
            'maxX' => (3 * 4.8) - DELTA
        ],
        'terminal' => [
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

//$selectedOssCategory = 'Stagnant'; // refactor to argument + validations

$selectedColumn =  constant('TESTABILITY_RANKING_INDEX');


$selectedOssCategories = [
    'attractive',
     'fluctuating',
     'stagnant',
     'terminal'
];


/*$selectedRankings = [
    ['column' => constant('ANALYSABILITY_RANKING_INDEX'), 'dimension' => 'analysability'],
    ['column' => constant('CHANGEABILITY_RANKING_INDEX'), 'dimension' => 'changeability'],
    ['column' => constant('TESTABILITY_RANKING_INDEX'), 'dimension' => 'testability'],
    ['column' => constant('OVERALL_RANKING_INDEX'), 'dimension' => 'overall']
];*/

$availableDates = array_keys($dimensions);

$count = [];

if (($handle = fopen($csvFile, 'r')) !== false) {

    $row = 1;
    $headers = [];
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {

        if ($row == 1) {
            $headers = $data;
        }
        elseif (!empty($data[0])) {

            // x, y, xdelta, ydelta, value
        //    if ($data[OSS_CATEGORY] == $selectedOssCategory) {

                $ossCategory = $data[OSS_CATEGORY];
                $startDate = $data[PERIOD_START_DATE];

                    // we hebben het juist record, nu alle 4 de maintainability rankings parsen
                  //  foreach ($selectedOssCategories as $selectedOssCategoryData) {
                    $selectedOssCategory = strtolower($ossCategory);

/*                    $count[$startDate][$selectedOssCategory][$selectedColumn] = ($count[$startDate][$selectedOssCategory][$selectedColumn] ?? 0) + 1;


                    $count[$startDate][$selectedOssCategory]['total'] = ($count[$startDate][$selectedOssCategory]['total']  ?? 0) + 1;*/

            if (!isset($filteredData[$startDate][$selectedOssCategory])) {
                $filteredData[$startDate][$selectedOssCategory] = [
                    1=>0,2=>0,3=>0,4=>0,5=>0,
                    'total' => 0
                ];
            }

            $filteredData[$startDate][$selectedOssCategory] = [
              5 => ($data[$selectedColumn] == 5) ? ($filteredData[$startDate][$selectedOssCategory][5] + 1) : $filteredData[$startDate][$selectedOssCategory][5],
              4 => ($data[$selectedColumn] == 4) ? ($filteredData[$startDate][$selectedOssCategory][4] + 1) : $filteredData[$startDate][$selectedOssCategory][4],
              3 => ($data[$selectedColumn] == 3) ? ($filteredData[$startDate][$selectedOssCategory][3] + 1) : $filteredData[$startDate][$selectedOssCategory][3],
              2 => ($data[$selectedColumn] == 2) ? ($filteredData[$startDate][$selectedOssCategory][2] + 1) : $filteredData[$startDate][$selectedOssCategory][2],
              1 => ($data[$selectedColumn] == 1) ? ($filteredData[$startDate][$selectedOssCategory][1] + 1) : $filteredData[$startDate][$selectedOssCategory][1],
              'total' => ($filteredData[$startDate][$selectedOssCategory]['total']  ?? 0) + 1
            ];



            //$filteredData[$startDate][$selectedOssCategory]['total'] += ($filteredData[$startDate][$selectedOssCategory]['total']  ?? 0) + 1;





/*                    $filteredData[$startDate][$selectedOssCategory][] = [
                        'x' => $dimensions[$startDate][$selectedOssCategory]['x'],
                        'y' => $dimensions[$startDate][$selectedOssCategory]['y'],
                        'x-delta' => DELTA,
                        'y-delta' => DELTA,
                        'ranking' => $data[$selectedColumn],
                        'full_name' => $data[FULL_NAME],
                        'oss_category' => $data[OSS_CATEGORY],
                        'count' => $count[$startDate][$selectedOssCategory][$selectedColumn],
                        'start_date' => $startDate,
                        'end_date' => $data[PERIOD_END_DATE]
                    ];*/

/*
                    $dimensions[$startDate][$selectedOssCategory]['x'] += 2 * DELTA;
                    if ((round($dimensions[$startDate][$selectedOssCategory]['x'], 2)) > (round($dimensions[$startDate][$selectedOssCategory]['maxX'], 2))) {
                        $dimensions[$startDate][$selectedOssCategory]['x'] = $dimensions[$startDate][$selectedOssCategory]['minX'];
                        $dimensions[$startDate][$selectedOssCategory]['y'] -= 2 * DELTA;
                    }*/

//                }

          //  }





        }

        $row++;
    }

    fclose($handle);
}
else {
    echo 'Kan het bestand niet openen.';
}


$blocksAvailable = 64;
// nu omrekenen naar percentages?

foreach ($filteredData as $date => $values) {

    foreach ($values as $ossCategory => $rankings) {

        foreach ($rankings as $ranking => $values) {

            if ($ranking !== 'total') {
                $percents = round((($filteredData[$date][$ossCategory][$ranking] ?? 0) / $filteredData[$date][$ossCategory]['total']) * 100);
                $filteredData[$date][$ossCategory][$ranking . '_percents'] =  $percents;
                $filteredData[$date][$ossCategory][$ranking . '_blocks'] =  round(($blocksAvailable / 100) * $percents);
            }

        }

    }
    }


// blokjes echo
$blocks_columns = [5 => '5_blocks', 4 =>'4_blocks', 3 => '3_blocks', 2=> '2_blocks', 1 => '1_blocks'];
$blocks_percents = [5 => '5_percents', 4 =>'4_percents', 3 => '3_percents', 2=> '2_percents', 1 => '1_percents'];

foreach ($availableDates as $s => $startDate) {

    foreach ($selectedOssCategories as $selectedOssCategory ) {

        foreach ($blocks_columns as $r => $blockColumn) {



            for ($i = 0; $i < $filteredData[$startDate][$selectedOssCategory][$blockColumn]; $i++) {

                $x = $dimensions[$startDate][$selectedOssCategory]['x'];
                $y = $dimensions[$startDate][$selectedOssCategory]['y'];
                $percentage = '';
                if ($i == 0) {
                    $percentage = $filteredData[$startDate][$selectedOssCategory][$blocks_percents[$r]].'%';
                }
                echo "{$x} {$y} ".DELTA." ".DELTA." {$r} \"$percentage\"\n";

                $dimensions[$startDate][$selectedOssCategory]['x'] += 2 * DELTA;
                if ((round($dimensions[$startDate][$selectedOssCategory]['x'], 2)) > (round($dimensions[$startDate][$selectedOssCategory]['maxX'], 2))) {
                    $dimensions[$startDate][$selectedOssCategory]['x'] = $dimensions[$startDate][$selectedOssCategory]['minX'];
                    $dimensions[$startDate][$selectedOssCategory]['y'] -= 2 * DELTA;
                }


            }


        }
    }

//print_r($filteredData);

/*        $selectedOssCategory = $selectedOssCategoryData['dimension'];
        // hack for sorting rankings and keep coordinates for grid
        $rankings = array_column($filteredData[$s][$selectedOssCategory], 'ranking');
        rsort($rankings);*/


      //  foreach ($filteredData[$s][$selectedOssCategory] as $key => $record) {
            //echo "{$record['x']} {$record['y']} {$record['x-delta']} {$record['y-delta']} {$rankings[$key]} {$record['count']} {$record['start_date']} {$record['end_date']}\n";
     //   }

    //}
}
