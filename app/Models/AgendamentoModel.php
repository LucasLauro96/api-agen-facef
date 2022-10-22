<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgendamentoModel extends Model
{
    use HasFactory;

    protected $table = 'agendamento';
    protected $primaryKey = 'id';
    protected $fillable = ['quadra_id', 'usuario_id', 'data_hora'];
}
