<?php

class giftd extends def_module {

    /**
     * Конструктор
     */
    public function __construct() {
        parent::__construct();

        // В зависимости от режима работы системы
        if (cmsController::getInstance()->getCurrentMode() == "admin") {
            // Создаем вкладки административной панели
            $this->initTabs();
            // Подключаем классы функционала административной панели
            $this->includeAdminClasses();
        } else {
            // Подключаем классы клиентского функционала
            $this->includeGuestClasses();
        }

        $this->includeCommonClasses();
    }

    /**
     * Возвращает ссылки на форму редактирования страницы модуля и
     * на форму добавления дочернего элемента к странице.
     * @param int $element_id идентификатор страницы модуля
     * @param string|bool $element_type тип страницы модуля
     * @return array
     */
    public function getEditLink($element_id, $element_type = false) {
        return [
            false,
            $this->pre_lang . "/admin/giftd/editPage/{$element_id}/"
        ];
    }

    /**
     * Возвращает ссылку на редактирование объектов в административной панели
     * @param int $objectId ID редактируемого объекта
     * @param string|bool $type метод типа объекта
     * @return string
     */
    public function getObjectEditLink($objectId, $type = false) {
        return $this->pre_lang . "/admin/giftd/editObject/"  . $objectId . "/";
    }

    /**
     * Создает вкладки административной панели модуля
     */
    protected function initTabs() {
        $configTabs = $this->getConfigTabs();

        if ($configTabs instanceof iAdminModuleTabs) {
            $configTabs->add("config");
        }

    }

    /**
     * Подключает классы функционала административной панели
     */
    protected function includeAdminClasses() {
        $this->__loadLib("admin.php");
        $this->__implement("GiftdAdmin");

        $this->loadAdminExtension();

        $this->__loadLib("customAdmin.php");
        $this->__implement("GiftdCustomAdmin", true);
    }

    /**
     * Подключает классы функционала клиентской части
     */
    protected function includeGuestClasses() {
        $this->__loadLib("macros.php");
        $this->__implement("GiftdMacros");

        $this->loadSiteExtension();

        $this->__loadLib("customMacros.php");
        $this->__implement("GiftdCustomMacros", true);
    }

    /**
     * Подключает общие классы функционала
     */
    protected function includeCommonClasses() {
        $this->__loadLib("GiftdApi.php");
        $this->__implement("Giftd_Client");

        $this->loadCommonExtension();
        $this->loadTemplateCustoms();
    }

    public function getGift() {
        return unserialize(base64_decode($this->giftd_object));
    }

    public function setGiftd($result = false) {
        if ($result) {
            $this->giftd_object = $result;
        }
    }

    public function applyGiftd(iUmiEventPoint $event) {
        if ($event->getMode() === "before") return true;

        if ($event->getMode() === "after") {
            $emarket = cmsController::getInstance()->getModule('emarket');
            $order = $emarket->getBasketOrder();
            $giftdParams = unserialize(base64_decode($order->giftd_object));
	            

            if (!empty($giftdParams)) {

            	$discount_type = ($giftdParams['discount_percent']) ? 'percent' : 'simple';
            	$discount_value = ($giftdParams['discount_percent']) ? $giftdParams['discount_percent'] : $giftdParams['amount_available'];
            	$min_total = $giftdParams['min_amount_total'];
                $discount = $giftdParams['cannot_be_used_on_discounted_items'];
                $PriceWithoutDiscountProducts = '';
                $actualPrice = &$event->getRef('actualPrice');

                print_r($actualPrice);

                if (!$min_total) {
                    // если не задана минимальная сумма, просто вычитаем скидку
                    $giftdPrice = $actualPrice;
                    $giftdPrice = $giftdPrice - $this->getDiscountValue($discount_type, $actualPrice, $discount_value, $giftdParams['amount_available']);
                    $actualPrice = ($giftdPrice > 0) ? $giftdPrice : '0';

                    $giftdParams['applied'] = true;

                } elseif ($min_total <= ($actualPrice - $order->delivery_price)) {
                    // если общая сумма заказа больше допустимой для скидки минимальной суммы
                    if (!$discount) {
                        $giftdPrice = &$event->getRef('actualPrice');
                        $giftdPrice -= $this->getDiscountValue($discount_type, $actualPrice, $discount_value, $giftdParams['amount_available']);
                        $actualPrice = ($giftdPrice > 0) ? $giftdPrice : '0';
                        $giftdParams['applied'] = true;
                    } elseif ($discount) {
                        // убираем из общей суммы заказа товары со скидкой
                        foreach ($order->getItems() as $item) {
                            if ($item->getDiscountValue() == 0) {
                                $PriceWithoutDiscountProducts += $item->getTotalActualPrice();
                            }
                        }
                        // если общая сумма заказа без товаров со скидкой больше допустимой для скидки минимальной суммы
                        if ($min_total < $PriceWithoutDiscountProducts) {
                            $giftdPrice = &$event->getRef('actualPrice');
                            $giftdPrice -= $this->getDiscountValue($discount_type, $actualPrice, $discount_value, $giftdParams['amount_available']);
                            $actualPrice = ($giftdPrice > 0) ? $giftdPrice : '0';
                            $giftdParams['applied'] = true;
                        }
                    }
                }

                $order->giftd_object = base64_encode(serialize($giftdParams));
            }
        }
    }

    private function getDiscountValue($discount_type, $actualPrice, $discount_val, $amount_available) {
    	switch ($discount_type) {
        	case 'percent':
        		$discount_value = ($actualPrice/100) * $discount_val;
        		if (!empty($amount_available)) {
                    $discount_value = ($discount_value < $amount_available) ? $discount_value : $amount_available;
        		}
        		break;
        	
        	case 'simple':
        		$discount_value = $discount_val;
        		break;
        }

        return $discount_value;
    }

    private function getAmountAvalible($actualPrice, $discount_val) {
        return ($actualPrice/100) * $discount_val;
    }

    public function processGiftd(iUmiEventPoint $event) {
        if ($event->getMode() === "before") return true;

        if ($event->getMode() === "after") {
            $order = &$event->getRef('order');

            if ($order->getCodeByStatus($event->getParam('new-status-id')) == 'waiting') {
                $giftdParams = unserialize(base64_decode($order->giftd_object));
                if (!empty($giftdParams)) {
                    $regedit = regedit::getInstance();
                    cmsController::getInstance()->getModule('giftd');
                    $client = new Giftd_Client($regedit->getVal("//modules/giftd/user_id"), $regedit->getVal("//modules/giftd/api_key"));
                    if (substr($giftdParams['token'], 0, 2) == $regedit->getVal("//modules/giftd/partner_token_prefix")) {
                        $amount_available = ($giftdParams['discount_percent']) ? $this->getAmountAvalible($order->getActualPrice(), $giftdParams['discount_percent']) : $giftdParams['amount_available'];
                        $amount_total = $order->getActualPrice() + $amount_available;
                        $external_id = $regedit->getVal("//modules/giftd/user_id") .'_'. time() . '_' . $order->getNumber();
                        $result = $client->charge($giftdParams['token'], $order->getActualPrice(), $amount_total, $external_id);
                    }
                }  
            }
        }

    }

    public function updateOrderGiftd(iUmiEventPoint $event)
    {
        $order = &$event->getRef('order');
        $giftdParams = unserialize(base64_decode($order->giftd_object));

        $arItems = array();
        foreach ($order->getItems() as $key => $item) {
            $element = $item->getItemElement();
            $arItems[$key] = array(
                'id' => $element->id,
                'title' => $element->name,
                'quantity' => $item->getAmount(),
                'price' => $item->getItemPrice()
            );
        }
        $data = array(
            'id' => $order->getNumber(),
            'amount_total' => $order->getActualPrice(),
            'items' => $arItems,
            'promo_code' => $giftdParams['token'],
            'cookies' => $_COOKIE,
            'created' => $order->order_date->timestamp,
            'updated' => $order->getObject()->getUpdateTime(),
            'raw_data ' => serialize($order)
			);

        $regedit = regedit::getInstance();
        cmsController::getInstance()->getModule('giftd');
        $client = new Giftd_Client($regedit->getVal("//modules/giftd/user_id"), $regedit->getVal("//modules/giftd/api_key"));
        $result = $client->query('cmsModule/orderUpdate', array('user_id' => $regedit->getVal("//modules/giftd/user_id"), 'api_key' => $regedit->getVal("//modules/giftd/api_key"), 'data' => $data, 'cms' => 'umi'));
    }

    public function loadPage(iUmiEventPoint $event) {
        if ($event->getMode() === "before") {
            $regedit = regedit::getInstance();
            if (!empty($regedit->getVal("//modules/giftd/user_id")) && !empty($regedit->getVal("//modules/giftd/api_key"))) {
                $this->updateJsCode();
                $class = $regedit->getVal("//modules/giftd/input_class");
                $buffer = &$event->getRef('buffer');

                $input_html = '<div class="giftd-coupon">
                                    <div class="giftd-coupon-title">Скидка по купону</div>
                                    <input type="text" name="coupon" />
                                    <button class="giftd-check-coupon">Применить</button>
                               </div>';

                $js_code = $this->getJsCode();

               
                $buffer = preg_replace('/(<body.*>)/', '$1<script>'.$js_code.'</script><script src="/giftd_assets/js/giftd.js" type="text/javascript"></script>', $buffer, 1);

                $buffer = preg_replace('/(<div class=\"'.$class.'\">)/', $input_html.'$1', $buffer, 1);

            }
        }
    }

    /**
     * Создаем таблицу под js код
     */

    public static function createTableToJsCode() {
        $sql = "CREATE TABLE IF NOT EXISTS `giftd_js_code` ( `ID` INT(10) NULL DEFAULT '1', `js_code` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, PRIMARY KEY (`ID`)) ENGINE = InnoDB";
        $connection = ConnectionPool::getInstance()->getConnection();
        $result = $connection->queryResult($sql);

        return false;
    }


    /**
     * Получаем актуальный js_code
     */

    public static function getActualJsCode($user_id, $api_key)
    {
        $client = new Giftd_Client($user_id, $api_key);
        $result = $client->query('partner/getJs');
        $code = isset($result['data']['js']) ? $result['data']['js'] : null;
        if ($code) {
            return $code;
        }

        return false;
    }

    /**
     * Записываем js код в таблицу БД
     */

    public static function setJsCode($code) {
        $sCode= base64_encode($code);
        $sql = "INSERT INTO `giftd_js_code` SET `js_code`='".$sCode."', `ID` = 1 ON DUPLICATE KEY UPDATE `js_code`='".$sCode."', `ID` = 1";
        $connection = ConnectionPool::getInstance()->getConnection();
        $result = $connection->queryResult($sql);

        return false;
    }

    /**
     * Получаем js код из талицы БД
     */

    public static function getJsCode() {
        $sql = "SELECT `js_code` FROM `giftd_js_code` WHERE `ID` = 1";
        $connection = ConnectionPool::getInstance()->getConnection();
        $result = $connection->queryResult($sql);
        $singleRow = $result->fetch();

        if ($singleRow['js_code']) {
            return base64_decode($singleRow['js_code']);
        }

        return false;
    }

    public function updateJsCode() {
        $regedit = regedit::getInstance();
        if (isset($_REQUEST['giftd-update-js']) && $_REQUEST['giftd-update-js'] == $regedit->getVal("//modules/giftd/api_key")) {
            try {
                $client = new Giftd_Client($regedit->getVal("//modules/giftd/user_id"), $regedit->getVal("//modules/giftd/api_key"));
                $result = $client->query('partner/getJs');
                $code = isset($result['data']['js']) ? $result['data']['js'] : null;
                if ($code) {
                    giftd::setJsCode($code);
                }
            } catch (Exception $e) {

            }
        }
    }
};