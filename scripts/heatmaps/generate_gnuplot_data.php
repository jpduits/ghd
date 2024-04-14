<?php

const ANALYSABILITY_RANKING_INDEX = 77;
const CHANGEABILITY_RANKING_INDEX = 78;
const TESTABILITY_RANKING_INDEX = 79;
const PERIOD_START_DATE = 6;
const PERIOD_END_DATE = 7;
const OSS_CATEGORY = 13;
const FULL_NAME = 0;

// grids
const DELTA = 0.3;

$dimensions = [
    'Fluctuating' => [
        'x' => DELTA,
        'y' => 9.6 - DELTA,
        'minX' => DELTA,
        'maxX' => 4.8 - DELTA,
    ],
    'Attractive' => [
        'x' => 4.8 + DELTA,
        'y' => 9.6 - DELTA,
        'minX' => 4.8 + DELTA,
        'maxX' => 9.6 - DELTA,
    ],
    'Terminal' => [
        'x' => DELTA,
        'y' => 4.8 - DELTA,
        'minX' => DELTA,
        'maxX' => 4.8 - DELTA,
    ],
    'Stagnant' => [
        'x' => 4.8 + DELTA,
        'y' => 4.8 - DELTA,
        'minX' => 4.8 + DELTA,
        'maxX' => 9.6 - DELTA,
    ]

];

const AVAILABLE_OSS_CATEGORIES = ['Stagnant', 'Attractive', 'Fluctuating', 'Terminal'];

$csvFile = 'dump.csv';

$filteredData = [];

$selectedDate = '2019-01-01'; // refactor to argument + validations
$selectedRanking = constant('ANALYSABILITY_RANKING_INDEX');

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
            if ($data[PERIOD_START_DATE] == $selectedDate) {

                $ossCategory = $data[OSS_CATEGORY];
                $count[$ossCategory] = ($count[$ossCategory] ?? 0) + 1;


                $filteredData[$ossCategory][] = [
                    'x' => $dimensions[$ossCategory]['x'],
                    'y' => $dimensions[$ossCategory]['y'],
                    'x-delta' => DELTA,
                    'y-delta' => DELTA,
                    'ranking' => $data[$selectedRanking],
                    'full_name' => $data[FULL_NAME],
                    'oss_category' => $data[OSS_CATEGORY],
                    'count' => $count[$ossCategory]
                ];


                $dimensions[$ossCategory]['x'] += 2 * DELTA;
                if ($dimensions[$ossCategory]['x'] > $dimensions[$ossCategory]['maxX']) {
                    $dimensions[$ossCategory]['x'] = $dimensions[$ossCategory]['minX'];
                    $dimensions[$ossCategory]['y'] -= 2 * DELTA;
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

foreach (AVAILABLE_OSS_CATEGORIES as $ossCategory) {
    foreach ($filteredData[$ossCategory] as $record) {
        echo "{$record['x']} {$record['y']} {$record['x-delta']} {$record['y-delta']} {$record['ranking']} {$record['count']}\n";
    }
}
