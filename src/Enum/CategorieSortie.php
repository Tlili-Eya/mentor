<?php
// src/Enum/CategorieSortie.php

namespace App\Enum;

enum CategorieSortie: string
{
    case Pedagogique = 'PEDAGOGIQUE';
    case Strategique = 'STRATEGIQUE';
    case Administrative = 'ADMINISTRATIVE';
    

    public function getLabel(): string
    {
        return match($this) {
            self::Pedagogique => 'Pédagogique',
            self::Strategique => 'Stratégique',
            self::Administrative => 'Administrative',
        };
    }
    
    public static function getChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->getLabel()] = $case->value;
        }
        return $choices;
    }
}