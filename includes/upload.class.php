<?php
/**
 * Upload Class - Vylepšená verze
 * Agresivní komprese obrázků: 10MB → 200-500KB
 */

class Upload {
    private $uploadDir;
    private $allowedTypes;
    private $maxSize;
    
    public function __construct() {
        $this->uploadDir = UPLOADS_PATH;
        $this->allowedTypes = ALLOWED_IMAGE_TYPES;
        $this->maxSize = 50 * 1024 * 1024; // Zvýšeno na 50MB pro velké obrázky
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
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
        $filepath = $this->uploadDir . $filename;
        
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
            $optimized = $this->aggressiveOptimize($filepath);
            if (!$optimized) {
                // Pokud optimalizace selhala, smazat soubor
                @unlink($filepath);
                return [
                    'success' => false,
                    'message' => 'Nepodařilo se optimalizovat obrázek'
                ];
            }
        }
        
        $filesize = filesize($filepath);
        $path = 'uploads/' . $filename;
        
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
            if ($ext !== 'jpg' && $ext !== 'jpeg') {
                $newPath = preg_replace('/\.[^.]+$/', '.jpg', $filepath);
                $filepath = $newPath;
            }
            
            imagejpeg($resized, $filepath, $quality);
            $saved = true;
        }
        
        // Uvolnit paměť
        imagedestroy($image);
        imagedestroy($resized);
        
        // Pokud je soubor stále > 1MB, zkusit ještě více komprimovat
        if ($saved && filesize($filepath) > 1024 * 1024) {
            $this->secondPassCompression($filepath);
        }
        
        return $saved;
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