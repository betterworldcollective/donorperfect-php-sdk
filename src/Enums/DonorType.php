<?php

namespace DonorPerfect\Enums;

enum DonorType: string
{
    case Individual = 'IN';
    case Organization = 'OR';
    case Foundation = 'FO';
    case Corporation = 'CO';
}
