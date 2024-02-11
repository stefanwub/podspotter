<?php

namespace App\Services;

use App\Models\WhisperJob;
use Exception;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class LocalWhisperService
{
    public function __construct(private $ssh = null, private $username = '', private $privateKey = '')
    {
        $username = config('services.ssh.username');
        $host = config('services.ssh.host');
        $keyPath = config('services.ssh.key_path');

        $this->username = $username;

        $this->privateKey = PublicKeyLoader::load(file_get_contents($keyPath));

        $this->ssh = new SSH2($host);
    }

    protected function getProcesses()
    {
        if (!$this->ssh->login($this->username, $this->privateKey)) {
            exit('Login failed');
        }

        $output = $this->ssh->exec('ps -u ' . $this->username); 

        return $output;
    }

    public static function processes()
    {
        return app(LocalWhisperService::class)->getProcesses();
    }

    protected function toTranscribe(WhisperJob $whisperJob)
    {
        if (!$this->ssh->login($this->username, $this->privateKey)) {
            exit('Login failed');
        }

        return $this->ssh->exec('conda activate base && nohup /opt/conda/bin/python /home/info/whisper.py ' . $whisperJob->id . ' > /dev/null 2>&1 &');
    }

    public static function transcribe(WhisperJob $whisperJob)
    {
        return app(LocalWhisperService::class)->toTranscribe($whisperJob);
    }
}