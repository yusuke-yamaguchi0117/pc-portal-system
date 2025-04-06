<?php
class ImageProcessor
{
    private $upload_dir;
    private $max_size;
    private $allowed_types;
    private $max_width;
    private $max_height;

    public function __construct()
    {
        $this->upload_dir = dirname(__DIR__) . '/uploads/students/';
        $this->max_size = 2 * 1024 * 1024; // 2MB
        $this->allowed_types = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',  // WebP形式の追加
            'image/heic',  // HEIC形式の追加（iPhone）
            'image/heif'   // HEIF形式の追加（iPhone）
        ];
        $this->max_width = 800;
        $this->max_height = 800;
    }

    /**
     * アップロードディレクトリの準備
     */
    public function prepareUploadDirectory()
    {
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
            chmod($this->upload_dir, 0777);
        }
    }

    /**
     * 古い画像ファイルの削除
     */
    public function cleanupOldImages($days = 30)
    {
        if (!file_exists($this->upload_dir)) {
            return;
        }

        $files = glob($this->upload_dir . 'student_*');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 60 * 60 * 24 * $days) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * EXIF情報から画像の向きを取得
     */
    private function getImageOrientation($file_path)
    {
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($file_path);
            return isset($exif['Orientation']) ? $exif['Orientation'] : 1;
        }
        return 1;
    }

    /**
     * 画像の向きを補正
     */
    private function fixImageOrientation($image, $orientation)
    {
        switch ($orientation) {
            case 2:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 3:
                $image = imagerotate($image, 180, 0);
                break;
            case 4:
                imageflip($image, IMG_FLIP_VERTICAL);
                break;
            case 5:
                $image = imagerotate($image, -90, 0);
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 6:
                $image = imagerotate($image, -90, 0);
                break;
            case 7:
                $image = imagerotate($image, 90, 0);
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 8:
                $image = imagerotate($image, 90, 0);
                break;
        }
        return $image;
    }

    /**
     * HEIC/HEIF形式の画像をJPEGに変換
     */
    private function convertHeicToJpeg($source_path)
    {
        $temp_path = tempnam(sys_get_temp_dir(), 'heic_');
        $jpeg_path = $temp_path . '.jpg';

        // ImageMagickを使用して変換
        exec("convert {$source_path} {$jpeg_path}");

        if (file_exists($jpeg_path)) {
            return $jpeg_path;
        }
        return false;
    }

    /**
     * 画像のリサイズ処理
     */
    private function resizeImage($source_path, $target_path)
    {
        // HEIC/HEIF形式の場合はJPEGに変換
        if (in_array(mime_content_type($source_path), ['image/heic', 'image/heif'])) {
            $converted_path = $this->convertHeicToJpeg($source_path);
            if ($converted_path) {
                $source_path = $converted_path;
            }
        }

        list($width, $height, $type) = getimagesize($source_path);
        $orientation = $this->getImageOrientation($source_path);

        // リサイズが必要ない場合は向き補正のみ
        if ($width <= $this->max_width && $height <= $this->max_height) {
            if ($orientation !== 1) {
                $source = $this->createImageFromFile($source_path, $type);
                $source = $this->fixImageOrientation($source, $orientation);
                $this->saveImage($source, $target_path, $type);
                imagedestroy($source);
                return true;
            }
            return move_uploaded_file($source_path, $target_path);
        }

        // アスペクト比を維持してリサイズ
        $ratio = min($this->max_width / $width, $this->max_height / $height);
        $new_width = $width * $ratio;
        $new_height = $height * $ratio;

        // 新しい画像の作成
        $new_image = imagecreatetruecolor($new_width, $new_height);

        // 元画像の読み込み
        $source = $this->createImageFromFile($source_path, $type);

        // 向き補正
        if ($orientation !== 1) {
            $source = $this->fixImageOrientation($source, $orientation);
        }

        // リサイズ
        imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // 保存
        $this->saveImage($new_image, $target_path, $type);

        // メモリ解放
        imagedestroy($source);
        imagedestroy($new_image);

        return true;
    }

    /**
     * ファイルから画像リソースを作成
     */
    private function createImageFromFile($path, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($path);
                imagealphablending($image, false);
                imagesavealpha($image, true);
                return $image;
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }

    /**
     * 画像を保存
     */
    private function saveImage($image, $path, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, $path, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($image, $path, 9);
                break;
            case IMAGETYPE_GIF:
                imagegif($image, $path);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($image, $path, 85);
                break;
        }
    }

    /**
     * 画像のアップロード処理
     */
    public function uploadImage($file)
    {
        // バリデーション
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('ファイルのアップロードに失敗しました。');
        }

        if (!in_array($file['type'], $this->allowed_types)) {
            throw new Exception('アップロードできる画像は JPG, PNG, GIF, WebP, HEIC のみです。');
        }

        if ($file['size'] > $this->max_size) {
            throw new Exception('画像サイズは2MB以下にしてください。');
        }

        // アップロードディレクトリの準備
        $this->prepareUploadDirectory();

        // ファイル名の生成
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('student_') . '.' . $extension;
        $filepath = $this->upload_dir . $filename;

        // 画像のリサイズと保存
        if (!$this->resizeImage($file['tmp_name'], $filepath)) {
            throw new Exception('画像の保存に失敗しました。');
        }

        // 古い画像のクリーンアップ
        $this->cleanupOldImages();

        return '/portal/uploads/students/' . $filename;
    }
}