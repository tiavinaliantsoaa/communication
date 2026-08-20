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

        format(cmd) {
            this.$refs.editor?.focus();
            document.execCommand(cmd, false, null);
            this.onInput();
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
