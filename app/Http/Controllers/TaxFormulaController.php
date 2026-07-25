<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\tax_formula;

class TaxFormulaController extends Controller
{
    public function index(Request $request)
    {
        $taxFormulas = tax_formula::query();

        $search = $request->input('search');
        $searchstatus = $request->input('searchstatus');

        if ($search) {
            $taxFormulas->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($searchstatus !== null) {
            $taxFormulas->where('status', $searchstatus);
        }

        $taxFormulas = $taxFormulas->orderBy('code')->paginate(config('app.paginate'));

        return view('tax.tax_formula.index', compact('taxFormulas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|max:20|unique:tax_formulas,code',
            'name' => 'required|max:100',
            'type' => 'required|in:Percentage,FixedAmount,Formula',
            'basis' => 'required',
            'operation' => 'required',

            'expression' => [
                Rule::requiredIf($request->type === 'Formula'),
                'nullable',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {

                    if ($request->type !== 'Formula') {
                        return;
                    }

                    $this->validateExpression($value, $fail);

                },
            ],
        ]);

        tax_formula::create([

            'code' => $request->code,
            'name' => $request->name,
            'type' => $request->type,
            'expression' => $request->type == 'Formula' ? $request->expression : null,
            'basis' => $request->basis,
            'operation' => $request->operation,
            'rounding' => $request->rounding,
            'decimal_places' => $request->decimal_places,
            'status' => $request->status,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Formula saved successfully.');
    }

    // private function validateExpression($expression)
    // {
    //     $allowedVariables = [

    //         'Gross',
    //         'Net',
    //         'Taxable',
    //         'VAT',
    //         'PreviousTax',
    //         'Rate',
    //         'Quantity',
    //         'UnitPrice',

    //     ];

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Validate Variables
    //     |--------------------------------------------------------------------------
    //     */

    //     preg_match_all('/[A-Za-z_][A-Za-z0-9_]*/', $expression, $matches);

    //     foreach ($matches[0] as $variable) {

    //         if (!in_array($variable, $allowedVariables)) {

    //             throw \Illuminate\Validation\ValidationException::withMessages([

    //                 'expression' => "Unknown variable: {$variable}"

    //             ]);

    //         }

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Parentheses
    //     |--------------------------------------------------------------------------
    //     */

    //     $stack = 0;

    //     foreach (str_split($expression) as $char) {

    //         if ($char == '(')
    //             $stack++;

    //         if ($char == ')')
    //             $stack--;

    //         if ($stack < 0) {

    //             throw \Illuminate\Validation\ValidationException::withMessages([

    //                 'expression' => 'Unbalanced parentheses.'

    //             ]);

    //         }

    //     }

    //     if ($stack != 0) {

    //         throw \Illuminate\Validation\ValidationException::withMessages([

    //             'expression' => 'Unbalanced parentheses.'

    //         ]);

    //     }

    // }

    public function update(Request $request, $id)
    {
        $taxFormula = tax_formula::findOrFail($id);

        $request->validate([
            'edit_code' => 'required|max:20|unique:tax_formulas,code,' . $taxFormula->id,
            'edit_name' => 'required|max:100',
            'edit_type' => 'required|in:Percentage,FixedAmount,Formula',
            'edit_basis' => 'required',
            'edit_operation' => 'required',

            'edit_expression' => [
                Rule::requiredIf($request->edit_type === 'Formula'),
                'nullable',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {

                    if ($request->edit_type !== 'Formula') {
                        return;
                    }

                    $this->validateExpression($value, $fail);

                },
            ],
        ]);

        $taxFormula->update([

            'code' => $request->edit_code,
            'name' => $request->edit_name,
            'type' => $request->edit_type,
            'expression' => $request->edit_type == 'Formula' ? $request->edit_expression : null,
            'basis' => $request->edit_basis,
            'operation' => $request->edit_operation,
            'rounding' => $request->edit_rounding,
            'decimal_places' => $request->edit_decimal_places,
            'status' => $request->edit_status,
            'updated_by' => Auth::id(),
            'updated_at' => now(),

        ]);

        return redirect()
            ->back()
            ->with('success', 'Formula updated successfully.');
    }

    private function validateExpression($expression, $fail)
    {
        if (blank($expression)) {
            $fail('Formula expression is required.');
            return;
        }

        $allowedVariables = [
            'Gross',
            'Net',
            'Taxable',
            'VAT',
            'PreviousTax',
            'Rate',
            'Quantity',
            'UnitPrice',
        ];

        preg_match_all('/[A-Za-z_][A-Za-z0-9_]*/', $expression, $matches);

        foreach ($matches[0] as $variable) {
            if (!in_array($variable, $allowedVariables)) {
                $fail("Unknown variable '{$variable}'.");
                return;
            }
        }

        // Validate parentheses
        $balance = 0;

        foreach (str_split($expression) as $char) {
            if ($char === '(') {
                $balance++;
            }

            if ($char === ')') {
                $balance--;

                if ($balance < 0) {
                    $fail('Unbalanced parentheses.');
                    return;
                }
            }
        }

        if ($balance !== 0) {
            $fail('Unbalanced parentheses.');
        }
    }
}
