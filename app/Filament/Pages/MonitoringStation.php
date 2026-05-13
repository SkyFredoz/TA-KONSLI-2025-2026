<?php

namespace App\Filament\Pages;

use App\Models\Item;
use App\Models\MonitoringLog;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MonitoringStation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'Monitoring Station';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Monitoring Station';

    protected static ?string $slug = 'monitoring-station';

    protected static string $view = 'filament.pages.monitoring-station';

    /**
     * The scanned RFID UID.
     */
    public string $rfidUid = '';

    /**
     * The last scan result to display in the UI.
     */
    public ?array $lastScan = null;

    /**
     * Recent scan logs for the activity feed.
     */
    public array $recentLogs = [];

    public function mount(): void
    {
        $this->loadRecentLogs();
    }

    /**
     * Process an RFID scan with webcam snapshot.
     *
     * Called from Alpine.js when Enter is pressed in the hidden input.
     */
    public function processScan(string $rfidUid, string $imageData): void
    {
        $rfidUid = trim($rfidUid);

        if (empty($rfidUid)) {
            Notification::make()
                ->title('Scan Error')
                ->body('No RFID UID received. Please try again.')
                ->danger()
                ->send();
            return;
        }

        // Find the item by RFID UID
        $item = Item::where('rfid_uid', $rfidUid)->first();

        if (!$item) {
            Notification::make()
                ->title('Item Not Found')
                ->body("No item registered with RFID UID: {$rfidUid}")
                ->danger()
                ->icon('heroicon-o-exclamation-triangle')
                ->duration(5000)
                ->send();

            $this->lastScan = [
                'success' => false,
                'message' => "RFID UID \"{$rfidUid}\" is not registered. Please register the item first.",
                'rfid_uid' => $rfidUid,
            ];

            $this->rfidUid = '';
            return;
        }

        // Determine the type based on last log (toggle check-in / check-out)
        $lastLog = $item->monitoringLogs()->latest('scanned_at')->first();
        $type = (!$lastLog || $lastLog->type === 'check-out') ? 'check-in' : 'check-out';

        // Save the webcam snapshot
        $photoPath = $this->saveSnapshot($imageData, $rfidUid);

        // Create the monitoring log
        $log = MonitoringLog::create([
            'item_id' => $item->id,
            'photo_path' => $photoPath,
            'type' => $type,
            'scanned_at' => now(),
        ]);

        // Send success notification
        $typeLabel = $type === 'check-in' ? '✅ CHECK IN' : '📤 CHECK OUT';
        $icon = $type === 'check-in' ? 'heroicon-o-arrow-down-tray' : 'heroicon-o-arrow-up-tray';

        Notification::make()
            ->title("{$typeLabel} — {$item->name}")
            ->body("RFID: {$rfidUid} | Category: {$item->category}")
            ->success()
            ->icon($icon)
            ->duration(5000)
            ->send();

        $this->lastScan = [
            'success' => true,
            'item_name' => $item->name,
            'item_category' => $item->category,
            'rfid_uid' => $rfidUid,
            'type' => $type,
            'photo_url' => $photoPath ? Storage::disk('public')->url($photoPath) : null,
            'scanned_at' => now()->format('d M Y, H:i:s'),
        ];

        $this->rfidUid = '';
        $this->loadRecentLogs();
    }

    /**
     * Save a Base64 encoded snapshot to storage.
     */
    protected function saveSnapshot(string $imageData, string $rfidUid): string
    {
        if (empty($imageData) || !str_contains($imageData, 'base64')) {
            // If no image data (camera offline), create a placeholder path
            return 'logs/no-camera.jpg';
        }

        try {
            // Ensure the logs directory exists
            Storage::disk('public')->makeDirectory('logs');

            // Extract the Base64 data (remove data:image/...;base64, prefix)
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $decodedImage = base64_decode($imageData);

            if ($decodedImage === false) {
                Log::warning('Failed to decode Base64 image data for RFID: ' . $rfidUid);
                return 'logs/decode-error.jpg';
            }

            // Generate a unique filename
            $filename = 'logs/' . now()->format('Ymd_His') . '_' . Str::slug($rfidUid) . '_' . Str::random(6) . '.jpg';

            // Save to storage/app/public/logs
            Storage::disk('public')->put($filename, $decodedImage);

            return $filename;
        } catch (\Exception $e) {
            Log::error('Failed to save snapshot: ' . $e->getMessage());
            return 'logs/error.jpg';
        }
    }

    /**
     * Load recent monitoring logs for the activity feed.
     */
    protected function loadRecentLogs(): void
    {
        $logs = MonitoringLog::with('item')
            ->latest('scanned_at')
            ->take(15)
            ->get();

        $this->recentLogs = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'item_name' => $log->item->name ?? 'Unknown',
                'item_category' => $log->item->category ?? '-',
                'rfid_uid' => $log->item->rfid_uid ?? '-',
                'type' => $log->type,
                'photo_url' => Storage::disk('public')->url($log->photo_path),
                'scanned_at' => $log->scanned_at->format('d M Y, H:i:s'),
            ];
        })->toArray();
    }
}
