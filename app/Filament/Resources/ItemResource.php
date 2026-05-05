<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Item Information')
                    ->description('Register a new item with its RFID tag.')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Forms\Components\TextInput::make('rfid_uid')
                            ->label('RFID UID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->autofocus()
                            ->placeholder('Scan or type RFID UID...')
                            ->helperText('Place your cursor here and scan the RFID tag, or type manually.')
                            ->prefixIcon('heroicon-o-signal'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Laptop Dell Latitude 5520')
                            ->prefixIcon('heroicon-o-tag'),

                        Forms\Components\Select::make('category')
                            ->required()
                            ->options([
                                'Electronics' => 'Electronics',
                                'Furniture' => 'Furniture',
                                'Stationery' => 'Stationery',
                                'Equipment' => 'Equipment',
                                'Vehicle' => 'Vehicle',
                                'Other' => 'Other',
                            ])
                            ->searchable()
                            ->prefixIcon('heroicon-o-folder'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rfid_uid')
                    ->label('RFID UID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Electronics' => 'info',
                        'Furniture' => 'warning',
                        'Stationery' => 'success',
                        'Equipment' => 'danger',
                        'Vehicle' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('monitoring_logs_count')
                    ->counts('monitoringLogs')
                    ->label('Total Scans')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'Electronics' => 'Electronics',
                        'Furniture' => 'Furniture',
                        'Stationery' => 'Stationery',
                        'Equipment' => 'Equipment',
                        'Vehicle' => 'Vehicle',
                        'Other' => 'Other',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MonitoringLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'view' => Pages\ViewItem::route('/{record}'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}
