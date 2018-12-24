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
    const SERVICE_VERSION = '1.6.9.1';

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
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Profiler_Exception
     * @throws Zend_Db_Statement_Exception
     * @throws pm_Exception
     */
    public function syncDomains($domains)
    {
        $transipDomains = $this->getDomainNames();
        $domains = array_intersect($domains, $transipDomains);
        foreach ($domains as $domain) {
            // Merge TransIP and local records
            $pleskDomain = pm_Domain::getByName($domain);
            $pleskRecords = static::getPleskDnsEntries($domain);
            $newRecords = static::getTransipDnsEntries($domain);
            foreach ($pleskRecords as $id => $pleskRecord) {
                $newRecords[$id] = $pleskRecord;
            }

            // Detect removed domains and remove them from TransIP as well
            $pleskRecordIds = array_keys($pleskRecords);
            $removedRecords = [];
            foreach (array_keys(static::getSavedDnsEntries($pleskDomain->getName())) as $savedId) {
                if (!in_array($savedId, $pleskRecordIds)) {
                    $removedRecords[] = $savedId;
                    unset($newRecords[$savedId]);
                }
            }

            static::enableSoapEntityLoader();
            $this->client->domain()->setDnsEntries($domain, array_values($newRecords));
            /** @var DnsEntry $entry */
            $this->setDomainInfo($pleskDomain->getName(), array_map(function ($entry) {
                return [
                    'type'  => $entry->type,
                    'host'  => $entry->name,
                    'value' => $entry->content,
                ];
            }, $pleskRecords));
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
     * Return saved DNS entries
     *
     * @param string $id ID = Domain name
     *
     * @return DnsEntry[]
     * @throws pm_Exception
     */
    public static function getSavedDnsEntries($id)
    {
        return static::getDnsEntries($id, false);
    }

    /**
     * Return Plesk DNS entries
     *
     * @param string $id ID = Domain name
     *
     * @return DnsEntry[]
     * @throws pm_Exception
     */
    public static function getPleskDnsEntries($id)
    {
        return static::getDnsEntries($id, true);
    }

    /**
     * @param string $id ID = Domain name
     *
     * @return DnsEntry[]
     *
     * @throws SoapFault
     */
    public static function getTransipDnsEntries($id)
    {
        return static::getInstance()->transipDnsEntries($id);
    }

    /**
     * @param string $domainName ID = Domain name
     *
     * @return DnsEntry[]
     *
     * @throws SoapFault
     */
    public function transipDnsEntries($domainName)
    {
        $records = [];
        foreach ($this->client->domain()->getInfo($domainName)->dnsEntries as $dnsEntry) {
            $records["{$dnsEntry->name}||{$dnsEntry->type}||{$dnsEntry->content}"] = $dnsEntry;
        }

        return $records;
    }

    /**
     * Get domain info
     *
     * @param string  $domainName Plesk ID = Domain name
     * @param boolean $refresh    When enabled, it will grab the latest info from Plesk
     *                            instead of the cached info.
     *
     * @return DnsEntry[]
     * @throws pm_Exception
     */
    private static function getDnsEntries($domainName, $refresh = false)
    {
        $pleskDomain = pm_Domain::getByName($domainName);
        if (!$refresh) {
            try {
                $db = pm_Bootstrap::getDbAdapter();
                $localRecords = $db->fetchRow($db->select()->from('transip_domains')->where('domain = ?', $domainName));
                if (!$localRecords) {
                    $localRecords = ['dns' => ''];
                }
                $localRecords = isset($localRecords['dns']) ? $localRecords['dns'] : '';
                $localRecords = (array) @json_decode($localRecords, true);
            } catch (Exception $e) {
                $localRecords = [];
            }
            $records = [];
            foreach ($localRecords as $localRecord) {
                // Skip invalid entries
                if (!isset($localRecord['host']) || !isset($localRecord['type']) || !isset($localRecord['value'])) {
                    continue;
                }

                $records["{$localRecord['host']}||{$localRecord['type']}||{$localRecord['value']}"]
                    = new DnsEntry($localRecord['host'], Modules_Transip_Form_Settings::getTtl($pleskDomain->getId()), $localRecord['type'], $localRecord['value']);
            }

            return $records;
        }

        $domain = $pleskDomain->getName();
        $request = <<<APICALL
<packet>
<dns>
 <get_rec>
  <filter>
   <site-id>{$pleskDomain->getId()}</site-id>
  </filter>
 </get_rec>
</dns>
</packet>
APICALL;
        $records = [];
        $response = pm_ApiRpc::getService(static::SERVICE_VERSION)->call($request);
        if (isset($response->dns->get_rec->result)) {
            foreach (json_decode(json_encode($response->dns->get_rec), true)['result'] as $localRecord) {
                $host = rtrim(str_replace("$domain.", '@', str_replace(".$domain.", '', $localRecord['data']['host'])), '.');
                $type = $localRecord['data']['type'];
                $value = rtrim(str_replace("$domain.", '@', str_replace(".$domain", '', $localRecord['data']['value'])), '.');

                if (($type === 'MX' && $host === '@') || $type === 'NS') {
                    continue;
                }

                $records["{$host}||{$type}||{$value}"] = new DnsEntry($host, Modules_Transip_Form_Settings::getTtl($pleskDomain->getId()), $type, $value);
            }
        }

        return $records;
    }

    /**
     * Save domain info, in order to track changes
     *
     * @param string $domainName Plesk ID of domain
     * @param array  $info       DNS Entries
     *
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    private function setDomainInfo($domainName, $info)
    {
        $db = pm_Bootstrap::getDbAdapter();
        $db->delete('transip_domains', "`domain` = {$db->quote($domainName)}");
        $db->insert('transip_domains', [
            'domain' => $domainName,
            'dns'    => json_encode($info),
        ]);
    }
}
