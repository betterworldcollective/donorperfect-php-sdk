<?php

namespace DonorPerfect\Enums;

enum GiftType: string
{
    case Gift = 'G';
    case Pledge = 'P';
    case RecurringGift = 'R';
}
