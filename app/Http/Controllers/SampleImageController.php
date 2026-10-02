<?php

namespace App\Http\Controllers;

use App\Models\Sample;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Serves handwriting photos only to signed-in users; files live outside the public folder. */
class SampleImageController extends Controller
{
    public function __invoke(Request $request, Sample $sample)
    {
        $path = $request->query('size') === 'thumb' ? $sample->thumbPath() : $sample->image_path;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }
}
