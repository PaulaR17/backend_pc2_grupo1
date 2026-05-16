<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

//lanza el script de PC1 en local o por SSH segun config
class RunPredictions extends Command
{
    protected $signature = 'predictions:run
                            {--target=Accidentes : Accidentes, Calidad Aire o Emergencias}
                            {--modelo=random_forest.pkl : Nombre del .pkl en modelos_guardados/}
                            {--dias=7 : Cuántos días futuros predecir}';

    protected $description = 'Ejecuta el modelo de PC1 y vuelca las predicciones a la BD';

    //entrada del comando artisan
    public function handle(): int
    {
        $target = (string) $this->option('target');
        $modelo = (string) $this->option('modelo');
        $dias   = (int)    $this->option('dias');

        $usaSsh        = filter_var(env('ML_USE_SSH', false), FILTER_VALIDATE_BOOLEAN);
        $proyectoLocal = env('ML_PROJECT_PATH', base_path('../Proyecto-de-Computacion-I'));
        $pythonLocal   = env('ML_PYTHON_BIN', 'python3');

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

    //comando para correr el script en local
    private function construirComandoLocal(string $proyecto, string $python, string $args): string
    {
        $comando = sprintf(
            'cd %s && %s generar_predicciones.py %s',
            escapeshellarg($proyecto),
            escapeshellarg($python),
            $args,
        );

        return $comando;
    }

    //comando para correr el script por SSH en LORCA
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

        $comandoSsh = sprintf(
            'ssh %s %s',
            escapeshellarg($usuario . '@' . $host),
            escapeshellarg($comandoRemoto),
        );

        return $comandoSsh;
    }
}
