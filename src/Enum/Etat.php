<?php

namespace App\Enum;

enum Etat: string
{
    case realisee = 'realisee';
    case encours = 'encours';
    case Abandonner = 'Abandonner';
}