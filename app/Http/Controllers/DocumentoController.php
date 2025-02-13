<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Anio;
use App\Models\Mes;
use App\Models\Documento;
use App\Models\EstadoDocumento;
use Illuminate\Support\Facades\Validator;
class DocumentoController extends Controller
{
    //
    public function index(){
        //obtener la fecha actual
        $anioActual = (int)(date("Y"));
        //$anioActual= "2025";
        
        //buscar y obtener el id del año en la tabla año mediante el numero de año
        $aniotabla = Anio::where("numero",$anioActual)->value("id_anio");

        //obtener todos los documentos
        $documentos = Documento::all();

        //crear un array vacio para guardar todos los registro recientes del año actual
        $estadosActuales = [];
        $n = 0;
        //recorremos cada id de cada registro de $documento y con eso buscamos la fecha de modificacion en EstadoDocumento mas reciente que concuerda con el año actual y asociado con el id del documento
        foreach ($documentos as $documento) {
            $id = $documento->id_documento;
            $estados = EstadoDocumento::where("id_documento",$id)
                                         ->where("id_anio",$aniotabla)
                                         ->orderBy('fecha_modificacion','desc')
                                         ->first();

            //determinamos si $estados tiene algun dato rescatado
            if($estados){
                //si encuentra algo, lo almacena en el array
                $estadosActuales[$n] = [$estados,$documento];
                $n++;
            }else{
                $n++;
            }
            
            
        };
        //comprobamos solo si dentro del primer registro y que dentro de este el primer subregistro este vacio por ende todos los demas lo estan
        if(Empty($estadosActuales[0][0])){
            return response()->json(['message'=>['no hay documentos registrados'],'code'=> 200]);
        }else{
            //ordenar array de manera ascendente(mas antiguo al mas reciente)
            usort($estadosActuales,function($a,$b) {
                $fecha_a = strtotime($a[0]['fecha_modificacion']);
                $fecha_b = strtotime($b[0]['fecha_modificacion']);

                if ($fecha_a == $fecha_b) {
                    return 0;
                }
                return ($fecha_a < $fecha_b) ?-1:1;
            });
        }
        
        return response()->json(compact("estadosActuales"),200);
    }

    public function store(Request $request){
        $fechaActual = date("Y-m-d");
        $mesActual = (int)substr(date("m"),1);
        
        $anioActual = date("Y");
        $idanio = Anio::where("numero",$anioActual)->value("id_anio");

        $validator = Validator::make($request->all(), [
            'titulo'=> 'required|max:191',
            'descripcion'=> 'required|max:255',
            'archivo'=>'required',
        ]);
        if ($validator->fails()) {
            $data=[
                'message'=>'Error en la validacion de los datos',
                'errors' => $validator->errors(), 
                'status'=> 400
            ];
            return response()->json($data,400);
        };
        $documento = Documento::create([
                'titulo' => $request->titulo,
                'descripcion'=> $request->descripcion,
                'archivo' => $request->archivo,
                'estado' => 'abierto'
        ]);
        if(!$documento){
            $data = [
                'message'=> 'Error al crear el documento',
                'status' => 500
            ];
            return response()->json($data,400);
        }else{
            $estadodocumento= EstadoDocumento::create([
            'fecha_modificacion'=> $fechaActual,
            'id_documento' => $documento->id_documento,
            'id_mes' =>$mesActual,
            'id_anio'=> $idanio
            ]);
            if(!$estadodocumento){
                $data = [
                    'message'=> 'Error al crear el estado del documento',
                    'status' => 500
                ];
                return response()->json($data,400);
            }

        }
        
        $data=[
            [
            'documento' => $documento,
            'status' => 201
            ],
            [
            'estado_documento' => $estadodocumento,
            'status' => 201 
            ]
        ];
        return response()->json($data,201);
    }

    public function update(Request $request, $id){
        $fechaActual = date("Y-m-d");
        $mesActual = (int)substr(date("m"),1);
        
        $anioActual = date("Y");
        $idanio = Anio::where("numero",$anioActual)->value("id_anio");

        $documento = Documento::find($id);
        if(!$documento){
            $data=[
                'message'=> 'Documento no encontrado',
                'status' => 404
            ];
            return response()->json($data,404);
        }

        $validator = Validator::make($request->all(), [
            'titulo'=> 'required|max:191',
            'descripcion'=>'required|max:255',
            'archivo'=> 'required|max:250'
        ]);
        if ($validator->fails()) {
            $data=[
                'message'=>'Error en la validacion de los datos',
                'errors' => $validator->errors(), 
                'status'=> 400
            ];
            return response()->json($data,400);
        }
        $documento->titulo = $request->titulo;
        $documento->descripcion = $request->descripcion;
        $documento->archivo = $request->archivo;
        $documento->save();

        $estadodocumento= EstadoDocumento::create([
            'fecha_modificacion'=> $fechaActual,
            'id_documento' => $documento->id_documento,
            'id_mes' =>$mesActual,
            'id_anio'=> $idanio
            ]);
            if(!$estadodocumento){
                $data = [
                    'message'=> 'Error al crear el estado del documento',
                    'status' => 500
                ];
                return response()->json($data,400);
            }
        $data=[
            'message'=> 'Datos de documento actualizado',
            [
            'documento' => $documento,
            'status' => 200
            ],
            [
            'estado_documento' => $estadodocumento,
            'status' => 200 
            ]
        ];
        return response()->json($data,201);
    }

    public function cambiarEstado(Request $request, $id){
        $documento = Documento::find($id);
        if(!$documento){
            $data=[
                'message'=> 'Documento no encontrado',
                'status' => 404
            ];
            return response()->json($data,404);
        }
        /*
        $validator = Validator::make($request->all(), [
            'titulo'=> 'required|max:191',
            'descripcion'=>'required|max:255',
            'archivo'=> 'required|max:250'
        ]);
        */
        if (!$documento) {
            $data=[
                'message'=>'Error en la validacion de los datos',
                'status'=> 400
            ];
            return response()->json($data,400);
        }
        if($documento->estado == 'abierto'){
            $documento->estado = 'cerrado';
            $documento->save();
            $data=[
            'message'=> 'Estado actualizado',
            'status' => 200
            
            ];
        }else{
            $data=[
                'message'=> 'Estado ya esta marcado como cerrado',
                'status' => 200
                
            ];
        }
        //$documento->estado = $request->estado;
        return response()->json($data,201);
        
    }

    public function show($id){
        $documento = Documento::find($id);
        if(!$documento){
            $data=[
                'message'=> 'Documento no encontrado',
                'status' => 404
            ];
            return response()->json($data,404);
        }
        $data=[
            'documento'=> $documento,
            'status'=> 200
        ];

        return response()->json($data,200);
    }
}
