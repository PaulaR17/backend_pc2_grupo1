<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

// Lanza el script Python de PC1 que genera predicciones e inserta
// directamente en la tabla `predictions` de PostgreSQL.
//
// El script puede vivir:
//   - LOCALMENTE: si la app y el código de PC1 están en la misma máquina.
//     Se controla con la variable de entorno ML_PYTHON_BIN + ML_PROJECT_PATH.
//   - REMOTO (LORCA): si PC1 corre en otra máquina, se ejecuta vía SSH.
//     Se controla con ML_USE_SSH=true + ML_SSH_USER + ML_SSH_HOST.
class RunPredictions extends Command
{
    protected $signature = 'predictions:run
                            {--target=Accidentes : Accidentes, Calidad Aire o Emergencias}
                            {--modelo=random_forest.pkl : Nombre del .pkl en modelos_guardados/}
                            {--dias=7 : Cuántos días futuros predecir}';

    protected $description = 'Ejecuta el modelo de PC1 y vuelca las predicciones a la BD';

    public function handle(): int
    {
        $target = (string) $this->option('target');
        $modelo = (string) $this->option('modelo');
        $dias   = (int)    $this->option('dias');

        $usaSsh        = filter_var(env('ML_USE_SSH', false), FILTER_VALIDATE_BOOLEAN);
        $proyectoLocal = env('ML_PROJECT_PATH', base_path('../Proyecto-de-Computacion-I'));
        $pythonLocal   = env('ML_PYTHON_BIN', 'python3');

        // Argumentos del script Python.
        $argsScript = sprintf(
            '--dias %d --modelo %s --target %s',
            $dias,
            escapeshellarg($modelo),
            escapeshellarg($target),
        );

        if ($usaSsh) {
            $comando = $this->construirComandoSsh($argsScript);
        } else {
            $comando = $this->construirComandoLocal($proyectoLocal, $pythonLocal, $argsScript);
        }

        $this->info('Lanzando script de predicciones...');
        $this->line($comando);

        $salida = [];
        $codigo = 0;
        exec($comando . ' 2>&1', $salida, $codigo);

        foreach ($salida as $linea) {
            $this->line($linea);
        }

        $resultado = 0;

        if ($codigo !== 0) {
            $this->error('El script Python terminó con código ' . $codigo);
            $resultado = 1;
        } else {
            $this->info('Predicciones generadas correctamente.');
        }

        return $resultado;
    }

    // Construye el comando para ejecutar localmente.
    private function construirComandoLocal(string $proyecto, string $python, string $args): string
    {
        return sprintf(
            'cd %s && %s generar_predicciones.py %s',
            escapeshellarg($proyecto),
            escapeshellarg($python),
            $args,
        );
    }

    // Construye el comando para ejecutar a través de SSH (LORCA).
    private function construirComandoSsh(string $args): string
    {
        $usuario = env('ML_SSH_USER');
        $host    = env('ML_SSH_HOST');
        $ruta    = env('ML_PROJECT_PATH');
        $python  = env('ML_PYTHON_BIN', 'python3');

        $comandoRemoto = sprintf(
            'cd %s && %s generar_predicciones.py %s',
            escapeshellarg($ruta),
            escapeshellarg($python),
            $args,
        );

        return sprintf(
            'ssh %s %s',
            escapeshellarg($usuario . '@' . $host),
            escapeshellarg($comandoRemoto),
        );
    }
}
