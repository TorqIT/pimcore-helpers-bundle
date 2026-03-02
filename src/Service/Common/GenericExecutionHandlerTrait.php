<?php

namespace Torq\PimcoreHelpersBundle\Service\Common;

use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Symfony\Component\Process\Process;

trait GenericExecutionHandlerTrait
{
    /**
     * Executes a console command with real-time output logging to the job run.
     * @param array $baseCommand The base command array (e.g., ['php', 'bin/console', 'command:name'])
     * @param array $environmentVariables Environment variables from the automation action
     * @param array $optionMappings Array mapping env var names to command options
     * @param JobRun $jobRun The job run for logging
     * @param string $successMessage Message to log on success
     * @param string $errorMessage Message prefix to log on error
     * @return bool `true` if successful, `false` otherwise
     */
    protected function executeCommandWithLogging(
        array $baseCommand,
        array $environmentVariables,
        array $optionMappings,
        JobRun $jobRun,
        string $successMessage,
        string $errorMessage
    ): bool {
        // Build command with options from environment variables
        $command = $this->buildCommandWithOptions($baseCommand, $environmentVariables, $optionMappings, $jobRun);

        // Run the command in a separate process
        $process = new Process($command, timeout: null);

        // Use callback to capture real-time output and log it to the job run
        $process->run(function ($type, $buffer) use ($jobRun): void {
            $this->logProcessOutput($jobRun, $buffer);
        });

        // Add final success or error message
        if ($process->isSuccessful()) {
            $this->logMessageToJobRun($jobRun, $successMessage);
            return true;
        } else {
            $this->logMessageToJobRun($jobRun, $errorMessage . ': ' . $process->getExitCodeText());
            return false;
        }
    }

    /**
     * Builds a command array by adding options based on environment variables.
     * @param array $baseCommand The base command array
     * @param array $environmentVariables Environment variables from the automation action
     * @param array $optionMappings Array mapping env var names to command options
     * @param JobRun $jobRun The job run for logging
     * @return array The complete command array
     */
    protected function buildCommandWithOptions(
        array $baseCommand,
        array $environmentVariables,
        array $optionMappings,
        JobRun $jobRun
    ): array {
        $command = $baseCommand;

        foreach ($optionMappings as $envVarName => $option) {
            if (isset($environmentVariables[$envVarName]) && $this->isValidOptionValue(
                    $environmentVariables[$envVarName]
                )) {
                $value = $environmentVariables[$envVarName];

                // Handle boolean flags (VALUE_NONE options)
                if ($value === true) {
                    $command[] = $option['flag'];
                } else {
                    if (!is_bool($value)) {
                        // Handle regular options with values
                        $command[] = $option['flag'] . '=' . $value;
                    }
                }

                // Log the option being used
                if (isset($option['logMessage'])) {
                    $logMessage = str_replace('{value}', $value, $option['logMessage']);
                    $this->logMessageToJobRun($jobRun, $logMessage);
                }
            }
        }

        return $command;
    }

    /**
     * Logs process output to the job run, cleaning up empty lines.
     * @param string $buffer The process output buffer
     * @param JobRun $jobRun The job run for logging
     */
    protected function logProcessOutput(JobRun $jobRun, string $buffer): void
    {
        // Clean up the buffer (remove empty lines, trim whitespace)
        $buffer = trim($buffer);
        if (empty($buffer)) {
            return;
        }

        // Log each line to the job run
        foreach (explode("\n", $buffer) as $line) {
            $line = trim($line);

            if (!empty($line)) {
                // Split long lines to avoid causing Pimcore translation issues where keys must be less than 190 characters.
                if (strlen($line) > 190) {
                    $chunks = str_split($line, 190);
                    foreach ($chunks as $chunk) {
                        $this->logMessageToJobRun($jobRun, $chunk);
                    }
                } else {
                    $this->logMessageToJobRun($jobRun, $line);
                }
            }
        }
    }

    /**
     * Logs a message to the job run, truncating if necessary to avoid
     * Pimcore's 190 character translation key limit.
     * @param JobRun $jobRun The job run for logging
     * @param string $message The message to log
     */
    protected function logSafeMessage(JobRun $jobRun, string $message): void
    {
        if (strlen($message) > 190) {
            $message = substr($message, 0, 187) . '...';
        }
        $this->logMessageToJobRun($jobRun, $message);
    }

    /**
     * Checks if an environment variable value is valid for use as a command option.
     * @param mixed $value The environment variable value
     * @return bool True if valid, false otherwise
     */
    protected function isValidOptionValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        if (is_string($value)) {
            return !empty($value);
        }
        if (is_numeric($value)) {
            return $value > 0;
        }
        if (is_bool($value)) {
            return $value;
        }
        return false;
    }
}
