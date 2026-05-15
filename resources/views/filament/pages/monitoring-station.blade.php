<x-filament-panels::page>
    <div
        x-data="monitoringStation"
        x-init="initCamera()"
        class="space-y-6"
    >
        {{-- Status Bar --}}
        <div class="flex items-center justify-between rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg"
                    :class="cameraReady ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'"
                >
                    <x-heroicon-o-video-camera class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">
                        Camera Status
                    </p>
                    <p class="text-xs" :class="cameraReady ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                        <span x-text="cameraReady ? 'Online — Ready to scan' : 'Offline — Connecting...'"></span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                {{-- Scan counter --}}
                <div class="hidden sm:flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-signal class="h-4 w-4" />
                    <span>Scans: <span class="font-semibold text-gray-950 dark:text-white" x-text="scanCount">0</span></span>
                </div>
                {{-- Live indicator --}}
                <div class="flex h-3 w-3 items-center justify-center relative">
                    <span
                        class="absolute inline-flex h-3 w-3 animate-ping rounded-full opacity-75"
                        :class="cameraReady ? 'bg-green-400' : 'bg-red-400'"
                    ></span>
                    <span
                        class="relative inline-flex h-2 w-2 rounded-full"
                        :class="cameraReady ? 'bg-green-500' : 'bg-red-500'"
                    ></span>
                </div>
            </div>
        </div>

        {{-- Hidden RFID Input — always focused --}}
        <input
            type="text"
            x-ref="rfidInput"
            x-model="rfidBuffer"
            @keydown.enter.prevent="handleScan()"
            @blur="$nextTick(() => { if($refs.rfidInput) $refs.rfidInput.focus() })"
            class="sr-only"
            autocomplete="off"
            autofocus
            id="rfid-scanner-input"
        />

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- LEFT: Webcam Preview --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-video-camera class="h-5 w-5 text-primary-500" />
                            Live Camera Feed
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Scan an RFID tag to capture a snapshot automatically.
                        </p>
                    </div>
                    <div class="relative p-4">
                        {{-- Video element --}}
                        <video
                            x-ref="video"
                            autoplay
                            playsinline
                            muted
                            class="w-full rounded-lg bg-gray-100 dark:bg-gray-800"
                            style="max-height: 480px; object-fit: cover;"
                        ></video>

                        {{-- Canvas for snapshot (hidden) --}}
                        <canvas x-ref="canvas" class="hidden"></canvas>

                        {{-- Flash overlay --}}
                        <div
                            x-ref="flashOverlay"
                            class="pointer-events-none absolute inset-4 rounded-lg bg-white opacity-0 transition-opacity duration-150"
                        ></div>

                        {{-- Scanning indicator --}}
                        <div
                            x-show="isProcessing"
                            x-transition
                            class="absolute inset-4 flex items-center justify-center rounded-lg bg-gray-900/60 backdrop-blur-sm"
                        >
                            <div class="text-center">
                                <svg class="mx-auto h-8 w-8 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="mt-2 text-sm font-medium text-white">Processing scan...</p>
                            </div>
                        </div>

                        {{-- Camera offline placeholder --}}
                        <div
                            x-show="!cameraReady && !isProcessing"
                            class="absolute inset-4 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800"
                        >
                            <div class="text-center">
                                <x-heroicon-o-video-camera class="mx-auto h-12 w-12 text-gray-400" />
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Connecting to camera...</p>
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Make sure your webcam is connected and allowed.</p>
                            </div>
                        </div>
                    </div>

                    {{-- RFID Input Hint --}}
                    <div class="border-t border-gray-200 px-6 py-3 dark:border-white/10">
                        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-signal class="h-4 w-4 shrink-0" />
                            <span>
                                Waiting for RFID scan...
                                <span class="font-medium text-primary-600 dark:text-primary-400">
                                    Tap your RFID tag or type a UID and press Enter to simulate.
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Last Scan Result --}}
                <div
                    x-show="lastScanResult"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="overflow-hidden rounded-xl shadow-sm ring-1"
                    :class="lastScanResult?.success
                        ? 'bg-green-50 ring-green-200 dark:bg-green-950/30 dark:ring-green-800'
                        : 'bg-red-50 ring-red-200 dark:bg-red-950/30 dark:ring-red-800'"
                >
                    <div class="p-6">
                        {{-- Header row: icon + status text --}}
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                                :class="lastScanResult?.success
                                    ? 'bg-green-500/10 text-green-600 dark:text-green-400'
                                    : 'bg-red-500/10 text-red-600 dark:text-red-400'"
                            >
                                <template x-if="lastScanResult?.success && lastScanResult?.type === 'check-in'">
                                    <x-heroicon-o-arrow-down-tray class="h-6 w-6" />
                                </template>
                                <template x-if="lastScanResult?.success && lastScanResult?.type === 'check-out'">
                                    <x-heroicon-o-arrow-up-tray class="h-6 w-6" />
                                </template>
                                <template x-if="!lastScanResult?.success">
                                    <x-heroicon-o-exclamation-triangle class="h-6 w-6" />
                                </template>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-lg font-semibold"
                                    :class="lastScanResult?.success ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'"
                                >
                                    <span x-show="lastScanResult?.success" x-text="lastScanResult?.type === 'check-in' ? '✅ CHECK IN' : '📤 CHECK OUT'"></span>
                                    <span x-show="!lastScanResult?.success">⚠️ Scan Failed</span>
                                </h4>
                                <div x-show="lastScanResult?.success" class="mt-2 space-y-1">
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-medium">Item:</span>
                                        <span x-text="lastScanResult?.item_name"></span>
                                    </p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-medium">Category:</span>
                                        <span x-text="lastScanResult?.item_category"></span>
                                    </p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-medium">RFID:</span>
                                        <span x-text="lastScanResult?.rfid_uid" class="font-mono"></span>
                                    </p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-medium">Time:</span>
                                        <span x-text="lastScanResult?.scanned_at"></span>
                                    </p>
                                </div>
                                <div x-show="!lastScanResult?.success" class="mt-1">
                                    <p class="text-sm text-red-700 dark:text-red-300" x-text="lastScanResult?.message"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Captured photo (below text, fixed size) --}}
                        <div x-show="lastScanResult?.success && lastScanResult?.photo_url" class="mt-4">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">📷 Captured Photo</p>
                            <img
                                x-bind:src="lastScanResult?.photo_url"
                                alt="Captured snapshot"
                                style="width: 240px; height: 160px; object-fit: cover;"
                                class="rounded-lg ring-1 ring-gray-200 dark:ring-white/10"
                            />
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Activity Feed --}}
            <div class="space-y-6">
                {{-- Quick Stats --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-500/10">
                                <x-heroicon-o-arrow-down-tray class="h-5 w-5 text-green-500" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-950 dark:text-white">
                                    {{ \App\Models\MonitoringLog::where('type', 'check-in')->whereDate('scanned_at', today())->count() }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Check-ins Today</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-500/10">
                                <x-heroicon-o-arrow-up-tray class="h-5 w-5 text-red-500" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-950 dark:text-white">
                                    {{ \App\Models\MonitoringLog::where('type', 'check-out')->whereDate('scanned_at', today())->count() }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Check-outs Today</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Total registered items --}}
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-500/10">
                            <x-heroicon-o-cube class="h-5 w-5 text-primary-500" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-950 dark:text-white">
                                {{ \App\Models\Item::count() }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Registered Items</p>
                        </div>
                    </div>
                </div>

                {{-- Recent Activity --}}
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-clock class="h-5 w-5 text-primary-500" />
                            Recent Activity
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-white/10" style="max-height: 500px; overflow-y: auto;">
                        @forelse ($recentLogs as $log)
                            <div class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <img
                                    src="{{ $log['photo_url'] }}"
                                    alt="Scan photo"
                                    class="h-10 w-10 shrink-0 rounded-lg object-cover ring-1 ring-gray-200 dark:ring-white/10"
                                    onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect fill=%22%23e5e7eb%22 width=%2240%22 height=%2240%22/><text x=%2220%22 y=%2224%22 font-size=%2212%22 text-anchor=%22middle%22 fill=%22%239ca3af%22>N/A</text></svg>'"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                                        {{ $log['item_name'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $log['scanned_at'] }}
                                    </p>
                                </div>
                                <span @class([
                                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                    'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20' => $log['type'] === 'check-in',
                                    'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20' => $log['type'] === 'check-out',
                                ])>
                                    {{ $log['type'] === 'check-in' ? 'IN' : 'OUT' }}
                                </span>
                            </div>
                        @empty
                            <div class="px-6 py-12 text-center">
                                <x-heroicon-o-inbox class="mx-auto h-10 w-10 text-gray-400" />
                                <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                                    No scans yet
                                </p>
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                    Start scanning RFID tags to see activity here.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        Alpine.data('monitoringStation', () => ({
            cameraReady: false,
            isProcessing: false,
            rfidBuffer: '',
            lastScanResult: null,
            stream: null,
            scanCount: 0,

            async initCamera() {
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            width: { ideal: 1280 },
                            height: { ideal: 720 },
                            facingMode: 'environment'
                        },
                        audio: false
                    });

                    this.$refs.video.srcObject = this.stream;
                    this.cameraReady = true;

                    // Keep RFID input focused
                    this.$refs.rfidInput.focus();
                } catch (err) {
                    console.error('Camera access error:', err);
                    this.cameraReady = false;

                    // Still keep RFID input focused even without camera
                    if (this.$refs.rfidInput) {
                        this.$refs.rfidInput.focus();
                    }
                }
            },

            captureSnapshot() {
                const video = this.$refs.video;
                const canvas = this.$refs.canvas;

                if (!video.videoWidth || !video.videoHeight) {
                    return '';
                }

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                return canvas.toDataURL('image/jpeg', 0.85);
            },

            flashEffect() {
                const overlay = this.$refs.flashOverlay;
                overlay.style.opacity = '0.8';
                setTimeout(() => {
                    overlay.style.opacity = '0';
                }, 150);
            },

            async handleScan() {
                const rfid = this.rfidBuffer.trim();
                if (!rfid || this.isProcessing) return;

                this.isProcessing = true;

                // Flash effect
                if (this.cameraReady) {
                    this.flashEffect();
                }

                // Capture snapshot
                const imageData = this.cameraReady ? this.captureSnapshot() : '';

                // Clear buffer immediately
                this.rfidBuffer = '';

                try {
                    // Call Livewire method
                    await $wire.processScan(rfid, imageData);

                    // Increment scan counter
                    this.scanCount++;

                    // Update last scan from Livewire state
                    this.$nextTick(() => {
                        this.lastScanResult = $wire.lastScan;
                    });
                } catch (err) {
                    console.error('Scan processing error:', err);
                    this.lastScanResult = {
                        success: false,
                        message: 'Connection error. Please try again.'
                    };
                } finally {
                    this.isProcessing = false;

                    // Re-focus the input
                    this.$nextTick(() => {
                        if (this.$refs.rfidInput) {
                            this.$refs.rfidInput.focus();
                        }
                    });
                }
            }
        }));
    </script>
    @endscript
</x-filament-panels::page>
