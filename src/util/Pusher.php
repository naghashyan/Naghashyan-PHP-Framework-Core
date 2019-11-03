<?php

namespace ngs\util;


class Pusher {

  /**
   * Image
   * @const str
   */
  public const IMG = 'img';

  /**
   * Scr
   * @const str
   */
  public const SRC = 'src';

  /**
   * Link
   * @const str
   */
  public const LINK = 'link';
  /**
   * instance
   * @var Pusher
   */
  private static $instance;

  /**
   * pusher links
   * @var array
   */
  protected $links = [];

  /**
   * as types
   * @var array
   */
  protected $as =
    [
      Pusher::SRC => 'script',
      Pusher::IMG => 'image',
      Pusher::LINK => 'style'
    ];

  /**
   * get instance
   * @return PusherInterface
   */
  public static function getInstance(): Pusher {
    if (static::$instance === null){
      static::$instance = new static;
    }

    return static::$instance;
  }

  /**
   * Set Style
   * @param string $link
   * @param array $opts
   */
  public function link(string $link, array $opts = ['crossorigin' => 'anonymous']): Pusher {
    return $this->set(static::LINK, $link, $opts);
  }

  /**
   * set Script
   * @param string $src
   * @param array $opts
   */
  public function src(string $src, array $opts = ['crossorigin' => 'anonymous']): Pusher {
    return $this->set(static::SRC, $src, $opts);
  }

  /**
   * Set Image
   * @param string $img
   * @param array $opts
   */
  public function img(string $img, array $opts = ['crossorigin' => 'anonymous']): Pusher {
    return $this->set(static::IMG, $img, $opts);
  }

  /**
   * Set Link
   * @param string $type
   * @param string $link
   * @param array $opts
   */
  public function set(string $type, string $link, array $opts = []): Pusher {
    $this->links[$type][$link] = $opts;
    return $this;
  }

  /**
   * get links
   * @param string $type
   * @return string
   */
  public function getHeader(string $type = null): ?string {
    $line = [];

    if ($type === null && (bool)$this->links){

      foreach ($this->links as $type => $urls){
        $line[] = $this->toHeader($type, $urls);
      }

    } elseif (isset($this->links[$type])){

      $line[] = $this->toHeader($type, $this->links[$type]);

    } else{
      return null;
    }

    return implode($line, ', ');
  }

  /**
   * Push Headers
   * @param string $type
   * @return void
   */
  public function push(string $type = null): void {
    if (headers_sent($f, $l)){
      throw new \Exception("headers already sent at file: {$f}, line: {$l}");
    }
    if ($headers = $this->getHeader()){
      header("Link: " . $headers);
    }
    return;
  }

  /**
   * urls to header string
   * @param string $type
   * @param array $urls
   * @return string|null
   */
  public function toHeader(string $type, array $urls): ?string {
    if ((bool)$urls === false)
      return null;

    $line = [];
    $opts = [
      'rel' => 'preload',
      'as' => $this->as[$type] ?? false
    ];

    foreach ($urls as $url => $ops){
      $ops = array_merge($opts, $ops);
      $ops = $this->arrayOptionsToStr($ops);
      $line[] = "<{$url}>; {$ops}";
    }

    return implode(', ', $line);
  }

  /**
   * convert options to string
   * @param array $options
   * @return string
   */
  protected function arrayOptionsToStr(array $options): string {
    $opts = [];

    foreach ($options as $k => $v){
      if ($v === false)
        continue;

      $opts[] = "{$k}={$v}";
    }

    return implode('; ', $opts);
  }

  protected function __construct() {
  }

  protected function __clone() {
  }

  protected function __wakeup() {
  }
}
