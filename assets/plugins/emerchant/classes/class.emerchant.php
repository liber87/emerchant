<?php
/**
 * evoShk
 *
 * Shopping cart class
 *
 * @author Liber <alexey@liber.pro>
 * @package evoShk
 * @version 1.0.0
 */

class emerchant {
	
    /**
     *
     * @param object $modx
     * @param array $config
     */
    function __construct(&$modx, $config = array()){
		
        $this->modx = evolutionCMS();
	$pconfig = $this->getPluginConfig();	
	$config = array_merge($pconfig,$config);
		
	if (!$config['cart_name']) $config['cart_name'] = 'default';
	if (!$config['pricetv']) $config['pricetv'] = 'price';
	if (!$config['emailTo']) $config['emailTo'] = $modx->config['emailsender'];
	if (!$config['emailFrom']) $config['emailFrom'] = $modx->config['emailsender'];
	if (!$config['subject']) $config['subject'] = 'Новый заказ на сайте';
		
	$tplPaths = $this->tplPaths = MODX_BASE_PATH.'assets/plugins/emerchant/tpls/';		
	$this->config = $config;		
	if (isset($_SESSION['orderId'])) $this->config['orderId'] = $_SESSION['orderId'];			
	$this->setTplsFromFiles($tplPaths);		
	$this->rowPrepare = '';
	$this->ownerPrepare = '';
				        
	include_once(MODX_BASE_PATH.'assets/snippets/DocLister/lib/DLTemplate.class.php');
	$tpl = DLTemplate::getInstance($modx);
	$this->tpl = $tpl;				
	$this->ordertable = $modx->getFullTableName('emerchant_orders');
	$this->orderthintable = $modx->getFullTableName('emerchant_orders_thin');
	$this->tvtable = $modx->getFullTableName('site_tmplvar_contentvalues');	
	$this->tvnametable = $modx->getFullTableName('site_tmplvars');			
	$this->contenttable = $modx->getFullTableName('site_content');		
	$this->priceid = $modx->db->getValue('Select `id` from '.$modx->getFullTableName('site_tmplvars').' where `name`="'.$config['pricetv'].'"');				
				
	if (!isset($this->config['orderId'])){			
		//Проверяем живучисть сессии, если что, вытаскиваем из куки			
		if ((isset($_COOKIE["token"])) && (!$_SESSION['emCart'][$this->config['cart_name']])){
			$token = $modx->db->escape($_COOKIE["token"]);
			$this->cart = json_decode($modx->db->getValue('Select cart from  '.$this->orderthintable.' where name="'.$token.'"'),true);
		}
		else{
		if (!$_SESSION['emCart'][$this->config['cart_name']]) $_SESSION['emCart'][$this->config['cart_name']] = array();	
		$this->cart = $_SESSION['emCart'][$this->config['cart_name']];}
	}
	else {			
		$this->cart = json_decode($this->modx->db->getValue('Select `cart` from '.$this->ordertable.'
										 where id='.$this->config['orderId']),1);
	}
	$this->cart['different'] = $this->differentPrices();
	$this->startCurrency();
	$this->saveCart();
	$this->modx->invokeEvent('OnEmerchantInit',array('em' => $this));        		
    }
	
	/*
	* Окончание если ajax, возврат если нет
	*/
	function yankeeGoHome(){
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') exit();
		else $this->modx->sendRedirect($_SERVER['HTTP_REFERER'],0,'REDIRECT_HEADER');
	}
	
	
	/*
	* Берем конфиг из плагина
	*/
	function getPluginConfig(){
		$json = $this->modx->db->getValue('Select `properties` from '.$this->modx->getFullTableName('site_plugins').'
												where name="emerchant"');												
		$props = json_decode($json,1);	
		$config = array();
		foreach($props as $key=>$val) if ($val[0]['value']) $config[$key] = $val[0]['value'];
		return $config;
	}
	
	/*
	* Устанавливаем prepare для строки
	*/
	function issetRowPrepare($prepare){
		$this->rowPrepare = $prepare;
	}
	
	/*
	* Устанавливаем prepare для корзины
	*/
	function issetOwnerPrepare($prepare){
		$this->ownerPrepare = $prepare;
	}
	
	/*
	* Устанавливаем формат для цен
	*/
	function numberFormat($price){
		$format = explode('|',$this->config['numberFormat']);
		if (!is_array($price)) {
			$price = number_format($price, $format[0], $format[1], $format[2]); 
			return rtrim(rtrim($price, '0'), '.');
		}
		else {
			foreach($price as $key => $val) if (is_numeric($val)) {
				$price[$key] = number_format($val, $format[0], $format[1], $format[2]);
				$price[$key] = rtrim(rtrim($price[$key], '0'), '.');
			}				
			return $price;
		}
	}
	
	/*
	* Установка курса по умолчанию
	*/
	function startCurrency(){
		if (!is_array($_SESSION['emCart'][$this->config['cart_name']]['currency'])) {
			$_SESSION['emCart'][$this->config['cart_name']]['currency'] = array('name'=>'USD','currency'=>1);
		}
	}
	
	/*
	* Получение курса 
	*/
	function getCurrencyName(){				
		return $_SESSION['emCart'][$this->config['cart_name']]['currency']['name'];		
	}
	
	/*
	* Устанавливаем цены согласно курсу
	*/
	function setCurrency($price){				
		$currency = $_SESSION['emCart']['default']['currency']['currency'];		
		if (!is_array($price)) return $price*$currency;
		else {
		foreach($price as $key => $val) if (is_numeric($val)) $price[$key] = $val * $currency;
		return $price;
		}
	}
	
	
	/*
	* Устанавливаем шаблоны из файлов
	*/
	function setTplsFromFiles($path){
		$items = scandir($path);

		foreach($items as $item){	
			if($item != "." AND $item != ".."){
				if (is_file($path . $item)){				
					if (strpos($item, '.custom.tpl') === false){
						$relative = str_replace($this->tplPaths,'',$path.$item);
						$relative = str_replace('/','.',$relative);
						if (!$this->config[$relative]){
							if (file_exists($path . str_replace('.tpl','.custom.tpl',$item))) $tpl= $path . str_replace('.tpl','.custom.tpl',$item);
							else $tpl = $path . $item;
							$this->config[$relative] = '@CODE: '.file_get_contents($tpl);
						}
					}
				} 
				else $this->setTplsFromFiles($path . $item . "/");				
			}
		}
	}
	
	
	/**
     * Формируем из POST массива позицию
     * @param array $data
     */
	function makePosition($data){
	
		$id = (int) $data['em-id'];		
		if (!$id) return;
		$count = (isset($data['em-count'])) ? $data['em-count'] : 1;
		if (!$this->config['allowFloatCount']) $count = (int) $count;
		$price = $this->modx->db->getValue('Select `value` from '.$this->tvtable.' where contentid='.$id.' and tmplvarid='.$this->priceid);
				
		if (isset($data['em-id'])) unset($data['em-id']);
		if (isset($data['em-count'])) unset($data['em-count']);
		
		$position = array('id'=>$id,'count' => $count,'price'  => $price, 'price.add' => array(), 'add' => $data);	
		
		$evtOut = $this->modx->invokeEvent('OnEmerchantMakePosition',array('data' => $data,'position'=>$position,'em'=>$this));
      	if(is_array($evtOut)) $position = json_decode($evtOut[0],true);	
		return $position;
	}
	
	
	/**
     * Добавляем позицию в корзину   
     * @param array $position
     */
	function addToCart($position = array()){
	
		if ((!$this->cart['items']) || (count($this->cart['items'])==0)){
			$this->cart['items'][] = $position;			
		}	
		else{
			$up = '';
			$change_count = -1;
			foreach($this->cart['items'] as $key => $item){	
				if (isset($item['hash'])) unset($item['hash']);
				$diff = array_diff($item, $position);							
				if (((count($diff)==1) && (isset($diff['count']))) || (count($diff)==0)){
					$diff_add = array_diff($item['add'], $position['add']);				
					if (count($diff_add)==0) $change_count = $key;
				}
			}
			if ($change_count>=0) $this->cart['items'][$change_count]['count'] = $this->cart['items'][$change_count]['count'] + $position['count'];
			else $this->cart['items'][] = $position;
		}		
		$this->modx->invokeEvent('OnEmerchantAddToCart',array('position'=>$position,'em'=>$this));        
	}
	
	/**
     * Удаляем позицию из корзины
     * @param array $position
     */
	function removeFromCart($hash = '')	{	
		$key = $this->findPosition($hash);
		if ($key!==FALSE) unset($this->cart['items'][$key]);	
		$this->modx->invokeEvent('OnEmerchantRemoveFromCart',array('hash'=>$hash,'em'=>$this));
	}
	
	
	/**
     * Изменяем количество товаров у конкретной строки
     */
	function recountPositionCart($hash = '',$count = 1){
		
		if ($count<=0) {
			$this->removeFromCart($hash);
			$this->saveCart('remove');
		}
		$key = $this->findPosition($hash);
		if ($key!==FALSE) $this->cart['items'][$key]['count'] = $count;
		$this->modx->invokeEvent('OneMerchantRecountPositionCart',array('hash'=>$hash,'count'=>$count,'em'=>$this));
	}
	 
	 /**
     * Изменяем/дополняем дополнительные параметры у конкретной строки
     */
	 
	 
	
	/**
     * Находим ключ в массиве по хэшу
     */
	function findPosition($hash){	
		 return array_search($hash, array_column($this->cart['items'], 'hash'));	}
	 
	/**
     * Сохраняем корзину в сессию и проставляем хеш для строк        
     */
	function saveCart($action = '')	{
		if (!is_array($this->cart['items'])) return;
		if (count($this->cart['items'])){
			foreach($this->cart['items'] as $k => $item){				
				if (isset($item['hash'])) unset($item['hash']);
				$j = json_encode($item);
				$this->cart['items'][$k]['hash'] = md5($j);
			}
		}		
		$this->cart['items'] = array_values($this->cart['items']);
		$_SESSION['emCart'][$this->config['cart_name']] = $this->cart;		
		$_SESSION['emCart'][$this->config['cart_name']]['different'] = $this->differentPrices();		
		$this->cart = $_SESSION['emCart'][$this->config['cart_name']];
		if ($this->config['orderId']){
			$this->modx->db->update(
			array('cart'=>$this->modx->db->escape(json_encode($this->cart, JSON_UNESCAPED_UNICODE))),
			$this->ordertable, 'id='.$this->config['orderId']);
		}
		$this->saveCartCookie($this->cart);
		$this->modx->invokeEvent('OneMerchantSaveCart',array('action'=>$action,'em'=>$this));		
	}	
	
	/**
	* Сохраняем незаконченную корзину в таблицу незаконченных заказов
    */
	function saveCartCookie($cart){		
		
		if(!isset($_COOKIE["token"])){
		  $token = md5(uniqid());	
		  setcookie("token", $token, time()+60*60*24*30,'/');
		}				
		$this->modx->db->query('INSERT INTO '.$this->orderthintable.' 
		(`name`,`cart`,`date`)
		VALUES ("'.$this->modx->db->escape($_COOKIE["token"]).'",
		"'.$this->modx->db->escape(json_encode($this->cart, JSON_UNESCAPED_UNICODE)).'",
		"'.time().'")
		ON DUPLICATE KEY UPDATE `cart` = "'.$this->modx->db->escape(json_encode($this->cart, JSON_UNESCAPED_UNICODE)).'"');
	}
	
		
	/*
	* Проставление различных дополнительных наценок и скидок
	*/
	function differentPrices(){				
		if (!is_array($this->cart['items'])) return;		
		$diff = array(
		'price.base'=>0,
		'price.add'=>0,
		'price.full'=>0,
		'delivery'=>0,
		'payment'=>0,
		'sale'=>0,
		'other'=>0,
		'count.items'=>0,
		'price.final'=>0
		);				
		
		foreach($this->cart['items'] as $item){	
		
			$diff['price.base'] = $diff['price.base'] + ($item['price']*$item['count']);
			if ((is_array($item['price.add'])) && (count($item['price.add']))){			
				$apf = 0;
				foreach($item['price.add'] as $ap) $apf = $apf + $ap;
				$diff['price.add'] = $diff['price.add'] + ($apf*$item['count']);
			}
			$diff['price.full'] = $diff['price.base'] + $diff['price.add'];
			$diff['count.items'] = $diff['count.items'] + $item['count'];			
		}		
		
		$evtOut = $this->modx->invokeEvent('OnEmerchantDifferentPrices',array('diff'=>$diff,'em'=>$this));
        if(is_array($evtOut)) $diff = json_decode($evtOut[0],true);			
		$diff['price.final'] = $diff['price.full']+$diff['delivery']+$diff['payment']-$diff['sale']+$diff['other'];		
		return $diff;
	}
	
	/*
	* Подставляем плйесхолдеры в строку корзины
	*/
	function makeRowCart($tpl = '',$item = array(), $num = 0){
		$tvs = array();
		$res = $this->modx->db->query('SELECT name,value FROM '.$this->tvtable.'
		as vals left join '.$this->tvnametable.' as tv on tv.id = vals.tmplvarid where vals.contentid='.$item['id']);
		while ($row = $this->modx->db->getRow($res)) $tvs[$row['name']] = $row['value'];
		$res = $this->modx->db->query('Select * from '.$this->contenttable.' where id='.$item['id']);				
		$doc = $this->modx->db->getRow($res);		
		
		
		
		if (!is_array($doc)) $doc = array();
		$data = array_merge($tvs,$item,$doc);				
		$data['num'] = $num+1;
		$data['count.items'] = $data['count'];
		$data['price.base'] = $data['price'];
		$data['price.options'] = 0;
		$data['price.options.full'] = $data['price'];// + $data['price.add'];		
		
		if (is_array($item['price.add']) && (count($data['price.add']))){
			foreach($data['price.add'] as $name => $price){			
				$data['price.options'] = $data['price.options'] + $price;
				$data['price.options.full'] = $data['price.options.full'] + $price;				
			}
		}
		$data['price.base.total'] = $data['price.base']*$data['count'];
		$data['price.options.total'] = $data['price.options']*$data['count'];
		$data['price.options.full.total'] = $data['price.options.full']*$data['count'];	
				
		if ($this->rowPrepare){				
			foreach(explode(',',$this->rowPrepare) as $name){
				if ((is_object($name)) || is_callable($name)) {
					$result = call_user_func_array($name, array('data'=>$data));
					if ((is_array($result)) && (count($result))) $data = $result;
				} else {
					$result = $this->modx->runSnippet($name, array('data'=>$data));					
					if ((is_array($result)) && (count($result))) $data = $result;					
				}				
			}
		}		
		
		foreach($data as $key => $val) if (strpos($key,'price')!==false) {				
			$val = $this->setCurrency($val);									
			$data[$key] = $this->numberFormat($val);
		}		
		
		$data['current_currency'] = $this->getCurrencyName();		
		
		return $this->tpl->parseChunk($tpl,$data).PHP_EOL;			
	}
	
	/*
	* Подставляем плйесхолдеры в корзину
	*/
	function makeCart($tpl = '',$ownerTPL ='', $noneTPL= '', $oid = 0){
		if ((!isset($this->cart['items'])) or (!count($this->cart['items']))) $ownerTPL = $noneTPL;		
		$odata = $this->cart['different'];
		if ((is_array($odata)) && (count($odata))) {
			foreach($odata as $key => $val) {
				if ($key!='count.items') {
					$val = $this->setCurrency($val);
					$odata[$key] = $this->numberFormat($val);		
				}
			}
		}
		if ($oid) {
			$order = $this->getOrderInfo($oid);
			$odata['orderID'] = $oid;
			if (is_array($odata)) $odata = array_merge($odata,$order);			
		}
		
		if ((is_array($this->cart['items'])) && (count($this->cart['items']))) {
			foreach($this->cart['items'] as $num => $item) {
				$odata['cart'].= $this->makeRowCart($tpl,$item,$num);		
			}
		}
		$odata['current_currency'] = $this->getCurrencyName();
		return $this->tpl->parseChunk($ownerTPL,$odata);
	}
	
	/*
	* Получаем информацию о пользовтаеле заказа
	*/
	function getOrderInfo($oid){ 
		$oid = (int) $oid;		
		$ord = $this->modx->db->getValue('Select `form` from '.$this->ordertable.' where `id`='.$oid);
		if ($ord) return json_decode($ord,1);	
	}
	
	/*
	* Создаем форму в админке
	*/
	function makeForm($oid){
		$out='';
		$order = $this->getOrderInfo($oid);		
		$ff = explode('||',$this->config['form_fields']);
		$out.='<table style="width:100%;">';
		foreach($ff as $str){
			$col = explode('==',$str);
			$out.= '<tr><td>'.$col[1].'</td><td><input type="text" name="'.$col[0].'" value="'.$order[$col[0]].'"></td></tr>';
		}
		$out.= '</table>';				
		$st = $ser = $this->modx->db->getValue('Select `status` from '.$this->ordertable.' where id = '.$oid);		
		$out.= '<h3 style="margin-top:20px;">Статус заказа</h3>
		<div class="row statuses" style="padding:0 15px 50px 15px;">';
		foreach(explode('||',$this->config['statusNames']) as $key => $val){
			$i = $key + 1;
			$status = explode('==',$val);
			if ($st == $i) $act = 'checked="checked"';
			else $act='';
			$out.= '<div class="col-xs-2"><label>
			<input type="radio" name="status" value="'.$i.'" '.$act.'>
			<span class="status status-'.$i.'"></span> <span class="name">'.$status[0].'</span>
			</label></div>';
		}			
		$out.= '</div>';		
		return $out;
	}	
		
	
	/**
     * Сохраняем заказ в базе
     */
	function saveOrder($data)	{		
		if ((!is_array($this->cart['items'])) || (!count($this->cart['items']))) return;		
		$oid = $this->modx->db->insert(
		['cart'=>$this->modx->db->escape(json_encode($this->cart, JSON_UNESCAPED_UNICODE)),
		'form'=>$this->modx->db->escape(json_encode($data, JSON_UNESCAPED_UNICODE)),
		'date'=>time(),
		'status'=>1,
		'userId'=>$this->modx->getLoginUserID(),
		'name'=>$this->modx->db->escape($this->config['cart_name'])
		],
		$this->ordertable);
		$this->modx->invokeEvent('OneMerchantSaveOrder',array('oid'=>$oid,'cart'=>$this->cart,'form'=>$data,'em'=>$this));
		return $oid;
	}
	
	/*
	* Отправка письма
	*/
	function sendLetter($oid,$email,$to){		
		if (!$oid) return;
		$content = $this->makeCart($this->config['order.'.$to.'.order.row.tpl'],$this->config['order.'.$to.'.order.outer.tpl'],'',$oid);		
		if ($this->config['methodSend']){
			$cart = $this->modx->db->getValue('Select `cart` from '.$this->ordertable.' where id='.$oid);			
			$form = $this->modx->db->getValue('Select `form` from '.$this->ordertable.' where id='.$oid);
			$modx->runSnippet(config['methodSend'],
			['email'=>$email,'subject'=>$this->config['subject'],'cart'=>$cart,'form'=>$form,'letter'=>$content,'to'=>$to]);
		}else{
			$this->modx->loadExtension('MODxMailer');	
			$mail = $this->modx->mail;	
			$mail->IsHTML(true);
			$mail->From = $this->config['emailFrom'];
			$mail->FromName = $this->modx->config['site_name'];
			$mail->Subject = $this->config['subject'];
			$mail->Body = $content;
			$mail->addAddress($email); 
			$mail->send(); 
			$mail->ClearAllRecipients();			
		}
		$this->modx->invokeEvent('OneMerchantSendLetter',array('oid'=>$oid,'email'=>$email,'to'=>$to,'content'=>$content));
	}
	
	/*
	*	Вывод таблицы модуля
	*/
	function getModuleTable($paginate){
		return $this->modx->runSnippet('DocLister',
		array('controller'=>'onetable',
		'table'=>'emerchant_orders',
		'idField'=>'id',
		'orderBy'=>'id desc',
		'display'=>15,
		'parents'=>'1',
		'parentField'=>'dl',
		'showParent'=>-1,
		'prepare'=>'emDashboardPrepare',		
		'paginate'=>$paginate,
		'TplNextP'=>'',
		'TplPrevP'=>'',
		'TplPage'=>'@CODE: <li><a href="index.php?a=112&id='.$_GET['id'].'&page=[+num+]" >[+num+]</a></li>',
		'TplCurrentPage'=>'@CODE: <li class="active"><a>[+num+]</a></li>',
		'TplWrapPaginate'=>'@CODE: <ul id="pagination">[+wrap+]</ul>',
		'TplDotsPage'=>'@CODE: <li><a>...</a></li>',
		'ownerTPL'=>$this->config['module.table.outer.tpl'],
		'tpl'=>$this->config['module.table.row.tpl'])
		);
	}
	
	/*
	* Очищаем корзину
	*/
	function clearCart($oid = 0) {
		unset($_SESSION['emCart'][$this->config['cart_name']]);		
		if (isset($_COOKIE["token"])){
			$token = $this->modx->db->escape($_COOKIE["token"]);
			$this->modx->db->query('Delete from '.$this->orderthintable.' where name="'.$token.'"');
			setcookie("token","",time()-10000,'/');							
		}	
		$_SESSION['last_order_id'] = $oid;
		$this->modx->invokeEvent('OnEmerchantClearCart',array('em'=>$this));		
	}	
}
