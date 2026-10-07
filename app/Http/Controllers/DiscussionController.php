<?php

namespace App\Http\Controllers;

use App\Models\DiscussionGroup;
use App\Models\DiscussionMessageFile;
use App\Models\User;
use App\Services\DiscussionService;
use App\Support\PrivateFile;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DiscussionController extends Controller
{
    public function __construct(private DiscussionService $discussions) {}

    public function index(Request $request)
    {
        $groupe = $request->integer('groupe');
        $boot = $this->discussions->page($request->user(), $groupe > 0 ? $groupe : null);
        $boot['csrf'] = csrf_token();
        $boot['urls'] = [
            'poll' => route('discussions.poll'),
            'store' => route('discussions.store'),
            'base' => url('/discussions'),
        ];

        return view('discussions.index', [
            'title' => 'Discussion',
            'boot' => $boot,
        ]);
    }

    public function poll(Request $request)
    {
        $user = $request->user();

        if ($request->boolean('resume')) {
            return response()->json([
                'ok' => true,
                'unread' => $this->discussions->unreadTotal($user),
            ]);
        }

        $groupe = $request->integer('groupe');

        return response()->json($this->discussions->poll(
            $user,
            $groupe > 0 ? $groupe : null,
            max(0, $request->integer('since'))
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:80'],
            'membres' => ['nullable', 'array', 'max:50'],
            'membres.*' => ['integer', 'distinct'],
        ], [
            'nom.required' => 'Donnez un nom au groupe.',
        ]);

        $group = $this->discussions->createGroup(
            $request->user(),
            $data['nom'],
            $data['membres'] ?? []
        );

        return response()->json([
            'ok' => true,
            'redirect' => route('discussions.index', ['groupe' => $group->id]),
        ]);
    }

    public function messages(Request $request, DiscussionGroup $group)
    {
        $before = $request->integer('before');
        if ($before > 0) {
            return response()->json($this->discussions->older($request->user(), $group, $before));
        }

        return response()->json($this->discussions->open($request->user(), $group));
    }

    public function storeMessage(Request $request, DiscussionGroup $group)
    {
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'reply_to_id' => ['nullable', 'integer'],
            'fichiers' => ['nullable', 'array', 'max:5'],
            'fichiers.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip'],
        ], [
            'fichiers.max' => 'Cinq fichiers maximum par message.',
            'fichiers.*.max' => 'Chaque fichier doit faire moins de 10 Mo.',
            'fichiers.*.mimes' => 'Ce type de fichier n’est pas accepté.',
        ]);

        $files = $request->file('fichiers', []);
        if (! is_array($files)) {
            $files = [$files];
        }

        $body = trim((string) ($data['body'] ?? ''));
        if ($body === '' && $files === []) {
            throw ValidationException::withMessages([
                'body' => 'Écrivez un message ou joignez un fichier.',
            ]);
        }

        $message = $this->discussions->send(
            $request->user(),
            $group,
            $body,
            isset($data['reply_to_id']) ? (int) $data['reply_to_id'] : null,
            $files
        );

        return response()->json([
            'ok' => true,
            'message' => $message->presentFor($request->user()),
        ]);
    }

    public function storeMembers(Request $request, DiscussionGroup $group)
    {
        $data = $request->validate([
            'membres' => ['required', 'array', 'min:1', 'max:50'],
            'membres.*' => ['integer', 'distinct'],
        ], [
            'membres.required' => 'Sélectionnez au moins un membre.',
            'membres.min' => 'Sélectionnez au moins un membre.',
        ]);

        return response()->json($this->discussions->addMembers(
            $request->user(),
            $group,
            $data['membres']
        ));
    }

    public function destroyMember(Request $request, DiscussionGroup $group, User $user)
    {
        return response()->json($this->discussions->removeMember($request->user(), $group, $user));
    }

    public function destroy(Request $request, DiscussionGroup $group)
    {
        return response()->json($this->discussions->deleteGroup($request->user(), $group));
    }

    public function showFile(DiscussionMessageFile $file)
    {
        if ($file->isImage()) {
            return PrivateFile::inline($file->path, $file->nom);
        }

        return PrivateFile::download($file->path, $file->nom);
    }
}
