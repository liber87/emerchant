document.addEventListener('DOMContentLoaded', function(){
	function unserialize (serializedString) {
		var str = decodeURI(serializedString);
		var pairs = str.split('&');
		var obj = {}, p, idx, val;for (var i=0, n=pairs.length;i < n;i++) {
			p = pairs[i].split('=');
			idx = p[0];
			if (idx.indexOf("[]") == (idx.length - 2)) {
				var ind = idx.substring(0, idx.length-2);
				if (obj[ind] === undefined) {obj[ind] = [];}
				obj[ind].push(p[1]);
			}
			else {
			obj[idx] = p[1];}
		}
		return obj;
	}
	
	function serialize (form,id='') {
		if (!form || form.nodeName !== "FORM") {
		var text = form;var form = document.createElement("form");form.innerHTML = text;}
		var i, j, q = [];for (i = form.elements.length - 1;i >= 0;i = i - 1) {
			if (form.elements[i].name === "") {
				continue;
			}
			switch (form.elements[i].nodeName) {
				case 'INPUT': switch (form.elements[i].type) {
					case 'text': 
					case 'tel': 
					case 'email':
					case 'number':
					case 'hidden': 
					case 'password': 
					case 'button': 
					case 'reset':
					case 'submit': q.push(form.elements[i].name + "=" + encodeURIComponent(form.elements[i].value));break;case 'checkbox': case 'radio':if (form.elements[i].checked) {
						q.push(form.elements[i].name + "=" + encodeURIComponent(form.elements[i].value));
					}
					break;
				}
				break;
				case 'file': break;
				case 'TEXTAREA':q.push(form.elements[i].name + "=" + encodeURIComponent(form.elements[i].value));break;
				case 'SELECT': switch (form.elements[i].type) {
					case 'select-one':q.push(form.elements[i].name + "=" + encodeURIComponent(form.elements[i].value));
					break;case 'select-multiple':for (j = form.elements[i].options.length - 1;j >= 0;j = j - 1) {
						if (form.elements[i].options[j].selected) {
						q.push(form.elements[i].name + "=" + encodeURIComponent(form.elements[i].options[j].value));}
					}
				break;}
				break;
				case 'BUTTON': switch (form.elements[i].type) {
					case 'reset': case 'submit': case 'button':q.push(form.elements[i].name + "=" + encodeURIComponent(form.elements[i].value));
				break;}
			break;}
		}
		if (id) q.push('em-id='+id);return q.join("&");
	}
	
	function reload_carts(){
		const request = new XMLHttpRequest();request.open("GET", location.href);request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");request.addEventListener("readystatechange", () => {
			if(request.readyState === 4 && request.status === 200) {
				var doc = new DOMParser().parseFromString(request.responseText, "text/html");var carts = document.querySelectorAll('.em-cart');var cartsReloaded = doc.querySelectorAll('.em-cart');if (cartsReloaded.length>0){
					var i = 0;for (let el of cartsReloaded) {
					carts[i].innerHTML = el.innerHTML;i++;}
				}
			}
		}
		);
		request.send();
	}
	
	function add_to_cart(params){
		const request = new XMLHttpRequest();
		request.open("POST", 'emerchant/act?add', true);
		request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		request.addEventListener("readystatechange", () => {
			if(request.readyState === 4 && request.status === 200) {
				reload_carts();
			if ( typeof(emAddPostition) == 'function') emAddPostition(unserialize(params));}
		});
		request.send(params);
	}
	
	
	var addButtons = document.querySelectorAll('.add-to-cart');
	for (let el of addButtons) {
		el.addEventListener('click', function(evt) {
			evt.preventDefault;
			evt.stopImmediatePropagation();
			var id = this.getAttribute('data-em-id');
			if (id==undefined) id='';
			var data = serialize(this.closest('.em-item').outerHTML,id);			
			add_to_cart(data);
		return false;}
		);
	};
	
	
	var forms = document.querySelectorAll('.em-item form');
	for (let el of forms) {
		el.addEventListener('submit', function(evt) {
			evt.preventDefault();
			evt.stopImmediatePropagation();			
			add_to_cart(serialize(this));
			return false;
		});
	}
	
	document.addEventListener('click', function(event) {
		var t = event.target.closest('.em-cart-item .em-del');
		if (!t) return;
		event.preventDefault;
		var hash = event.target.closest('.em-cart-item').getAttribute('data-hash');
		const request = new XMLHttpRequest();
		request.open("GET", 'emerchant/act?del&hash='+hash);
		request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		request.addEventListener("readystatechange", () => {
			if(request.readyState === 4 && request.status === 200) {
				reload_carts();
			if ( typeof(emRemovePosition) == 'function') emRemovePosition();}
		}
		);
		request.send();
		return false;
	});
	
	document.addEventListener('click', function(event) {
		var t = event.target.closest('.em-cart-item .em-plus');
		if (!t) return;
		event.preventDefault;
		var input = event.target.closest('.em-cart-item').querySelector('.em-count-value');
		var val = parseInt(input.value)+1;
		input.value = val;
		var event = document.createEvent("Event");
		event.initEvent("change", true, true);
		input.dispatchEvent(event);
		return false;
	});
	
	document.addEventListener('click', function(event) {
		var t = event.target.closest('.em-cart-item .em-minus');
		if (!t) return;
		event.preventDefault;
		var input = event.target.closest('.em-cart-item').querySelector('.em-count-value');
		var val = parseInt(input.value)-1;if (val>0){
			input.value = val;
			var event = document.createEvent("Event");
			event.initEvent("change", true, true);
			input.dispatchEvent(event);
		}
		return false;
	});
	
	document.addEventListener('click', function(event) {
		var t = event.target.closest('.em-cart .em-clear');
		if (!t) return;
		event.preventDefault;
		const request = new XMLHttpRequest();
		request.open("GET", 'emerchant/act?clearCart');
		request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		request.addEventListener("readystatechange", () => {
			if(request.readyState === 4 && request.status === 200) {
			reload_carts();
			if ( typeof(emAfterClearCart) == 'function') emAfterClearCart();}
		});
		request.send();
		return false;
	});
	
	document.addEventListener('change', function(event) {
		var t = event.target.closest('.em-cart-item .em-count-value');
		if (!t) return;
		event.preventDefault;
		var hash = event.target.closest('.em-cart-item').getAttribute('data-hash');
		var count = event.target.value;
		const request = new XMLHttpRequest();
		request.open("GET", 'emerchant/act?recount&hash='+hash+'&count='+count);
		request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		request.addEventListener("readystatechange", () => {
			if(request.readyState === 4 && request.status === 200) {
			reload_carts();if ( typeof(emAfterRecountPosition) == 'function') emAfterRecountPosition();}
		});
		request.send();
		return false;
	});
	
	var formOrder = document.querySelectorAll('.em-order-form');
	for (let el of formOrder) {
		el.addEventListener('submit', function(evt) {
			if (evt.preventDefault) evt.preventDefault();
			else evt.returnValue = false;
			if ( typeof(emBeforeOrderSent) == 'function') emBeforeOrderSent($(this));
			var params = serialize(this);
			console.log(params);
			var redirect = this.getAttribute('data-redirect');
			const request = new XMLHttpRequest();
			request.open("POST", 'emerchant/act?saveorder', true);
			request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			request.addEventListener("readystatechange", () => {
				if(request.readyState === 4 && request.status === 200) {
					reload_carts();if (redirect) window.location.href=redirect;
					else if ( typeof(emAfterOrderSent) == 'function') emAfterOrderSent($(this));
				}
			}
			);
			request.send(params);
		return false;}
		, false);
	}
});
