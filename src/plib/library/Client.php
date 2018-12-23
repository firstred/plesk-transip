<?php
/**
 * Copyright 2018-2019 Michael Dekker
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge,
 * publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @copyright 2018-2019 Michael Dekker
 * @author Michael Dekker <info@trendweb.io>
 * @license MIT
 */

use TransIP\Model\DnsEntry;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Class Module_Transip_Client
 */
class Modules_Transip_Client
{
    /**
     * @var \TransIP\Client $client
     */
    private $client;

    /**
     * Singleton
     *
     * Module_Transip_Client constructor.
     *
     * @param string $username
     * @param string $privateKey
     */
    protected function __construct($username, $privateKey)
    {
        $this->client = new TransIP\Client($username, $privateKey);
    }

    /**
     * Get an instance of the TransIP Client
     *
     * @param string $username
     * @param string $privateKey
     *
     * @return static|null
     */
    public static function getInstance($username = '', $privateKey = '')
    {
        static $instance = null;
        if ($instance === null || $username) {
            if (!$username) {
                $username = pm_Settings::get(Modules_Transip_Form_Settings::USERNAME);
            }
            if (!$privateKey) {
                $privateKey = pm_Settings::get(Modules_Transip_Form_Settings::PRIVATE_KEY);
            }
            $instance = new static($username, $privateKey);
        }

        return $instance;
    }

    /**
     * Get all domain names for the TransIP account
     *
     * @return string[]
     *
     * @throws SoapFault
     */
    public function getDomainNames()
    {
        static::enableSoapEntityLoader();
        return $this->client->domain()->getDomainNames();
    }

    /**
     * @param $domains
     *
     * @throws SoapFault
     */
    public function syncDomains($domains)
    {
        $transipDomains = $this->getDomainNames();
        $savedDomains = @json_decode(pm_Settings::get(Modules_Transip_List_Domains::DOMAINS), true);
        if (!is_array($savedDomains)) {
            $savedDomains = [];
        }
        $domains = array_intersect($domains, $transipDomains, $savedDomains);
        foreach ($domains as $domain) {
            $pleskDomain = pm_Domain::getByName($domain);
            $records = static::getDomainInfo($pleskDomain->getId(), true);
            static::enableSoapEntityLoader();
            $this->client->domain()->setDnsEntries($domain, $records);
            /** @var DnsEntry $entry */
            $this->setDomainInfo($pleskDomain->getId(), array_map(function ($entry) {
                return [
                    'type'  => $entry->type,
                    'host'  => $entry->name,
                    'value' => $entry->content,
                ];
            }, $records));
        }
    }

    /**
     * Enable Entity Loader for the TransIP library -- some Plesk instances need this
     */
    public static function enableSoapEntityLoader()
    {
        if (function_exists('libxml_disable_entity_loader')) {
            libxml_disable_entity_loader(false);
        }
    }

    /**
     * Get domain info
     *
     * @param string $id Plesk Domain ID
     * @param boolean $refresh When enabled, it will grab the latest info from Plesk
     *                         instead of the cached setting.
     *
     * @return array
     */
    public static function getDomainInfo($id, $refresh = false)
    {
        if (!$refresh) {
            return (array) @json_decode(pm_Settings::get("domain_info_{$id}"), true);
        }

        $pleskDomain = new pm_Domain($id);
        $domain = $pleskDomain->getName();

        $request = <<<APICALL
<packet>
<dns>
 <get_rec>
  <filter>
   <site-id>{$id}</site-id>
  </filter>
 </get_rec>
</dns>
</packet>
APICALL;
        $records = [];
        $response = pm_ApiRpc::getService('1.6.9.1')->call($request);
        if (isset($response->dns->get_rec->result)) {
            foreach (json_decode(json_encode($response->dns->get_rec), true)['result'] as $localRecord) {
                $host = rtrim(str_replace("$domain.", '@', str_replace(".$domain.", '', $localRecord['data']['host'])), '.');
                $type = $localRecord['data']['type'];
                $value = rtrim(str_replace("$domain.", '@', str_replace(".$domain", '', $localRecord['data']['value'])), '.');

                if (($type === 'MX' && $host === '@') || $type === 'NS') {
                    continue;
                }

                $records[] = new DnsEntry($host, 300, $type, $value);
            }
        }

        return $records;
    }

    /**
     * Save domain info, in order to track changes
     *
     * @param string $id   Plesk ID of domain
     * @param array  $info DNS Entries
     */
    private function setDomainInfo($id, $info)
    {
        pm_Settings::set("domain_info_${id}", json_encode($info));
    }
}
