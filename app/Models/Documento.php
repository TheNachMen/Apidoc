<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class Documento extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;

    protected $connection = 'mysql'; //configurar la conexion a la base de usuarios

    protected $table = 'db_sisdocumentos.documento'; //configurar la conexion con la tabla

    protected $primaryKey = 'id_documento'; 


    //definir los datos que se pueden asignar de formar masiva
    protected $fillable = [
        'titulo',
        'descripcion',
        'archivo',
        'estado',
        'user_id',
        'fecha_inicio',
        'fecha_termino'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function estado_documento(){
        return $this->hasMany(EstadoDocumento::class ,'id_documento','id_documento');
    }
}
