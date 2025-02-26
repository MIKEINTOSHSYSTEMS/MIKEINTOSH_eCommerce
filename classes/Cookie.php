<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class CookieCore
 *
 * @since 1.0.0
 *
 * Known properties set up by core:
 *
 * @property int $id_customer
 * @property int $id_guest
 * @property bool $no_mobile
 */
class CookieCore
{
    // @codingStandardsIgnoreStart
    /** @var array Contain cookie content in a key => value format */
    protected $_content;
    /** @var array Crypted cookie name for setcookie() */
    protected $_name;
    /** @var array expiration date for setcookie() */
    protected $_expire;
    /** @var array Website domain for setcookie() */
    protected $_domain;
    /** @var array Path for setcookie() */
    protected $_path;
    /** @var bool $_modified */
    protected $_modified = false;
    protected $_allow_writing;
    protected $_salt;
    protected $_standalone;
    protected $_secure = false;
    // @codingStandardsIgnoreEnd

    /**
     * Get data if the cookie exists and else initialize an new one
     *
     * @param string      $name Cookie name before encrypting
     * @param string      $path
     *
     * @param string|null $expire
     * @param array|null  $sharedUrls
     * @param bool        $standalone
     * @param bool        $secure
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function __construct($name, $path = '', $expire = null, $sharedUrls = null, $standalone = false, $secure = false)
    {
        $this->_content = [];
        $this->_standalone = $standalone;
        $this->_expire = is_null($expire) ? time() + 1728000 : (int) $expire;

        $this->_path = trim(($this->_standalone ? '' : Context::getContext()->shop->physical_uri).$path, '/\\').'/';
        if ($this->_path[0] != '/') {
            $this->_path = '/'.$this->_path;
        }
        $this->_path = rawurlencode($this->_path);
        $this->_path = str_replace('%2F', '/', $this->_path);
        $this->_path = str_replace('%7E', '~', $this->_path);
        // Take Windows case insensitivity of file paths into account
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->_path = mb_strtolower($this->_path);
        }

        $this->_domain = $this->getDomain($sharedUrls);
        $this->_name = 'thirtybees-'.md5($name.$this->_domain);
        $this->_allow_writing = true;
        $this->_salt = $this->_standalone ? str_pad('', 8, md5('ps'.__FILE__)) : _COOKIE_IV_;
        $this->_secure = (bool) $secure;

        $this->update();
    }

    /**
     * @param null $sharedUrls
     *
     * @return bool|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected function getDomain($sharedUrls = null)
    {
        $r = '!(?:(\w+)://)?(?:(\w+)\:(\w+)@)?([^/:]+)?(?:\:(\d*))?([^#?]+)?(?:\?([^#]+))?(?:#(.+$))?!i';

        if (!preg_match($r, Tools::getHttpHost(false, false), $out) || !isset($out[4])) {
            return false;
        }

        if (preg_match(
            '/^(((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]{1}[0-9]|[1-9]).)'.
            '{1}((25[0-5]|2[0-4][0-9]|[1]{1}[0-9]{2}|[1-9]{1}[0-9]|[0-9]).)'.
            '{2}((25[0-5]|2[0-4][0-9]|[1]{1}[0-9]{2}|[1-9]{1}[0-9]|[0-9]){1}))$/', $out[4]
        )) {
            return false;
        }
        if (!strstr(Tools::getHttpHost(false, false), '.')) {
            return false;
        }

        $domain = false;
        if ($sharedUrls !== null) {
            foreach ($sharedUrls as $sharedUrl) {
                if ($sharedUrl != $out[4]) {
                    continue;
                }
                if (preg_match('/^(?:.*\.)?([^.]*(?:.{2,4})?\..{2,3})$/Ui', $sharedUrl, $res)) {
                    $domain = '.'.$res[1];
                    break;
                }
            }
        }
        if (!$domain) {
            $domain = $out[4];
        }

        return $domain;
    }

    /**
     * Get cookie content
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function update($nullValues = false)
    {
        if (isset($_COOKIE[$this->_name])) {
            /* Decrypt cookie content */
            $content = $this->getCipherTool()->decrypt(Tools::base64UrlDecode($_COOKIE[$this->_name]));
            if ($content) {
                /* Get cookie checksum */
                $tmpTab = explode('¤', $content);
                array_pop($tmpTab);
                $contentForChecksum = implode('¤', $tmpTab) . '¤';
                $checksum = crc32($this->_salt . $contentForChecksum);

                /* Unserialize cookie content */
                $tmpTab = explode('¤', $content);
                foreach ($tmpTab as $keyAndValue) {
                    $tmpTab2 = explode('|', $keyAndValue);
                    if (count($tmpTab2) == 2) {
                        $this->_content[$tmpTab2[0]] = $tmpTab2[1];
                    }
                }
                /* Blowfish fix */
                if (isset($this->_content['checksum'])) {
                    $this->_content['checksum'] = (int)($this->_content['checksum']);
                }
            }

            /* Check if cookie has not been modified */
            if (!isset($this->_content['checksum']) || $this->_content['checksum'] != $checksum) {
                $this->delete();
            }

            if (!isset($this->_content['date_add'])) {
                $this->_content['date_add'] = date('Y-m-d H:i:s');
            }
        } else {
            $this->_content['date_add'] = date('Y-m-d H:i:s');
        }

        //checks if the language exists, if not choose the default language
        if (!$this->_standalone && !Language::getLanguage((int) $this->id_lang)) {
            $this->_content['id_lang'] = Configuration::get('PS_LANG_DEFAULT');
            // set detect_language to force going through Tools::setCookieLanguage to figure out browser lang
            $this->_content['detect_language'] = true;
        }
    }

    /**
     * Delete cookie
     *
     * @deprecated 1.0.0 Use Customer::logout() or Employee::logout() instead;
     */
    public function logout()
    {
        Tools::displayAsDeprecated();
        $this->delete();
    }

    /**
     * Deletes cookie
     *
     * @since   1.1.1
     */
    public function delete()
    {
        $this->_content = [];
        $this->_setcookie();
        unset($_COOKIE[$this->_name]);
        $this->_modified = true;
    }

    /**
     * Setcookie according to php version
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function _setcookie($cookie = null)
    {
        if ($cookie) {
            $content = $this->getCipherTool()->encrypt($cookie);
            $time = $this->_expire;
        } else {
            $content = 0;
            $time = 1;
        }

        return setrawcookie($this->_name, Tools::base64UrlEncode($content), $time, $this->_path, $this->_domain, $this->_secure, true);
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function disallowWriting()
    {
        $this->_allow_writing = false;
    }

    /**
     * Set expiration date
     *
     * @param int $expire Expiration time from now
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setExpire($expire)
    {
        $this->_expire = (int) ($expire);
    }

    /**
     * Magic method wich return cookie data from _content array
     *
     * @param string $key key wanted
     *
     * @return string value corresponding to the key
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __get($key)
    {
        return isset($this->_content[$key]) ? $this->_content[$key] : false;
    }

    /**
     * Magic method which adds data into _content array
     *
     * @param string $key   Access key for the value
     * @param mixed  $value Value corresponding to the key
     *
     * @throws Exception
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __set($key, $value)
    {
        if ($key === '_cipherTool') {
            Tools::displayAsDeprecated('Cookie object no longer contains _cipherTool property');
            return;
        }
        if (is_array($value)) {
            throw new Exception("Value can't be array");
        }
        if (preg_match('/¤|\|/', $key.$value)) {
            throw new Exception('Forbidden chars in cookie');
        }
        if (!$this->_modified && (!isset($this->_content[$key]) || (isset($this->_content[$key]) && $this->_content[$key] != $value))) {
            $this->_modified = true;
        }
        $this->_content[$key] = $value;
    }

    /**
     * Magic method which check if key exists in the cookie
     *
     * @param string $key key wanted
     *
     * @return bool key existence
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __isset($key)
    {
        return isset($this->_content[$key]);
    }

    /**
     * Magic method wich delete data into _content array
     *
     * @param string $key key wanted
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __unset($key)
    {
        if (isset($this->_content[$key])) {
            $this->_modified = true;
        }
        unset($this->_content[$key]);
    }

    /**
     * Check customer informations saved into cookie and return customer validity
     *
     * @deprecated 1.0.0 use Customer::isLogged() instead
     *
     * @param bool $withGuest
     *
     * @return bool customer validity
     */
    public function isLogged($withGuest = false)
    {
        Tools::displayAsDeprecated();
        if (!$withGuest && $this->is_guest == 1) {
            return false;
        }

        /* Customer is valid only if it can be load and if cookie password is the same as database one */
        if ($this->logged == 1 && $this->id_customer && Validate::isUnsignedId($this->id_customer) && Customer::checkPassword((int) ($this->id_customer), $this->passwd)) {
            return true;
        }

        return false;
    }

    /**
     * Check employee informations saved into cookie and return employee validity
     *
     * @deprecated 1.0.0 use Employee::isLoggedBack() instead
     * @return bool employee validity
     */
    public function isLoggedBack()
    {
        Tools::displayAsDeprecated();

        /* Employee is valid only if it can be load and if cookie password is the same as database one */

        return ($this->id_employee
            && Validate::isUnsignedId($this->id_employee)
            && Employee::checkPassword((int) $this->id_employee, $this->passwd)
            && (!isset($this->_content['remote_addr']) || $this->_content['remote_addr'] == ip2long(Tools::getRemoteAddr()) || !Configuration::get('PS_COOKIE_CHECKIP'))
        );
    }

    /**
     * Soft logout, delete everything links to the customer
     * but leave there affiliate's informations.
     *
     * @deprecated 1.0.0 use Customer::mylogout() instead;
     */
    public function mylogout()
    {
        unset($this->_content['id_compare']);
        unset($this->_content['id_customer']);
        unset($this->_content['id_guest']);
        unset($this->_content['is_guest']);
        unset($this->_content['id_connections']);
        unset($this->_content['customer_lastname']);
        unset($this->_content['customer_firstname']);
        unset($this->_content['passwd']);
        unset($this->_content['logged']);
        unset($this->_content['email']);
        unset($this->_content['id_cart']);
        unset($this->_content['id_address_invoice']);
        unset($this->_content['id_address_delivery']);
        $this->_modified = true;
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function makeNewLog()
    {
        unset($this->_content['id_customer']);
        unset($this->_content['id_guest']);
        Guest::setNewGuest($this);
        $this->_modified = true;
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __destruct()
    {
        $this->write();
    }

    /**
     * Save cookie with setcookie()
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function write()
    {
        if (!$this->_modified) {
            return true;
        }

        if (headers_sent() || !$this->_allow_writing) {
            return false;
        }

        $cookie = '';

        /* Serialize cookie content */
        if (isset($this->_content['checksum'])) {
            unset($this->_content['checksum']);
        }
        foreach ($this->_content as $key => $value) {
            $cookie .= $key.'|'.$value.'¤';
        }

        /* Add checksum to cookie */
        $cookie .= 'checksum|'.crc32($this->_salt.$cookie);
        $this->_modified = false;

        /* Cookies are encrypted for evident security reasons */

        return $this->_setcookie($cookie);
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param string $origin
     */
    public function unsetFamily($origin)
    {
        $family = $this->getFamily($origin);
        foreach (array_keys($family) as $member) {
            unset($this->$member);
        }
    }

    /**
     * Get a family of variables (e.g. "filter_")
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     * @param string $origin
     *
     * @return array
     */
    public function getFamily($origin)
    {
        $result = [];
        if (count($this->_content) == 0) {
            return $result;
        }
        foreach ($this->_content as $key => $value) {
            if (strncmp($key, $origin, strlen($origin)) == 0) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getAll()
    {
        return $this->_content;
    }

    /**
     * @return String name of cookie
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Check if the cookie exists
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function exists()
    {
        return isset($_COOKIE[$this->_name]);
    }

    /**
     * Get the cipher tool instance used by this cookie instance
     *
     * @return Blowfish|Rijndael|PhpEncryption
     *
     * @since 1.0.1
     */
    public function getCipherTool()
    {
        return $this->_standalone ? Encryptor::getStandaloneInstance(__FILE__) : Encryptor::getInstance();
    }
}
