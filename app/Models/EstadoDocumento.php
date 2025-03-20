<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class EstadoDocumento extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;

    protected $connection = 'mysql'; //configurar la conexion a la base de usuarios

    protected $table = 'db_sisdocumentos.estado_documento'; //configurar la conexion con la tabla

    protected $primaryKey = 'id_estado_documento';
    
    protected $fillable = [
        'fecha_modificacion',
        'mes',
        'id_documento',
        'id_anio',
        'estado_actual'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function Documento(){
        return $this->belongsTo(Documento::class,'id_documento');
    }

    public function mes(){
        return $this->belongsTo(Mes::class,'id_mes');
    }
    public function año(){
        return $this->belongsTo(Anio::class,'id_anio');
    }
}
