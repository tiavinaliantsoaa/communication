<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderByDesc('created_at')->paginate(10);

        return view('users.index', compact('users'));
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function create()
    {
        return view('users.create', ['roles' => User::ROLES]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        $created = User::create($validated);

        app(ActivityLogger::class)->log(
            'user',
            auth()->user()->name.' a créé l\'utilisateur « '.$created->name.' »',
            auth()->user(),
            'create',
            'Utilisateurs',
            route('users.index'),
            $created
        );

        return redirect()->route('users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(User $user)
    {
        return view('users.edit', ['user' => $user, 'roles' => User::ROLES]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        app(ActivityLogger::class)->log(
            'user',
            auth()->user()->name.' a modifié l\'utilisateur « '.$user->name.' »',
            auth()->user(),
            'update',
            'Utilisateurs',
            route('users.index'),
            $user
        );

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $name = $user->name;
        $user->delete();

        app(ActivityLogger::class)->log(
            'user',
            auth()->user()->name.' a supprimé l\'utilisateur « '.$name.' »',
            auth()->user(),
            'delete',
            'Utilisateurs',
            route('users.index')
        );

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }
}
