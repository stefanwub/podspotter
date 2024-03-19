<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Clip;
use App\Models\Post;
use Illuminate\Http\Request;
use Storage;

class ClipPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Clip $clip)
    {
        $this->authorize('view', $clip->team);

        return PostResource::collection($clip->posts()->orderBy('updated_at', 'desc')->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Clip $clip, Request $request)
    {
        $this->authorize('view', $clip->team);

        $request->validate([
            'name' => [
                'required',
                'max:255'
            ],
            'title' => [
                'nullable',
                'string'
            ],
            'template_name' => [
                'required',
                'in:petjeaf-insta-purple,petjeaf-insta-light,petjeaf-reel-purple,petjeaf-reel-light'
            ],
        ]);

        $post = $clip->posts()->create([
            'name' => $request->get('name'),
            'title' => $request->get('title'),
            'template_name' => $request->get('template_name'),
            'status' => 'processing'
        ]);

        return new PostResource($post);
    }

    /**
     * Display the specified resource.
     */
    public function show(Clip $clip, Post $post)
    {
        $this->authorize('view', $clip->team);

        return new PostResource($post);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Clip $clip, Post $post)
    {
        $this->authorize('view', $clip->team);

        $request->validate([
            'name' => [
                'required',
                'max:255'
            ],
        ]);

        $post->update([
            'name' => $request->get('name')
        ]);           

        return new PostResource($post);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Clip $clip, Post $post)
    {
        $this->authorize('view', $clip->team);   

        Storage::disk($post->storage_disk)->delete($post->storage_key);

        $post->delete();

        return response('', 204);
    }
}
