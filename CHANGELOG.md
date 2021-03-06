## 2021-01-14 v.5.6.2
* Исправлено формирование картинок в ICML при включеном CDN
* Убрана некорректная запись внешнего идентификатора платежа для новых платежей по истории
* Добавлена проверка на длину email при отправке в систему

## 2020-12-15 v.5.6.1
* Обновлено наименование бренда

## 2020-11-24 v.5.6.0
* Добавлена возможность активации онлайн-консультанта
* Обновлен список стран
* Улучшена синхронизация ФИО в истории
* Добавлен перевод на английский язык
* Заменены некорректные символы в списке стран

## 2020-10-14 v.5.5.3
* Исправлено затирание полей при создании заказа по агенту

## 2020-10-01 v.5.5.2
* Исправлена ошибка, препятствовавшая обновлению модуля
* Исправлена неверная кодировка в настройках модуля

## 2020-09-28 v.5.5.1
* Исправлена ошибка переноса полей в RetailCRM для заказов с интеграционными доставками

## 2020-09-08 v.5.5.0
* Добавлена возможность ручной выгрузки заказов в CRM

## 2020-09-02 v.5.4.6
* Исправлена ошибка установки модуля при отсутствии заказов в Битрикс

## 2020-09-02 v.5.4.5
* Исправлена ошибка установки статуса оплаты в заказе

## 2020-08-26 v.5.4.4
* Исправлена ошибка при установке модуля

## 2020-08-25 v.5.4.3
* Исправлена ошибка с некорректным ID товара при редактировании заказа
* Исправлены опечатки в API-клиенте RetailCRM
* Добавлена фильтрация изменений истории по текущему API-ключу

## 2020-07-24 v.5.4.2
* Исправлена кодировка

## 2020-07-24 v.5.4.1
* Оптимизирован генератор каталога
* Передача статуса оплаты и статуса отгрузки заказа в Битрикс
* Предупреждение в случае обнаружения несовместимых настроек
* Запрещенные для редактирования поля в заказах с интеграционной доставкой более не передаются при редактировании заказа из Битрикс

## 2020-07-14 v.5.4.0
* Добавлена поддержка функционала смены клиента

## 2020-05-04 v.5.3.2
* Исправлена кодировка в настройках модуля

## 2020-04-27 v.5.3.1
* Добавлена локализация свойств при генерации каталога

## 2020-04-23 v.5.3.0
* Добавлена поддержка корпоративных клиентов

## 2020-01-09 v.5.2.5
* Добавлена передача "externalIds" у позиций товаров в заказе
* Добавлено разделение поля строение/корпус на два отдельных

## 2019-12-20 v.5.2.4
* Добавлена обработка изменения номера заказа по истории

## 2019-11-1 v.2.5.3
* Исправление при обработке полученных изменений о контрагенте
* При обработки истории по клиентам добавлены кастомные поля
* Исправлены мелкие ошибки и недочеты

## 2019-09-17 v.2.5.2
* Поддержка функции добавления одинакового товара в заказ как разные товарные позиции из CRM

## 2019-08-28 v.2.5.1
* Исправление генерации единиц измерения

## 2019-07-26 v.2.5.0
* Исправление создание дублей заказов

## 2019-07-18 v.2.4.9
* Добавлен поиск города по почтовому индексу при выгрузке истории изменений

## 2019-07-01 v.2.4.8
* Исправлена отправка пустого заказа при удалении заказа из СMS
* Изменена логика генерации внешнего идентификатора оплат для сохранения его уникальности в пределах системы

## 2019-03-28 v.2.4.7
* Добавлено удаление в системе типа цены у товара для неактивного типа цены на сайте

## 2019-03-28 v.2.4.6
* Исправление проверки информации о контрагенте при обработке полученных изменений

## 2019-03-28 v.2.4.5
* Обновлен конфигурационный файл для валидатора

## 2019-01-22 v.2.4.4
* Добавлена обработка клиентов с внешним кодом в виде хэша при выгрузке истории изменений

## 2019-01-15 v.2.4.3
* Добавлена выгрузка НДС в ICML каталоге
* Улучшена выгрузка истории изменений заказа
* Улучшена настройка выгрузки типов цен

## 2018-12-26 v.2.4.2
* Добавлена конвертация закупочной цены при выгрузке заказа
* Исправлен файл переводов для выгрузки каталога
* В настройку экспорта каталога добавлена настройка максимального количества торговых предложений у товара
* Исправлен вызов обработчика сохранения оплаты при создании заказа

## 2018-11-02 v.2.4.1
* Исправлены ошибки в файле options.php

## 2018-11-02 v.2.4.0
* Изменена привязка на событие сохранения заказа. Используется привязка к событию "OnSaleOrderSaved"
* Исправлено удаление событий модуля при удалении модуля интеграции из CMS
* Добавлено подключение файла Logger.php при удалении модуля
* Изменен механизм определения протокола, с которым работает сайт. Используется метод isHttps() 1С-Bitrix
* Исправлена передача веса товара при отправке габбаритов заказа

## 2018-10-29 v.2.3.14
* Добавлено подключение файла RCrmActions.php при удалении модуля

## 2018-10-25 v.2.3.13
* Добавлен функционал для активации модуля в маркетплейсе RetailCRM
* Исправлен баг при генерации каталога с подстановкой схемы

## 2018-10-17 v.2.3.12
* Исрпавлена некорректная выгрузка остатков по складам
* Исправлена отправка габаритов товаров в заказах 

## 2018-10-04 v.2.3.11
* Добавлен учет настроек часового пояса при создании заказа
* Устранено удаление событий изменения оплат при переводе выгрузки на агент
* Добавлена возможность указать свойство в настройках экспорта, из которого будет подставляться картинка, если отсутствует в "Подробно" и "Анонс"
* Добавлена подстановка домена в ссылках каталога в зависимости от пренадлежности инфоблока к сайту

## 2018-09-26 v.2.3.10
* Исправлена некорректная генерация скрипта UA
* Исправлена выгрузка остатков, если для товара указано более 50 складов
* Добавлен перехват исключений при сохранении заказа в Битрикс

## 2018-08-08 v.2.3.9
* Устранено резервирование товара в отмененном заказе
* Исправлен некорректный расчет скидки на товар

## 2018-07-16 v.2.3.8
* Добавлен рассчет стоимости товара с учетом наценки
* Добавлена выгрузка картинок товара, если отсутствует картинка торгового предложения
* Заменены устаревшие методы в API клиенте

## 2018-06-13 v.2.3.7
* Добавлена выгрузка штрихкодов в ICML
* Добавлена выгрузка картинок торговых предложений в ICML
* Улучшена передача типа доставки в заказе
* Добавлена проверка некоторых настроек при передаче заказа

## 2018-05-23 v.2.3.6
* Улучшена выгрузка свойств товаров типа "справочник"
* Добавлена настройка выгрузки габаритов и веса в заказе
* Добавлена совместимость натройки экспорта для Google Chrome
* Исправлена ошибка при выгрузке истории с пустым городом
* Добавлены проверки на существование модуля Highloadblock
* Исправлен баг с отправкой пустого заказа при удалении в 1С-Битрикс

## 2018-03-22 v.2.3.5
* В настройку экспорта добавлена настройка свойств типа "справочник"(highloadblock)
* Добавлена проверка необходимости резервации товаров при выгрузке заказов из RetailCRM
* Исправлен вызов рекурсивного метода в RCrmActions
* Добавлены недостающие поля retailcrm.json

## 2018-02-27 v.2.3.4
* Добавлена передача веса и габаритов в заказе
* Добавлена проверка существования fuser у корзины товаров перед сохранением
* Добавлено снятие резерва с товаров при отмене заказа в CRM
* Исправлена выборка данных для UA, когда id заказа не совпадает с номером
* Исправлены мелкие баги

## 2018-01-23 v.2.3.3
* Исправлен баг с передачей номера заказа
* Исправлены мелкие ошибки и недочеты

## 2017-12-27 v.2.3.2
* Исправлен баг с рассчетом суммы заказа
* Добавлен перехват исключения при редактировании отгруженной доставки

## 2017-12-27 v.2.3.1
* Исправлены мелкие баги и недочеты

## 2017-12-04 v.2.3.0
* Добавлен выбор валюты в настройках, для выгрузки заказов из CRM
* Исправлена выборка свойств заказа
* Устранен баг в настройках соответствия полей свойств заказа
* Улучшена механика выгрузки заказов из CRM

## 2017-11-20 v.2.2.10
* Устранен баг с созданием чеков
* Улучшен механизм работы с оплатами

## 2017-11-13 v.2.2.9
* Исправлены ошибки подключения кастомных классов
* Улучшена обработка истории

## 2017-11-01 v.2.2.8
* Исправлены баги пакетной выгрузки
* Исправлена ошибка при работе на php7.1

## 2017-10-26 v.2.2.7
* Исправлен баг при работе с одним сайтом

## 2017-10-25 v.2.2.6
* Доработана система синхронизации оплат
* Исправлены ошибки в истории заказов

## 2017-09-22 v.2.2.5
* Теперь учитываются группы доставки
* Изменен алгоритм передачи оплат
* Исправлено задваивание количества товаров в отгрузке
* Небольшие исправления

## 2017-09-07 v.2.2.4
* Исправлена работа истории пользователей
* Убраны события для старого API

## 2017-09-04 v.2.2.3
* Исправлена работа истории

## 2017-09-04 v.2.2.2
* Исправлен инсталлятор
* Изменена передача данных по пользователю

## 2017-09-01 v.2.2.1
* Добавлена встроенная функция retailCrmApiResult	
* Добавлен триггерный вариант истории изменений
* Исправлены ошибки

## 2017-08-21 v.2.2.0
* API V5
* Возможность выбора версии API
* Добавлена возможность выгрузки остатков в разрезе складов
* Добавлена возможность выгрузки типов цен
* Добавлена базовая интеграция Daemon Collector
* Добавлена интеграция с Universal Analytics
* Доработана логика работы встроенных функций для модификации данных
* Исправлены ошибки

## 2016-12-09 v.2.1.2
* Добавлены единицы измерения в экспорте каталога
* Исправлены пути в include
* Добавлено время нового формата в валидатор
* Исправлено неверное изменение типа заказа по истории
* Исправлена ошибка с некорректным разбиением ФИО
* Небольшие исправления по коду
## 2016-11-15 v.2.1.1
* Исправлена проблема с отсутствием в настройках доставок
* Небольшие исправления

## 2016-10-31 v.2.1.0
* Добавлена передача адреса из карточки клиента в RetailCRM
* Добавлено больше информации в журнале битрикса и дополнительное логирование
* Небольшие исправления

## 2016-10-25 v.2.0.9
* Исправлена ошибка с неверной кодировкой ФИО
* Исправлена ошибка с отсутствием местоположения

## 2016-10-20 v.2.0.8
* Исправлена ошибка с отсутствием LID
* Изменены методы для совместимости с ранними версиями sale 16 версии

## 2016-10-20 v.2.0.7
* Исправлена ошибка с недобавлением товара в заказ по истории
* Исправлена ошибка с недобавлением сервиса доставки в црм

## 2016-10-14 v.2.0.6
* Оптимизация History
* Исправлены ошибки

## 2016-10-11 v.2.0.5
* Исправлена ошибка при обработке Ф.И.О.
* Исправлена ошибка с неверной кодировкой свойств
* Исправлена ошибка формирования списка статусов

## 2016-10-06 v.2.0.4
* Оптимизация History
* Исправлена ошибка выгрузки доставок при установке

## 2016-10-04 v.2.0.3
* fix состава отгрузки

## 2016-10-04 v.2.0.2
* Исправлены ошибки

## 2016-10-03 v.2.0.1
* Исправлены ошибки

## 2016-09-12 v.2.0.0
* API V4
* Переход на ядро d7
* История изменений по клиентам
* Множественный флаг отмены заказа

## 2015-11-09 v.1.1.3
* Добавлено логгирование в файл для приходящей из црм и уходящей в црм информации
* Изменен механизм добавления товара в заказ
* Возможность добавить товар в заказ, который есть в црм, но нет на сайте
* Изменил логику выбора товаров для выгрузки в xml
* Появилась возможность перевести History на триггер
* Исправлены ошибки

## 2015-05-18 v.1.1.2
* Добавлена возможность изменять файлы основных классов(ICMLLoader и ICrmOrderActions) и экспорт каталога без потери обновлений
* Исправлены мелкие ошибки

## 2015-03-19 v.1.1.1
* Исправлена ошибка, связанная с версионностью PHP в History.
* Добавлена выгрузка закупочной цены вместе с заказом
* Добавлены индивидуальные настройки для профилей выгрузки
* Исправлены мелкие ошибки

## 2015-02-20 v.1.1.0
* Модуль переведен на новую версию API
* Добавлена поддержка реквизитов юр. лиц
* Добавлена многосайтовость
* Добавлена выборочная загрузка заказов из настроек модуля
* Оптимизирована загрузка старых заказов
* Исправлена ошибка с удалением id товара в заказе
* Исправлена ошибка пустого $_SERVER['SERVER_NAME'] при экспорте каталога
* Исправлена ошибка с неправильной скидкой у товара при наличии копеек
* Исправлена ошибка с пропаданием автоматических служб доставок из настроек модуля
* Исправлена неправильная выгрузка сервисов для служб доставок
* Исправлено не правильное определение местоположения
* Рефакторинг модуля

## 2015-02-13 v.1.0.16
* Все действия агента происходят от имени retailcrm

## 2015-02-12 v.1.0.16
* Исправлен агент
 * Исправлены ошибки с запоминанием пользователя
 * Исправлена ошибка с выходом пользователя из системы
 * Исправлена ошибка хождения пользователя под другим логином
* Исправлены проблема с fix-external-ids
* Добавлена возможность получения скидки из CRM в Битрикс
