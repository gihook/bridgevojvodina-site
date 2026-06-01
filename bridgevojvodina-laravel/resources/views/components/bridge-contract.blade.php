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

    $rendered = $contract;
    foreach ($suitSymbols as $letter => $symbol) {
        // Matches the suit letter (case-insensitive) when it follows a number or rank (4S, KS, AS)
        $rendered = preg_replace("/([0-9TJQKA])" . $letter . "/i", "$1" . $symbol, $rendered);
        // Matches the suit letter (case-insensitive) when it precedes a number or rank (S4, SK, SA)
        $rendered = preg_replace("/" . $letter . "([0-9TJQKA])/i", $symbol . "$1", $rendered);
    }
@endphp

<span {!! $attributes->merge(['class' => 'font-bold font-mono']) !!}>
    {!! $rendered !!}
</span>
