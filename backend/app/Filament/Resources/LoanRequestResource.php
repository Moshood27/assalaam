<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanRequestResource\Pages;
use App\Models\QardHasan;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoanRequestResource extends Resource
{
    protected static ?string $model = QardHasan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Loan Management';
    protected static ?string $navigationLabel = 'Loan Requests';
    protected static ?int $navigationSort = 61;

    protected static ?string $slug = 'loan-requests';

    public static function form(Form $form): Form
    {
        return QardHasanResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return QardHasanResource::table($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return QardHasanResource::getBaseFilteredQuery()->where('status', 'pending');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanRequests::route('/'),
            'create' => Pages\CreateLoanRequest::route('/create'),
            'edit' => Pages\EditLoanRequest::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super_admin') || $user->is_admin || $user->can('view_any_qard_hasan');
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super_admin') || $user->is_admin || $user->can('create_qard_hasan');
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return $user->hasRole('super_admin') || $user->is_admin || $user->can('update_qard_hasan');
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return $user->hasRole('super_admin') || $user->can('delete_qard_hasan');
    }
}
