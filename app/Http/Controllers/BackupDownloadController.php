<?php

namespace App\Http\Controllers;

use App\Backup;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sends a finished backup zip to the admin who made it. This is an ordinary
 * download rather than part of the Backup screen's button, because a response
 * to the button is held in memory, which a site with many images runs out of.
 */
class BackupDownloadController extends Controller
{
    public function __invoke(string $file): BinaryFileResponse
    {
        $path = Backup::folder().'/'.$file;

        abort_unless(is_file($path), 404);

        return response()->download($path, Backup::filename())->deleteFileAfterSend();
    }
}
