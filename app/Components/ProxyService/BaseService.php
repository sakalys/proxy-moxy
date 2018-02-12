<?php

namespace Sakalys\Components\ProxyService;

use Exception;
use ReflectionClass;

abstract class BaseService
{
    /**
     * Groups list
     *
     * @var array
     */
    public $groups = array();

    /**
     * Cookie file for curl requests
     *
     * @var
     */
    public $cookieFile;

    /**
     * Programme list
     *
     * @var array
     */
    public $list = array();

    public $lastNode = array();
    public $lastDate;


    /**
     * Statically cached page contents
     *
     * @var array
     */
    public static $pageHtml = array();


    public function __construct($cookiePath)
    {
        $refl = new ReflectionClass(get_class($this));
        $cookiePath = realpath(rtrim($cookiePath, '/') . '/' . $refl->getShortName());
        if (!is_dir($cookiePath)) {
            try {
                mkdir($cookiePath);
            } catch (Exception $e) {
                dd ($e->getMessage());
            }
        }

        $this->cookieFile = $cookiePath . '/' . 'cookie.txt';
    }


    /**
     * Grabs the content of a url
     *
     * @param string $url
     * @param array $post post vars
     * @param array $headers an array of headers to be passed
     * @param null $cookieFile ?
     * @return mixed
     */
    protected function getPage($url, $post = array(), $headers = array(), $cookieFile = null)
    {
        if (1 || !isset(self::$pageHtml[$url])) {
            $cookieFile = $cookieFile ? $cookieFile : $this->cookieFile;
            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
//            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0");
            curl_setopt($ch, CURLOPT_TIMEOUT, 90);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($ch, CURLOPT_URL, $url);
//            curl_setopt($ch, CURLOPT_REFERER, $url);

            //timeouts
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 25);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);

            if ($post) {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            }
            if ($headers) {
                curl_setopt_array($ch, $headers);
            }

            ob_start();
            $response       = curl_exec($ch);
            //$responseInfo   = curl_getinfo($ch);

            ob_end_clean();
            curl_close($ch);
            unset($ch);

            self::$pageHtml[$url] = $response;
        }

        return self::$pageHtml[$url];
    }

    /**
     * Leave asc chars only
     *
     * @param $str
     * @return string
     */
    public function clean($str)
    {
        return trim(preg_replace('/[^(\x20-\x7F)]*/','', $str));
    }

    public function console($msg)
    {
        syslog(5, $msg);;
    }

}