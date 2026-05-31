<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Incident;
use App\Models\History;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    //contadores para el panel de admin
    public function dashboard()
    {
        $resumen = [
            'users_total' => User::count(),
            'users_active' => User::where('status', true)->count(),
            'users_inactive' => User::where('status', false)->count(),
            'incidents_total' => Incident::count(),
            'incidents_active' => Incident::where('active', true)->count(),
            //volumen de rutas calculadas (entradas de historial)
            'routes_total' => History::count(),
        ];

        return response()->json($resumen);
    }

    //todos los usuarios
    public function users()
    {
        $usuarios = User::orderBy('id')->get();

        return response()->json($usuarios);
    }

    //ficha del usuario con perfil y vehiculos
    public function userDetail(User $user)
    {
        $usuario = $user->load(['profile', 'vehicles']);

        return response()->json($usuario);
    }

    //edita un usuario desde el admin
    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'mail' => 'sometimes|email|max:100|unique:users,mail,' . $user->id,
            'rol' => 'sometimes|in:USER,ADMIN',
            'status' => 'sometimes|boolean',
        ]);

        $user->update($data);

        return response()->json($user);
    }

    //da de baja al usuario sin borrarlo
    public function deactivateUser(User $user)
    {
        $user->update(['status' => false]);

        return response()->json(['ok' => true]);
    }

    //alta de incidencia en el mapa
    public function createIncident(Request $request)
    {
        //title y description se aceptan vacios (null o "")
        $data = $request->validate([
            'type' => 'required|in:ACCIDENT,ROADWORK,EVENT',
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
            'active' => 'sometimes|boolean',
            'title' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:1000',
        ]);

        $incident = Incident::create($data);

        return response()->json($incident, 201);
    }

    //edita una incidencia
    public function updateIncident(Request $request, Incident $incident)
    {
        $data = $request->validate([
            'type' => 'sometimes|in:ACCIDENT,ROADWORK,EVENT',
            'lat' => 'sometimes|numeric|between:-90,90',
            'lon' => 'sometimes|numeric|between:-180,180',
            'active' => 'sometimes|boolean',
            'title' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:1000',
        ]);

        $incident->update($data);

        return response()->json($incident);
    }

    //borrado logico, deja la incidencia en BD pero inactiva
    public function deleteIncident(Incident $incident)
    {
        $incident->update(['active' => false]);

        return response()->json([
            'ok' => true,
            'deleted' => true,
        ]);
    }

    //lanza el script de PC1 para llenar la tabla predictions
    public function runPredictions(\Illuminate\Http\Request $request)
    {
        $target = $request->input('target', 'Accidentes');
        $modelo = $request->input('modelo', 'random_forest.pkl');
        $dias   = (int) $request->input('dias', 7);

        $exitCode = \Illuminate\Support\Facades\Artisan::call('predictions:run', [
            '--target' => $target,
            '--modelo' => $modelo,
            '--dias'   => $dias,
        ]);

        $salida = \Illuminate\Support\Facades\Artisan::output();
        $ok = $exitCode === 0;

        $respuesta = response()->json([
            'ok'      => $ok,
            'message' => $ok
                ? 'Predicciones ejecutadas correctamente.'
                : 'Error ejecutando las predicciones (revisa los logs).',
            'output'  => $salida,
        ], $ok ? 200 : 500);

        return $respuesta;
    }
}
