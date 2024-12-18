<?php

namespace Innoweb\PdfToImageConverter\Conversion;

use Exception;
use Imagick;
use SilverStripe\Assets\Conversion\FileConverter;
use SilverStripe\Assets\Conversion\FileConverterException;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image_Backend;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Path;

class PdfToImageConverter implements FileConverter
{
    public function supportsConversion(string $fromExtension, string $toExtension, array $options = []): bool
    {
        $supported = true;
        if (strtolower($fromExtension) !== 'pdf') {
            $supported = false;
        }
        if (!in_array(strtolower($toExtension), ['jpg', 'png', 'gif', 'webp'])) {
            $supported = false;
        }
        if (!class_exists('Imagick') || !extension_loaded('imagick')) {
            $supported = false;
        }
        if (!in_array(strtoupper($toExtension), Imagick::queryFormats())) {
            $supported = false;
        }
        return $supported;
    }

    public function convert(DBFile|File $from, string $toExtension, array $options = []): DBFile
    {
        $fromExtension = $from->getExtension();
        if (!$this->supportsConversion($fromExtension, $toExtension, $options)) {
            throw new FileConverterException(
                "Conversion from '$fromExtension' to '$toExtension' is not supported."
            );
        }

        // Handle conversion to image
        $result = $from->manipulateExtension(
            $toExtension,
            function (AssetStore $store, string $filename, string $hash, string $variant) use ($toExtension, $from) {
                $im = $temp = $tempPath = null;
                try {
                    $tempPath = TEMP_PATH . DIRECTORY_SEPARATOR . md5(microtime()) . '.' . strtolower($toExtension);
                    $temp = fopen($tempPath, 'w+b');
                    $orig = Path::normalise(ASSETS_PATH . DIRECTORY_SEPARATOR . $from->getFilename());

                    $im = new Imagick($orig . "[0]"); // 0-first page, 1-second page
                    $im->setImageBackgroundColor('white');
                    $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE); // remove transparency
                    $im->transformImageColorspace(Imagick::COLORSPACE_SRGB);
                    $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
                    $im->setImageFormat(strtolower($toExtension));
                    $im->writeImage($tempPath);

                    $backend = Injector::inst()->create(Image_Backend::class);
                    $backend->loadFrom($tempPath);

                    $config = ['conflict' => AssetStore::CONFLICT_USE_EXISTING];
                    $tuple = $backend->writeToStore($store, $filename, $hash, $variant, $config);
                    return [$tuple, $backend];
                } catch (Exception $e) {
                    throw new FileConverterException('Failed to convert: ' . $e->getMessage() . ' (' . $from->getFilename() . ' to ' . $toExtension . ')', $e->getCode(), $e);
                } finally {
                    if ($im) {
                        $im->clear();
                        $im->destroy();
                    }
                    if ($temp) {
                        fclose($temp);
                    }
                    if ($tempPath && file_exists($tempPath)) {
                        unlink($tempPath);
                    }
                }
            }
        );

        if ($result === null) {
            throw new FileConverterException('File conversion resulted in null. Check whether original file actually exists: ' . $from->getFilename() . ' to ' . $toExtension);
        }
        return $result;
    }
}
