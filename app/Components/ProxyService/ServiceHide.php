<?php

namespace Sakalys\Components\ProxyService;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class ServiceHide
 *
 * Service provider for http://proxylist.hidemyass.com
 */
class ServiceHide extends BaseService
{
    const SERVICE_ID = 1;

    /**
     * Link that lists all groups and channels
     */
    const URL_LIST = 'http://proxylist.hidemyass.com';

    public function getList()
    {
        $url = self::URL_LIST;
        $pages = 0;

        while ($url) {
            $pages++;
            $page = $this->getPage($url);

            if ($page) {
                $url = null;
                $crawler = new Crawler($page);
                $crawler->filter('#listable tbody tr')->each(function(Crawler $node, $i) {
                    $proxy = array();
                    $tNode = $node->filter('td.timestamp')->first();
                    if ($tNode->count()) {
                        $time = $tNode->attr('rel');
                    }
                    else {
                        $time = time();
                    }
                    $ipNode = $node->filter('td')->eq(1);
                    $style = $ipNode->filter('style');
                    $ip = '';
                    $country = null;
                    $countryCode = null;
                    if ($style->count()) {
                        $styleHtml = $style->html();
                        $s = explode("\n", $styleHtml);
                        array_map('trim', $s);
                        $styles = array();
                        //get what ip parts are false o_0
                        foreach ($s as $item) {
                            if (preg_match('#\.([a-z0-9_-]+)#ui', $item, $m)) {
                                if (strstr($item, 'none'))
                                    $styles[$m[1]] = true;
                            }
                        }
                        $span = $ipNode->filter('span')->first();
                        foreach ($span->getNode(0)->childNodes as $domElement) {
                            $nodeName = $domElement->nodeName;
                            if ($nodeName != '#text') {
                                $nodeClass =$domElement->getAttribute('class');
                                $nodeStyle =$domElement->getAttribute('style');
                            }
                            $nodeText =$domElement->nodeValue;
                            if ($nodeName == '#text' || (!in_array($nodeName, array('style', 'div')) && !isset($styles[$nodeClass]) && !strstr($nodeStyle, 'none'))) {
                                $ip .= trim($nodeText);
                            }

                        }
                    }
                    $port = $this->clean($node->filter('td')->eq(2)->text());
                    $countryCode = $node->filter('td')->eq(3)->attr('rel');
                    $country = $node->filter('td')->eq(3)->text();

                    $validator = \Validator::make(
                        array(
                            'ip' => $ip,
                            'port' => $port,
                        ),
                        array(
                            'ip' => 'required|ip',
                            'port' => 'required|integer',
                        )
                    );
                    if ($validator->fails()) {
                        return false;
                    }


                    $proxy['service_id'] = self::SERVICE_ID;
                    $proxy['ip'] = $ip;
                    $proxy['port'] = $port;
                    $proxy['country'] = trim($country);
                    $proxy['country_code'] = trim($countryCode);
                    $proxy['last_active'] = trim($time);

                    $p = Proxy::where('ip', '=', $proxy['ip'])
                        ->where('port', '=', $proxy['port'])
                        ->first();
                    if (!$p) {
                        $p = new Proxy();
                        $p->setAttributes($proxy, array(
                            'service_id',
                            'ip',
                            'port',
                            'country',
                            'country_code',
                            'last_active',
                        ));
                    } else {
                        $p->last_active = $proxy['last_active'];
                    }
                    try {
                        $p->save();
                    } catch (Exception $e) {

                    }

                    $this->list[] = $proxy;
                });

                $next = $crawler->filter('li.arrow.next a');
                $nexta = $crawler->filter('li.arrow.next.unavailable a');
                if (!$nexta->count() && $next->count() && $pages < 15) {
                    $url = $next->attr('href');
                    if (strpos($url, 'http') === false) {
                        $url = self::URL_LIST . $url;
                    }
                }
                else {
//                    echo $pages;
                }
            }
            else {
                break;
//                throw new Exception('Error opening page: ' . $url);
            }
        }

        return $this->list;
    }

}