<?php

namespace App\Enum;

enum TypeRessource: string
{
    case IMAGE    = 'IMAGE';
    case PDF      = 'PDF';
    case VIDEO    = 'VIDEO';
    case DOCUMENT = 'DOCUMENT';
    case ARCHIVE  = 'ARCHIVE';
    case OTHER    = 'OTHER';
}