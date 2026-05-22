<?php
/**
 * Upload Class - Vylepšená verze
 * Agresivní komprese obrázků: 10MB → 200-500KB
 */

class Upload {
    private $uploadDir;
    private $allowedTypes;
    private $allowedMediaTypes;
    private $maxSize;
    
    public function __construct() {
        $this->uploadDir = UPLOADS_PATH;
        $this->allowedTypes = ALLOWED_IMAGE_TYPES;
        $this->allowedMediaTypes = [
            'jpg', 'jpeg', 'png', 'gif', 'webp',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
            'mp4', 'webm', 'mov',
            'mp3', 'wav', 'ogg', 'm4a'
        ];
        $this->maxSize = 50 * 1024 * 1024; // Zvýšeno na 50MB pro velké obrázky
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function uploadMedia($file, $optimizeImages = true, $saveToMedia = true) {
        $validation = $this->validateMedia($file);
        if (!$validation['success']) {
            return $validation;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $this->allowedTypes, true)) {
            $result = $this->uploadImage($file, $optimizeImages, $saveToMedia);
            if ($result['success']) {
                $result['mime_type'] = $validation['mime_type'] ?? 'image/jpeg';
                $result['original_name'] = $file['name'];
                $result['media_kind'] = 'image';
            }
            return $result;
        }

        $filename = $this->generateFilename($ext);
        $target = $this->targetForExtension($ext);
        $filepath = $target['dir'] . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => false,
                'message' => 'Nepodařilo se nahrát soubor'
            ];
        }

        $filesize = filesize($filepath);
        $path = $target['path'] . $filename;
        $mimeType = $validation['mime_type'] ?? ($file['type'] ?? 'application/octet-stream');

        if ($saveToMedia) {
            $media = new Media();
            $media->add([
                'filename' => $filename,
                'original_name' => $file['name'],
                'path' => $path,
                'mime_type' => $mimeType,
                'size' => $filesize,
                'width' => null,
                'height' => null
            ]);
        }

        return [
            'success' => true,
            'path' => $path,
            'filename' => $filename,
            'size' => $filesize,
            'mime_type' => $mimeType,
            'original_name' => $file['name'],
            'media_kind' => $target['kind']
        ];
    }
    
    /**
     * Upload obrázku s agresivní kompresí
     */
    public function uploadImage($file, $optimize = true, $saveToMedia = false) {
        // Validace
        $validation = $this->validateImage($file);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Generovat unikátní jméno
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $this->generateFilename($ext);
        $target = $this->targetForExtension($ext);
        $filepath = $target['dir'] . $filename;
        
        // Upload
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => false,
                'message' => 'Nepodařilo se nahrát soubor'
            ];
        }
        
        // Získat rozměry před optimalizací
        $imageInfo = @getimagesize($filepath);
        $originalWidth = $imageInfo ? $imageInfo[0] : null;
        $originalHeight = $imageInfo ? $imageInfo[1] : null;
        
        // Agresivní optimalizace
        if ($optimize) {
            $optimizedPath = $this->aggressiveOptimize($filepath);
            if (!$optimizedPath) {
                // Pokud optimalizace selhala, smazat soubor
                @unlink($filepath);
                return [
                    'success' => false,
                    'message' => 'Nepodařilo se optimalizovat obrázek'
                ];
            }
            $filepath = $optimizedPath;
            $filename = basename($filepath);
        }
        
        $filesize = filesize($filepath);
        $path = $target['path'] . $filename;
        
        // Uložit do media tabulky pokud je požadováno
        if ($saveToMedia) {
            // Získat aktuální rozměry po optimalizaci
            $imageInfo = @getimagesize($filepath);
            $width = $imageInfo ? $imageInfo[0] : $originalWidth;
            $height = $imageInfo ? $imageInfo[1] : $originalHeight;
            
            $media = new Media();
            $media->add([
                'filename' => $filename,
                'original_name' => $file['name'],
                'path' => $path,
                'mime_type' => $imageInfo ? $imageInfo['mime'] : 'image/jpeg',
                'size' => $filesize,
                'width' => $width,
                'height' => $height
            ]);
        }
        
        return [
            'success' => true,
            'path' => $path,
            'filename' => $filename,
            'size' => $filesize
        ];
    }
    
    /**
     * Validace obrázku
     */
    private function validateImage($file) {
        // Kontrola chyb
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'Soubor je příliš velký (server limit)',
                UPLOAD_ERR_FORM_SIZE => 'Soubor je příliš velký (form limit)',
                UPLOAD_ERR_PARTIAL => 'Soubor byl nahrán pouze částečně',
                UPLOAD_ERR_NO_FILE => 'Nebyl vybrán žádný soubor',
                UPLOAD_ERR_NO_TMP_DIR => 'Chybí dočasná složka',
                UPLOAD_ERR_CANT_WRITE => 'Nepodařilo se zapsat na disk',
                UPLOAD_ERR_EXTENSION => 'Upload byl zastaven rozšířením'
            ];
            
            return [
                'success' => false,
                'message' => $errors[$file['error']] ?? 'Neznámá chyba uploadu'
            ];
        }
        
        // Kontrola velikosti (50MB limit)
        if ($file['size'] > $this->maxSize) {
            return [
                'success' => false,
                'message' => 'Soubor je příliš velký (max 50MB)'
            ];
        }
        
        // Kontrola přípony
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Nepovolený typ souboru. Povolené: ' . implode(', ', $this->allowedTypes)
            ];
        }
        
        // Kontrola MIME type
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        } else {
            // Fallback
            $mimeType = $file['type'];
        }
        
        $allowedMimes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ];
        
        if (!in_array($mimeType, $allowedMimes)) {
            return [
                'success' => false,
                'message' => 'Neplatný typ obrázku'
            ];
        }
        
        return ['success' => true];
    }

    private function validateMedia($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'Soubor je příliš velký (server limit)',
                UPLOAD_ERR_FORM_SIZE => 'Soubor je příliš velký (form limit)',
                UPLOAD_ERR_PARTIAL => 'Soubor byl nahrán pouze částečně',
                UPLOAD_ERR_NO_FILE => 'Nebyl vybrán žádný soubor',
                UPLOAD_ERR_NO_TMP_DIR => 'Chybí dočasná složka',
                UPLOAD_ERR_CANT_WRITE => 'Nepodařilo se zapsat na disk',
                UPLOAD_ERR_EXTENSION => 'Upload byl zastaven rozšířením'
            ];
            return ['success' => false, 'message' => $errors[$file['error']] ?? 'Neznámá chyba uploadu'];
        }

        if ($file['size'] > $this->maxSize) {
            return ['success' => false, 'message' => 'Soubor je příliš velký (max 50 MB)'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedMediaTypes, true)) {
            return [
                'success' => false,
                'message' => 'Nepovolený typ souboru. Povolené formáty: ' . implode(', ', $this->allowedMediaTypes)
            ];
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        } else {
            $mimeType = $file['type'] ?: 'application/octet-stream';
        }

        $allowedMimes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword', 'application/octet-stream'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
            'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
            'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
            'txt' => ['text/plain'],
            'csv' => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
            'mp4' => ['video/mp4', 'application/octet-stream'],
            'webm' => ['video/webm'],
            'mov' => ['video/quicktime', 'application/octet-stream'],
            'mp3' => ['audio/mpeg', 'audio/mp3', 'application/octet-stream'],
            'wav' => ['audio/wav', 'audio/x-wav', 'audio/wave'],
            'ogg' => ['audio/ogg', 'video/ogg', 'application/ogg'],
            'm4a' => ['audio/mp4', 'audio/x-m4a', 'application/octet-stream']
        ];

        if (!in_array($mimeType, $allowedMimes[$ext] ?? [], true)) {
            return ['success' => false, 'message' => 'Neplatný typ souboru pro příponu .' . $ext];
        }

        return ['success' => true, 'mime_type' => $mimeType];
    }
    
    /**
     * Agresivní optimalizace obrázku
     * Cíl: 10MB → 200-500KB
     */
    private function aggressiveOptimize($filepath) {
        // Načíst info o obrázku
        $imageInfo = @getimagesize($filepath);
        if (!$imageInfo) {
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Načíst obrázek podle typu
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = @imagecreatefromjpeg($filepath);
                break;
            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($filepath);
                break;
            case IMAGETYPE_GIF:
                $image = @imagecreatefromgif($filepath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $image = @imagecreatefromwebp($filepath);
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Vypočítat nové rozměry
        $maxWidth = 1920;
        $maxHeight = 1920;
        
        $newWidth = $width;
        $newHeight = $height;
        
        // Zmenšit pokud je větší
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);
        }
        
        // Vytvořit nový obrázek
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Pro PNG zachovat průhlednost
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Pro GIF zachovat průhlednost
        if ($type == IMAGETYPE_GIF) {
            $transparentIndex = imagecolortransparent($image);
            if ($transparentIndex >= 0) {
                $transparentColor = imagecolorsforindex($image, $transparentIndex);
                $transparentNew = imagecolorallocate($resized, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
                imagefill($resized, 0, 0, $transparentNew);
                imagecolortransparent($resized, $transparentNew);
            }
        }
        
        // Resamplovat (vysoká kvalita)
        imagecopyresampled(
            $resized, $image,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        );
        
        // Uložit s kompresí
        $saved = false;
        
        // Vždy ukládat jako JPEG pro maximální kompresi (kromě PNG s průhledností)
        if ($type == IMAGETYPE_PNG && $this->hasTransparency($image)) {
            // PNG s průhledností - zachovat jako PNG ale komprimovat
            imagepng($resized, $filepath, 6); // Komprese 6/9
            $saved = true;
        } else {
            // Vše ostatní → JPEG s progresivním kódováním
            imageinterlace($resized, 1); // Progresivní JPEG
            
            // Adaptivní kvalita podle velikosti
            $fileSize = filesize($filepath);
            $quality = 85;
            
            if ($fileSize > 5 * 1024 * 1024) { // Pokud > 5MB
                $quality = 70;
            } elseif ($fileSize > 3 * 1024 * 1024) { // Pokud > 3MB
                $quality = 75;
            } elseif ($fileSize > 1 * 1024 * 1024) { // Pokud > 1MB
                $quality = 80;
            }
            
            // Změnit příponu na .jpg pokud není
            $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            $originalPath = null;
            if ($ext !== 'jpg' && $ext !== 'jpeg') {
                $originalPath = $filepath;
                $newPath = preg_replace('/\.[^.]+$/', '.jpg', $filepath);
                $filepath = $newPath;
            }
            
            imagejpeg($resized, $filepath, $quality);
            if ($originalPath && $originalPath !== $filepath) {
                @unlink($originalPath);
            }
            $saved = true;
        }
        
        // Uvolnit paměť
        imagedestroy($image);
        imagedestroy($resized);
        
        // Pokud je soubor stále > 1MB, zkusit ještě více komprimovat
        if ($saved && filesize($filepath) > 1024 * 1024) {
            $this->secondPassCompression($filepath);
        }
        
        return $saved ? $filepath : false;
    }
    
    /**
     * Druhý průchod komprese pro velké soubory
     */
    private function secondPassCompression($filepath) {
        $imageInfo = @getimagesize($filepath);
        if (!$imageInfo) {
            return;
        }
        
        $image = @imagecreatefromjpeg($filepath);
        if (!$image) {
            return;
        }
        
        // Ještě nižší kvalita pro velké soubory
        imageinterlace($image, 1);
        imagejpeg($image, $filepath, 65); // Nižší kvalita
        
        imagedestroy($image);
    }
    
    /**
     * Kontrola průhlednosti v PNG
     */
    private function hasTransparency($image) {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Zkontrolovat vzorky pixelů
        $samples = 10;
        $stepX = max(1, floor($width / $samples));
        $stepY = max(1, floor($height / $samples));
        
        for ($x = 0; $x < $width; $x += $stepX) {
            for ($y = 0; $y < $height; $y += $stepY) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                if ($alpha > 0) {
                    return true; // Má průhlednost
                }
            }
        }
        
        return false;
    }
    
    /**
     * Generovat unikátní název souboru
     */
    private function generateFilename($ext) {
        return uniqid() . '_' . time() . '.' . $ext;
    }

    private function targetForExtension($ext) {
        $kind = $this->kindForExtension($ext);
        $year = date('Y');
        $relativeDir = 'uploads/' . $kind . '/' . $year . '/';
        $absoluteDir = ROOT_PATH . $relativeDir;

        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }

        return [
            'kind' => $kind,
            'dir' => $absoluteDir,
            'path' => $relativeDir
        ];
    }

    private function kindForExtension($ext) {
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'images';
        }
        if (in_array($ext, ['mp4', 'webm', 'mov'], true)) {
            return 'videos';
        }
        if (in_array($ext, ['mp3', 'wav', 'ogg', 'm4a'], true)) {
            return 'audio';
        }
        return 'documents';
    }
    
    /**
     * Smazat obrázek
     */
    public function deleteImage($path) {
        $filepath = ROOT_PATH . $path;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
}
