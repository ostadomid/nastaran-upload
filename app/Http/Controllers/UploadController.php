<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\ContentRangeUploadHandler;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Str;

class UploadController extends Controller
{
    function index()
    {
        return Inertia::render('App');
    }
    function create(Request $request)
    {

        $receiver = new FileReceiver(
            UploadedFile::fake()->createWithContent('videoFile', $request->getContent()),
            $request,
            ContentRangeUploadHandler::class
        );

        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }

        $receivedFile = $receiver->receive();

        if ($receivedFile->isFinished()) {
            $newName = "Yoga-" . date("Y_m_d__H_i_s") . "." . $receivedFile->getFile()->guessExtension();
            $receivedFile->getFile()->storeAs('videos', $newName, 'public');
            session()->put("flash", "file $newName saved!");
        }

        // $save->handler();
    }
}
