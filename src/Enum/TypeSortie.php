<?php

namespace App\Enum;

enum TypeSortie: string
{
    case Alerte = 'ALERTE';
    case Prediction = 'PREDICTION';
    case Recommandation = 'RECOMMANDATION';
}