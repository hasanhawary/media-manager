# Media Manager

[![Latest Stable Version](https://img.shields.io/packagist/v/hasanhawary/media-manager.svg)](https://packagist.org/packages/hasanhawary/media-manager)
[![Total Downloads](https://img.shields.io/packagist/dm/hasanhawary/media-manager.svg)](https://packagist.org/packages/hasanhawary/media-manager)
[![PHP Version](https://img.shields.io/packagist/php-v/hasanhawary/media-manager.svg)](https://packagist.org/packages/hasanhawary/media-manager)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A **powerful, fluent Laravel package** for managing file uploads, replacements, chunked uploads, URL generation, metadata, and safe deletion. Works with any disk (`local`, `public`, `s3`, etc.) and supports multiple input types (`UploadedFile`, base64, URL, raw content, or local path).

---

## ⚡ Installation

```bash
composer require hasanhawary/media-manager
```

Auto-discovered in Laravel.
Facade alias: `Media` → `HasanHawary\MediaManager\Facades\Media`.

Ensure your disks are configured in `config/filesystems.php`.

---

## 🟢 Quick Start

### Upload a file

```php
use HasanHawary\MediaManager\Facades\Media;

$path = Media::upload($request->file('avatar'), 'uploads/avatars');
$url = Media::url($path);
```

Fluent API:

```php
$path = Media::on('public')
    ->generateName('uuid')      // naming strategy
    ->visibility('private')     // visibility
    ->fallbackExtension('png')  // optional fallback
    ->upload($file, 'uploads/photos');
```

---

## 🔹 Supported Input Types

| Input Type     | Example                           |
| -------------- | --------------------------------- |
| `UploadedFile` | `$request->file('avatar')`        |
| Base64 string  | `$base64Image`                    |
| URL            | `'https://example.com/photo.jpg'` |
| Raw content    | `'Hello world'`                   |
| Local path     | `storage_path('temp/file.pdf')`   |

```php
Media::upload($request->file('avatar'), 'uploads/avatars');
Media::upload($base64Image, 'uploads/images');
Media::upload('https://example.com/photo.jpg', 'uploads/images');
Media::upload(file_get_contents('path/to/file.txt'), 'uploads/docs');
Media::upload(storage_path('app/temp/logo.png'), 'uploads/logos');
```

---

## 🔹 Naming Strategies

| Strategy    | Description                  |
| ----------- | ---------------------------- |
| `uuid`      | Default; auto-generated UUID |
| `hash`      | MD5 hash of file contents    |
| `timestamp` | Current timestamp            |
| `original`  | Keep the original filename   |
| `custom`    | Custom string or closure     |

```php
Media::generateName('hash')->upload($file, 'uploads/files');
Media::generateName('timestamp')->upload($file, 'uploads/files');
Media::keepOriginalName()->upload($file, 'uploads/files');
Media::withName(fn() => 'custom_name_' . time())->upload($file, 'uploads/files');
```

---

## 🔹 Replace Existing Files

```php
$path = Media::replace($user->avatar)->upload($request->file('avatar'), 'uploads/avatars');
$user->update(['avatar' => $path]);
```

| Input Value    | Behavior                                |
| -------------- | --------------------------------------- |
| `null` or `''` | Keep old file                           |
| `'delete'`     | Deletes old file and returns `null`     |
| Same path      | Keeps existing file                     |
| New file       | Uploads new file and deletes old safely |

---

## 🔹 Chunk Uploads (`Media::chunk()`)

Upload large files in chunks:

**Parameters (`$data`)**:

| Parameter      | Type              | Description                               |
| -------------- | ----------------- | ----------------------------------------- |
| `file_name`    | string            | Original file name (with extension)       |
| `chunk_number` | int               | Current chunk number (starts at 1)        |
| `chunk_file`   | UploadedFile      | File chunk                                |
| `is_final`     | bool (optional)   | True if last chunk (merges automatically) |
| `user_id`      | int (optional)    | ID of user; defaults to `auth()->id()`    |
| `directory`    | string (optional) | Final file directory (default: `uploads`) |

```php
$path = Media::chunk([
    'file_name'    => $request->file_name,
    'chunk_number' => $request->chunk_number,
    'chunk_file'   => $request->file('chunk_file'),
    'is_final'     => $request->boolean('is_final'),
    'user_id'      => $request->user_id, // optional
    'directory'    => 'videos',          // optional
]);

if ($request->boolean('is_final')) {
    echo "Final file saved at: $path";
} else {
    echo "Chunk {$request->chunk_number} uploaded successfully";
}
```

---

## 🔹 URL Generation

```php
Media::url($path);                             // Absolute URL
Media::on('public')->url($path);               // Disk-specific
Media::on('s3')->temporaryUrl($path, 10);      // Temporary URL (minutes)
Media::on('s3')->signedUrl($path, now()->addDay()); // Signed URL
```

---

## 🔹 Metadata

```php
$meta = Media::meta('uploads/docs/report.pdf');

$meta->size();          // bytes
$meta->mime();          // mime type
$meta->extension();     // file extension
$meta->basename();      // file name
$meta->dimensions();    // [width, height]
$meta->toArray();       // all metadata
```

---

## 🔹 File Operations

```php
Media::exists($path);            // Check if file exists
Media::delete($path);            // Delete file
Media::safeDelete($path);        // Move to trash
```

**Safe delete** moves files to `trash/` instead of permanently deleting.

---

## 🔹 Advanced Options

```php
Media::on('s3')
    ->visibility('private')
    ->generateName('hash')
    ->fallbackExtension('png')
    ->safeDelete(true)
    ->upload($file, 'uploads');
```

---

## ✅ Compatibility

* **PHP:** 8.0 → 8.5
* **Laravel:** 8 → 12

---

## 🆕 Changelog (v1.2.0)

* Added `chunk()` for large file uploads
* Added all naming strategies: `uuid`, `hash`, `timestamp`, `original`, `custom`
* Fluent API for disk, visibility, fallback extension
* Safe delete (`safeDelete`) and metadata support
* Improved documentation and examples

---

## 📜 License

MIT © [Hasan Hawary](https://github.com/hasanhawary)

---
