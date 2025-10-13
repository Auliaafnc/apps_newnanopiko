<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PointMinimumResource\Pages;
use App\Models\PointMinimum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class PointMinimumResource extends Resource
{
    protected static ?string $model = PointMinimum::class;

   
    protected static ?string $navigationGroup = 'Loyalty';
    protected static ?string $navigationLabel = 'Minimum Poin';
    protected static ?string $modelLabel       = 'Minimum Poin';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pengaturan Minimum')
                    ->schema([
                        Forms\Components\Radio::make('type')
                            ->label('Jenis Minimum')
                            ->options([
                                'reward'  => 'Reward',
                                'program' => 'Program',
                            ])
                            ->inline()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Jika pindah ke Reward, kosongkan program_id
                                if ($state === 'reward') {
                                    $set('program_id', null);
                                }
                            }),

                        Forms\Components\Select::make('program_id')
                            ->label('Pilih Program')
                            ->relationship('program', 'name') // relasi ke App\Models\CustomerProgram
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('type') === 'program')
                            ->required(fn (Get $get) => $get('type') === 'program')
                            ->dehydrated(fn (Get $get) => $get('type') === 'program')
                            ->placeholder('Pilih salah satu program'),

                        Forms\Components\TextInput::make('min_amount')
                            ->label('Minimum Pembelian (Rp)')
                            ->numeric()
                            ->minValue(0)
                            ->step(1000)
                            ->prefix('Rp')
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktifkan')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipe')
                    ->colors([
                        'primary' => 'reward',
                        'success' => 'program',
                    ])
                    ->formatStateUsing(fn (string $state) => $state === 'reward' ? 'Reward' : 'Program')
                    ->sortable(),

                Tables\Columns\TextColumn::make('program.name')
                    ->label('Program')
                    ->placeholder('—')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('min_amount')
                    ->label('Minimum (Rp)')
                    ->numeric(thousandsSeparator: '.', decimalSeparator: ',', decimalPlaces: 0)
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d M Y H:i')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'reward'  => 'Reward',
                        'program' => 'Program',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Aktif'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
        
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
                ]);
    }

    public static function getRelations(): array
    {
        return [
            // Tidak ada relation manager khusus
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPointMinimums::route('/'),
            'create' => Pages\CreatePointMinimum::route('/create'),
            'edit'   => Pages\EditPointMinimum::route('/{record}/edit'),
        ];
    }
}
