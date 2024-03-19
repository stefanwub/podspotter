<?php

namespace App\Services;

use App\Models\Clip;
use App\Models\Show;
use Exception;
use Imagick;
use Storage;
use Str;

class ClipPostService
{
    // ffmpeg -y -i clip.mp3 -loop 1 -i bgtest.jpeg -i overlay.png -filter_complex "[0:a]showwaves=s=3000x750:mode=cline:colors=White:draw=full,format=rgba,colorchannelmixer=aa=1[v];[1:v][v]overlay=-750:1125[firstv];[firstv][2:v]overlay=(W-w)/2:(H-h)/2[video];[video]drawtext=fontfile=/path/to/font.ttf:text='Your Title Here':fontcolor=white:fontsize=64:x=(w-text_w)/2:y=100[finalv]" -map "[finalv]" -map 0:a -pix_fmt yuv420p -c:v libx264 -c:a aac -shortest output-audio-visualisation22.mp4

    // ffmpeg -y -i clip.mp3 -loop 1 -i bgtest.jpeg -i overlay.png -filter_complex "[0:a]showwaves=s=3000x750:mode=cline:colors=White:draw=full,format=rgba,colorchannelmixer=aa=1[v1];[1:v][v1]overlay=-750:1125[v2];[v2][2:v]overlay=(W-w)/2:(H-h)/2[v3];[v3]drawtext=fontfile=/path/to/font.ttf:text='Your Title Here':fontcolor=white:fontsize=64:x=(w-text_w)/2:y=100[v4];[v4]scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(ow-iw)/2:(oh-ih)/2[finalv]" -map "[finalv]" -map 0:a -pix_fmt yuv420p -c:v libx264 -c:a aac -shortest output-audio-visualisation30.mp4

    protected $layers;

    protected $width = 1080;

    protected $height = 1080;

    protected $clipPath;

    protected $disk;

    protected $subtitlesFile;

    public function __construct(public Clip $clip)
    {
        $this->layers = collect([]);

        $this->setClipPath();

        $this->disk = config('filesystems.default');
    }

    public static function clip(Clip $clip)
    {
        $service = new self($clip);

        return $service->setClipPath();
    }

    public function setClipPath()
    {
        $this->disk = $this->clip->storage_disk;

        $this->clipPath = $this->getLocalPath($this->clip->storage_key);

        return $this;
    }

    public function size($width, $height)
    {
        $this->width = $width;

        $this->height = $height;

        return $this;
    }

    public function getLocalPath($path)
    {
        Storage::disk('local')->put($path, Storage::disk($this->disk)->get($path));

        return Storage::disk('local')->path($path);
    }

    public function addBackground($pseudoString = "gradient:#510fa8-#4338ca")
    {
        $path = '/backgrounds/' . $pseudoString . '-' . $this->width . '-' . $this->height . '.png';

        if (! Storage::disk('local')->exists($path)) {
            $image = new Imagick();

            // Specify the size of the gradient
            $image->newPseudoImage($this->width, $this->height, $pseudoString);

            // Set the image format to PNG
            $image->setImageFormat('png');

            Storage::disk('local')->put($path,  $image->getImageBlob());
        }

        $this->layers->prepend([
            'type' => 'background',
            'path' => $path,
            'local_path' => Storage::disk('local')->path($path)
        ]);

        return $this;
    }

    public function addWaveform($color = "White", $mode = "cline", $width = '1500', $height = '750', $x = '0', $y = '1125')
    {
        $this->layers->add([
            'type' => 'waveform',
            'color' => $color,
            'mode' => $mode,
            'size' => $width . 'x' . $height,
            'overlay' => $x . ':' . $y 
        ]);

        return $this;
    }

    public function addImageFromDisk($imagePath, $disk, $width = 1200, $height = 1200, $radius = 50, $x = '(W-w)/2', $y = '(H-h)/2')
    {
        $image = $radius ? $this->getroundedCornerImage(Storage::disk($disk)->url($imagePath), $radius) : Storage::disk($disk)->get($imagePath);

        $path = Str::uuid();
        
        Storage::disk('local')->put($path, $image);

        $this->layers->add([
            'type' => 'image',
            'path' => $path,
            'overlay' => $x . ":" . $y,
            'width' => $width,
            'height' => $height,
            'local_path' => Storage::disk('local')->path($path)
        ]);

        return $this;
    }

    public function addShowImage(Show $show, $width = 1200, $height = 1200, $radius = 50, $x = '(W-w)/2', $y = '(H-h)/2')
    {
        return $this->addImageFromDisk(
            $show->image_storage_key, 
            $show->image_storage_disk, 
            $width, 
            $height, 
            $radius, 
            $x, 
            $y
        );
    }

    public function addSubtitles($color = '#FFFFFF', $background = '#000000', $format = 'ass')
    {
        if ($format === 'ass') {
            $this->subtitlesFile = Str::uuid() . '.' . $format; 
            
            Storage::disk('local')->put($this->subtitlesFile, $this->clip->createAssSubtitles(540, 540, $color, $background));
        }

        if ($format === 'srt') {
            $this->subtitlesFile = Str::uuid() . '.' . $format; 
            
            Storage::disk('local')->put($this->subtitlesFile, $this->clip->createSrtSubtitles());     
        }

        return $this;
    }

    public function addText($text, $color, $breakPoint, $size, $y, $font = 'NunitoSans_10pt-ExtraBold.ttf')
    {
        if (strlen($text) > $breakPoint) {
            if ((strlen($text) / 2) <= $breakPoint) {
                $breakPoint = strlen($text) / 2;
            }
        }
        
        $textArray = explode(" ", $text);

        $line = '';

        $lines = [];

        foreach ($textArray as $t) {
            if ($line) {
                if ((strlen($line) + strlen($t)) > $breakPoint) {
                    $lines[] = $line;
                    $line = '';
                }
            }

            $line .= $line ? " " . $t : $t;
        }

        if ($line) {
            $lines[] = $line;
        }

        $lineHeight = 8;

        if(count($lines) === 3) {
            $y = $y - ($size / 2);
        }

        $offset = count($lines) === 1 ? $y + ($size / 2) : $y;
       
        foreach ($lines as $t) {
            $this->layers->add([
                'type' => 'text',
                'text' => $t,
                'size' => $size,
                'color' => $color,
                'font' => Storage::disk('local')->path('fonts/' . $font),
                'y' => $offset
            ]);

            $offset = $offset + $size + $lineHeight;
        }

        return $this;
    }

    public function save($path = null)
    {
        if (! $path) {
            $path = 'posts/' . Str::uuid() . '.mp4';
        }

        $filter = "";

        $fileIndex = 1;
        $index = 0;

        $audioFileIndex = 0;

        if ($waveform = $this->layers->where('type', 'waveform')->first()) {
            $filter .= "[" . $audioFileIndex . ":a]showwaves=s=" . $waveform['size'] . ":mode=" . $waveform['mode'] . ":colors=" . $waveform['color'] . ":draw=full,format=rgba,colorchannelmixer=aa=0.8[waveform];";
        }

        foreach($this->layers as $layer) {
            if ($layer['type'] === 'waveform') {
                $filter .= "[" . $fileIndex - 1 . ":v][waveform]overlay=" . $layer['overlay'] . "[v$index];";
            }
            
            if ($layer['type'] === 'image') {
                $filter .= "[" . $fileIndex . ":v]scale=" . $layer['width'] . ":" . $layer['height'] . "[" . $index ."resized];";
                $filter .= "[v" . $index - 1 . "][" . $index . "resized]overlay=" . $layer['overlay'] . "[v" . $index . "];";
            } 
            
            if ($layer['type'] === 'text') {
                $filter .= "[v" . $index - 1 . "]drawtext=fontfile=" . $layer['font'] . ":text='" . $layer['text'] . "':fontcolor=" . $layer['color'] . ":fontsize=" . $layer['size'] .":x=(main_w-text_w)/2:y=" . $layer['y'] ."[v$index];";
            }

            if (in_array($layer['type'], ['image', 'background'])) {
                $fileIndex++;
            }

            $index++;
        }

        $lastMix = "[v" . $index - 1 . "]";

        if ($this->subtitlesFile) {
            $filter .= $lastMix . "subtitles=" . Storage::disk('local')->path($this->subtitlesFile) . "[withsubs];";
            
            // $filter .= $lastMix . "drawtext=fontfile=/path/to/font.ttf:fontsize=24:fontcolor=white:x=(w-text_w)/2:y=h-100:enable='between(t,1,3)':text='Hello, welcome to our podcast!', drawtext=fontfile=/path/to/font.ttf:fontsize=24:fontcolor=white:x=(w-text_w)/2:y=h-100:enable='between(t,4,6)':text='In today's episode, we'll be discussing...', drawtext=fontfile=/path/to/font.ttf:fontsize=24:fontcolor=white:x=(w-text_w)/2:y=h-100:enable='between(t,7,11)':text='the latest trends in technology and innovation.', drawtext=fontfile=/path/to/font.ttf:fontsize=24:fontcolor=white:x=(w-text_w)/2:y=h-100:enable='between(t,12,15)':text='Stay tuned!'[withsubs]";
            
            $lastMix = "[withsubs]";
        }

        $filter .= $lastMix . "scale=" . $this->width . ":" . $this->height . ":force_original_aspect_ratio=decrease,pad=" . $this->width . ":" . $this->height . ":(ow-iw)/2:(oh-ih)/2[finalv]";

        $filterComplex = escapeshellarg($filter);

        $command = "ffmpeg -i " . $this->clipPath . " ";

        foreach ($this->layers as $layer) {
            if (isset($layer['local_path'])) {
                $command .= "-i " . $layer['local_path'] . " ";
            }
        }

        $command .= "-filter_complex $filterComplex -map '[finalv]' ";

        $command .= "-map $audioFileIndex:a -pix_fmt yuv420p ";

        $command .= "-c:v libx264 -c:a aac -shortest ";

        $command .= Storage::disk('local')->path($path);

        exec($command, $output, $return_var);

        if ($return_var === 0) {
            Storage::disk('local')->delete($this->clipPath);

            foreach($this->layers as $layer) {
                if (isset($layer['path'])) {
                    Storage::disk('local')->delete($layer['path']);
                }
            }

            Storage::disk($this->disk)->put($path, Storage::disk('local')->get($path));

            Storage::disk('local')->delete($path);

            if ($this->subtitlesFile) {
                Storage::disk('local')->delete($this->subtitlesFile);
            }

            return $path;
        }

        throw new Exception($command . ' :' . implode(", ", $output));
    }

    public function getroundedCornerImage($imagePath, $radius)
    {
        $tempImage = tempnam(sys_get_temp_dir(), 'img'); // Create a temporary file
        file_put_contents($tempImage, file_get_contents($imagePath)); // Download and save the image

        // Create an Imagick object
        $image = new Imagick($tempImage);

        // Apply rounded corners
        // Parameters are x-radius, y-radius, stroke_width, displace, size_correction
        $image->roundCornersImage($radius, $radius, 0, 0, 0);

        $image->setImageFormat('PNG');

        unlink($tempImage);

        // Output the image
        return $image->getImageBlob();
    }

}