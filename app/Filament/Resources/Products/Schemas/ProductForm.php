<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Information')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                        if ($operation !== 'create') {
                                            return;
                                        }
                                        $set('slug', Str::slug($state));
                                    }),

                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->dehydrated(),

                                Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, Set $set) =>
                                                $set('slug', Str::slug($state))
                                            ),
                                        TextInput::make('slug')
                                            ->required()
                                            ->maxLength(255),
                                    ]),

                                RichEditor::make('description')
                                    ->maxLength(2000)
                                    ->columnSpanFull()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'bulletList',
                                        'orderedList',
                                    ]),
                            ])
                            ->columns(2),

                Section::make('Pricing & Inventory')
                            ->schema([
                                TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->maxValue(9999999999)
                                    ->default(0),

                                TextInput::make('stock')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->suffixIcon('heroicon-o-archive-box'),
                            ])
                            ->columns(2),

                // Right Column - Image & Status
                Group::make()
                    ->schema([
                        Section::make('Product Image')
                            ->schema([
                                FileUpload::make('image')
                                    ->image()
                                    ->directory('products')
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '1:1',
                                        '4:3',
                                        '16:9',
                                    ])
                                    ->maxSize(2048)
                                    ->helperText('Max 2MB. Recommended: 500x500px'),
                            ]),

                        Section::make('Status')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Inactive products won\'t appear in POS'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }
}
