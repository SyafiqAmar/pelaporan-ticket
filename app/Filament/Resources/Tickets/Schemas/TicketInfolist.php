<?php

namespace App\Filament\Resources\Tickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('subject')
                                    ->label('Subject'),

                                TextEntry::make('creator.name')
                                    ->label('Created By'),

                                TextEntry::make('assignee.name')
                                    ->label('Assigned To')
                                    ->placeholder('Unassigned'),

                                TextEntry::make('category')
                                    ->label('Category'),

                                TextEntry::make('priority')
                                    ->label('Priority')
                                    ->badge(),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge(),
                            ]),
                    ]),

                Section::make('Description')
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->columnSpanFull(),
                    ]),

                Section::make('Attachment')
                    ->schema([
                        TextEntry::make('attachment_path')
                            ->label('File')
                            ->formatStateUsing(
                                fn ($state) => $state
                                    ? basename($state)
                                    : 'No attachment'
                            ),

                        ViewEntry::make('attachment_path')
                            ->view('filament.tickets.previewdetail')
                            ->visible(
                                fn ($record) => filled($record->attachment_path)
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}