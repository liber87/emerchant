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

		$em = new emerchant($modx, $params);
		header("HTTP/1.1 200 OK");


		//Добавляем
		if (isset($_GET['add'])){
			$position = $em->makePosition($_POST);
			$em->addToCart($position);
			$em->saveCart('add');
		}
		

		//Удаляем
		if ((isset($_GET['del'])) && (isset($_GET['hash']))){			
			$em->removeFromCart($_GET['hash']);
			$em->saveCart('remove');	
		}

		//Изменяем количество
		if ((isset($_GET['recount'])) && (isset($_GET['hash'])) && ($_GET['count'])){
			$em->recountPositionCart($_GET['hash'],$_GET['count']);
			$em->saveCart('recount');
		}		

		//Очищаем корзину
		if (isset($_GET['clearCart'])) {
			$em->clearCart();
		}	

		//Сохраняем корзину
		if (isset($_GET['saveorder'])){	

			//Запихиваем данные в админку
			$oid = $em->saveOrder($_POST);								

			//Отправляем письмо админу 
			$em->sendLetter($oid,$em->config['emailTo'],'admin');
			
			//Отправляем письмо пользователю 
			if ($_POST['email']) $em->sendLetter($oid,$_POST['email'],'user');

			//Обнуляем <s>Путина</s> корзину
			$em->clearCart();		
		}		
		$em->yankeeGoHome();
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
			$data['form'] = $em->makeForm($params['orderId']);
			$data['cart'] = $em->makeCart($em->config['module.order.row.tpl'],$em->config['module.order.owner.tpl']);	
			echo $em->tpl->parseChunk($em->config['module.order.info.tpl'],$data);
			exit();		
		}

		if (isset($_GET['deleteOrder'])){
			$modx->db->query('Delete from '.$em->ordertable.' where id='.$oid);
			echo $em->ordertable;
		}

		if (isset($_GET['editOrder'])){
			$_SESSION['orderId'] = $oid;
		}

		if (isset($_GET['closeOrder'])){
			if ($_SESSION['orderId']) unset($_SESSION['orderId']);
		}

		if (isset($_GET['updateOrder'])){
			$info = json_decode($modx->db->getValue('Select `form` from '.$em->ordertable.' where id = '.$oid),true);			
			foreach($info as $key => $val){
				if ($_POST[$key]) $info[$key]=$modx->db->escape($_POST[$key]);									
			}			
			$modx->db->update(array('form'=>json_encode($info,JSON_UNESCAPED_UNICODE),'status'=>$_POST['status']),
			$em->ordertable,'id='.$oid);
		}		

		$em->yankeeGoHome();
	}

	if ($modx->event->name=='OnWebPagePrerender')
	{	
		$content = $modx->Event->params['documentOutput'];
		$src = '<script src="/assets/plugins/emerchant/js/emerchant.js"></script>'; 
		$content = str_replace('</body>',$src.PHP_EOL.'</body>',$content);	
		echo $content;
		exit();
	}
