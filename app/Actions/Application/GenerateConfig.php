<?php

namespace App\Actions\Application;

use App\Models\Application;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateConfig
{
    use AsAction;

    public function handle(Application $application, bool $is_json = false)
    {
        // Generate the raw compose configuration.
        $config = $application->generateConfig(is_json: $is_json);

        // If the caller expects a PHP array (not JSON) we can safely manipulate it.
        // The security issue originates from Coolify adding a global `.env`
        // file to every service via the `env_file:` key. We remove that implicit
        // injection while preserving any user‑defined env files.
        if (! $is_json && is_array($config) && isset($config['services'])) {
            foreach ($config['services'] as $serviceName => $service) {
                if (isset($service['env_file'])) {
                    // Normalise to an array for easier handling.
                    $envFiles = (array) $service['env_file'];
                    // Remove entries that are exactly the global `.env` file.
                    $filtered = array_filter($envFiles, fn($file) => $file !== '.env');
                    if (empty($filtered)) {
                        unset($config['services'][$serviceName]['env_file']);
                    } else {
                        // Preserve the original type (string vs array).
                        $config['services'][$serviceName]['env_file'] = count($filtered) === 1 ? reset($filtered) : $filtered;
                    }
                }
            }
        }

        return $config;
    }
}
