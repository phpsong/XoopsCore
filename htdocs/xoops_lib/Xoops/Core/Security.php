<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core;

/**
 * XOOPS security handler
 *
 * @category  Xoops\Core
 * @package   Security
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2014 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     2.0.0
 */
class Security
{
    private $errors = array();

    /**
     * Check if there is a valid token in $_REQUEST[$name . '_REQUEST']
     *
     * @param bool   $clearIfValid whether to clear the token after validation
     * @param bool   $token        token to validate
     * @param string $name         name of session variable
     *
     * @return bool
     */
    public function check($clearIfValid = true, $token = false, $name = 'XOOPS_TOKEN')
    {
        return $this->validateToken($token, $clearIfValid, $name);
    }

    /**
     * Create a token in the user's session
     *
     * @param int    $timeout time in seconds the token should be valid
     * @param string $name    name of session variable
     *
     * @return string token value
     */
    public function createToken($timeout = 0, $name = 'XOOPS_TOKEN')
    {
        $this->garbageCollection($name);
        if ($timeout <= 0) {
            $expire = @ini_get('session.gc_maxlifetime');
            $timeout = ($expire > 0) ? $expire : 900;
        }
        $token_id = md5(uniqid(rand(), true));
        // save token data on the server
        if (!isset($_SESSION[$name . '_SESSION'])) {
            $_SESSION[$name . '_SESSION'] = array();
        }
        $token_data = array(
            'id' => $token_id, 'expire' => time() + intval($timeout)
        );
        array_push($_SESSION[$name . '_SESSION'], $token_data);
        return md5($token_id . $_SERVER['HTTP_USER_AGENT'] . XOOPS_DB_PREFIX);
    }

    /**
     * Check if a token is valid. If no token is specified, $_REQUEST[$name . '_REQUEST'] is checked
     *
     * @param string|bool $token        token to validate
     * @param bool        $clearIfValid whether to clear the token value if valid
     * @param string      $name         session name to validate
     *
     * @return bool
     */
    public function validateToken($token = false, $clearIfValid = true, $name = 'XOOPS_TOKEN')
    {
        $ret = false;
        $log = array();
        $token = ($token !== false)
			? $token
			: (isset($_REQUEST[$name . '_REQUEST']) ? $_REQUEST[$name . '_REQUEST'] : '');
        if (empty($token) || empty($_SESSION[$name . '_SESSION'])) {
			$str = 'No valid token found in request/session';
			$this->setErrors($str);
			$log[] = array('Token Validation', $str);
        } else {
            $token_data =& $_SESSION[$name . '_SESSION'];
            if(is_array($token_data)) foreach (array_keys($token_data) as $i) {
                if ($token === md5($token_data[$i]['id'] . $_SERVER['HTTP_USER_AGENT'] . XOOPS_DB_PREFIX)) {
                    if ($this->filterToken($token_data[$i])) {
                        if ($clearIfValid) {
                            // token should be valid once, so clear it once validated
                            unset($token_data[$i]);
                        }
                        $log[] = array('Token Validation', 'Valid token found');
                        $ret = true;
                    } else {
                        $str = 'Valid token expired';
                        $this->setErrors($str);
                        $log[] = array('Token Validation', $str);
                    }
                }
            }
            if (!$ret) {
                $log[] = array('Token Validation', 'No valid token found');
            }
            $this->garbageCollection($name);
        }
        \Xoops::getInstance()->preload()->triggerEvent('core.security.validatetoken.end', array($log));
        return $ret;
    }

    /**
     * Clear all token values from user's session
     *
     * @param string $name session name
     *
     * @return void
     */
    public function clearTokens($name = 'XOOPS_TOKEN')
    {
        $_SESSION[$name . '_SESSION'] = array();
    }

    /**
     * Check whether a token value is expired or not
     *
     * @param string $token token
     *
     * @return bool
     */
    public function filterToken($token)
    {
        return (!empty($token['expire']) && $token['expire'] >= time());
    }

    /**
     * Perform garbage collection, clearing expired tokens
     *
     * @param string $name session name
     *
     * @return void
     */
    public function garbageCollection($name = 'XOOPS_TOKEN')
    {
		$sessionName = $name . '_SESSION';
        if (!empty($_SESSION[$sessionName]) && is_array($_SESSION[$sessionName])) {
            $_SESSION[$sessionName] = array_filter($_SESSION[$sessionName], array($this, 'filterToken'));
        }
    }

    /**
     * Check the user agent's HTTP REFERER against XOOPS_URL
     *
     * @param int $docheck 0 to not check the referer (used with XML-RPC), 1 to actively check it
     *
     * @return bool
     */
    public function checkReferer($docheck = 1)
    {
        $ref = \Xoops::getInstance()->getEnv('HTTP_REFERER');
        if ($docheck == 0) {
            return true;
        }
        if ($ref == '') {
            return false;
        }
        if (strpos($ref, XOOPS_URL) !== 0) {
            return false;
        }
        return true;
    }

    /**
     * Check superglobals for contamination
     *
     * @return bool
     */
    public function checkSuperglobals()
    {
		foreach(array_keys($_REQUEST) as $key) {
			if (in_array($key, array(
				'GLOBALS', '_SESSION', 'HTTP_SESSION_VARS', '_GET', 'HTTP_GET_VARS', '_POST', 'HTTP_POST_VARS', '_COOKIE',
				'HTTP_COOKIE_VARS', '_REQUEST', '_SERVER', 'HTTP_SERVER_VARS', '_ENV', 'HTTP_ENV_VARS', '_FILES',
				'HTTP_POST_FILES')))
				return false;
			if (stripos($key, 'xoops') === 0) // exclude all keys starting with "xoops" ignoring case
				return false;
		}
		return true;
    }

    /**
     * Check if visitor's IP address is banned
     * Should be changed to return bool and let the action be up to the calling script
     *
     * @return void
     */
    public function checkBadips()
    {
        $xoops = \Xoops::getInstance();
        if ($xoops->getConfig('enable_badips') == 1
            && isset($_SERVER['REMOTE_ADDR'])
            && $_SERVER['REMOTE_ADDR'] != ''
        ) {
            foreach ($xoops->getConfig('bad_ips') as $bi) {
                if (!empty($bi) && preg_match('/' . $bi . '/', $_SERVER['REMOTE_ADDR'])) {
                    exit();
                }
            }
        }
    }

    /**
     * Get the HTML code for a XoopsFormHiddenToken object - used in forms that do not use XoopsForm elements
     *
     * @param string $name session token name
     *
     * @return string
     */
    public function getTokenHTML($name = 'XOOPS_TOKEN')
    {
        $token = new \XoopsFormHiddenToken($name);
        return $token->render();
    }

    /**
     * Add an error
     *
     * @param string $error message
     *
     * @return void
     */
    public function setErrors($error)
    {
        $this->errors[] = trim($error);
    }

    /**
     * Get generated errors
     *
     * @param bool $ashtml Format using HTML?
     *
     * @return array|string Array of array messages OR HTML string
     */
    public function getErrors($ashtml = false)
    {
        if (!$ashtml) {
            return $this->errors;
        } else {
			$ret = '';
            if (is_array($this->errors)) {
				$ret = implode('<br />', $this->errors) . '<br />';
            }
            return $ret;
        }
    }
}
