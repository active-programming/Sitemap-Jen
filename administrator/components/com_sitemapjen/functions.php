<?php
/**
 * Sitemap Jen
 * @author Konstantin@Kutsevalov.name
 *
 * Функции для сканера
 */

// инициализация
// может быть произведена только из админ-панели
// в качестве ответа возвращает:
//		- для сканирования: количество потоков, которое можно породить (а также адреса для первого сканирования) или ошибку (error)
//		- для генерации: ошибку или пустой ответ
function doInit()
{
    $param = isset($_POST['param']) ? $_POST['param'] : 'full';
    $thr = intval(SPDO::getSetting('threads', 0));
    if ($thr < 1) {
        $thr = 1;
    } elseif ($thr > 10) {
        $thr = 10;
    }

    $json = [
        'action' => '',
        'logs' => [],
        'urls' => [],
        'newcount' => 0,
        'threadsCount' => 0,
        'error' => 0,
    ];

    file_put_contents(__DIR__ . '/.last_time', '<' . '?' . 'php $lastTime = ' . time() . ';');

    if ($param == 'continue') {
        // ПРОДОЛЖЕНИЕ прерванного или текущего сканирования
        // проверяем есть ли незавершенная задача
        if (SPDO::getSetting('task_action') != 'scan') {
            SPDO::setSetting('task_action', 'scan');
        }
        if (SPDO::getSetting('task_status') != 'in_work') {
            SPDO::setSetting('task_status', 'in_work');
        }
        // есть ли зарезервированные для сканирования адреса?
        $links = SPDO::query("SELECT `id`,`loc` FROM `#__sitemapjen_links` WHERE `changefreq` LIKE '+scan'");
        if (empty($links)) {
            $url = SPDO::getSetting('task_url', '');
            if (substr($url, 0, strlen(WEB_DOMAIN)) != WEB_DOMAIN) {
                $url = WEB_DOMAIN;
            }
            $links = SPDO::query(
                "SELECT `id`,`loc` FROM `#__sitemapjen_links` WHERE `changefreq`='-' AND `loc` LIKE :loc LIMIT {$thr}",
                [':loc' => $url . '%']
            );
        }
        // готовим ответ для клиента
        foreach ($links as $i => $link) {
            SPDO::query("UPDATE `#__sitemapjen_links` SET `changefreq`='+scan' WHERE `id` = :id ", [':id' => $link['id']]);
            $json['urls'][$link['id']] = $link;
        }
        if (count($json['urls']) > 0) {
            $json['action'] = 'scan_init';
            $json['logs'][] = '';
            $json['threadsCount'] = $thr;
        } else {
            $json['error'] = 110;
            $json['logs'][] = 'Ошибка: не найдены адреса для сканирования.';
        }
    } else if ($param == 'no_scan') {
        // ГЕНЕРАЦИЯ SITEMAP ПО ССЫЛКАМ ИЗ БАЗЫ
        // setOption( 'last_starttime', date('d.m.Y H:i') );
        SPDO::setSetting('task_url', '');
        SPDO::setSetting('task_action', 'generate');
        SPDO::setSetting('task_status', 'in_work');
        SPDO::setSetting('task_step', '0');
        $json['logs'][] = 'Генерация sitemap';
        $json['action'] = 'generate';
        // получаем количество адресов в базе
        $res = SPDO::query("SELECT COUNT(*) AS `count` FROM `#__sitemapjen_links`");
        // по нему определим какого вида sitemap генерировать.
        if ($res[0]['count'] > 50000) {
            // составной sitemap
            SPDO::setSetting('task_gentype', 'multiple');
        } else {
            // одиночный sitemap
            SPDO::setSetting('task_gentype', 'single');
        }
        // удаляем старые файлы sitemap
        $files = glob(WEB_ROOT . 'sitemap*.xml');
        foreach ($files as $fl) {
            @unlink($fl);
        }
    } else {
        // НОВОЕ СКАНИРОВАНИЕ
        $url = isset($_POST['url']) ? intval($_POST['url']) : WEB_DOMAIN;
        if (substr($url, 0, strlen(WEB_DOMAIN)) != WEB_DOMAIN) {
            $url = WEB_DOMAIN;
        }
        $url = rtrim($url, '/');
        // сбрасываем частоту изменений всех страниц в базе, как пометку о результате прошлого сканирования
        SPDO::query(
            "UPDATE `#__sitemapjen_links` SET `changefreq`='-' WHERE `loc` LIKE :loc OR `loc` LIKE :loc2",
            [':loc' => $url.'%', ':loc2' => $url]
        );
        SPDO::setSetting('last_starttime', date('d.m.Y H:i'));
        SPDO::setSetting('task_url', $url); // последний сканируемый URL
        SPDO::setSetting('task_action', 'scan');
        SPDO::setSetting('task_status', 'in_work');
        // резервируем несколько адресов для сканирования потоками
        $links = SPDO::query(
            "SELECT `id`,`loc` FROM `#__sitemapjen_links` WHERE `changefreq`='-' AND (`loc` LIKE :loc OR `loc` = :loc2 ) LIMIT {$thr}",
            [':loc' => $url.'%', ':loc2' => $url]
        );
        if (empty($links)) {
            SPDO::query(
                "INSERT INTO `#__sitemapjen_links` (`loc`,`md5_loc`,`changefreq`,`priority`) VALUES ( :url , :md5loc , '-', '0.5')",
                [':url' => $url, ':md5loc' => md5($url)]
            );
            $links[] = ['id' => SPDO::lastInsertId(), 'loc' => $url];
        }
        foreach ($links as $i => $link) {
            SPDO::query("UPDATE `#__sitemapjen_links` SET `changefreq`='+scan' WHERE `id` = :id", [':id' => $link['id']]);
            $json['urls'][$link['id']] = $link;
        }
        if (count($json['urls']) > 0) {
            $json['logs'][] = 'Сканирование сайта';
            $json['threadsCount'] = $thr; // количество потоков, которое можно запускать единовременно
            $json['action'] = 'scan_init';
        } else {
            $json['error'] = 110;
            $json['logs'][] = 'Ошибка инициализации: не найдены адреса для сканирования.';
        }
    }
    return json_encode($json);
}


function doStop()
{
    SPDO::setSetting('task_status', '');
    $json = [
        'action' => 'stop',
        'logs' => [],
        'urls' => [],
        'newcount' => 0,
        'threadsCount' => 0,
        'error' => 0,
    ];
    return json_encode($json);
}


function parseIgnoreList($list = '')
{
    $ignore = explode("\n", $list);
    foreach ($ignore as $i => $v) {
        $v = trim($v);
        if (empty($v)) {
            unset($ignore[$i]);
            continue;
        }
        $ignore[$i] = $v;
    }
    return $ignore;
}


// сканирование сайта
function doScan($mode)
{
    if ($mode == 'cron') {
        // режим CRON
        $log = [];
        // в режиме cron сначала проверяем наличие незавершенных процессов и только если они отсутствуют, запускаются новые
        $links = SPDO::query("SELECT `id`,`loc` FROM `#__sitemapjen_links` WHERE `changefreq`='+scan' OR `changefreq`='-' LIMIT 3");
        if (!empty($links)) {
            foreach ($links as $link) {
                // загружаем страницу
                $page = loadPage($link['loc']);
                if ($page['content'] == '') {
                    $log[] = 'Ошибка, не удалось загрузить страницу: ' . $link['loc'];
                } else {
                    // сканируем страницу
                    $stat = scanPage($link, $page);
                    $log[] = '-&gt; ' . $link['loc'] . '............<b>+ ' . $stat . ' url</b>';
                }
            }
            saveLog($log);
        } else {
            // просканирована последняя страница
            $links = SPDO::query("SELECT COUNT(*) AS `count` FROM `#__sitemapjen_links`");
            $res2 = SPDO::query("SELECT COUNT(*) AS `count` FROM `#__sitemapjen_links` WHERE `changefreq` <> '-'");
            // переходим к новой операции
            SPDO::setSetting('task_action', 'generate');
            $log[] = 'Всего найдено ' . $links['count'] . ' и просканировано ' . $res2['count'] . ' адресов.';
            saveLog($log);
            saveLog("Приступаем к генерации sitemap\n");
        }
        return '';
    } else {
        // режим WEB
        file_put_contents(__DIR__ . '/.last_time', '<' . '?' . 'php $lastTime = ' . time() . ';'); // время запроса
        $locId = isset($_POST['loc_id']) ? intval($_POST['loc_id']) : 0; // номер процесса
        $need = isset($_POST['need']) ? intval($_POST['need']) : 1; // запрос дополнительных адресов для сканирования
        if ($need > 0) {
            $need++; // включая текущий
        }
        $json = [
            'action' => 'scan',
            'logs' => '',
            'urls' => [],
            'thr' => $locId, // ID записи - это ID потока (ajax)
            'newcount' => 0,
            'threadsCount' => 0,
            'error' => 0,
        ];
        if ($locId > 0) {
            // в режиме ajax ищем зарезервированную для данного запроса запись
            $link = SPDO::query("SELECT `id`,`loc` FROM `#__sitemapjen_links` WHERE `id` = :id ", [':id' => $locId]);
            if (!empty($link)) {
                // загружаем страницу
                $page = loadPage($link[0]['loc']);
                if ($page['content'] == '') {
                    $json['logs'][] = 'Ошибка, не удалось загрузить страницу: ' . $link[0]['loc'];
                    $json['error'] = 510;
                } else {
                    // сканируем страницу
                    $json['newcount'] = scanPage($link[0], $page);
                }
            } else {
                $json['error'] = 120; // текущая ссылка не была найдена
            }
        } else {
            // запрос адресов (недостаточно потоков на стороне клиента)
        }
        // пытаемся получить новую ссылку(ки)
        if ($need > 0) {
            $links = SPDO::query("SELECT `id`,`loc` FROM `#__sitemapjen_links` WHERE `changefreq`='-' LIMIT {$need}");
            if (!empty($links)) {
                foreach ($links as $link) {
                    $json['urls'][$link['id']] = $link;
                    SPDO::query("UPDATE `#__sitemapjen_links` SET `changefreq`='+scan' WHERE `id` = :id", [':id' => $link['id']]);
                }
            } else {
                // если доступных ссылок нет, возможно что ссылки закончились или просто не успели появиться новые.
                // посему не делаем поспешных выводов, а возвращаем пустой ответ, чтобы менеджер запросов на стороне клиента
                // смог сделать объективный вывод на основе других ответов.
                $json['error'] = 200; // доступных адресов не найдено
            }
        }
        return json_encode($json);
    }
    return;
}


function scanPage($link, $page)
{
    $now = date('Y-m-d');
    // парсим ссылки
    $links = grabLinks($link['loc'], $page['content']);
    // сохраняем в базу
    $stat = saveLinks($links);
    // вычисляем хеш контента загруженной страницы
    $md5c = md5(grabContent($page['content']));
    $thisLoc = SPDO::query("SELECT `md5_content`,`lastmod` FROM `#__sitemapjen_links` WHERE `id` = :id", [':id' => $link['id']]);
    if (!empty($thisLoc)) {
        $thisLoc = $thisLoc[0];
        // обновляем текущую запись
        if ($thisLoc['md5_content'] != $md5c) {
            // если хеш контента изменился, вычисляем дату прошлого изменения
            if (empty($thisLoc['md5_content'])) { // если предыдущий хеш контента пустой, значит страница сканируется впервые
                $period = 'monthly';
            } else {
                $period = getPeriod($thisLoc['lastmod']);
            }
            SPDO::query(
                "UPDATE `#__sitemapjen_links` SET `changefreq` = :period ,`lastmod` = :now ,`md5_content` = :md5c WHERE `id` = :id",
                [':period' => $period, ':now' => $now, ':md5c' => $md5c, ':id' => $link['id']]
            );
        } else {
            SPDO::query(
                "UPDATE `#__sitemapjen_links` SET `changefreq` = :period WHERE `id` = :id",
                [':period' => 'monthly', ':id' => $link['id']]
            );
        }
    }
    unset($page);
    return $stat;
}


// Парсит ссылки из страницы и обновляет информацию о них в базе
function grabLinks($url, &$page)
{
    $total = []; // итоговый список спарсенных адресов
    $count = preg_match_all('/<a.+href="([^"]+)"/iU', $page, $links);
    if ($count > 0) {
        $links = $links[1];
        // фильтруем ссылки
        $ignore = parseIgnoreList(SPDO::getSetting('ignore_list'));
        foreach ($links as $href) {
            // исключаем js ссылки и тп
            if (substr($href, 0, 1) == '#' ||
                stripos($href, 'javascript') !== false ||
                stripos($href, 'print=') !== false ||
                stripos($href, 'mailto') !== false) {
                continue;
            }
            // корректируем ссылку
            if (!WWW_ALIAS) {
                $href = str_replace(HTTP_SCHEME . 'www.', HTTP_SCHEME, $href); // бывает что на сайте проскакивают адреса с www, в то время как сам сайт без www
            }
            if (strpos($href, 'http://') === false && strpos($href, 'https://') === false) {
                if (substr($href, 0, 1) == '/') {
                    // абсолютная ссылка
                    $href = WEB_DOMAIN . $href;
                } else {
                    // относительная ссылка (корректируем с учетом адреса загруженной страницы)
                    if (substr($url, -1, 1) != '/') {
                        $url .= '/';
                    }
                    $href = $url . $href;
                }
            }
            // ссылки на внешние сайты
            if (substr($href, 0, strlen(WEB_DOMAIN)) != WEB_DOMAIN) {
                continue;
            }
            // опускаем #якоря :)
            $href = explode('#', $href);
            $href = $href[0];
            $ext = explode('.', $href);
            $ext = end($ext);
            if (strlen($ext) < 5 && substr($ext, -1, 1) != '/') { // если у страницы есть расширение, возможно это файл, проверим
                if (!in_array($ext, ['php', 'aspx', 'htm', 'html', 'asp', 'cgi', 'pl'])) {
                    continue;
                }
            }
            // игнор-лист
            $isIgnore = false;
            foreach ($ignore as $iurl) {
                $iurl = str_replace(WEB_DOMAIN, '', $iurl);
                $part = str_replace(WEB_DOMAIN, '', $href);
                $part = substr($part, 0, strlen($iurl));
                if ($iurl == $part) {
                    $isIgnore = true;
                    break;
                }
            }
            if ($isIgnore == true) {
                continue;
            }
            // Исключать адреса вида "?option=com_"
            if (SPDO::getSetting('ignore_option_com') == 'Y' && stripos($href, '?option=com_') !== false) {
                continue;
            }
            // Исключать адреса вида "?query=value&..."
            if (SPDO::getSetting('only_4pu') == 'Y' && stripos($href, '?') !== false) {
                continue;
            }
            // Исключать ссылки "nofollow"
            if (SPDO::getSetting('ignore_nofollow') == 'Y' && preg_match('/ rel=("|\')?nofollow("|\')?( |\>){1}/', $href) > 0) {
                continue;
            }
            $total[] = rtrim($href, '/');
        }
        $total = array_unique($total);
    }
    return $total;
}


// сохраняет в базу ссылки, найденные парсером на странице
function saveLinks($links)
{
    $now = date('Y-m-d');
    $newLinksCount = 0;
    // проверяем есть ли уже такая ссылка в базе
    $has = [];
    if (count($links) > 0) {
        $params = [];
        $query = "SELECT `loc` FROM `#__sitemapjen_links` WHERE ";
        foreach ($links as $i => $link) {
            if ($i > 0) {
                $query .= " OR ";
            }
            $query .= "`md5_loc` = :loc{$i}";
            $params[':loc'.$i] = md5($link);
        }
        $res = SPDO::query($query, $params);
        if (!empty($res)) {
            foreach ($res as $row) {
                $has[] = $row['loc'];
            }
        }
        unset($res);
    }
    // теперь смотрим, есть ли что добавить?
    if (count($has) < count($links)) {
        $query = "INSERT INTO `#__sitemapjen_links` (`loc`,`md5_loc`,`lastmod`,`changefreq`,`priority`,`md5_content`) VALUES ";
        $data = [];
        foreach ($links as $i => $link) {
            $key = array_search($link, $has);
            if ($key === false) { // добавляем новую запись
                $data = $data + [":link{$i}" => $link, ":md5link{$i}" => md5($link), ":now{$i}" => $now, ":chf{$i}" => '-',
                    ":prio{$i}" => '0.5', ":md5c{$i}" => ''];
                $query .= " ( :link{$i} , :md5link{$i} , :now{$i} , :chf{$i} , :prio{$i} , :md5c{$i} ),";
                $newLinksCount++;
            }
        }
        if (count($data) > 0) {
            $query = rtrim($query, ',');
            SPDO::query($query, $data);
        }
    }
    return $newLinksCount;
}


// если в шаблоне есть теги <!--pagecontent--> и <!--/pagecontent-->
// то вырезаем все что находится между ними. Это и будет контентом страницы без шелухи.
function grabContent($content = '')
{
    $pos = strpos($content, '<!--pagecontent-->');
    if ($pos !== false) {
        $content = substr($content, $pos + 18);
    }
    $pos = strpos($content, '<!--/pagecontent-->');
    if ($pos !== false) {
        $content = substr($content, ($pos + 19) * (-1));
    }
    return strip_tags($content);
}


// генерация sitemap на основании базы
function doGenerate($mode)
{
    $json = [
        'action' => 'generate',
        'logs' => '',
        'urls' => [],
        'thr' => '0',
        'newcount' => 0,
        'threadsCount' => 0,
        'error' => 0,
    ];

    $limit = 50000;
    $next = intval(SPDO::getSetting('task_step', 0));
    $type = SPDO::getSetting('task_gentype', 'single');

    $fileNumber = ''; // номер xml файла
    if ($type == 'multiple') {
        if ($next == 0) {
            $fileNumber = 1;
        }
        if ($next >= $limit) {
            $fileNumber = intval($next / $limit) + 1;
        }
    }
    $file = 'sitemap' . $fileNumber . '.xml';

    // считываем очередные N адресов из базы
    $res = SPDO::query("SELECT `loc`,`lastmod`,`changefreq`,`priority` FROM `#__sitemapjen_links` ORDER BY `lastmod` DESC LIMIT {$next},{$limit}");
    if (!empty($res)) {
        $fl = fopen(WEB_ROOT . $file, 'w');
        $head = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        if (fwrite($fl, $head) === false) {
            return json_encode(['error' => 600, 'logs' => ['Ошибка записи в файл!']]);
        }
        $total = 0;
        foreach ($res as $link) {
            $loc = str_replace('&', '&amp;', $link['loc']);
            if (!empty($link['lastmod'])) {
                $link['lastmod'] = '	<lastmod>' . $link['lastmod'] . '</lastmod>' . "\n";
            }
            if ($link['changefreq'] != '-' && $link['changefreq'] != '+scan') {
                $link['changefreq'] = '	<changefreq>' . $link['changefreq'] . '</changefreq>' . "\n";
            } else {
                $link['changefreq'] = '';
            }
            fwrite($fl,
                '<url>' . "\n" .
                '	<loc>' . $loc . '</loc>' . "\n" .
                $link['lastmod'] .
                $link['changefreq'] .
                '	<priority>' . $link['priority'] . '</priority>' . "\n" .
                '</url>' . "\n"
            );
            $total++;
        }
        fwrite($fl, '</urlset>');
        fclose($fl);
        $json['logs'][] = 'generated -> ' . $file;
        if ($total < $limit) {
            // если прочитанное количество записей меньше лимита, значит больше адресов в базе нет
            // генерация завершена
            if ($type == 'multiple') {
                // нужен составной sitemap
                $files = glob(WEB_ROOT . 'sitemap*.xml');
                $fl = fopen(WEB_ROOT . 'sitemap.xml', 'w');
                $head = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
                if (fwrite($fl, $head) === false) {
                    return json_encode(['error' => 600, 'logs' => ['Ошибка записи в файл!']]);
                }
                foreach ($files as $xml) {
                    $loc = WEB_DOMAIN . '/' . basename($xml);
                    fwrite($fl,
                        '<sitemap>' . "\n" .
                        '	<loc>' . $loc . '</loc>' . "\n" .
                        '	<lastmod>' . @date('Y-m-d') . '</lastmod>' . "\n" .
                        '</sitemap>' . "\n"
                    );
                }
                fwrite($fl, '</sitemapindex>');
                fclose($fl);
                $json['logs'][] = 'index map -> sitemap.xml';
                $json['logs'][] = 'Всего ' . ($next + $total) . ' ссылок в ' . $fileNumber . ' файлах.';
            } else {
                $json['logs'][] = 'Всего ' . $total . ' ' . modifWordByCount(['ссылка', 'ссылки', 'ссылок'], $total) . '.';
            }
            $json['action'] = 'end';
            SPDO::setSetting('task_action', '');
            SPDO::setSetting('task_status', '');
        } else {
            $next += $limit;
            SPDO::setSetting('task_step', '');
        }
    } else {
        // нет записей - вообще нет...
        $json['logs'][] = 'В базе нет ссылок для генерации sitemap.';
        $json['action'] = 'stop';
        SPDO::setSetting('task_action', '');
        SPDO::setSetting('task_status', '');
    }
    if ($mode == 'cron') {
        saveLog($json['logs']);
        return '';
    }
    return json_encode($json);
}


/**
 * Загружает страницу
 * @param $url
 * @return array|mixed
 */
function loadPage($url)
{
    // на случай, если не установлен curl
    if (!IS_CURL) {
        $cnt = file_get_contents($url);
        $header = ['content' => $cnt, 'errno' => 0, 'errmsg' => ''];
    } else {
        $options = [
            CURLOPT_CUSTOMREQUEST => "GET", //set request type post or get
            CURLOPT_POST => false, //set to GET
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'], //set user agent
            CURLOPT_COOKIEFILE => ".cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR => ".cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_HEADER => false, // don't return headers
            CURLOPT_FOLLOWLOCATION => true, // follow redirects
            CURLOPT_ENCODING => "", // handle all encodings
            CURLOPT_AUTOREFERER => true, // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
            CURLOPT_TIMEOUT => 120, // timeout on response
            CURLOPT_MAXREDIRS => 2, // stop after 10 redirects
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $header = curl_getinfo($ch);
        $header['errno'] = curl_errno($ch);
        $header['errmsg'] = curl_error($ch);
        $header['content'] = $content;
        curl_close($ch);
    }
    return $header;
}


/**
 * Вычисляет период, за который поменялся контент страницы с последнего сканирования
 * @param string $date Дата последнего сканирования
 * @return string
 */
function getPeriod($date)
{
    // - always
    // - hourly
    // + daily
    // + weekly
    // + monthly
    // + yearly
    // - never
    //конвертируем в timestamp
    $arr = explode(' ', $date);
    $arr = explode('-', $arr[0]);
    // mktime( 0, 0, 0, 12, 32, 1997 )
    $timestamp2 = @mktime(0, 0, 0, @date('m'), @date('d'), @date('Y'));
    $timestamp1 = @mktime(0, 0, 0, $arr[1], $arr[2], $arr[0]);
    $days = floor(($timestamp2 - $timestamp1) / 86400);
    $period = 'yearly';
    if ($days <= 2) { // ну один день сверху накинем чо
        $period = 'daily';
    } else if ($days > 2 && $days <= 8) {
        $period = 'weekly';
    } else if ($days > 8 && $days <= 31) {
        $period = 'monthly';
    } else if ($days > 31 && $days <= 365) {
        $period = 'yearly';
    }
    return $period;
}


/**
 * @return string
 */
function parseRobotstxt()
{
    $disallow = '';
    if (is_file(WEB_ROOT . 'robots.txt')) {
        $cnt = file(WEB_ROOT . 'robots.txt');
        foreach ($cnt as $line) {
            $line = trim($line);
            if (substr($line, 0, 9) == 'Disallow:') {
                $disallow .= trim(substr($line, 9)) . "\n";
            }
        }
    }
    return $disallow;
}

/**
 * Фукцния склоняет слова в соответствии с числовым значением.
 * @param array $words например: array('слон','слона','слонов')
 * @param int $number например: 7
 * @return string вернет "слонов"
 */
function modifWordByCount($words, $number)
{
    if (!is_array($words) || count($words) < 3) {
        return 'ERR<!--Ошибка modifWordByCount(): аргумент $words должен быть массивом из трех ячеек.-->';
    }
    if ($number == 0) {
        $result = $words[2];
    } elseif ($number == 1) {
        $result = $words[0];
    } elseif (($number > 20) && (($number % 10) == 1)) {
        $result = $words[2];
    } elseif ((($number >= 2) && ($number <= 4)) || ((($number % 10) >= 2) && (($number % 10) <= 4)) && ($number > 20)) {
        $result = $words[1];
    } else {
        $result = $words[2];
    }
    return $result;
}


function saveLog($logs = [])
{
    $lines = [];
    if (is_file(__DIR__ . '/cron-log.txt')) {
        $lines = file(__DIR__ . '/cron-log.txt');
    }
    if (count($lines) > 100) {
        for ($i = 0; $i < 20; $i++) {
            unset($lines[$i]);
        }
    }
    foreach ($logs as $line) {
        $lines[] = '<div class="line">' . $line . '</div>' . "\n";
    }
    $log = implode('', $lines);
    file_put_contents('cron-log.txt', $log, FILE_APPEND);
}

