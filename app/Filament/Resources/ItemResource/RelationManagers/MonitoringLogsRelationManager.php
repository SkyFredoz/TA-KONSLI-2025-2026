<?php

namespace App\Filament\Resources\ItemResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MonitoringLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'monitoringLogs';

    protected static ?string $title = 'Monitoring Logs';

    protected static ?string $icon = 'heroicon-o-clipboard-document-list';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->disk('public')
                    ->width(80)
                    ->height(60)
                    ->circular(false),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'check-in' => 'success',
                        'check-out' => 'danger',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'check-in' => 'heroicon-o-arrow-down-tray',
                        'check-out' => 'heroicon-o-arrow-up-tray',
                    }),

                Tables\Columns\TextColumn::make('scanned_at')
                    ->label('Scanned At')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'check-in' => 'Check In',
                        'check-out' => 'Check Out',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('scanned_at', 'desc');
    }
}
