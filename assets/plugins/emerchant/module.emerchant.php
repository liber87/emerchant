<?php	
	defined('IN_MANAGER_MODE') or die();	
	if (!class_exists('emerchant')) {
		require_once MODX_BASE_PATH."assets/plugins/emerchant/classes/class.emerchant.php";		
	}
	$em = new emerchant($modx, $params);
	if (!function_exists('emDashboardPrepare'))
	{
		function emDashboardPrepare($data)
		{
			global $modx;		
			if ($data['date']) $data['date'] = date("d-m-Y H:i:s",$data['date']);			
			$info = json_decode($data['form'],1);	
			if ((is_array($info)) && (count($info))) foreach($info as $key => $val) $data[$key] = $val;
			$cart = json_decode($data['cart'],1);	
			if (is_array($cart['different']) && (count($cart['different']))) foreach($cart['different'] as $key => $val) $data[$key] = $val;
			$form = json_decode($data['form'],1);	
			if ((is_array($form)) && (count($form))) foreach($form as $key => $val) $data[$key] = $val;
			return $data;
		}
	}
?>
<html>
	<head>
		<title>Evolution CMS</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width">
		<meta name="theme-color" content="#1d2023">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<link rel="stylesheet" type="text/css" href="media/style/default/css/styles.min.css?v=1535750542">
		<script type="text/javascript" src="media/script/tabpane.js"></script>
		<script type="text/javascript" src="media/script/jquery/jquery.min.js"></script>		
		<style>
			*{font-weight: normal;font-style: normal;font-size: 0.8125rem;	line-height: 1.5;font-family: sans-serif;-webkit-font-smoothing: subpixel-antialiased;}
			.status{display: inline-block;background-color: #2DCE89; width: 10px; height: 10px;  border-radius: 50%; cursor:pointer;}
			.more-info{cursor:pointer;}
			.evo-popup.alert .evo-popup-header {border:none !important;}
			.status-1{background-color:#C5CAFE;}
			.status-2{background-color:#B1F2FC;}
			.status-3{background-color:#F3FDB0;}
			.status-4{background-color:#BEFAB4;}
			.status-5{background-color:#FFAEAE;}
			.status-6{background-color:#FFE1A4;}
			.statuses input{display:none;}
			.trash-item{cursor:pointer;}
			.statuses input:checked ~ .name {font-weight:700; border-bottom:1px dashed;}
			.evo-popup-body{    overflow-x: hidden;}
		</style>	
		<script>					
			document.addEventListener('DOMContentLoaded', function()
			{
			$('.trash-item').click(function(){						
			var oid = $(this).data('id');
			if(confirm('Вы уверены?'))
			{
			$(this).closest('tr').remove();
			$.ajax({type: 'get',url: './../emerchant/module?deleteOrder&oid='+oid,success: function(result){
			console.log(result);
			}});
			}
			});
			$('.more-info').click(function()
			{			
			var oid = $(this).data('id');			
			modx = parent.modx;			
			$.ajax({type: 'get',url: './../emerchant/module?getTable&oid='+oid,
			success: function(result){ 
			modx.popup({content: result,title:' ',draggable:false,width: '90%',height: '90%',hover: 0,hide: 0});		
			<?php
			if ($_SESSION['orderId']) {
			?>
				$('.closeOrder').show();
				$('#editOrder').hide();
			<?
			}
			?>
					}
				});
			});
			$(document).on('submit','#updateOrder',function(e){
				e.preventDefault();							
				$.ajax({type: 'post', url: $(this).attr('action'),data: $(this).serialize(),success: function(result){}});
			});
			$(document).on('click','#editOrder',function(e){
				e.preventDefault();			
				var oid = $(this).data('oid');
				$.ajax({type: 'get',url: './../emerchant/module?editOrder&oid='+oid,
					success: function(result){
						$('#editOrder').hide();
						$('.closeOrder').show();
						window.open("<?php echo $modx->makeUrl('1','','','full');?>");
					}});
			});
			$(document).on('click','.closeOrder',function(e){
				e.preventDefault();			
				var oid = $(this).data('oid');
				$.ajax({type: 'get',url: './../emerchant/module?closeOrder&oid='+oid,
					success: function(result){
						$('.closeOrder').hide();
						$('#editOrder').show();
						$('#warningedit').remove();
						location.reload();
					}});
			});
		});
		</script>
</head>
<body>		
	<div class="container container-body">	
		<div class="row form-row widgets">		
			<?php
			if ($_SESSION['orderId']) {
			?>
			<div class="col-sm-12" id="warningedit">
				<div class="card">
					<div class="card-header"> <i class="fa fa-exclamation-triangle"></i> Внимание! </div>
					<div class="card-block">
						<div class="card-body">
							<p>У вас имеется незавершенное <a class="more-info" data-id="<?=$_SESSION['orderId'];?>" style="cursor:pointer; border-bottom:1px dashed lightgrey;">редактирование заказа #<?=$_SESSION['orderId'];?></a>. <a class="closeOrder" data-id="<?=$_SESSION['orderId'];?>" style="cursor:pointer; border-bottom:1px dashed lightgrey;">Завершить?</a>.
							</p>
						</div>
					</div>
				</div>
			</div>
			<?php
			}
			?>
			<div class="col-sm-12" id="shop">
				<div class="card">
					<div class="card-header"> <i class="fa fa-shopping-cart">
					</i> Управление заказами </div>
					<div class="card-block">
						<div class="card-body">
							<div class="table-responsive">
								<?php
									echo $em->getModuleTable('pages');										
								?>
							</div>
							<div align="center"> 
								<?php 
									echo $modx->getPlaceholder('pages');
								?>
								</div>
							</div> 
					</div>
				</div>
			</div>				
		</div>	
	</div>
</body>
</html>
