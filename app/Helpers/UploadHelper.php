<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class UploadHelper
{
  const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
  const VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm'];
  const MAX_IMAGE_SIZE = 5242880;
  const COMPRESSED_IMAGE_SIZE = 2097152;

  public static function handleImageFile($file, $fileName, $basePath, $fileSize)
  {
    if ($fileSize > self::MAX_IMAGE_SIZE) {
      $image = Image::make($file);
      
      $image->resize(1280, null, function ($constraint) {
        $constraint->aspectRatio();
        $constraint->upsize();
      });

      $quality = 90;
      do {
        $tempPath = sys_get_temp_dir() . '/' . $fileName;
        $image->save($tempPath, $quality);
        $quality -= 5;
        
        if ($quality < 20) {
            break;
        }
      } while (filesize($tempPath) > self::COMPRESSED_IMAGE_SIZE);

      $path = $basePath . '/images/' . $fileName;
      Storage::disk('local')->put($path, file_get_contents($tempPath));
      unlink($tempPath);
    } else {
      $path = $basePath . '/images/' . $fileName;
      $file->storeAs($basePath . '/images', $fileName, 'public');
    }
    return $path;
  }
  public static function handleVideoFile($file, $fileName, $basePath)
  {
    $path = $basePath . '/videos/' . $fileName;
    $file->storeAs($basePath . '/videos', $fileName, 'public');
    return $path;
  } 
  
  public static function handleDocumentFile($file, $fileName, $basePath)
  {
    $path = $basePath . '/documents/' . $fileName;
    $file->storeAs($basePath . '/documents', $fileName, 'public');
    return $path;
  }
}
