<?php
/**
 * Blacklist - проверка E-Mail пользователей на наличие в базах спамеров.
 *
 * Версия:    1.2
 * Автор:    Karel Wintersky
 *
 **/

/**
 * Class PluginBlacklist_ModuleBlacklist
 */
class PluginBlacklist_ModuleBlacklist extends Module
{
    const SERVICE_STOPFORUMSPAM_COM = 1;
    const SERVICE_BOTSCOUT_COM = 2;
    const SERVICE_FSPAMLIST_COM = 3;
    const TYPE_MAIL = 'mail';
    const TYPE_IP = 'ip';
    protected $oMapper;

    /**
     *
     */
    public function Init()
    {
        $this->oMapper = Engine::GetMapper(__CLASS__);
    }

    /**
     * Главная функция. Возвращает TRUE если емейл не прошел одну из проверок на валидность
     * (то есть его нет в white-list или он есть в blacklist)
     *
     * @param $sMail
     * @param null $sName
     * @return bool|mixed
     */
    public function checkCredentialsBlocked($sMail, $sName = null)
    {
        $sIp = func_getIp();

        if (
            ($this->check_whitelist_users_mail($sMail)) ||      // проверка по белому списку конкретных емейлов
            ($this->check_whitelist_users_name($sName)) ||      // проверка по белому списку логинов
            ($this->check_whitelist_users_ip($sIp)) ||          // проверка по белому списку IP
             $this->check_whitelist_domains($sMail)             // проверка по белому списку доменов емейлов
        ) {
            // одно из правил вернуло TRUE, то есть email валиден и не заблокирован (то есть isBlocked === FALSE)
            return false;
        }

        if (
            ($this->check_blacklist_users_mail($sMail)) ||      // проверка по черному списку конкретных емейлов
            ($this->check_blacklist_users_name($sName)) ||      // проверка по черному списку логинов
            ($this->check_blacklist_users_ip($sIp)) ||          // проверка по черному списку IP
            $this->check_blacklist_domains($sMail)              // проверка по черному списку доменов емейлов
        ) {
            // одно из правил вернуло TRUE, то есть email заблокирован (то есть isBlocked === TRUE)
            return true;
        }

        // емейл не найден ни в белых списках, ни в чёрных
        // обрабатываем

        $bCheckMail = (Config::Get('plugin.blacklist.check_mail') && $sMail);
        $bCheckIp = (Config::Get('plugin.blacklist.check_ip') && $sIp && $sIp !== '127.0.0.1');
        if (!$bCheckMail && !$bCheckIp) {
            return false;
        }

        $bIpExact = Config::Get('plugin.blacklist.check_ip_exact');
        $aResult = $this->check_local_base($sMail, $sIp, $bCheckMail, $bCheckIp);
        if (is_array($aResult)) {
            if ($this->analyse_result($aResult, $bCheckMail, $bCheckIp, $bIpExact)) {
                return true;
            } elseif ((!$bCheckMail || ($bCheckMail && isset($aResult[self::TYPE_MAIL]))) &&
                (!$bCheckIp || ($bCheckIp && isset($aResult[self::TYPE_IP])))) {
                return false;
            }
        }
        $bMail = false;
        $bIp = false;
        $bResult = false;

        if (!$bResult && Config::Get('plugin.blacklist.use_botscout_com')) {
            $aResult = $this->check_botscout_com($sMail, $sIp, $bCheckMail, $bCheckIp);
            $bMail |= (is_array($aResult) && isset($aResult[self::TYPE_MAIL]) ? $aResult[self::TYPE_MAIL] : false);
            $bIp |= (is_array($aResult) && isset($aResult[self::TYPE_IP]) ? $aResult[self::TYPE_IP] : false);
            $bResult = $this->analyse_result($aResult, $bCheckMail, $bCheckIp, $bIpExact);
            if ($bCheckMail) {
                $this->AddMailResult($sMail, $bMail, self::SERVICE_BOTSCOUT_COM);
            }
            if ($bCheckIp) {
                $this->AddIpResult($sIp, $bIp, self::SERVICE_BOTSCOUT_COM);
            }
        }
        if (!$bResult && Config::Get('plugin.blacklist.use_fspamlist_com')) {
            $aResult = $this->check_fspamlist_com($sMail, $sIp, $bCheckMail, $bCheckIp);
            $bMail |= (is_array($aResult) && isset($aResult[self::TYPE_MAIL]) ? $aResult[self::TYPE_MAIL] : false);
            $bIp |= (is_array($aResult) && isset($aResult[self::TYPE_IP]) ? $aResult[self::TYPE_IP] : false);
            $bResult = $this->analyse_result($aResult, $bCheckMail, $bCheckIp, $bIpExact);
            if ($bCheckMail) {
                $this->AddMailResult($sMail, $bMail, self::SERVICE_FSPAMLIST_COM);
            }
            if ($bCheckIp) {
                $this->AddIpResult($sIp, $bIp, self::SERVICE_FSPAMLIST_COM);
            }
        }
        return $bResult;
    }

    /**
     * Проверка емейла по белому списку ЕМЕЙЛОВ
     *
     * @param $sMail
     * @return bool
     */
    public function check_whitelist_users_mail($sMail)
    {
        if (empty($sMail)) {
            return false;
        }

        $whitelist_users_mail = Config::Get('plugin.blacklist.whitelist_users_mail');

        return in_array(mb_strtolower($sMail), $whitelist_users_mail);
    }

    /**
     * Проверка по белому списку юзернеймов
     *
     * @param $sName
     * @return bool
     */
    public function check_whitelist_users_name($sName)
    {
        if (empty($sName)) {
            return false;
        }

        $whitelist_users_name = Config::Get('plugin.blacklist.whitelist_users_name');

        return in_array(mb_strtolower($sName), $whitelist_users_name);
    }

    /**
     * Проверка по белому списку IP
     *
     * @param $sIp
     * @return bool
     */
    public function check_whitelist_users_ip($sIp)
    {
        $whitelist_users_ip = Config::Get('plugin.blacklist.whitelist_users_ip');

        return in_array(mb_strtolower($sIp), $whitelist_users_ip);
    }

    /**
     * Проверяет ДОМЕНЫ
     *
     * @param $sMail
     * @return bool
     */
    public function check_whitelist_domains($sMail)
    {
        $whitelist_zones = Config::Get('plugin.blacklist.whitelist_zones');

        $aMail = explode("@", $sMail);
        $sDomain = (count($aMail) > 1 ? $aMail[1] : '');
        $aDomain = explode('.', $sDomain);
        $sBaseDomain = count($aDomain) > 2 ? $aDomain[count($aDomain) - 2] . '.' . $aDomain[count($aDomain) - 1] : $sDomain;
        $sZone = count($aDomain) > 1 ? $aDomain[count($aDomain) - 1] : $sDomain;
        $aDomains = Config::Get('plugin.blacklist.whitelist_domains');

        return in_array(mb_strtolower($sDomain), $aDomains)
            || in_array(mb_strtolower($sBaseDomain), $aDomains)
            || in_array(mb_strtolower($sZone), $whitelist_zones);
    }

    /**
     * @param $sMail
     * @return bool
     */
    public function check_blacklist_users_mail($sMail)
    {
        if (empty($sMail)) {
            return false;
        }

        return in_array(mb_strtolower($sMail), Config::Get('plugin.blacklist.blacklist_users_mail'));
    }

    /**
     * @param $sName
     * @return bool
     */
    public function check_blacklist_users_name($sName)
    {
        if (empty($sName)) {
            return false;
        }

        return in_array(mb_strtolower($sName), Config::Get('plugin.blacklist.blacklist_users_name'));
    }

    /**
     * @param $sIp
     * @return bool
     */
    public function check_blacklist_users_ip($sIp)
    {
        return in_array(mb_strtolower($sIp), Config::Get('plugin.blacklist.blacklist_users_ip'));
    }

    /**
     * @param $sMail
     * @return bool
     */
    public function check_blacklist_domains($sMail)
    {
        $aMail = explode("@", $sMail);
        $sDomain = (count($aMail) > 1 ? $aMail[1] : '');
        $aDomain = explode('.', $sDomain);
        $sBaseDomain = count($aDomain) > 2 ? $aDomain[count($aDomain) - 2] . '.' . $aDomain[count($aDomain) - 1] : $sDomain;
        $sZone = count($aDomain) > 1 ? $aDomain[count($aDomain) - 1] : $sDomain;
        $aDomains = Config::Get('plugin.blacklist.blacklist_domains');
        return (in_array('*', $aDomains) || in_array(mb_strtolower($sDomain), $aDomains) || in_array(mb_strtolower($sBaseDomain), $aDomains) || in_array(mb_strtolower($sZone), Config::Get('plugin.blacklist.blacklist_zones')));
    }

    /**
     * @param $sMail
     * @param $sIp
     * @param $bCheckMail
     * @param $bCheckIp
     * @return array|bool
     */
    public function check_local_base($sMail, $sIp, $bCheckMail, $bCheckIp)
    {
        $aWhere = array();
        if ($bCheckMail) {
            $aWhere[self::TYPE_MAIL] = $sMail;
        }
        if ($bCheckIp) {
            $aWhere[self::TYPE_IP] = $sIp;
        }
        if (!$bCheckMail && !$bCheckIp) {
            return false;
        }
        $aInfo = $this->oMapper->Check($aWhere);

        if (DEBUG) {
            error_log('Local Base');
            error_log(serialize($aWhere));
            error_log(serialize($aInfo));
        }

        if ($aInfo) {
            $bMailExist = false;
            $bIpExist = false;
            $bMail = false;
            $bIp = false;
            foreach ($aInfo as $aItem) {
                if (isset($aItem['content'])) {
                    if ($bCheckMail && $aItem['content'] == $sMail) {
                        $bMail |= ((isset($aItem['result']) && $aItem['result']) ? true : false);
                        $bMailExist = true;
                    } elseif ($bCheckIp && $aItem['content'] == $sIp) {
                        $bIp |= ((isset($aItem['result']) && $aItem['result']) ? true : false);
                        $bIpExist = true;
                    }
                }
            }

            $bResult = array();
            if ($bMailExist) {
                $bResult[self::TYPE_MAIL] = $bMail;
            }
            if ($bIpExist) {
                $bResult[self::TYPE_IP] = $bIp;
            }
            return $bResult;
        }
        return false;
    }

    /**
     * @param $aResult
     * @param $bCheckMail
     * @param $bCheckIp
     * @param $bIpExact
     * @return bool|mixed
     */
    private function analyse_result($aResult, $bCheckMail, $bCheckIp, $bIpExact)
    {
        if (!is_array($aResult)) {
            return false;
        }
        $bMail = (isset($aResult[self::TYPE_MAIL]) ? $aResult[self::TYPE_MAIL] : false);
        $bIp = (isset($aResult[self::TYPE_IP]) ? $aResult[self::TYPE_IP] : false);
        if ($bCheckMail && !$bCheckIp) {
            return $bMail;
        } else if (!$bCheckMail && $bCheckIp) {
            return $bIp;
        } else if ($bCheckMail && $bCheckIp) {
            return ($bIpExact ? ($bMail && $bIp) : ($bMail || $bIp));
        }
    }



    /**
     * @param $sMail
     * @param $bResult
     * @param $iService
     */
    public function AddMailResult($sMail, $bResult, $iService)
    {
        $this->oMapper->AddResult(self::TYPE_MAIL, $sMail, $bResult, $iService);
    }

    /**
     * @param $sIp
     * @param $bResult
     * @param $iService
     */
    public function AddIpResult($sIp, $bResult, $iService)
    {
        $this->oMapper->AddResult(self::TYPE_IP, $sIp, $bResult, $iService);
    }

    /**
     * @param $sMail
     * @param $sIp
     * @param $bCheckMail
     * @param $bCheckIp
     * @return array|bool
     */
    public function check_botscout_com($sMail, $sIp, $bCheckMail, $bCheckIp)
    {
        $aParams = array(
            'key' => Config::Get('plugin.blacklist.key_botscout_com'),
        );
        if ($bCheckMail) {
            $aParams['mail'] = $sMail;
        }
        if ($bCheckIp) {
            $aParams['ip'] = $sIp;
        }
        if ($bCheckMail && $bCheckIp) {
            $aParams['multi'] = true;
        }
        $sUrl = 'http://botscout.com/test/' . '?' . urldecode(http_build_query($aParams));
        $sAnswer = @file_get_contents($sUrl);

        if ($sAnswer) {
            $aAnswer = explode('|', $sAnswer);
            if (count($aAnswer) > 1 && $aAnswer[0] === 'Y') {
                $bMail = false;
                $bIp = false;
                $iMailLimit = Config::Get('plugin.blacklist.check_mail_limit');
                $iIpLimit = Config::Get('plugin.blacklist.check_ip_limit');

                if ($bCheckMail && $bCheckIp && $aAnswer[1] === 'MULTI') {
                    for ($i = 2; $i < count($aAnswer); $i += 2) {
                        if (isset($aAnswer[$i]) && isset($aAnswer[$i + 1])) {
                            if ($aAnswer[$i] == 'MAIL') {
                                $bMail = ($aAnswer[$i + 1] >= $iMailLimit);
                            } elseif ($aAnswer[$i] == 'IP') {
                                $bIp = ($aAnswer[$i + 1] >= $iIpLimit);
                            }
                        }
                    }
                } else if (count($aAnswer) == 3) {
                    if ($bCheckMail && $aAnswer[1] === 'MAIL') {
                        $bMail = ($aAnswer[2] >= $iMailLimit);
                    } else if ($bCheckMail && $aAnswer[1] === 'IP') {
                        $bIp = ($aAnswer[4] >= $iIpLimit);
                    }
                }
                return array(
                    self::TYPE_MAIL => $bMail,
                    self::TYPE_IP => $bIp,
                );
            }
        }
        return false;
    }

    /**
     * @param $sMail
     * @param $sIp
     * @param $bCheckMail
     * @param $bCheckIp
     * @return array|bool
     */
    public function check_fspamlist_com($sMail, $sIp, $bCheckMail, $bCheckIp)
    {
        $aParams = array(
            'json' => true,
            'key' => Config::Get('plugin.blacklist.key_fspamlist_com'),
        );
        $aSpammer = array();
        if ($bCheckMail) {
            $aSpammer[] = $sMail;
        }
        if ($bCheckIp) {
            $aSpammer[] = $sIp;
        }
        if ($bCheckMail || $bCheckIp) {
            $aParams['spammer'] = implode(',', $aSpammer);
        } else {
            return false;
        }
        $sUrl = 'http://www.fspamlist.com/api.php' . '?' . urldecode(http_build_query($aParams));
        $sAnswer = @file_get_contents($sUrl);

        if (DEBUG) {
            error_log('fspamlist.com');
            error_log($sUrl);
            error_log($sAnswer);
        }

        $aInfo = json_decode($sAnswer, true);
        if (count($aInfo)) {
            $bMail = false;
            $bIp = false;
            $iMailLimit = Config::Get('plugin.blacklist.check_mail_limit');
            $iIpLimit = Config::Get('plugin.blacklist.check_ip_limit');
            foreach ($aInfo as $aItem) {
                if (isset($aItem['spammer'])) {
                    if ($bCheckMail && $aItem['spammer'] == $sMail) {
                        $bMail = ((isset($aItem['isspammer']) && isset($aItem['timesreported']) && $aItem['isspammer']) ? ($aItem['timesreported'] >= $iMailLimit) : false);
                    } elseif ($bCheckIp && $aItem['spammer'] == $sIp) {
                        $bIp = ((isset($aItem['isspammer']) && isset($aItem['timesreported']) && $aItem['isspammer']) ? ($aItem['timesreported'] >= $iIpLimit) : false);
                    }
                }
            }
            return array(
                self::TYPE_MAIL => $bMail,
                self::TYPE_IP => $bIp,
            );
        }
        return false;
    }
}
