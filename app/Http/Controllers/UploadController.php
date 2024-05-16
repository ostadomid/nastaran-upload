<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use PDO;
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
        $fileNameFromHeader = $request->header('X-FILE-NAME', 'videoFile');
        if (($justFileName = mb_strrchr($fileNameFromHeader, ".", true)) !== false) {
            $fileNameFromHeader = $justFileName;
        }

        $receiver = new FileReceiver(
            UploadedFile::fake()->createWithContent($fileNameFromHeader, $request->getContent()),
            $request,
            ContentRangeUploadHandler::class
        );

        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }

        $receivedFile = $receiver->receive();

        if ($receivedFile->isFinished()) {
            $newName = "[" . date("Y_m_d__H_i_s") . "]-" . $fileNameFromHeader . "." . $receivedFile->getFile()->guessExtension();
            $receivedFile->getFile()->storeAs('videos', $newName, 'public');
            //session()->put("flash", "file $newName saved!");
        }
        // $save->handler();
    }
}
