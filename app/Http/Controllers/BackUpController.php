<?php

namespace App\Http\Controllers;

use App\Utils\Util;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Log;
use Storage;

class BackUpController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorizeBackupAction();

        $disk = Storage::disk(config('backup.backup.destination.disks')[0]);

        $files = $disk->files(config('backup.backup.name'));

        $backups = [];
        // make an array of backup files, with their filesize and creation date
        foreach ($files as $k => $f) {
            // only take the zip files into account
            if (substr($f, -4) == '.zip' && $disk->exists($f)) {
                $backups[] = [
                    'file_path' => $f,
                    'file_name' => str_replace(str_replace('\\', '/', config('backup.backup.name')).'/', '', $f),
                    'file_size' => $disk->size($f),
                    'last_modified' => $disk->lastModified($f),
                ];
            }
        }
        // reverse the backups, so the newest one would be on top
        $backups = array_reverse($backups);

        $cron_job_command = $this->commonUtil->getCronJobCommand();
        
        // $backup_clean_cron_job_command = $this->commonUtil->getBackupCleanCronJobCommand();

        return view('backup.index')
            ->with(compact('backups', 'cron_job_command'));
    }

    /**
     * Create a resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorizeBackupAction();

        try {
            //Disable in demo
            $notAllowed = $this->commonUtil->notAllowedInDemo();
            if (! empty($notAllowed)) {
                return $notAllowed;
            }

            // start the backup process
            Artisan::call('backup:run');
            $output = Artisan::output();

            // log the results
            Log::info("Backpack\BackupManager -- new backup started from admin interface \r\n".$output);

            $output = ['success' => 1,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            $output = ['success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return back()->with('status', $output);
    }

    /**
     * Auto-heal backup permission for superadmin if missing.
     */
    protected function ensureBackupPermission($user): void
    {
        if (! $user || ! method_exists($user, 'isSuperAdmin') || ! $user->isSuperAdmin()) {
            return;
        }

        try {
            $permission = Permission::firstOrCreate([
                'name' => 'backup',
                'guard_name' => 'web',
            ]);

            $adminRoles = Role::where('guard_name', 'web')
                ->where('name', 'like', 'Admin%')
                ->get();

            foreach ($adminRoles as $role) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }

            if (! $user->can('backup')) {
                $user->givePermissionTo($permission);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            Log::warning('Unable to auto-heal backup permission', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Ensure backup permission exists for superadmin and enforce authorization.
     */
    protected function authorizeBackupAction(): void
    {
        $user = auth()->user();

        if ($user && ! $user->can('backup')) {
            $this->ensureBackupPermission($user);
            $user = $user->fresh();
        }

        if (! $user || ! $user->can('backup')) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Downloads a backup zip file.
     *
     * TODO: make it work no matter the flysystem driver (S3 Bucket, etc).
     */
    public function download($file_name)
    {
        $this->authorizeBackupAction();

        //Disable in demo
        if (config('app.env') == 'demo') {
            $output = ['success' => 0,
                'msg' => 'Feature disabled in demo!!',
            ];

            return back()->with('status', $output);
        }

        $file = config('backup.backup.name').'/'.$file_name;
        $disk = Storage::disk(config('backup.backup.destination.disks')[0]);
        if ($disk->exists($file)) {
            $fs = Storage::disk(config('backup.backup.destination.disks')[0])->getDriver();
            $stream = $fs->readStream($file);
            //var_dump($fs->size($file));exit;

            return \Response::stream(function () use ($stream) {
                fpassthru($stream);
            }, 200, [
                'Content-Type' => $fs->mimeType($file),
                //'Content-Length' => $fs->getSize($file),
                'Content-disposition' => 'attachment; filename="'.basename($file).'"',
            ]);
        } else {
            abort(404, "The backup file doesn't exist.");
        }
    }

    /**
     * Deletes a backup file.
     */
    public function delete($file_name)
    {
        $this->authorizeBackupAction();

        //Disable in demo
        if (config('app.env') == 'demo') {
            $output = ['success' => 0,
                'msg' => 'Feature disabled in demo!!',
            ];

            return back()->with('status', $output);
        }

        $disk = Storage::disk(config('backup.backup.destination.disks')[0]);
        if ($disk->exists(config('backup.backup.name').'/'.$file_name)) {
            $disk->delete(config('backup.backup.name').'/'.$file_name);

            return redirect()->back();
        } else {
            abort(404, "The backup file doesn't exist.");
        }
    }
}
