<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mes extends Model
{
    use HasFactory;

    protected $connection = 'mysql'; //configurar la conexion a la base de usuarios

    protected $table = 'db_sisdocumentos.mes'; //configurar la conexion con la tabla

    protected $primaryKey = 'id_mes'; 

    protected $fillable = [
        'nombre_mes',
    ];

    public function estado_documento(){
        return $this->hasMany(EstadoDocumento::class,'id_mes','id_mes');
    }
}
