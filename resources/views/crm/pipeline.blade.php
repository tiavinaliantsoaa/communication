@php
    $title = 'CRM — Pipeline';
    $subtitle = 'Kanban des candidats par statut';
@endphp

@extends('layouts.app')

@section('content')
@livewire('crm.pipeline-board')
@endsection
