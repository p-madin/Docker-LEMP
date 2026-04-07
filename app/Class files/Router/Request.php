<?php
class Request {
    public $server;
    public $get;
    public $post;
    public $cookies;

    public function __construct() {
        $this->server = $_SERVER;
        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookies = $_COOKIE;
    }

    public function getMethod() {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function getPath() {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $position = strpos($uri, '?');
        if ($position !== false) {
            return substr($uri, 0, $position);
        }
        return $uri;
    }
}
?>
