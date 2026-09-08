<div class="grid grid-cols-1 md:grid-cols-12 gap-6">
    {{-- Sidebar Kiri: Smart Folders --}}
    <div class="md:col-span-3 space-y-4">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 shadow-xs">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3 flex items-center gap-2">
                <flux:icon icon="square-3-stack-3d" class="size-4 text-emerald-600" />
                Document Tree
            </h3>
            
            <div class="mb-4">
                <flux:select wire:model.live="groupingMode" size="sm" class="w-full">
                    <option value="category_classification">Kategori &rarr; Klasifikasi</option>
                    <option value="year_category">Tahun &rarr; Kategori</option>
                </flux:select>
            </div>

            <div class="space-y-1 overflow-y-auto max-h-[600px] pr-1">
                <button 
                    type="button" 
                    wire:click="resetSelection"
                    class="w-full text-left px-2 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-2 {{ $activeNodeId === '' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                    <flux:icon icon="home" class="size-4" />
                    Semua Arsip
                </button>

                @foreach($this->smartFolders as $folder)
                    <div x-data="{ open: {{ str_starts_with($activeNodeId, $folder['id']) ? 'true' : 'false' }} }" class="mt-1">
                        <button 
                            type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-2 py-1.5 rounded-md text-sm transition-colors text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <div class="flex items-center gap-2 font-medium">
                                <flux:icon icon="chevron-right" class="size-3 transition-transform" x-bind:class="open ? 'rotate-90' : ''" />
                                <flux:icon icon="folder" class="size-4 text-amber-500" />
                                {{ $folder['name'] }}
                            </div>
                            <span class="text-[10px] bg-zinc-100 dark:bg-zinc-800 text-zinc-500 px-1.5 py-0.5 rounded-full">{{ $folder['count'] }}</span>
                        </button>
                        
                        <div x-show="open" x-collapse>
                            <div class="pl-6 py-1 space-y-1 relative before:absolute before:inset-y-0 before:left-3.5 before:w-px before:bg-zinc-200 dark:before:bg-zinc-700">
                                <button 
                                    type="button"
                                    wire:click="selectNode('{{ $folder['id'] }}', {{ json_encode($folder['filters']) }})"
                                    class="w-full text-left px-2 py-1.5 rounded-md text-xs transition-colors flex items-center gap-2 {{ $activeNodeId === $folder['id'] ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-medium' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                    <flux:icon icon="document-duplicate" class="size-3.5" />
                                    Semua di {{ $folder['name'] }}
                                </button>
                                
                                @foreach($folder['children'] as $child)
                                    <button 
                                        type="button"
                                        wire:click="selectNode('{{ $child['id'] }}', {{ json_encode($child['filters']) }})"
                                        class="w-full text-left px-2 py-1.5 rounded-md text-xs transition-colors flex items-center justify-between {{ $activeNodeId === $child['id'] ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-medium' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                        <div class="flex items-center gap-2 truncate">
                                            <flux:icon icon="folder-open" class="size-3.5 text-sky-500" />
                                            <span class="truncate">{{ $child['name'] }}</span>
                                        </div>
                                        <span class="text-[10px] shrink-0 {{ $activeNodeId === $child['id'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400' }}">{{ $child['count'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Area Kanan: Data Table --}}
    <div class="md:col-span-9">
        <livewire:archive-table :is-explorer="true" />
    </div>
</div>
