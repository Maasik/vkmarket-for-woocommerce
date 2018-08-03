=== VKMarket for WooCommerce ===
Contributors: alekseysolo
Tags: woocommerce, vk, vkmarket, vkontakte, vk market, wp ecommerce, eshop, shop
Requires at least: 4.4
Tested up to: 4.9
Stable tag: 0.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Автоматическая синхронизация магазина на WooCommerce c разделом Товары ВКонтакте.

== Description ==

Включите синхронизацию с группой ВК и при *создании*, *обновлении* или *удалении* товара с сайта он будет автоматически создан, обновлен или удален из раздела Товары в вашей группе ВКонтакте.

**Возможности**

* **Автоматическая** синхронизация товаров на сайте с разделом **Товары ВКонтакте** (создание, обновление, удаление).
* **!!!** Управление описанием товара в разделе товары ВК: **ссылка на товар на сайте**, краткое и полное описание товара.
* Поддержка статуса "*Нет в наличии*" (в группе ВК товар будет отмечен как "*Товар недоступен*").
* **Синхронизация категорий** товаров на сайте и в группе ВК (какой категории в ВК соответствует та или иная категория на сайте).

**PRO версия** [VKMarket PRO for WooCommerce](http://ukraya.ru/vkmarket-pro-for-woocommerce "Товары ВКонтакте PRO для WooCommerce")

* Массовый **экспорт**, **удаление**, **обновление** товаров из раздела Товары ВК по критериям.
* **!!!** **Подборки товаров** ВКонтакте: создание, изменение, удаление подборок, поддержка псевдовложенных подборок. Поддерживается **массовое** создание и удаление.
* **!!!** Товары: добавление в подборку, удаление.
* Управление описанием товара в разделе товары ВК: **!!!** **ссылка на товар в корзине** (при клике, товар автоматически помещается в корзину и открывается страница оформления заказа), **атрибуты товара** (product attributes, свойства), **new:** **вариации** товара (variations).
* **new:** Автоматичеcкая коррекция изображений товара, если размер не соответствует требованиям ВК.
* **new:** Автоматическое добавление к товару изображения по умолчанию, если оно отсутствует (ВК не принимает товары без фото).

**English**

Allows to create (and synchronize) shop in social network vk.com from woocommerce shop.

**Спасибо**

* [Woocommerce - свой интернет магазин на Wordpress](https://vk.com/woocommerce_russian "Woocommerce - свой интернет магазин на Wordpress") ВКонтакте.

== Frequently Asked Questions ==

[Документация](http://ukraya.ru/vkmarket-for-woocommerce/documentation "Документация по работе с плагином") по работе с плагином Товары ВКонтакте PRO для WooCommerce.

Техническая [поддежка](https://vk.me/wordpressvk "Техническая поддержка") и помощь.

== Installation ==

1. Установите и активируйте плагин.
1. В группе ВК, в меню *Управление сообществом* - *Разделы* - установите опцию *Товары:включены*.
1. В меню плагина *Товары ВК* - *Настройки VK API*: создайте приложение ВК и подключите его к сайту.
1. В меню плагина *Товары ВК* - *Настройки*: введите адрес группы ВК, включите синхронизацию и задайте категорию в ВК в которую будут отправляться товары с сайта.
1. В меню woocommerce *Товары* - *Категории*: откройте любую категорию в режиме редактирования и установите, какой именно категории в ВК она соответствует.
1. Откройте любой товар в режиме редактирования и нажмите кнопку *Обновить*. Товар **будет опубликован в разделе Товары** в вашей группе ВКонтакте.

== Changelog ==

= 0.8 / 2018-06-08 =
* Optimized VK API requests limit. / 2017-02-20 / 0.7.01
* Added timeout option. / 2017-02-20 / 0.7.02
* Fixed php 7.1 compatibility. / 2017-04-09 / 0.7.03
* Fixed product duplication (only without image!). / 2017-06-23 / 0.7.04
* Added reset errors for products compatibility. / 2017-06-30 / 0.7.05
* Added limit for product photo uploading. / 2017-07-24 / 0.7.06
* Added captcha processing for vkm_vkapi_market_add. / 2017-07-24 / 0.7.07
* Fixed requirements checking for multisite installation. / 2017-09-09 / 0.7.08
* Fixed vkm_init for multisite installation. / 2017-09-10 / 0.7.09
* Fixed outofstock status when edit existing products. / 2017-09-11 / 0.7.11
* Fixed global wp_version definition. / 2017-10-14 / 0.7.12
* Fixed outofstock status when edit existing variable products. / 2017-11-10 / 0.7.13
* Fixed conflict with getting token when easy vkontakte connect is installed. / 2018-05-29 / 0.7.14
* Поправлено отображение информационных блоков в мобильной версии. / 2018-06-07 / 0.7.15
* Добавлено меню Помощь с подробным описанием настроек плагина. / 2018-06-08 / 0.7.16
* Добавлены ссылка на меню Помощь и wp-pointer для меню Помощь. / 2018-06-08 / 0.7.17
* Добавлена ссылка на техническую поддержку через vk.me. / 2018-06-08 / 0.7.18


= 0.7 / 2016-01-20 =
* Added price delimiter correction. / 2016-03-22 / 0.6.02
* Added some filters (for product description). / 2016-03-24 / 0.6.03
* Fixed link to product on VK (now only for products). / 2016-04-14 / 0.6.04
* Added Product Delete from VK checkbox. / 2016-04-14 / 0.6.05
* Added delay to correspond vk requests limits. / 2016-05-20 / 0.6.06
* Added export product with sale price. / 2016-07-03 / 0.6.07
* Fixed a bug in which new item are not added to vk. / 2016-07-05 / 0.6.08
* Product title length count in cp1251. / 2016-07-24 / 0.6.09
* Added compatibility with wordpress v. < 4.4  / 2016-08-11 / 0.6.10
* Save vkm_export error in poduct meta.  / 2016-08-31 / 0.6.11
* Fixed url to created apps in VK.  / 2016-12-30 / 0.6.12
* Prevent to publish identical product images.  / 2017-01-13 / 0.6.13
* Product status in VK will be in_stock, if at least one of the variations has the status in_stock.  / 2017-01-13 / 0.6.14
* Added compatibility with vkmarket-pro-for-woocommerce v.0.7.  / 2017-01-20 / 0.6.15

= 0.6 / 2016-03-18 =
* Added vkm_upload_photo_attachment_meta filter. / 2016-03-03 / 0.5.03
* Added vkm_upload_photos_id filter. / 2016-03-18 / 0.6
* Fixed bug with product main image cropping. / 2016-03-20 / 0.6.01

= 0.5 / 2016-02-16 =
* Added link to product in vk group on product edit page. / 2016-02-10 / 0.21
* Reupload photo if post does not have vk_item_id. Affected if product incorrect deleted from VK. / 2016-02-19 / 0.5.01
* Fixed dependency handling. / 2016-02-19 / 0.5.02

= 0.2 / 2016-02-07 =
* Added mask for product description (content, excerpt, link).

= 0.1 / 2016-01-20 =
* First stable release.