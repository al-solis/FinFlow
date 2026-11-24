<?php

namespace App\Http\Controllers;

use App\Constants\Modules;
use App\Http\Requests\StoreCreditMemoRequest;
use App\Models\credit_memo;

class CreditMemoController extends ArMemoController
{
    protected function cfg(): array
    {
        return [
            'type' => 'credit',
            'label' => 'Credit Memo',
            'plural' => 'Credit Memos',
            'model' => credit_memo::class,
            'module' => Modules::AR_CMEMO,
            'workflow' => 'credit_memo',
            'series_type' => 'credit_memo',
            'no_col' => 'credit_memo_no',
            'series_col' => 'credit_memo_series_id',
            'date_col' => 'credit_date',
            'request' => StoreCreditMemoRequest::class,
            'reasons' => [
                'sales_return' => 'Sales Return / Goods Returned',
                'billing_error' => 'Overbilling / Pricing Error',
                'trade_allowance' => 'Trade Allowance / Rebate',
                'other' => 'Other Adjustment',
            ],
            'route' => 'ar.credit-memos',
            'index_route' => 'ar.cmemo',
            'account_label' => 'Sales Returns / Contra-Revenue Account',
            'blurb' => 'Reduce what a customer owes for returns, allowances, price adjustments or billing errors. Reverses revenue and Output VAT.',
        ];
    }
}
