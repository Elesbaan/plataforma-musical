<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadVideoChunkRequest;
use App\Services\ChunkedVideoUploadService;
use Illuminate\Http\JsonResponse;

class MediaChunkController extends Controller
{
    /**
     * Recibe un fragmento de video (POST /admin/media/upload-chunk).
     */
    public function store(UploadVideoChunkRequest $request, ChunkedVideoUploadService $uploader): JsonResponse
    {
        $result = $uploader->receive($request->validated(), $request->file('chunk'), $request->user()->id);

        if ($result['status'] === 'complete') {
            return response()->json([
                'status' => 'complete',
                'message' => 'El video se subió correctamente.',
                'media' => $result['media'],
            ], 201);
        }

        return response()->json($result);
    }
}
