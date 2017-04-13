<?

/**
 * @var array $INFO реестр модуля
 */

require './classes/components/giftd/class.php';
giftd::createTableToJsCode();

$typesCollection = umiObjectTypesCollection::getInstance();
$typeId = $typesCollection->getTypeIdByHierarchyTypeName('emarket', 'order');
$fieldsCollection = umiFieldsCollection::getInstance();
$type = umiObjectTypesCollection::getInstance()->getType($typeId);
$fieldId = $fieldsCollection->addField('giftd_object', 'GiftdObject', 20);
$group = $type->getFieldsGroupByName('order_props');
$group->attachField($fieldId);

$INFO = [
    'name' => 'giftd', // Имя модуля
    'config' => '1', // У модуля есть настройки
    'default_method' => 'page', // Метод по умолчанию в клиентской части
    'default_method_admin' => 'config', // Метод по умолчанию в административной части
    'func_perms' => 'Группы прав на функционал модуля', // Группы прав
    'func_perms/guest' => 'Гостевые права', // Гостевая группа прав
    'func_perms/admin' => 'Административные права', // Административная группа прав
    'paging/' => 'Настройки постраничного вывода', // Группа настроек
];

/**
 * @var array $COMPONENTS файлы модуля
 */
$COMPONENTS = [
    './classes/components/giftd/admin.php',
    './classes/components/giftd/class.php',
    './classes/components/giftd/customAdmin.php',
    './classes/components/giftd/customMacros.php',
    './classes/components/giftd/i18n.php',
    './classes/components/giftd/install.php',
    './classes/components/giftd/lang.php',
    './classes/components/giftd/macros.php',
    './classes/components/giftd/permissions.php',
    './classes/components/giftd/GiftdApi.php',
    './classes/components/giftd/custom_events.php'
];
