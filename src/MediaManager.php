<?php

namespace HasanHawary\MediaManager;

use Carbon\Carbon;
use HasanHawary\MediaManager\Contracts\HandlerInterface;
use HasanHawary\MediaManager\Exceptions\NoHandlerDefinedException;
use HasanHawary\MediaManager\Exceptions\UnsupportedTypeException;
use HasanHawary\MediaManager\Handlers\Base64Handler;
use HasanHawary\MediaManager\Handlers\ContentHandler;
use HasanHawary\MediaManager\Handlers\LocalPathHandler;
use HasanHawary\MediaManager\Handlers\UploadedFileHandler;
use HasanHawary\MediaManager\Handlers\UrlHandler;
use HasanHawary\MediaManager\Support\ChunkResolver;
use HasanHawary\MediaManager\Support\MediaMeta;
use HasanHawary\MediaManager\Support\PathNormalizer;
use HasanHawary\MediaManager\Support\RemoteMediaFetcher;
use HasanHawary\MediaManager\Support\UrlResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaManager
{
    protected ?HandlerInterface $handler = null;
    protected ?string $disk = null;
    protected ?string $visibility = 'public';
    protected ?string $fallbackExtension = null;
    protected ?string $path = 'files';
    protected ?string $namingMode = 'uuid';
    protected mixed $customName = null;
    protected ?string $pendingDeletePath = null;
    private const NAMING_STRATEGIES = ['uuid', 'hash', 'timestamp', 'original', 'custom'];

    public function __construct()
    {
        $this->disk = $this->config('media-manager.disk')
            ?? $this->config('filesystems.default')
            ?? 'local';
        $this->visibility = $this->config('media-manager.visibility', $this->visibility);
        $this->fallbackExtension = $this->config('media-manager.fallback_extension');
        $this->path = (new PathNormalizer())->directory(
            (string) $this->config('media-manager.path', $this->path)
        );
        $this->namingMode = (string) $this->config('media-manager.naming_strategy', $this->namingMode);
    }

    /*--------------------------------------------------------------
    |  Sources
    |--------------------------------------------------------------
    */
    public function fromBase64(string $base64): static
    {
        $this->handler = new Base64Handler($base64);
        return $this;
    }

    public function fromUrl(string $url, bool $storedLocal = false): static
    {
        $this->handler = new UrlHandler($url, $storedLocal);
        return $this;
    }

    public function fromLocalPath(string $path, bool $copy = true): static
    {
        $this->handler = new LocalPathHandler($path, $copy);
        return $this;
    }

    public function fromFile(UploadedFile $file): static
    {
        $this->handler = new UploadedFileHandler($file);
        return $this;
    }

    public function fromContent(string $content): static
    {
        $this->handler = new ContentHandler($content);
        return $this;
    }

    /**
     * Try to auto-detect the source type.
     *
     * @throws UnsupportedTypeException
     */
    public function from(mixed $item): static
    {
        return match (true) {
            is_string($item) && (new RemoteMediaFetcher())->isValidUrl($item) => $this->fromUrl($item),
            is_string($item) && preg_match('/^data:([a-z0-9+\-\.\/]+);base64,(.*)$/i', $item) => $this->fromBase64($item),
            is_string($item) && is_file($item) => $this->fromLocalPath($item),
            is_string($item) && $this->isPlainBase64($item) => $this->fromBase64($item),
            is_string($item) => $this->fromContent($item),
            $item instanceof UploadedFile => $this->fromFile($item),
            is_object($item) && method_exists($item, 'getPathname') => $this->fromFile(
                new UploadedFile(
                    $item->getPathname(),
                    basename($item->getPathname()),
                    null,
                    true
                )
            ),
            default => throw new UnsupportedTypeException('Unsupported media source type: '.get_debug_type($item)),
        };
    }

    public function to(string $path = 'files'): static
    {
        $this->path = (new PathNormalizer())->directory($path);
        return $this;
    }

    public function on(string $disk): static
    {
        if ($disk === '') {
            throw new \InvalidArgumentException('Disk name cannot be empty.');
        }

        $this->disk = $disk;
        return $this;
    }

    public function visibility(?string $visibility): static
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function fallbackExtension(?string $extension): static
    {
        $this->fallbackExtension = $extension;
        return $this;
    }

    public function keepOriginalName(): static
    {
        $this->namingMode = 'original';
        return $this;
    }

    public function generateName(string $strategy = 'uuid'): static
    {
        if (! in_array($strategy, self::NAMING_STRATEGIES, true) || $strategy === 'custom') {
            throw new \InvalidArgumentException("Unsupported naming strategy: {$strategy}");
        }

        $this->namingMode = $strategy;
        return $this;
    }

    public function withName(string|\Closure $nameOrCallback): static
    {
        $this->namingMode = 'custom';
        $this->customName = $nameOrCallback;
        return $this;
    }

    /**
     * @throws NoHandlerDefinedException
    */
    public function store(): string|array|null
    {
        if (!$this->handler) {
            throw new NoHandlerDefinedException('No media source handler has been selected.');
        }

        $path = $this->path ?: 'files';
        $options = [
            'disk' => $this->disk,
            'visibility' => $this->visibility,
            'fallbackExtension' => $this->fallbackExtension,
            'namingMode' => $this->namingMode,
            'customName' => $this->customName
        ];

        $result = $this->handler->store($path, $options);

        // Delete previous file after successful store
        if ($result && $this->pendingDeletePath) {
            $this->delete($this->pendingDeletePath);
            $this->pendingDeletePath = null;
        }

        return $result;
    }

    /**
    * Main Method to upload media
    *
    * @throws NoHandlerDefinedException
    */
    public function upload(mixed $value, ?string $path = null): string|array|null
    {
        if ($this->isEmptyUploadValue($value)) {
            return $this->pendingDeletePath;
        }

        if ($value === 'delete') {
            $this->delete($this->pendingDeletePath);
            $this->pendingDeletePath = null;

            return null;
        }

        if ($value === $this->pendingDeletePath) {
            return $this->pendingDeletePath;
        }

        $this->from($value);
        if ($path !== null) {
            $this->to($path);
        }

        return $this->store();
    }

    public function chunk(array $data): string|false
    {
        $resolver = new ChunkResolver($this->disk);

        return $resolver->upload(
            $data,
            isset($data['is_final']) ? (bool) $data['is_final'] : false
        );
    }

    public function replace(?string $oldPath = null): static
    {
        // Normalize to be valid path
        $this->pendingDeletePath = $this->resolvePath($oldPath);

        return $this;
    }

    public function exists(?string $item = null): bool
    {
        $path = $this->resolvePath($item);

        return $path !== null && Storage::disk($this->disk)->exists($path);
    }

    public function delete(array|string|null $files = null): void
    {
        $items = array_filter(Arr::wrap($files));
        foreach ($items as $item) {
            $file = $this->resolvePath($item);
            if (Storage::disk($this->disk)->exists($file)) {
                Storage::disk($this->disk)->delete($file);
            }
        }
    }

    public function safeDelete(array|string|null $files = null): void
    {
        $items = array_filter(Arr::wrap($files));

        foreach ($items as $item) {
            $file = $this->resolvePath($item);

            if (Storage::disk($this->disk)->exists($file)) {
                $trashPath = 'trash/' . basename($file);

                if (Storage::disk($this->disk)->exists($trashPath)) {
                    $trashPath = 'trash/' . uniqid() . '_' . basename($file);
                }

                Storage::disk($this->disk)->move($file, $trashPath);
            }
        }
    }

    public function url(string|array|null $paths = null): array|string|null
    {
        return (new UrlResolver($paths, $this->disk))->url();
    }

    public function temporaryUrl(string|array|null $paths = null, int $minutes = 5): array|string|null
    {
        return (new UrlResolver($paths, $this->disk))->temporaryUrl($minutes);
    }

    public function signedUrl(string|array|null $paths = null, ?\DateTimeInterface $expiresAt = null): array|string|null
    {
        $expiresAt ??= Carbon::now()->addMinutes(5);

        return (new UrlResolver($paths, $this->disk))->signedUrl($expiresAt);
    }

    public function meta(mixed $paths): MediaMeta
    {
        return new MediaMeta($paths, $this->disk);
    }

    public function getPendingDeletePath(): ?string
    {
        return $this->pendingDeletePath;
    }

    public function resolvePath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // if an array-like string was passed, take the first element (defensive)
        if (is_array($path)) {
            $path = reset($path);
        }

        // Handle full URLs: only resolve if it belongs to configured disk
        $diskUrl = Storage::disk($this->disk)->url('');
        if (Str::startsWith($path, ['http://', 'https://'])) {
            if (Str::startsWith($path, $diskUrl)) {
                $path = Str::after($path, $diskUrl);
            } else {
                return null;
            }
        }

        // Remove storage prefix if present (for local/public disk)
        $path = Str::after($path, 'storage/');

        // Remove any leading slashes
        $path = ltrim($path, '/');

        return $path === '' ? null : $path;
    }

    private function isPlainBase64(string $value): bool
    {
        $normalized = preg_replace('/\s+/', '', $value);
        if (! is_string($normalized) || strlen($normalized) < 16 || strlen($normalized) % 4 !== 0) {
            return false;
        }

        if (! preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $normalized)) {
            return false;
        }

        $decoded = base64_decode($normalized, true);
        if ($decoded === false) {
            return false;
        }

        return rtrim(base64_encode($decoded), '=') === rtrim($normalized, '=');
    }

    private function isEmptyUploadValue(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return function_exists('config') ? config($key, $default) : $default;
    }
}
