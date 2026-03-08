<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Prediction;

class RunPredictions extends Command
{
    protected $signature = 'predictions:run {modelType=random_forest} {targetType=accidentes}';
    protected $description = 'Ejecuta el modelo en la VM y guarda los resultados en la BD';

    public function handle()
    {
        $sshUser = env('ML_SSH_USER');
        $sshHost = env('ML_SSH_HOST');
        $remoteProject = env('ML_REMOTE_PROJECT_PATH');
        $remotePython = env('ML_REMOTE_PYTHON_BIN');
        $remoteJson = env('ML_REMOTE_JSON_PATH');
        $modelPath = env('ML_MODEL_PATH');

        $modelType = $this->argument('modelType');
        $targetType = $this->argument('targetType');

        if (
            !$sshUser || !$sshHost || !$remoteProject ||
            !$remotePython || !$remoteJson || !$modelPath
        ) {
            $this->error('Faltan variables ML_* en el .env');
            return 1;
        }

        $remoteCommand =
            'cd ' . escapeshellarg($remoteProject) .
            ' && ' . escapeshellarg($remotePython) .
            ' run_predictions_backend.py' .
            ' --model-path ' . escapeshellarg($modelPath) .
            ' --model-type ' . escapeshellarg($modelType) .
            ' --target-type ' . escapeshellarg($targetType);

        $sshCommand =
            'ssh ' . escapeshellarg($sshUser . '@' . $sshHost) . ' ' .
            escapeshellarg($remoteCommand);

        exec($sshCommand . ' 2>&1', $outPredict, $codePredict);

        if ($codePredict !== 0) {
            $this->error('Error ejecutando predicciones en la VM');
            $this->line(implode("\n", $outPredict));
            return 1;
        }

        $sshCatCommand =
            'ssh ' . escapeshellarg($sshUser . '@' . $sshHost) . ' ' .
            escapeshellarg('cat ' . escapeshellarg($remoteJson));

        $json = shell_exec($sshCatCommand);

        if (!$json) {
            $this->error('No se pudo leer predicciones_resumen.json desde la VM');
            return 1;
        }

        $predictions = json_decode($json, true);

        if (!is_array($predictions)) {
            $this->error('El JSON recibido no es válido');
            return 1;
        }

        foreach ($predictions as $prediction) {
            Prediction::create([
                'district' => $prediction['district'],
                'probability' => $prediction['probability'],
                'level' => $prediction['level'],
                'predicted_at' => $prediction['predicted_at'],
                'model_type' => $prediction['model_type'],
                'target_type' => $prediction['target_type'],
            ]);
        }

        $this->info('Predicciones guardadas correctamente');
        return 0;
    }
}