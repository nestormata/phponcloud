<?php

namespace PHPOnCloud\App\Extensions;

use Exception;
use PHPOnCloud\App\Application;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigCustomExtension extends AbstractExtension
{
    private Application $app;
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register functions in the extension.
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('versioned_asset', [$this, 'getVersionedAsset']),
        ];
    }

    /**
     * Twig function to fetch the URL of the versioned version of an asset.
     * @param string $asset The asset file name.
     * @return string The public URL of the versioned file asset.
     */
    public function getVersionedAsset(string $asset): ?string
    {
        $manifest = $this->getManifest();
        if (!array_key_exists($asset, $manifest)) {
            throw new Exception(
                "Unknown Mix file path: {$asset}. Please check your requested " .
                "webpack.mix.js output path, and try again."
            );
        }
        return $manifest[$asset];
    }

    /**
     * Fetches the manifest with the assets versions information.
     * @return array The dictionary with the files.
     */
    private function getManifest(): array
    {
        static $manifest;
        if (!$manifest) {
            $manifestPath = $this->app->getPublicPath('assets/manifest.json');

            if (!file_exists($manifestPath)) {
                throw new Exception(
                    'The Mix manifest file does not exist. ' .
                    'Please run "npm run webpack" and try again.'
                );
            }
            $manifest = json_decode(file_get_contents($manifestPath), true);
        }
        return $manifest;
    }
}
