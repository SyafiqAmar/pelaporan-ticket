<?php

namespace App\Filament\Resources\Tickets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('subject')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('assignee.name')
                    ->label('Assigned To')
                    ->placeholder('Unassigned')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('priority')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                ->label('')
                ->icon('heroicon-o-eye')
                ->tooltip('View Ticket'),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
