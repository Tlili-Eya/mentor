<?php

namespace App\Enum;

enum StatutSortie: string
{
    case Nouveau = 'NOUVEAU';
    case Planifie = 'PLANIFIE';
    case Traitee = 'TRAITEE';
    case Ignore = 'IGNORE';

    public function getLabel(): string
    {
        return match($this) {
            self::Nouveau => 'Nouveau',
            self::Planifie => 'Planifié',
            self::Traitee => 'Traitée',
            self::Ignore => 'Ignoré',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::Nouveau => 'info',
            self::Planifie => 'success',
            self::Traitee => 'success',
            self::Ignore => 'secondary',
        };
    }
}
