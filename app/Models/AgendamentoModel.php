<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgendamentoModel extends Model
{
    use HasFactory;

    protected $table = 'scheduler';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'userId',
        'startedAt',
        'finishedAt',
        'amount',
    ];
}
