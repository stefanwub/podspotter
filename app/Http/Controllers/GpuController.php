<?php

namespace App\Http\Controllers;

use App\Http\Resources\GpuResource;
use App\Jobs\DeleteGpuInstance;
use App\Models\Gpu;
use App\Services\GoogleCloudComputeService;
use Illuminate\Http\Request;

class GpuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('view-any', Gpu::class);

        $instances = GoogleCloudComputeService::make()->listInstances();

        return Gpu::all()->map(function($gpu) use ($instances) {
            $gpu->instance = $instances->where('name', $gpu->external_name)->first();

            return new GpuResource($gpu);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Gpu::class);

        $request->validate([
            'name' => [
                'required'
            ],
            'machine_image' => [
                'required',
                'in:transcribe-t1-1gpu,transcribe-t4-1-gpu-europe'
            ],
            'queue' => [
                'required',
                'in:gpu-1,gpu-2,gpu-3,gpu-4,gpu-5,gpu-6,gpu-7,gpu-8',
                'unique:gpus,queue'
            ]
        ]);

        $gpu = Gpu::create([
            'name' => $request->get('name'),
            'machine_image' => $request->get('machine_image'),
            'queue' => $request->get('queue'),
            'status' => 'creating'
        ]);

        return new GpuResource($gpu);
    }

    /**
     * Display the specified resource.
     */
    public function show(Gpu $gpu)
    {
        $this->authorize('view', $gpu);

        $gpu->instance = $gpu->getInstance();

        return new GpuResource($gpu);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Gpu $gpu)
    {
        $this->authorize('update', $gpu);

        $request->validate([
            'status' => [
                'required',
                'in:stopping,starting'
            ]
        ]);

        $gpu->update([
            'status' => $request->get('status')
        ]);

        $gpu->instance = $gpu->getInstance();

        return new GpuResource($gpu);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gpu $gpu)
    {
        $this->authorize('delete', $gpu);

        $gpu->update([
            'status' => 'deleting'
        ]);

        DeleteGpuInstance::dispatch($gpu)->onQueue('gpus');

        $gpu->instance = $gpu->getInstance();

        return new GpuResource($gpu);
    }
}
