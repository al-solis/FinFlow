<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class gl_journal_line extends Model
{
    protected $table = 'gl_journal_lines';
    protected $guarded = [];

    public function journal()
    {
        return $this->belongsTo(gl_journal::class, 'gl_journal_id');
    }

    public function glAccount()
    {
        return $this->belongsTo(chart_of_account::class, 'gl_account_id');
    }
}