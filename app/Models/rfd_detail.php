<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\rfd_header;
use App\Models\rfd_detail_tax;
use App\Models\chart_of_account;
use App\Models\vendor;

class rfd_detail extends Model
{
    protected $table = 'rfd_details';

    protected $fillable = [
        'rfd_header_id',
        'line_no',
        'item_id',
        'description',
        'vendor_id',
        'gl_account_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'taxable_amount',
        'tax_amount',
        'total_amount',
        'reference',
        'reference_date',
        'remarks',
        'created_by',
        'updated_by',
    ];

    // protected $casts = [
    //     'quantity' => 'decimal:15,6',
    //     'unit_price' => 'decimal:15,6',
    //     'discount_amount' => 'decimal:15,6',
    //     'taxable_amount' => 'decimal:15,6',
    //     'tax_amount' => 'decimal:15,6',
    //     'total_amount' => 'decimal:15,6',
    // ];

    public function rfdHeader()
    {
        return $this->belongsTo(rfd_header::class, 'rfd_header_id');
    }

    public function vendor()
    {
        return $this->belongsTo(vendor::class, 'vendor_id');
    }

    public function taxes()
    {
        return $this->hasMany(rfd_detail_tax::class, 'rfd_detail_id');
    }

    // Rename/adjust to your actual GL account model if it isn't called chart_of_account
    public function glAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'gl_account_id');
    }

    public function item()
    {
        return $this->belongsTo(item::class, 'item_id');
    }

    /**
     * Recompute taxable/tax/total for this line from its tax rows.
     * Call after syncing taxes() so header totals stay in sync.
     */
    public function recalcTotals(int $taxOption = 2): void
    {
        $gross = ($this->quantity * $this->unit_price) - $this->discount_amount;

        $tax = (float) $this->taxes()->sum('tax_amount');

        $this->taxable_amount = $gross;  // keep as gross
        $this->tax_amount = $tax;
        $this->total_amount = ($taxOption === 1) ? $gross : ($gross + $tax);

        $this->save();
    }
}
