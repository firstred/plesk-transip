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

require_once __DIR__.'/../../vendor/autoload.php';

/**
 * Class Modules_Transip_Form_Settings
 */
class Modules_Transip_Form_Settings extends pm_Form_Simple
{
    const USERNAME = 'transip_username';
    const PRIVATE_KEY = 'transip_private_key';

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
            'label'      => pm_Locale::lmsg('transipUsernameLabel'),
            'value'      => pm_Settings::get(static::USERNAME),
            'class'      => 'f-large-size',
            'required'   => true,
            'validators' => [
                ['NotEmpty', true],
            ],
        ]);
        $this->addElement('textarea', static::PRIVATE_KEY, [
            'label'      => pm_Locale::lmsg('transipPrivateKeyLabel'),
            'value'      => pm_Settings::get(static::PRIVATE_KEY) ? static::PRIVATE_KEY_PLACEHOLDER : '',
            'required'   => true,
            'validators' => [
                ['NotEmpty', true],
            ],
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
     * @return array|void
     * @throws Zend_Db_Table_Exception
     * @throws Zend_Db_Table_Row_Exception
     * @throws pm_Exception_InvalidArgumentException
     */
    public function process()
    {
        $res = [];

        $username = $this->getValue(static::USERNAME);
        $privateKey = $this->getValue(static::PRIVATE_KEY);

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
}
