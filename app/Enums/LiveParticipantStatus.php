<?php

namespace App\Enums;

enum LiveParticipantStatus: string
{
    case Joined = 'joined';
    case Left = 'left';
}
