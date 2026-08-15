<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use App\Models\User;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('subject')
                    ->required(),
                    
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('category')
                    ->required(),

                Select::make('priority')
                    ->options(TicketPriority::class)
                    ->required(),

                Select::make('assigned_to')
                    ->label('Assigned To')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->hiddenOn('create'),

                Select::make('status')
                    ->options(TicketStatus::class)
                    ->required()
                    ->hiddenOn('create'),
                    
                FileUpload::make('attachment_path')
                    ->disk('public')
                    ->directory('tickets')
                    ->nullable(),
            ]);
    }
}
