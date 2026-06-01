@php
    $boardsCount = $round->boards_per_round ?? $results->boards_per_round ?? 16;
    $isOpen = $room === 'open';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex flex-col">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __($isOpen ? 'Open Room' : 'Closed Room') }}: {{ $nsTeam->name }} vs {{ $ewTeam->name }}
                </h2>
                <div class="flex items-center gap-4 mt-2">
                    <div class="flex p-0.5 bg-gray-100 rounded-lg shadow-inner">
                        <a href="{{ route('tournaments.match.room.edit', [$tournament, $round->id, ($match->id ?: $match->home_team_id), 'open']) }}" 
                            class="px-4 py-1 text-[10px] font-black uppercase tracking-widest rounded-md transition-all {{ $isOpen ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600' }}">
                            {{ __('Open') }}
                        </a>
                        <a href="{{ route('tournaments.match.room.edit', [$tournament, $round->id, ($match->id ?: $match->home_team_id), 'closed']) }}" 
                            class="px-4 py-1 text-[10px] font-black uppercase tracking-widest rounded-md transition-all {{ !$isOpen ? 'bg-white text-red-600 shadow-sm' : 'text-gray-400 hover:text-gray-600' }}">
                            {{ __('Closed') }}
                        </a>
                    </div>
                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">
                        {{ $homeTeam->name }} vs {{ $awayTeam->name }} ({{ $round->name }})
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div id="save-indicator" class="hidden text-xs font-bold text-green-600 bg-green-50 px-3 py-1 rounded-full animate-pulse">
                    {{ __('Saving...') }}
                </div>
                
                <div class="flex gap-2">
                    @if($match->boards && !empty($tournament->team_results->player_butlers))
                        <a href="{{ route('tournaments.butler', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-[10px] text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Butler') }}
                        </a>
                    @endif

                    <x-secondary-button type="button" onclick="document.getElementById('csvInput').click()" class="!text-[10px]">
                        {{ __('Upload CSV') }}
                    </x-secondary-button>
                    <a href="{{ route('tournaments.match.room.boards.csv.download', [$tournament, $round->id, ($match->id ?: $match->home_team_id), $room]) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-[10px] text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        {{ __('Export CSV') }}
                    </a>
                </div>

                <a href="{{ route('tournaments.edit', $tournament) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Back to Tournament') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="{
        homeImp: {{ $match->home_imp }},
        awayImp: {{ $match->away_imp }},
        homeVp: {{ $match->home_vp }},
        awayVp: {{ $match->away_vp }},
        boards: {{ json_encode(array_map(function($b) use ($isOpen) {
            $contractStr = $isOpen ? ($b->home_contract ?? '') : ($b->away_contract ?? '');
            $parsed = (new \App\Services\BridgeScoringService())->parseContract($contractStr);
            
            $data = $b->toArray();
            $data['current_room_contract_level'] = $parsed[0];
            $data['current_room_contract_suit'] = $parsed[1];
            $data['current_room_contract_risk'] = $parsed[2] ?: 1;
            $data['current_room_contract_base'] = $parsed[0] === 0 ? '0' : $parsed[0] . $parsed[1];
            $data['current_room_declarer'] = $isOpen ? $b->home_declarer : $b->away_declarer;
            $data['current_room_tricks'] = $isOpen ? $b->home_tricks : $b->away_tricks;
            $data['current_room_score'] = $isOpen ? $b->home_score : $b->away_score;
            $data['current_room_lead'] = $isOpen ? $b->home_lead : $b->away_lead;
            
            return $data;
        }, $match->boards)) }},
        boardsCount: {{ $boardsCount }},
        room: '{{ $room }}',
        
        editingBoard: null,
        boardModalOpen: false,
        isSaving: false,

        showIndicator() {
            const el = document.getElementById('save-indicator');
            el.classList.remove('hidden');
            setTimeout(() => el.classList.add('hidden'), 2000);
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

        openBoardEditor(board) {
            this.editingBoard = JSON.parse(JSON.stringify(board));
            this.boardModalOpen = true;
        },

        async saveBoard() {
            if (this.isSaving) return;
            this.isSaving = true;

            try {
                const response = await fetch('{{ route('tournaments.match.room.board.update', [$tournament, $round->id, ($match->id ?: $match->home_team_id), $room, 'BOARD_NUM']) }}'.replace('BOARD_NUM', this.editingBoard.board_number), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        contract_level: this.editingBoard.current_room_contract_level,
                        contract_suit: this.editingBoard.current_room_contract_suit,
                        contract_risk: this.editingBoard.current_room_contract_risk,
                        declarer: this.editingBoard.current_room_declarer,
                        tricks: this.editingBoard.current_room_tricks,
                        lead: this.editingBoard.current_room_lead
                    })
                });

                if (!response.ok) throw new Error('Save failed');

                const data = await response.json();
                let idx = this.boards.findIndex(b => b.board_number === this.editingBoard.board_number);
                if (idx !== -1) {
                    // Update local board data with server-calculated fields (IMPs)
                    this.boards[idx].home_score = data.board.home_score;
                    this.boards[idx].away_score = data.board.away_score;
                    this.boards[idx].home_contract = data.board.home_contract;
                    this.boards[idx].away_contract = data.board.away_contract;
                    this.boards[idx].home_declarer = data.board.home_declarer;
                    this.boards[idx].away_declarer = data.board.away_declarer;
                    this.boards[idx].home_tricks = data.board.home_tricks;
                    this.boards[idx].away_tricks = data.board.away_tricks;
                    this.boards[idx].home_lead = data.board.home_lead;
                    this.boards[idx].away_lead = data.board.away_lead;
                    this.boards[idx].home_imp = data.board.home_imp;
                    this.boards[idx].away_imp = data.board.away_imp;

                    // Sync current room helper fields
                    this.boards[idx].current_room_contract_level = this.editingBoard.current_room_contract_level;
                    this.boards[idx].current_room_tricks = this.editingBoard.current_room_tricks;
                    this.boards[idx].current_room_score = this.editingBoard.current_room_score;
                    this.boards[idx].current_room_lead = this.editingBoard.current_room_lead;
                    this.boards[idx].current_room_contract_base = this.editingBoard.current_room_contract_base;
                }

                // Update match totals
                this.homeImp = data.match_home_imp;
                this.awayImp = data.match_away_imp;
                this.homeVp = data.match_home_vp;
                this.awayVp = data.match_away_vp;

                this.showIndicator();
                this.boardModalOpen = false;
            } catch (error) {
                alert('{{ __("Error saving board. Please try again.") }}');
            } finally {
                this.isSaving = false;
            }
        },

        async saveLineup() {
            const formData = new FormData(document.getElementById('lineup-form'));
            try {
                const response = await fetch('{{ route('tournaments.match.room.lineup.update', [$tournament, $round->id, ($match->id ?: $match->home_team_id), $room]) }}', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                if (response.ok) {
                    this.showIndicator();
                }
            } catch (error) {
                console.error('Lineup save failed');
            }
        },

        updateBoardScores() {
            if (!this.editingBoard) return;

            // Parse contract base
            if (this.editingBoard.current_room_contract_base === '0') {
                this.editingBoard.current_room_contract_level = 0;
                this.editingBoard.current_room_contract_suit = '';
            } else {
                let m = this.editingBoard.current_room_contract_base.match(/^([1-7])(C|D|H|S|NT)$/);
                if (m) {
                    this.editingBoard.current_room_contract_level = parseInt(m[1]);
                    this.editingBoard.current_room_contract_suit = m[2];
                }
            }
            
            this.editingBoard.current_room_score = this.calculateBridgeScore(
                this.editingBoard.current_room_contract_level,
                this.editingBoard.current_room_contract_suit,
                this.editingBoard.current_room_contract_risk,
                this.editingBoard.current_room_tricks,
                this.editingBoard.current_room_declarer,
                this.editingBoard.board_number
            );
        },

        getTrickOptions(level) {
            if (level === 0 || level === null) return [];
            let options = [];
            let required = 6 + parseInt(level);
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
        },

        formatContract(str) {
            if (!str || String(str).trim() === '-' || String(str).trim() === 'Pass') return str || '-';
            let res = String(str).trim();
            res = res.replace(/(10|[0-9TJQKA])S/i, '$1<span class=\'text-gray-900\'>&spades;</span>');
            res = res.replace(/S(10|[0-9TJQKA])/i, '<span class=\'text-gray-900\'>&spades;</span>$1');
            res = res.replace(/(10|[0-9TJQKA])H/i, '$1<span class=\'text-red-600\'>&hearts;</span>');
            res = res.replace(/H(10|[0-9TJQKA])/i, '<span class=\'text-red-600\'>&hearts;</span>$1');
            res = res.replace(/(10|[0-9TJQKA])D/i, '$1<span class=\'text-orange-500\'>&diams;</span>');
            res = res.replace(/D(10|[0-9TJQKA])/i, '<span class=\'text-orange-500\'>&diams;</span>$1');
            res = res.replace(/(10|[0-9TJQKA])C/i, '$1<span class=\'text-green-700\'>&clubs;</span>');
            res = res.replace(/C(10|[0-9TJQKA])/i, '<span class=\'text-green-700\'>&clubs;</span>$1');
            return res;
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Hidden CSV Upload Form -->
            <form id="csvForm" method="POST" action="{{ route('tournaments.match.room.boards.csv.upload', [$tournament, $round->id, ($match->id ?: $match->home_team_id), $room]) }}" enctype="multipart/form-data" class="hidden">
                @csrf
                <input type="file" id="csvInput" name="csv_file" accept=".csv" onchange="document.getElementById('csvForm').submit()">
            </form>

            <!-- Match Score (TOP) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8 border-b-4 border-indigo-500">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold uppercase tracking-widest text-gray-500">{{ __('Overall Match Score') }}</h3>
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-400">
                            <span>{{ $boardsCount }} {{ __('Boards') }}</span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 text-center">
                        <div class="space-y-2">
                            <div class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ __('Total IMPs') }}</div>
                            <div class="flex items-center justify-center gap-6">
                                <div class="text-4xl font-black text-gray-900" x-text="homeImp"></div>
                                <div class="text-2xl font-black text-gray-300">:</div>
                                <div class="text-4xl font-black text-gray-900" x-text="awayImp"></div>
                            </div>
                        </div>

                        <div class="space-y-2 bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                            <div class="text-xs font-bold text-indigo-400 uppercase tracking-widest">{{ __('Total VP') }}</div>
                            <div class="flex items-center justify-center gap-6">
                                <div class="text-3xl font-black text-indigo-700" x-text="parseFloat(homeVp).toFixed(2)"></div>
                                <div class="text-xl font-bold text-indigo-300">-</div>
                                <div class="text-3xl font-black text-indigo-700" x-text="parseFloat(awayVp).toFixed(2)"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seating / Lineup for this Room -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 border-t-4 {{ $isOpen ? 'border-blue-500' : 'border-red-500' }} mb-12">
                <div class="text-center font-bold uppercase {{ $isOpen ? 'text-blue-800' : 'text-red-800' }} mb-8 text-sm tracking-widest border-b pb-1 w-full">{{ __($isOpen ? 'Open Room' : 'Closed Room') }}</div>
                
                <form id="lineup-form" @change="saveLineup()">
                    <div class="flex flex-col items-center gap-6">
                        <!-- North (NS Team) -->
                        <div class="w-full max-w-[250px]">
                            <x-input-label class="text-center text-xs font-black mb-1 {{ $isOpen ? 'text-blue-600' : 'text-red-600' }}" value="N ({{ $nsTeam->name }})" />
                            <select name="n_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">-</option>
                                @foreach($nsPlayers as $player)
                                    @php
                                        $currentId = $isOpen ? ($match->open_ns_ids[0] ?? null) : ($match->closed_ns_ids[0] ?? null);
                                    @endphp
                                    <option value="{{ $player->id }}" {{ $currentId == $player->id ? 'selected' : '' }}>
                                        {{ $player->last_name }} {{ $player->first_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- East/West (EW Team) -->
                        <div class="flex justify-between w-full max-w-[600px] gap-8">
                            <div class="flex-1">
                                <x-input-label class="text-center text-xs font-black mb-1 {{ $isOpen ? 'text-red-600' : 'text-blue-600' }}" value="W ({{ $ewTeam->name }})" />
                                <select name="w_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                    <option value="">-</option>
                                    @foreach($ewPlayers as $player)
                                        @php
                                            $currentId = $isOpen ? ($match->open_ew_ids[1] ?? null) : ($match->closed_ew_ids[1] ?? null);
                                        @endphp
                                        <option value="{{ $player->id }}" {{ $currentId == $player->id ? 'selected' : '' }}>
                                            {{ $player->last_name }} {{ $player->first_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Visual Table Center -->
                            <div class="w-24 h-24 bg-gray-50 border-2 border-gray-200 rounded-xl flex items-center justify-center shadow-inner self-center">
                                <span class="text-xs font-black text-gray-300">{{ __($isOpen ? 'OPEN' : 'CLOSED') }}</span>
                            </div>

                            <div class="flex-1">
                                <x-input-label class="text-center text-xs font-black mb-1 {{ $isOpen ? 'text-red-600' : 'text-blue-600' }}" value="E ({{ $ewTeam->name }})" />
                                <select name="e_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                    <option value="">-</option>
                                    @foreach($ewPlayers as $player)
                                        @php
                                            $currentId = $isOpen ? ($match->open_ew_ids[0] ?? null) : ($match->closed_ew_ids[0] ?? null);
                                        @endphp
                                        <option value="{{ $player->id }}" {{ $currentId == $player->id ? 'selected' : '' }}>
                                            {{ $player->last_name }} {{ $player->first_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- South (NS Team) -->
                        <div class="w-full max-w-[250px]">
                            <x-input-label class="text-center text-xs font-black mb-1 {{ $isOpen ? 'text-blue-600' : 'text-red-600' }}" value="S ({{ $nsTeam->name }})" />
                            <select name="s_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">-</option>
                                @foreach($nsPlayers as $player)
                                    @php
                                        $currentId = $isOpen ? ($match->open_ns_ids[1] ?? null) : ($match->closed_ns_ids[1] ?? null);
                                    @endphp
                                    <option value="{{ $player->id }}" {{ $currentId == $player->id ? 'selected' : '' }}>
                                        {{ $player->last_name }} {{ $player->first_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Board Results Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-6 border-b pb-2 uppercase tracking-widest text-gray-500">{{ __('Board Results') }}</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-center border-collapse">
                            <thead class="bg-gray-50 font-bold text-gray-700">
                                <tr>
                                    <th class="py-3 border px-4" rowspan="2">#</th>
                                    <th class="py-3 border" rowspan="2">{{ __('Contract') }}</th>
                                    <th class="py-3 border" rowspan="2">{{ __('Declarer') }}</th>
                                    <th class="py-3 border" rowspan="2">{{ __('Lead') }}</th>
                                    <th class="py-3 border" rowspan="2">{{ __('Tricks') }}</th>
                                    <th class="py-3 border" rowspan="2">{{ __('Score') }} (NS)</th>
                                    <th class="py-3 border" colspan="2">{{ __('IMPs') }}</th>
                                </tr>
                                <tr class="text-[10px] uppercase bg-gray-100">
                                    <th class="py-1 border">H</th>
                                    <th class="py-1 border">A</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="board in boards" :key="board.board_number">
                                    <tr @click="openBoardEditor(board)" class="hover:bg-indigo-50 cursor-pointer transition-colors border-b">
                                        <td class="py-3 border font-bold" x-text="board.board_number"></td>
                                        <td class="py-3 border italic text-gray-600" x-html="formatContract((room === 'open' ? board.home_contract : board.away_contract) || '-')"></td>
                                        <td class="py-3 border" x-text="(room === 'open' ? board.home_declarer : board.away_declarer) || '-'"></td>
                                        <td class="py-3 border font-mono text-xs" x-html="formatContract((room === 'open' ? board.home_lead : board.away_lead) || '-')"></td>
                                        <td class="py-3 border text-xs" x-text="formatTricks(board.current_room_contract_level, room === 'open' ? board.home_tricks : board.away_tricks)"></td>
                                        <td class="py-3 border font-mono font-bold" :class="(room === 'open' ? board.home_score : board.away_score) > 0 ? 'text-green-600' : ((room === 'open' ? board.home_score : board.away_score) < 0 ? 'text-red-600' : '')">
                                            <span x-text="(room === 'open' ? board.home_score : board.away_score) !== null ? ((room === 'open' ? board.home_score : board.away_score) > 0 ? '+' : '') + (room === 'open' ? board.home_score : board.away_score) : '-'"></span>
                                        </td>
                                        <td class="py-3 border font-bold text-green-700" x-text="board.home_imp || ''"></td>
                                        <td class="py-3 border font-bold text-red-700" x-text="board.away_imp || ''"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50 font-black">
                                <tr>
                                    <td colspan="6" class="py-4 border text-right px-6 uppercase tracking-widest text-gray-400 text-xs">{{ __('Total') }}</td>
                                    <td class="py-4 border text-xl text-green-700" x-text="homeImp"></td>
                                    <td class="py-4 border text-xl text-red-700" x-text="awayImp"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Board Edit Modal -->
        <div x-show="boardModalOpen" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="boardModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity" @click="boardModalOpen = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div x-show="boardModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                    <template x-if="editingBoard">
                        <div class="bg-white p-8">
                            <div class="flex justify-between items-center mb-8 border-b pb-4">
                                <h3 class="text-2xl font-black text-gray-900">{{ __('Edit Board') }} <span x-text="editingBoard.board_number"></span></h3>
                                <div class="px-3 py-1 bg-gray-100 rounded text-xs font-black text-gray-500 uppercase tracking-tighter">
                                    {{ __('Vuln') }}: <span x-text="getVulnerability(editingBoard.board_number)"></span>
                                </div>
                            </div>
                            
                            <div class="space-y-8">
                                <div class="grid grid-cols-1 gap-6">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="col-span-2">
                                            <x-input-label value="{{ __('Contract') }}" />
                                            <select x-model="editingBoard.current_room_contract_base" @change="updateBoardScores()" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                <option value="0">Pass</option>
                                                @foreach(['1','2','3','4','5','6','7'] as $l)
                                                    @foreach(['C' => '♣', 'D' => '♦', 'H' => '♥', 'S' => '♠', 'NT' => 'NT'] as $s => $sym)
                                                        <option value="{{ $l.$s }}">{{ $l }}{{ $sym }}</option>
                                                    @endforeach
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-span-2" x-show="editingBoard.current_room_contract_level > 0">
                                            <x-input-label value="{{ __('Risk') }}" class="mb-2" />
                                            <div class="flex p-1 bg-gray-100 rounded-lg w-full">
                                                <button type="button" @click="editingBoard.current_room_contract_risk = 1; updateBoardScores()" 
                                                    :class="editingBoard.current_room_contract_risk === 1 ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                                                    class="flex-1 py-2 text-xs font-bold rounded-md transition-all duration-200">
                                                    {{ __('None') }}
                                                </button>
                                                <button type="button" @click="editingBoard.current_room_contract_risk = 2; updateBoardScores()" 
                                                    :class="editingBoard.current_room_contract_risk === 2 ? 'bg-orange-500 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                                    class="flex-1 py-2 text-xs font-bold rounded-md transition-all duration-200">
                                                    DBL (X)
                                                </button>
                                                <button type="button" @click="editingBoard.current_room_contract_risk = 4; updateBoardScores()" 
                                                    :class="editingBoard.current_room_contract_risk === 4 ? 'bg-red-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                                    class="flex-1 py-2 text-xs font-bold rounded-md transition-all duration-200">
                                                    RDBL (XX)
                                                </button>
                                            </div>
                                        </div>

                                        <div x-show="editingBoard.current_room_contract_level > 0">
                                            <x-input-label value="{{ __('Declarer') }}" />
                                            <select x-model="editingBoard.current_room_declarer" @change="updateBoardScores()" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                <option value="">-</option>
                                                <option value="N">N</option><option value="E">E</option><option value="S">S</option><option value="W">W</option>
                                            </select>
                                        </div>
                                        
                                        <div x-show="editingBoard.current_room_contract_level > 0">
                                            <x-input-label value="{{ __('Lead') }}" />
                                            <select x-model="editingBoard.current_room_lead" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                <option value="">-</option>
                                                @foreach(['S' => '♠', 'H' => '♥', 'D' => '♦', 'C' => '♣'] as $suitCode => $suitSym)
                                                    <optgroup label="{{ $suitSym }} {{ $suitCode }}">
                                                        @foreach(['A', 'K', 'Q', 'J', 'T', '9', '8', '7', '6', '5', '4', '3', '2'] as $val)
                                                            <option value="{{ $val }}{{ $suitCode }}">{{ $val }}{{ $suitSym }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-span-2" x-show="editingBoard.current_room_contract_level > 0">
                                            <x-input-label value="{{ __('Tricks') }}" />
                                            <select x-model.number="editingBoard.current_room_tricks" @change="updateBoardScores()" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                <template x-for="opt in getTrickOptions(editingBoard.current_room_contract_level)" :key="opt.value">
                                                    <option :value="opt.value" x-text="opt.label" :selected="editingBoard.current_room_tricks === opt.value"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="bg-indigo-50 p-6 rounded-xl border border-indigo-100">
                                        <x-input-label value="{{ __('Calculated Score for NS') }}" class="text-indigo-600 font-bold mb-2" />
                                        <div class="text-3xl font-mono font-black text-indigo-900">
                                            <span x-text="editingBoard.current_room_score !== null ? (editingBoard.current_room_score > 0 ? '+' : '') + editingBoard.current_room_score : '-'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-12 flex justify-end gap-3">
                                <x-secondary-button type="button" @click="boardModalOpen = false" class="px-6">{{ __('Cancel') }}</x-secondary-button>
                                <x-primary-button type="button" @click="saveBoard()" class="px-8" x-bind:disabled="isSaving">
                                    <span x-show="!isSaving">{{ __('Update Board') }}</span>
                                    <span x-show="isSaving">{{ __('Saving...') }}</span>
                                </x-primary-button>
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
