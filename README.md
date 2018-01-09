# giftd-umi-cms
BitRewards / GIFTD module for UMI.CMS

English Instruction
===============

Installing module
--------------------------


1. [Download module archive](https://github.com/BitRewards/UmiCmsModule) from repository.
2. Open config.ini, which is in the website root folder, finr parameter "buffer-send-event-enable" and set it to "1".
3. Upload archive contents through FTP to the root of your website. Replace all existing files if needed.
4. Navigate to the admin area: "Modules" > "Configuration" > "Modules". In the field "Installation file path" put classes/components/giftd/install.php and click "Install".
5. Open URL /admin/giftd/config/ and fill in the settings:
* GIFTD User ID
* GIFTD API Key
* Giftd Partner Code
* Gift Cards Codes Prefix
6. Click "Save"



Russian Instruction
===============

Установка модуля в магазин
--------------------------


1. [Скачайте архив модуля](https://github.com/BitRewards/UmiCmsModule) из репозитория.
2. Откройте для редактирования файл config.ini, который располагается в корневой папке сайта, найдите параметр «buffer-send-event-enable» и установите ему значение «1».
3. Загрузите содержимое архива через FTP в корень вашего сайта. Замените все существующие файлы при необходимости.
4. Перейдите в админ панель сайта «Модули» > «Конфигурация» > «Модули». В поле «Путь до инсталляционного файла» наберите classes/components/giftd/install.php и нажмите «Установить».
5. Откроте страницу ваш_сайт/admin/giftd/config/ и заполните поля настроек:
* ID пользователя GIFTD
* Ключ API GIFTD
* Код партнера Giftd
* Префикс кодов подарочных карт
* Класс к которому привязывать поле ввода кода купона – класс блока, после которого в корзине будет добавлен блок для ввода купона. Данный класс обязательно должен быть уникальным.
6. Нажмите «Сохранить»
