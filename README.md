blacklist
=========

Плагин "Blacklist" (версия 1.3) для LiveStreet 1.0.4

Проверка E-Mail и IP пользователей на наличие в черном и белом списках и, опционально, базах спамеров.

Installation
============

1. Скопировать плагин в каталог /plugins/
2. Отредактировать, при необходимости, файлы `/config/plugins.blacklist/blacklist.txt` и `/config/plugins.blacklist/whitelist.txt`
3. Через панель управления плагинами (/admin/plugins/) запустить его активацию.


Configure
=========

Настройка плагина осуществляется редактированием файла "/plugins/blacklist/config/config.php".

Поддерживаемые директивы:
1) `$config['check_mail']` - Проверять e-mail по базам. По умолчанию включено (true).

2) `$config['check_mail_limit']` - Порог для срабатывания проверки e-mail (не менее указанного значения).

3) `$config['check_ip']` - Проверять IP по базам. По умолчанию включено (true).

4) `$config['check_ip_limit']` - Порог для срабатывания проверки IP (не менее указанного значения).

5) `$config['check_ip_exact']` - Строгая проверка IP (e-mail и IP должны быть в базе одновременно). По умолчанию отлючено (false).

6) `$config['use_botscout_com']` - Использовать базу сайта botscout.com. По умолчанию включено (true).

7) `$config['key_botscout_com']` - Ключ для сайта botscout.com (http://botscout.com/getkey.htm).7

8) `$config['use_fspamlist_com']` - Использовать базу сайта fspamlist.com. По умолчанию включено (true).

9) `$config['key_fspamlist_com']` - Ключ для сайта fspamlist.com (http://fspamlist.com/index.php?c=register).

10) `$config['check_authorization']` - Проверять e-mail при авторизации. По умолчанию включено (true).

11) `$config['whitelist_zones']` - Белый список доменных зон (e-mail с этих доменных зон считаются доверенными и не проверяются).

12) `$config['blacklist_zones']` - Черный список доменных зон (e-mail с этих доменных зон запрещены).

13) `$config['whitelist_domains']` - Белый список доменов (e-mail с этих доменов считаются доверенными и не проверяются).

14) `$config['blacklist_domains']` - Черный список доменов (e-mail с этих доменов запрещены).

15) `$config['whitelist_users_name']` - Белый список пользователей (логины). Проверяется только при авторизации.

16) `$config['whitelist_users_mail']` - Белый список пользователей (e-mail).

17) `$config['whitelist_users_ip']` - Белый список пользователей (IP-адреса).

18) `$config['blacklist_users_name']` - Черный список пользователей (логины). Проверяется только при авторизации.

19) `$config['blacklist_users_mail']` - Черный список пользователей (e-mail).

20) `$config['blacklist_users_ip']` - Черный список пользователей (IP-адреса).

21) `$config['recheck_time']` - Время в секундах, в течении которого данные о предыдущей проверке пользователя считаются корректными. По умолчанию сутки (60*60*24*1).


Changelog
=========

1.3.0 (2018-11-13):

Refactoring for PHP7.2 by Karel Wintersky
Изменена логика правил валидации

1.2.0 (2018-10-31):

Refactoring for PHP7 by Karel Wintersky

1.1.0 (27.10.2015):
- Добавлено кэширование запросов.
- Добавлены параметры конфигурации:
`$config['recheck_time']` - Время в секундах, в течении которого данные о предыдущей проверке пользователя считаются корректными.
`$config['whitelist_zones']` - Белый список доменных зон (e-mail с этих доменных зон считаются доверенными и не проверяются).
`$config['blacklist_zones']` - Черный список доменных зон (e-mail с этих доменных зон запрещены).



1.0.2 (17.10.2014):
- Добавлена возможность запрета всех доменов (маска '*' в черном списке доменов).
- Исправлена неточность в работе с базой fspamlist.com
- Добавлена возможность отключения проверки e-mail.
- Добавлено исключение проверки администраторов на наличие в черном списке при авторизации.
- Добавлены параметры конфигурации:
`$config['check_mail']` - Проверять e-mail по базам.
`$config['whitelist_users_name']` - Белый список пользователей (логины). Проверяется только при авторизации.
`$config['whitelist_users_mail']` - Белый список пользователей (e-mail).
`$config['whitelist_users_ip']` - Белый список пользователей (IP-адреса).
`$config['blacklist_users_name']` - Черный список пользователей (логины). Проверяется только при авторизации.
`$config['blacklist_users_mail']` - Черный список пользователей (e-mail).
`$config['blacklist_users_ip']` - Черный список пользователей (IP-адреса).
`$config['check_mail_limit']` - Порог для срабатывания проверки e-mail (не менее указанного значения).
`$config['check_ip_limit']` - Порог для срабатывания проверки IP (не менее указанного значения).

1.0.1 (19.09.2014):
- Функционал вынесен в отдельный класс.
- Добавлены параметры:
`$config['whitelist_domains']` - Белый список доменов (e-mail с этих доменов считаются доверенными и не проверяются).
`$config['blacklist_domains']` - Черный список доменов (e-mail с этих доменов запрещены).
`$config['check_ip']` - Дополнительно проверять IP.
`$config['check_ip_exact']` - Строгая проверка IP (e-mail и IP должны быть в базе одновременно).

Copyrights
==========

Original: Александр Вереник ( https://github.com/wasja1982/livestreet_blacklist )

Refactoring: Karel Wintersky for Imaginaria Project

