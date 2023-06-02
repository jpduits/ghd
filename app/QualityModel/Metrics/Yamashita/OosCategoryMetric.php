<?php

namespace App\QualityModel\Metrics\Yamashita;

use Carbon\Carbon;
use App\Models\Repository;
use App\QualityModel\Metrics\BaseMetric;
use Symfony\Component\Console\Output\Output;

class OosCategoryMetric extends BaseMetric
{
    const STICKY_THRESHOLD = 0.23;
    const MAGNET_THRESHOLD = 0.005;
    private StickyMetric $stickyMetric;
    private MagnetMetric $magnetMetric;

    public function __construct(Output $output, StickyMetric $stickyMetric, MagnetMetric $magnetMetric)
    {
        parent::__construct($output);
        $this->stickyMetric = $stickyMetric;
        $this->magnetMetric = $magnetMetric;
    }

    public function calculate(Repository $repository, Carbon $startDate, int $periodInterval = null) : array
    {

        $sticky = $this->stickyMetric->calculate($repository, $startDate, $periodInterval);
        $magnet = $this->magnetMetric->calculate($repository, $startDate, $periodInterval);
        $quadrant = $this->getQuadrant($sticky['sticky_value'], $magnet['magnet_value']);
        return array_merge($sticky, $magnet, ['quadrant' => $quadrant]);

    }

    private function getQuadrant(float $sticky, float $magnet) : string
    {

        if ($sticky >= self::STICKY_THRESHOLD && $magnet >= self::MAGNET_THRESHOLD) {
            return 'Attractive';
        }
        elseif ($sticky >= self::STICKY_THRESHOLD && $magnet < self::MAGNET_THRESHOLD) {
            return 'Stagnant';
        }
        elseif ($sticky < self::STICKY_THRESHOLD && $magnet >= self::MAGNET_THRESHOLD) {
            return 'Fluctuating';
        }
        elseif ($sticky < self::STICKY_THRESHOLD && $magnet < self::MAGNET_THRESHOLD) {
            return 'Terminal';
        }
        else {
            return 'Unknown';
        }

    }

}
