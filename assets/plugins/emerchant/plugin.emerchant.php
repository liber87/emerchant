<?php
	defined('IN_PARSER_MODE') or die();	
	if (!class_exists('emerchant')) {
		require_once MODX_BASE_PATH."assets/plugins/emerchant/classes/class.emerchant.php";		
	}	

	if (($modx->event->name=='OnPageNotFound') && ($_REQUEST['q']=='emerchant/act'))
	{
		//Если установлена $_POST['cart_name'] значит работаем с кастомной корзиной
		if (isset($_POST['cart_name'])){
			$params['cart_name'] = $_POST['cart_name'];
			unset($_POST['cart_name']);
		}		

		$shk = new emerchant($modx, $params);
		header("HTTP/1.1 200 OK");


		//Добавляем
		if (isset($_GET['add'])){
			$position = $shk->makePosition($_POST);
			$shk->addToCart($position);
			$shk->saveCart('add');
		}
		

		//Удаляем
		if ((isset($_GET['del'])) && (isset($_GET['hash']))){			
			$shk->removeFromCart($_GET['hash']);
			$shk->saveCart('remove');	
		}

		//Изменяем количество
		if ((isset($_GET['recount'])) && (isset($_GET['hash'])) && ($_GET['count'])){
			$shk->recountPositionCart($_GET['hash'],$_GET['count']);
			$shk->saveCart('recount');
		}		

		//Очищаем корзину
		if (isset($_GET['clearCart'])) {
			$shk->clearCart();
		}	

		//Сохраняем корзину
		if (isset($_GET['saveorder'])){	

			//Запихиваем данные в админку
			$oid = $shk->saveOrder($_POST);								

			//Отправляем письмо админу 
			$shk->sendLetter($oid,$shk->config['emailTo'],'admin');
			
			//Отправляем письмо пользователю 
			if ($_POST['email']) $shk->sendLetter($oid,$_POST['email'],'user');

			//Обнуляем <s>Путина</s> корзину
			$shk->clearCart();		
		}		
		$shk->yankeeGoHome();
	}

	//MODULE		
	if (($modx->event->name=='OnPageNotFound') && ($_REQUEST['q']=='emerchant/module'))
	{
		if (!isset($_SESSION['mgrRole'])) die('Access denied!');
		$shk = new emerchant($modx, $params);
		header("HTTP/1.1 200 OK");
		$oid = (int) $_GET['oid'];	

		if (isset($_GET['getTable'])){
			$data = array();
			$params['orderId'] = $data['orderID'] = $oid;		
			$shk = new emerchant($modx, $params);
			$data['form'] = $shk->makeForm($params['orderId']);
			$data['cart'] = $shk->makeCart($shk->config['module.order.row.tpl'],$shk->config['module.order.owner.tpl']);	
			echo $shk->tpl->parseChunk($shk->config['module.order.info.tpl'],$data);
			exit();		
		}

		if (isset($_GET['deleteOrder'])){
			$modx->db->query('Delete from '.$shk->ordertable.' where id='.$oid);
			echo $shk->ordertable;
		}

		if (isset($_GET['editOrder'])){
			$_SESSION['orderId'] = $oid;
		}

		if (isset($_GET['closeOrder'])){
			if ($_SESSION['orderId']) unset($_SESSION['orderId']);
		}

		if (isset($_GET['updateOrder'])){
			$info = json_decode($modx->db->getValue('Select `form` from '.$shk->ordertable.' where id = '.$oid),true);			
			foreach($info as $key => $val){
				if ($_POST[$key]) $info[$key]=$modx->db->escape($_POST[$key]);									
			}			
			$modx->db->update(array('form'=>json_encode($info,JSON_UNESCAPED_UNICODE),'status'=>$_POST['status']),
			$shk->ordertable,'id='.$oid);
		}		

		$shk->yankeeGoHome();
	}

	if ($modx->event->name=='OnWebPagePrerender')
	{	
		$content = $modx->Event->params['documentOutput'];
		$src = '<script src="/assets/plugins/emerchant/js/emerchant.js"></script>'; 
		$content = str_replace('</body>',$src.PHP_EOL.'</body>',$content);	
		echo $content;
		exit();
	}
