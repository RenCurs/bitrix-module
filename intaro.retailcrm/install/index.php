<?php

/**
 * Module Install/Uninstall script
 * Module name: intaro.retailcrm
 * Class name:  intaro_retailcrm
 */
global $MESS;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Internals\OrderPropsGroupTable;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\sale\EventActions;
use Bitrix\Sale\Internals\OrderPropsGroupTable;
use Bitrix\Sale\Internals\OrderTable;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Intaro\RetailCrm\Component\Loyalty\EventsHandlers;
use Intaro\RetailCrm\Repository\OrderPropsRepository;
use Intaro\RetailCrm\Repository\PersonTypeRepository;
use Intaro\RetailCrm\Repository\ToModuleRepository;
use RetailCrm\ApiClient;
use RetailCrm\Exception\CurlException;

IncludeModuleLangFile(__FILE__);
if (class_exists('intaro_retailcrm')) {
    return false;
}

class intaro_retailcrm extends CModule
{
    public const LP_ORDER_GROUP_NAME = 'Программа лояльности';
    public const BONUS_COUNT = 'Количество бонусов';
    public const BONUS_PAY_SYSTEM_NAME = 'Оплата бонусами';
    public const BONUS_PAY_SYSTEM_CODE = 'retailcrmbonus';
    public const BONUS_PAY_SYSTEM_DESCRIPTION = 'Оплата бонусами программы лояльности retailCRM';

    /**
     * @var string[][]
     */
    private const SUBSCRIBE_LP_EVENTS = [
        ['EVENT_NAME' => 'OnBeforeSalePaymentSetField', 'FROM_MODULE' => 'sale'],
        ['EVENT_NAME' => 'OnBeforeEndBufferContent', 'FROM_MODULE' => 'main'],
        ['EVENT_NAME' => 'OnSaleOrderBeforeSaved', 'FROM_MODULE' => 'sale'],
        ['EVENT_NAME' => 'OnSaleOrderPaid', 'FROM_MODULE' => 'sale'],
        ['EVENT_NAME' => 'OnSaleStatusOrderChange', 'FROM_MODULE' => 'sale'],
        ['EVENT_NAME' => 'OnSaleOrderSaved', 'FROM_MODULE' => 'sale'],
        ['EVENT_NAME' => 'OnSaleOrderCanceled', 'FROM_MODULE' => 'sale'],
        ['EVENT_NAME' => 'OnSaleOrderDeleted', 'FROM_MODULE' => 'sale'],
        ['EVENT_NAME' => 'OnSaleComponentOrderOneStepProcess', 'FROM_MODULE' => 'sale'],
    ];

    public const V5 = 'v5';
    public $MODULE_ID = 'intaro.retailcrm';
    public $OLD_MODULE_ID = 'intaro.intarocrm';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS = 'N';

    public $PARTNER_NAME;
    public $PARTNER_URI;

    public $RETAIL_CRM_API;
    public $RETAIL_CRM_EXPORT = 'retailcrm';
    public $CRM_API_HOST_OPTION = 'api_host';
    public $CRM_API_KEY_OPTION = 'api_key';
    public $CRM_SITES_LIST = 'sites_list';
    public $CRM_ORDER_TYPES_ARR = 'order_types_arr';
    public $CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    public $CRM_DELIVERY_SERVICES_ARR = 'deliv_services_arr';
    public $CRM_PAYMENT_TYPES = 'pay_types_arr';
    public $CRM_PAYMENT_STATUSES = 'pay_statuses_arr';
    public $CRM_PAYMENT = 'payment_arr'; //order payment Y/N
    public $CRM_ORDER_LAST_ID = 'order_last_id';
    public $CRM_ORDER_PROPS = 'order_props';
    public $CRM_LEGAL_DETAILS = 'legal_details';
    public $CRM_CUSTOM_FIELDS = 'custom_fields';
    public $CRM_CONTRAGENT_TYPE = 'contragent_type';
    public $CRM_ORDER_DISCHARGE = 'order_discharge';
    public $CRM_ORDER_FAILED_IDS = 'order_failed_ids';
    public $CRM_ORDER_HISTORY = 'order_history';
    public $CRM_CUSTOMER_HISTORY = 'customer_history';
    public $CRM_CATALOG_BASE_PRICE = 'catalog_base_price';
    public $CRM_ORDER_NUMBERS = 'order_numbers';
    public $CRM_CANSEL_ORDER = 'cansel_order';
    public $CRM_CURRENCY = 'currency';
    public $CRM_ADDRESS_OPTIONS = 'address_options';

    public $CRM_INVENTORIES_UPLOAD = 'inventories_upload';
    public $CRM_STORES = 'stores';
    public $CRM_SHOPS = 'shops';
    public $CRM_IBLOCKS_INVENTORIES = 'iblocks_inventories';

    public $CRM_PRICES_UPLOAD = 'prices_upload';
    public $CRM_PRICES = 'prices';
    public $CRM_PRICE_SHOPS = 'price_shops';
    public $CRM_IBLOCKS_PRICES = 'iblock_prices';

    public $CRM_COLLECTOR = 'collector';
    public $CRM_COLL_KEY = 'coll_key';

    public $CRM_UA = 'ua';
    public $CRM_UA_KEYS = 'ua_keys';

    public $CRM_API_VERSION = 'api_version';
    public $HISTORY_TIME = 'history_time';

    public $CLIENT_ID = 'client_id';
    public $PROTOCOL = 'protocol';

    public $INSTALL_PATH;

    function intaro_retailcrm()
    {
        $arModuleVersion    = [];
        $path               = str_replace("\\", "/", __FILE__);
        $path               = substr($path, 0, strlen($path) - strlen("/index.php"));
        $this->INSTALL_PATH = $path;
        include($path . "/version.php");
        $this->MODULE_VERSION      = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME         = GetMessage('RETAIL_MODULE_NAME');
        $this->MODULE_DESCRIPTION  = GetMessage('MODULE_DESCRIPTION');
        $this->PARTNER_NAME        = GetMessage('MODULE_PARTNER_NAME');
        $this->PARTNER_URI         = GetMessage('MODULE_PARTNER_URI');
    }

    function loadDeps()
    {
        if (!class_exists('RetailcrmConstants')) {
            require_once dirname(__FILE__) . '/../classes/general/RetailcrmConstants.php';
        }
        if (!class_exists('RetailcrmConfigProvider')) {
            require_once dirname(__FILE__) . '/../classes/general/RetailcrmConfigProvider.php';
        }

        if (!class_exists('RetailcrmDependencyLoader')) {
            require_once dirname(__FILE__) . '/../classes/general/RetailcrmDependencyLoader.php';
        }
    }

    /**
     * Functions DoInstall and DoUninstall are
     * All other functions are optional
     */
    function DoInstall()
    {
        global $APPLICATION, $step, $arResult;

        if (!in_array('curl', get_loaded_extensions(), true)) {
            $APPLICATION->ThrowException(GetMessage("RETAILCRM_CURL_ERR"));
            return false;
        }

        $infoSale = CModule::CreateModuleObject('sale')->MODULE_VERSION;
        if (version_compare($infoSale, '16', '<=')) {
            $APPLICATION->ThrowException(GetMessage("SALE_VERSION_ERR"));

            return false;
        }

        if (!Loader::includeModule('sale')) {
            return false;
        }

        if (!date_default_timezone_get() && !ini_get('date.timezone')) {
            $APPLICATION->ThrowException(GetMessage("DATE_TIMEZONE_ERR"));

            return false;
        }

        include($this->INSTALL_PATH . '/../classes/general/Http/Client.php');
        include($this->INSTALL_PATH . '/../classes/general/Response/ApiResponse.php');
        include($this->INSTALL_PATH . '/../classes/general/RCrmActions.php');
        include($this->INSTALL_PATH . '/../classes/general/user/RetailCrmUser.php');
        include($this->INSTALL_PATH . '/../classes/general/events/RetailCrmEvent.php');
        include($this->INSTALL_PATH . '/../classes/general/icml/RetailCrmICML.php');
        include($this->INSTALL_PATH . '/../classes/general/Exception/InvalidJsonException.php');
        include($this->INSTALL_PATH . '/../classes/general/Exception/CurlException.php');
        include($this->INSTALL_PATH . '/../classes/general/RestNormalizer.php');
        include($this->INSTALL_PATH . '/../classes/general/Logger.php');
        include($this->INSTALL_PATH . '/../classes/general/services/RetailCrmService.php');

        $version = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_VERSION, 0);
        include($this->INSTALL_PATH . '/../classes/general/ApiClient_v5.php');
        include($this->INSTALL_PATH . '/../classes/general/order/RetailCrmOrder_v5.php');
        include($this->INSTALL_PATH . '/../classes/general/history/RetailCrmHistory_v5.php');

        $step = intval($_REQUEST['step']);

        if (file_exists($this->INSTALL_PATH . '/../classes/general/config/options.xml')) {
            $options = simplexml_load_file($this->INSTALL_PATH . '/../classes/general/config/options.xml');

            foreach ($options->contragents->contragent as $contragent) {
                $type["NAME"]                 = $APPLICATION->ConvertCharset((string)$contragent, 'utf-8', SITE_CHARSET);
                $type["ID"]                   = (string)$contragent["id"];
                $arResult['contragentType'][] = $type;
                unset ($type);
            }
            foreach ($options->fields->field as $field) {
                $type["NAME"] = $APPLICATION->ConvertCharset((string)$field, 'utf-8', SITE_CHARSET);
                $type["ID"]   = (string)$field["id"];

                if ($field["group"] == 'custom') {
                    $arResult['customFields'][] = $type;
                } elseif (!$field["group"]) {
                    $arResult['orderProps'][] = $type;
                } else {
                    $groups = explode(",", (string)$field["group"]);
                    foreach ($groups as $group) {
                        $type["GROUP"][] = trim($group);
                    }
                    $arResult['legalDetails'][] = $type;
                }
                unset($type);
            }
        }

        include($this->INSTALL_PATH . '/../lib/model/bitrix/abstractmodelproxy.php');
        include($this->INSTALL_PATH . '/../lib/model/bitrix/orderprops.php');
        include($this->INSTALL_PATH . '/../lib/model/bitrix/tomodule.php');
        include($this->INSTALL_PATH . '/../lib/repository/abstractrepository.php');
        include($this->INSTALL_PATH . '/../lib/repository/orderpropsrepository.php');
        include($this->INSTALL_PATH . '/../lib/repository/persontyperepository.php');
        include($this->INSTALL_PATH . '/../lib/repository/tomodulerepository.php');
        include($this->INSTALL_PATH . '/../lib/model/bitrix/orm/tomodule.php');

        $this->CopyFiles();
        $this->addBonusPaySystem();
        $this->addLPUserFields();
        $this->addLPEvents();

        try {
            $this->addLPOrderProps();
        } catch (ObjectPropertyException | ArgumentException | SystemException $e) {
            return false;
        }

        if ($step == 11) {
            $arResult['arSites'] = RCrmActions::SitesList();
            if (count($arResult['arSites']) < 2) {
                $step = 2;
            }
        }
        if ($step <= 1) {
            if (!CModule::IncludeModule("sale")) {
                $arResult['errCode'] = 'ERR_SALE';
            }

            if (!CModule::IncludeModule("iblock")) {
                $arResult['errCode'] = 'ERR_IBLOCK';
            }

            if (!CModule::IncludeModule("catalog")) {
                $arResult['errCode'] = 'ERR_CATALOG';
            }

            if ($api_host = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_API_HOST_OPTION, 0)) {
                $arResult['API_HOST'] = $api_host;
            }
            if ($api_key = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_API_KEY_OPTION, 0)) {
                $arResult['API_KEY'] = $api_key;
            }

            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step1.php'
            );
        } elseif ($step == 11) {
            //new page
            if (!CModule::IncludeModule("sale")) {
                $arResult['errCode'] = 'ERR_SALE';
            }

            if (!CModule::IncludeModule("iblock")) {
                $arResult['errCode'] = 'ERR_IBLOCK';
            }

            if (!CModule::IncludeModule("catalog")) {
                $arResult['errCode'] = 'ERR_CATALOG';
            }

            if (isset($arResult['errCode']) && $arResult['errCode']) {
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step1.php'
                );

                return false;
            }

            $api_host = htmlspecialchars(trim($_POST[$this->CRM_API_HOST_OPTION]));
            $api_key  = htmlspecialchars(trim($_POST[$this->CRM_API_KEY_OPTION]));

            // form correct url
            $api_host = parse_url($api_host);
            if ($api_host['scheme'] !== 'https') {
                $api_host['scheme'] = 'https';
            }
            $api_host = $api_host['scheme'] . '://' . $api_host['host'];

            if (!$api_host || !$api_key) {
                $arResult['errCode'] = 'ERR_FIELDS_API_HOST';
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step1.php'
                );

                return false;
            }

            $ping = $this->ping($api_host, $api_key);
            if (isset($ping['sitesList'])) {
                $arResult['sitesList'] = $ping['sitesList'];
            } elseif (isset($ping['errCode'])) {
                $arResult['errCode'] = $ping['errCode'];
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step1.php'
                );

                return false;
            }

            COption::SetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, $api_host);
            COption::SetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, $api_key);

            if ($sites_list = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_SITES_LIST, 0)) {
                $arResult['SITES_LIST'] = unserialize($sites_list);
            }

            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step11.php'
            );
        } elseif ($step == 2) {
            if (!CModule::IncludeModule("sale")) {
                $arResult['errCode'] = 'ERR_SALE';
            }
            if (!CModule::IncludeModule("iblock")) {
                $arResult['errCode'] = 'ERR_IBLOCK';
            }
            if (!CModule::IncludeModule("catalog")) {
                $arResult['errCode'] = 'ERR_CATALOG';
            }

            if (isset($arResult['errCode']) && $arResult['errCode']) {
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step1.php'
                );

                return false;
            }

            $arResult['arSites'] = RCrmActions::SitesList();

            if (count($arResult['arSites']) > 1) {

                $api_host = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, 0);
                $api_key  = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, 0);

                foreach ($arResult['arSites'] as $site) {
                    if ($_POST['sites-id-' . $site['LID']] && !empty($_POST['sites-id-' . $site['LID']])) {
                        $siteCode[$site['LID']] = htmlspecialchars(trim($_POST['sites-id-' . $site['LID']]));
                    } else {
                        $siteCode[$site['LID']] = null;
                    }
                }
                if (count($arResult['arSites']) != count($siteCode)) {
                    $arResult['errCode'] = 'ERR_FIELDS_API_HOST';
                    $APPLICATION->IncludeAdminFile(
                        GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step11.php'
                    );

                    return false;
                }

                $this->RETAIL_CRM_API = new ApiClient($api_host, $api_key);
                COption::SetOptionString($this->MODULE_ID, $this->CRM_SITES_LIST, serialize($siteCode));
            } else {
                $api_host = htmlspecialchars(trim($_POST[$this->CRM_API_HOST_OPTION]));
                $api_key  = htmlspecialchars(trim($_POST[$this->CRM_API_KEY_OPTION]));

                // form correct url
                $api_host = parse_url($api_host);
                if ($api_host['scheme'] != 'https') {
                    $api_host['scheme'] = 'https';
                }
                $api_host = $api_host['scheme'] . '://' . $api_host['host'];

                if (!$api_host || !$api_key) {
                    $arResult['errCode'] = 'ERR_FIELDS_API_HOST';
                    $APPLICATION->IncludeAdminFile(
                        GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step1.php'
                    );

                    return false;
                }

                $ping = $this->ping($api_host, $api_key);
                if (isset($ping['sitesList'])) {
                    $arResult['sitesList'] = $ping['sitesList'];
                } elseif (isset($ping['errCode'])) {
                    $arResult['errCode'] = $ping['errCode'];
                    $APPLICATION->IncludeAdminFile(
                        GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step1.php'
                    );

                    return false;
                }

                $this->RETAIL_CRM_API = new ApiClient($api_host, $api_key);
                COption::SetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, $api_host);
                COption::SetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, $api_key);
                COption::SetOptionString($this->MODULE_ID, $this->CRM_SITES_LIST, serialize([]));
            }

            //prepare crm lists
            try {
                $arResult['orderTypesList']       = $this->RETAIL_CRM_API->orderTypesList()->orderTypes;
                $arResult['deliveryTypesList']    = $this->RETAIL_CRM_API->deliveryTypesList()->deliveryTypes;
                $arResult['deliveryServicesList'] = $this->RETAIL_CRM_API->deliveryServicesList()->deliveryServices;
                $arResult['paymentTypesList']     = $this->RETAIL_CRM_API->paymentTypesList()->paymentTypes;
                $arResult['paymentStatusesList']  = $this->RETAIL_CRM_API->paymentStatusesList()->paymentStatuses;
                $arResult['paymentList']          = $this->RETAIL_CRM_API->statusesList()->statuses;
                $arResult['paymentGroupList']     = $this->RETAIL_CRM_API->statusGroupsList()->statusGroups;
            } catch (CurlException $e) {
                RCrmActions::eventLog(
                    'intaro.retailcrm/install/index.php', 'RetailCrm\ApiClient::*List::CurlException',
                    $e->getCode() . ': ' . $e->getMessage()
                );
            } catch (\InvalidArgumentException $e) {
                $arResult['errCode'] = 'ERR_METHOD_NOT_FOUND';
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step1.php'
                );

                return false;
            }

            $delivTypes = [];
            foreach ($arResult['deliveryTypesList'] as $delivType) {
                if ($delivType['active'] === true) {
                    $delivTypes[$delivType['code']] = $delivType;
                }
            }
            $arResult['deliveryTypesList'] = $delivTypes;

            //bitrix personTypes
            $arResult['bitrixOrderTypesList'] = RCrmActions::OrderTypesList($arResult['arSites']);

            //bitrix deliveryList
            $arResult['bitrixDeliveryTypesList'] = RCrmActions::DeliveryList();

            //bitrix paymentList
            $arResult['bitrixPaymentTypesList'] = RCrmActions::PaymentList();

            //bitrix statusesList --statuses
            $arResult['bitrixStatusesList'] = RCrmActions::StatusesList();

            if ($order_types = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_ORDER_TYPES_ARR, 0)) {
                $arResult['ORDER_TYPES'] = array_flip(unserialize($order_types));
            }
            if ($delivery_types = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_DELIVERY_TYPES_ARR, 0)) {
                $arResult['DELIVERY_TYPES'] = array_flip(unserialize($delivery_types));
            }
            if ($payment_types = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_PAYMENT_TYPES, 0)) {
                $arResult['PAYMENT_TYPES'] = array_flip(unserialize($payment_types));
            }
            if ($payment_statuses = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_PAYMENT_STATUSES, 0)) {
                $arResult['PAYMENT_STATUSES'] = array_flip(unserialize($payment_statuses));
            }
            if ($payment = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_PAYMENT, 0)) {
                $arResult['PAYMENT'] = array_flip(unserialize($payment));
            }

            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step2.php'
            );
        } elseif ($step == 3) {
            if (!CModule::IncludeModule("sale")) {
                $arResult['errCode'] = 'ERR_SALE';
            }

            if (isset($_POST['back']) && $_POST['back']) {
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step1.php'
                );
            }

            // api load
            $api_host             = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, 0);
            $api_key              = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, 0);
            $this->RETAIL_CRM_API = new ApiClient($api_host, $api_key);

            //bitrix orderTypesList
            $arResult['arSites']              = RCrmActions::SitesList();
            $arResult['bitrixOrderTypesList'] = RCrmActions::OrderTypesList($arResult['arSites']);

            $orderTypesArr = [];
            foreach ($arResult['bitrixOrderTypesList'] as $orderType) {
                $orderTypesArr[$orderType['ID']] = htmlspecialchars(trim($_POST['order-type-' . $orderType['ID']]));
            }

            //bitrix deliveryTypesList
            $arResult['bitrixDeliveryTypesList'] = RCrmActions::DeliveryList();

            if (htmlspecialchars(trim($_POST['delivery-types-export'])) == 'false') {
                $deliveryTypesArr = [];
                foreach ($arResult['bitrixDeliveryTypesList'] as $delivery) {
                    $deliveryTypesArr[$delivery['ID']] = htmlspecialchars(trim($_POST['delivery-type-' . $delivery['ID']]));
                }
            } elseif (htmlspecialchars(trim($_POST['delivery-types-export'])) == 'true') {
                // send to intaro crm and save delivery types!
                $arDeliveryServiceAll = Manager::getActiveList();
                foreach ($arResult['bitrixDeliveryTypesList'] as $deliveryType) {
                    $load = true;
                    try {
                        $this->RETAIL_CRM_API->deliveryTypesEdit(RCrmActions::clearArr([
                            'code' => $deliveryType['ID'],
                            'name' => RCrmActions::toJSON($deliveryType['NAME']),
                            'defaultCost' => $deliveryType['CONFIG']['MAIN']['PRICE'],
                            'description' => RCrmActions::toJSON($deliveryType['DESCRIPTION']),
                            'paymentTypes' => '',
                        ]));
                    } catch (CurlException $e) {
                        $load = false;
                        RCrmActions::eventLog(
                            'intaro.crm/install/index.php', 'RetailCrm\ApiClient::deliveryTypeEdit::CurlException',
                            $e->getCode() . ': ' . $e->getMessage()
                        );
                    }
                    if ($load) {
                        $deliveryTypesArr[$deliveryType['ID']] = $deliveryType['ID'];
                        foreach ($arDeliveryServiceAll as $deliveryService) {
                            if ($deliveryService['PARENT_ID'] != 0 && $deliveryService['PARENT_ID'] == $deliveryType['ID']) {
                                $srv = explode(':', $deliveryService['CODE']);
                                if (count($srv) == 2) {
                                    try {
                                        $this->RETAIL_CRM_API->deliveryServicesEdit(RCrmActions::clearArr([
                                            'code' => $srv[1],
                                            'name' => RCrmActions::toJSON($deliveryService['NAME']),
                                            'deliveryType' => $deliveryType['ID'],
                                        ]));
                                    } catch (CurlException $e) {
                                        RCrmActions::eventLog(
                                            'intaro.crm/install/index.php', 'RetailCrm\ApiClient::deliveryServiceEdit::CurlException',
                                            $e->getCode() . ': ' . $e->getMessage()
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }

            //bitrix paymentTypesList
            $arResult['bitrixPaymentTypesList'] = RCrmActions::PaymentList();

            $paymentTypesArr = [];
            foreach ($arResult['bitrixPaymentTypesList'] as $payment) {
                $paymentTypesArr[$payment['ID']] = htmlspecialchars(trim($_POST['payment-type-' . $payment['ID']]));
            }

            //bitrix statusesList
            $arResult['bitrixStatusesList'] = RCrmActions::StatusesList();

            $paymentStatusesArr = [];
            $canselOrderArr     = [];

            foreach ($arResult['bitrixStatusesList'] as $status) {
                $paymentStatusesArr[$status['ID']] = htmlspecialchars(trim($_POST['payment-status-' . $status['ID']]));
                if (trim($_POST['order-cansel-' . $status['ID']]) == 'Y') {
                    $canselOrderArr[] = $status['ID'];
                }
            }

            //form payment ids arr
            $paymentArr      = [];
            $paymentArr['Y'] = htmlspecialchars(trim($_POST['payment-Y']));
            $paymentArr['N'] = htmlspecialchars(trim($_POST['payment-N']));

            //new page
            //form orderProps
            $arResult['arProp'] = RCrmActions::OrderPropsList();

            $request = Application::getInstance()->getContext()->getRequest();

            if ($request->isHttps() === true) {
                COption::SetOptionString($this->MODULE_ID, $this->PROTOCOL, 'https://');
            } else {
                COption::SetOptionString($this->MODULE_ID, $this->PROTOCOL, 'http://');
            }

            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_TYPES_ARR, serialize(RCrmActions::clearArr($orderTypesArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_DELIVERY_TYPES_ARR, serialize(RCrmActions::clearArr($deliveryTypesArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_TYPES, serialize(RCrmActions::clearArr($paymentTypesArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_STATUSES, serialize(RCrmActions::clearArr($paymentStatusesArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT, serialize(RCrmActions::clearArr($paymentArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_LAST_ID, 0);
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_DISCHARGE, 1);
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_FAILED_IDS, serialize([]));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_CANSEL_ORDER, serialize(RCrmActions::clearArr($canselOrderArr)));

            if ($orderProps = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_ORDER_PROPS, 0)) {
                $arResult['ORDER_PROPS'] = unserialize($orderProps);
            }
            if ($customFields = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_CUSTOM_FIELDS, 0)) {
                $arResult['CUSTOM_FIELDS'] = unserialize($customFields);
            }
            if ($legalDetails = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_LEGAL_DETAILS, 0)) {
                $arResult['LEGAL_DETAILS'] = unserialize($legalDetails);
            }
            if ($contragentType = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_CONTRAGENT_TYPE, 0)) {
                $arResult['CONTRAGENT_TYPES'] = unserialize($contragentType);
            }

            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step3.php'
            );
        } elseif ($step == 4) {
            if (!CModule::IncludeModule("sale")) {
                $arResult['errCode'] = 'ERR_SALE';
            }

            if (isset($_POST['back']) && $_POST['back']) {
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step2.php'
                );
            }
            //order upload
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                && isset($_POST['ajax'])
                && $_POST['ajax'] == 1
            ) {
                $historyTime = Date('');
                $this->loadDeps();
                RetailCrmOrder::uploadOrders(); // each 50

                $lastUpOrderId = COption::GetOptionString($this->MODULE_ID, $this->CRM_ORDER_LAST_ID, 0);
                $countLeft     = (int)OrderTable::getCount(['>ID' => $lastUpOrderId]);
                $countAll      = (int)OrderTable::getCount();

                if (!isset($_POST['finish'])) {
                    $finish = 0;
                } else {
                    $finish = (int)$_POST['finish'];
                }

                if (!$countAll) {
                    $percent = 100;
                } else {
                    $percent = round(100 - ($countLeft * 100 / $countAll), 1);
                }

                if (!$countLeft) {
                    $finish = 1;
                }

                $APPLICATION->RestartBuffer();
                header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
                die(json_encode(["finish" => $finish, "percent" => $percent]));
            }

            if (isset($_POST['back']) && $_POST['back']) {
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step2.php'
                );
            }

            //bitrix orderTypesList
            $orderTypesList = RCrmActions::OrderTypesList(RCrmActions::SitesList());

            $orderTypesArr = [];
            foreach ($orderTypesList as $orderType) {
                $orderTypesArr[$orderType['ID']] = htmlspecialchars(trim($_POST['order-type-' . $orderType['ID']]));
            }

            $orderPropsArr = [];
            foreach ($orderTypesList as $orderType) {
                $propsCount     = 0;
                $_orderPropsArr = [];
                foreach ($arResult['orderProps'] as $orderProp) {
                    if ((!(int)htmlspecialchars(trim($_POST['address-detail-' . $orderType['ID']]))) && $propsCount > 4) {
                        break;
                    }
                    $_orderPropsArr[$orderProp['ID']] = htmlspecialchars(trim($_POST['order-prop-' . $orderProp['ID'] . '-' . $orderType['ID']]));
                    $propsCount++;
                }
                $orderPropsArr[$orderType['ID']] = $_orderPropsArr;
            }

            //legal details props
            $legalDetailsArr = [];
            foreach ($orderTypesList as $orderType) {
                $_legalDetailsArr = [];
                foreach ($arResult['legalDetails'] as $legalDetails) {
                    $_legalDetailsArr[$legalDetails['ID']] = htmlspecialchars(trim($_POST['legal-detail-' . $legalDetails['ID'] . '-' . $orderType['ID']]));
                }
                $legalDetailsArr[$orderType['ID']] = $_legalDetailsArr;
            }

            $customFieldsArr = [];
            foreach ($orderTypesList as $orderType) {
                $_customFieldsArr = [];
                foreach ($arResult['customFields'] as $custom) {
                    $_customFieldsArr[$custom['ID']] = htmlspecialchars(trim($_POST['custom-fields-' . $custom['ID'] . '-' . $orderType['ID']]));
                }
                $customFieldsArr[$orderType['ID']] = $_customFieldsArr;
            }

            //contragents type list
            $contragentTypeArr = [];
            foreach ($orderTypesList as $orderType) {
                $contragentTypeArr[$orderType['ID']] = htmlspecialchars(trim($_POST['contragent-type-' . $orderType['ID']]));
            }

            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_PROPS, serialize(RCrmActions::clearArr($orderPropsArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_CUSTOM_FIELDS, serialize(RCrmActions::clearArr($customFieldsArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_LEGAL_DETAILS, serialize(RCrmActions::clearArr($legalDetailsArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_CONTRAGENT_TYPE, serialize(RCrmActions::clearArr($contragentTypeArr)));

            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step4.php'
            );

        } elseif ($step == 5) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                && isset($_POST['ajax'])
                && $_POST['ajax'] == 1
            ) {
                CModule::IncludeModule('highloadblock');
                $rsData               = HighloadBlockTable::getList(['filter' => ['TABLE_NAME' => $_POST['table']]]);
                $hlblockArr           = $rsData->Fetch();
                $hlblock              = HighloadBlockTable::getById($hlblockArr["ID"])->fetch();
                $entity               = HighloadBlockTable::compileEntity($hlblock);
                $hbFields             = $entity->getFields();
                $hlblockList['table'] = $hlblockArr["TABLE_NAME"];

                foreach ($hbFields as $hbFieldCode => $hbField) {
                    $hlblockList['fields'][] = $hbFieldCode;
                }

                $APPLICATION->RestartBuffer();
                header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
                die(json_encode($hlblockList));
            }
            if (!CModule::IncludeModule("iblock")) {
                $arResult['errCode'] = 'ERR_IBLOCK';
            }
            if (!CModule::IncludeModule("catalog")) {
                $arResult['errCode'] = 'ERR_CATALOG';
            }
            if (!CModule::IncludeModule("sale")) {
                $arResult['errCode'] = 'ERR_SALE';
            }

            $api_host = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, 0);
            $api_key  = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, 0);
            $api      = new ApiClient($api_host, $api_key);

            $customerH = $this->historyLoad($api, 'customersHistory');
            COption::SetOptionString($this->MODULE_ID, $this->CRM_CUSTOMER_HISTORY, $customerH);

            //new data
            if ($historyDate = COption::GetOptionString($this->OLD_MODULE_ID, 'order_history_date', 0)) {
                try {
                    $history = $api->ordersHistory(['startDate' => $historyDate]);
                } catch (CurlException $e) {
                    RCrmActions::eventLog(
                        'intaro.retailcrm/install/index.php', 'RetailCrm\RestApi::ordersHistory::CurlException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                } catch (InvalidArgumentException $e) {
                    RCrmActions::eventLog(
                        'intaro.retailcrm/install/index.php', 'RetailCrm\RestApi::ordersHistory::InvalidArgumentException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                }
                if (isset($history['history'])) {
                    $hIs    = (int)$history['history'][0]['id'] - 1;
                    $orderH = $hIs;
                } else {
                    $orderH = $this->historyLoad($api, 'ordersHistory');
                }
            } else {
                $orderH = $this->historyLoad($api, 'ordersHistory');
            }

            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_HISTORY, $orderH);

            if ($orderLastId = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_ORDER_LAST_ID, 0)) {
                COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_LAST_ID, $orderLastId);
            } else {
                $dbOrder = OrderTable::GetList([
                    'order' => ["ID" => "DESC"],
                    'limit' => 1,
                    'select' => ['ID'],
                ]);
                $arOrder = $dbOrder->fetch();
                if (!empty($arOrder['ID'])) {
                    COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_LAST_ID, $arOrder['ID']);
                } else {
                    COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_LAST_ID, 0);
                }
            }

            if ($orderFailedIds = COption::GetOptionString($this->OLD_MODULE_ID, $this->CRM_ORDER_FAILED_IDS, 0)) {
                COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_FAILED_IDS, $orderFailedIds);
            }

            $arResult['PRICE_TYPES'] = [];

            $dbPriceType = CCatalogGroup::GetList(
                ["SORT" => "ASC"], [], [], [], ["ID", "NAME", "BASE"]
            );

            while ($arPriceType = $dbPriceType->Fetch()) {
                $arResult['PRICE_TYPES'][$arPriceType['ID']] = $arPriceType;
            }

            if (isset($_POST['back']) && $_POST['back']) {
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step3.php'
                );
            }

            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step5.php'
            );
        } elseif ($step == 6) {
            if (!CModule::IncludeModule("iblock")) {
                $arResult['errCode'] = 'ERR_IBLOCK';
            }
            if (!CModule::IncludeModule("catalog")) {
                $arResult['errCode'] = 'ERR_CATALOG';
            }
            if (!CModule::IncludeModule("sale")) {
                $arResult['errCode'] = 'ERR_SALE';
            }

            if (isset($arResult['errCode']) && $arResult['errCode']) {
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step5.php'
                );

                return false;
            }

            if (isset($_POST['back']) && $_POST['back']) {
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step4.php'
                );
            }

            if (!isset($_POST['IBLOCK_EXPORT'])) {
                $arResult['errCode'] = 'ERR_FIELDS_IBLOCK';
            } else {
                $iblocks = $_POST['IBLOCK_EXPORT'];
            }

            $hlblockModule = false;
            //highloadblock
            if (CModule::IncludeModule('highloadblock')) {
                $hlblockModule = true;
                $hlblockList   = [];
                $hlblockListDb = HighloadBlockTable::getList();

                while ($hlblockArr = $hlblockListDb->Fetch()) {
                    $hlblock                                         = HighloadBlockTable::getById($hlblockArr["ID"])->fetch();
                    $entity                                          = HighloadBlockTable::compileEntity($hlblock);
                    $hbFields                                        = $entity->getFields();
                    $hlblockList[$hlblockArr["TABLE_NAME"]]['LABEL'] = $hlblockArr["NAME"];

                    foreach ($hbFields as $hbFieldCode => $hbField) {
                        $hlblockList[$hlblockArr["TABLE_NAME"]]['FIELDS'][] = $hbFieldCode;
                    }
                }
            }

            $iblockProperties = [
                "article" => "article",
                "manufacturer" => "manufacturer",
                "color" => "color",
                "weight" => "weight",
                "size" => "size",
                "length" => "length",
                "width" => "width",
                "height" => "height",
                "picture" => "picture",
            ];

            $propertiesSKU     = [];
            $propertiesUnitSKU = [];
            $propertiesHbSKU   = [];

            foreach ($iblockProperties as $prop) {
                foreach ($_POST['IBLOCK_PROPERTY_SKU' . '_' . $prop] as $iblock => $val) {
                    $propertiesSKU[$iblock][$prop] = $val;
                }
                foreach ($_POST['IBLOCK_PROPERTY_UNIT_SKU' . '_' . $prop] as $iblock => $val) {
                    $propertiesUnitSKU[$iblock][$prop] = $val;
                }

                if ($hlblockModule === true && $prop !== 'picture') {
                    foreach ($hlblockList as $tableName => $hb) {
                        foreach ($_POST['highloadblock' . $tableName . '_' . $prop] as $iblock => $val) {
                            $propertiesHbSKU[$tableName][$iblock][$prop] = $val;
                        }
                    }
                }
            }

            $propertiesProduct     = [];
            $propertiesUnitProduct = [];
            $propertiesHbProduct   = [];

            foreach ($iblockProperties as $prop) {
                foreach ($_POST['IBLOCK_PROPERTY_PRODUCT' . '_' . $prop] as $iblock => $val) {
                    $propertiesProduct[$iblock][$prop] = $val;
                }
                foreach ($_POST['IBLOCK_PROPERTY_UNIT_PRODUCT' . '_' . $prop] as $iblock => $val) {
                    $propertiesUnitProduct[$iblock][$prop] = $val;
                }

                if ($hlblockModule == true && $prop != 'picture') {
                    foreach ($hlblockList as $tableName => $hb) {
                        foreach ($_POST['highloadblock_product' . $tableName . '_' . $prop] as $iblock => $val) {
                            $propertiesHbProduct[$tableName][$iblock][$prop] = $val;
                        }
                    }
                }
            }

            if (!isset($_POST['SETUP_FILE_NAME'])) {
                $arResult['errCode'] = 'ERR_FIELDS_FILE';
            } else {
                $filename = $_POST['SETUP_FILE_NAME'];
            }

            if (!isset($_POST['TYPE_LOADING'])) {
                $typeLoading = 0;
            } else {
                $typeLoading = $_POST['TYPE_LOADING'];
            }

            if (!isset($_POST['MAX_OFFERS_VALUE'])) {
                $maxOffers = "";
            } else {
                $maxOffers = $_POST['MAX_OFFERS_VALUE'];
            }

            if (!isset($_POST['SETUP_PROFILE_NAME'])) {
                $profileName = "";
            } else {
                $profileName = $_POST['SETUP_PROFILE_NAME'];
            }

            if ($typeLoading != 'none' && $profileName == "") {
                $arResult['errCode'] = 'ERR_FIELDS_PROFILE';
            }

            if (empty($filename)) {
                $arResult['errCode'] = 'ERR_FIELDS_FILE';
            }

            if (isset($arResult['errCode']) && $arResult['errCode']) {
                $arOldValues = [
                    'IBLOCK_EXPORT' => $iblocks,
                    'IBLOCK_PROPERTY_SKU' => $propertiesSKU,
                    'IBLOCK_PROPERTY_UNIT_SKU' => $propertiesUnitSKU,
                    'IBLOCK_PROPERTY_PRODUCT' => $propertiesProduct,
                    'IBLOCK_PROPERTY_UNIT_PRODUCT' => $propertiesUnitProduct,
                    'SETUP_FILE_NAME' => $filename,
                    'SETUP_PROFILE_NAME' => $profileName,
                    'MAX_OFFERS_VALUE' => $maxOffers,
                ];
                global $oldValues;
                $oldValues = $arOldValues;
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step5.php'
                );

                return false;
            }

            RegisterModule($this->MODULE_ID);
            RegisterModuleDependences("sale", "OnOrderUpdate", $this->MODULE_ID, "RetailCrmEvent", "onUpdateOrder");
            RegisterModuleDependences("main", "OnAfterUserUpdate", $this->MODULE_ID, "RetailCrmEvent", "OnAfterUserUpdate");
            RegisterModuleDependences("sale", EventActions::EVENT_ON_ORDER_SAVED, $this->MODULE_ID, "RetailCrmEvent", "orderSave");
            RegisterModuleDependences("sale", "OnSaleOrderDeleted", $this->MODULE_ID, "RetailCrmEvent", "orderDelete");
            RegisterModuleDependences("sale", "OnSalePaymentEntitySaved", $this->MODULE_ID, "RetailCrmEvent", "paymentSave");
            RegisterModuleDependences("sale", "OnSalePaymentEntityDeleted", $this->MODULE_ID, "RetailCrmEvent", "paymentDelete");

            COption::SetOptionString($this->MODULE_ID, $this->CRM_CATALOG_BASE_PRICE, htmlspecialchars(trim($_POST['price-types'])));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_INVENTORIES_UPLOAD, 'N');
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PRICES_UPLOAD, 'N');
            COption::SetOptionString($this->MODULE_ID, $this->CRM_COLLECTOR, 'N');
            COption::SetOptionString($this->MODULE_ID, $this->CRM_UA, 'N');

            //agent
            $dateAgent = new DateTime();
            $intAgent  = new DateInterval('PT60S'); // PT60S - 60 sec;
            $dateAgent->add($intAgent);

            CAgent::AddAgent(
                "RCrmActions::orderAgent();", $this->MODULE_ID, "N", 600, // interval - 10 mins
                $dateAgent->format('d.m.Y H:i:s'), // date of first check
                "Y", // agent is active
                $dateAgent->format('d.m.Y H:i:s'), // date of first start
                30
            );

            $this->CopyFiles();

            if (isset($_POST['LOAD_NOW'])) {
                $loader                        = new RetailCrmICML();
                $loader->iblocks               = $iblocks;
                $loader->propertiesUnitProduct = $propertiesUnitProduct;
                $loader->propertiesProduct     = $propertiesProduct;
                $loader->propertiesUnitSKU     = $propertiesUnitSKU;
                $loader->propertiesSKU         = $propertiesSKU;

                if ($hlblockModule === true) {
                    $loader->highloadblockSkuProperties     = $propertiesHbSKU;
                    $loader->highloadblockProductProperties = $propertiesHbProduct;
                }

                if ($maxOffers) {
                    $loader->offerPageSize = $maxOffers;
                }

                $loader->filename    = $filename;
                $loader->serverName  = Context::getCurrent()->getServer()->getHttpHost();
                $loader->application = $APPLICATION;
                $loader->Load();
            }

            COption::RemoveOption($this->MODULE_ID, $this->CRM_CATALOG_BASE_PRICE);

            if ($typeLoading === 'agent' || $typeLoading === 'cron') {
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/' . $this->RETAIL_CRM_EXPORT . '_run.php')) {
                    $dbProfile = CCatalogExport::GetList([], ["FILE_NAME" => $this->RETAIL_CRM_EXPORT]);

                    while ($arProfile = $dbProfile->Fetch()) {
                        if ($arProfile["DEFAULT_PROFILE"] != "Y") {
                            CAgent::RemoveAgent("CCatalogExport::PreGenerateExport(" . $arProfile['ID'] . ");", "catalog");
                            CCatalogExport::Delete($arProfile['ID']);
                        }
                    }
                }

                $ar         = $this->GetProfileSetupVars(
                    $iblocks,
                    $propertiesProduct,
                    $propertiesUnitProduct,
                    $propertiesSKU,
                    $propertiesUnitSKU,
                    $propertiesHbSKU,
                    $propertiesHbProduct,
                    $filename,
                    $maxOffers
                );
                $PROFILE_ID = CCatalogExport::Add([
                    "LAST_USE" => false,
                    "FILE_NAME" => $this->RETAIL_CRM_EXPORT,
                    "NAME" => $profileName,
                    "DEFAULT_PROFILE" => "N",
                    "IN_MENU" => "N",
                    "IN_AGENT" => "N",
                    "IN_CRON" => "N",
                    "NEED_EDIT" => "N",
                    "SETUP_VARS" => $ar,
                ]);
                if (intval($PROFILE_ID) <= 0) {
                    $arResult['errCode'] = 'ERR_IBLOCK';

                    return false;
                }

                COption::SetOptionString(
                    $this->MODULE_ID,
                    $this->CRM_CATALOG_BASE_PRICE . '_' . $PROFILE_ID,
                    htmlspecialchars(trim($_POST['price-types']))
                );

                if ($typeLoading === 'agent') {
                    $dateAgent = new DateTime();
                    $intAgent  = new DateInterval('PT60S'); // PT60S - 60 sec;
                    $dateAgent->add($intAgent);
                    CAgent::AddAgent(
                        "CCatalogExport::PreGenerateExport(" . $PROFILE_ID . ");", "catalog", "N", 86400, $dateAgent->format('d.m.Y H:i:s'), // date of first check
                        "Y", // agent is active
                        $dateAgent->format('d.m.Y H:i:s'), // date of first start
                        30
                    );

                    CCatalogExport::Update($PROFILE_ID, [
                        "IN_AGENT" => "Y",
                    ]);
                } else {
                    $agent_period   = 24;
                    $agent_php_path = "/usr/local/php/bin/php";

                    if (!file_exists($_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS . "cron_frame.php")) {
                        CheckDirPath($_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS);
                        $tmp_file_size = filesize($_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS_DEF . "cron_frame.php");
                        $fp            = fopen($_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS_DEF . "cron_frame.php", "rb");
                        $tmp_data      = fread($fp, $tmp_file_size);
                        fclose($fp);

                        $tmp_data = str_replace(["#DOCUMENT_ROOT#", "#PHP_PATH#"], [$_SERVER["DOCUMENT_ROOT"], $agent_php_path], $tmp_data);

                        $fp = fopen($_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS . "cron_frame.php", "ab");
                        fwrite($fp, $tmp_data);
                        fclose($fp);
                    }

                    $cfg_data = "";
                    if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/bitrix/crontab/crontab.cfg")) {
                        $cfg_file_size = filesize($_SERVER["DOCUMENT_ROOT"] . "/bitrix/crontab/crontab.cfg");
                        $fp            = fopen($_SERVER["DOCUMENT_ROOT"] . "/bitrix/crontab/crontab.cfg", "rb");
                        $cfg_data      = fread($fp, $cfg_file_size);
                        fclose($fp);
                    }

                    CheckDirPath($_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS . "logs/");

                    if (isset($arProfile["IN_CRON"]) && $arProfile["IN_CRON"] === "Y") {
                        // remove
                        $cfg_data = preg_replace("#^.*?" . preg_quote(CATALOG_PATH2EXPORTS) . "cron_frame.php +" . $PROFILE_ID . " *>.*?$#im", "", $cfg_data);
                    } else {
                        $strTime = "0 */" . $agent_period . " * * * ";
                        if ($cfg_data !== '') {
                            $cfg_data .= "\n";
                        }

                        $cfg_data .= $strTime
                            . $agent_php_path
                            . " -f "
                            . $_SERVER["DOCUMENT_ROOT"]
                            . CATALOG_PATH2EXPORTS
                            . "cron_frame.php "
                            . $PROFILE_ID
                            . " >"
                            . $_SERVER["DOCUMENT_ROOT"]
                            . CATALOG_PATH2EXPORTS
                            . "logs/"
                            . $PROFILE_ID
                            . ".txt\n";
                    }

                    CCatalogExport::Update($PROFILE_ID, [
                        "IN_CRON" => "Y",
                    ]);

                    CheckDirPath($_SERVER["DOCUMENT_ROOT"] . "/bitrix/crontab/");
                    $cfg_data = preg_replace("#[\r\n]{2,}#im", "\n", $cfg_data);
                    $fp       = fopen($_SERVER["DOCUMENT_ROOT"] . "/bitrix/crontab/crontab.cfg", "wb");
                    fwrite($fp, $cfg_data);
                    fclose($fp);

                    $arRetval = [];
                    @exec("crontab " . $_SERVER["DOCUMENT_ROOT"] . "/bitrix/crontab/crontab.cfg", $arRetval, $return_var);
                }
            }

            $api_host             = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, 0);
            $api_key              = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, 0);
            $api_version          = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_VERSION, 0);
            $this->RETAIL_CRM_API = new ApiClient($api_host, $api_key);

            RCrmActions::sendConfiguration($this->RETAIL_CRM_API, $api_version);

            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'), $this->INSTALL_PATH . '/step6.php'
            );
        }
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $api_host    = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, 0);
        $api_key     = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, 0);
        $api_version = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_VERSION, 0);

        include($this->INSTALL_PATH . '/../classes/general/Http/Client.php');
        include($this->INSTALL_PATH . '/../classes/general/Response/ApiResponse.php');
        include($this->INSTALL_PATH . '/../classes/general/Exception/InvalidJsonException.php');
        include($this->INSTALL_PATH . '/../classes/general/Exception/CurlException.php');
        include($this->INSTALL_PATH . '/../classes/general/RCrmActions.php');
        include($this->INSTALL_PATH . '/../classes/general/Logger.php');
        include($this->INSTALL_PATH . '/../classes/general/ApiClient_v5.php');
        include($this->INSTALL_PATH . '/../classes/general/order/RetailCrmOrder_v5.php');
        include($this->INSTALL_PATH . '/../classes/general/history/RetailCrmHistory_v5.php');

        $retail_crm_api = new ApiClient($api_host, $api_key);

        CAgent::RemoveAgent("RCrmActions::orderAgent();", $this->MODULE_ID);
        CAgent::RemoveAgent("RetailCrmInventories::inventoriesUpload();", $this->MODULE_ID);
        CAgent::RemoveAgent("RetailCrmPrices::pricesUpload();", $this->MODULE_ID);

        COption::RemoveOption($this->MODULE_ID, $this->CRM_API_HOST_OPTION);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_API_KEY_OPTION);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_DELIVERY_TYPES_ARR);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT_TYPES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT_STATUSES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_LAST_ID);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_SITES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_PROPS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_LEGAL_DETAILS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_CONTRAGENT_TYPE);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_CUSTOM_FIELDS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_SITES_LIST);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_DISCHARGE);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_FAILED_IDS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_CUSTOMER_HISTORY);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_HISTORY);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_CATALOG_BASE_PRICE);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_CURRENCY);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ADDRESS_OPTIONS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_NUMBERS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_CANSEL_ORDER);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_INVENTORIES_UPLOAD);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_STORES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_SHOPS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_IBLOCKS_INVENTORIES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PRICES_UPLOAD);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PRICES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PRICE_SHOPS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_IBLOCKS_PRICES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_COLLECTOR);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_COLL_KEY);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_UA);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_UA_KEYS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_API_VERSION);
        COption::RemoveOption($this->MODULE_ID, $this->HISTORY_TIME);
        COption::RemoveOption($this->MODULE_ID, $this->CLIENT_ID);
        COption::RemoveOption($this->MODULE_ID, $this->PROTOCOL);

        if (CModule::IncludeModule('sale')) {
            UnRegisterModuleDependences(
                "sale",
                EventActions::EVENT_ON_ORDER_SAVED,
                $this->MODULE_ID,
                "RetailCrmEvent",
                "orderSave"
            );
        }

        UnRegisterModuleDependences("sale", "OnOrderUpdate", $this->MODULE_ID, "RetailCrmEvent", "onUpdateOrder");
        UnRegisterModuleDependences("main", "OnAfterUserUpdate", $this->MODULE_ID, "RetailCrmEvent", "OnAfterUserUpdate");
        UnRegisterModuleDependences("sale", "OnSaleOrderDeleted", $this->MODULE_ID, "RetailCrmEvent", "orderDelete");
        UnRegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "RetailCrmCollector", "add");
        UnRegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "RetailCrmUa", "add");
        UnRegisterModuleDependences("sale", "OnSalePaymentEntitySaved", $this->MODULE_ID, "RetailCrmEvent", "paymentSave");
        UnRegisterModuleDependences("sale", "OnSalePaymentEntityDeleted", $this->MODULE_ID, "RetailCrmEvent", "paymentDelete");

        if (CModule::IncludeModule("catalog")) {
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/' . $this->RETAIL_CRM_EXPORT . '_run.php')) {
                $dbProfile = CCatalogExport::GetList([], ["FILE_NAME" => $this->RETAIL_CRM_EXPORT]);

                while ($arProfile = $dbProfile->Fetch()) {
                    if ($arProfile["DEFAULT_PROFILE"] !== "Y") {
                        CAgent::RemoveAgent("CCatalogExport::PreGenerateExport(" . $arProfile['ID'] . ");", "catalog");
                        CCatalogExport::Delete($arProfile['ID']);
                    }
                }
            }
        }

        RCrmActions::sendConfiguration($retail_crm_api, $api_version, false);

        $this->DeleteFiles();
        $this->deleteLPEvents();

        UnRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            GetMessage('MODULE_UNINSTALL_TITLE'), $this->INSTALL_PATH . '/unstep1.php'
        );
    }

    public function CopyFiles(): void
    {
        $pathFrom = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install';

        CopyDirFiles(
            $pathFrom . '/export',
            $_SERVER['DOCUMENT_ROOT'],
            true,
            true,
            false
        );
        CopyDirFiles(
            $pathFrom
            . '/export_sale_payment',
            $_SERVER['DOCUMENT_ROOT']
            . COption::GetOptionString('sale', 'path2user_ps_files'),
            true,
            true,
            false
        );
    }

    public function DeleteFiles(): void
    {
        $rsSites     = CSite::GetList($by, $sort, ['DEF' => 'Y']);
        $defaultSite = [];
        while ($ar = $rsSites->Fetch()) {
            $defaultSite = $ar;
            break;
        }

        unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/retailcrm_run.php');
        unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/retailcrm_setup.php');
        unlink($defaultSite['ABS_DOC_ROOT'] . '/retailcrm/agent.php');
        rmdir($defaultSite['ABS_DOC_ROOT'] . '/retailcrm/');
        DeleteDirFilesEx(
            $_SERVER['DOCUMENT_ROOT']
            . COption::GetOptionString('sale', 'path2user_ps_files')
            . 'retailcrmbonus'
        );
    }

    public function GetProfileSetupVars(
        $iblocks,
        $propertiesProduct,
        $propertiesUnitProduct,
        $propertiesSKU,
        $propertiesUnitSKU,
        $propertiesHbSKU,
        $propertiesHbProduct,
        $filename,
        $maxOffers
    ): string
    {
        $strVars = "";
        foreach ($iblocks as $key => $val) {
            $strVars .= 'IBLOCK_EXPORT[' . $key . ']=' . $val . '&';
        }
        foreach ($propertiesSKU as $iblock => $arr) {
            foreach ($arr as $id => $val) {
                $strVars .= 'IBLOCK_PROPERTY_SKU_' . $id . '[' . $iblock . ']=' . $val . '&';
            }
        }
        foreach ($propertiesUnitSKU as $iblock => $arr) {
            foreach ($arr as $id => $val) {
                $strVars .= 'IBLOCK_PROPERTY_UNIT_SKU_' . $id . '[' . $iblock . ']=' . $val . '&';
            }
        }
        foreach ($propertiesProduct as $iblock => $arr) {
            foreach ($arr as $id => $val) {
                $strVars .= 'IBLOCK_PROPERTY_PRODUCT_' . $id . '[' . $iblock . ']=' . $val . '&';
            }
        }
        foreach ($propertiesUnitProduct as $iblock => $arr) {
            foreach ($arr as $id => $val) {
                $strVars .= 'IBLOCK_PROPERTY_UNIT_PRODUCT_' . $id . '[' . $iblock . ']=' . $val . '&';
            }
        }
        if ($propertiesHbSKU) {
            foreach ($propertiesHbSKU as $table => $arr) {
                foreach ($arr as $iblock => $val) {
                    foreach ($val as $id => $value) {
                        $strVars .= 'highloadblock' . $table . '_' . $id . '[' . $iblock . ']=' . $value . '&';
                    }
                }
            }
        }
        if ($propertiesHbProduct) {
            foreach ($propertiesHbProduct as $table => $arr) {
                foreach ($arr as $iblock => $val) {
                    foreach ($val as $id => $value) {
                        $strVars .= 'highloadblock_product' . $table . '_' . $id . '[' . $iblock . ']=' . $value . '&';
                    }
                }
            }
        }

        $strVars .= 'SETUP_FILE_NAME=' . urlencode($filename);
        $strVars .= '&MAX_OFFERS_VALUE=' . urlencode($maxOffers);

        return $strVars;
    }

    public function historyLoad($api, $method): int
    {
        $page      = null;
        $end['id'] = 0;

        try {
            $history = $api->$method([], $page);
        } catch (CurlException $e) {
            RCrmActions::eventLog(
                'RetailCrmHistory::' . $method, 'RetailCrm\RestApi::' . $method . '::CurlException',
                $e->getCode() . ': ' . $e->getMessage()
            );

            return $end['id'];
        } catch (InvalidArgumentException $e) {
            RCrmActions::eventLog(
                'RetailCrmHistory::' . $method, 'RetailCrm\RestApi::' . $method . '::InvalidArgumentException',
                $e->getCode() . ': ' . $e->getMessage()
            );

            return $end['id'];
        }
        if ($history['pagination']['totalPageCount'] > $history['pagination']['currentPage']) {
            $page = $history['pagination']['totalPageCount'];
            while (true) {
                try {
                    $history = $api->$method([], $page);
                } catch (CurlException $e) {
                    RCrmActions::eventLog(
                        'RetailCrmHistory::' . $method, 'RetailCrm\RestApi::' . $method . '::CurlException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    return $end['id'];
                } catch (InvalidArgumentException $e) {
                    RCrmActions::eventLog(
                        'RetailCrmHistory::' . $method, 'RetailCrm\RestApi::' . $method . '::InvalidArgumentException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    return $end['id'];
                }

                if (isset($history['history'])) {
                    $end = array_pop($history['history']);

                    break;
                } else {
                    $page--;
                }
            }
        } else {
            if (isset($history['history']) && count($history['history']) > 0) {
                $end = array_pop($history['history']);
            } else {
                $end['id'] = 0;
            }
        }

        return $end['id'];
    }

    function ping($api_host, $api_key)
    {
        global $APPLICATION;

        $client = new RetailCrm\Http\Client($api_host . '/api/' . self::V5, ['apiKey' => $api_key]);
        try {
            $result = $client->makeRequest('/reference/sites', 'GET');
        } catch (CurlException $e) {
            RCrmActions::eventLog(
                'intaro.retailcrm/install/index.php', 'RetailCrm\ApiClient::sitesList',
                $e->getCode() . ': ' . $e->getMessage()
            );

            $res['errCode'] = 'ERR_' . $e->getCode();
        }

        if ($result->getStatusCode() == 200) {
            COption::SetOptionString($this->MODULE_ID, $this->CRM_API_VERSION, self::V5);
            $res['sitesList'] = $APPLICATION->ConvertCharsetArray($result->sites, 'utf-8', SITE_CHARSET);

            return $res;
        } else {
            $res['errCode'] = 'ERR_METHOD_NOT_FOUND';
        }

        return $res;
    }

    /**
     * Add USER fields for LP
     */
    public function addLPUserFields(): void
    {
        $this->addCustomUserFields(
            [
                "UF_REG_IN_PL_INTARO",
                "UF_AGREE_PL_INTARO",
                "UF_PD_PROC_PL_INTARO",
                "UF_EXT_REG_PL_INTARO",
            ]
        );
    }

    /**
     * add LP Order Props
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function addLPOrderProps(): void
    {
        $persons = PersonTypeRepository::getCollectionByWhere(['ID']);

        foreach ($persons as $person) {
            $personId = $person->getID();
            $groupID  = $this->getGroupID($personId);

            if (isset($groupID)) {
                $this->addBonusField($personId, $groupID);
            }
        }
    }

    /**
     * @param        $fieldNames
     * @param string $filedType
     */
    public function addCustomUserFields($fieldNames, $filedType = 'boolean'): void
    {
        foreach ($fieldNames as $filedName) {
            $arProps = [
                "ENTITY_ID" => 'USER',
                "FIELD_NAME" => $filedName,
                "USER_TYPE_ID" => $filedType,
                "MULTIPLE" => "N",
                "MANDATORY" => "N"
            ];
            $obUserField = new CUserTypeEntity;
            $dbRes = CUserTypeEntity::GetList([], ["FIELD_NAME" => $filedName])->fetch();

            if (!$dbRes['ID']) {
                $obUserField->Add($arProps);
            }
        }
    }

    /**
     * @param $personId
     *
     * @return \Bitrix\Main\ORM\Data\AddResult|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getGroupID($personId)
    {
        $LPGroup = OrderPropsGroupTable::query()
            ->setSelect(['ID'])
            ->where(
                [
                    ['PERSON_TYPE_ID', '=', $personId],
                    ['NAME', '=', self::LP_ORDER_GROUP_NAME],
                ]
            )
            ->fetch();

        if (is_array($LPGroup)) {
            return $LPGroup['ID'];
        }

        if ($LPGroup === false) {
            return OrderPropsGroupTable::add([
                'PERSON_TYPE_ID' => $personId,
                'NAME' => self::LP_ORDER_GROUP_NAME,
            ])->getId();
        }
    }

    /**
     * @param $personID
     * @param $groupID
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function addBonusField($personID, $groupID): void
    {
        $bonusProp = OrderPropsRepository::getFirstByWhere(
            ['ID'],
            [
                ['PERSON_TYPE_ID', '=', $personID],
                ['PROPS_GROUP_ID', '=', $groupID],
            ]
        );

        if ($bonusProp === null) {
            CSaleOrderProps::Add(
                [
                    "REQUIRED" => "N",
                    "NAME" => self::BONUS_COUNT,
                    "TYPE" => "TEXT",
                    "CODE" => "BONUS_RETAILCRM",
                    "USER_PROPS" => "Y",
                    "IS_LOCATION" => "N",
                    "IS_LOCATION4TAX" => "N",
                    "IS_EMAIL" => "N",
                    "IS_PROFILE_NAME" => "N",
                    "IS_PAYER"        => "N",
                    'IS_FILTERED'     => 'Y',
                    'PERSON_TYPE_ID'  => $personID,
                    'PROPS_GROUP_ID'  => $groupID,
                    "DEFAULT_VALUE"   => 0,
                    "DESCRIPTION"     => self::BONUS_COUNT
                ]
            );
        }
    }

    /**
     * add bonus pay system
     */
    private function addBonusPaySystem(): void
    {
        $arrPaySystemAction = PaySystemActionTable::query()
            ->setSelect(['ID'])
            ->where([
                ['ACTION_FILE', '=', self::BONUS_PAY_SYSTEM_CODE],
            ])
            ->fetchCollection();

        if (count($arrPaySystemAction) === 0) {
            $result = PaySystemActionTable::add(
                [
                    'NAME' => self::BONUS_PAY_SYSTEM_NAME,
                    'PSA_NAME' => self::BONUS_PAY_SYSTEM_NAME,
                    'ACTION_FILE' => self::BONUS_PAY_SYSTEM_CODE,
                    'DESCRIPTION' => self::BONUS_PAY_SYSTEM_DESCRIPTION,
                    'RESULT_FILE' => '',
                    'NEW_WINDOW' => 'N',
                    'ENCODING' => 'utf-8',
                    'ACTIVE' => 'Y',
                    'HAVE_PAYMENT' => 'Y',
                    'HAVE_ACTION' => 'N',
                    'AUTO_CHANGE_1C' => 'N',
                    'HAVE_RESULT' => 'N',
                    'HAVE_PRICE' => 'N',
                    'HAVE_PREPAY' => 'N',
                    'HAVE_RESULT_RECEIVE' => 'N',
                    'ALLOW_EDIT_PAYMENT' => 'Y',
                    'IS_CASH' => 'N',
                    'CAN_PRINT_CHECK' => 'N',
                    'ENTITY_REGISTRY_TYPE' => 'ORDER',
                    'XML_ID' => 'intaro_' . randString(15),
                ]
            );

            PaySystemActionTable::update($result->getId(), [
                'PAY_SYSTEM_ID' => $result->getId(),
                'PARAMS' => serialize(['BX_PAY_SYSTEM_ID' => $result->getId()]),
            ]);
        }
    }

    /**
     * create loyalty program events handlers
     */
    private function addLPEvents(): void
    {
        $eventManager = EventManager::getInstance();

        foreach (self::SUBSCRIBE_LP_EVENTS as $event) {
            try {
                $events = ToModuleRepository::getCollectionByWhere(
                    ['ID'],
                    [
                        ['from_module_id', '=', $event['FROM_MODULE']],
                        ['to_module_id', '=', $this->MODULE_ID],
                        ['to_method', '=', $event['EVENT_NAME'] . 'Handler'],
                        ['to_class', '=', EventsHandlers::class],
                    ]
                );

                if ($events !== null && count($events) === 0) {
                    $eventManager->registerEventHandler(
                        $event['FROM_MODULE'],
                        $event['EVENT_NAME'],
                        $this->MODULE_ID,
                        EventsHandlers::class,
                        $event['EVENT_NAME'] . 'Handler'
                    );
                }
            } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
                AddMessage2Log($exception->getMessage(), $this->MODULE_ID);
            }
        }
    }

    /**
     * delete loyalty program events handlers
     */
    private function deleteLPEvents(): void
    {
        $eventManager = EventManager::getInstance();

        foreach (self::SUBSCRIBE_LP_EVENTS as $event) {
            $eventManager->unRegisterEventHandler(
                $event['FROM_MODULE'],
                $event['EVENT_NAME'],
                $this->MODULE_ID,
                EventsHandlers::class,
                $event['EVENT_NAME'] . 'Handler'
            );
        }
    }

}
