<?php

namespace App\Http\Controllers;

use App\Http\Resources\WhisperJobResource;
use App\Models\WhisperJob;
use Illuminate\Http\Request;

class WhisperJobController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', WhisperJob::class);

        $request->validate([
            'gpus' => [
                'nullable',
                'array',
                'exists:gpus,id'
            ],
            'order_by' => [
                'required',
                'in:created_at,updated_at'
            ],
            // 'status' => [
            //     'nullable',
            //     'in:queued,batched,completed,succeeded,starting,running'
            // ]
        ]);

        $query = WhisperJob::with('episode', 'episode.show', 'serverGpu')->orderBy($request->get('order_by') ?? 'updated_at', 'desc');

        if ($request->get('status')) {
            $query = $query->whereIn('status', explode(',', $request->get('status')));
        }

        if ($request->get('gpus')) {
            $query = $query->whereIn('gpu_id', $request->get('gpus'));
        }

        return WhisperJobResource::collection($query->paginate());
    }

    /**
     * Display the specified resource.
     */
    public function show(WhisperJob $whisperJob)
    {
        $this->authorize('view', $whisperJob);

        return new WhisperJobResource($whisperJob);
    }
}
