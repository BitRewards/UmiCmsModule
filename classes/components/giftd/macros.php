<?php

class GiftdMacros
{
    /**
     * @var emarket $module
     */
    public $module;

    /**
     * Возвращает данные страницы
     * @param string $template имя шаблона (для tpl)
     * @param bool|int $pageId идентификатор страниц, если не передан - возьмет текущую страницу.
     * @return mixed
     * @throws publicAdminException
     */
    public function page($template = 'default', $pageId = false)
    {
        $module = cmsController::getInstance()->getModule('emarket');

        // Загружаем блоки шаблона для tpl
        list($templateBlock) = Giftd::loadTemplates('giftd/' . $template, 'block');

        // Если страница не передана -  возьмем текущую
        if (!is_numeric($pageId)) {
            $pageId = cmsController::getInstance()->getCurrentElementId();
        }

        $umiHierarchy = umiHierarchy::getInstance();
        $page = $umiHierarchy->getElement($pageId);

        // Если страница не существует - кинем исключение
        if (!$page instanceof iUmiHierarchyElement) {
            throw new publicAdminException(getLabel('error-page-not-found', 'giftd'));
        }

        // Формируем нужные данные
        $pageData = [
            'id' => $pageId,
            'link' => $umiHierarchy->getPathById($pageId)
        ];

        // Уведомляем панель редактирования о доступной странице
        giftd::pushEditable('giftd', 'page', $pageId);

        // Применяем шаблон и возвращаем результат
        return giftd::parseTemplate($templateBlock, $pageData, $pageId);
    }

    /**
     * Возвращает список страниц
     * @param string $template имя шаблона (для tpl)
     * @param bool|int $limit ограничение на количество, если не передано - возьмет из настроек.
     * @return mixed
     * @throws selectorException
     */
    public function pagesList($template = 'default', $limit = false)
    {
        // Загружаем блоки шаблона для tpl
        list($templateBlock, $templateLine, $templateEmpty) = giftd::loadTemplates(
            'giftd/' . $template,
            'pages_list_block',
            'pages_list_line',
            'pages_list_block_empty'
        );

        // Если не передано ограничение - возьмем из настроек модуля в реестре
        if (!is_numeric($limit)) {
            $limit = (int)regedit::getInstance()->getVal($this->module->pagesLimitXpath);
        }

        // Получаем настройки постраничного вывода
        $pageNumber = (int)getRequest('p');
        $offset = $limit * $pageNumber;

        // Делаем выборку
        $pages = new selector('pages');
        $pages->types('object-type')->name('giftd', 'page');
        $pages->limit($offset, $limit);

        $result = $pages->result();
        $total = $pages->length();

        // Если страниц нет -  применяем шаблон $templateEmpty  и возвращаем результат
        if ($total == 0) {
            return dummy::parseTemplate($templateEmpty, []);
        }

        $items = [];
        $data = [];

        /**
         * @var iUmiHierarchyElement|iUmiEntinty $page
         */
        // Формируем данные каждой страницы и применяем к каждой шаблон $templateLine
        foreach ($result as $page) {
            $item = [];
            $item['attribute:id'] = $page->getId();
            $item['attribute:name'] = $page->getName();
            $items[] = giftd::parseTemplate($templateLine, $item);
        }

        // Формируем общие данные работы макроса
        $data['subnodes:items'] = $items;
        $data['total'] = $total;

        // Применяем общий шаблон $templateBlock и возвращаем результат
        return giftd::parseTemplate($templateBlock, $data);
    }

}