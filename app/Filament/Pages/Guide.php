<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Guide extends Page
{
    protected string $view = 'filament.pages.guide';

    protected static ?string $navigationLabel = 'Start here';

    protected static ?string $title = 'Your first five minutes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 0;
}
