# Media Manager

[![Latest Stable Version](https://img.shields.io/packagist/v/hasanhawary/media-manager.svg)](https://packagist.org/packages/hasanhawary/media-manager)
[![Total Downloads](https://img.shields.io/packagist/dm/hasanhawary/media-manager.svg)](https://packagist.org/packages/hasanhawary/media-manager)
[![PHP Version](https://img.shields.io/packagist/php-v/hasanhawary/media-manager.svg)](https://packagist.org/packages/hasanhawary/media-manager)
[![Tests](https://github.com/hasanhawary/media-manager/actions/workflows/tests.yml/badge.svg)](https://github.com/hasanhawary/media-manager/actions/workflows/tests.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Media Manager is a reusable Laravel package for storing files from uploaded files, base64 strings, remote URLs, raw content, and local paths. It supports disk selection, naming strategies, replacement flows, chunked uploads, URL generation, metadata, delete, and safe delete.

The package is self-contained and does not depend on a host app namespace such as `App\...`.

## Requirements

- PHP 8.1+
- Laravel 10, 11, 12, or 13
- Configured Laravel filesystem disks

Some low-level helpers such as `PathNormalizer`, `FileNameGenerator`, and `RemoteMediaFetcher` can be used in plain PHP. Storage, facades, `UploadedFile`, URL generation, metadata, and chunk uploads are Laravel filesystem features.

## Installation

```bash
composer require hasanhawary/media-manager
```

Laravel discovers the service provider and facade alias automatically:

```php
use HasanHawary\MediaManager\Facades\Media;
```

Publish the optional config file:

```bash
php artisan vendor:publish --tag=media-manager-config
```

## Configuration

Published config: `config/media-manager.php`

```php
return [
    'disk' => null,
    'path' => 'files',
    'visibility' => 'public',
    'fallback_extension' => 'jpg',
    'naming_strategy' => 'uuid',

    'chunks' => [
        'directory' => 'chunks',
        'uploads_directory' => 'uploads',
    ],

    'routes' => [
        'enabled' => true,
        'prefix' => 'media-manager',
        'middleware' => [],
        'name' => 'media-manager.',
    ],
];
```

`disk => null` means the package uses `filesystems.default`.

The package registers `POST /media-manager/chunk-file` by default. Change `routes.prefix`, `routes.middleware`, or set `routes.enabled` to `false` if you need to avoid route conflicts.

## Quick Start

```php
use HasanHawary\MediaManager\Facades\Media;

$path = Media::upload($request->file('avatar'), 'uploads/avatars');
$url = Media::url($path);
```

Fluent options:

```php
$path = Media::on('public')
    ->visibility('private')
    ->fallbackExtension('png')
    ->generateName('uuid')
    ->upload($request->file('photo'), 'uploads/photos');
```

If no path is passed to `upload()`, the package uses `media-manager.path`.

## Supported Sources

| Source | Example |
| --- | --- |
| Uploaded file | `$request->file('avatar')` |
| Base64 data URI | `data:image/png;base64,...` |
| Plain base64 string | `base64_encode($contents)` |
| Remote URL | `https://example.com/photo.jpg` |
| Raw content | `Hello world` |
| Local path | `storage_path('app/temp/logo.png')` |

```php
Media::upload($request->file('avatar'), 'avatars');
Media::upload($base64Image, 'images');
Media::fromUrl('https://example.com/photo.jpg', storedLocal: true)->to('images')->store();
Media::upload(file_get_contents('report.txt'), 'documents');
Media::upload(storage_path('app/temp/logo.png'), 'logos');
```

Remote URLs are validated before fetching. Local/private IP URLs are rejected by default to reduce SSRF risk.

## Naming

| Strategy | Behavior |
| --- | --- |
| `uuid` | Generated UUID filename |
| `hash` | MD5 hash for uploaded files, UUID fallback for generated content |
| `timestamp` | Timestamp plus random suffix |
| `original` | Keep the original filename when available |
| `custom` | Use `withName()` string or closure |

```php
Media::generateName('hash')->upload($file, 'files');
Media::generateName('timestamp')->upload($file, 'files');
Media::keepOriginalName()->upload($file, 'files');
Media::withName(fn () => 'invoice_'.time().'.pdf')->upload($file, 'invoices');
```

Filenames are sanitized with `basename()` before storage.

## Replacing Files

```php
$path = Media::replace($user->avatar)
    ->upload($request->file('avatar'), 'uploads/avatars');

$user->update(['avatar' => $path]);
```

| Input value | Behavior |
| --- | --- |
| `null`, `''`, or `[]` | Keep the old path |
| `'delete'` | Delete the old file and return `null` |
| Same path | Keep the existing file |
| New source | Store the new file and delete the old file after success |

## Chunk Uploads

Use the fluent API directly:

```php
$pathOrChunkNumber = Media::chunk([
    'file_name' => $request->input('file_name'),
    'chunk_number' => $request->integer('chunk_number'),
    'chunk_file' => $request->file('chunk_file'),
    'is_final' => $request->boolean('is_final'),
    'user_id' => $request->user()?->id,
    'directory' => 'videos',
]);
```

| Key | Required | Description |
| --- | --- | --- |
| `file_name` | yes | Final filename with extension |
| `chunk_number` | yes | 1-based chunk number |
| `chunk_file` | yes | `Illuminate\Http\UploadedFile` chunk |
| `is_final` | no | Merge chunks when true |
| `user_id` | sometimes | Used to isolate chunk directories |
| `directory` | no | Final merged file directory |

If `user_id` is not provided, Laravel apps fall back to `auth()->id()`. Outside Laravel auth, `user_id` is required.

Chunks are stored under `media-manager.chunks.directory`, merged in numeric order, streamed into the final file, and then cleaned up.

Or use the default package route:

```http
POST /media-manager/chunk-file
```

Multipart form fields:

| Field | Required | Description |
| --- | --- | --- |
| `file_name` | yes | Final filename with extension |
| `chunk_number` | yes | 1-based chunk number |
| `chunk_file` | yes | Uploaded chunk file |
| `is_final` | no | Boolean-ish value such as `1`, `true`, or `0` |
| `user_id` | sometimes | Required when no authenticated Laravel user exists |
| `directory` | no | Final merged file directory |

Successful non-final response:

```json
{
  "status": true,
  "code": 200,
  "message": "Chunk uploaded successfully.",
  "data": {
    "path": "1",
    "is_final": false
  }
}
```

Successful final response:

```json
{
  "status": true,
  "code": 201,
  "message": "File assembled successfully.",
  "data": {
    "path": "videos/(1234)_movie.mp4",
    "is_final": true
  }
}
```

## URLs

```php
Media::url($path);
Media::on('public')->url($path);
Media::on('s3')->temporaryUrl($path, 10);
Media::on('s3')->signedUrl($path, now()->addDay());
```

`url()`, `temporaryUrl()`, and `signedUrl()` return `null` for missing paths. Arrays of paths return an array when more than one path resolves.

## Metadata

```php
$meta = Media::meta('uploads/docs/report.pdf');

$meta->path();
$meta->url();
$meta->size();
$meta->mime();
$meta->extension();
$meta->basename();
$meta->filename();
$meta->dirname();
$meta->lastModified();
$meta->hash();
$meta->dimensions();
$meta->toArray();
```

`MediaMeta` is JSON serializable.

## File Operations

```php
Media::exists($path);
Media::delete($path);
Media::safeDelete($path);
```

`exists()`, `delete()`, and `safeDelete()` normalize package-generated disk URLs back to disk paths. External URLs are ignored.

`safeDelete()` moves files to `trash/` on the selected disk.

## Exceptions

The package fails explicitly for invalid usage:

| Exception | Example |
| --- | --- |
| `NoHandlerDefinedException` | Calling `store()` before selecting a source |
| `UnsupportedTypeException` | Passing an unsupported source type |
| `InvalidArgumentException` | Invalid naming strategy, chunk data, empty disk name |

`ApiHandler` and `ZipHandler` are placeholders and throw `UnsupportedTypeException`.

## Plain PHP Notes

These classes do not require a Laravel application container:

```php
use HasanHawary\MediaManager\Support\FileNameGenerator;
use HasanHawary\MediaManager\Support\PathNormalizer;
use HasanHawary\MediaManager\Support\RemoteMediaFetcher;

$name = FileNameGenerator::generate('txt', 'uuid');
$path = (new PathNormalizer())->directory('../unsafe');
$isPublicUrl = (new RemoteMediaFetcher())->isValidUrl('https://example.com/file.jpg');
```

Storage-backed operations require Laravel filesystem components.

## Testing

```bash
composer install
composer test
composer audit
```

The package test suite is isolated from any host project.

## License

MIT © [Hasan Hawary](https://github.com/hasanhawary)
