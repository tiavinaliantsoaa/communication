{{-- Sticky note (pense-bête privé) --}}
<div
    x-data="stickyNote({
        showUrl: @js(route('notes.show')),
        saveUrl: @js(route('notes.update')),
        imageUrl: @js(route('notes.image')),
        csrf: @js(csrf_token()),
    })"
    class="contents"
>
    <button
        type="button"
        @click="toggle()"
        class="relative p-2 rounded-lg text-slate-500 hover:bg-white hover:text-slate-700 transition-colors"
        title="Pense-bête"
        :class="open && 'bg-white text-slate-800 shadow-sm'"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 -960 960 960" fill="currentColor" style="color:#EA3323">
            <path d="M440-240h80v-120h120v-80H520v-120h-80v120H320v80h120v120ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        @keydown.escape.window="if (open) close()"
        class="fixed z-[70] right-4 sm:right-8 top-20 w-[min(100vw-2rem,22rem)] shadow-2xl rounded-lg overflow-hidden border border-black/40"
        style="background:#2b2b2b;"
        @click.outside="menuOpen = false"
    >
        {{-- Header jaune --}}
        <div class="flex items-center justify-between px-2 h-10" style="background:#c9a227;">
            <button type="button" @click="clearNote()" class="p-1.5 rounded text-black/80 hover:bg-black/10" title="Nouvelle note">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </button>
            <div class="flex items-center gap-0.5">
                <div class="relative">
                    <button type="button" @click="menuOpen = !menuOpen" class="p-1.5 rounded text-black/80 hover:bg-black/10" title="Options">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"/></svg>
                    </button>
                    <div
                        x-show="menuOpen"
                        x-cloak
                        @click.outside="menuOpen = false"
                        class="absolute right-0 mt-1 w-40 rounded-md bg-[#3a3a3a] border border-white/10 shadow-lg py-1 z-10"
                    >
                        <button type="button" @click="clearNote(); menuOpen = false" class="w-full text-left px-3 py-1.5 text-xs text-white/90 hover:bg-white/10">Effacer la note</button>
                        <button type="button" @click="save(true); menuOpen = false" class="w-full text-left px-3 py-1.5 text-xs text-white/90 hover:bg-white/10">Enregistrer</button>
                    </div>
                </div>
                <button type="button" @click="close()" class="p-1.5 rounded text-black/80 hover:bg-black/10" title="Fermer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Zone d'édition --}}
        <div class="relative">
            <div
                x-ref="editor"
                contenteditable="true"
                @input="onInput()"
                @paste="onPaste($event)"
                class="min-h-[14rem] max-h-[50vh] overflow-y-auto px-3 py-3 text-sm text-white/90 outline-none leading-relaxed"
                style="word-break: break-word; overflow-wrap: anywhere;"
                data-placeholder="Rédigez une note…"
            ></div>
            <p
                x-show="empty"
                x-cloak
                class="pointer-events-none absolute top-3 left-3 text-sm text-white/35"
            >Rédigez une note…</p>
        </div>

        {{-- Toolbar bas --}}
        <div class="flex items-center gap-0.5 px-2 py-1.5 border-t border-white/10" style="background:#1f1f1f;">
            <button type="button" @click="format('bold')" class="w-8 h-8 rounded text-white/70 hover:bg-white/10 font-bold text-sm" title="Gras">B</button>
            <button type="button" @click="format('italic')" class="w-8 h-8 rounded text-white/70 hover:bg-white/10 italic text-sm" title="Italique">I</button>
            <button type="button" @click="format('underline')" class="w-8 h-8 rounded text-white/70 hover:bg-white/10 text-sm underline" title="Souligné">U</button>
            <button type="button" @click="format('strikeThrough')" class="w-8 h-8 rounded text-white/70 hover:bg-white/10 text-sm line-through" title="Barré">ab</button>
            <button type="button" @click="format('insertUnorderedList')" class="w-8 h-8 rounded text-white/70 hover:bg-white/10 flex items-center justify-center" title="Liste">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
            </button>
            <label class="w-8 h-8 rounded text-white/70 hover:bg-white/10 flex items-center justify-center cursor-pointer" title="Image">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="insertImage($event)">
            </label>
            <span class="ml-auto text-[10px] text-white/30 pr-1" x-text="status"></span>
        </div>
    </div>
</div>
