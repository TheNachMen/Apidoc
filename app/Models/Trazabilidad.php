<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trazabilidad extends Model
{
    use HasFactory;
    protected $primaryKey = 'id_trazabilidad';

    protected $table = 'db_sisdocumentos.trazabilidad';
    protected $fillable = [
        'id_doc',
        'accion',
        'fecha_accion',
        'estado'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
