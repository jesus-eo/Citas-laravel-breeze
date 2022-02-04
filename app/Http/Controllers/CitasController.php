<?php

namespace App\Http\Controllers;
use App\Models\Cita;
use App\Models\Compania;
use App\Models\Especialidad;
use App\Models\Especialista;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CitasController extends Controller
{
    public function index()
    {
        $citas = Auth::user()->citas;//Accedo a las citas del usuario que esta logeado

        return view('citas.index', [
            'citas' => $citas,
        ]);
    }


    public function destroy(Cita $cita){
        /* dd($cita); PREguntar porque me devuelve el modelo y no puedo acceder a las propiedades de ese objeto */
       /*  dd($cita->user_id) Me da null*/
        $cita->user_id = null;
        $cita->compania_id = null;
        $cita->save();
        return redirect()->route('ver-citas')->with('succes', 'Cita anulada con exito');//Retornar la cita de esta manera
    }


    public function create()
    {
        //Retorna a la vista create pasandole todas las companias y la compania de el usuario que esta logueado.
        //En esa vista retorna la compania que elijas y llama al la route que devuelve al controlador createEspecialidadpasandole la compania elegida
        return view('citas.create', [
            'companias' => Compania::all(),
            'companias_usuario' => Auth::user()->companias,
        ]);
    }

    //En la vista create-especialidad se elige la especialidad y se llama a la ruta crear-cita-especialista[$compania,$especialidad] pasandole la compania elegida anteriormente y la especialidad elegida ahora
    public function createEspecialidad(Compania $compania)
    {
        return view('citas.create-especialidad', [
            'compania' => $compania,
            'especialidades' => Especialidad::all(),
        ]);
    }

    //Aqui le pasamos a la vista los datos anteriores y el especialista de la especialidad elegida anteriormente, esta vista después usa la ruta crear-cita-fecha-hora que es la funcion createFEchaHora de este controlador pasandole todos los paŕametros acumulados anteriormente
    public function createEspecialista(Compania $compania, Especialidad $especialidad)
    {
        //Esta función devuelve(filtra) los que cumplan esa condición, recorre cada especialista guardandolo en el $value y en el return comprueba si la especialidad del  especialista == a la del $especialista que viene en el parámetro y la compania de ese especialista es igual(contains) a la compania($compania) que venimos pasandole como paŕametro entonces en $especialistas guarda los especialista que coincida con esas dos cosas.
       /*  $especialistas = Especialista::all()->filter(function ($value, $key) use ($especialidad, $compania) {
            return $value->especialidad == $especialidad &&
                   $value->companias->contains($compania);
        }); */
        /* dd($compania); */
      /*   whereHas(string $relation, Closure $callback = null, string $operator = '>=', int $count = 1) */
        $especialistas = Especialista::whereHas('especialidad', function (Builder $query) use ($especialidad) {
            $query->where('id', $especialidad->id);
        })->whereHas('companias', function (Builder $query) use ($compania) {
            $query->where('id', $compania->id);
        })->get();
        return view('citas.create-especialista', [
            'compania' => $compania,
            'especialidad' => $especialidad,
            'especialistas' => $especialistas,
        ]);
    }


    public function createFechaHora(Compania $compania, Especialidad $especialidad, Especialista $especialista)
    {
        // La siguiente línea no hace falta porque estamos usando
        // modelos anidados en la ruta:
        // (Ver https://laravel.com/docs/8.x/routing#implicit-model-binding-scoping)
        // abort_if($especialista->especialidad != $especialidad, 404);
        abort_unless($especialista->companias->contains($compania), 404);
        return view('citas.create-fecha-hora', [
            'compania' => $compania,
            'especialidad' => $especialidad,
            'especialista' => $especialista,
            //Aqui pasamos un querybuilder de las citas que tiene el especialista elegido anteriormente donde el user_id sea nulo es decir que no tenga ningun usuario esa cita elegida
            'citas' => $especialista->citas()->where('user_id', null)->get(),
        ]);
    }


    public function createConfirmar(Compania $compania, Cita $cita)
    {
        abort_unless($cita->especialista->companias->contains($compania), 404);
        //Comprueba si la compannia del especialista de esa cita es igual a la compania elegida
        return view('citas.create-confirmar', [
            'compania' => $compania,
            'cita' => $cita,
        ]);
    }

    public function storeConfirmar(Compania $compania, Cita $cita)
    {
        abort_unless($cita->especialista->companias->contains($compania), 404);

        $cita->compania_id = $compania->id;
        $cita->user_id = Auth::id();
        $cita->save();

        return redirect()->route('ver-citas')
            ->with('success', 'Cita creada con éxito.');
    }



    public function especialistasIndex()
    {
        //Devuelve las citas donde la fecha y hora es posterior > a la fecha de hoy (now) del especialista donde id = al usuario autenticado que es especialista id
        $citas = Cita::where('fecha_hora', '>', now())
            ->has('user')
            ->whereHas('especialista', function (Builder $query) {
                $query->where('id', Auth::user()->especialista->id);
            })
            ->get();

        return view('citas.especialistas-index', [
            'citas' => $citas,
        ]);
    }
}
