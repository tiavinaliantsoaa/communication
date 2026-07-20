<section>
    <header>
        <h2 class="text-lg font-medium text-slate-900">
            Informations du profil
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            Mettez à jour votre photo, votre nom et votre adresse e-mail.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6" x-data="{ preview: null, removeAvatar: false }">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="avatar" value="Photo de profil" />
            <div class="mt-3 flex items-center gap-4">
                <div class="relative h-20 w-20 shrink-0">
                    <template x-if="preview">
                        <img :src="preview" alt="Aperçu" class="h-20 w-20 rounded-full object-cover ring-2 ring-white shadow border border-slate-200">
                    </template>
                    <template x-if="!preview && !removeAvatar && {{ $user->avatar_url ? 'true' : 'false' }}">
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-20 w-20 rounded-full object-cover ring-2 ring-white shadow border border-slate-200">
                    </template>
                    <template x-if="!preview && (removeAvatar || {{ $user->avatar_url ? 'false' : 'true' }})">
                        <div class="h-20 w-20 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-2xl font-semibold ring-2 ring-white shadow">
                            {{ $user->initials() }}
                        </div>
                    </template>
                </div>

                <div class="min-w-0 flex-1 space-y-2">
                    <input
                        id="avatar"
                        name="avatar"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        x-ref="avatarInput"
                        class="block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-escm-primary file:text-white hover:file:bg-escm-primary-dark"
                        @change="
                            removeAvatar = false;
                            const file = $event.target.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = e => preview = e.target.result;
                                reader.readAsDataURL(file);
                            } else {
                                preview = null;
                            }
                        "
                    >
                    <p class="text-xs text-slate-500">JPG, PNG, WEBP ou GIF — max 2 Mo</p>

                    @if ($user->avatar_url)
                        <label class="inline-flex items-center gap-2 text-xs text-red-600 cursor-pointer">
                            <input type="checkbox" name="remove_avatar" value="1" x-model="removeAvatar" @change="if (removeAvatar) { preview = null; if ($refs.avatarInput) $refs.avatarInput.value = ''; }" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                            Supprimer la photo actuelle
                        </label>
                    @endif
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        <div>
            <x-input-label for="name" value="Nom" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-slate-800">
                        Votre adresse e-mail n’est pas vérifiée.

                        <button form="send-verification" class="underline text-sm text-slate-600 hover:text-slate-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-escm-primary">
                            Renvoyer l’e-mail de vérification
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            Un nouveau lien de vérification a été envoyé.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Enregistrer</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-600"
                >Enregistré.</p>
            @endif
        </div>
    </form>
</section>
