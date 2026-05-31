@props(['hand'])

@php
    $suits = [
        'S' => ['symbol' => '&spades;', 'color' => 'text-gray-900'],
        'H' => ['symbol' => '&hearts;', 'color' => 'text-red-600'],
        'D' => ['symbol' => '&diams;', 'color' => 'text-orange-500'],
        'C' => ['symbol' => '&clubs;', 'color' => 'text-green-700'],
    ];

    $hcp = 0;
    $values = ['A' => 4, 'K' => 3, 'Q' => 2, 'J' => 1];
    foreach ($hand as $cards) {
        if (is_string($cards)) {
            foreach (str_split($cards) as $char) {
                $hcp += $values[strtoupper($char)] ?? 0;
            }
        }
    }
@endphp

<div {{ $attributes->merge(['class' => 'text-sm font-bold font-mono space-y-0.5 min-w-[100px]']) }}>
    @foreach ($suits as $letter => $data)
        <div class="flex gap-2 items-center">
            <span class="{{ $data['color'] }} w-4 text-center text-base leading-none">{!! $data['symbol'] !!}</span>
            <span class="tracking-widest">{{ $hand[$letter] ?? '-' }}</span>
        </div>
    @endforeach
    <div class="text-[10px] text-gray-400 mt-2 border-t pt-1 text-right italic">
        HCP: {{ $hcp }}
    </div>
</div>
