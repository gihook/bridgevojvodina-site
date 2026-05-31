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
        // Matches the suit letter (case-insensitive) when it follows a number (4S)
        // OR when it is at the start of a string followed by a rank (SK, S4, ST)
        $rendered = preg_replace("/(\d)" . $letter . "/i", "$1" . $symbol, $rendered);
        $rendered = preg_replace("/^" . $letter . "([0-9TJQKA])/i", $symbol . "$1", $rendered);
    }
@endphp

<span {!! $attributes->merge(['class' => 'font-bold font-mono']) !!}>
    {!! $rendered !!}
</span>
