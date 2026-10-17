@props(['id', 'title'])

<div id="{{ $id }}" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-[999] flex items-end sm:items-center justify-center p-0 sm:p-4 hidden" onclick="if(event.target===this){this.classList.add('hidden')}">
    <div class="bg-white rounded-t-3xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl border border-slate-200 transform scale-100 transition-all duration-300 max-h-[92dvh] sm:max-h-[90vh] overflow-y-auto custom-scrollbar">
        <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-slate-200 sm:hidden" aria-hidden="true"></div>
        <div class="flex justify-between items-center pb-3 border-b border-slate-100 mb-4 sticky top-0 bg-white z-10">
            <h3 class="font-black text-slate-800 text-sm sm:text-base flex items-center gap-2 min-w-0 pr-2">
                {{ $title }}
            </h3>
            <button type="button" onclick="document.getElementById('{{ $id }}').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 p-1 shrink-0">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        
        <div class="min-w-0">
            {{ $slot }}
        </div>
    </div>
</div>
