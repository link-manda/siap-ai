<?php

namespace App\Observers;

use App\Models\Archive;
use App\Models\ArchiveLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ArchiveObserver
{
    /**
     * Handle the Archive "created" event.
     */
    public function created(Archive $archive): void
    {
        $this->logAction($archive, 'UPLOAD');
    }

    /**
     * Handle the Archive "updated" event.
     */
    public function updated(Archive $archive): void
    {
        $this->logAction($archive, 'EDIT');
    }

    /**
     * Handle the Archive "deleted" event.
     */
    public function deleted(Archive $archive): void
    {
        $this->logAction($archive, 'DELETE');
    }

    /**
     * Handle the Archive "restored" event.
     */
    public function restored(Archive $archive): void
    {
        $this->logAction($archive, 'RESTORE');
    }

    protected function logAction(Archive $archive, string $action): void
    {
        ArchiveLog::create([
            'archive_id' => $archive->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
