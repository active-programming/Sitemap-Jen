/**
 * Sitemap Jen
 * @author Konstantin@Kutsevalov.name
 */

jQuery(function ($) {

    var smjen = {
        "$log": null,
        url: 'components/com_sitemapjen/process.php?mode=ajax',
        sessionUrl: 'index.php?option=com_sitemapjen&task=update_session',
        threads: {
            // 1: {
            //  id:1, - id записи в базе
            //  loc:'http://....', - сканируемый адрес
            //  xhr: null, - объект запроса
            //  time:4, - сколько секунд висит запрос?
            //  status: false - статус запроса }
        },
        thrMax: 1,
        dotsTimer: null,
        isStopped: false,
        getLinksCounter: 0,
        endScan: false,
        sessionTimer: null,

        // пингует сервер, чтобы сессия не обрывалась
        "updateSession": function() {
            jQuery.ajax({
                url: smjen.sessionUrl,
                type: "POST",
                data: {
                    "query": "ping"
                }
            });
        },

        // инициализация сканера
        "start": function() {
            $(".jen-start, .jenForm input[name=jhref]").prop("disabled", true);
            $(".jen-stop").prop("disabled", false);
            $(".jen-scan-modes").css("display", "none");
            $(".jenToolbar .info").html('<p class="alert">При закрыти данного окна, операция' +
                ' будет продолжаться только при наличии задачи в планировщике (cron)</p>');
            var toContinue = $("#mode1").prop("checked"),
                noScan = $("#mode2").prop("checked");
            smjen.isStopped = false;
            if (noScan) {
                // без сканирования, только генерация
                smjen.log("<i>Запуск</i>", '', {clear: true, dots: true});
                smjen.doQuery({ action: "init", param: "no_scan" });
            } else if (toContinue) {
                // продолжить незавершенную операцию
                smjen.log("<i>Продолжаем операцию</i>", '', {clear: true, dots: true});
                smjen.doQuery({ action: "init", param: "continue" });
            } else {
                // новое сканирование
                smjen.log("<i>Запуск</i>", '', {clear: true, dots: true});
                smjen.doQuery({ action: "init", param: "full", url: $("input[name=jhref]").val() });
            }
            if (smjen.sessionTimer) {
                clearInterval(smjen.sessionTimer);
            }
            smjen.updateSession();
            smjen.sessionTimer = setInterval(smjen.updateSession, 10000);
            smjen.logDots();
        },


        "stop": function() {
            smjen.isStopped = true;
            smjen.doQuery({ action: "stop" });
            $(".jenToolbar .info").html('<p class="alert">Остановка</p>');
            $(".jen-stop").prop("disabled", true);
            if (smjen.sessionTimer) {
                clearInterval(smjen.sessionTimer);
            }
        },

        "doQuery": function(datas) {
            datas = datas || {};
            var xhr = $.ajax({
                url: smjen.url,
                type: "POST",
                dataType: "json",
                data: datas,
                reqaction: datas.action ? datas.action : '',
                thr: (datas.loc_id) ? datas.loc_id : 0
            }).done(function (resp) {
                smjen.processManager(this, this.thr, resp);
            }).fail(function (rxhr, textStatus) {
                smjen.processManager(rxhr, this.thr, { action: this.reqaction, error: 500});
            });
            return xhr;
        },

        // организует работу ajax-запросов и анализирует ответы
        "processManager": function(xhr, thr, data) {
            var param, url, i;
            // проверяем тип ответа и принимаем соответствующие меры
            // { 'action': 'текущая операция',
            // 'logs': 'строка для окна логов',
            // 'thr': 1, идентификатор запроса, вернувшего ответ
            // 'urls': [ адреса, которые будут сканироваться на текущем этапе ],
            // 'threadsCount': количество допустимых потоков,
            // 'error': номер ошибки }
            console.log(thr, data);
            if (data == null) { // если пустой ответ
                if (smjen.threads[ thr ]) { // удаляем поток
                    smjen.log(". <i>Непредвиденная ошибка: пустой ответ</i>", thr, { append: true });
                    delete smjen.threads[ thr ]; // удаляем информацию об отработаном потоке
                }
                return;
            }
            if (data.logs && data.logs.length > 0) {
                for (i = 0; i < data.logs.length; i++) {
                    smjen.log(data.logs[i]);
                }
            }
            if (data.error > 0) {
                switch (data.error) {
                    case 10:
                        smjen.log("<i>Ошибка запроса: неизвестная команда</i>"); break;
                    case 300:
                        smjen.log("<i>Ошибка запроса: некорректный ajax</i>"); break;
                    case 110: // Ошибка инициализации: не найдены адреса для сканирования.
                        smjen.stop(); break;
                }
            }
            if (data.action) {
                if (data.action == "scan_init") {
                    // сканировение сайта (инициализация)
                    if (smjen.threads[ thr ]) {
                        delete smjen.threads[ thr ]; // удаляем информацию об отработаном потоке
                    }
                    smjen.thrMax = data.threadsCount; // запоминаем максимальное кол-во потоков
                    // создаем запросы
                    var osize = smjen.getLen(data.urls);
                    var cnt = 0;
                    if (osize > 0) {
                        for (i in data.urls) {
                            if (!$.isFunction(data.urls[i]) && data.urls.hasOwnProperty(i) && $.isPlainObject(data.urls[i])) {
                                url = data.urls[i];
                                if (cnt < smjen.thrMax) { // не создаем потоков больше чем установленный предел
                                    smjen.log("scan -> " + url.loc + "...", url.id);
                                    param = { action: "scan", loc_id: url.id };
                                    if (osize < smjen.thrMax) { // если адресов меньше чем потоков
                                        // запросим дополнительные адреса для сканирования
                                        param.need = smjen.thrMax - osize;
                                    }
                                    var xhr = smjen.doQuery(param);
                                    smjen.threads[ url.id ] = { 'id': url.id, 'loc': url.loc, 'status': true, xhr: xhr, time: ( (new Date().getTime()) / 1000 ) };
                                } else {
                                    // остальные адреса помещаем в буффер
                                    smjen.threads[ url.id ] = { 'id': url.id, 'loc': url.loc, 'status': false, xhr: null, time: 0 };
                                }
                                cnt++;
                            }
                        }
                    } else {
                        smjen.log("<i>Ошибка инициализации :(</i>");
                    }
                    return;
                } else if (data.action == "scan") {
                    var nowsec = ( new Date().getTime() ) / 1000;
                    // ответ при сканировании сайта
                    switch (data.error) {
                        case 120:
                            // текущая ссылка не была найдена
                            // в прочем, это не обязательно ошибка, есть запросы не передающие номер записи...
                            break;
                        case 200:
                            // доступных адресов не найдено
                            if (smjen.getLen(smjen.threads) <= 1 && thr > 0) {
                                // больше нет адресов для сканирования, значит пора переходить к генерации sitemap
                                smjen.log("<i>Сканирование завершено. Запуск генератора...</i>");
                                smjen.doQuery({ action: "init", param: "no_scan" });
                                smjen.endScan = true; // флаг окончания сканирования
                            }
                            if (smjen.getLen(smjen.threads) == 0 && thr == 0 && data.urls.length == 0) {
                                // был запрос новых адресов, но увы
                                if (!smjen.endScan) {
                                    smjen.endScan = true;
                                    smjen.log("<i>Сканирование завершено. Запуск генератора...</i>");
                                    smjen.doQuery({ action: "init", param: "no_scan" });
                                }
                                smjen.endScan = true;
                            }
                            break;
                        case 500:
                            // запрос не удался, перезапустим его
                            if (thr && smjen.threads[thr]) {
                                smjen.log(".<i>обрыв связи, повтор...</i>", thr, { append: true, rid: true });
                                smjen.threads[thr].time = nowsec;
                                smjen.threads[thr].status = false;
                                smjen.threads[thr].xhr = null;
                            }
                            break;
                        case 510:
                            // Ошибка, не удалось загрузить страницу
                            smjen.log(".<i>не удалось загрузить страницу.</i>", thr, { append: true, rid: true });
                            break;
                    }
                    if (smjen.threads[ thr ]) { // удаляем информацию об отработаном потоке
                        smjen.log(".<b>+ " + data.newcount + " url</b>", thr, { append: true });
                        delete smjen.threads[ thr ];
                    }
                    // если команда остановки
                    if (smjen.isStopped == true || smjen.endScan == true) {
                        return;
                    }
                    // добавляем в задачи полученные адреса
                    for (i in data.urls) {
                        if (!$.isFunction(data.urls[i]) && data.urls.hasOwnProperty(i) && $.isPlainObject(data.urls[i])) {
                            if (typeof(data.urls[i].id) != 'undefined') {
                                smjen.threads[ i ] = { 'id': data.urls[i].id, 'loc': data.urls[i].loc, 'status': false, xhr: null, time: 0 };
                            }
                        }
                    }
                    // ищем повисшие запросы
                    for (i in smjen.threads) {
                        if (!$.isFunction(smjen.threads[i]) && smjen.threads.hasOwnProperty(i) && $.isPlainObject(smjen.threads[i])) {
                            if (smjen.threads[i].status == true) {
                                if (nowsec - smjen.threads[i].time > 50) {
                                    // скорее всего повисший запрос, удаляем его из логов и запускаем заново
                                    smjen.log("<i>timeout, повтор...</i>", i, { append: true, rid: true });
                                    smjen.threads[i].time = 0;
                                    smjen.threads[i].status = false;
                                    smjen.threads[i].xhr.abort();
                                }
                            }
                        }
                    }
                    // проверяем сколько сейчас активных потоков и пытаемся запустить недостающее количество
                    var countAct = smjen.getLen(smjen.threads, true),
                        count = smjen.getLen(smjen.threads),
                        need = 1;
                    // console.log( countAct );
                    // console.log( count );
                    if (countAct < smjen.thrMax) {
                        // создаем новые потоки
                        countAct = smjen.thrMax - countAct; // разница между лимитом и текущим кол-вом активных
                        for (i in smjen.threads) {
                            if (!$.isFunction(smjen.threads[i]) && smjen.threads.hasOwnProperty(i) && $.isPlainObject(smjen.threads[i])) {
                                if (countAct < 1) {
                                    break;
                                } // создаем пока не достигнем лимита
                                if (smjen.threads[i].status === false) {
                                    smjen.log("scan -> " + smjen.threads[i].loc + ".", smjen.threads[i].id);
                                    smjen.threads[i].status = true;
                                    smjen.threads[i].time = ( new Date().getTime() ) / 1000; // время запуска в секундах
                                    if (count > smjen.thrMax) { // если общее количество адресов в буфере больше лимита, то новых адресов не запрашиваем
                                        need = 0;
                                    }
                                    smjen.threads[i].xhr = smjen.doQuery({ action: "scan", loc_id: smjen.threads[i].id, need: need });
                                    countAct--;
                                }
                            }
                        }
                        if (countAct > 0) {
                            // создано недостаточно потоков, нужны новые адреса
                            smjen.doQuery({ action: "scan", need: countAct }); // запрашиваем доп адреса
                        }
                    }
                    return;
                } else if (data.action == "generate") {
                    // генерация sitemap
                    smjen.doQuery({ action: "generate" });
                } else if (data.action == "stop" || data.action == "end") {
                    // остановка
                    smjen.isStopped = true;
                    // возможно стоило бы проверять количество незавершенных запросов....?
                    for (i in smjen.threads) {
                        if (!$.isFunction(smjen.threads[i]) && smjen.threads.hasOwnProperty(i) && $.isPlainObject(smjen.threads[i])) {
                            delete smjen.threads[i];
                        }
                    }
                    smjen.logDots(smjen.isStopped);
                    if (data.action == "stop") {
                        smjen.log("<i>Операция остановлена.</i>");
                    } else {
                        smjen.log("<i>Операция завершена.</i>");
                    }
                    $(".jenToolbar .info").html('');
                    $(".jen-scan-modes").css("display", "block");
                    $(".jen-start, .jen-scan-modes input, .jenForm input[name=jhref]").prop("disabled", false);
                }
            }
        },

        "getLen": function(obj, val) {
            val = val || false;
            var size = 0, key;
            for (key in obj) {
                if (!$.isFunction(obj[key]) && obj.hasOwnProperty(key) && $.isPlainObject(obj[key])) {
                    if (typeof(obj[key].id) != 'undefined') {
                        if ((val !== false && val == obj[key].status) || val === false) {
                            size++;
                        }
                    }
                }
            }
            return size;
        },

        // управляет сообщениями в окне логов
        "log": function(text, id, param) {
            var scroll = false, divs, process = "";
            id = id || "";
            if (id != "") {
                id = "line-" + id;
            }
            text = text || "";
            param = param || {};
            if (param.clear) {
                $(smjen.$log).html('');
            }
            // проверяем нужна ли автоматическая прокрутка
            if ((smjen.$log.scrollTop() + smjen.$log.height()) > (smjen.$log[0].scrollHeight - 20)) {
                scroll = smjen.$log[0].scrollHeight + 3;
            }
            // проверяем количество строк в окне
            divs = $(".log .line");
            if (divs.length > 100) {
                // удаляем первых 10 строк
                for (var i = 0; i < 10; i++) {
                    $(divs[i]).remove();
                }
            }
            if (param.append) {
                smjen.$log.find("#" + id).append(text);
            } else {
                if (param.dots) {
                    process = " process";
                }
                smjen.$log.append('<div class="line' + process + '" id="' + id + '">' + text + '</div>');
            }
            if (param.rid) { // удалить идентификатор
                smjen.$log.find("#" + id).removeAttr("id");
            }
            $(".log .line.process").removeClass("process");
            if (scroll) {
                smjen.$log.scrollTop(scroll);
            }
        },


        "logDots": function(stop) {
            stop = stop || false;
            if (stop) {
                clearInterval(smjen.dotsTimer);
            } else {
                smjen.dotsTimer = setInterval(function () {
                    // активные процессы
                    for (var id in smjen.threads) {
                        if (smjen.threads[ id ].status == true) {
                            $("#line-" + id).append(".");
                        }
                    }
                    // спец запросы
                    $(".log .line.process").append(".");
                }, 1000);
            }
        }

    };


    // init
    smjen.$log = $(".jenLog");
    $(".jen-start").click(smjen.start);
    $(".jen-stop").click(smjen.stop);
    if ($("#jen_in_work").val() == '1') {
        // есть текущая операция, продолжим ее
        smjen.start();
    }

    var reSubminBtnTm = setInterval(function () {
        if (!window.Joomla) return; // wait joomla init
        // переопределяем joom функцию
        Joomla.submitbutton = function (pressbutton) {
            if (pressbutton == 'clear_links') {
                if (!confirm("Удалить все ссылки из базы?")) {
                    return;
                }
            }
            document.adminForm.task.value = pressbutton;
            Joomla.submitform(pressbutton);
        }
        clearInterval(reSubminBtnTm);
    }, 50);

});