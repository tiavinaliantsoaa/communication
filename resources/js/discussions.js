import Alpine from 'alpinejs';

function renderDiscussionBadge(count) {
    const badge = document.getElementById('discussion-unread');
    if (!badge) return;
    const total = parseInt(count || 0, 10) || 0;
    if (total > 0) {
        badge.textContent = total > 9 ? '9+' : String(total);
        badge.hidden = false;
    } else {
        badge.textContent = '';
        badge.hidden = true;
    }
}

function firstError(data) {
    const errors = data.errors || {};
    const first = Object.values(errors)[0];
    return (Array.isArray(first) ? first[0] : null) || data.message || 'Action impossible.';
}

document.addEventListener('alpine:init', () => {
    Alpine.data('discussionApp', (boot) => ({
        groups: boot.groups || [],
        active: boot.active,
        messages: boot.messages || [],
        members: boot.members || [],
        directory: boot.directory || [],
        annuaire: boot.annuaire || [],
        hasMore: !!boot.has_more,
        since: boot.latest_id || 0,
        csrf: boot.csrf,
        urls: boot.urls,
        search: '',
        body: '',
        reply: null,
        files: [],
        error: '',
        formError: '',
        sending: false,
        showCreate: false,
        showInfo: false,
        createNom: '',
        createQuery: '',
        createSelected: [],
        addQuery: '',
        addSelected: [],
        panel: boot.active ? 'chat' : 'list',
        loadingOlder: false,
        timer: null,

        init() {
            this.timer = setInterval(() => this.poll(), 5000);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this.poll();
            });
            this.$nextTick(() => this.scrollBottom(true));
        },

        destroy() {
            clearInterval(this.timer);
        },

        get filteredGroups() {
            const query = this.search.trim().toLowerCase();
            if (!query) return this.groups;
            return this.groups.filter((group) => group.nom.toLowerCase().includes(query));
        },

        get filteredAnnuaire() {
            return this.filterPeople(this.annuaire, this.createQuery);
        },

        get filteredDirectory() {
            return this.filterPeople(this.directory, this.addQuery);
        },

        get timeline() {
            const items = [];
            let previous = null;
            this.messages.forEach((message) => {
                if (!previous || previous.day !== message.day) {
                    items.push({
                        kind: 'day',
                        key: 'd-' + message.day + '-' + message.id,
                        label: message.day_label,
                    });
                }
                items.push(Object.assign({
                    kind: 'msg',
                    key: 'm-' + message.id,
                    overlap: !!(previous && !message.system && !previous.system && previous.user_id === message.user_id && previous.day === message.day),
                }, message));
                previous = message;
            });
            return items;
        },

        filterPeople(list, query) {
            const needle = query.trim().toLowerCase();
            if (!needle) return list;
            return list.filter((person) => person.name.toLowerCase().includes(needle));
        },

        peopleByIds(list, ids) {
            return list.filter((person) => ids.includes(person.id));
        },

        toggle(listName, id) {
            const index = this[listName].indexOf(id);
            if (index === -1) this[listName].push(id);
            else this[listName].splice(index, 1);
        },

        nearBottom() {
            const el = this.$refs.thread;
            if (!el) return true;
            return el.scrollHeight - el.scrollTop - el.clientHeight < 90;
        },

        scrollBottom(force) {
            const el = this.$refs.thread;
            if (!el) return;
            if (force || this.nearBottom()) el.scrollTop = el.scrollHeight;
        },

        startReply(item) {
            this.reply = {
                id: item.id,
                author: item.author,
                excerpt: item.excerpt || item.body || 'Message',
                color: item.color || '#00a884',
            };
            this.$nextTick(() => this.$refs.composer?.focus());
        },

        jumpTo(id) {
            const el = document.getElementById('msg-' + id);
            if (!el) return;
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.remove('is-flash');
            void el.offsetWidth;
            el.classList.add('is-flash');
        },

        onKeydown(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                this.send();
            }
        },

        onFiles(event) {
            const picked = Array.from(event.target.files || []);
            this.error = '';
            picked.forEach((file) => {
                if (file.size > 10 * 1024 * 1024) {
                    this.error = 'Chaque fichier doit faire moins de 10 Mo.';
                    return;
                }
                if (this.files.length >= 5) {
                    this.error = 'Cinq fichiers maximum par message.';
                    return;
                }
                this.files.push(file);
            });
            event.target.value = '';
        },

        removeFile(index) {
            this.files.splice(index, 1);
        },

        async openGroup(id) {
            this.panel = 'chat';
            this.showInfo = false;
            this.reply = null;
            this.error = '';
            const known = this.groups.find((group) => group.id === id);
            this.active = known || { id, nom: 'Discussion', initials: '…', color: '#00a884' };
            try {
                const res = await fetch(this.urls.base + '/' + id + '/messages', {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    this.error = 'Cette discussion est introuvable.';
                    return;
                }
                const data = await res.json();
                this.applyThread(data);
                this.$nextTick(() => this.scrollBottom(true));
                this.poll();
            } catch (_) {
                this.error = 'Connexion impossible.';
            }
        },

        applyThread(data) {
            this.active = data.group || this.active;
            this.messages = data.messages || [];
            this.members = data.members || [];
            this.directory = data.directory || [];
            this.hasMore = !!data.has_more;
            this.since = data.latest_id || 0;
            this.addSelected = [];
        },

        async loadOlder() {
            if (!this.active || this.loadingOlder || !this.hasMore || !this.messages.length) return;
            this.loadingOlder = true;
            const el = this.$refs.thread;
            const previous = el ? el.scrollHeight : 0;
            const before = this.messages[0].id;
            try {
                const res = await fetch(this.urls.base + '/' + this.active.id + '/messages?before=' + before, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                const known = new Set(this.messages.map((message) => message.id));
                const older = (data.messages || []).filter((message) => !known.has(message.id));
                this.messages = older.concat(this.messages);
                this.hasMore = !!data.has_more;
                this.$nextTick(() => {
                    if (el) el.scrollTop = el.scrollHeight - previous;
                });
            } finally {
                this.loadingOlder = false;
            }
        },

        async poll() {
            if (document.hidden) return;
            const params = new URLSearchParams();
            const viewing = this.active?.id && (window.innerWidth >= 768 || this.panel === 'chat');
            if (viewing) {
                params.set('groupe', String(this.active.id));
                params.set('since', String(this.since || 0));
            }
            try {
                const res = await fetch(this.urls.poll + (params.toString() ? '?' + params.toString() : ''), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (res.status === 404 && this.active) {
                    this.active = null;
                    this.messages = [];
                    this.showInfo = false;
                    this.panel = 'list';
                    return;
                }
                if (!res.ok) return;
                const data = await res.json();
                if (Array.isArray(data.groups)) this.groups = data.groups;
                renderDiscussionBadge(data.unread);
                if (this.active && !this.groups.some((group) => group.id === this.active.id)) {
                    this.active = null;
                    this.messages = [];
                    this.showInfo = false;
                    this.panel = 'list';
                    return;
                }
                this.mergeMessages(data.messages || [], false);
                if (data.latest_id) this.since = Math.max(this.since || 0, data.latest_id);
            } catch (_) {}
        },

        mergeMessages(incoming, stick) {
            const known = new Set(this.messages.map((message) => message.id));
            const fresh = incoming.filter((message) => !known.has(message.id));
            if (!fresh.length) return;
            const follow = stick || this.nearBottom();
            this.messages.push(...fresh);
            this.since = Math.max(this.since || 0, ...fresh.map((message) => message.id));
            this.$nextTick(() => this.scrollBottom(follow));
        },

        async send() {
            if (!this.active || this.sending) return;
            if (!this.body.trim() && this.files.length === 0) return;
            this.sending = true;
            this.error = '';
            const form = new FormData();
            form.append('body', this.body.trim());
            if (this.reply) form.append('reply_to_id', String(this.reply.id));
            this.files.forEach((file) => form.append('fichiers[]', file));
            try {
                const res = await fetch(this.urls.base + '/' + this.active.id + '/messages', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    credentials: 'same-origin',
                    body: form,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.error = firstError(data);
                    return;
                }
                if (data.message) this.mergeMessages([data.message], true);
                this.body = '';
                this.files = [];
                this.reply = null;
                this.poll();
            } catch (_) {
                this.error = 'Envoi impossible.';
            } finally {
                this.sending = false;
            }
        },

        async createGroup() {
            this.formError = '';
            const form = new FormData();
            form.append('nom', this.createNom.trim());
            this.createSelected.forEach((id) => form.append('membres[]', String(id)));
            try {
                const res = await fetch(this.urls.store, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    credentials: 'same-origin',
                    body: form,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.formError = firstError(data);
                    return;
                }
                window.location = data.redirect;
            } catch (_) {
                this.formError = 'Création impossible.';
            }
        },

        async addMembers() {
            if (!this.active || this.addSelected.length === 0) return;
            this.formError = '';
            const form = new FormData();
            this.addSelected.forEach((id) => form.append('membres[]', String(id)));
            try {
                const res = await fetch(this.urls.base + '/' + this.active.id + '/membres', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    credentials: 'same-origin',
                    body: form,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.formError = firstError(data);
                    return;
                }
                this.members = data.members || this.members;
                this.directory = data.directory || [];
                this.mergeMessages(data.messages || [], true);
                this.addSelected = [];
                this.addQuery = '';
                this.poll();
            } catch (_) {
                this.formError = 'Ajout impossible.';
            }
        },

        async removeMember(member) {
            if (!this.active || !member.removable) return;
            const question = member.self ? 'Quitter ce groupe ?' : 'Retirer ' + member.name + ' du groupe ?';
            if (!window.confirm(question)) return;
            try {
                const res = await fetch(this.urls.base + '/' + this.active.id + '/membres/' + member.id, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.formError = firstError(data);
                    return;
                }
                if (data.left) {
                    this.groups = this.groups.filter((group) => group.id !== this.active.id);
                    this.active = null;
                    this.messages = [];
                    this.showInfo = false;
                    this.panel = 'list';
                    return;
                }
                this.members = data.members || [];
                this.mergeMessages(data.messages || [], true);
                this.poll();
            } catch (_) {
                this.formError = 'Action impossible.';
            }
        },
    }));
});

function bootDiscussionBadge() {
    if (document.getElementById('discussion-app')) return;
    const nav = document.getElementById('discussion-nav');
    if (!nav?.dataset.pollUrl) return;

    const poll = async () => {
        if (document.hidden) return;
        try {
            const res = await fetch(nav.dataset.pollUrl + '?resume=1', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();
            renderDiscussionBadge(data.unread);
        } catch (_) {}
    };

    poll();
    setInterval(poll, 15000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) poll();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootDiscussionBadge);
} else {
    bootDiscussionBadge();
}
