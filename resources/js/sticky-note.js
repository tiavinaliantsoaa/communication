/**
 * Alpine component: private sticky note (pense-bête) per user.
 */
window.stickyNote = function stickyNote(config) {
    return {
        open: false,
        menuOpen: false,
        empty: true,
        status: '',
        saving: false,
        loaded: false,
        saveTimer: null,

        async toggle() {
            if (this.open) {
                this.close();
                return;
            }
            this.open = true;
            if (!this.loaded) {
                await this.load();
            }
            this.$nextTick(() => {
                this.$refs.editor?.focus();
            });
        },

        close() {
            this.open = false;
            this.menuOpen = false;
            this.save(true);
        },

        async load() {
            try {
                const res = await fetch(config.showUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                const html = data.contenu || '';
                if (this.$refs.editor) {
                    this.$refs.editor.innerHTML = html;
                }
                this.empty = !this.$refs.editor?.innerText?.trim();
                this.loaded = true;
            } catch (_) {
                // silent
            }
        },

        onInput() {
            this.empty = !this.$refs.editor?.innerText?.trim();
            this.status = '…';
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.save(false), 700);
        },

        onPaste(event) {
            event.preventDefault();
            const text = (event.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, text);
        },

        onKeydown(event) {
            // Allow Enter inside lists to create a new bullet
            if (event.key === 'Enter' && !event.shiftKey) {
                const inList = this.isSelectionInList();
                if (inList) {
                    // Let the browser handle list Enter natively
                    return;
                }
            }
        },

        format(cmd) {
            const editor = this.$refs.editor;
            if (!editor) return;
            editor.focus();
            document.execCommand(cmd, false, null);
            this.onInput();
        },

        isSelectionInList() {
            const sel = window.getSelection();
            if (!sel || !sel.rangeCount) return false;
            let node = sel.anchorNode;
            if (node && node.nodeType === Node.TEXT_NODE) node = node.parentElement;
            return !!(node && node.closest && node.closest('ul, ol, li'));
        },

        /**
         * insertUnorderedList is unreliable on empty / plain text contenteditable.
         * Ensure a selection exists, then toggle list (with HTML fallback).
         */
        toggleList() {
            const editor = this.$refs.editor;
            if (!editor) return;
            editor.focus();

            const sel = window.getSelection();
            if (!sel) return;

            // If empty editor, seed a paragraph so list can attach
            if (!editor.innerText.trim()) {
                editor.innerHTML = '<div><br></div>';
                const range = document.createRange();
                range.selectNodeContents(editor.firstChild);
                range.collapse(true);
                sel.removeAllRanges();
                sel.addRange(range);
            } else if (sel.isCollapsed && sel.anchorNode) {
                // Expand to the current line/block so the whole line becomes a list item
                let block = sel.anchorNode;
                if (block.nodeType === Node.TEXT_NODE) block = block.parentElement;
                while (block && block !== editor && !/^(DIV|P|LI|H[1-6])$/i.test(block.tagName || '')) {
                    block = block.parentElement;
                }
                if (block && block !== editor) {
                    const range = document.createRange();
                    range.selectNodeContents(block);
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            }

            const alreadyList = this.isSelectionInList();
            let ok = false;
            try {
                ok = document.execCommand('insertUnorderedList', false, null);
            } catch (_) {
                ok = false;
            }

            // Fallback if execCommand failed or did nothing
            if (!ok || (!alreadyList && !editor.querySelector('ul, ol'))) {
                const text = (sel.toString() || editor.innerText || 'Élément').trim() || 'Élément';
                const lines = text.split(/\n+/).filter(Boolean);
                const items = lines.map((l) => '<li>' + this.escapeHtml(l) + '</li>').join('');
                document.execCommand('insertHTML', false, '<ul>' + items + '</ul>');
            }

            this.onInput();
            editor.focus();
        },

        escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        },

        async insertImage(event) {
            const file = event.target.files?.[0];
            event.target.value = '';
            if (!file) return;

            const fd = new FormData();
            fd.append('image', file);

            try {
                this.status = 'Upload…';
                const res = await fetch(config.imageUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: fd,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'Échec upload');
                }
                this.$refs.editor?.focus();
                document.execCommand(
                    'insertHTML',
                    false,
                    '<div><img src="' + data.url + '" alt="" style="max-width:100%;height:auto;border-radius:4px;"></div><div><br></div>'
                );
                this.onInput();
            } catch (e) {
                this.status = e.message || 'Erreur';
            }
        },

        clearNote() {
            if (!confirm('Effacer tout le contenu de la note ?')) return;
            if (this.$refs.editor) {
                this.$refs.editor.innerHTML = '';
            }
            this.empty = true;
            this.save(true);
            this.$refs.editor?.focus();
        },

        async save(force) {
            if (this.saving && !force) return;
            clearTimeout(this.saveTimer);
            this.saving = true;
            this.status = 'Enregistrement…';
            try {
                const contenu = this.$refs.editor?.innerHTML || '';
                const res = await fetch(config.saveUrl, {
                    method: 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ contenu }),
                });
                if (!res.ok) throw new Error('Erreur');
                this.status = 'Enregistré';
                setTimeout(() => {
                    if (this.status === 'Enregistré') this.status = '';
                }, 1500);
            } catch (_) {
                this.status = 'Erreur';
            } finally {
                this.saving = false;
            }
        },
    };
};
