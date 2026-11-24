<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDebitMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permission is checked in the controller (AuthorizationService).
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['nullable', 'in:draft,submit'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'invoice_id' => ['required', 'integer', 'exists:sales_invoices,id'],
            'series_id' => ['nullable', 'integer', 'exists:invoice_series,id'],
            'memo_date' => ['required', 'date'],
            'reason_code' => ['required', 'in:underbilling,additional_charge,penalty_fee,other'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'tax_option' => ['required', 'integer', 'in:0,1,2'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['nullable', 'integer', 'exists:items,id'],
            'lines.*.uom_id' => ['nullable', 'integer', 'exists:unit_of_measures,id'],
            'lines.*.description' => ['required', 'string', 'max:1000'],
            'lines.*.gl_account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.vat' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Add at least one line.',
            'lines.*.description.required' => 'Every line needs a description.',
            'lines.*.gl_account_id.required' => 'Every line needs an account.',
            'reason_code.required' => 'Select the reason this debit memo is issued.',
            'invoice_id.required' => 'Select the invoice this debit memo adds to.',
        ];
    }
}
