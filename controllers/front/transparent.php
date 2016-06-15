<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    Pagar.me
*  @copyright 2015 Pagar.me
*  @version   1.0.0
*  @link      https://pagar.me/
*  @license  
*/

class PagarmepsTransparentModuleFrontController extends ModuleFrontController
{
	/**
	 * Do whatever you have to before redirecting the customer on the website of your payment processor.
	 */
	public function postProcess()
	{
		/**
		 * Oops, an error occured.
		 */
		if (Tools::getValue('action') == 'error')
			return $this->displayError('An error occurred while trying to redirect the customer');
		else
		{
			$cart = Context::getContext()->cart;
			$total_order = $cart->getOrderTotal();
			$pay_way = Configuration::get('PAGARME_PAY_WAY');
			$integration_mode = Configuration::get('PAGARME_INTEGRATION_MODE');
			$encryption_key = Configuration::get('PAGARME_ENCRYPTION_KEY');
			
			if(empty($encryption_key)){
				return $this->displayError('An error occurred, missing configuration for the Pagar.me Module');
			}
			if($integration_mode != 'checkout_transparente'){
				return $this->displayError('This payment mode is not activated, please contact the administrator of this site');
			}
			
			$customer = new Customer((int)$cart->id_customer);
			$address = new Address((int)$cart->id_address_invoice);
			$state = new State((int)$address->id_state);
			
			$phone = empty($address->phone)?$address->phone_mobile:$address->phone;
			$ddd = '';
			if(!empty($address->phone) && Tools::strlen($phone) > 2) {
				$ddd = Tools::substr($phone, 0, 2);
				$phone = Tools::substr($phone, 2, Tools::strlen($phone));
			}
			
			$this->context->smarty->assign(array(
				'cart_id' => $cart->id,
				'total_order' => $total_order,
				'encryption_key' => $encryption_key,
				'pay_way' => $pay_way,
				'integration_mode' => $integration_mode,
				'secure_key' => Context::getContext()->customer->secure_key,
				'customer_name' => $customer->firstname.' '.$customer->lastname,
				'customer_email' => $customer->email,
				'address_street' => $address->address1,
				'address_street_number' => '',
				'address_complementary' => $address->address2,
				'address_neighborhood' => $address->other,
				'address_city' => $address->city,
				'address_state' => $state->name,
				'address_zipcode' => $address->postcode,
				'phone_ddd' => $ddd,
				'phone_number' => $phone,
				'customer_document_number' => $customer->siret,
				
			));

			return $this->setTemplate('redirect-transparent.tpl');
		}
	}

	protected function displayError($message, $description = false)
	{
		/**
		 * Create the breadcrumb for your ModuleFrontController.
		 */
		$this->context->smarty->assign('path', '
			<a href="'.$this->context->link->getPageLink('order', null, null, 'step=3').'">'.$this->module->l('Payment').'</a>
			<span class="navigation-pipe">&gt;</span>'.$this->module->l('Error'));

		/**
		 * Set error message and description for the template.
		 */
		array_push($this->errors, $this->module->l($message), $description);

		return $this->setTemplate('error.tpl');
	}
	
		/**
	* Set default medias for this controller
	*/
	public function setMedia(){
		parent::setMedia();
		$this->context->controller->addJS(array('https://assets.pagar.me/js/pagarme.min.js',
		$this->module->getPath().'/views/js/jquery.mask.min.js',
		$this->module->getPath().'/views/js/gateway.js',
		));
	}

}
