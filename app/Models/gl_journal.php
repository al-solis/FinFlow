<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class gl_journal extends Model
{
    protected $table = 'gl_journals';
    protected $guarded = [];
    protected $casts = ['journal_date' => 'date', 'posted_at' => 'datetime'];

    public function lines()
    {
        return $this->hasMany(gl_journal_line::class);
    }

    public function reference()
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }
}