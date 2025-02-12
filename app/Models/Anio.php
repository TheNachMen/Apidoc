<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anio extends Model
{
    use HasFactory;

    protected $connection = 'mysql'; //configurar la conexion a la base de usuarios

    protected $table = 'db_sisdocumentos.año'; //configurar la conexion con la tabla

    protected $primaryKey = 'id_anio';

    protected $fillable = [
        'numero',
    ];

    public function estado_documento(){
        return $this->hasMany(EstadoDocumento::class,'id_anio','id_anio');
    }
}
