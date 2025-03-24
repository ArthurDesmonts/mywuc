<?php

namespace App\Enum;

enum TransactionType: string
{
    case DEPOSIT = 'CREDIT';
    case WITHDRAWAL = 'DEBIT';
}
