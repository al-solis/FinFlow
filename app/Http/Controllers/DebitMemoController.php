<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Http\Requests\StoreDebitMemoRequest;
use App\Models\debit_memo;

class DebitMemoController extends ArMemoController
{
    protected function cfg(): array
    {
        return [
            'type' => 'debit',
            'label' => 'Debit Memo',
            'plural' => 'Debit Memos',
            'model' => debit_memo::class,
            'module' => Modules::AR_DMEMO,
            'workflow' => 'debit_memo',
            'series_type' => 'debit_memo',
            'no_col' => 'debit_memo_no',
            'series_col' => 'debit_memo_series_id',
            'date_col' => 'debit_date',
            'request' => StoreDebitMemoRequest::class,
            'reasons' => [
                'underbilling' => 'Underbilling / Price Adjustment',
                'additional_charge' => 'Additional Freight / Service',
                'penalty_fee' => 'Late / Debit Fee',
                'other' => 'Other Adjustment',
            ],
            'route' => 'ar.debit-memos',
            'index_route' => 'ar.dmemo',
            'account_label' => 'Revenue Account',
            'blurb' => 'Charge a customer more on an invoice already issued (price adjustment, additional charges, interest). Adds revenue and Output VAT and increases the invoice balance.',
        ];
    }
}
