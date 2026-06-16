<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex flex-col">
                <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                    {{ __($room === 'open' ? 'Open Room' : 'Closed Room') }}
                </h2>
                <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">
                    {{ $homeTeam->name ?? '???' }} vs {{ $awayTeam->name ?? '???' }} ({{ $round->name }})
                </div>
            </div>
            <a href="{{ route('scoring.index') }}" class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-[10px] text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition ease-in-out duration-150">
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6 px-2 sm:px-4 lg:px-8" x-data="{
        boards: {{ json_encode($boards) }},
        
        room: '{{ $room }}',
        editingBoard: null,
        boardModalOpen: false,
        isSaving: false,
        showSuccess: false,

        getVulnerability(boardNumber) {
            const vulns = ['None', 'NS', 'EW', 'All', 'NS', 'EW', 'All', 'None', 'EW', 'All', 'None', 'NS', 'All', 'None', 'NS', 'EW'];
            return vulns[(boardNumber - 1) % 16];
        },

        calculateBridgeScore(level, suit, risk, tricks, decl, boardNumber) {
            if (level === 0) return 0;
            if (!suit || !decl || tricks === null || tricks === '') return null;

            let tricksMade = parseInt(tricks) - 6;
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
                const response = await fetch('{{ route('scoring.board.update', [$tournament->id, $round->id, ($match->id ?: $match->home_team_id), $room, 'BOARD_NUM']) }}'.replace('BOARD_NUM', this.editingBoard.board_number), {
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
                    const updated = data.board;
                    this.boards[idx].current_room_contract_level = this.editingBoard.current_room_contract_level;
                    this.boards[idx].current_room_contract_suit = this.editingBoard.current_room_contract_suit;
                    this.boards[idx].current_room_contract_risk = this.editingBoard.current_room_contract_risk;
                    this.boards[idx].current_room_contract_base = this.editingBoard.current_room_contract_base;
                    this.boards[idx].current_room_declarer = this.editingBoard.current_room_declarer;
                    this.boards[idx].current_room_tricks = this.editingBoard.current_room_tricks;
                    this.boards[idx].current_room_lead = this.editingBoard.current_room_lead;
                    this.boards[idx].current_room_score = updated.current_room_score;
                }

                this.boardModalOpen = false;
                this.showSuccess = true;
                setTimeout(() => this.showSuccess = false, 3000);
            } catch (error) {
                alert('{{ __('Error saving score.') }}');
            } finally {
                this.isSaving = false;
            }
        }
    }">
        <div x-show="showSuccess" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-4"
             class="max-w-xl mx-auto mb-4 bg-green-500 text-white px-4 py-2 rounded-lg text-center text-xs font-black uppercase tracking-widest shadow-lg"
             x-cloak>
            {{ __('Score Saved Successfully!') }}
        </div>

        <div class="max-w-xl mx-auto space-y-2">
            <template x-for="board in boards" :key="board.board_number">
                <div @click="openBoardEditor(board)" 
                     class="bg-white p-3 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between active:bg-gray-50 transition-colors cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-800 text-white font-black text-xs">
                            <span x-text="board.board_number"></span>
                        </div>
                        <div class="flex flex-col">
                            <template x-if="board.current_room_contract_level === 0">
                                <span class="text-sm font-bold text-gray-500 uppercase tracking-widest">Pass</span>
                            </template>
                            <template x-if="board.current_room_contract_level > 0">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-sm font-black text-gray-800">
                                        <span x-text="board.current_room_contract_level"></span><span x-html="{
                                            'C': '&clubs;',
                                            'D': '&diams;',
                                            'H': '&hearts;',
                                            'S': '&spades;',
                                            'NT': 'NT'
                                        }[board.current_room_contract_suit]" :class="{
                                            'C': 'text-gray-800',
                                            'D': 'text-red-600',
                                            'H': 'text-red-600',
                                            'S': 'text-gray-800',
                                            'NT': 'text-gray-600'
                                        }[board.current_room_contract_suit]"></span><span x-text="board.current_room_contract_risk == 2 ? 'X' : (board.current_room_contract_risk == 4 ? 'XX' : '')"></span>
                                        <span class="text-gray-400 font-bold" x-text="board.current_room_declarer"></span>
                                    </span>
                                    <span class="text-[10px] text-gray-400 font-bold" x-text="board.current_room_tricks"></span>
                                </div>
                            </template>
                            <template x-if="board.current_room_contract_level === null">
                                <span class="text-[10px] font-bold text-gray-300 uppercase tracking-widest italic">{{ __('No Score') }}</span>
                            </template>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-black" :class="board.current_room_score > 0 ? 'text-green-600' : (board.current_room_score < 0 ? 'text-red-600' : 'text-gray-400')">
                            <span x-text="board.current_room_score !== null ? (board.current_room_score > 0 ? '+' : '') + board.current_room_score : ''"></span>
                        </div>
                        <div class="text-[8px] font-bold text-gray-300 uppercase" x-text="getVulnerability(board.board_number)"></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Board Entry Modal -->
        <div x-show="boardModalOpen" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div @click.away="boardModalOpen = false" 
                 class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100">
                
                <div class="p-4 bg-gray-800 text-white flex justify-between items-center">
                    <h3 class="text-sm font-black uppercase tracking-widest">{{ __('Board') }} <span x-text="editingBoard?.board_number"></span></h3>
                    <div class="text-[10px] font-bold opacity-60 uppercase" x-text="getVulnerability(editingBoard?.board_number)"></div>
                </div>

                <div class="p-4 space-y-4" x-if="editingBoard">
                    <!-- Contract Selector -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ __('Contract') }}</label>
                        <div class="grid grid-cols-4 gap-1">
                            <template x-for="lvl in [0,1,2,3,4,5,6,7]">
                                <button @click="editingBoard.current_room_contract_level = lvl"
                                        :class="editingBoard.current_room_contract_level === lvl ? 'bg-gray-800 text-white shadow-md' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'"
                                        class="py-2 text-sm font-black rounded-lg transition-all"
                                        x-text="lvl === 0 ? 'Pass' : lvl"></button>
                            </template>
                        </div>

                        <template x-if="editingBoard.current_room_contract_level > 0">
                            <div class="mt-2 space-y-2">
                                <div class="grid grid-cols-5 gap-1">
                                    <template x-for="s in [
                                        {code: 'C', label: '&clubs;', color: 'text-gray-800'},
                                        {code: 'D', label: '&diams;', color: 'text-red-600'},
                                        {code: 'H', label: '&hearts;', color: 'text-red-600'},
                                        {code: 'S', label: '&spades;', color: 'text-gray-800'},
                                        {code: 'NT', label: 'NT', color: 'text-gray-600'}
                                    ]">
                                        <button @click="editingBoard.current_room_contract_suit = s.code"
                                                :class="editingBoard.current_room_contract_suit === s.code ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-50 text-gray-600'"
                                                class="py-2 text-xl font-black rounded-lg transition-all flex flex-col items-center justify-center">
                                            <span x-html="s.label" :class="editingBoard.current_room_contract_suit === s.code ? 'text-white' : s.color"></span>
                                            <span class="text-[8px] uppercase tracking-tighter" x-text="s.code === 'NT' ? '' : s.code"></span>
                                        </button>
                                    </template>
                                </div>
                                <div class="grid grid-cols-3 gap-1">
                                    <button @click="editingBoard.current_room_contract_risk = 1"
                                            :class="editingBoard.current_room_contract_risk == 1 ? 'bg-gray-800 text-white shadow-md' : 'bg-gray-50 text-gray-600'"
                                            class="py-2 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all">Normal</button>
                                    <button @click="editingBoard.current_room_contract_risk = 2"
                                            :class="editingBoard.current_room_contract_risk == 2 ? 'bg-red-600 text-white shadow-md' : 'bg-gray-50 text-gray-600'"
                                            class="py-2 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all">DBL (X)</button>
                                    <button @click="editingBoard.current_room_contract_risk = 4"
                                            :class="editingBoard.current_room_contract_risk == 4 ? 'bg-red-900 text-white shadow-md' : 'bg-gray-50 text-gray-600'"
                                            class="py-2 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all">RDBL (XX)</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <template x-if="editingBoard.current_room_contract_level > 0">
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Declarer -->
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ __('Declarer') }}</label>
                                <div class="grid grid-cols-4 gap-1">
                                    <template x-for="d in ['N', 'S', 'E', 'W']">
                                        <button @click="editingBoard.current_room_declarer = d"
                                                :class="editingBoard.current_room_declarer === d ? 'bg-gray-800 text-white' : 'bg-gray-50 text-gray-600'"
                                                class="py-2 text-xs font-black rounded-lg transition-all"
                                                x-text="d"></button>
                                    </template>
                                </div>
                            </div>
                            <!-- Result -->
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ __('Tricks') }}</label>
                                <select x-model="editingBoard.current_room_tricks" 
                                       class="w-full bg-gray-50 border-0 rounded-lg text-sm font-black focus:ring-2 focus:ring-gray-800 p-2">
                                    <option value="">{{ __('Select') }}</option>
                                    <template x-for="t in Array.from({length: 14}, (_, i) => i)">
                                        <option :value="t" x-text="(() => {
                                            const target = parseInt(editingBoard.current_room_contract_level) + 6;
                                            const diff = t - target;
                                            if (diff === 0) return '=';
                                            return (diff > 0 ? '+' : '') + diff;
                                        })() + ' (' + t + ')'"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </template>

                    <template x-if="editingBoard.current_room_contract_level > 0">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ __('Lead') }}</label>
                            <select x-model="editingBoard.current_room_lead" 
                                   class="w-full bg-gray-50 border-0 rounded-lg text-sm font-black focus:ring-2 focus:ring-gray-800 p-2">
                                <option value="">{{ __('Select Lead') }}</option>
                                <template x-for="suit in [
                                    {code: 'S', label: '&spades;', color: 'text-gray-800'},
                                    {code: 'H', label: '&hearts;', color: 'text-red-600'},
                                    {code: 'D', label: '&diams;', color: 'text-red-600'},
                                    {code: 'C', label: '&clubs;', color: 'text-gray-800'}
                                ]">
                                    <optgroup :label="suit.code">
                                        <template x-for="rank in ['A', 'K', 'Q', 'J', 'T', '9', '8', '7', '6', '5', '4', '3', '2']">
                                            <option :value="suit.code + rank" x-text="suit.code + rank"></option>
                                        </template>
                                    </optgroup>
                                </template>
                            </select>
                        </div>
                    </template>

                    <div class="pt-4 flex flex-col gap-2">
                        <div class="text-center p-3 rounded-xl bg-gray-50 border border-dashed border-gray-200">
                            <span class="block text-[8px] font-black text-gray-400 uppercase tracking-widest">{{ __('Estimated Score') }}</span>
                            <span class="text-xl font-black" :class="calculateBridgeScore(editingBoard.current_room_contract_level, editingBoard.current_room_contract_suit, editingBoard.current_room_contract_risk, editingBoard.current_room_tricks, editingBoard.current_room_declarer, editingBoard.board_number) > 0 ? 'text-green-600' : (calculateBridgeScore(editingBoard.current_room_contract_level, editingBoard.current_room_contract_suit, editingBoard.current_room_contract_risk, editingBoard.current_room_tricks, editingBoard.current_room_declarer, editingBoard.board_number) < 0 ? 'text-red-600' : 'text-gray-400')">
                                <span x-text="calculateBridgeScore(editingBoard.current_room_contract_level, editingBoard.current_room_contract_suit, editingBoard.current_room_contract_risk, editingBoard.current_room_tricks, editingBoard.current_room_declarer, editingBoard.board_number) !== null ? (calculateBridgeScore(editingBoard.current_room_contract_level, editingBoard.current_room_contract_suit, editingBoard.current_room_contract_risk, editingBoard.current_room_tricks, editingBoard.current_room_declarer, editingBoard.board_number) > 0 ? '+' : '') + calculateBridgeScore(editingBoard.current_room_contract_level, editingBoard.current_room_contract_suit, editingBoard.current_room_contract_risk, editingBoard.current_room_tricks, editingBoard.current_room_declarer, editingBoard.board_number) : '---'"></span>
                            </span>
                        </div>

                        <button @click="saveBoard"
                                :disabled="isSaving"
                                class="w-full bg-gray-800 hover:bg-gray-700 disabled:bg-gray-400 text-white font-black py-4 rounded-xl text-xs uppercase tracking-[0.2em] shadow-lg shadow-gray-200 transition-all">
                            <span x-show="!isSaving">{{ __('Submit Score') }}</span>
                            <span x-show="isSaving" class="animate-pulse">{{ __('Saving...') }}</span>
                        </button>
                        <button @click="boardModalOpen = false" class="w-full py-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
