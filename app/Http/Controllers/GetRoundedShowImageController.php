<?php

namespace App\Http\Controllers;

use App\Models\Show;
use Illuminate\Http\Request;
use Imagick;

class GetRoundedShowImageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Show $show, Request $request)
    {
        $imagePath = $show->image_storage_url; // Specify the image path without extension

        $tempImage = tempnam(sys_get_temp_dir(), 'img'); // Create a temporary file
        file_put_contents($tempImage, file_get_contents($imagePath)); // Download and save the image

        // Create an Imagick object
        $image = new Imagick($tempImage);

        // Apply rounded corners
        // Parameters are x-radius, y-radius, stroke_width, displace, size_correction
        $image->roundCornersImage($request->query('radius') ?? 50, $request->query('radius') ?? 50, 0, 0, 0);

        $image->setImageFormat('PNG');

        unlink($tempImage);

        // Output the image
        return response($image)->header('Content-Type', 'image/png');
    }
}
