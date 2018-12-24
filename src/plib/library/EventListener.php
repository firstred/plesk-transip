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

/**
 * Class Modules_Transip_EventListener
 */
class Modules_Transip_EventListener implements EventListener
{
    public function filterActions()
    {
        return [
            'domain_dns_update',
        ];
    }

    public function handleEvent($objectType, $objectId, $action, $oldValues, $newValues)
    {
        // Push all new/updated entries of this domain
        if (!pm_Settings::get(Modules_Transip_Form_Settings::USERNAME)
            || !pm_Settings::get(Modules_Transip_Form_Settings::PRIVATE_KEY)
        ) {
            return;
        }

        $domain = new pm_Domain($objectId);
        $savedDomains = @json_decode(pm_Settings::get(Modules_Transip_List_Domains::DOMAINS), true);
        if (!is_array($savedDomains)) {
            $savedDomains = [];
        }
        if (!in_array($domain->getName(), $savedDomains)) {
            return;
        }
        Modules_Transip_Client::getInstance()->syncDomains([$domain->getName()]);
    }
}

return new Modules_Transip_EventListener();
