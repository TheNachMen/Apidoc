<?php

namespace App\Http\Controllers;

use App\Models\Trazabilidad;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Anio;
use App\Models\Documento;
use App\Models\EstadoDocumento;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Log;
use OwenIt\Auditing\Models\Audit;
use Str;

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
        //
        $fecha = Carbon::now('America/Santiago')->format('d-m-Y H-i-s');
        // Procesar el archivo
        if ($request->archivo) {
            try {
            $base64Data = $request->archivo;

            // Log para ver qué estamos recibiendo
            Log::info('Datos base64 recibidos', [
                'longitud' => strlen($base64Data),
                'primeros_caracteres' => substr($base64Data, 0, 100)
            ]);

            // Decodificar archivo - ya viene en base64 puro, no necesitamos strip
            $decodedFile = base64_decode($base64Data, true);
            if ($decodedFile === false) {
                throw new \Exception('Error al decodificar el archivo base64');
            }

            // Verificar que el contenido decodificado no está vacío
            if (empty($decodedFile)) {
                throw new \Exception('El archivo decodificado está vacío');
            }

            // Generar nombre de archivo temporal
            $filename = $request->titulo. ' ' .$fecha . 'file' . uniqid() . '.pdf';
            $path = 'files/documentos/'. date('Y');
            $fullPath = $path . '/' . $filename;

            // Asegurar que el directorio existe
            if (!Storage::disk('public')->exists($path)) {
                Storage::disk('public')->makeDirectory($path,0777, true);
            }

            // Guardar el archivo temporalmente
            $saved = Storage::disk('public')->put($fullPath, $decodedFile);
            

            if (!$saved) {
                throw new \Exception('Error al guardar el archivo en el servidor');
            }

            // Guardar la ruta del archivo en la base de datos
            $pathtodb = Str::after($fullPath,'/');
            $url ='http://127.0.0.1:8000/';
            $ruta = $url .$pathtodb;

            Log::info('Archivo guardado exitosamente', [
                'ruta' =>  $fullPath
            ]);
            
            
            } catch (\Exception $e) {
                Log::error('Error procesando archivo: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                throw new \Exception('Error al procesar el archivo: ' . $e->getMessage());
            }
        }
        // 
        // 
        //obtenemos las cabezeras del request
        $userId= $request->header("X-User-ID");
        $ip = $request->header("X-IP");
        //obtenemos el año actual
        $fechaActual = Carbon::parse($request->fecha_inicio)->format('Y-m-d');
        $mesActual = (int)substr(date("m"),0);
        //dd($mesActual);
        $anioActual = date("Y");
        $idanio = Anio::where("numero",$anioActual)->value("id_anio");
       
        $documento = Documento::create([
                'titulo' => $request->titulo,
                'descripcion'=> $request->descripcion,
                'archivo' => $ruta,
                'estado' => 'pendiente',
                'user_id' => $userId,
                'fecha_inicio' => $request->fecha_inicio,
        ]);

        
        //cuando se crea un documento nuevo, este tendra siempre un nuevo id_documento
        $auditdoc = Audit::where('auditable_id',$documento->id_documento)->update(['user_id' => $userId,'ip_address'=> $ip]);
        
        if(!$documento){
            $data = [
                'message'=> 'Error al crear el documento',
                'status' => 500
            ];
            return response()->json($data,400);
        }else{

            $estadodocumento= EstadoDocumento::create([
            'fecha_modificacion'=> $fechaActual,
            'mes' => $mesActual,
            'id_documento' => $documento->id_documento,
            'id_anio'=> $idanio,
            'estado_actual' => 'o'
            ]);
            //cuando se crea un estado nuevo, este tendra siempre un conjunto de nuevos valores nuevos, ya que son unicos para cada estado.
            $auditestado= Audit::where('new_values',$estadodocumento)->update(['user_id'=> $userId,'ip_address'=> $ip]);
            $trazabilidad = Trazabilidad::create([
                'id_doc'=>$documento->id_documento,
                'accion'=>'Creacion',
                'fecha_accion'=> Carbon::now('America/Santiago')->format('Y-m-d H:i:s'),
                'estado'=>$documento->estado 
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
        $fecha = Carbon::now('America/Santiago')->format('d-m-Y H-i-s');
        $documento = Documento::find($id);
        if(!$documento){
            $data=[
                'message'=> 'Documento no encontrado',
                'status' => 404
            ];
            return response()->json($data,404);
        }
        //dd(Carbon::parse($documento->fecha_inicio)->format('m'));
        $cargo = $request->titulo;
        // if($documento->estado == 'abierto'){
        //     if(Str::contains($request->titulo,'(ACTUALIZADO)')){
        //         $cargo = $request->titulo;
        //     }else{
        //         $cargo = $request->titulo.' '.'(ACTUALIZADO)';
        //     }
        // }
        //dd($cargo);
        //dd($request->titulo,$palabra);
        $ruta = $documento->archivo;
        // Procesar el archivo
        if ($request->archivo) {
            $base64Data = $request->archivo;

            // Log para ver qué estamos recibiendo
             Log::info('Datos base64 recibidos', [
                 'longitud' => strlen($base64Data),
                 'primeros_caracteres' => substr($base64Data, 0, 100)
             ]);

            // Decodificar archivo - ya viene en base64 puro, no necesitamos strip
            $decodedFile = base64_decode($base64Data, true);
            
            //verificar si la decodificacion fue exitosa.
            if($decodedFile !== false){
                // Generar nombre de archivo temporal
                $filename = $request->titulo. ' ' .$fecha . 'file' . uniqid() . '.pdf';
                $path = 'files/documentos/'. date('Y');
                $fullPath = $path . '/' . $filename;

                // Asegurar que el directorio existe
                if (!Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->makeDirectory($path,0777, true);
                }

                // Guardar el archivo temporalmente
                $saved = Storage::disk('public')->put($fullPath, $decodedFile);
                

                if (!$saved) {
                    throw new \Exception('Error al guardar el archivo en el servidor');
                }

                // Guardar la ruta del archivo en la base de datos
                $url ='http://127.0.0.1:8000/';
                $ruta = $url .'storage/'.$fullPath;

                Log::info('Archivo guardado exitosamente', [
                    'ruta' =>  $fullPath
                ]);
            }else{
                $ruta = $documento->archivo;
            }
            

        }
        //
        $userId= $request->header("X-User-ID");
        $ip = $request->header("X-IP");
        $fechaActual = date("Y-m-d");
        $mesActual = (int)substr(date("m"),1);
        
        $anioActual = date("Y");
        $idanio = Anio::where("numero",$anioActual)->value("id_anio");
        
        // $documento->titulo = $cargo;
        // $documento->descripcion = $request->descripcion;
        // $documento->archivo = $ruta;
        // $documento->save();
        //cada vez que audit detecta que se uso algun modelo para crear o actualizar un registro, este lo registra en su tabla, por ende siempre sera el ultimo registro de la tabla audit
        // $auditdoc = Audit::orderBy('created_at','desc')->first()->update(['user_id'=> $userId,'ip_address'=> $ip]);
        if($documento->estado == 'pendiente'){
            $documento->titulo = $cargo;
            $documento->descripcion = $request->descripcion;
            $documento->fecha_inicio = $request->fecha_inicio;
            $documento->archivo = $ruta;
            $documento->save();
        //cada vez que audit detecta que se uso algun modelo para crear o actualizar un registro, este lo registra en su tabla, por ende siempre sera el ultimo registro de la tabla audit
            $auditdoc = Audit::orderBy('created_at','desc')->first()->update(['user_id'=> $userId,'ip_address'=> $ip]);
            $estadodocumento= EstadoDocumento::create([
                'fecha_modificacion'=> Carbon::parse($documento->fecha_inicio)->format('Y-m-d'),
                'mes' =>(int)Carbon::parse($documento->fecha_inicio)->format('m'),
                'id_documento' => $documento->id_documento,
                'id_anio'=> $idanio,
                'estado_actual' => 'o'
            ]);
            $auditestado= Audit::where('new_values',$estadodocumento)->update(['user_id'=> $userId,'ip_address'=> $ip]);
            $trazabilidad = Trazabilidad::create([
                'id_doc'=>$documento->id_documento,
                'accion'=>'Actualizacion',
                'fecha_accion'=> Carbon::now('America/Santiago')->format('Y-m-d H:i:s'),
                'estado'=>$documento->estado 
            ]);
                if(!$estadodocumento){
                    $data = [
                        'message'=> 'Error al crear el estado del documento',
                        'status' => 500
                    ];
                    return response()->json($data,400);
                }
        }elseif($documento->estado == 'abierto' || $documento->estado == 'cerrado'){
            $documento->titulo = $cargo;
            $documento->descripcion = $request->descripcion;
            $documento->archivo = $ruta;
            $documento->save();
            //cada vez que audit detecta que se uso algun modelo para crear o actualizar un registro, este lo registra en su tabla, por ende siempre sera el ultimo registro de la tabla audit
            $auditdoc = Audit::orderBy('created_at','desc')->first()->update(['user_id'=> $userId,'ip_address'=> $ip]);
            $estadodocumento= EstadoDocumento::create([
                'fecha_modificacion'=> $fechaActual,
                'mes' =>$mesActual,
                'id_documento' => $documento->id_documento,
                'id_anio'=> $idanio,
                'estado_actual' => 'a'
            ]);
            $auditestado= Audit::where('new_values',$estadodocumento)->update(['user_id'=> $userId,'ip_address'=> $ip]);
            $trazabilidad = Trazabilidad::create([
                'id_doc'=>$documento->id_documento,
                'accion'=>'Actualizacion',
                'fecha_accion'=> Carbon::now('America/Santiago')->format('Y-m-d H:i:s'),
                'estado'=>$documento->estado 
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
        $userId= $request->header("X-User-ID");
        $ip = $request->header("X-IP");
        $fecha_cierre = Carbon::now('America/Santiago')->toDateTimeString();
        $documento = Documento::find($id);
        if(!$documento){
            $data=[
                'message'=> 'Documento no encontrado',
                'status' => 404
            ];
            return response()->json($data,404);
        }
        
        if (!$documento) {
            $data=[
                'message'=>'Error en la validacion de los datos',
                'status'=> 400
            ];
            return response()->json($data,400);
        }
        if($documento->estado == 'abierto'){
            $documento->estado = 'cerrado';
            $documento->fecha_termino = $fecha_cierre;
            $documento->save();
            $auditdoc = Audit::orderBy('created_at','desc')->first()->update(['user_id'=> $userId,'ip_address'=> $ip]);
            $trazabilidad = Trazabilidad::create([
                'id_doc'=>$documento->id_documento,
                'accion'=>'Cerrar',
                'fecha_accion'=> Carbon::now('America/Santiago')->format('Y-m-d H:i:s'),
                'estado'=>$documento->estado 
            ]);
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

    public function trazabilidadindex(){
         //obtener todos los documentos
         $trazabilidad = Trazabilidad::all();

         return response()->json(compact("trazabilidad"),200);
    }
}
