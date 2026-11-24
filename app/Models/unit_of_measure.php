<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class unit_of_measure extends Model
{
    protected $table = 'unit_of_measures';

    protected $fillable = [
        'organization_id', 'code', 'name', 'base_uom_id', 'factor', 'status', 'created_by', 'updated_by',
    ];

    public function organization()
    {
        return $this->belongsTo(organization::class, 'organization_id');
    }

    /** The unit this one converts to (null when this IS a base unit). */
    public function baseUom()
    {
        return $this->belongsTo(self::class, 'base_uom_id');
    }

    /** Units that convert to this one (BOX, DOZ -> PC). */
    public function derivedUoms()
    {
        return $this->hasMany(self::class, 'base_uom_id');
    }

    public function items()
    {
        return $this->hasMany(item::class, 'uom_id');
    }

    public function purchaseItems()
    {
        return $this->hasMany(item::class, 'purchase_uom_id');
    }

    public function salesItems()
    {
        return $this->hasMany(item::class, 'sales_uom_id');
    }

    public function isBase(): bool
    {
        return $this->base_uom_id === null;
    }

    /** Id of the base unit all conversions run through. */
    public function rootId(): int
    {
        return (int) ($this->base_uom_id ?? $this->id);
    }

    /** How many root-base units one of this unit contains (1 for a base unit). */
    public function rootFactor(): float
    {
        return $this->isBase() ? 1.0 : (float) $this->factor;
    }

    /** e.g. "1 BOX = 12 PC"; needs baseUom loaded to avoid a query per row. */
    public function conversionLabel(): string
    {
        if ($this->isBase()) {
            return 'Base unit';
        }
        $f = rtrim(rtrim(number_format((float) $this->factor, 6, '.', ''), '0'), '.');

        return "1 {$this->code} = {$f} {$this->baseUom->code}";
    }

    /** Convert a quantity between two units that share a root base: convert(2, BOX, PC) = 24. */
    public static function convert(float $qty, self $from, self $to): float
    {
        if ($from->rootId() !== $to->rootId()) {
            throw new InvalidArgumentException("Cannot convert {$from->code} to {$to->code}: they do not share a base unit.");
        }

        return round($qty * $from->rootFactor() / $to->rootFactor(), 6);
    }

    /** Referenced anywhere that a change to its conversion would silently alter. */
    public function isInUse(): bool
    {
        return $this->derivedUoms()->exists()
            || item::where(fn($q) => $q->where('uom_id', $this->id)
                ->orWhere('purchase_uom_id', $this->id)
                ->orWhere('sales_uom_id', $this->id))->exists()
            || sales_invoice_line::where('uom_id', $this->id)->exists();
    }
}
