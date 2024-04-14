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
        'x' => DELTA,
        'y' => 38.4 - DELTA,
        'minX' => DELTA,
        'maxX' => (4 * 4.8) - DELTA
    ],
    '2019-07-02' => [
        'x' => DELTA,
        'y' => 33.6 - DELTA,
        'minX' => DELTA,
        'maxX' => (4 * 4.8) - DELTA
    ],
    '2019-12-31' => [
        'x' => DELTA,
        'y' => 28.8 - DELTA,
        'minX' => DELTA,
        'maxX' => (4 * 4.8) - DELTA
    ],
    '2020-06-30' => [
        'x' => DELTA,
        'y' => 24 - DELTA,
        'minX' => DELTA,
        'maxX' => (4 * 4.8) - DELTA
    ],
    '2020-12-29' => [
        'x' => DELTA,
        'y' => 19.2 - DELTA,
        'minX' => DELTA,
        'maxX' => (4 * 4.8) - DELTA
    ],
    '2021-06-29' => [
        'x' => DELTA,
        'y' => 14.4 - DELTA,
        'minX' => DELTA,
        'maxX' => (4 * 4.8) - DELTA
    ],
    '2021-12-28' => [
        'x' => DELTA,
        'y' => 9.6 - DELTA,
        'minX' => DELTA,
        'maxX' => (4 * 4.8) - DELTA
    ],
    '2022-06-28' => [
        'x' => DELTA,
        'y' => 4.8 - DELTA,
        'minX' => DELTA,
        'maxX' => (4 * 4.8) - DELTA
    ]
];

const AVAILABLE_OSS_CATEGORIES = ['Stagnant', 'Attractive', 'Fluctuating', 'Terminal'];

$csvFile = 'dump.csv';

$filteredData = [];

//$selectedOssCategory = 'Stagnant'; // refactor to argument + validations

$selectedColumn =  constant('OVERALL_RANKING_INDEX');


$selectedOssCategories = [
    ['dimension' => 'attractive'],
    ['dimension' => 'fluctuating'],
    ['dimension' => 'stagnant'],
    ['dimension' => 'terminal']
];


$availableDates = array_keys($dimensions);

$count = [];

$rankings = [5,4,3,2,1];

foreach($rankings as $ranking) {

    if (($handle = fopen($csvFile, 'r')) !== false) {

        $row = 1;
        $headers = [];
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {

            if ($row == 1) {
                $headers = $data;
            }
            elseif (!empty($data[0])) {

                // x, y, xdelta, ydelta, value
                if ($data[$selectedColumn] == $ranking) {

                    $startDate = $data[PERIOD_START_DATE];


                    $count[$startDate] = ($count[$startDate] ?? 0) + 1;

                    $filteredData[$startDate][] = [
                        'x' => $dimensions[$startDate]['x'],
                        'y' => $dimensions[$startDate]['y'],
                        'x-delta' => DELTA,
                        'y-delta' => DELTA,
                        'ranking' => $data[$selectedColumn],
                        'full_name' => $data[FULL_NAME],
                        'oss_category' => $data[OSS_CATEGORY],
                        'count' => $count[$startDate],
                        'start_date' => $startDate,
                        'end_date' => $data[PERIOD_END_DATE]
                    ];


                    $dimensions[$startDate]['x'] += 2 * DELTA;
                    if ((round($dimensions[$startDate]['x'], 2)) > (round($dimensions[$startDate]['maxX'], 2))) {
                        $dimensions[$startDate]['x'] = $dimensions[$startDate]['minX'];
                        $dimensions[$startDate]['y'] -= 2 * DELTA;
                    }

                }

                //  }

            }

            $row++;
        }

        fclose($handle);
    }
    else {
        echo 'Kan het bestand niet openen.';
    }
} // foreach ranking



foreach ($availableDates as $s) {

    foreach ($filteredData[$s] as $key => $record) {
        echo "{$record['x']} {$record['y']} {$record['x-delta']} {$record['y-delta']} {$record['ranking']} {$record['count']} {$record['start_date']} {$record['end_date']}\n";
    }

}
