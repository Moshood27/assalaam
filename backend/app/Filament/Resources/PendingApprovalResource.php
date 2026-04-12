<?php

namespace App\Filament\Resources;

use App\Models\TransactionApproval;
use App\Models\QardHasan;
use App\Models\WithdrawalRequest;
use App\Models\ExpenseEntry;
use App\Models\CharityEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PendingApprovalResource extends Resource
{
    protected static ?string $model = TransactionApproval::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Security & Audit';
    protected static ?string $navigationLabel = 'My Pending Approvals';
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // This is a bit tricky since TransactionApproval table tracks COMPLETED approvals.
                // We actually want to show things that NEED approval.
                // But to keep it simple and within the Filament Resource pattern,
                // we can show all high-value pending entities.

                // However, a better way is to use a custom page or a resource that
                // joins all models. For now, let's show COMPLETED approvals
                // and a separate section for what's MISSING.

                // Actually, let's make this resource represent "Requests Awaiting My Signature".
                // Since we don't have a single table for "Pending Requests",
                // we'll have to be creative or just list the models.

                return TransactionApproval::query()->where('approver_id', auth()->id());
            })
            ->columns([
                TextColumn::make('approvable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => Str::afterLast($state, '\\')),
                TextColumn::make('approvable_id')->label('ID'),
                TextColumn::make('status')->badge()->color('success'),
                TextColumn::make('responded_at')->label('Signed At')->dateTime(),
            ])
            ->emptyStateHeading('You have no signed approvals yet.')
            ->description('This list shows transactions you have already signed. Use the respective modules (Loans, Withdrawals, Expenses) to find pending items to sign.');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'Chairman', 'Sharia Auditor']);
    }

    public static function getPages(): array
    {
        return [
            'index' => PendingApprovalResource\Pages\ListPendingApprovals::route('/'),
        ];
    }
}
