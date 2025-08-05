<?php

namespace DonorPerfect\Enums;

enum PaymentType: string
{
    case CreditCard = 'CC';
    case Check = 'CH';
    case Cash = 'CA';
    case BankTransfer = 'BT';
}
