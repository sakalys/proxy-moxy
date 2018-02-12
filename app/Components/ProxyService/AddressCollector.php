<?php

namespace Sakalys\Components\ProxyService;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AddressCollector
 *
 * Service provider for http://www.gatherproxy.com/proxylist/country/
 */
class AddressCollector extends BaseService
{
    const SERVICE_ID = 2;

    /**
     * Link that lists all groups and channels
     */
    const URL_LIST = 'http://www.gatherproxy.com/proxylist/country/?c=';

    public $countries = array(
        'ru' => 'Russian%20Federation',
        'ua' => 'Ukraine',
        'nl' => 'Netherlands',
        'sg' => 'Singapore',
        'fr' => 'France',
        'tr' => 'Turkey',
        'cn' => 'China',
        'in' => 'India',
        'id' => 'Indonesia',
    );

    public function getList()
    {
        foreach ($this->countries as $countryCode => $country) {
            $url = self::URL_LIST . $country;
            $pages = 0;
            $pg = 1;

            while ($url) {
                $pages++;
                $page = $this->getPage($url, array(
                    'Country' => urldecode($country),
                    'PageIdx' => $pg,
                    'Filter' => '',
                    'Uptime' => '',
                ));

                if ($page) {
                    $url = null;
                    $crawler = new Crawler($page);
                    $crawler->filter('#tblproxy tr')->each(function(Crawler $node, $i) use ($country, $countryCode) {
                        $proxy = array();
                        $tNode = $node->filter('td')->eq(0);
                        if (!$tNode->count())
                            return false;
                        $t = $tNode->text();
                        if (preg_match('#([0-9]+)m\s([0-9]+)s#ui', $t, $m)) {
                            $time = time() - ($m[1] * 60) - $m[2];
                        }
                        else {
                            return false;
                        }
                        $ipNode = $node->filter('td')->eq(1);
                        $script = $ipNode->filter('script');
                        if ($script->count()) {
                            if (preg_match('#([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)#ui', $script->text(), $m)) {
                                $ip = $m[1];
                            }
                            else {
                                return false;
                            }
                        }
                        else {
                            return false;
                        }
                        $portNode = $node->filter('td')->eq(2);
                        $script = $portNode->filter('script');
                        if ($script->count()) {
                            if (preg_match('#([0-9]+)#ui', $script->text(), $m)) {
                                $port = (int) $m[1];
                            }
                            else {
                                return false;
                            }
                        }
                        else {
                            return false;
                        }

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
                        $proxy['country'] = urldecode($country);
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

                    $last = $crawler->filter('.pagenavi a')->last();
                    $lastPg = $last->count() ? $last->text() : 0;
                    if ($lastPg > $pg) {
                        $url = self::URL_LIST . $country;
                        $pg++;
                    }
                }
                else {
                    break;
//                throw new Exception('Error opening page: ' . $url);
                }
            }
        }


        return $this->list;
    }

}