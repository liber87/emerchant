## eMerchant - решение для интернет-магазина Evolution CMS.
### Карточка товара:
```html
<div class="em-item">
	<form method="post">
		<input type="hidden" name="em-id" value="5">
		<input type="hidden" name="em-count" value="1">
		<button type="submit">В корзину</button>
	</form>
</div>
```
**Либо**
```html
<div class="em-item">
	<button type="button" data-em-id="5" class="add-to-cart">В корзину</button>
</div>
```
После добавления в корзину срабатывает js-функция emAddPostition(params);
**Пример:**
```html
<script>
function emAddPostition(data){	
	alert('Вы добавили товар с названием "'+data['em-name']+'" и с id '+data['em-id']);
}
</script>
```
Также срабатывает событие: OnEmerchantMakePosition
**Пример плагина (ставим стоимость товара в зависимости от веса и добавляем дополнительную цену коробки):**
```php
//<?php
//position - то что сформировал скрипт
//data то, что пришло в post
$weights = array('1 кг'=>array('tv_id'=>5,'box'=>25), '1.5 кг'=>array('tv_id'=>6,'box'=>30), '0.5 кг'=>array('tv_id'=>7,'box'=>0),'2 кг'=>array('tv_id'=>29,'box'=>30));

$position['price'] = $modx->db->getValue('Select `value` from '.$em->tvtable.' where `contentid`='.$position['id'].' and tmplvarid='.$weights[$data['weight']]['tv_id']);
$position['price.add'] = array('box'=>$weights[$data['weight']]['box']);
$modx->event->setOutput(json_encode($position));
```
### Корзина (вызывается в любом месте в любом количестве):
```html
<div class="em-cart">
[!emerchant?			
&ownerTPL=`shopCartOuter` //Обертка
&tpl=`shopCartRow` //Строка
&noneTPL=`@CODE: В вашей корзине нет товаров` //Пустая корзина
&prepare=`shopPrepare` //Обработчик если нужен
!]	
</div>
```
**Чанк shopCartOuter:**
```html
[+cart+]	
<p><b>Итого:</b> [+price.full+] руб.</p>
<p><b>Доставка:</b> [+delivery+] руб.</p>
<p><b>Скидка:</b> [+sale+] руб.</p>
<p><b>К оплате:</b> [+price.final+] руб.</p>
```
**Здесь:**
[+cart+] - корзина

[+price.base+] - базовая цена товаров

[+price.full+] - общая базовая цена товаров

[+price.add+] - общая цена добавок

[+delivery+] - цена доставки

[+sale+] - цена скидки

[+price.final+] - конечная цена 

[+count.items+] - количество позиций


**Чанк shopCartRow:**
```html
<div style="position:relative;" class="em-cart-item" data-hash="[+hash+]">
	<div class="cart-left">
		<img src="[+image+]">
		<a class="em-del">удалить</a>
	</div>
	<div class="cart-right">
		<h4 class="cart-name">[+pagetitle+] [+add.weight+]</h4>
		<p class="cart-composition">[+introtext+]</p>	
	</div>	
	<div style="float:right;">
		<span>[+price.base+] + [+price.add.box+]</span>
		
		<span class="em-minus">-</span>
		<input class="em-count-value" value="[+count+]">
		<span class="em-plus">+</span>
		
		<span class="subtotal">[+price.options.full.total+] руб. </span>
	</div>	
</div>
```
**Здесь:**
.em-cart-item - класс для строчки, обязательный аргумент для строчки в корзине: data-hash="[+hash+]" (для работы js)

.em-del, .em-plus, .em-minus - клик на соответствующие действия

.em-count-value - проверяет изменение события, в нем же пишется количество


[+hash+] - необходимо для корректной работы js

[+id+] - id позиции

[+count+] - количество

[+add.название+] - дополнительный параметр, передающийся через форму

[+price.base+] - базовая цена

[+price.add.название+] - цена дополнительной опции (см.пример плагина)

[+price.options.full.total+] - цена со всеми добавками умноженная на число позиций


**Сниппет shopPrepare:**
```php
<?php
$data['image'] = $modx->runSnippet('phpthumb',array('input'=>$data['image'],'options'=>'w=120,h=120,far=C,bg=FFFFFF'));
return $data;
?>
```

### Оформление закза:
```html
<form method="post" class="em-order-form" action="emerchant/act?saveorder" data-redirect="[~56~]" [!emerchant? &noneTPL=`@CODE: style="display:none;"`!]>
<input type="email" name="email" placeholder="Введите ваш email">
<button type="submit">Отправить заказ</button>
</form>
```
**Здесь:**
Наличие .em-order-form обязательно

action="emerchant/act?saveorder" - на всякий случай если js не сработал

Если есть поле с названием email, то пользователю отправляется также отправляется письмо.


### Отправка писем: 
Стандартные tpl для отправки лежат в /assets/plugins/emerchant/tpls/order/

Для менеджера в папаке /admin/, для пользователя в папке /user/

order.outer.tpl - Общая, order.row.tpl - строка

Плейсхолдеры такие же, как и в случае с корзиной, плюс доступны [+название_переменной_post+]

[+orderID+] - id заказа

Выполнение сниппетов допустимо.


***Также возможно отправлять письма следующим образом: в настройках плагина пишем название сниппета, который будет получать $email, $to - (admin/user), $letter - сформированное письмо, а также массивы $cart - корзину, и $form - отправляемые данные.***

### Дополнительно.
**js-функции на события:**
emerchantReloadCart - обновление корзины

emAddPostition - добавление позиции

emRemovePosition - удаление позиции

emBeforeRecountPosition - до пересчета

emAfterRecountPosition - после пересчета

emAfterOrderSent - после отправки формы (не срабатывает если указан data-redirect)


**События:**

*** Для всех событий, кроме OneMerchantSendLetter, доступен основной класс $em*** 
OnEmerchantInit - инициализация eMerchant 

OnEmerchantAddToCart - добавление в корзину (Доступен $position - то, что передалось)

OnEmerchantClearCart - очистка корзины 

OnEmerchantDifferentPrices - Проставление различных дополнительных наценок и скидок ($diff - массив с наценками)

OnEmerchantRecountPositionCart  - Пересчет позиции в корзину (Доступен $hash - соответствуюзей строки и $count - количество)

OnEmerchantRemoveFromCart - удаление позиции (Доступен $hash - соответствуюзей строки)

OnEmerchantSaveCart - сохранение корзины в сессии (Доступен $action, который получает значения: 'add','remove','recount')

OnEmerchantSaveOrder - сохранение заказа в таблицу заказов (Доступны $oid - id заказа, $cart - состояние корзины, $form - данные отправленной формы )

OnEmerchantSendLetter - После отправки каждого письма (Получает $email, $to - (admin/user), $letter - сформированное письмо, а также массивы $cart - корзину, и $form - отправляемые данные.)


***Для замены стандартных tpl можно прописать их в настройках плагина, либо перед tpl добавить .custom, например order.outer.custom.tpl***

### Пример плагина для бесплатной доставки свыше какой-то суммы и установки скидки:
```php
//<?php
if (($modx->event->name="OnPageNotFound") && ($_REQUEST['q']=="set_diff")){
	header("HTTP/1.1 200 OK");
	$_SESSION['sale'] = (int) $_POST['sale'];
	$_SESSION['delivery'] = (int) $_POST['delivery'];	
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') exit();
	else $modx->sendRedirect($_SERVER['HTTP_REFERER'],0,'REDIRECT_HEADER');
}

if ($modx->event->name="OnEmerchantDifferentPrices"){
	if ($diff['price.full']<1000){
		$diff['delivery'] = 300;
	}
	else{
		$diff['delivery'] = 0;
	}

	if (isset($_SESSION['delivery'])) $diff['delivery'] = $_SESSION['delivery'];
	if (isset($_SESSION['sale'])) $diff['sale'] = $_SESSION['sale'];

	$modx->event->output(json_encode($diff, JSON_UNESCAPED_UNICODE));
}
if ($modx->event->name="OnEmerchantClearCart"){
	if (isset($_SESSION['delivery'])) unset($_SESSION['delivery']);
	if (isset($_SESSION['sale'])) unset($_SESSION['sale']);
}
//?>
```
