@php
    $boardsCount = $round->boards_per_round ?? $results->boards_per_round ?? 16;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Enter Results') }}: {{ $homeTeam->name }} vs {{ $awayTeam->name }}
            </h2>
            <a href="{{ route('tournaments.edit', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Tournament') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12" x-data="{
        homeImp: {{ $match->home_imp }},
        awayImp: {{ $match->away_imp }},
        homeVp: {{ $match->home_vp }},
        awayVp: {{ $match->away_vp }},
        boards: {{ json_encode(array_map(function($b) {
            $parsed = (new \App\Services\BridgeScoringService())->parseContract($b->home_contract ?? '');
            $data = $b->toArray();
            $data['home_contract_level'] = $parsed[0];
            $data['home_contract_suit'] = $parsed[1];
            $data['home_contract_risk'] = $parsed[2] ?: 1;
            $data['home_contract_base'] = $parsed[0] === 0 ? '0' : $parsed[0] . $parsed[1];
            
            $parsedAway = (new \App\Services\BridgeScoringService())->parseContract($b->away_contract ?? '');
            $data['away_contract_level'] = $parsedAway[0];
            $data['away_contract_suit'] = $parsedAway[1];
            $data['away_contract_risk'] = $parsedAway[2] ?: 1;
            $data['away_contract_base'] = $parsedAway[0] === 0 ? '0' : $parsedAway[0] . $parsedAway[1];
            return $data;
        }, $match->boards)) }},
        boardsCount: {{ $boardsCount }},
        
        editingBoard: null,
        boardModalOpen: false,

        scoreToImp(score) {
            let absScore = Math.abs(score);
            let sign = Math.sign(score);
            let impScale = [20, 50, 90, 130, 170, 220, 270, 320, 370, 430, 500, 600, 750, 900, 1100, 1300, 1500, 1750, 2000, 2250, 2500, 3000, 3500, 4000];
            let imps = 0;
            for (let threshold of impScale) {
                if (absScore >= threshold) imps++;
                else break;
            }
            return imps * sign;
        },

        getVulnerability(boardNumber) {
            const vulns = ['None', 'NS', 'EW', 'All', 'NS', 'EW', 'All', 'None', 'EW', 'All', 'None', 'NS', 'All', 'None', 'NS', 'EW'];
            return vulns[(boardNumber - 1) % 16];
        },

        calculateBridgeScore(level, suit, risk, tricks, decl, boardNumber) {
            if (level === 0) return 0;
            if (!suit || !decl || tricks === null || tricks === '') return null;

            let tricksMade = tricks - 6;
            let vuln = this.getVulnerability(boardNumber);
            let isVul = (vuln === 'All' || vuln === ((decl === 'N' || decl === 'S') ? 'NS' : 'EW'));

            let score = 0;
            if (tricksMade < level) {
                score = -this.calculateUndertrickPenalty(level - tricksMade, risk, isVul);
            } else {
                let cp = this.calculateContractPoints(level, suit, risk);
                let op = this.calculateOvertrickPoints(tricksMade - level, suit, risk, isVul);
                let gb = (cp >= 100) ? (isVul ? 500 : 300) : 50;
                let sb = (level === 6) ? (isVul ? 750 : 500) : (level === 7 ? (isVul ? 1500 : 1000) : 0);
                let ib = (risk === 2) ? 50 : (risk === 4 ? 100 : 0);
                score = cp + op + gb + sb + ib;
            }

            return (decl === 'N' || decl === 'S') ? score : -score;
        },

        calculateContractPoints(level, suit, risk) {
            let pts = (suit === 'C' || suit === 'D') ? 20 * level : ((suit === 'H' || suit === 'S') ? 30 * level : 40 + 30 * (level - 1));
            return pts * risk;
        },

        calculateOvertrickPoints(overtricks, suit, risk, isVul) {
            if (overtricks <= 0) return 0;
            if (risk === 1) return (suit === 'C' || suit === 'D') ? 20 * overtricks : 30 * overtricks;
            let val = (isVul ? 200 : 100) * (risk === 4 ? 2 : 1);
            return val * overtricks;
        },

        calculateUndertrickPenalty(down, risk, isVul) {
            if (risk === 1) return (isVul ? 100 : 50) * down;
            let p = 0;
            if (isVul) p = 200 + (down - 1) * 300;
            else {
                if (down === 1) p = 100;
                else if (down === 2) p = 300;
                else if (down === 3) p = 500;
                else p = 500 + (down - 3) * 300;
            }
            return (risk === 4) ? p * 2 : p;
        },

        calculate() {
            let totalHomeImp = 0;
            let totalAwayImp = 0;

            this.boards.forEach(b => {
                if (b.home_score !== null && b.away_score !== null && b.home_score !== '' && b.away_score !== '') {
                    let diff = b.home_score - b.away_score;
                    let imp = this.scoreToImp(diff);
                    b.home_imp = imp > 0 ? imp : 0;
                    b.away_imp = imp < 0 ? Math.abs(imp) : 0;
                } else {
                    b.home_imp = 0;
                    b.away_imp = 0;
                }
                totalHomeImp += b.home_imp;
                totalAwayImp += b.away_imp;
            });

            this.homeImp = totalHomeImp;
            this.awayImp = totalAwayImp;

            // Calculate VPs
            let imps = this.homeImp - this.awayImp;
            let absImps = Math.abs(imps);
            let x = 15 * Math.sqrt(this.boardsCount);
            
            if (absImps >= x) {
                this.homeVp = imps >= 0 ? 20.00 : 0.00;
                this.awayVp = imps >= 0 ? 0.00 : 20.00;
            } else if (absImps === 0) {
                this.homeVp = 10.00;
                this.awayVp = 10.00;
            } else {
                let tau = (Math.sqrt(5) - 1) / 2;
                let r = Math.pow(tau, 3);
                let raw = 10 + 10 * ((1 - Math.pow(r, absImps / x)) / (1 - r));
                let winVp = Math.round(Math.floor(raw * 1000 + 1e-9) / 10) / 100;
                let loseVp = Math.round((20 - winVp) * 100) / 100;

                if (imps > 0) {
                    this.homeVp = winVp.toFixed(2);
                    this.awayVp = loseVp.toFixed(2);
                } else {
                    this.homeVp = loseVp.toFixed(2);
                    this.awayVp = winVp.toFixed(2);
                }
            }
        },

        openBoardEditor(board) {
            this.editingBoard = JSON.parse(JSON.stringify(board));
            this.boardModalOpen = true;
        },

        saveBoard() {
            let idx = this.boards.findIndex(b => b.board_number === this.editingBoard.board_number);
            if (idx !== -1) {
                this.boards[idx] = this.editingBoard;
                this.calculate();
            }
            this.boardModalOpen = false;
        },

        updateBoardScores() {
            if (!this.editingBoard) return;

            // Parse home contract base
            if (this.editingBoard.home_contract_base === '0') {
                this.editingBoard.home_contract_level = 0;
                this.editingBoard.home_contract_suit = '';
                this.editingBoard.home_contract = 'Pass';
            } else {
                let m = this.editingBoard.home_contract_base.match(/^([1-7])(C|D|H|S|NT)$/);
                if (m) {
                    this.editingBoard.home_contract_level = parseInt(m[1]);
                    this.editingBoard.home_contract_suit = m[2];
                    
                    let riskStr = this.editingBoard.home_contract_risk === 2 ? 'X' : (this.editingBoard.home_contract_risk === 4 ? 'XX' : '');
                    this.editingBoard.home_contract = m[0] + riskStr;
                }
            }
            
            this.editingBoard.home_score = this.calculateBridgeScore(
                this.editingBoard.home_contract_level,
                this.editingBoard.home_contract_suit,
                this.editingBoard.home_contract_risk,
                this.editingBoard.home_tricks,
                this.editingBoard.home_declarer,
                this.editingBoard.board_number
            );

            // Parse away contract base
            if (this.editingBoard.away_contract_base === '0') {
                this.editingBoard.away_contract_level = 0;
                this.editingBoard.away_contract_suit = '';
                this.editingBoard.away_contract = 'Pass';
            } else {
                let m = this.editingBoard.away_contract_base.match(/^([1-7])(C|D|H|S|NT)$/);
                if (m) {
                    this.editingBoard.away_contract_level = parseInt(m[1]);
                    this.editingBoard.away_contract_suit = m[2];
                    
                    let riskStr = this.editingBoard.away_contract_risk === 2 ? 'X' : (this.editingBoard.away_contract_risk === 4 ? 'XX' : '');
                    this.editingBoard.away_contract = m[0] + riskStr;
                }
            }

            this.editingBoard.away_score = this.calculateBridgeScore(
                this.editingBoard.away_contract_level,
                this.editingBoard.away_contract_suit,
                this.editingBoard.away_contract_risk,
                this.editingBoard.away_tricks,
                this.editingBoard.away_declarer,
                this.editingBoard.board_number
            );
        },

        getTrickOptions(level) {
            if (level === 0 || level === null) return [];
            let options = [];
            let required = 6 + parseInt(level);
            // Sort from most overtricks to most undertricks for better UX
            for (let t = 13; t >= 0; t--) {
                let diff = t - required;
                let label = '';
                if (diff === 0) label = '=';
                else if (diff > 0) label = '+' + diff;
                else label = diff.toString();
                
                options.push({ value: t, label: label + ' (' + t + ')' });
            }
            return options;
        },

        formatTricks(level, tricks) {
            if (level === 0 || level === null || tricks === null || tricks === '') return '-';
            let required = 6 + parseInt(level);
            let diff = tricks - required;
            if (diff === 0) return '=';
            return (diff > 0 ? '+' : '') + diff;
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Hidden CSV Upload Forms -->
            <form id="csvFormOpen" method="POST" action="{{ route('tournaments.match.room.boards.csv.upload', [$tournament, $round->id, ($match->id ?: $match->home_team_id), 'open']) }}" enctype="multipart/form-data" class="hidden">
                @csrf
                <input type="file" id="csvInputOpen" name="csv_file" accept=".csv" onchange="document.getElementById('csvFormOpen').submit()">
            </form>
            <form id="csvFormClosed" method="POST" action="{{ route('tournaments.match.room.boards.csv.upload', [$tournament, $round->id, ($match->id ?: $match->home_team_id), 'closed']) }}" enctype="multipart/form-data" class="hidden">
                @csrf
                <input type="file" id="csvInputClosed" name="csv_file" accept=".csv" onchange="document.getElementById('csvFormClosed').submit()">
            </form>

            <form method="POST" action="{{ route('tournaments.match.update', ['tournament' => $tournament, 'round' => $round->id, 'match' => ($match->id ?: $match->home_team_id)]) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="boards_json" :value="JSON.stringify(boards)">

                <!-- Match Score (TOP) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8 border-b-4 border-indigo-500">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold uppercase tracking-widest text-gray-500">{{ __('Match Score') }}</h3>
                            <div class="flex items-center gap-2 text-xs font-bold text-gray-400">
                                <span>{{ $boardsCount }} {{ __('Boards') }}</span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                            <div class="space-y-6">
                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <x-input-label for="home_imp" :value="__('Home IMPs')" />
                                        <x-text-input id="home_imp" name="home_imp" type="number" class="mt-1 block w-full text-2xl text-center font-bold bg-gray-50" x-model.number="homeImp" readonly required />
                                    </div>
                                    <div class="text-3xl font-black pt-6 text-gray-300">:</div>
                                    <div class="flex-1">
                                        <x-input-label for="away_imp" :value="__('Away IMPs')" />
                                        <x-text-input id="away_imp" name="away_imp" type="number" class="mt-1 block w-full text-2xl text-center font-bold bg-gray-50" x-model.number="awayImp" readonly required />
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-6 bg-indigo-50 p-6 rounded-xl border border-indigo-100">
                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <x-input-label for="home_vp" :value="__('Home VP')" />
                                        <x-text-input id="home_vp" name="home_vp" type="number" step="0.01" class="mt-1 block w-full text-xl text-center text-indigo-700 font-black bg-white" x-model="homeVp" readonly required />
                                    </div>
                                    <div class="text-2xl font-bold pt-6 text-indigo-300">-</div>
                                    <div class="flex-1">
                                        <x-input-label for="away_vp" :value="__('Away VP')" />
                                        <x-text-input id="away_vp" name="away_vp" type="number" step="0.01" class="mt-1 block w-full text-xl text-center text-indigo-700 font-black bg-white" x-model="awayVp" readonly required />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seating / Lineups -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12">
                    <!-- Open Room Table -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-blue-500">
                        <div class="flex justify-between items-center mb-6 border-b pb-1">
                            <div class="font-bold uppercase text-blue-800 text-sm tracking-widest">{{ __('Open Room') }}</div>
                            <x-secondary-button type="button" onclick="document.getElementById('csvInputOpen').click()" class="!py-0.5 !text-[9px]">
                                {{ __('CSV') }}
                            </x-secondary-button>
                        </div>
                        
                        <div class="flex flex-col items-center gap-4">
                            <!-- North (Home) -->
                            <div class="w-full max-w-[200px]">
                                <x-input-label class="text-center text-[10px] text-blue-600 font-black mb-1" value="N ({{ $homeTeam->name }})" />
                                <select name="open_n_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                    <option value="">-</option>
                                    @foreach($homePlayers as $player)
                                        <option value="{{ $player->id }}" {{ ($match->open_ns_ids[0] ?? null) == $player->id ? 'selected' : '' }}>
                                            {{ $player->last_name }} {{ $player->first_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- East/West (Away) -->
                            <div class="flex justify-between w-full gap-4">
                                <div class="flex-1">
                                    <x-input-label class="text-center text-[10px] text-red-600 font-black mb-1" value="W ({{ $awayTeam->name }})" />
                                    <select name="open_w_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                        <option value="">-</option>
                                        @foreach($awayPlayers as $player)
                                            <option value="{{ $player->id }}" {{ ($match->open_ew_ids[1] ?? null) == $player->id ? 'selected' : '' }}>
                                                {{ $player->last_name }} {{ $player->first_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <x-input-label class="text-center text-[10px] text-red-600 font-black mb-1" value="E ({{ $awayTeam->name }})" />
                                    <select name="open_e_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                        <option value="">-</option>
                                        @foreach($awayPlayers as $player)
                                            <option value="{{ $player->id }}" {{ ($match->open_ew_ids[0] ?? null) == $player->id ? 'selected' : '' }}>
                                                {{ $player->last_name }} {{ $player->first_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- South (Home) -->
                            <div class="w-full max-w-[200px]">
                                <x-input-label class="text-center text-[10px] text-blue-600 font-black mb-1" value="S ({{ $homeTeam->name }})" />
                                <select name="open_s_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                    <option value="">-</option>
                                    @foreach($homePlayers as $player)
                                        <option value="{{ $player->id }}" {{ ($match->open_ns_ids[1] ?? null) == $player->id ? 'selected' : '' }}>
                                            {{ $player->last_name }} {{ $player->first_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Closed Room Table -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-red-500">
                        <div class="flex justify-between items-center mb-6 border-b pb-1">
                            <div class="font-bold uppercase text-red-800 text-sm tracking-widest">{{ __('Closed Room') }}</div>
                            <x-secondary-button type="button" onclick="document.getElementById('csvInputClosed').click()" class="!py-0.5 !text-[9px]">
                                {{ __('CSV') }}
                            </x-secondary-button>
                        </div>
                        
                        <div class="flex flex-col items-center gap-4">
                            <!-- North (Away) -->
                            <div class="w-full max-w-[200px]">
                                <x-input-label class="text-center text-[10px] text-red-600 font-black mb-1" value="N ({{ $awayTeam->name }})" />
                                <select name="closed_n_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                    <option value="">-</option>
                                    @foreach($awayPlayers as $player)
                                        <option value="{{ $player->id }}" {{ ($match->closed_ns_ids[0] ?? null) == $player->id ? 'selected' : '' }}>
                                            {{ $player->last_name }} {{ $player->first_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- East/West (Home) -->
                            <div class="flex justify-between w-full gap-4">
                                <div class="flex-1">
                                    <x-input-label class="text-center text-[10px] text-blue-600 font-black mb-1" value="W ({{ $homeTeam->name }})" />
                                    <select name="closed_w_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                        <option value="">-</option>
                                        @foreach($homePlayers as $player)
                                            <option value="{{ $player->id }}" {{ ($match->closed_ew_ids[1] ?? null) == $player->id ? 'selected' : '' }}>
                                                {{ $player->last_name }} {{ $player->first_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <x-input-label class="text-center text-[10px] text-blue-600 font-black mb-1" value="E ({{ $homeTeam->name }})" />
                                    <select name="closed_e_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                        <option value="">-</option>
                                        @foreach($homePlayers as $player)
                                            <option value="{{ $player->id }}" {{ ($match->closed_ew_ids[0] ?? null) == $player->id ? 'selected' : '' }}>
                                                {{ $player->last_name }} {{ $player->first_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- South (Away) -->
                            <div class="w-full max-w-[200px]">
                                <x-input-label class="text-center text-[10px] text-red-600 font-black mb-1" value="S ({{ $awayTeam->name }})" />
                                <select name="closed_s_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs">
                                    <option value="">-</option>
                                    @foreach($awayPlayers as $player)
                                        <option value="{{ $player->id }}" {{ ($match->closed_ns_ids[1] ?? null) == $player->id ? 'selected' : '' }}>
                                            {{ $player->last_name }} {{ $player->first_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Board Results Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-6 border-b pb-2 uppercase tracking-widest text-gray-500">{{ __('Detailed Board Results') }}</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-center border-collapse">
                                <thead class="bg-gray-50 font-bold text-gray-700">
                                    <tr>
                                        <th class="py-3 border px-2" rowspan="2">#</th>
                                        <th class="py-3 border bg-blue-50 text-blue-800" colspan="5">{{ __('Open Room') }}</th>
                                        <th class="py-3 border bg-red-50 text-red-800" colspan="5">{{ __('Closed Room') }}</th>
                                        <th class="py-3 border" colspan="2">{{ __('IMPs') }}</th>
                                    </tr>
                                    <tr>
                                        <th class="py-2 border bg-blue-50 text-[10px] uppercase">{{ __('Contr.') }}</th>
                                        <th class="py-2 border bg-blue-50 text-[10px] uppercase">{{ __('Decl.') }}</th>
                                        <th class="py-2 border bg-blue-50 text-[10px] uppercase">{{ __('Lead') }}</th>
                                        <th class="py-2 border bg-blue-50 text-[10px] uppercase">{{ __('Tr.') }}</th>
                                        <th class="py-2 border bg-blue-50 text-[10px] uppercase">{{ __('Score') }}</th>
                                        <th class="py-2 border bg-red-50 text-[10px] uppercase">{{ __('Contr.') }}</th>
                                        <th class="py-2 border bg-red-50 text-[10px] uppercase">{{ __('Decl.') }}</th>
                                        <th class="py-2 border bg-red-50 text-[10px] uppercase">{{ __('Lead') }}</th>
                                        <th class="py-2 border bg-red-50 text-[10px] uppercase">{{ __('Tr.') }}</th>
                                        <th class="py-2 border bg-red-50 text-[10px] uppercase">{{ __('Score') }}</th>
                                        <th class="py-2 border text-[10px] uppercase">H</th>
                                        <th class="py-2 border text-[10px] uppercase">A</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="board in boards" :key="board.board_number">
                                        <tr @click="openBoardEditor(board)" class="hover:bg-indigo-50 cursor-pointer transition-colors border-b">
                                            <td class="py-3 border font-bold" x-text="board.board_number"></td>
                                            <td class="py-3 border italic text-gray-600" x-text="board.home_contract || '-'"></td>
                                            <td class="py-3 border" x-text="board.home_declarer || '-'"></td>
                                            <td class="py-3 border font-mono text-[10px]" x-text="board.home_lead || '-'"></td>
                                            <td class="py-3 border text-[10px]" x-text="formatTricks(board.home_contract_level, board.home_tricks)"></td>
                                            <td class="py-3 border font-mono" x-text="board.home_score !== null ? (board.home_score > 0 ? '+' + board.home_score : board.home_score) : '-'"></td>
                                            <td class="py-3 border italic text-gray-600" x-text="board.away_contract || '-'"></td>
                                            <td class="py-3 border" x-text="board.away_declarer || '-'"></td>
                                            <td class="py-3 border font-mono text-[10px]" x-text="board.away_lead || '-'"></td>
                                            <td class="py-3 border text-[10px]" x-text="formatTricks(board.away_contract_level, board.away_tricks)"></td>
                                            <td class="py-3 border font-mono" x-text="board.away_score !== null ? (board.away_score > 0 ? '+' + board.away_score : board.away_score) : '-'"></td>
                                            <td class="py-3 border font-bold text-green-700" x-text="board.home_imp || ''"></td>
                                            <td class="py-3 border font-bold text-red-700" x-text="board.away_imp || ''"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <x-primary-button class="px-12 py-3 text-lg">
                        {{ __('Update Match & Results') }}
                    </x-primary-button>
                </div>
            </form>
        </div>

        <!-- Board Edit Modal -->
        <div x-show="boardModalOpen" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="boardModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity" @click="boardModalOpen = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div x-show="boardModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <template x-if="editingBoard">
                        <div class="bg-white p-6">
                            <div class="flex justify-between items-center mb-8 border-b pb-2">
                                <h3 class="text-xl font-black text-gray-900">{{ __('Edit Board') }} <span x-text="editingBoard.board_number"></span></h3>
                                <div class="text-xs font-bold text-gray-400 uppercase">
                                    {{ __('Vuln') }}: <span x-text="getVulnerability(editingBoard.board_number)"></span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                                <!-- Open Room Form -->
                                <div class="space-y-6">
                                    <div class="text-sm font-black uppercase tracking-widest text-blue-600 border-b border-blue-100 pb-1">{{ __('Open Room') }}</div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="col-span-2 flex gap-2">
                                            <div class="flex-[2]">
                                                <x-input-label value="{{ __('Contract') }}" />
                                                <select x-model="editingBoard.home_contract_base" @change="updateBoardScores()" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                    <option value="0">Pass</option>
                                                    @foreach(['1','2','3','4','5','6','7'] as $l)
                                                        @foreach(['C', 'D', 'H', 'S', 'NT'] as $s)
                                                            <option value="{{ $l.$s }}">{{ $l.$s }}</option>
                                                        @endforeach
                                                    @endforeach
                                                </select>
                                            </div>
                                        <div class="col-span-2" x-show="editingBoard.home_contract_level > 0">
                                            <x-input-label value="{{ __('Risk') }}" class="mb-2" />
                                            <div class="flex p-1 bg-gray-100 rounded-lg w-full">
                                                <button type="button" @click="editingBoard.home_contract_risk = 1; updateBoardScores()" 
                                                    :class="editingBoard.home_contract_risk === 1 ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                                                    class="flex-1 py-2 text-xs font-bold rounded-md transition-all duration-200">
                                                    {{ __('None') }}
                                                </button>
                                                <button type="button" @click="editingBoard.home_contract_risk = 2; updateBoardScores()" 
                                                    :class="editingBoard.home_contract_risk === 2 ? 'bg-orange-500 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                                    class="flex-1 py-2 text-xs font-bold rounded-md transition-all duration-200">
                                                    DBL (X)
                                                </button>
                                                <button type="button" @click="editingBoard.home_contract_risk = 4; updateBoardScores()" 
                                                    :class="editingBoard.home_contract_risk === 4 ? 'bg-red-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                                    class="flex-1 py-2 text-xs font-bold rounded-md transition-all duration-200">
                                                    RDBL (XX)
                                                </button>
                                            </div>
                                        </div>
                                        </div>
                                        <div x-show="editingBoard.home_contract_level > 0">
                                            <x-input-label value="{{ __('Declarer') }}" />
                                            <select x-model="editingBoard.home_declarer" @change="updateBoardScores()" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                <option value="">-</option>
                                                <option value="N">N</option><option value="E">E</option><option value="S">S</option><option value="W">W</option>
                                            </select>
                                        </div>
                                        <div x-show="editingBoard.home_contract_level > 0">
                                            <x-input-label value="{{ __('Lead') }}" />
                                            <select x-model="editingBoard.home_lead" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                <option value="">-</option>
                                                @foreach(['S' => '♠', 'H' => '♥', 'D' => '♦', 'C' => '♣'] as $suitCode => $suitSym)
                                                    <optgroup label="{{ $suitSym }} {{ $suitCode }}">
                                                        @foreach(['A', 'K', 'Q', 'J', 'T', '9', '8', '7', '6', '5', '4', '3', '2'] as $val)
                                                            <option value="{{ $val }}{{ $suitCode }}">{{ $val }}{{ $suitCode }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div x-show="editingBoard.home_contract_level > 0" class="col-span-2">
                                            <x-input-label value="{{ __('Tricks') }}" />
                                            <select x-model.number="editingBoard.home_tricks" @change="updateBoardScores()" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                <template x-for="opt in getTrickOptions(editingBoard.home_contract_level)" :key="opt.value">
                                                    <option :value="opt.value" x-text="opt.label" :selected="editingBoard.home_tricks === opt.value"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <x-input-label value="{{ __('Score (for NS)') }}" />
                                            <x-text-input type="number" class="block w-full text-lg font-mono font-bold bg-gray-50" x-model.number="editingBoard.home_score" readonly />
                                        </div>
                                    </div>
                                </div>

                                <!-- Closed Room Form -->
                                <div class="space-y-6">
                                    <div class="text-sm font-black uppercase tracking-widest text-red-600 border-b border-red-100 pb-1">{{ __('Closed Room') }}</div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="col-span-2 flex gap-2">
                                            <div class="flex-[2]">
                                                <x-input-label value="{{ __('Contract') }}" />
                                                <select x-model="editingBoard.away_contract_base" @change="updateBoardScores()" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                    <option value="0">Pass</option>
                                                    @foreach(['1','2','3','4','5','6','7'] as $l)
                                                        @foreach(['C', 'D', 'H', 'S', 'NT'] as $s)
                                                            <option value="{{ $l.$s }}">{{ $l.$s }}</option>
                                                        @endforeach
                                                    @endforeach
                                                </select>
                                            </div>
                                        <div class="col-span-2" x-show="editingBoard.away_contract_level > 0">
                                            <x-input-label value="{{ __('Risk') }}" class="mb-2" />
                                            <div class="flex p-1 bg-gray-100 rounded-lg w-full">
                                                <button type="button" @click="editingBoard.away_contract_risk = 1; updateBoardScores()" 
                                                    :class="editingBoard.away_contract_risk === 1 ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                                                    class="flex-1 py-2 text-xs font-bold rounded-md transition-all duration-200">
                                                    {{ __('None') }}
                                                </button>
                                                <button type="button" @click="editingBoard.away_contract_risk = 2; updateBoardScores()" 
                                                    :class="editingBoard.away_contract_risk === 2 ? 'bg-orange-500 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                                    class="flex-1 py-2 text-xs font-bold rounded-md transition-all duration-200">
                                                    DBL (X)
                                                </button>
                                                <button type="button" @click="editingBoard.away_contract_risk = 4; updateBoardScores()" 
                                                    :class="editingBoard.away_contract_risk === 4 ? 'bg-red-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                                    class="flex-1 py-2 text-xs font-bold rounded-md transition-all duration-200">
                                                    RDBL (XX)
                                                </button>
                                            </div>
                                        </div>
                                        </div>
                                        <div x-show="editingBoard.away_contract_level > 0">
                                            <x-input-label value="{{ __('Declarer') }}" />
                                            <select x-model="editingBoard.away_declarer" @change="updateBoardScores()" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                <option value="">-</option>
                                                <option value="N">N</option><option value="E">E</option><option value="S">S</option><option value="W">W</option>
                                            </select>
                                        </div>
                                        <div x-show="editingBoard.away_contract_level > 0">
                                            <x-input-label value="{{ __('Lead') }}" />
                                            <select x-model="editingBoard.away_lead" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                <option value="">-</option>
                                                @foreach(['S' => '♠', 'H' => '♥', 'D' => '♦', 'C' => '♣'] as $suitCode => $suitSym)
                                                    <optgroup label="{{ $suitSym }} {{ $suitCode }}">
                                                        @foreach(['A', 'K', 'Q', 'J', 'T', '9', '8', '7', '6', '5', '4', '3', '2'] as $val)
                                                            <option value="{{ $val }}{{ $suitCode }}">{{ $val }}{{ $suitCode }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div x-show="editingBoard.away_contract_level > 0" class="col-span-2">
                                            <x-input-label value="{{ __('Tricks') }}" />
                                            <select x-model.number="editingBoard.away_tricks" @change="updateBoardScores()" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                <template x-for="opt in getTrickOptions(editingBoard.away_contract_level)" :key="opt.value">
                                                    <option :value="opt.value" x-text="opt.label" :selected="editingBoard.away_tricks === opt.value"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <x-input-label value="{{ __('Score (for NS)') }}" />
                                            <x-text-input type="number" class="block w-full text-lg font-mono font-bold bg-gray-50" x-model.number="editingBoard.away_score" readonly />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-12 flex justify-end gap-3">
                                <x-secondary-button type="button" @click="boardModalOpen = false">{{ __('Cancel') }}</x-secondary-button>
                                <x-primary-button type="button" @click="saveBoard()">{{ __('Save Board') }}</x-primary-button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
