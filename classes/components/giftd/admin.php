<?php

class GiftdAdmin {

    use baseModuleAdmin;
    /**
     * @var giftd $module
     */
    public $module;

    /**
     * Возвращает список страниц
     * @return bool
     * @throws coreException
     * @throws selectorException
     */
    public function pages() {
        // Устанавливаем тип данных - список
        $this->setDataType('list');
        // Устанавливаем тип операции - просмотр
        $this->setActionType('view');

        // Если метод вызван не через /.xml - ничего не возвращаем
        if ($this->module->ifNotXmlMode()) {
            $this->setDirectCallError();
            $this->doData();
            return true;
        }

        // Получаем настройки постраничного вывода
        $limit = getRequest('per_page_limit');
        $pageNumber = (int) getRequest('p');
        $offset = $limit * $pageNumber;

        // Делаем выборку страниц модуля
        $pages = new selector('pages');
        $pages->types('object-type')->name('giftd', 'page');
        $pages->limit($offset, $limit);

        // Применяем фильтры и сортировку
        selectorHelper::detectHierarchyFilters($pages);
        selectorHelper::detectWhereFilters($pages);
        selectorHelper::detectOrderFilters($pages);

        $result = $pages->result();
        $total = $pages->length();

        // Устанавливаем результаты работы метода
        $this->setDataRange($limit, $offset);
        $data = $this->prepareData($result, 'pages');
        $this->setData($data, $total);
        $this->doData();
    }

    /**
     * Возвращает данные для построения формы добавления страницы модуля.
     * Если передан ключевой параметр $_REQUEST['param2'] = do, то добавляет страницу.
     * @throws coreException
     * @throws expectElementException
     * @throws wrongElementTypeAdminException
     */
    public function addPage() {
        // Валидируем родительскую страницу
        $parent = $this->expectElement('param0');
        $type = (string) getRequest("param1");
        $mode = getRequest('param2');

        // Оформляем данные
        $inputData = [
            'type'		=> $type,
            'parent'	=> $parent,
            'type-id'	=> getRequest('type-id'),
            'allowed-element-types' => [
                'page'
            ]
        ];

        // Если передан ключевой параметр
        if ($mode == 'do') {
            // Добавляем страницу
            $this->saveAddedElementData($inputData);
            // Делаем перенаправления, в зависимости от режима кнопки "Добавить"
            $this->chooseRedirect();
        }

        // Устанавливаем тип данных - форма
        $this->setDataType('form');
        // Устанавливаем тип операции - создание
        $this->setActionType('create');
        // Устанавливаем результаты работы метода
        $data = $this->prepareData($inputData, 'page');
        $this->setData($data);
        $this->doData();
    }

    /**
     * Возвращает данные для построения формы редактирования страницы модуля.
     * Если передан ключевой параметр $_REQUEST['param1'] = do, то сохраняет изменения страницы.
     * @throws coreException
     * @throws expectElementException
     * @throws wrongElementTypeAdminException
     */
    public function editPage() {
        // Валидируем редактируемую страницу
        $element = $this->expectElement('param0');
        $mode = (string) getRequest('param1');

        // Оформляем данные
        $inputData = [
            'element'	=> $element,
            'allowed-element-types' => [
                'page'
            ]
        ];

        // Если передан ключевой параметр
        if ($mode == "do") {
            // Сохраняем изменения страницы
            $this->saveEditedElementData($inputData);
            // Делаем перенаправления, в зависимости от режима кнопки "Сохранить"
            $this->chooseRedirect();
        }

        // Устанавливаем тип данных - форма
        $this->setDataType('form');
        // Устанавливаем тип операции - редактирование
        $this->setActionType('modify');
        // Устанавливаем результаты работы метода
        $data = $this->prepareData($inputData, 'page');
        $this->setData($data);
        $this->doData();
    }

    /**
     * Возвращает настройки модуля.
     * Если передан ключевой параметр $_REQUEST['param0'] = do,
     * то сохраняет настройки.
     * @throws coreException
     */
    public function config() {
        $regedit = regedit::getInstance();
        $params = Array(
            'module' => array(
                'string:user_id'    => null,
                'string:api_key'    => null,
                'string:partner_code' => null,
                'string:partner_token_prefix' => null,
                'string:input_class' => null
            )
        );

        if (getRequest('param0') == 'do') {
            try {
                $params = $this->expectParams($params);

                // если изменился api_key вызываем метод cmsModule/install
                if ($regedit->getVal("//modules/giftd/api_key") != $params['module']['string:api_key'] && !empty($params['module']['string:api_key']) && !empty($params['module']['string:user_id'])) {
                    $jsCode = giftd::getActualJsCode($params['module']['string:user_id'], $params['module']['string:api_key']);
                    if ($jsCode != giftd::getJsCode()) {
                        giftd::setJsCode($jsCode);
                    }

                    $typesCollection = umiObjectTypesCollection::getInstance();
                    $typeId = $typesCollection->getBaseType('users', 'user');
                    $objectsCollection = umiObjectsCollection::getInstance();
                    $supervisor = $objectsCollection->getObjectByGUID('system-supervisor');

                    $url = 'http://'.$_SERVER['HTTP_HOST'];
                    $name = $supervisor->getPropByName('fname')->getValue() . ' ' . $supervisor->getPropByName('lname')->getValue();
                    $phoneObject = $supervisor->getPropByName('phone');
                    $phone ='';
                    if ($phoneObject) $phone = $phoneObject->getValue();

                    $data =  [
                        'email' => $supervisor->getPropByName('e-mail')->getValue(),
                        'phone' => $phone,
                        'name' => $name,
                        'url' => $url,
                        'cms' => 'umi',
                        'version' => '2.14'
                    ];

                    $gClient = new Giftd_Client($params['module']['string:user_id'] ,$params['module']['string:api_key']);

                    $result = $gClient->query("cmsModule/install", $data);
                }

                $regedit->setVar('//modules/giftd/user_id',  	   $params['module']['string:user_id']);
                $regedit->setVar('//modules/giftd/api_key',  	   $params['module']['string:api_key']);
                $regedit->setVar('//modules/giftd/partner_code',  	   $params['module']['string:partner_code']);
                $regedit->setVar('//modules/giftd/partner_token_prefix',  	   $params['module']['string:partner_token_prefix']);
                $regedit->setVar('//modules/giftd/input_class',  	   $params['module']['string:input_class']);

            } catch(Exception $e) {}

            $this->chooseRedirect();
        }

        $params['module']['string:user_id']   = $regedit->getVal("//modules/giftd/user_id");
        $params['module']['string:api_key']   = $regedit->getVal("//modules/giftd/api_key");
        $params['module']['string:partner_code']   = $regedit->getVal("//modules/giftd/partner_code");
        $params['module']['string:partner_token_prefix']   = $regedit->getVal("//modules/giftd/partner_token_prefix");
        $params['module']['string:input_class']   = $regedit->getVal("//modules/giftd/input_class");


        $this->setDataType('settings');
        $this->setActionType('modify');
        $data = $this->prepareData($params, 'settings');
        $this->setData($data);
        $this->doData();
    }

    /**
     * Удаляет страницы модуля
     * @throws coreException
     * @throws expectElementException
     * @throws wrongElementTypeAdminException
     */
    public function deletePages() {
        // Получаем идентификатор страницы
        $elements = getRequest('element');

        if (!is_array($elements)) {
            $elements = [$elements];
        }

        // Обходим массив идентификаторов
        foreach ($elements as $elementId) {
            // Валидируем страницу
            $element = $this->expectElement($elementId, false, true);

            // Оформляем данные страницы
            $params = [
                'element' => $element,
                "allowed-element-types" => [
                    'page'
                ]
            ];

            // Удаляем станицу
            $this->deleteElement($params);
        }

        // Устанавливаем тип данных - список
        $this->setDataType('list');
        // Устанавливаем тип операции - просмотр
        $this->setActionType('view');
        // Устанавливаем результаты работы метода
        $data = $this->prepareData($elements, 'pages');
        $this->setData($data);
        $this->doData();
    }

    /**
     * Переключает активность страниц модуля
     * @throws coreException
     * @throws expectElementException
     * @throws requreMoreAdminPermissionsException
     * @throws wrongElementTypeAdminException
     */
    public function activity() {
        // Получаем идентификатор страницы
        $elements = getRequest('element');

        if (!is_array($elements)) {
            $elements = [$elements];
        }

        // Получаем флаг активности
        $is_active = getRequest('active');

        // Обходим массив идентификаторов
        foreach ($elements as $elementId) {
            // Валидируем страницу
            $element = $this->expectElement($elementId, false, true);

            // Оформляем данные страницы
            $params = [
                'element' => $element,
                'activity' => $is_active,
                'allowed-element-types' => [
                    'page'
                ]
            ];

            // Переключаем активность
            $this->switchActivity($params);
            $element->commit();
        }

        // Устанавливаем тип данных - список
        $this->setDataType('list');
        // Устанавливаем тип операции - просмотр
        $this->setActionType('view');
        // Устанавливаем результаты работы метода
        $data = $this->prepareData($elements, "pages");
        $this->setData($data);
        $this->doData();
    }
}