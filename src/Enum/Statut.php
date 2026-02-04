<?php

namespace App\Enum;

enum Statut: string
{
    case EnAttente = 'EN_ATTENTE';
    case EnCours   = 'EN_COURS';
    case Fini      = 'FINI';
    case Rejete    = 'REJETE';
} 