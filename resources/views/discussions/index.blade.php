@extends('layouts.app')

@section('content')
<div
    id="discussion-app"
    class="discussion-shell"
    :class="panel === 'chat' ? 'is-chat' : 'is-list'"
    x-data="discussionApp({{ \Illuminate\Support\Js::from($boot) }})"
    x-cloak
    @keydown.escape.window="showCreate = false; showInfo = false"
>
    <aside class="disc-list">
        <header class="disc-list-head">
            <h2>Discussions</h2>
            <button type="button" class="icon-btn" title="Nouveau groupe" @click="showCreate = true; formError = ''">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/></svg>
            </button>
        </header>
        <div class="disc-search">
            <input type="search" x-model="search" placeholder="Rechercher une discussion" autocomplete="off">
        </div>
        <div class="disc-groups">
            <template x-for="group in filteredGroups" :key="group.id">
                <button type="button" class="disc-item" :class="active && active.id === group.id ? 'is-active' : ''" @click="openGroup(group.id)">
                    <span class="avatar" :style="`background:${group.color}`" x-text="group.initials"></span>
                    <span class="disc-item-body">
                        <span class="disc-item-top">
                            <span class="disc-name" x-text="group.nom"></span>
                            <span class="disc-time" x-text="group.heure"></span>
                        </span>
                        <span class="disc-item-bottom">
                            <span class="disc-preview" x-text="group.apercu"></span>
                            <span class="unread" x-show="group.unread > 0" x-text="group.unread > 9 ? '9+' : group.unread"></span>
                        </span>
                    </span>
                </button>
            </template>
            <p class="disc-empty" x-show="filteredGroups.length === 0">Aucun groupe pour le moment.</p>
        </div>
    </aside>

    <section class="disc-chat">
        <template x-if="!active">
            <div class="disc-placeholder">
                <div class="placeholder-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <h3>Sélectionnez une discussion</h3>
                    <p>Seuls les membres ajoutés à la création ou plus tard voient les messages du groupe.</p>
                </div>
            </div>
        </template>

        <template x-if="active">
            <div class="disc-thread-wrap">
                <header class="disc-chat-head">
                    <button type="button" class="icon-btn mobile-back" @click="panel = 'list'" title="Retour">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" class="chat-id" @click="showInfo = true; formError = ''">
                        <span class="avatar sm" :style="`background:${active.color}`" x-text="active.initials"></span>
                        <span>
                            <strong x-text="active.nom"></strong>
                            <small x-text="members.length + (members.length > 1 ? ' membres' : ' membre')"></small>
                        </span>
                    </button>
                </header>

                <div class="thread" x-ref="thread">
                    <button type="button" class="older" x-show="hasMore" @click="loadOlder" :disabled="loadingOlder" x-text="loadingOlder ? 'Chargement…' : 'Messages précédents'"></button>
                    <template x-for="item in timeline" :key="item.key">
                        <div :id="item.kind === 'msg' ? 'msg-' + item.id : null">
                            <div class="day-chip" x-show="item.kind === 'day'" x-text="item.label"></div>
                            <div class="system-pill" x-show="item.kind === 'msg' && item.system" x-text="item.body"></div>
                            <div
                                class="bubble-row"
                                x-show="item.kind === 'msg' && !item.system"
                                :class="(item.mine ? 'mine' : 'theirs') + (item.overlap ? ' overlap' : '')"
                            >
                                <button type="button" class="reply-btn" title="Répondre" @click="startReply(item)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h10a5 5 0 015 5v2M3 10l5-5M3 10l5 5"/></svg>
                                </button>
                                <div class="bubble" :class="item.mine ? 'mine' : 'theirs'">
                                    <div class="author" x-show="!item.mine && !item.overlap" :style="`color:${item.color}`" x-text="item.author"></div>
                                    <button type="button" class="reply-quote" x-show="item.reply" @click="jumpTo(item.reply.id)">
                                        <span class="reply-bar" :style="item.reply ? `background:${item.reply.color}` : ''"></span>
                                        <span>
                                            <span class="reply-author" :style="item.reply ? `color:${item.reply.color}` : ''" x-text="item.reply ? item.reply.author : ''"></span>
                                            <span class="reply-excerpt" x-text="item.reply ? item.reply.excerpt : ''"></span>
                                        </span>
                                    </button>
                                    <template x-for="file in (item.files || [])" :key="file.id">
                                        <div class="file-block">
                                            <a x-show="file.image" :href="file.url" target="_blank" rel="noopener">
                                                <img :src="file.url" :alt="file.nom">
                                            </a>
                                            <a class="file-card" x-show="!file.image" :href="file.url">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M14 3v6h6"/></svg>
                                                <span>
                                                    <strong x-text="file.nom"></strong>
                                                    <small x-text="file.taille"></small>
                                                </span>
                                            </a>
                                        </div>
                                    </template>
                                    <p class="bubble-text" x-show="item.body" x-text="item.body"></p>
                                    <span class="time" x-text="item.time"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <p class="form-error" x-show="error" x-text="error"></p>
                <div class="file-picks" x-show="files.length">
                    <template x-for="(file, index) in files" :key="file.name + index">
                        <span class="file-pick">
                            <span x-text="file.name"></span>
                            <button type="button" @click="removeFile(index)" title="Retirer">×</button>
                        </span>
                    </template>
                </div>
                <div class="reply-dock" x-show="reply">
                    <span class="reply-bar" :style="reply ? `background:${reply.color}` : ''"></span>
                    <span>
                        <span class="reply-author" :style="reply ? `color:${reply.color}` : ''" x-text="reply ? reply.author : ''"></span>
                        <span class="reply-excerpt" x-text="reply ? reply.excerpt : ''"></span>
                    </span>
                    <button type="button" class="icon-btn" @click="reply = null" title="Annuler la réponse">×</button>
                </div>
                <form class="composer" @submit.prevent="send">
                    <label class="icon-btn clip" title="Joindre un fichier">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <input type="file" class="sr-only" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,image/*" @change="onFiles">
                    </label>
                    <textarea x-ref="composer" x-model="body" rows="1" placeholder="Écrire un message" @keydown="onKeydown"></textarea>
                    <button type="submit" class="send" :disabled="sending" title="Envoyer">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3.4 20.6l17.2-8.2c.7-.3.7-1.3 0-1.6L3.4 2.6c-.7-.3-1.5.3-1.3 1.1L4 10.2c.1.3.3.5.6.6l8.2 1.2-8.2 1.2c-.3.1-.5.3-.6.6l-1.9 6.5c-.2.8.6 1.4 1.3 1.1z"/></svg>
                    </button>
                </form>
            </div>
        </template>
    </section>

    <div class="modal" x-show="showCreate" x-cloak>
        <div class="modal-backdrop" @click="showCreate = false"></div>
        <form class="sheet" @submit.prevent="createGroup">
            <header>
                <h3>Nouveau groupe</h3>
                <button type="button" class="icon-btn" @click="showCreate = false" title="Fermer">×</button>
            </header>
            <label class="field">
                <span>Nom du groupe</span>
                <input type="text" x-model="createNom" maxlength="80" required placeholder="Ex. Équipe communication">
            </label>
            <label class="field">
                <span>Ajouter des membres</span>
                <input type="search" x-model="createQuery" placeholder="Rechercher un membre" autocomplete="off">
            </label>
            <div class="chips" x-show="createSelected.length">
                <template x-for="person in peopleByIds(annuaire, createSelected)" :key="person.id">
                    <button type="button" class="chip" @click="toggle('createSelected', person.id)">
                        <span x-text="person.name"></span>
                        <span>×</span>
                    </button>
                </template>
            </div>
            <div class="people">
                <template x-for="person in filteredAnnuaire" :key="person.id">
                    <label class="person">
                        <span class="avatar sm" :style="`background:${person.color}`">
                            <img x-show="person.avatar_url" :src="person.avatar_url" alt="">
                            <span x-show="!person.avatar_url" x-text="person.initials"></span>
                        </span>
                        <span x-text="person.name"></span>
                        <input type="checkbox" class="check" :checked="createSelected.includes(person.id)" @change="toggle('createSelected', person.id)">
                    </label>
                </template>
                <p class="disc-empty" x-show="filteredAnnuaire.length === 0">Aucun membre à ajouter dans ce département.</p>
            </div>
            <p class="form-error" x-show="formError" x-text="formError"></p>
            <button type="submit" class="primary">Créer le groupe</button>
        </form>
    </div>

    <div class="drawer" x-show="showInfo && active" x-cloak>
        <div class="modal-backdrop" @click="showInfo = false"></div>
        <aside class="sheet info-sheet">
            <header>
                <h3 x-text="active ? active.nom : ''"></h3>
                <button type="button" class="icon-btn" @click="showInfo = false" title="Fermer">×</button>
            </header>
            <p class="hint">Visibles uniquement par les membres du groupe.</p>
            <div class="people">
                <template x-for="member in members" :key="member.id">
                    <div class="person">
                        <span class="avatar sm" :style="`background:${member.color}`">
                            <img x-show="member.avatar_url" :src="member.avatar_url" alt="">
                            <span x-show="!member.avatar_url" x-text="member.initials"></span>
                        </span>
                        <span>
                            <strong x-text="member.name"></strong>
                            <small x-show="member.admin">Admin</small>
                        </span>
                        <button type="button" class="text-btn" x-show="member.removable" @click="removeMember(member)" x-text="member.self ? 'Quitter' : 'Retirer'"></button>
                    </div>
                </template>
            </div>
            <h4>Ajouter un membre</h4>
            <label class="field">
                <input type="search" x-model="addQuery" placeholder="Rechercher un membre" autocomplete="off">
            </label>
            <div class="people short">
                <template x-for="person in filteredDirectory" :key="person.id">
                    <label class="person">
                        <span class="avatar sm" :style="`background:${person.color}`">
                            <img x-show="person.avatar_url" :src="person.avatar_url" alt="">
                            <span x-show="!person.avatar_url" x-text="person.initials"></span>
                        </span>
                        <span x-text="person.name"></span>
                        <input type="checkbox" class="check" :checked="addSelected.includes(person.id)" @change="toggle('addSelected', person.id)">
                    </label>
                </template>
                <p class="disc-empty" x-show="filteredDirectory.length === 0">Tout le département est déjà dans le groupe.</p>
            </div>
            <p class="form-error" x-show="formError" x-text="formError"></p>
            <button type="button" class="primary" @click="addMembers" :disabled="addSelected.length === 0">Ajouter</button>
        </aside>
    </div>
</div>

<style>
    .discussion-shell { margin: 0 -1rem -1rem; height: calc(100dvh - 7.25rem); min-height: 0; display: flex; background: #fff; color: #111b21; position: relative; }
    @media (min-width: 640px) { .discussion-shell { margin: 0 -1.5rem -1.5rem; height: calc(100dvh - 7.75rem); } }
    @media (min-width: 1024px) { .discussion-shell { margin: 0 -2rem -2rem; height: calc(100dvh - 8.25rem); } }
    .disc-list { width: 100%; max-width: 22.5rem; border-right: 1px solid #e9edef; display: flex; flex-direction: column; background: #fff; min-height: 0; }
    .disc-list-head, .disc-chat-head, .sheet header { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .85rem 1rem; background: #f0f2f5; }
    .disc-list-head h2, .sheet header h3 { font-size: 1.05rem; font-weight: 700; }
    .icon-btn, .send { width: 2.25rem; height: 2.25rem; border: 0; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: transparent; color: #54656f; cursor: pointer; }
    .icon-btn svg, .send svg, .reply-btn svg, .file-card svg, .placeholder-card svg { width: 1.25rem; height: 1.25rem; }
    .disc-search, .field { padding: .55rem .85rem; }
    .disc-search input, .field input, .composer textarea { width: 100%; border: 0; border-radius: 8px; background: #f0f2f5; padding: .65rem .8rem; font-size: .92rem; color: #111b21; }
    .disc-search input:focus, .field input:focus, .composer textarea:focus { outline: 2px solid #00a88433; }
    .disc-groups, .people, .thread { overflow-y: auto; min-height: 0; }
    .disc-groups { flex: 1; }
    .disc-item { width: 100%; display: flex; gap: .75rem; padding: .7rem 1rem; text-align: left; background: #fff; border: 0; border-bottom: 1px solid #f0f2f5; cursor: pointer; }
    .disc-item.is-active, .disc-item:hover { background: #f5f6f6; }
    .avatar { width: 3rem; height: 3rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; flex-shrink: 0; overflow: hidden; }
    .avatar.sm { width: 2.4rem; height: 2.4rem; font-size: .8rem; }
    .avatar img { width: 100%; height: 100%; object-fit: cover; }
    .disc-item-body { min-width: 0; flex: 1; }
    .disc-item-top, .disc-item-bottom { display: flex; justify-content: space-between; gap: .5rem; }
    .disc-name { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .disc-time, .disc-preview, .time, .hint, .person small { color: #667781; font-size: .75rem; }
    .disc-preview { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .unread { min-width: 1.25rem; height: 1.25rem; padding: 0 .3rem; border-radius: 999px; background: #25d366; color: #fff; font-size: .7rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
    .disc-chat { flex: 1; min-width: 0; min-height: 0; display: flex; background: #efeae2; }
    .disc-placeholder, .disc-thread-wrap { flex: 1; min-width: 0; min-height: 0; display: flex; flex-direction: column; }
    .placeholder-card { margin: auto; max-width: 22rem; text-align: center; color: #54656f; }
    .placeholder-card svg { width: 3rem; height: 3rem; margin: 0 auto 1rem; }
    .placeholder-card h3 { color: #111b21; font-size: 1.15rem; font-weight: 650; margin-bottom: .4rem; }
    .chat-id { display: flex; align-items: center; gap: .7rem; background: transparent; border: 0; text-align: left; cursor: pointer; }
    .chat-id strong { display: block; }
    .chat-id small { color: #667781; }
    .thread { flex: 1; padding: 1rem .8rem 1.2rem; background-color: #efeae2; background-image: radial-gradient(rgba(17,27,33,.045) .7px, transparent .7px); background-size: 16px 16px; }
    .day-chip, .system-pill { width: fit-content; margin: .7rem auto; background: #fff; color: #54656f; font-size: .75rem; padding: .3rem .7rem; border-radius: 8px; box-shadow: 0 1px 0.5px rgba(11,20,26,.13); }
    .bubble-row { width: 100%; display: flex; align-items: flex-end; gap: .35rem; margin-top: .35rem; }
    .bubble-row.mine { justify-content: flex-end; }
    .bubble-row.theirs .reply-btn { order: 2; }
    .bubble-row.overlap { margin-top: -7px; position: relative; z-index: 2; }
    .bubble { max-width: min(78%, 32rem); padding: .35rem .5rem .25rem; border-radius: 8px; box-shadow: 0 1px 0.5px rgba(11,20,26,.13); position: relative; }
    .bubble.mine { background: #d9fdd3; border-top-right-radius: 0; }
    .bubble.theirs { background: #fff; border-top-left-radius: 0; }
    .bubble-row.overlap .bubble { box-shadow: 0 1px 0 rgba(11,20,26,.08); }
    .author { font-size: .78rem; font-weight: 700; margin-bottom: .15rem; }
    .bubble-text { white-space: pre-wrap; overflow-wrap: anywhere; font-size: .92rem; line-height: 1.35; }
    .time { display: block; text-align: right; font-size: .68rem; color: #667781; margin-top: .15rem; }
    .reply-btn { opacity: .55; border: 0; background: transparent; color: #54656f; cursor: pointer; }
    .bubble-row:hover .reply-btn { opacity: 1; }
    .reply-quote, .reply-dock { display: flex; gap: .5rem; align-items: stretch; text-align: left; }
    .reply-quote { width: 100%; margin: -2px -2px 6px; padding: .4rem .5rem; border: 0; border-radius: 7px; background: rgba(0,0,0,.05); cursor: pointer; position: relative; z-index: 1; }
    .reply-bar { width: 4px; border-radius: 4px; flex-shrink: 0; }
    .reply-author, .reply-excerpt { display: block; }
    .reply-author { font-size: .75rem; font-weight: 700; }
    .reply-excerpt { font-size: .78rem; color: #3b4a54; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 16rem; }
    .file-block img { display: block; max-height: 16rem; border-radius: 6px; margin-bottom: .25rem; }
    .file-card { display: flex; gap: .55rem; align-items: center; background: rgba(0,0,0,.04); border-radius: 8px; padding: .45rem .55rem; margin-bottom: .3rem; color: inherit; text-decoration: none; }
    .file-card strong, .file-card small { display: block; }
    .reply-dock { margin: 0 .6rem -12px; padding: .55rem .7rem .9rem; background: #fff; border-radius: 12px 12px 0 0; position: relative; z-index: 1; box-shadow: 0 1px 0.5px rgba(11,20,26,.13); }
    .composer { display: flex; align-items: flex-end; gap: .4rem; padding: .45rem .6rem .7rem; background: #f0f2f5; position: relative; z-index: 2; }
    .composer textarea { resize: none; max-height: 7rem; min-height: 2.6rem; }
    .send { background: #00a884; color: #fff; }
    .send:disabled { opacity: .6; }
    .clip { cursor: pointer; }
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
    .file-picks { display: flex; flex-wrap: wrap; gap: .35rem; padding: .4rem .8rem 0; background: #f0f2f5; }
    .file-pick { display: inline-flex; gap: .35rem; align-items: center; background: #fff; border-radius: 999px; padding: .2rem .55rem; font-size: .75rem; }
    .file-pick button { border: 0; background: transparent; cursor: pointer; }
    .form-error { color: #b42318; font-size: .8rem; padding: .25rem .9rem; background: #efeae2; }
    .older { display: block; margin: 0 auto .6rem; border: 0; background: #fff; border-radius: 999px; padding: .35rem .8rem; font-size: .75rem; color: #54656f; cursor: pointer; }
    .modal, .drawer { position: absolute; inset: 0; z-index: 30; display: flex; align-items: center; justify-content: center; }
    .drawer { justify-content: flex-end; }
    .modal-backdrop { position: absolute; inset: 0; background: rgba(11,20,26,.45); }
    .sheet { position: relative; z-index: 1; width: min(28rem, calc(100% - 1.5rem)); max-height: calc(100% - 1.5rem); overflow: auto; background: #fff; border-radius: 12px; box-shadow: 0 12px 40px rgba(11,20,26,.2); display: flex; flex-direction: column; }
    .info-sheet { height: 100%; max-height: 100%; width: min(24rem, 100%); border-radius: 0; }
    .field span, .sheet h4 { display: block; font-size: .75rem; font-weight: 700; color: #54656f; margin-bottom: .3rem; }
    .sheet h4 { padding: .8rem 1rem 0; }
    .hint { padding: 0 1rem; }
    .people { padding: .2rem 0 .6rem; }
    .people.short { max-height: 12rem; }
    .person { display: flex; align-items: center; gap: .7rem; padding: .45rem 1rem; }
    .person > span:nth-child(2) { flex: 1; min-width: 0; }
    .check { width: 1.15rem; height: 1.15rem; accent-color: #00a884; }
    .chips { display: flex; flex-wrap: wrap; gap: .35rem; padding: 0 .85rem .4rem; }
    .chip { border: 0; background: #e7fce3; color: #027d5d; border-radius: 999px; padding: .25rem .6rem; display: inline-flex; gap: .3rem; cursor: pointer; }
    .primary { margin: .4rem 1rem 1rem; border: 0; border-radius: 999px; background: #00a884; color: #fff; font-weight: 700; padding: .7rem 1rem; cursor: pointer; }
    .primary:disabled { opacity: .45; cursor: default; }
    .text-btn { border: 0; background: transparent; color: #c4352d; font-size: .75rem; font-weight: 700; cursor: pointer; }
    .disc-empty { padding: 1rem; color: #667781; font-size: .85rem; text-align: center; }
    .is-flash .bubble { animation: disc-flash 1.1s ease; }
    @keyframes disc-flash { 40% { filter: brightness(.9); } }
    .mobile-back { display: none; }
    @media (max-width: 767px) {
        .discussion-shell.is-list .disc-chat { display: none; }
        .discussion-shell.is-chat .disc-list { display: none; }
        .disc-list { max-width: none; }
        .mobile-back { display: inline-flex; }
    }
</style>
@endsection
