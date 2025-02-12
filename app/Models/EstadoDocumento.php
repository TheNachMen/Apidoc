<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoDocumento extends Model
{
    use HasFactory;

    protected $connection = 'mysql'; //configurar la conexion a la base de usuarios

    protected $table = 'db_sisdocumentos.estado_documento'; //configurar la conexion con la tabla

    protected $primaryKey = 'id_estado_documento';
    
    protected $fillable = [
        'fecha_modificacion',
        'id_documento',
        'id_mes',
        'id_anio'
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
