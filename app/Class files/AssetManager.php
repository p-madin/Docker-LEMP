<?php

class AssetManager {
    protected string $nonce;
    protected array $jsFiles = [];
    protected array $cssFiles = [];
    protected string $basePath;

    public function __construct(string $basePath = __DIR__ . '/../../') {
        $this->nonce = base64_encode(random_bytes(16));
        $this->basePath = rtrim($basePath, '/');
    }

    public function getNonce(): string {
        return $this->nonce;
    }

    public function registerJs(string $path, array $attributes = []): void {
        foreach ($this->jsFiles as $js) {
            if ($js['path'] === $path) return;
        }
        $this->jsFiles[] = [
            'path' => $path,
            'attributes' => $attributes
        ];
    }

    public function registerCss(string $path, array $attributes = []): void {
        foreach ($this->cssFiles as $css) {
            if ($css['path'] === $path) return;
        }
        $this->cssFiles[] = [
            'path' => $path,
            'attributes' => $attributes
        ];
    }

    public function getImageUrl(string $path): string {
        return $this->getVersionedUrl($path);
    }

    public function getVersionedUrl(string $path): string {
        $physicalPath = $this->basePath . '/app/' . ltrim($path, '/');
        $version = '';
        if (file_exists($physicalPath)) {
            $version = '?v=' . filemtime($physicalPath);
        }
        return '/' . ltrim($path, '/') . $version;
    }

    public function applyToDom(xmlDom $dom): void {
        // Inject CSS
        foreach ($this->cssFiles as $css) {
            $attrs = array_merge([
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => $this->getVersionedUrl($css['path'])
            ], $css['attributes']);
            $dom->fabricateChild($dom->head, 'link', $attrs);
        }

        // Inject JS
        foreach ($this->jsFiles as $js) {
            $attrs = array_merge([
                'type' => 'text/javascript',
                'src' => $this->getVersionedUrl($js['path']),
                'nonce' => $this->nonce
            ], $js['attributes']);
            $dom->fabricateChild($dom->head, 'script', $attrs);
        }
    }
}
?>
