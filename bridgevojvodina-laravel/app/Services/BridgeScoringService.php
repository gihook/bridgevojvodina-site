<?php

namespace App\Services;

class BridgeScoringService
{
    /**
     * Calculate bridge score for a contract.
     * Returns the score for the DECLARER'S side (positive if made, negative if down).
     */
    public function calculateScore(int $level, string $suit, int $risk, int $tricks, bool $isVul): int
    {
        if ($level === 0) return 0; // Pass

        $tricksMade = $tricks - 6;
        
        if ($tricksMade < $level) {
            return - $this->calculateUndertrickPenalty($level - $tricksMade, $risk, $isVul);
        }

        $contractPoints = $this->calculateContractPoints($level, $suit, $risk);
        $overtrickPoints = $this->calculateOvertrickPoints($tricksMade - $level, $suit, $risk, $isVul);
        
        $gameBonus = ($contractPoints >= 100) ? ($isVul ? 500 : 300) : 50;
        
        $slamBonus = 0;
        if ($level === 6) $slamBonus = $isVul ? 750 : 500;
        if ($level === 7) $slamBonus = $isVul ? 1500 : 1000;
        
        $insultBonus = 0;
        if ($risk === 2) $insultBonus = 50;
        if ($risk === 4) $insultBonus = 100;

        return $contractPoints + $overtrickPoints + $gameBonus + $slamBonus + $insultBonus;
    }

    private function calculateContractPoints(int $level, string $suit, int $risk): int
    {
        $pts = 0;
        if ($suit === 'C' || $suit === 'D') {
            $pts = 20 * $level;
        } elseif ($suit === 'H' || $suit === 'S') {
            $pts = 30 * $level;
        } elseif ($suit === 'NT') {
            $pts = 40 + 30 * ($level - 1);
        }
        return $pts * $risk;
    }

    private function calculateOvertrickPoints(int $overtricks, string $suit, int $risk, bool $isVul): int
    {
        if ($overtricks <= 0) return 0;

        if ($risk === 1) {
            return ($suit === 'C' || $suit === 'D') ? 20 * $overtricks : 30 * $overtricks;
        }

        $val = $isVul ? 200 : 100;
        if ($risk === 4) $val *= 2;
        
        return $val * $overtricks;
    }

    private function calculateUndertrickPenalty(int $down, int $risk, bool $isVul): int
    {
        if ($risk === 1) {
            return $isVul ? 100 * $down : 50 * $down;
        }

        $penalty = 0;
        if ($isVul) {
            // Vul Doubled: 200, 500, 800, 1100...
            $penalty = 200 + ($down - 1) * 300;
        } else {
            // Non-vul Doubled: 100, 300, 500, 800, 1100...
            if ($down === 1) $penalty = 100;
            elseif ($down === 2) $penalty = 300;
            elseif ($down === 3) $penalty = 500;
            else $penalty = 500 + ($down - 3) * 300;
        }

        return ($risk === 4) ? $penalty * 2 : $penalty;
    }

    /**
     * Parses a contract string like "4S", "4SX", "4SXX" into [level, suit, risk]
     */
    public function parseContract(string $contract): array
    {
        if (strtoupper($contract) === 'PASS') return [0, '', 1];
        
        preg_match('/^([1-7])(S|H|D|C|NT)(X{0,2})$/i', $contract, $matches);
        if (!$matches) return [0, '', 1];
        
        $level = (int) $matches[1];
        $suit = strtoupper($matches[2]);
        $risk = 1;
        if (strtoupper($matches[3]) === 'X') $risk = 2;
        if (strtoupper($matches[3]) === 'XX') $risk = 4;
        
        return [$level, $suit, $risk];
    }
}
