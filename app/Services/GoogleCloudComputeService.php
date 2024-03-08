<?php

namespace App\Services;

use App\Models\Gpu;
use Google\Cloud\Compute\V1\Instance;
use Google\Cloud\Compute\V1\InstancesClient;

class GoogleCloudComputeService
{
    protected $projectId;

    protected $client;

    public $zones = [
        'us-central1-a'
    ];

    public $images = [
        'transcribe-t1-1gpu' => 'us-central1-a',
        'transcribe-t4-1-gpu-europe' => 'europe-west4-a'
    ];

    public function __construct()
    {
        $this->client = new InstancesClient();

        $this->projectId = config('services.google_cloud.project');
    }

    public static function make()
    {
        return new self();
    }

    public function listInstances()
    {
        $list = [];

        foreach ($this->zones as $zone) {
            $list = array_merge($list, $this->listInstancesPerZone($zone));
        }

        return collect($list);
    }

    public function listInstancesPerZone($zone)
    {
        $list = [];

        foreach ($this->client->list($this->projectId, $zone) as $instance) {
            $ip = null;

            foreach ($instance->getNetworkInterfaces() as $interface) {
                foreach ($interface->getAccessConfigs() as $config) {
                    $ip = $config->getNatIP();
                }
            }

            $list[] = [
                'name' => $instance->getName(),
                'status' => $instance->getStatus(),
                'ip' => $ip,
                'zone' => $zone
            ];
        }

        return $list;
    }

    public function getInstance($name, $zone)
    {
        $instance = $this->client->get($name, $this->projectId, $zone);

        $ip = null;

        foreach ($instance->getNetworkInterfaces() as $interface) {
            foreach ($interface->getAccessConfigs() as $config) {
                $ip = $config->getNatIP();
            }
        }

        return [
            'name' => $instance->getName(),
            'status' => $instance->getStatus(),
            'ip' => $ip,
            'zone' => $zone
        ];
    }

    public function createInstance(Gpu $gpu)
    {
        if ($gpu->status !== 'creating') return;

        $instance = (new Instance())
            ->setName($gpu->name)
            ->setSourceMachineImage("projects/$this->projectId/global/machineImages/$gpu->machine_image");

        $operationResponse = $this->client->insert($instance, $this->projectId, $this->images[$gpu->machine_image]);
        $operationResponse->pollUntilComplete();

        if ($operationResponse->operationSucceeded()) {
            $i = $this->getInstance($gpu->name, $this->images[$gpu->machine_image]);

            $gpu->update([
                'zone' => $this->images[$gpu->machine_image],
                'ip' => $i['ip'],
                'external_name' => $i['name'],
                'status' => 'active'
            ]);
        } else {
            $error = $operationResponse->getError();
            $gpu->update([
                'zone' => $this->images[$gpu->machine_image],
                'status' => 'creation_failed',
                'error_message' => $error->getMessage()
            ]);
        }

    }

    public function startInstance($name, $zone)
    {
        return $this->client->start($name, $this->projectId, $zone);
    }

    public function stopInstance($name, $zone)
    {
        return $this->client->stop($name, $this->projectId, $zone);
    }

    public function deleteInstance($name, $zone)
    {
        return $this->client->delete($name, $this->projectId, $zone);
    }
}