<?php

namespace App\Enum;

enum TransactionType: string
{
    case DEPOSIT = 'DEBIT';
    case WITHDRAWAL = 'CREDIT';
}
