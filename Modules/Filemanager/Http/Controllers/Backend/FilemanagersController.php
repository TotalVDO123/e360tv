<?php
namespace Modules\Filemanager\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use Modules\Filemanager\Models\Filemanager;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Modules\Filemanager\Http\Requests\FilemanagerRequest;
use App\Trait\ModuleTrait;
use App\Models\Setting;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessFileUpload;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\DB;
use Modules\Entertainment\Models\Entertainment;
use Illuminate\Support\Facades\Log;

class FilemanagersController extends Controller
{
    protected string $exportClass = '\App\Exports\FilemanagerExport';

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct()
    {
        $this->traitInitializeModuleTrait(
            'filemanager.title', // module title
            'media', // module name
            'fa-solid fa-clipboard-list' // module icon
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $module_action = 'List';
        $searchQuery = $request->get('query');
        $perPage = 31;
        $page = $request->get('page', 1);

        $result = getMediaUrls($searchQuery, $perPage, $page);
        $mediaUrls = $result['mediaUrls'];
        $hasMore = $result['hasMore'];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('filemanager::backend.filemanager.partial', compact('mediaUrls'))->render(),
                'hasMore' => $hasMore,
            ]);
        }

        return view('filemanager::backend.filemanager.index', compact('module_action', 'mediaUrls', 'hasMore'));
    }



    public function getMediaStore(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = 31; // Number of items per page

        $searchQuery = $request->get('query');
        $result = getMediaUrls($searchQuery, $perPage, $page);


        $mediaUrls = $result['mediaUrls'];
        $hasMore = $result['hasMore'];

        $html = view('filemanager::backend.filemanager.partial', compact('mediaUrls'))->render();

            return response()->json([
                'html' => $html,
                'hasMore' => $hasMore,
            ]);
    }


    public function store(FilemanagerRequest $request)
  {

    $page_type = $request->input('page_type');

    $jobs = [];

    // Mode A: direct file post (fallback)
    if ($request->hasFile('file_url')) {
        foreach ($request->file('file_url') as $file) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileType = $this->getFileType($extension);
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $sanitizedBaseName = str_replace([' ', '-', '.','%20'], '_', $baseName);
            $uniqueFileName = $sanitizedBaseName . '_' . uniqid() . '.' . $extension;
            $temporaryPath = $file->storeAs('temp/uploads', $uniqueFileName);
            // If chunk-assembled temp (original name) exists, remove to avoid duplicate
            $assembledTempPath = storage_path('app/temp/uploads/' . $originalName);
            if (file_exists($assembledTempPath)) {
                @unlink($assembledTempPath);
            }
            $filemanager = Filemanager::create([
                'file_url' => $temporaryPath,
                'file_name' => $uniqueFileName,
            ]);
            $diskType = env('ACTIVE_STORAGE', 'local');
            Log::info('file uploaded', ['file' => $uniqueFileName]);
            $job = new ProcessFileUpload($filemanager, $temporaryPath, $diskType, $originalName, $page_type, $fileType);
            $jobs[] = $job;
        }
    }
    // Mode B: chunk upload already assembled; receive only file names
    elseif ($request->filled('file_names')) {
        foreach ((array) $request->input('file_names', []) as $originalName) {
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileType = $this->getFileType($extension);
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $sanitizedBaseName = str_replace([' ', '-', '.','%20'], '_', $baseName);
            $uniqueFileName = $sanitizedBaseName . '_' . uniqid() . '.' . $extension;
            // Source path is the assembled temp file produced by /upload
            $temporaryPath = 'temp/uploads/' . $originalName;
            $filemanager = Filemanager::create([
                'file_url' => $temporaryPath,
                'file_name' => $uniqueFileName,
            ]);
            $diskType = env('ACTIVE_STORAGE', 'local');
            Log::info('queued assembled temp', ['file' => $originalName]);
            $job = new ProcessFileUpload($filemanager, $temporaryPath, $diskType, $originalName, $page_type, $fileType);
            $jobs[] = $job;
        }
    }

    if (!empty($jobs)) {

        Bus::batch($jobs)->dispatch();
        Log::info('batch dispatched', ['count' => count($jobs)]);

        // foreach ($jobs as $job) {
        //      ProcessFileUpload::dispatchSync(
        //         $job->filemanager,
        //         $job->filePath,
        //         $job->diskType,
        //         $job->originalName,
        //         $job->page_type,
        //         $job->fileType
        //     );
        // }
        // Log::info('jobs dispatched synchronously', ['count' => count($jobs)]);

    } else {
        Log::warning('no jobs queued for upload');
    }
    $message = trans('filemanager.file_added');

    return redirect()->route('backend.media-library.index')->with('success', $message);
}


private function getFileType($extension)
{
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico', 'tiff', 'tif'];
    $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp', 'm4v', 'mpg', 'mpeg'];

    $extension = strtolower($extension);

    if (in_array($extension, $imageExtensions)) {
        return 'image';
    } elseif (in_array($extension, $videoExtensions)) {
        return 'video';
    } else {
        return 'other';
    }
}


//old file upload code


// public function upload(Request $request)
// {
//     $fileChunk = $request->file('file_chunk');
//     $fileName = $request->input('file_name');           // original name or server-generated token
//     $index = (int) $request->input('index');            // 0-based index
//     $totalChunks = (int) $request->input('total_chunks');
//     $temporaryDirectory = storage_path('app/temp/uploads/');
//     if (! is_dir($temporaryDirectory)) {
//         mkdir($temporaryDirectory, 0775, true);
//     }
//     $partPath = $temporaryDirectory . $fileName . '.part' . $index;
//     $fileChunk->move($temporaryDirectory, $fileName . '.part' . $index);
//     // If last chunk, merge all parts
//     if ($index + 1 === $totalChunks) {
//         $outputFilePath = $temporaryDirectory . $fileName;  // or final destination path
//         $output = fopen($outputFilePath, 'wb');             // overwrite, not append
//         for ($i = 0; $i < $totalChunks; $i++) {
//             $chunkPath = $temporaryDirectory . $fileName . '.part' . $i;
//             $in = fopen($chunkPath, 'rb');
//             stream_copy_to_stream($in, $output);
//             fclose($in);
//             unlink($chunkPath);
//         }
//         fclose($output);
//     }
//     return response()->json(['success' => true]);
// }


    public function upload(Request $request)
{
    $fileChunk = $request->file('file_chunk');
    $fileName = $request->input('file_name');           // unique name/token for the whole file
    $index = (int) $request->input('index');            // 0-based index
    $totalChunks = (int) $request->input('total_chunks');

    $temporaryDirectory = storage_path('app/temp/uploads/');
    if (! is_dir($temporaryDirectory)) {
        mkdir($temporaryDirectory, 0775, true);
    }

    // Append this chunk directly to a single temp file
    $outputFilePath = $temporaryDirectory . $fileName;

    // First chunk: start fresh
    if ($index === 0 && file_exists($outputFilePath)) {
        @unlink($outputFilePath);
    }

    // Stream-append current chunk
    $in = fopen($fileChunk->getRealPath(), 'rb');
    $out = fopen($outputFilePath, $index === 0 ? 'wb' : 'ab');
    if ($out !== false) {
        // exclusive lock to avoid concurrent writes
        @flock($out, LOCK_EX);
        stream_copy_to_stream($in, $out);
        @flock($out, LOCK_UN);
        fclose($out);
    }
    fclose($in);

    // If last chunk, finalize: move to final storage and remove temp
    // if ($index + 1 === $totalChunks) {
    //     $activeDisk = env('ACTIVE_STORAGE', 'local');
    //     if ($activeDisk === 'local') {
    //         $targetPath = 'public/streamit-laravel/' . $fileName;
    //         \Illuminate\Support\Facades\Storage::disk('local')->put($targetPath, file_get_contents($outputFilePath));
    //     } else {
    //         $targetPath = 'streamit-laravel/' . $fileName;
    //         \Illuminate\Support\Facades\Storage::disk($activeDisk)->put($targetPath, file_get_contents($outputFilePath));
    //     }
    //     @unlink($outputFilePath);
    // }

    return response()->json(['success' => true]);
}
    //delete function chnage while old is repating url with public/storage/
    public function destroy(Request $request)
    {


        $url = $request->input('url');

        $activeDisk = env('ACTIVE_STORAGE', 'local');

        $parsedUrl = parse_url($url);
        $urlPath = ltrim($parsedUrl['path'] ?? '', '/');



        $relativePath = null;

        if ($activeDisk === 'local') {

            $storagePos = strpos($urlPath, 'storage/');
            if ($storagePos !== false) {
                $afterStorage = substr($urlPath, $storagePos + strlen('storage/'));
                $relativePath = 'public/' . ltrim($afterStorage, '/');
            } else if (strpos($urlPath, 'public/') === 0) {
                $relativePath = $urlPath;
            } else {
                $relativePath = 'public/' . $urlPath;
            }
        } else {
            // For S3/Spaces, use the key without leading public/storage
            $relativePath = ltrim($urlPath, '/');
            if (strpos($relativePath, 'storage/') === 0) {
                $relativePath = substr($relativePath, strlen('storage/'));
            }
            if (strpos($relativePath, 'public/') === 0) {
                $relativePath = substr($relativePath, strlen('public/'));
            }
        }

        $fileName = basename($relativePath);

        deleteBunnyStreamVideoByFile($fileName);

        $deleted = Storage::disk($activeDisk)->delete($relativePath);

        if ($deleted) {
            $filemanager = Filemanager::where('file_name', $fileName)->first();
            if ($filemanager) {
                $filemanager->forceDelete();
            }
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 500);
    }

   public function SearchMedia(Request $request){
        $search = $request->input('search', '');
        $storagePath = storage_path('app/public');
        $results = [];

        if (empty($search)) {
            return response()->json([
                'success' => true,
                'results' => []
            ]);
        }

        // Search through all directories recursively
        $this->searchMediaRecursively($storagePath, $search, $results);

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
   }

   private function searchMediaRecursively($path, $search, &$results, $currentFolder = '') {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . '/' . $item;
            $relativePath = $currentFolder ? $currentFolder . '/' . $item : $item;

            if (is_dir($itemPath)) {
                // Recursively search subdirectories
                $this->searchMediaRecursively($itemPath, $search, $results, $relativePath);
            } else {
                // Check if it's an image or video file
                $isVideo = preg_match('/\.(mp4|webm|avi|mov)$/i', $item);
                $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $item);

                if (($isVideo || $isImage) && stripos($item, $search) !== false) {
                    // Derive page_type from folder structure
                    $pageType = 'default';
                    $folderSegments = explode('/', $currentFolder);
                    $imageIndex = array_search('image', $folderSegments, true);
                    $videoIndex = array_search('video', $folderSegments, true);

                    if ($imageIndex !== false && $imageIndex > 0) {
                        $pageType = $folderSegments[$imageIndex - 1];
                    } elseif ($videoIndex !== false && $videoIndex > 0) {
                        $pageType = $folderSegments[$videoIndex - 1];
                    } elseif (!empty($folderSegments)) {
                        $pageType = end($folderSegments);
                    }

                    $mediaUrl = '';
                    if ($isVideo || $isImage) {
                        $type = $isVideo ? 'video' : 'image';
                        $mediaUrl = setBaseUrlWithFileName($item, $type, $pageType);
                    }

                    $results[] = [
                        'name' => $item,
                        'path' => $relativePath,
                        'is_dir' => false,
                        'size' => filesize($itemPath),
                        'modified' => filemtime($itemPath),
                        'media_url' => $mediaUrl,
                        'is_video' => $isVideo,
                        'is_image' => $isImage,
                        'folder' => $currentFolder
                    ];
                }
            }
        }
    }

    public function getFolderContents(Request $request)
    {
        $folder = (string) $request->get('folder', '');
        $limit = max(1, min(200, (int) $request->get('limit', 60)));
        $offset = max(0, (int) $request->get('offset', 0));
        $activeDisk = env('ACTIVE_STORAGE', 'local');
        $contents = [];
        $totalItems = 0;
        $nextOffset = null;

        try {
            // Phase 1: lightweight listing (name + mtime only) — avoid per-file URL/size work
            $entries = $activeDisk === 'local'
                ? $this->listLocalFolderEntries($folder)
                : $this->listRemoteFolderEntries($activeDisk, $folder);

            // Newest first; on ties prefer files over dirs
            usort($entries, static function ($a, $b) {
                $timeCmp = ((int) ($b['modified'] ?? 0)) <=> ((int) ($a['modified'] ?? 0));
                if ($timeCmp !== 0) {
                    return $timeCmp;
                }

                $dirCmp = ((int) !empty($a['is_dir'])) <=> ((int) !empty($b['is_dir']));
                if ($dirCmp !== 0) {
                    return $dirCmp;
                }

                return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
            });

            $totalItems = count($entries);
            $page = array_slice($entries, $offset, $limit);
            $nextOffset = ($offset + $limit) < $totalItems ? ($offset + $limit) : null;

            // Phase 2: build response payload only for the current page
            $pageType = $this->resolveMediaPageType($folder);
            $diskLabel = $activeDisk === 'local' ? 'local' : $activeDisk;
            foreach ($page as $entry) {
                $contents[] = $this->formatListedItem($entry, $pageType, $diskLabel);
            }
        } catch (\Exception $e) {
            Log::error('Error getting folder contents: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'contents' => $contents,
            'pagination' => [
                'next_offset' => $nextOffset,
                'total_items' => $totalItems,
                'current_offset' => $offset,
                'limit' => $limit,
                'has_more' => $nextOffset !== null,
            ],
        ]);
    }

    /**
     * Fast local listing: only name/path/mtime (no media URL / size yet).
     * Cached against directory mtime so new uploads invalidate automatically.
     */
    private function listLocalFolderEntries(string $folder): array
    {
        $storagePath = storage_path('app/public');
        $fullPath = $folder !== ''
            ? $storagePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $folder)
            : $storagePath;

        if (!is_dir($fullPath)) {
            return [];
        }

        $dirMtime = (int) (@filemtime($fullPath) ?: 0);
        $cacheKey = 'folder_listing:local:' . md5($folder) . ':' . $dirMtime;

        return Cache::remember($cacheKey, 60, static function () use ($fullPath, $folder) {
            $entries = [];
            $handle = opendir($fullPath);
            if ($handle === false) {
                return [];
            }

            while (($item = readdir($handle)) !== false) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $absolute = $fullPath . DIRECTORY_SEPARATOR . $item;
                $entries[] = [
                    'name' => $item,
                    'path' => ltrim(($folder !== '' ? $folder . '/' : '') . $item, '/'),
                    'absolute' => $absolute,
                    'is_dir' => is_dir($absolute),
                    'modified' => (int) (@filemtime($absolute) ?: 0),
                    'size' => null,
                ];
            }

            closedir($handle);

            return $entries;
        });
    }

    /**
     * Remote listing via a single listContents() call (includes mtime/size — no N+1 HEAD requests).
     */
    private function listRemoteFolderEntries(string $diskName, string $folder): array
    {
        $disk = Storage::disk($diskName);
        $entries = [];

        foreach ($disk->listContents($folder, false) as $item) {
            $path = method_exists($item, 'path') ? $item->path() : (string) ($item['path'] ?? '');
            if ($path === '') {
                continue;
            }

            $isDir = method_exists($item, 'isDir')
                ? $item->isDir()
                : (($item['type'] ?? '') === 'dir');

            $modified = 0;
            if (method_exists($item, 'lastModified')) {
                $modified = (int) ($item->lastModified() ?? 0);
            } elseif (isset($item['lastModified'])) {
                $modified = (int) $item['lastModified'];
            }

            $size = 0;
            if (!$isDir) {
                if ($item instanceof FileAttributes) {
                    $size = (int) ($item->fileSize() ?? 0);
                } elseif (isset($item['fileSize'])) {
                    $size = (int) $item['fileSize'];
                }
            }

            $entries[] = [
                'name' => basename($path),
                'path' => trim($path, '/'),
                'absolute' => $path,
                'is_dir' => $isDir,
                'modified' => $modified,
                'size' => $size,
            ];
        }

        return $entries;
    }

    private function resolveMediaPageType(string $folder): string
    {
        if ($folder === '') {
            return 'default';
        }

        $segments = explode('/', $folder);
        $imageIndex = array_search('image', $segments, true);
        $videoIndex = array_search('video', $segments, true);

        if ($imageIndex !== false && $imageIndex > 0) {
            return $segments[$imageIndex - 1];
        }

        if ($videoIndex !== false && $videoIndex > 0) {
            return $segments[$videoIndex - 1];
        }

        return end($segments) ?: 'default';
    }

    /**
     * Build API payload for one already-listed (and paginated) entry.
     */
    private function formatListedItem(array $entry, string $pageType, string $disk): array
    {
        $name = $entry['name'];
        $isDir = !empty($entry['is_dir']);
        $isVideo = (bool) preg_match('/\.(mp4|webm|avi|mov)$/i', $name);
        $isImage = (bool) preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $name);

        $mediaUrl = '';
        if (!$isDir && ($isVideo || $isImage)) {
            $mediaUrl = setBaseUrlWithFileName($name, $isVideo ? 'video' : 'image', $pageType);
        }

        $size = $entry['size'];
        if ($size === null && !$isDir && $disk === 'local' && !empty($entry['absolute']) && is_file($entry['absolute'])) {
            $size = (int) (@filesize($entry['absolute']) ?: 0);
        }

        return [
            'name' => $name,
            'path' => $entry['path'],
            'is_dir' => $isDir,
            'size' => (int) ($size ?? 0),
            'modified' => (int) ($entry['modified'] ?? 0),
            'media_url' => $mediaUrl,
            'is_video' => $isVideo,
            'is_image' => $isImage,
        ];
    }

    /**
     * Get media URL using helper function
     */
    public function getMediaUrl(Request $request)
    {
        $fileName = $request->get('file');
        $type = $request->get('type', 'image');
        $pageType = $request->get('page_type', 'default');

        // Call the helper function
        $url = setBaseUrlWithFileName($fileName, $type, $pageType);

        return response()->json([
            'success' => true,
            'url' => $url
        ]);
    }

}
