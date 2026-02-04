<?php

namespace App\Enum;

enum Cible: string
{
    case Etudiant = 'ETUDIANT';
    case Classe = 'CLASSE';
    case Enseignant = 'ENSEIGNANT';
    case Administrateur = 'ADMINISTRATEUR';
}