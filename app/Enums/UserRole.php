<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel, HasColor, HasIcon
{
    case Manager  = 'manager';
    case Cashier  = 'cashier';
    case Kitchen  = 'kitchen';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manager => 'Manager',
            self::Cashier => 'Cashier',
            self::Kitchen => 'Kitchen',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Manager => 'danger',
            self::Cashier => 'success',
            self::Kitchen => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Manager => 'heroicon-o-shield-check',
            self::Cashier => 'heroicon-o-computer-desktop',
            self::Kitchen => 'heroicon-o-fire',
        };
    }
}
