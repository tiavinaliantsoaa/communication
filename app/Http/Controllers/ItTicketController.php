<?php

namespace App\Http\Controllers;

use App\Models\ItTicket;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $statut = $request->string('statut')->toString();

        $tickets = ItTicket::query()
            ->with(['user:id,name,email'])
            ->when(
                ! $user->isSuperAdmin() && ! $user->isAdmin(),
                fn ($q) => $q->where('user_id', $user->id)
            )
            ->when(
                $statut !== '' && isset(ItTicket::STATUTS[$statut]),
                fn ($q) => $q->where('statut', $statut)
            )
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('it-tickets.index', [
            'tickets' => $tickets,
            'statuts' => ItTicket::STATUTS,
            'statutActif' => $statut,
            'canManage' => $user->isSuperAdmin(),
        ]);
    }

    public function create()
    {
        return view('it-tickets.create', [
            'impacts' => ItTicket::IMPACTS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'resultat_attendu' => ['required', 'string', 'max:5000'],
            'impact' => ['required', Rule::in(array_keys(ItTicket::IMPACTS))],
        ], [
            'titre.required' => 'Le titre est obligatoire.',
            'description.required' => 'Décrivez votre requête.',
            'resultat_attendu.required' => 'Indiquez le résultat attendu.',
            'impact.required' => 'Sélectionnez l’impact du problème.',
            'impact.in' => 'Impact invalide.',
        ]);

        $user = auth()->user();

        $ticket = ItTicket::create([
            ...$validated,
            'user_id' => $user->id,
            'departement_id' => $user->currentDepartementId(),
            'statut' => 'en_attente',
        ]);

        app(ActivityLogger::class)->log(
            'it_ticketing',
            $user->name.' a créé le ticket IT « '.$ticket->titre.' »',
            $user,
            'create',
            'IT Ticketing',
            route('it-tickets.show', $ticket),
            $ticket
        );

        return redirect()->route('it-tickets.show', $ticket)
            ->with('success', 'Votre requête a été envoyée. Vous pouvez suivre son avancement ici.');
    }

    public function show(ItTicket $ticket)
    {
        abort_unless($ticket->canBeViewedBy(), 403);

        $ticket->load(['user:id,name,email', 'updatedBy:id,name']);

        return view('it-tickets.show', [
            'ticket' => $ticket,
            'statuts' => ItTicket::STATUTS,
            'canManage' => $ticket->canBeManagedBy(),
        ]);
    }

    public function updateStatus(Request $request, ItTicket $ticket)
    {
        abort_unless($ticket->canBeManagedBy(), 403);

        $validated = $request->validate([
            'statut' => ['required', Rule::in(array_keys(ItTicket::STATUTS))],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'statut.required' => 'Sélectionnez un statut.',
            'statut.in' => 'Statut invalide.',
        ]);

        $old = $ticket->statut;
        $user = auth()->user();

        $ticket->update([
            'statut' => $validated['statut'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'updated_by' => $user->id,
            'statut_updated_at' => now(),
        ]);

        app(ActivityLogger::class)->log(
            'it_ticketing',
            $user->name.' a passé le ticket « '.$ticket->titre.' » de '
                .(ItTicket::STATUTS[$old] ?? $old)
                .' à '
                .$ticket->statut_label,
            $user,
            'update',
            'IT Ticketing',
            route('it-tickets.show', $ticket),
            $ticket
        );

        return redirect()->route('it-tickets.show', $ticket)
            ->with('success', 'Statut du ticket mis à jour.');
    }

    public function destroy(ItTicket $ticket)
    {
        $user = auth()->user();
        $isOwner = (int) $ticket->user_id === (int) $user->id;
        abort_unless($user->isSuperAdmin() || ($isOwner && $ticket->statut === 'en_attente'), 403);

        $titre = $ticket->titre;
        $ticket->delete();

        app(ActivityLogger::class)->log(
            'it_ticketing',
            $user->name.' a supprimé le ticket IT « '.$titre.' »',
            $user,
            'delete',
            'IT Ticketing',
            route('it-tickets.index')
        );

        return redirect()->route('it-tickets.index')
            ->with('success', 'Ticket supprimé.');
    }
}
