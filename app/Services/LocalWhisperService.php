<?php

namespace App\Services;

use App\Models\WhisperJob;
use Exception;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class LocalWhisperService
{
    public function __construct(private $ssh = null, private $username = '', private $privateKey = '', private $host = '')
    {
        $username = config('services.ssh.username');
        $this->host = config('services.ssh.host');
        $keyPath = config('services.ssh.key_path');

        $this->username = $username;

        $this->privateKey = PublicKeyLoader::load(file_get_contents($keyPath));
    }

    protected function getProcesses($instance)
    {
        $this->ssh = new SSH2(config('services.gpus.'. $instance  . '.host'));

        if (!$this->ssh->login($this->username, $this->privateKey)) {
            exit('Login failed');
        }

        $output = $this->ssh->exec('ps -u ' . $this->username); 

        return $output;
    }

    public static function processes($instance)
    {
        return app(LocalWhisperService::class)->getProcesses($instance);
    }

    protected function toTranscribe(WhisperJob $whisperJob)
    {
        $this->ssh = new SSH2($whisperJob->server ? $whisperJob->server : $this->host);

        if (!$this->ssh->login($this->username, $this->privateKey)) {
            exit('Login failed');
        }

        return $this->ssh->exec('export PATH="/usr/local/cuda/bin:/opt/conda/bin:/opt/conda/condabin:${PATH}" && nohup /opt/conda/bin/python /home/info/whisper.py ' . $whisperJob->id . ' > /dev/null 2>&1 &');
    }

    protected function toTranscribeOnGpu(WhisperJob $whisperJob)
    {
        $this->ssh = new SSH2($whisperJob->serverGpu?->ip);

        if (!$this->ssh->login($this->username, $this->privateKey)) {
            exit('Login failed');
        }

        $this->ssh->setTimeout(700);

        return $this->ssh->exec('export PATH="/usr/local/cuda/bin:/opt/conda/bin:/opt/conda/condabin:${PATH}" && /opt/conda/bin/python /home/info/whisper.py ' . $whisperJob->id . ' > /dev/null 2>&1 &');
    }

    public static function transcribe(WhisperJob $whisperJob)
    {
        return app(LocalWhisperService::class)->toTranscribe($whisperJob);
    }

    public static function transcribeOnGpu(WhisperJob $whisperJob)
    {
        return app(LocalWhisperService::class)->toTranscribeOnGpu($whisperJob);
    }
}