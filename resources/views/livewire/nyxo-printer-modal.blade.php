<div>
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="relative w-full max-w-xl bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden transform transition-all">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">{{ __('Print Document') }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Nyxo Universal Printer • {{ __('Destination & format') }}</p>
                        </div>
                    </div>
                    <button wire:click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5">

                    <!-- Feedback Messages -->
                    @if($feedbackMessage)
                        <div class="p-3.5 rounded-xl text-sm font-medium flex items-center gap-3 {{ $feedbackType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800' }}">
                            @if($feedbackType === 'success')
                                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-rose-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                            <span class="flex-1 text-xs sm:text-sm">{{ $feedbackMessage }}</span>
                        </div>
                    @endif

                    <!-- Format Selector -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">{{ __('Output Format') }}</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" wire:click="setFormat('a4')" class="p-3.5 rounded-xl border text-left flex items-start gap-3 transition-all {{ $format === 'a4' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-900/20 text-indigo-900 dark:text-indigo-200 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 text-slate-700 dark:text-slate-300' }}">
                                <div class="p-2 rounded-lg bg-white dark:bg-slate-700 shadow-sm border border-slate-200/60 dark:border-slate-600">
                                    <svg class="w-5 h-5 text-slate-700 dark:text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-sm">{{ __('Standard A4 Page') }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('Conventional PDF / Office') }}</div>
                                </div>
                            </button>

                            <button type="button" wire:click="setFormat('ticket_80mm')" class="p-3.5 rounded-xl border text-left flex items-start gap-3 transition-all {{ $format === 'ticket_80mm' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-900/20 text-indigo-900 dark:text-indigo-200 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 text-slate-700 dark:text-slate-300' }}">
                                <div class="p-2 rounded-lg bg-white dark:bg-slate-700 shadow-sm border border-slate-200/60 dark:border-slate-600">
                                    <svg class="w-5 h-5 text-slate-700 dark:text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-sm">{{ __('Thermal Receipt (80mm)') }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('ESC/POS POS Printer') }}</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Target Printer Node Selector -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">{{ __('Target Printer Station') }}</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            @forelse($printerNodes as $node)
                                <div wire:click="setPrinterNode({{ $node->id }})" class="p-3 rounded-xl border flex items-center justify-between cursor-pointer transition-all {{ $printerNodeId === $node->id ? 'border-indigo-600 bg-indigo-50/40 dark:bg-indigo-900/20 ring-1 ring-indigo-500' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600' }}">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2.5 h-2.5 rounded-full {{ $node->is_online ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300 dark:bg-slate-600' }}"></div>
                                        <div>
                                            <div class="font-bold text-sm text-slate-800 dark:text-white">{{ $node->name }}</div>
                                            <div class="text-[10px] text-slate-400">
                                                @if($node->is_online)
                                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ __('Online') }}</span> • {{ __('Connected recently') }}
                                                @else
                                                    <span class="text-slate-400">{{ __('Offline / No active connection') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-xs font-mono text-slate-400">#{{ $node->id }}</div>
                                </div>
                            @empty
                                <div class="p-4 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 text-center text-slate-500 text-xs">
                                    {{ __('No printer stations found. Please register a node in database or settings.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white">
                        {{ __('Close') }}
                    </button>

                    <button type="button" wire:click="sendToPrinter" wire:loading.attr="disabled" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-600/20 disabled:opacity-50 inline-flex items-center gap-2 transition-all">
                        <span wire:loading.remove wire:target="sendToPrinter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                        </span>
                        <span wire:loading wire:target="sendToPrinter" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        <span>{{ __('Send to Printer') }}</span>
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
