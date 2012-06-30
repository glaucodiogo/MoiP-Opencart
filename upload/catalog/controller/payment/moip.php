<?phpclass ControllerPaymentMoip extends Controller {	protected function index() {				$this->data['button_continue'] = $this->language->get('button_continue');		$this->data['button_back'] = $this->language->get('button_back');		//Verifica se está em modo de teste		if (!$this->config->get('moip_test')) {    		$this->data['action']     = 'https://www.moip.com.br/ws/alpha/EnviarInstrucao/Unica';    		$this->data['actionJson'] = 'https://www.moip.com.br/transparente/MoipWidget-v2.js';  		} else {			$this->data['action']     = 'https://desenvolvedor.moip.com.br/sandbox/ws/alpha/EnviarInstrucao/Unica';			$this->data['actionJson'] = 'https://desenvolvedor.moip.com.br/sandbox/transparente/MoipWidget-v2.js';		}						//Carrega o arquivo catalog/model/checkout/order.php		$this->load->model('checkout/order');				//Adiciona os dados da compra no array order_info		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);				//Captura a 'razão' cadastrato no módulo de pagamento MoiP no painel administrativo		$this->data['nometranzacao'] = $this->config->get('moip_razao');				//Captura o 'Token' cadastrato no módulo de pagamento MoiP no painel administrativo		$this->data['apitoken'] = $this->config->get('moip_apitoken');								//Captura a 'Key' cadastrato no módulo de pagamento MoiP no painel administrativo		$this->data['apikey'] = $this->config->get('moip_apikey');								//Captura o tipo da moeda utilizada na compra		$this->data['currency_code'] = $order_info['currency_code'];				//Captura o valor total		$this->data['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], FALSE);				//Captura o primeiro nome do Cliente e remove os caracteres especiais		$this->data['first_name'] = htmlentities($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');				//Captura o sobrenome do cliente e remove os caracteres especiais		$this->data['last_name'] = htmlentities($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');				//Captura o logadouro do cliente e remove os caracteres especiais		$this->data['address1'] = htmlentities($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');				//Captura o bairro do cliente e remove os caracteres especiais		$this->data['address2'] = htmlentities($order_info['payment_address_2'], ENT_QUOTES, 'UTF-8');				//Captura a cidade do Cliente e remove os caracteres especiais		$this->data['city'] = htmlentities($order_info['payment_city'], ENT_QUOTES, 'UTF-8');				//Captura o CEP do Cliente		$this->data['zip'] = $order_info['payment_postcode'];				//Captura o País do Cliente		$this->data['country'] = $order_info['payment_country'];				//Adiciona a url com a função de callback na variável $notify_url		$this->data['notify_url'] = HTTPS_SERVER . 'payment/moip/callback&order_id';				//Inicia a sessão com o id da compra		$this->session->data['order_id'];				//Captura o id da compra		$this->data['codipedido'] = $this->session->data['order_id'];				//Captura o email do Cliente		$this->data['email'] = $order_info['email'];				//		$this->data['invoice'] = $this->session->data['order_id'] . ' - ' . $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];				//		if(isset($order_info['payment_numero'])){			$this->data['numero'] = $order_info['payment_numero'];		}		if(isset($order_info['payment_bairro'])){			$this->data['bairro'] = $order_info['payment_bairro'];		}				/* Pega o id do país */				$this->load->model('localisation/country');    	$paises = $this->model_localisation_country->getCountries();				foreach ($paises as $country) {			if($country['name']==$order_info['payment_country']){				$codigodopais = $country['country_id'];			}		}		/* Com id do país pega o code da cidade */		$this->load->model('localisation/zone');    	$results = $this->model_localisation_zone->getZonesByCountryId($codigodopais);		foreach ($results as $result) {        	if($result['name']==$order_info['payment_zone']){				$this->data['estado'] =$result['code'];			}    	} 		//Verifica se existe o ddd do cliente		if(isset($order_info['ddd'])){			$this->data['ddd'] = $order_info['ddd'];		} else {			$ntelefone = preg_replace("/[^0-9]/", "", $order_info['telephone']);			if(strlen($ntelefone) >= 10){					$ntelefone = ltrim($ntelefone, "0");				$this->data['ddd'] = substr($ntelefone, 0, 2);				$this->data['telephone'] = substr($ntelefone, 2,11);			} else {				$this->data['telephone'] = substr($ntelefone, 0,11);			}		}				//Adiciona a url que chama a função success na variavel $return		$this->data['return'] = HTTPS_SERVER . 'checkout/success';				//		$this->data['cancel_return'] = HTTPS_SERVER . 'checkout/payment';				$this->data['back'] = HTTPS_SERVER . 'checkout/payment';				//Captura o email cadastrado na página de pagamento MoiP no painel administrativo		$this->data['mailpg'] = $this->config->get('moip_email');				$this->data['products'] = array();			foreach ($this->cart->getProducts() as $product) {			/*$option_data = array();					foreach ($product['option'] as $option) {        		$option_data[] = array(          			'name'  => $option['name'],          			'value' => $option['value']        		);      		}*/			      		$this->data['products'][] = array(				'descricao'   => htmlentities($product['name'], ENT_COMPAT, 'UTF-8'),				'valor'       => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))),				//'disconto'  => ($product['discount'] ? $this->currency->format($this->tax->calculate($product['price'] - $product['discount'], $product['tax_class_id'], $this->config->get('config_tax'))) : NULL),				'quantidade'  => $product['quantity'],				//'option'      => $option_data,				'id'          => $product['product_id'],					'peso'        => $this->weight->convert($product['weight'], $product['weight_class_id'], $this->config->get('config_weight_class')),				//'discontos' => ($product['discount'] ? $this->currency->format($product['price'] - $product['discount']) : NULL)      		);     	} 				if (isset($this->session->data['coupon'])) {			$this->load->model('checkout/coupon');			$coupon = $this->model_checkout_coupon->getCoupon($this->session->data['coupon']);			if ($coupon) {				$desconto = preg_replace("/[^0-9]/", "", $this->currency->format($coupon['discount'])); //valor do desconto				$valototal = preg_replace("/[^0-9]/", "", $this->currency->format($this->cart->getTotal())); //total da compra				$desctotalcompra = preg_replace("/[^0-9]/", "", $this->currency->format($coupon['total'])); //valo da compra que e aceito o desconto				if($valototal>=$desctotalcompra){					$this->data['cupomnome'] = $coupon['name'];					if($coupon['type']=='P' and $coupon['shipping']==0){						$valorddescon = $this->currency->format(($coupon['discount']/100)*$this->cart->getTotal());						$this->data['cupondedesconto'] = str_replace("[^0-9]", "", $valorddescon); 					} else if ($coupon['type']=='F' and $coupon['shipping']==0){						$this->data['cupondedesconto'] = $desconto;					} else if ($coupon['shipping']==1){						$this->data['fretegratis'] = true;					}				} 			}		}						$this->data['continue'] = HTTPS_SERVER . 'checkout/success';				$this->id = 'payment';				if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/moip.tpl')) {			$this->template = $this->config->get('config_template') . '/template/payment/moip.tpl';		} else {			$this->template = 'default/template/payment/moip.tpl';		}					$this->render();						}		public function confirm() {		$this->load->language('payment/moip');		$this->load->model('checkout/order');			$comment  = $this->language->get('text_instruction') . "\n\n";		$comment .= $this->language->get('text_payment');		$this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('config_order_status_id'), $comment);		if (isset($this->session->data['order_id'])) {			$this->cart->clear();			unset($this->session->data['shipping_method']);			unset($this->session->data['shipping_methods']);			unset($this->session->data['payment_method']);			unset($this->session->data['payment_methods']);			unset($this->session->data['comment']);			unset($this->session->data['order_id']);				unset($this->session->data['coupon']);		}	}}?>