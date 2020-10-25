<h3>Список товаров (Заказ #[+orderID+])</h3>
[+cart+]
<div class="text-center" style="margin-top:30px;">
	<input type="button" value="Изменить заказ" class="btn btn-warning" id="editOrder" data-oid="[+orderID+]">
	<input type="button" value="Звершить изменение заказа" class="btn btn-info closeOrder" style="display:none;"  data-oid="[+orderID+]">
</div>

<h3 style="margin-top:20px;">Данные о клиенте</h3>	
<form method="post" action="./../emerchant/module?updateOrder&oid=[+orderID+]" id="updateOrder">				
	[+form+]		
	<div class="text-center">
		<input type="submit" value="Сохранить" class="btn btn-success">
	</div>
</form>
