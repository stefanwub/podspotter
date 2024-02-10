<?php

namespace App\Services;

use Exception;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class LocalWhisperService
{
    public function __construct(private $ssh = null, private $username = '', private $privateKey = '')
    {
        $this->username = env('SSH_USERNAME');

        $this->privateKey = PublicKeyLoader::load(file_get_contents(base_path('.ssh/id_rsa')));

        $this->ssh = new SSH2(env('SSH_HOST'), '22');
    }

    protected function toTranscribe($audioUrl)
    {
        try {
            if (!$this->ssh->login($this->username, $this->privateKey)) {
                return [
                    'error' => true,
                    'error_message' => 'Login failed'
                ];
            }

            $this->ssh->setTimeout(0);

            $output = $this->ssh->exec('/opt/conda/bin/python /home/info/whisper.py ' . strtok($audioUrl, '?'));

            $json = json_decode($output, true);

            if (! count($json)) {
                return [
                    'error' => true,
                    'error_message' => $output
                ];
            }

            return $json;
            
        } catch (Exception $e) {
            return [
                'error' => true,
                'error_message' => $e->getMessage()
            ];
        }
    }

    public static function transcribe($audioUrl)
    {
        return app(LocalWhisperService::class)->toTranscribe($audioUrl);
    }
}