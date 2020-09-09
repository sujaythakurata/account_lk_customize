<?php
global $Options;
$this->load->module_config( 'nexo', 'nexo' );

$currentRow		=	0;

if( in_array( store_option( 'nexo_vat_type' ),  [ 'fixed', 'variable' ], true ) ) {
	$rowNbr		=	7;
} else {
	$rowNbr		=	6;
}
?>
<script>
	tendooApp.directive('payBoxContent', function () {

		var paymentTypesObject = v2Checkout.paymentTypesObject;

		angular.element('angular-cache').remove();
		const template = `
		<div class="row paybox-row" style="margin-left: 0px;">
			<div class="col-lg-2 col-md-2 hidden-sm hidden-xs payment-options bootstrap-tab-menu">
				${Object.values( paymentTypesObject ).map( ( payment, index ) => {
					const keys 	=	Object.keys( paymentTypesObject );
					const key 	=	keys[ index ];
					return `
					<div class="list-group">
						<a class="text-left list-group-item ${key}" href="javascript:void(0)"
							ng-click="selectPayment('${key}')"
							ng-class="{ 'active' : paymentTypesObject.${key}.active }"
							style="margin: 0px; border-radius: 0px; border-width: 0px 0px 1px 1px; border-style: solid; border-bottom-color: rgb(222, 222, 222); border-left-color: rgb(222, 222, 222); border-image: initial; border-top-color: initial; border-right-color: initial; line-height: 30px;">
							${payment.text}
							</a>
					</div>
					`
				}).join('')}
			</div>
			<div class="hidden-lg hidden-md col-sm-12 col-xs-12" style="border-bottom: solid 1px #EEE;">
				<div class="input-group" style="margin: 15px 0px;">
					<div class="input-group-addon"><?php echo __( 'Moyen de paiement', 'nexo' );?></div>
					<select ng-init="paymentTypeDropdown = 'cash'" ng-model="paymentTypeDropdown" ng-change="selectPayment( paymentTypeDropdown )" name="payment-type" id="input-payment-type" class="form-control" required="required">
					${Object.values( paymentTypesObject ).map( ( payment, index ) => {
						const keys 	=	Object.keys( paymentTypesObject );
						const key 	=	keys[ index ];
						return `<option value="${key}">${payment.text}</option>`
					}).join('')}
					</select>					
				</div>							
			</div>
			<div class="col-lg-10 col-md-10 col-sm-12 col-xs-12 payment-options-content">
				<ul class="nav nav-tabs" ng-class="{ 'nav-justified' : ! is( 'xs' ) && ! is( 'sm' ) }" style="margin-top: 15px;">
					<li style="font-size: 1.2em;" ng-class="{ 'active' : payboxTab === 'payment' }" ng-click="payboxTab = 'payment'" ng-init="payboxTab = 'payment'" role="presentation">
						<a href="javascript:void(0)">
							<?php echo __( 'Paiement', 'nexo' );?>
							<span class="label label-default">{{ cart.netPayable | moneyFormat }}</span>
						</a>
					</li>
					<li style="font-size: 1.2em;" ng-class="{ 'active' : payboxTab === 'history' }" ng-click="payboxTab = 'history'" role="presentation">
						<a href="javascript:void(0)">
							<?php echo __( 'Payé', 'nexo' );?>
							<span class="label label-default">{{ totalPaid() | moneyFormat }}</span>
						</a>						
					</li>
					<li style="font-size: 1.2em;" ng-class="{ 'active' : payboxTab === 'cart' }" ng-click="payboxTab = 'cart'"role="presentation">
						<a href="javascript:void(0)">
							<?php echo __( 'Reste', 'nexo' );?>
							<span class="label label-default">{{ cart.balance | moneyFormat }}</span>
						</a>
					</li>
				</ul>
				<div class="payment-container" ng-show="payboxTab === 'history'" id="history">
					<div class="hidden-payment-list">
						<h4 class="text-center" style="margin: 10px 0px;"><?php echo __( 'Liste des paiements', 'nexo' );?></h4>
						<ul class="list-group">
							<li ng-repeat="payment in paymentList" class="list-group-item">
								<span class="btn btn-danger btn-xs" ng-click="removePayment( $index )"><i class="fa fa-remove"></i></span>
								<span>{{ payment.text }}</span>
								<span class="pull-right">{{ payment.amount | moneyFormat }}</span>
							</li>
							<li class="list-group-item">
								<span><?php echo __( 'Total Payé', 'nexo' );?></span>
								<span class="pull-right">{{ totalPaid() | moneyFormat }}</span>
							</li>
						</ul>
					</div>
				</div>
				<div class="payment-container" ng-show="payboxTab === 'cart'" id="cart">
					<table class="table table-bordered">
						<tbody>
							<tr>
								<td><?php echo __( 'Total Products', 'nexo' );?></td>
								<td class="text-right">${v2Checkout.CartItems.map( item => item.QTE_ADDED ).reduce( (before, after)=> before + after )}</td>
							</tr>
							<tr>
								<td><?php echo __( 'Sous-Total', 'nexo' );?></td>
								<td class="text-right">{{ cart.value | moneyFormat }}</td>
							</tr>
							<tr class="bg-info">
								<td>
									<?php echo __( 'Remise', 'nexo' );?>
									<button class="btn btn-xs btn-danger" ng-click="cancelDiscount()" ng-show="cart.discount > 0"><i class="fa fa-times"></i></button>
								</td>
								<td class="text-right">
									- {{ cart.discount | moneyFormat }}
								</td>
							</tr>
							<tr>
								<td><?php echo __( 'Livraison', 'nexo' );?></td>
								<td class="text-right">{{ cart.shipping | moneyFormat }}</td>
							</tr>
							<tr>
								<td><?php echo __( 'Taxes', 'nexo' );?></td>
								<td class="text-right">{{ cart.itemsVAT + cart.VAT | moneyFormat }}</td>
							</tr>
							<tr ng-if="cart.refund >0" class="bg-danger">
								<td>REFUND</td>
								<td class="text-right">- {{cart.refund | moneyFormat}}</td>
							</tr>
							<tr class="bg-success">
								<td><?php echo __( 'Total', 'nexo' );?></td>
								<td class="text-right">{{ cart.netPayable | moneyFormat }}</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="payment-container" ng-show="payboxTab === 'payment'" id="payment">
					${Object.values( paymentTypesObject ).map( ( payment, index ) => {
						const keys 	=	Object.keys( paymentTypesObject );
						const key 	=	keys[ index ];
						const defaultTemplate 	=	`
						<default-payment 
							payment="paymentTypesObject.${key}" 
							paid_amount="paidAmount" 
							add_payment="addPayment"
							full-payment="fullPayment" 
							bind_key_board_event="bindKeyBoardEvent"
							cancel_payment_edition="cancelPaymentEdition" 
							default_add_payment_text="defaultAddPaymentText"
							default_add_payment_class="defaultAddPaymentClass"
							default_selected_payment_text="defaultSelectedPaymentText"
							default_selected_payment_namespace="defaultSelectedPaymentNamespace"
							show_cancel_edition_button="showCancelEditionButton" data="data">
						</default-payment>
						<keyboard input_name="${key}-field" keyinput="keyboardInput">
						</keyboard>						
						`;
						const customPayment 	=	[ 'coupon', 'account' ];
						return `
						<div class="tab-wrapper tab-${key}" ng-show="paymentTypesObject.${key}.active">
							${customPayment.indexOf( key ) !== -1 ? `<${key}-payment/>` : defaultTemplate}	
						</div>
						`;
					}).join('')}
				</div>
			</div>
		</div>`;
		return { template };
	});
</script>
<style>
/** Extra small devices (portrait phones, less than 576px) */

/** Small devices (landscape phones, 576px and up) */
@media (max-width: 991.98px) {
	.paxbox-box .bootbox-body, .paxbox-box .payboxwrapper {
		flex: 1 0 auto;
		display: flex;
		flex-direction: column;
	}
	.paybox-row {
		display: flex;
		flex-direction: column;
		flex: 1 0 auto;
	}
	.payment-options-content {
		padding-left: 0px;
		padding: 0;
		display: flex;
		flex: 1 0 auto;
		background: #EEE;
		flex-direction: column;
		overflow-y: auto;
	}
	.paxbox-box .tab-wrapper {
		flex: 1 0 auto;
		width: 100%;
	}
	.payment-container {
		border: solid 1px #DDD;
		border-bottom: 0px;
		padding: 15px;
		background: #FFF;
		flex-basis: 0;
    	flex-grow: 1;
		border-top: 0px;
		overflow-y: auto;
	}
	.paxbox-box .modal-body {
		display: flex;
    	flex-direction: column;
	}
	pay-box-content {
		display: flex;
		flex-direction: column;
		flex: 1 0 auto;
	}
}

/** Large devices (desktops, 992px and up) */
@media (min-width: 992px) { 
	.paxbox-box .bootbox-body, .paxbox-box .payboxwrapper {
		height: 100%;
	}
	.paybox-row {
		margin-left: 0px;
		display: flex;
		flex: 1 0 auto;
		height: 100%;
	}
	.paxbox-box .tab-wrapper {
	}
	.payment-options-content {
		border-left: solid 1px #EEE;
    	border-right: solid 1px #EEE;
		background: #EEE;
		display: flex;
		flex-direction: column;
		overflow-y: auto;
	}
	.payment-container {
		border: solid 1px #DDD;
		padding: 15px;
		background: #FFF;
		flex: 1 0 auto;
		margin-bottom: 15px;
		border-top: 0px;
	}
}
</style>