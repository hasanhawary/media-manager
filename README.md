# Media Manager

[![Latest Stable Version](https://img.shields.io/packagist/v/hasanhawary/media-manager.svg)](https://packagist.org/packages/hasanhawary/media-manager)
[![Total Downloads](https://img.shields.io/packagist/dm/hasanhawary/media-manager.svg)](https://packagist.org/packages/hasanhawary/media-manager)
[![PHP Version](https://img.shields.io/packagist/php-v/hasanhawary/media-manager.svg)](https://packagist.org/packages/hasanhawary/media-manager)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A clean, expressive Laravel package for managing media uploads.
Easily upload, replace, and generate URLs for files from **any input type** —
`UploadedFile`, `base64`, `URL`, or raw content — to **any disk** (`local`, `public`, `s3`, etc.).

---

## ⚡ Quick Start

The fastest way to manage uploads, replacements, and URLs.

### 🟩 1. Upload a file

```php
use HasanHawary\MediaManager\Facades\Media;

// Simplest form
$path = Media::upload(request()->file('avatar'), 'uploads/avatars');

// Get URL
$url = Media::url($path);
```

You can chain configuration for disk, naming, or visibility:

```php
$path = Media::on('public')
    ->generateName('uuid')
    ->upload(request()->file('avatar'), 'uploads/avatars');
```

---

### 🟨 Upload Supports Multiple Input Types

The `$item` parameter can be **any of the following**:

* `UploadedFile` → from form uploads
* `base64 string` → from mobile/API uploads
* `URL` → download and store from remote link
* `string content` → direct raw data (text, JSON, etc.)
* `local file path` → existing file on the server (`/tmp/file.pdf`, `storage/app/x.png`)

```php
Media::upload($request->file('avatar'), 'uploads/avatars');
Media::upload($base64Image, 'uploads/images');
Media::upload('https://example.com/photo.jpg', 'uploads/images');
Media::upload(file_get_contents('path/to/file.pdf'), 'uploads/docs');
Media::upload(storage_path('app/temp/logo.png'), 'uploads/logos');
```
---

### 🟦 Replace an Existing File

Safely update existing files while automatically deleting the old one.

```php
$path = Media::replace($user->avatar)->upload($request->file('avatar'), 'uploads/avatars');

$user->update(['avatar' => $path]);
```

| Input Value  | Behavior                            |
| ------------ | ----------------------------------- |
| `null`, `''` | Keeps old file                      |
| `'delete'`   | Deletes old file and returns `null` |
| Same path    | Keeps existing file                 |
| New file     | Uploads new and deletes old safely  |

You can combine it with chainable configuration:

```php
$path = Media::replace($user->avatar)
    ->on('s3')
    ->generateName('uuid')
    ->fallbackExtension('jpg')
    ->upload($request->file('avatar'), 'avatars');
```

---

### 🌍 Generate URLs

```php
Media::url($path);                            // Absolute URL
Media::on('public')->url($path);              // Disk-specific
Media::on('s3')->temporaryUrl($path, 10);     // Temporary URL (minutes)
Media::on('s3')->signedUrl($path, now()->addDay()); // Signed URL
```

---

## 🧩 Main Features

### 🗂 Upload

* Accepts multiple input types (`UploadedFile`, base64, URL, raw content)
* Works with all Laravel disks (`local`, `public`, `s3`, etc.)
* Auto-handles directory creation and naming
* Supports fluent configuration:

  ```php
  Media::on('public')
      ->generateName('hash')
      ->fallbackExtension('png')
      ->upload($file, 'uploads/photos');
  ```

---

### 🔁 Replace (Safe Update)

* Automatically keeps or deletes old file based on new input
* Deletes only after successful upload
* Perfect for model updates or profile images

---

### 🌐 URL Generation

* `url()` – Get full URL
* `temporaryUrl($path, $minutes)` – Short-lived access
* `signedUrl($path, $expiration)` – Secure signed URLs
* Works seamlessly across disks

---

## 🏗 Old Way (Still Supported)

For developers who prefer full manual control, you can still use the original explicit API.

```php
$path = Media::from($request->file('avatar'))
    ->on('public')
    ->to('uploads/avatars')
    ->generateName('uuid')
    ->store();
```

Supports all source types:

```php
Media::fromBase64($data)->to('uploads')->store();
Media::fromUrl('https://example.com/image.jpg')->store();
Media::fromPath(storage_path('temp/file.pdf'))->store();
Media::fromContent($rawData)->to('uploads')->store();
```

> ✅ The new `upload()` and `replace()` methods are expressive shortcuts built on top of this same fluent core.

---

## 📊 Metadata Example

```php
$meta = Media::meta('uploads/docs/report.pdf');

$meta->size();         // bytes
$meta->mime();         // mime type
$meta->extension();    // file extension
$meta->basename();     // file name
$meta->dimensions();   // [width, height]
$meta->toArray();      // all metadata
```

---

## 🧠 Advanced Options

```php
Media::on('s3')
    ->visibility('private')
    ->generateName('uuid')
    ->fallbackExtension('png')
    ->safeDelete(true)
    ->upload($file, 'uploads');
```

---

## 📦 Installation

```bash
composer require hasanhawary/media-manager
```

Auto-discovered in Laravel.
Alias: `Media` → `HasanHawary\MediaManager\Facades\Media`

Ensure your disks are set in `config/filesystems.php`.

---

## ✅ Compatibility

* **PHP:** 8.0 → 8.5
* **Laravel:** 8 → 12

---

## 🆕 Changelog

**v1.1.0**

* Added `upload()` shortcut with multi-type input support
* Added `replace()` for safe file updates
* Enhanced chainable configuration (`generateName`, `on`, etc.)
* Kept backward compatibility with `from()->store()`

---

## 📜 License

MIT © [Hasan Hawary](https://github.com/hasanhawary)

---
