<?php

namespace App\Services;

class VpCalculationService
{
    /**
     * Calculate Victory Points based on IMP difference and number of boards.
     * Uses the WBF Continuous VP Scale formula.
     *
     * @param int $homeImp
     * @param int $awayImp
     * @param int $boards
     * @return array [home_vp, away_vp]
     */
    public function calculateVp(int $homeImp, int $awayImp, int $boards): array
    {
        if ($boards <= 0) {
            return [0, 0];
        }

        $imps = $homeImp - $awayImp;
        $isNegative = $imps < 0;
        $absImps = abs($imps);

        $winVp = $this->calculateRawVp($absImps, $boards);
        $loseVp = round(20 - $winVp, 2);

        return $isNegative ? [$loseVp, $winVp] : [$winVp, $loseVp];
    }

    /**
     * The core exponential formula with official rounding:
     * 1. Calculate raw value
     * 2. Truncate to 3 decimal places
     * 3. Round to 2 decimal places
     */
    private function calculateRawVp(float $imps, int $boards): float
    {
        $tau = (sqrt(5) - 1) / 2; // Golden Ratio conjugate (~0.618)
        $r = pow($tau, 3);        // Base ratio (~0.236)
        $x = 15 * sqrt($boards);  // Blitz point
        
        if ($imps >= $x) {
            return 20.00;
        }
        
        if ($imps <= 0) {
            return 10.00;
        }
        
        $raw = 10 + 10 * ((1 - pow($r, $imps / $x)) / (1 - $r));
        
        // Truncate to 3 decimals
        $trunc = floor($raw * 1000 + 1e-9) / 1000;
        
        // Round to 2 decimals
        return round($trunc, 2);
    }
}
