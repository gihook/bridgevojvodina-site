@props(['contract'])

@php
    if (empty($contract)) {
        return;
    }

    $suitSymbols = [
        'S' => '<span class="text-gray-900">&spades;</span>',
        'H' => '<span class="text-red-600">&hearts;</span>',
        'D' => '<span class="text-orange-500">&diams;</span>',
        'C' => '<span class="text-green-700">&clubs;</span>',
    ];

    // Single pass replacement using callback to prevent recursive corruption (e.g. replacing 'D' in '&spades;')
    $rendered = preg_replace_callback("/(?:(10|[0-9TJQKA])([SHDC]))|(?:([SHDC])(10|[0-9TJQKA]))/i", function($matches) use ($suitSymbols) {
        // Case 1: Rank then Suit (e.g., 4S, AS, 10H)
        if (!empty($matches[2])) {
            $rank = $matches[1];
            $suit = strtoupper($matches[2]);
            return $rank . ($suitSymbols[$suit] ?? $suit);
        }
        // Case 2: Suit then Rank (e.g., S4, SA, H10)
        if (!empty($matches[3])) {
            $suit = strtoupper($matches[3]);
            $rank = $matches[4];
            return ($suitSymbols[$suit] ?? $suit) . $rank;
        }
        return $matches[0];
    }, $contract);
@endphp

<span {!! $attributes->merge(['class' => 'font-bold font-mono']) !!}>
    {!! $rendered !!}
</span>
