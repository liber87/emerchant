//<?php
/**
 * emerchant
 *
 * <b>1.0</b> Управление магазином
 *
 * @category    plugin
 * @internal    @events OnWebPagePrerender,OnPageNotFound
 * @internal    @modx_category Shop
 * @internal    @properties &pricetv=TV-параметр с ценой;string;price&subject=Тема письма при уведомлении;string; &emailTo=Email для приема уведомлений;string; &emailFrom=Ящик откуда отправляются письма;string; &methodSend=Метод для отправки писем;string;;название сниппета, производящего отправку &separ1=<b>Письма админу</b>;string; &order.admin.order.tpl=Код письма о заказе;string; &order.admin.order.outer.tpl=Код таблицы с заказом;string; &order.admin.order.row.tpl=Код строки таблицы с заказом;string; &separ3=<b>Письма пользователю</b>;string; &order.user.order.tpl=Код письма о заказе;string; &order.user.order.outer.tpl=Код таблицы с заказом;string; &order.user.order.row.tpl=Код строки таблицы с заказом;string; &separ2=<b>Админка</b>;string; &module.order.info.tpl=Общая страница заказа;string; &module.order.owner.tpl=Обертка для таблицы товаров;string; &module.order.row.tpl=Строка в таблице товаров;string; &module.table.outer.tpl=Обертка для таблицы заказов;string; &module.table.row.tpl=Строка для таблицы заказов;string; &separ99=<b>Прочее</b>;string; &numberFormat=Фомат цен;string;2|.|;знаков после запятой|знак для запятой|разделитель разрядов &allowFloatCount=Разрешить дробное количество;list;true,false; &form_fields=Поля формы;string;name==Имя||phone==Телефон||address==Адрес доставки||delivery==Способ оплаты||payment==Способ оплаты;Название поля==Отображаемое имя &statusNames=Статусы;string;Новый==C5CAFE||Принят к оплате==B1F2FC||Отправлен==F3FDB0||Выполнен==BEFAB4||Отменен==FFAEAE||Оплата получена==FFE1A4;
 * @internal    @disabled 0
 * @internal    @installset base
 */
require MODX_BASE_PATH."assets/plugins/emerchant/plugin.emerchant.php";
