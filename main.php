<?php
class shopShiptorPlugin extends shopPlugin {
    private $allowedActions = array(
			"new"	=> array(
            "open"   => array(
                "name"   => "Открыть посылку",
                "target" => "_blank",
                "href"   => '//shiptor.ru/account/package/view/{$shiptor_id}',
                "class"  => "",
                "icon"   => "new-window"
            ),
            "edit"   => array(
                "name"   => "Редактировать посылку",
                "target" => "_blank",
                "href"   => '//shiptor.ru/account/package/edit/{$shiptor_id}',
                "class"  => "",
                "icon"   => "edit"
            ),
            /*"delete" => array(
                "name"   => "Удалить посылку",
                "target" => "_self",
                "href"   => "",
                "class"  => "shiptor-delete",
                "icon"   => "no"
            ),*/
            "print"  => array(
                "name"   => "Печать наклейки",
                "target" => "_blank",
                "href"   => '?module=shiptor&action=printLabel&label={$label_url}',
                "class"  => "",
                "icon"   => "print"
            )
        ),
        "removed" => array(
            "restore" => array(
                "name"   => "Восстановить",
                "target" => "_self",
                "href"   => "",
                "class"  => "shiptor-restore",
                "icon"   => "rotate-right"
            ),/*
            "add" => array(
                "name"   => "Создать заново",
                "target" => "_self",
                "href"   => '',
                "class"  => "shiptor-add",
                "icon"   => "add"
            )*/
        ),
        "packed" => array(
            "open"   => array(
                "name"   => "Открыть отгрузку",
                "target" => "_blank",
                "href"   => '//shiptor.ru/account/shipment/view/{$shiptor_id}',
                "class"  => "",
                "icon"   => "new-window"
            ),
            "edit"   => array(
                "name"   => "Редактировать отгрузку",
                "target" => "_blank",
                "href"   => '//shiptor.ru/account/shipment/edit/{$shiptor_id}',
                "class"  => "",
                "icon"   => "edit"
            ),
           /* "delete" => array(
                "name"   => "Удалить отгрузку",
                "target" => "_self",
                "href"   => '//shiptor.ru/account/shipment/remove/{$shiptor_id}',
                "class"  => "shiptor-delete",
                "icon"   => "no"
            ),*/
            "print"  => array(
                "name"   => "Печать наклейки",
                "target" => "_blank",
                "href"   => '?module=shiptor&action=printLabel&label={$label_url}',
                "class"  => "",
                "icon"   => "print"
            )
        ),
        "default" => array(
            "add" => array(
                "name"   => "Отправить в Shiptor",
                "target" => "_self",
                "href"   => '',
                "class"  => "shiptor-add",
                "icon"   => "add"
            )
        )
    );

    public function __construct($info)
    {
        parent::__construct($info);
        $this->autoload();
    }

    #region static methods
    /**
     * @param $orderID
     *
     * @return array Структура:
     * {
     * "shipping_method": {
     * "id":25,
     * "name":"CDEK",
     * "category":"delivery-point",
     * "courier":"cdek",
     * "comment":"",
     * "description":null
     * },
     * "address": {
     * "receiver":null,
     * "name":"Сергей",
     * "surname":"Гарин",
     * "patronymic":null,
     * "email":"sergikgarin@gmail.com",
     * "phone":"+7 961 159-09-19",
     * "country_code":"RU",
     * "administrative_area":"Ярославская обл.",
     * "settlement":"г. Ярославль",
     * "address_line_1":null,
     * "postal_code":"150003",
     * "street":"РЕСПУБЛИКАНСКАЯ",
     * "house":"3к7",
     * "apartment":null,
     * "kladr_id":"76000001000"
     * },
     * "delivery_point": {
     * "id":4225,
     * "courier":"cdek",
     * "address":"Ярославль, РЕСПУБЛИКАНСКАЯ, 3, кор. 7",
     * "phones":["74852679592"],
     * "trip_description":null,
     * "work_schedule":"пн-пт 10:00-20:00, сб 10:00-16:00, вс 10:00-14:00",
     * "shipping_days":null,
     * "cod":true,
     * "card":false,
     * "gps_location":{
     * "latitude":"57.640743",
     * "longitude":"39.884068"
     * },
     * "kladr_id":"76000001000",
     * "shipping_methods": [22,23,24,25,42,45,57,58,59,60],
     * "limits": {
     * "max_weight": {
     * "value":30,
     * "unit":"kg"
     * }
     * }
     * },
     * "cashless_payment":false,
     * "comment":null
     * }
     *
     * @see https://shiptor.ru/doc/#api-Shipping-getPackage response [departure]
     * TODO получение информации о ПВЗ.
     */
    public static function getDeliveryPointInfo($orderID)
    {
        $orderModel = new shopOrderParamsModel();
        $shiptorID = $orderModel->getOne($orderID, "shiptor_id");

        if(is_null($shiptorID))
        {
            return array();
        }

        $shiptorData = new shopOrderShiptorDataModel();

        return $shiptorData->getResponse($shiptorID)->departure;
    }

    public static function getModule($name = "block")
    {
        $stylesheet = "<link rel=\"stylesheet\" href=\"/wa-apps/shop/plugins/shiptor/css/build/stylesheet.css\">";

        if(file_exists(wa()->getAppPath() . "/plugins/shiptor/js/build/$name.js"))
            return $stylesheet . "<script src=\"/wa-apps/shop/plugins/shiptor/js/build/$name.js\"></script>";

        return $stylesheet . "<script src=\"/wa-apps/shop/plugins/shiptor/js/build/block.js\"></script>";
    }

    /**
     * Метод для получения трек-информации по заказу
     *
     * @param $order
     *
     * @return string
     */
    public static function trackingInfo($order)
    {
        try
        {
            $checkpoints = shiptorPackageWorker::getPackageInfo($order['params']['shiptor_id']['checkpoints']);

            wa()->getView()->assign("checkpoints", $checkpoints);
            wa()->getView()->assign("shiptor_id", $order['params']['shiptor_id']);
        }
        catch (shiptorException $shiptorException)
        {
            shiptorLogger::write_log($shiptorException->getMessage());
        }

        return self::getModule("my_order") . wa()->getView()->fetch(shiptorHelper::PLUGINPATH . "templates/trackInfo.html");
    }

    /**
     * Метод для получения информации о доставке в карточке товара
     *
     * @param $product
     *
     * @return string template
     */
    public static function getProductWidget($product)
    {
        wa()->getView()->assign("product_id", isset($product['id']) ? $product['id'] : $product['product_id']);

        return self::getModule("product_widget") . wa()->getView()->fetch(shiptorHelper::PLUGINPATH . "templates/productWidget.html");
    }
    #endregion

    #region public methods
    /**
     * Здесь подключатся js и css скрипты для работы в backend
     *
     * @return array template
     */
    public function backend_orders()
    {
        return array(
            'sidebar_section' => $this->getSidebarSection()
        );
    }

    public function backend_order($order)
    {
        if(shiptorSettings::getInstance()->isEnabled() && $order['params']['shipping_plugin'] === 'shiptor')
        {
            if(isset($order['params']['shiptor_id']))
            {
                $pack_type = shiptorSettings::getInstance()->getSettings()['pack_type'];
                if($pack_type=="shipment"){
                	$package_info = shiptorPackageWorker::getPackageInfo("",$order['id']);
                }else{
                	$package_info = shiptorPackageWorker::getPackageInfo($order['params']['shiptor_id']);
                }

                return array(
                    'title_suffix' => $this->getTitleSuffix($order),
                    'action_link'  => $this->getActionLinks($order, $this->allowedActions[$package_info['status']]),
                    'info_section' => $this->getInfoSection($order, $package_info, shiptorHelper::$statuses[$package_info['status']])
                );
            }
            else
            {
                return array(
                    'action_link' => $this->getActionLinks($order, $this->allowedActions['default'])
                );
            }
        }

        return array();
    }

    public function frontend_checkout()
    {
        return self::getModule('popup');
    }

    public function frontend_my_order($order)
    {
        self::trackingInfo($order);
    }

    public function frontend_product($product)
    {
        if(shiptorSettings::getInstance()->isEnabled() && shiptorSettings::getInstance()->getSettings()['show_product_page_widget'])
            return array(
                "block_aux" => self::getProductWidget($product)
            );

        return array();
    }

    public function uploadOrder($order_id)
    {
        $shiptorPackageWorker = new shiptorPackageWorker(new shiptorProductWorker(), new shiptorProductWorker());

        $response = $shiptorPackageWorker->addPackage($order_id);

        $shopOrderParamsModel = new shopOrderParamsModel();
        $pack_type = shiptorSettings::getInstance()->getSettings()['pack_type'];
        $arr_inserts = array();
        if ($pack_type=='package'){
			$arr_inserts = array(
            "shiptor_id"      => $response['package'][0]['id'],
			"label_url"       => $response['package'][0]['label_url'],
			"tracking_number" => $response['package'][0]['tracking_number'],
        );
        } elseif($pack_type=='shipment') {
        	$arr_inserts = array(
				"shiptor_id"      => $response['shipment']['id'],
				"label_url"       => $response['packages'][0]['label_url'],
				"tracking_number" => $response['packages'][0]['tracking_number'],
        	);
        }
        $shopOrderParamsModel->set($order_id, $arr_inserts, false);
//waLog::dump($response,'shop/shiptor/uploadOrder/log.log');
        return $response;
    }
    #endregion

    #region private fields
    private function autoload()
    {
        $basePath = "wa-apps/shop/plugins/shiptor/lib/classes/";
        $baseName = "shiptor";

        $autoload = waAutoload::getInstance();

        //interfaces
        $autoload->add("IWeightCalculator", $basePath . "IWeightCalculator.php");
        $autoload->add("IDimensionsCalculator", $basePath . "IDimensionsCalculator.php");
        //exceptions
        $autoload->add($baseName . "Exception", $basePath . "exceptions/" . $baseName . "Exception.php");
        $autoload->add($baseName . "GetSettingsException", $basePath . "exceptions/" . $baseName . "GetSettingsException.php");
        $autoload->add($baseName . "CityKladrException", $basePath . "exceptions/" . $baseName . "CityKladrException.php");
        $autoload->add($baseName . "GetRegionException", $basePath . "exceptions/" . $baseName . "GetRegionException.php");
        $autoload->add($baseName . "GroupOperationException", $basePath . "exceptions/" . $baseName . "GroupOperationException.php");
        $autoload->add($baseName . "InvalidParamException", $basePath . "exceptions/" . $baseName . "InvalidParamException.php");
        $autoload->add($baseName . "ResponseException", $basePath . "exceptions/" . $baseName . "ResponseException.php");
        $autoload->add($baseName . "PackageWorkerException", $basePath . "exceptions/" . $baseName . "PackageWorkerException.php");
        $autoload->add($baseName . "PluginDisabledException", $basePath . "exceptions/" . $baseName . "PluginDisabledException.php");
        //classes
        $autoload->add($baseName . "Settings", $basePath . $baseName . "Settings.php");
        $autoload->add($baseName . "ProductWorker", $basePath . $baseName . "ProductWorker.php");
        $autoload->add($baseName . "Logger", $basePath . $baseName . "Logger.php");
        $autoload->add($baseName . "Helper", $basePath . $baseName . "Helper.php");
        $autoload->add($baseName . "CityWorker", $basePath . $baseName . "CityWorker.php");
        $autoload->add($baseName . "APIworker", $basePath . $baseName . "APIworker.php");
        $autoload->add($baseName . "PackageWorker", $basePath . $baseName . "PackageWorker.php");
    }
    private function getActionLinks($order, $allowedActions)
    {
        foreach ($allowedActions as &$allowedAction)
        {
            $allowedAction['href'] = str_replace('{$shiptor_id}', $order['params']['shiptor_id'], $allowedAction['href']);
            $allowedAction['href'] = str_replace('{$label_url}', $order['params']['label_url'], $allowedAction['href']);
        }

        wa()->getView()->assign('order_id', $order['id']);
        wa()->getView()->assign('shiptor_id', $order['params']['shiptor_id']);
        wa()->getView()->assign('allowedActions', $allowedActions);

        return wa()->getView()->fetch(shiptorHelper::PLUGINPATH . "templates/rightBlock.html");
    }
    private function getTitleSuffix($order)
    {
        return "<span class='shiptor-tooltip' data-title=\"Заказ создан в Shiptor\"><i class='icon16 shiptor-icon'></i></span>";
    }
    private function getInfoSection($order, $package_info, $status)
    {
        wa()->getView()->assign("shiptor_id", $order['params']['shiptor_id']);
        wa()->getView()->assign("orderHistory", $package_info['history']);
        wa()->getView()->assign("checkpoints", $package_info['checkpoints']);
        wa()->getView()->assign("status", $status);

        $shiptorDataModel = new shopOrderShiptorDataModel();
        $shiptorDataModel->addResponse($order['params']['shiptor_id'], $package_info);

        return wa()->getView()->fetch(shiptorHelper::PLUGINPATH . "templates/infoSection.html");
    }
    private function getSidebarSection()
    {
        return "<link rel=\"stylesheet\" href=\"/" . shiptorHelper::PLUGINPATH . "css/build/stylesheet.css" . "\"><script src=\"/" . shiptorHelper::PLUGINPATH . "js/build/backend.js" . "\"></script>";
    }
    #endregion
}

/**
 * Функция - алиас для метода shopShiptorPlugin::getDeliveryPointInfo
 *
 * @param $orderID int id заказа
 *
 * @return array данные о точке. Подробности в документации
 * @see shopShiptorPlugin::getDeliveryPointInfo()
 */
function getDeliveryPointInfo($orderID)
{
    return shopShiptorPlugin::getDeliveryPointInfo($orderID);
}
