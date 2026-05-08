<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonitoringLogResource\Pages;
use App\Models\MonitoringLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MonitoringLogResource extends Resource
{
    protected static ?string $model = MonitoringLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Scan Logs';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->disk('public')
                    ->width(80)
                    ->height(60)
                    ->circular(false),

                Tables\Columns\TextColumn::make('item.name')
                    ->label('Item Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('item.rfid_uid')
                    ->label('RFID UID')
                    ->searchable()
                    ->badge()
                    ->color('primary')
                    ->copyable(),

                Tables\Columns\TextColumn::make('item.category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Electronics' => 'info',
                        'Furniture' => 'warning',
                        'Stationery' => 'success',
                        'Equipment' => 'danger',
                        'Vehicle' => 'gray',
                        default => 'secondary',
                    }),

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

                Tables\Filters\SelectFilter::make('item_id')
                    ->label('Item')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('scanned_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitoringLogs::route('/'),
        ];
    }
}
