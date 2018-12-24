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
 * @author    Michael Dekker <info@trendweb.io>
 * @license   MIT
 */

require_once __DIR__.'/../../vendor/autoload.php';

/**
 * Class Modules_Transip_Form_Settings
 */
class Modules_Transip_Form_Settings extends pm_Form_Simple
{
    const USERNAME = 'transip_username';
    const PRIVATE_KEY = 'transip_private_key';
    const OVERRIDE_TTL = 'transip_override_ttl';
    const NEW_DOMAINS = 'transip_new_domains';

    const PRIVATE_KEY_PLACEHOLDER = '⚫⚫⚫⚫⚫⚫';

    private $isConsole = false;

    /**
     * Modules_Transip_Form_Settings constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (!empty($options['isConsole'])) {
            $this->isConsole = $options['isConsole'];
        }

        parent::__construct($options);
    }

    /**
     * Init
     */
    public function init()
    {
        parent::init();

        $this->addElement('text', static::USERNAME, [
            'label'       => pm_Locale::lmsg('usernameLabel'),
            'value'       => pm_Settings::get(static::USERNAME),
            'class'       => 'f-large-size',
            'required'    => true,
            'placeholder' => 'transipuser',
            'validators'  => [
                ['NotEmpty', true],
            ],
        ]);
        $this->addElement('textarea', static::PRIVATE_KEY, [
            'label'       => pm_Locale::lmsg('privateKeyLabel'),
            'value'       => pm_Settings::get(static::PRIVATE_KEY) ? static::PRIVATE_KEY_PLACEHOLDER : '',
            'required'    => true,
            'placeholder' => '-----PRIVATE KEY-----
MIIEowIBAAKCAQEAvZfFCBE+cRYNotA8V2JjqGvsyrlntUPfKjL1YCMtzdEtCRh0
K20Ramnx5/txZEmD/cXo/xOiCT8vMadAw3t1qRk/hTakwK1Tglb9WEd2NAMgKdkS
1UB4kOD3/lMU8+Wz5fA5WKp2vzpxKaDdNIS1tB1nN+xGJdx040hXxyWkU9ft5Boa
7O7fZG7VT7YnAD5iSBwBhMfi3bTi34TLBrp3Pq2Pc10+hM3YskRYRBaHz7XvSIoY
/JP4hLKsA7B5fd4Koy9EpvZoRTTbYjEgxmxXWbwd0AHABoWXrw6FYu9W4ZOa/cOG
T/EP5gWHmANoWBMrg5noMYjMnjPIqGTVc4K/pQIDAQABAoIBAQCsL6pCKWcMTXsU
4wnqUvEvhNUJSFlnxrxnFuDC7zAqPE8qc4sN5YBrpMyOY04YRqwZTiTNhIck3r19
2uh2oSm66bNGyNnYI5I0TczI4B36HtyXJQ51npfg/HA+CjZ9S6CWtBVg8W/nPKyJ
og9EI0Li0dFseKk8uXtu78TImOclPOXr3s+DgDYpnxqQv+JOqoD7KPPpTD4Xg/YF
PGaicH5UeAe8N3++6uOXFSA/mGihACyZRmCx9KAkiBnMmhyRrnGV03W9Lz52ng1Z
815Eu34kZaEYeHzo3GGk3k8WvhM+rBMSvFTuJHNYDNvzyrwAAFQG0zzhte4IqD25
YFshNaYBAoGBAOPQrqB7+xJQxt8Ne/AGpXfFwgcun+lk9dnr1hvMR2VwrnxTYwa3
ZbslFSkJZtJTLtINzPYjvNDOcDp8YxT3a6m6+U89eidrcCfM29qT0a0hsvYO/43P
GDNhYOkgswPQFeQsNdQT7xZKB3ruwOnv8mI2woLGFmRmWVjobdY8GZPLAoGBANUM
hIwvpWBw4XmhocWAbIfNC1LlBR8Hmhc57y8YffB5zjsW5ZcWK2hqiCIz1hLUtq1c
YKv2wMfPaUN4Nus38qPHbp1BIxW+8dQZdYOd2SN5q9DG9Fc7/4Otv0eF5Siw3+il
uDE51YNCQP3LOge4P0FJXomHB89zNyy3Bt
-----END PRIVATE KEY-----',
            'validators'  => [
                ['NotEmpty', true],
            ],
        ]);
        $this->addElement('text', static::OVERRIDE_TTL, [
            'label'       => pm_Locale::lmsg('overrideTtlLabel'),
            'description' => pm_Locale::lmsg('overrideTtlHint'),
            'value'       => pm_Settings::get(static::OVERRIDE_TTL),
            'required'    => false,
            'validators'  => [],
        ]);
        $this->addElement('checkbox', static::NEW_DOMAINS, [
            'label'      => pm_Locale::lmsg('syncNewDomainsLabel'),
            'value'      => pm_Settings::get(static::NEW_DOMAINS),
            'required'   => false,
            'validators' => [],
        ]);
        $this->addControlButtons([
            'cancelLink' => pm_Context::getModulesListUrl(),
        ]);
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function isValid($data)
    {
        if (!parent::isValid($data)) {
            $this->markAsError();
            $this->getElement(static::USERNAME)->addError(pm_Locale::lmsg('usernamePrivateKeyInvalidError'));
            $this->getElement(static::PRIVATE_KEY)->addError(pm_Locale::lmsg('usernamePrivateKeyInvalidError'));

            return false;
        }

        return true;
    }

    /**
     * @return array
     *
     * @throws Zend_Db_Table_Exception
     * @throws Zend_Db_Table_Row_Exception
     * @throws pm_Exception_InvalidArgumentException
     */
    public function process()
    {
        $res = [];

        $username = $this->getValue(static::USERNAME);
        $privateKey = $this->getValue(static::PRIVATE_KEY);
        pm_Settings::set(static::OVERRIDE_TTL, $this->getValue(static::OVERRIDE_TTL));
        pm_Settings::set(static::NEW_DOMAINS, $this->getValue(static::NEW_DOMAINS));

        $this->saveUserData($username, $privateKey);

        return $res;
    }

    /**
     * @param string $username
     * @param string $privateKey
     *
     * @throws Zend_Db_Table_Exception
     * @throws Zend_Db_Table_Row_Exception
     * @throws pm_Exception_InvalidArgumentException
     */
    private function saveUserData($username, $privateKey)
    {
        pm_Settings::set(static::USERNAME, $username);
        if ($privateKey !== static::PRIVATE_KEY_PLACEHOLDER) {
            pm_Settings::set(static::PRIVATE_KEY, $privateKey);
        }

    }

    /**
     * Get override TTL
     *
     * string $id Site ID
     *
     * @param string $id Site ID
     *
     * @return int TTL
     *
     * @throws pm_Exception
     */
    public static function getTtl($id)
    {
        static $saved = [];
        if (isset($saved[$id])) {
            return $saved[$id];
        }

        $savedTtl = pm_Settings::get(static::OVERRIDE_TTL);
        if (!$savedTtl) {
            $request = <<<APICALL
<packet>
<dns>
 <get>
  <filter>
   <site-id>{$id}</site-id>
  </filter>
  <soa/>
 </get>
</dns>
</packet>
APICALL;
            $response = pm_ApiRpc::getService(Modules_Transip_Client::SERVICE_VERSION)->call($request);
            $savedTtl = isset($response->dns->get->result->soa->ttl) ? (int) $response->dns->get->result->soa->ttl : 300;
        }

        $saved[$id] = $savedTtl;

        return $savedTtl;
    }
}
