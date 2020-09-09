<?php global $Options;?>
<script>
const CartToolBoxData 	=	{
	payment_types 	:	<?php echo json_encode( $this->config->item( 'nexo_all_payment_types' ) );?>,
	textDomain 		:	{
		unknowPayment: 	`<?php echo __( 'Inconnu', 'nexo' );?>`
	}
}
</script>
<script>
tendooApp.directive( 'shipping', function(){
	return {
		restrict 		:	'E',
		templateUrl 	:	'<?php echo site_url([ 'dashboard', 'nexo', 'templates', 'shippings']);?>',
		controller 		:	[ '$scope', '$compile', '$filter', function( $scope, $compile, $filter ) {
			$scope.optionShowed 	=	true;

			$scope.$watch( 'price', function() {
				$( '.cart-shipping-amount' ).html( $filter( 'moneyFormat' )( $scope.price ) );
				v2Checkout.CartShipping  	=	parseFloat( $scope.price );
				v2Checkout.refreshCartValues();
			});

			$scope[ 'price' ] 		=	v2Checkout.CartShipping;

			// Check whether the current customer has valid informations
			$scope.isAddressValid	=	false;
			$scope.currentCustomer 	=	new Object;

			_.each( v2Checkout.customers.list, ( customer ) => {
				if( customer.ID == parseInt( v2Checkout.CartCustomerID ) ) {
					if( typeof customer.shipping_name != 'undefined' ) {
						$scope.isAddressValid	=	true;
						$scope.currentCustomer	=	customer;
					}
				}
			});

			/**
			 * Cancel Shipping
			 * @param void
			 * @return void
			**/
			
			$scope.cancelShipping = function(){
				_.each([ 
					'name', 'enterprise', 'address_1',
					'city', 'country', 'pobox',
					'state', 'surname', 'title',
					'address_2', 'phone', 'email',
					'id'
				], ( field ) => {
					$scope[ field ] 	=	'';
				});
				$scope[ 'price' ] 		=	0;
			}

			/**
			 * Toggle Options
			**/

			$scope.toggleOptions	=	function(){
				$scope.optionShowed  	=	!$scope.optionShowed;
			}

			/**
			 * toggleFillShippingInfo
			 * @param boolean
			 * @return void
			**/

			$scope.toggleFillShippingInfo 	=	function( bool ) {
				if( bool ) {

					_.each( $scope.currentCustomer, ( customer_fields, key ) => {
						if( key.substr( 0, 9 ) == 'shipping_' && _.indexOf([ 
							'name', 'enterprise', 'address_1',
							'city', 'country', 'pobox', 'price',
							'state', 'surname', 'title',
							'address_2', 'email', 'phone',
							'id'
						], key.substr( 9 ) ) != -1 ) {
							$scope[ key.substr( 9 ) ] 	=	customer_fields;
						}
					});
				} else {
					$scope.cancelShipping();
				}
			}

			// add custom cancel button
			$( '.modal-footer' ).append( '<a ng-click="cancelShipping()" class="cancel-shipping btn btn-default"><?php echo _s( 'Annuler', 'nexo' );?></a>');
			$( '[data-bb-handler="cancel"]' ).hide();
			$( '.cancel-shipping' ).replaceWith( $compile( $( '.cancel-shipping' )[0].outerHTML )($scope) );
			// bind special even to close it
			$( '.cancel-shipping' ).bind( 'click', function() {
				// close the box
				$( '[data-bb-handler="cancel"]' ).trigger( 'click' );
			});

			// Select field content
			setTimeout( function(){
				$( '[ng-model="price"]' ).select();
			}, 200 );
		}]
	}
});

tendooApp.directive( 'items', function(){
	return {
		restrict 		:	'E',
		templateUrl 	:	 '<?php echo site_url([ 'dashboard', 'nexo', 'template', 'quick_item_form' ]);?>',
		controller 		:	[ '$scope', '$compile', function( $scope, $compile ) {
			$scope.schema = {
				type: "object",
				properties: {
					item_name: { 
						type: "string", 
						minLength: 2, 
						title: "<?php echo _s( 'Nom du produit', 'nexo' );?>", 
						description: "<?php echo _s( 'Ajouter le nom du produit.', 'nexo' );?>" 
					},
					item_price : {
						type 	:	"number",
						title 	:	"<?php echo _s( 'Prix unitaire', 'nexo' );?>",
						description 	:	"<?php echo _s( 'Veuillez définir le prix de vente unitaire du produit.', 'nexo' );?>"
					},
					item_quantity : {
						type 	:	"number",
						title 	:	"<?php echo _s( 'Quantité', 'nexo' );?>",
						description 	:	"<?php echo _s( 'Veuillez définir la quantité que vous souhaitez ajouter.', 'nexo' );?>"
					}
					// ,
					// item_create : {
					// 	type 	:	"boolean",
					// 	title 	:	"<?php echo _s( 'Ajouter au stock', 'nexo' );?>",
					// 	description 	:	"<?php echo _s( 'En activant cette option, le produit sera ajouté au stock.', 'nexo' );?>"
					// },
					// item_category : {
					// 	title 	:	"<?php echo _s( 'Categorie', 'nexo' );?>",
					// 	type 	:	"string",
					// 	enum 	:	[ 'foo', 'bar' ]
					// },
					// item_provider : {
					// 	title 	:	"<?php echo _s( 'Fournisseur', 'nexo' );?>",
					// 	type 	:	"string",
					// 	enum 	:	[ 'foo', 'bar' ]
					// },
					// item_shipping : {
					// 	title 	:	"<?php echo _s( 'Collection', 'nexo' );?>",
					// 	type 	:	"string",
					// 	enum 	:	[ 'foo', 'bar' ]
					// },
					// item_sku : {
					// 	title 	:	"<?php echo _s( 'UGS', 'nexo' );?>",
					// 	type 	:	"string"
					// },
					// item_barcode : {
					// 	title 	:	"<?php echo _s( 'Code Barre', 'nexo' );?>",
					// 	type 	:	"string"
					// }
				},
				required 	:	[ 'item_name', 'item_price', 'item_quantity' ]
			};

			$scope.form = [
				"*"
			];

			function makeid()
			{
				var text = "";
				var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

				for( var i=0; i < 5; i++ )
					text += possible.charAt(Math.floor(Math.random() * possible.length));

				return text;
			}

			/**
			* Add Item
			* @return void
			**/

			$scope.addItem			=	function() {
				if( $scope.model.item_name == '' || $scope.model.item_quantity == '' || $scope.model.item_price == '' ) {
					return NexoAPI.Toast()( '<?php echo _s( 'Vous devez remplir tous champs', 'nexo' );?>' );
				}

				// Proceed add item to the cart
				let item 			=	new Object;
				let uniqueBarcode 	=	makeid();
				
				v2Checkout.addOnCart([{
					STATUS 			:	'1',
					CODEBAR 			:	uniqueBarcode,
					INLINE 			:	true, // it's an inline product,
					STOCK_ENABLED 		:	'0',
					QTE_ADDED 			:	0,
					TYPE 				:	'2',
					DESIGN 				:	$scope.model.item_name,
					PRIX_DE_VENTE 		:	$scope.model.item_price,
					PRIX_DE_VENTE_TTC 	:	$scope.model.item_price,
					PRIX_DE_VENTE_BRUT 	: 	$scope.model.item_price,
				}], uniqueBarcode, $scope.model.item_quantity, true );

				NexoAPI.events.doAction( 'add_inline_item', $scope );	

				$scope.model 			=	new Object;

				$( '[data-bb-handler="confirm"]' ).trigger( 'click' );				
			}

			// hide default modal buttons
			$( '[data-bb-handler="confirm"]' ).hide();
			$( '.modal-footer' ).append( '<a href="javascript:void(0)" ng-click="addItem()" class="confirm-btn btn btn-primary"><?php echo _s( 'Ajouter le produit', 'nexo' );?></a>')
			$( '.confirm-btn' ).replaceWith( $compile( $( '.confirm-btn' )[0].outerHTML )( $scope ) );
			
			// Set focus on the main field
			setTimeout( () => {
				$( '#item_name' ).select();
			}, 300 );	

			// add a hook to run each time the inline item popup is open
			NexoAPI.events.doAction( 'open_pos_new_item', $scope );	
		}]
	}
})

tendooApp.directive('validNumber', function() {
	return {
	require: '?ngModel',
	link: function(scope, element, attrs, ngModelCtrl) {
		if(!ngModelCtrl) {
		return; 
		}

		ngModelCtrl.$parsers.push(function(val) {
		if (angular.isUndefined(val)) {
			var val = '';
		}
		
		var clean = val.replace(/[^-0-9\.]/g, '');
		var negativeCheck = clean.split('-');
		var decimalCheck = clean.split('.');
		if(!angular.isUndefined(negativeCheck[1])) {
			negativeCheck[1] = negativeCheck[1].slice(0, negativeCheck[1].length);
			clean =negativeCheck[0] + '-' + negativeCheck[1];
			if(negativeCheck[0].length > 0) {
				clean =negativeCheck[0];
			}
			
		}
			
		if(!angular.isUndefined(decimalCheck[1])) {
			decimalCheck[1] = decimalCheck[1].slice(0,2);
			clean =decimalCheck[0] + '.' + decimalCheck[1];
		}

		if (val !== clean) {
			ngModelCtrl.$setViewValue(clean);
			ngModelCtrl.$render();
		}
		return clean;
		});

		element.bind('keypress', function(event) {
		if(event.keyCode === 32) {
			event.preventDefault();
		}
		});
	}
	};
});

/*
	*directive for the refund popup
	*@V15.01 pos screen
	*new directive for refund
*/
tendooApp.directive('refund', function(){

	return {

		restrict:'E',
		template:`<form>
			  <div class="form-group">
			    <label for="refund-id">Refund ID:</label>
			    <input type="text" class="form-control" id="refund-id" ng-model="refund_id">
			  </div>	
			</form>`,
		controller 		:	[ '$scope', '$compile', function( $scope, $compile ){
			$scope.schema = {
				type:"object",
				properties:{
					refund_id:{
						type:'string',
						minLength:2,
						title:'refund',
					}
				},
				required:['refund_id']

			},
			$scope.form = [
				"*"
			];
			$scope.doRefund = function(){

				//here fetch the refund
				if($scope.refund_id == undefined || $scope.refund_id == ''){
					return NexoAPI.Toast()( '<?php echo _s( 'Please enter a vaild ID', 'nexo' );?>' );
				}


				let id = $scope.refund_id;
				$scope.refund_id = '';
				v2Checkout.refund_details(id);
				$( '[data-bb-handler="confirm"]' ).trigger( 'click' );
			}

			//hide default modal button
			$( '[data-bb-handler="confirm"]' ).hide();
			$( '.modal-footer' ).append( '<a href="javascript:void(0)" ng-click="doRefund()" class="confirm-btn btn btn-primary"><?php echo _s( 'Confirm', 'nexo' );?></a>')
			$( '.confirm-btn' ).replaceWith( $compile( $( '.confirm-btn' )[0].outerHTML )( $scope ) );
		}]

	};
});

</script>

<script>
tendooApp.controller( 'cartToolBox', [ '$http', '$filter', '$compile', '$scope', '$timeout', 'hotkeys', '$rootScope',
	function( $http, $filter, $compile, $scope, $timeout, hotkeys, $rootScope ) {

	// set the shipping price to 0 on start
	$scope[ 'price' ] 				=	v2Checkout.CartShipping;
	$( '.cart-shipping-amount' ).html( $filter( 'moneyFormat' )( $scope.price ) );

	$scope.loadedOrders				=	new Object;
	$scope.orderDetails				=	null;
	let default_orderType			=	{
		nexo_order_devis			:	{
			title					:	'<?php echo _s( 'En attente', 'nexo' );?>',
			active					:	false
		}, nexo_order_advance			:	{
			title					:	'<?php echo _s( 'Incomplètes', 'nexo' );?>',
			active					:	false
		}
	}

	$scope.orderStatusObject			=	NexoAPI.events.applyFilters( 'history_orderType', default_orderType );
	$scope.theSpinner				=	new Object;
	$scope.theSpinner[ 'mspinner' ]	=	false;
	$scope.theSpinner[ 'rspinner' ]	=	true;
	$scope.windowHeight				=	window.innerHeight;
	$scope.wrapperHeight			=	$scope.windowHeight - ( ( 56 * 2 ) + 30 );

	/**
	 * Since the button has been moved to the pos header. It's not dynamically loaded
	 * @since 3.0.22
	**/

	$( '.history-box-button' ).replaceWith( $compile( $( '.history-box-button' )[0].outerHTML )( $scope ) );

	/**
	 * Load order for
	**/

	$scope.loadOrders			=	function( namespace ){

		$scope.theSpinner[ 'mspinner' ]	=	true;

		$http.get( '<?php echo site_url( array( 'rest', 'nexo', 'order_with_status' ) );?>' + '/' + namespace + '?<?php echo store_get_param( null );?>', {
			headers			:	{
				'<?php echo $this->config->item('rest_key_name');?>'	:	'<?php echo @$Options[ 'rest_key' ];?>'
			}
		}).then(function( returned ){
			$scope.theSpinner[ 'mspinner' ]		=	false;
			$scope.loadedOrders[ namespace ]	=	returned.data;
		});
	};

	/**
	 * Open History Box
	**/

	$scope.openHistoryBox		=	function(){
		if( ! v2Checkout.isCartEmpty() ) {
			NexoAPI.Bootbox().confirm( '<?php echo _s( 'Une commande est déjà en cours, souhaitez vous la supprimer ?', 'nexo' );?>', function( action ){
				if( action ) {
					NexoAPI.events.doAction( 'order_history_cart_busy' );
					v2Checkout.resetCart();
					$scope.openHistoryBox();
				}
			});
			return false;
		}

		NexoAPI.Bootbox().confirm({
			message 		:	'<div class="historyboxwrapper"><history-content/></div>',
			title			:	'<?php echo _s( 'Historique des commandes', 'nexo' );?>',
			className 	:	'history-box',
			buttons: {
				confirm: {
					label: '<?php echo _s( 'Ouvrir la commande', 'nexo' );?>',
					className: 'btn-success'
				},
				cancel: {
					label: '<?php echo _s( 'Fermer', 'nexo' );?>',
					className: 'btn-default'
				}
			},
			callback		:	function( action ) {
				return $scope.openOrderOnPOS( action );
			}
		});

		$( '.historyboxwrapper' ).html( $compile( $( '.historyboxwrapper' ).html() )($scope) );

		$timeout( function(){
			
			switch( layout.is() ) {
				case 'sm':
				case 'xs':
					angular.element( '.modal-dialog' ).css( 'width', '98%' );
				break;
				default:
					angular.element( '.modal-dialog' ).css( 'width', '98%' );
				break;
			}

			angular.element( '.history-box .modal-body' ).css( 'padding-top', '0px' );
			angular.element( '.history-box .modal-body' ).css( 'padding-bottom', '0px' );
			angular.element( '.history-box .modal-body' ).css( 'padding-left', '0px' );
			angular.element( '.history-box .modal-body' ).css( 'height', $scope.wrapperHeight );
			angular.element( '.history-box .modal-body' ).css( 'overflow-x', 'hidden' );
			angular.element( '.history-box .middle-content' ).attr( 'style', 'border-left:solid 1px #DEDEDE;overflow-y:scroll;height:' + $scope.wrapperHeight + 'px' );
			angular.element( '.history-box .order-details' ).attr( 'style', 'overflow-y:scroll;height:' + $scope.wrapperHeight + 'px' );
			angular.element( '.history-box .middle-content' ).css( 'padding', 0 );
		}, 150 );


		// Select first option
		$scope.selectHistoryTab( _.keys( $scope.orderStatusObject )[0] );
	};

	/**
	 * Open Order Details
	**/

	$scope.openOrderDetails			=	function( order_id ) {
		$scope.theSpinner[ 'rspinner' ]			=	true;
		$http.get( '<?php echo site_url( array( 'api', 'nexopos', 'full-order' ) );?>' + '/' + order_id + '?<?php echo store_get_param( null );?>', {
			headers			:	{
				'<?php echo $this->config->item('rest_key_name');?>'	:	'<?php echo @$Options[ 'rest_key' ];?>'
			}
		}).then(function( returned ){
			$scope.theSpinner[ 'rspinner' ]		=	false;
			$scope.orderDetails					=	{
				'order'  		:	returned.data.order,
				'items' 		:	returned.data.products,
			};
			$scope.rawOrderDetails 	=	returned.data;
		});
	};

	/**
	 * Search Order
	 */
	$scope.searchOrder 		=	function() {
		//  order_search_post
		$scope.theSpinner[ 'mspinner' ]	=	true;
		
		for( let namespace in $scope.orderStatusObject ) {
			if ( $scope.orderStatusObject[ namespace ].active ) {
				$http.post( '<?php echo site_url( array( 'rest', 'nexo', 'order_search' ) );?>' + '?<?php echo store_get_param( null );?>',{
					code 	:	$scope.search_order
				}, {
					headers			:	{
						'<?php echo $this->config->item('rest_key_name');?>'	:	'<?php echo @$Options[ 'rest_key' ];?>'
					}
				}).then(function( returned ){
					$scope.theSpinner[ 'mspinner' ]		=	false;
					$scope.loadedOrders[ namespace ]	=	returned.data;
				});
			}
		}
	}

	/**
	 * Cancel Search
	 */
	$scope.cancelSearch 	=	function(){
		$scope.theSpinner[ 'mspinner' ]	=	true;
		
		for( let namespace in $scope.orderStatusObject ) {
			if ( $scope.orderStatusObject[ namespace ].active ) {
				$scope.loadOrders( namespace );
			}
		}
	}

	/**
	 * Open Order On POS
	**/

	$scope.openOrderOnPOS			=	function( action ){
		if( action ) {

			if( $scope.orderDetails == null ) {
				NexoAPI.Notify().warning( '<?php echo _s( 'Attention', 'nexo' );?>', '<?php echo _s( 'Vous devez choisir une commande avant de l\'ouvrir.', 'nexo' );?>' );
				return false;
			}

			v2Checkout.ProcessURL  	=	"<?php echo site_url(array( 'rest', 'nexo', 'order', User::id() ) );?>/" + $scope.orderDetails.order.ID + "?store_id=<?php echo get_store_id();?>";
			v2Checkout.ProcessType 	=	'PUT';
			// NexoAPI.events.addFilter( 'process_data', function( data ){
			// 	data.url			=	

			// 	data.type			=	'PUT';
			// 	return data;
			// });

			/**
			 * Overrite open order on cart
			 * A script can then handle the way order are added to the cart
			 * @since 3.0.22
			**/
			if( NexoAPI.events.applyFilters( 'override_open_order', {
				order_details : $scope.orderDetails,
				proceed 	:	false
			}).proceed ) {
				return true;
			}

			v2Checkout.emptyCartItemTable();
			v2Checkout.CartItems			=	angular.copy( $scope.orderDetails.items );

			_.each( v2Checkout.CartItems, function( value, key ) {
				value.QTE_ADDED				=	parseFloat( value.QUANTITE );
				value.PRIX_DE_VENTE 		=	parseFloat( value.PRIX_DE_VENTE_BRUT );
				value.PRIX_DE_VENTE_TTC 	=	parseFloat( value.PRIX_DE_VENTE_BRUT );

				// if it's inline
				if( value.SKU === null ) {
					value.PRIX_DE_VENTE			=	parseFloat( value.PRIX );
					value.PRIX_DE_VENTE_TTC 	=	parseFloat( value.PRIX );
					value.PRIX_DE_VENTE_BRUT 	=	parseFloat( value.PRIX );
					value.PRIX_PROMOTIONNEL 	=	0;
					value.STOCK_ENABLED 	=	0;
					value.STATUS 			=	2;
					value.DESIGN 			=	value.NAME;
					value.INLINE 			=	true;
					value.STATUS 			=	'1';
				}

			});

			v2Checkout.CartPayments 		=	$scope.orderDetails.order.payments.map( ( payment ) => {
				return {
					amount: parseFloat( payment.MONTANT ),
					namespace: payment.PAYMENT_TYPE,
					text: CartToolBoxData.payment_types[ payment.PAYMENT_TYPE ] || CartToolBoxData.textDomain.unknowPayment,
					meta: {
						'payment_id'	:	payment.ID,
						'coupon_id'		:  	payment.REF_ID,
						'payment'		:	payment,
						'coupon'		:	payment.coupon,
						'saved'			:	true
					}
				}
			});

			// @added CartRemisePercent
			// @since 2.9.6

			if( $scope.orderDetails.order.REMISE_TYPE != '' ) {
				v2Checkout.CartRemiseType			=	$scope.orderDetails.order.REMISE_TYPE;
				v2Checkout.CartRemise				=	NexoAPI.ParseFloat( $scope.orderDetails.order.REMISE );
				v2Checkout.CartRemisePercent			=	NexoAPI.ParseFloat( $scope.orderDetails.order.REMISE_PERCENT );
				v2Checkout.CartRemiseEnabled			=	true;
			}

			if( parseFloat( $scope.orderDetails.order.GROUP_DISCOUNT ) > 0 ) {
				v2Checkout.CartGroupDiscount				=	parseFloat( $scope.orderDetails.order.GROUP_DISCOUNT ); // final amount
				v2Checkout.CartGroupDiscountAmount			=	parseFloat( $scope.orderDetails.order.GROUP_DISCOUNT ); // Amount set on each group
				v2Checkout.CartGroupDiscountType			=	'amount'; // Discount type
				v2Checkout.CartGroupDiscountEnabled			=	true;
			}

			v2Checkout.CartCustomerID						=	$scope.orderDetails.order.REF_CLIENT;

			// @since 2.7.3
			v2Checkout.CartNote								=	$scope.orderDetails.order.DESCRIPTION;

			v2Checkout.CartTitle							=	$scope.orderDetails.order.TITRE;

			// @since 3.1.2
			v2Checkout.CartShipping  						=	parseFloat( $scope.orderDetails.order.SHIPPING_AMOUNT );
			$scope.price 									=	v2Checkout.CartShipping; // for shipping directive
			$( '.cart-shipping-amount' ).html( $filter( 'moneyFormat' )( $scope.price ) );

			// broadcast open order to edit
			NexoAPI.events.doAction( 'open_order_on_pos', $scope.rawOrderDetails );

			// Restore Custom Ristourne
			v2Checkout.restoreCustomRistourne();

			// Refresh Cart
			// Reset Cart state
			v2Checkout.buildCartItemTable();
			v2Checkout.refreshCart();
			v2Checkout.refreshCartValues();

			// Restore Shipping
			// @since 3.1
			_.each( $scope.orderDetails.order.shipping, ( value, key ) => {
				$scope[ key ] 	=	value;
			});

			/**
			 * get customer and rebuild them
			 */
			$scope.getCustomers();
		}
	};

	/**
	 * Select History Tab
	**/

	$scope.selectHistoryTab			=	function( namespace ) {
		_.each( $scope.orderStatusObject, function( value, key ) {
			value.active	=	false;
		});

		_.propertyOf( $scope.orderStatusObject )( namespace ).active	=	true;

		$scope.loadOrders( namespace );

		$scope.theSpinner[ 'rspinner' ]			=	true;
		$scope.orderDetails						=	null;
	}

	/**
	 * Creating Customer
	 * @since 3.1
	**/

	$scope.calling 					= 	0;
	$scope.openCreatingUser 		=	function(){

		// create cache
		if( $( 'div.customers-directive-cache' ).length == 0 ) {
			angular.element( 'body' ).append( '<div class="customers-directive-cache" style="display:none;"></div>' );
		}

		NexoAPI.Bootbox().alert({
			message 		:	'<div class="customerwrapper"></div>',
			title			:	'<?php echo _s( 'Créer un nouveau client', 'nexo' );?>',
			buttons: {
				ok: {
					label: '<?php echo _s( 'Fermer', 'nexo' );?>',
					className: 'btn-default'
				}
			},
			callback		:	function( action ) {
				$( 'customers-main' ).appendTo( '.customers-directive-cache' );
				$scope.model        =   new Object;
			}
		});
		
		$timeout( function(){

			if( $( 'customers-main' ).length > 0 ) {
				$( '.customerwrapper' ).html( '' );
				$( 'customers-main' ).appendTo( '.customerwrapper' );
			} else {
				$( '.customerwrapper' ).append( '<customers-main></customers-main>' );
				$( 'customers-main' ).replaceWith( $compile( 
					$( 'customers-main' )[0].outerHTML )($scope) 
				);
			}

			switch( layout.is() ) {
				case 'sm':
				case 'xs':
					angular.element( '.modal-dialog' ).css( 'width', '98%' );
				break;
				default:
					angular.element( '.modal-dialog' ).css( 'width', '50%' );
				break;
			}

			angular.element( '.modal-body' ).css( 'height', $scope.wrapperHeight );
			angular.element( '.modal-body' ).css( 'background', '#f9f9f9' );
			angular.element( '.modal-body' ).css( 'overflow-x', 'hidden' );
			angular.element( '.middle-content' ).attr( 'style', 'border-left:solid 1px #DEDEDE;overflow-y:scroll;height:' + $scope.wrapperHeight + 'px' );
			angular.element( '.order-details' ).attr( 'style', 'overflow-y:scroll;height:' + $scope.wrapperHeight + 'px' );
			angular.element( '.middle-content' ).css( 'padding', 0 );
			angular.element( '.modal-footer' ).append( '<a class="btn btn-primary create-customer-footer-btn" href="javascript:void(0)" ng-click="submitForm()"><?php echo _s( 'Ajouter un client', 'nexo' );?></a>')
			
			$( '.create-customer-footer-btn' ).replaceWith( $compile( 
				$( '.create-customer-footer-btn' )[0].outerHTML )($scope) 
			);

		}, 150 );

		setTimeout( () => {
			$( '.customer-save-btn' ).remove();
			$( '.name-input-group' ).removeClass( 'input-group' );
		}, 600 );
	}

	/**
	 * Get Customer
	 * If the customers list exist obviously.
	 * @return void
	**/
	$scope.getCustomers 			=	function(){
		if( $( '.customers-list' ).length > 0 ) {
			$http.get( '<?php echo site_url( [ 'rest', 'nexo', 'customers', store_get_param( '?' ) ]);?>', {
				headers	:	{
					'<?php echo $this->config->item('rest_key_name');?>'	:	'<?php echo get_option( 'rest_key' );?>'
				}
			}).then( ( returned ) => {
				$scope.rebuildCustomers( returned.data );
			});
		}
	}

	$scope.rebuildCustomers  	=	function( customers ) {
		$( '.customers-list' ).selectpicker('destroy');
		// Empty list first
		$( '.customers-list' ).children().each(function(index, element) {
			$( this ).remove();
		});;

		customers	=	NexoAPI.events.applyFilters( 'customers_dropdown', customers );
		_.each( customers, function( value, key ){
			if( parseInt( v2Checkout.CartCustomerID ) == parseInt( value.ID ) ) {

				$( '.customers-list' ).append( '<option value="' + value.ID + '" selected="selected">' + `${value.NOM} &mdash; ${value.TEL || 'N/A' }` + '</option>' );
				// Fix customer Selection

			} else {
				$( '.customers-list' ).append( '<option value="' + value.ID + '">' + `${value.NOM} &mdash; ${value.TEL || 'N/A' }` + '</option>' );
			}
		});

		// @since 3.0.16
		v2Checkout.customers.list 	=	customers;

		if( typeof $( '.customers-list' ).attr( 'change-bound' ) == 'undefined' ) {
			$( '.customers-list' ).bind( 'change', function(){
				v2Checkout.customers.bindSelectCustomer( $( this ).val() );
			});
			$( '.customers-list' ).attr( 'change-bound', 'true' );
		}

		$( '.customers-list' ).selectpicker( 'refresh' );
	}

	NexoAPI.events.addAction( 'reset_cart', function(){
		$scope.getCustomers();
	});

	/**
	 * Open Delivery
	**/

	$scope.openDelivery 			=	function(){
		NexoAPI.Bootbox().confirm({
			message 		:	'<div class="shippingwrapper"><shipping></shipping></div>',
			title			:	'<?php echo _s( 'Livraison', 'nexo' );?>',
			buttons: {
				confirm: {
					label: '<?php echo _s( 'Confirmer', 'nexo' );?>',
					className: 'btn-primary'
				},
				cancel: {
					label: '<?php echo _s( 'Fermer', 'nexo' );?>',
					className: 'btn-default'
				}
			},
			callback		:	function( action ) {
			}
		});
		
		$timeout( function(){

			switch( layout.is() ) {
				case 'sm':
				case 'xs':
					angular.element( '.modal-dialog' ).css( 'width', '98%' );
				break;
				default:
					angular.element( '.modal-dialog' ).css( 'width', '50%' );
				break;
			}

			angular.element( '.modal-body' ).css( 'height', $scope.wrapperHeight - 100 );
			angular.element( '.modal-body' ).css( 'background', '#EEE' );
			angular.element( '.modal-body' ).css( 'overflow-x', 'hidden' );
			angular.element( '.middle-content' ).attr( 'style', 'border-left:solid 1px #DEDEDE;overflow-y:scroll;height:' + $scope.wrapperHeight + 'px' );
			angular.element( '.modal-body' ).attr( 'style', 'overflow-y:scroll;height:' + $scope.wrapperHeight + 'px;background:#EEE' );
			angular.element( '.middle-content' ).css( 'padding', 0 );

			$( '.shippingwrapper' ).replaceWith( $compile( 
				$( '.shippingwrapper' )[0].outerHTML )($scope) 
			);

			setTimeout( () => {
				$( '#customer-shipping-addresses input' ).bind( 'change', function() {
					const itIncludes 	=	[ 
						'name', 'enterprise', 'address_1',
						'city', 'country', 'pobox',
						'state', 'surname', 'title',
						'address_2', 'phone', 'email',
						'id'
					].includes( $( this ).attr( 'ng-model' ) );

					if ( itIncludes ) {
						v2Checkout.CartDeliveryInfo[ $( this ).attr( 'ng-model' ) ] 	=	$scope[ $( this ).attr( 'ng-model' ) ];
					}
				});
			}, 1000 )		
			
		}, 150 );
	}

	/**
	 * Open Click Add Product
	 * @param void
	 * @return void
	 * @since 3.1
	**/

	$scope.openAddQuickItem 		=	function() {
		NexoAPI.Bootbox().confirm({
			message 		:	'<div class="items_wrapper"><items></items></div>',
			title			:	'<?php echo _s( 'Ajouter un produit', 'nexo' );?>',
			buttons: {
				confirm: {
					label: '<?php echo _s( 'Ajouter', 'nexo' );?>',
					className: 'btn-primary'
				},
				cancel: {
					label: '<?php echo _s( 'Fermer', 'nexo' );?>',
					className: 'btn-default'
				}
			},
			callback		:	function( action ) {
				if( ! action ) {
					NexoAPI.events.doAction( 'close_add_inline_item', $scope );
				}
			}
		});
		
		$timeout( function(){

			switch( layout.is() ) {
				case 'sm':
				case 'xs':
					angular.element( '.modal-dialog' ).css( 'width', '98%' );
				break;
				default:
					angular.element( '.modal-dialog' ).css( 'width', '50%' );
				break;
			}

			angular.element( '.modal-body' ).css( 'height', $scope.wrapperHeight - 100 );
			angular.element( '.modal-body' ).css( 'background', '#f9f9f9' );
			angular.element( '.modal-body' ).css( 'overflow-x', 'hidden' );
			angular.element( '.middle-content' ).attr( 'style', 'border-left:solid 1px #DEDEDE;overflow-y:scroll;height:' + $scope.wrapperHeight + 'px' );
			angular.element( '.modal-body' ).attr( 'style', 'overflow-y:scroll;height:' + $scope.wrapperHeight + 'px' );
			angular.element( '.middle-content' ).css( 'padding', 0 );

			$( '.items_wrapper' ).replaceWith( $compile( 
				$( '.items_wrapper' )[0].outerHTML )($scope) 
			);
		}, 150 );
	}


	/**
	* refund button modal form
	* @param refund_id
	* @return void
	* *@V15.01 pos screen
	**/
	$scope.openRefund =	function() {
		NexoAPI.Bootbox().confirm({
			message 		:	`
			<div class="refund">
			<refund></refund>
			</div>`,
			title			:	'Refund',
			buttons: {
				confirm: {
					label: '<?php echo _s( 'Confirm', 'nexo' );?>',
					className: 'btn-primary'
				},
				cancel: {
					label: '<?php echo _s( 'Fermer', 'nexo' );?>',
					className: 'btn-default'
				}
			},
			callback		:	function( action ) {
				if( ! action ) {
				}
			}
		});
		
		$timeout( function(){
			switch( layout.is() ) {
				case 'sm':
				case 'xs':
					angular.element( '.modal-dialog' ).css( 'width', '98%' );
				break;
				default:
					angular.element( '.modal-dialog' ).css( 'width', '50%' );
				break;
			}

			$( '.refund' ).replaceWith( $compile( 
				$( '.refund' )[0].outerHTML )($scope) 
			);
		}, 150 );
	}





	$scope.model = {
		item_name 		:	'',
		item_price 		:	'',
		item_quantity 	:	''
	};

	$scope.getCustomers();

	hotkeys.add({
		combo: '<?php echo @$Options[ 'pending_order' ] == null ? "shift+s" : @$Options[ 'pending_order' ];?>',
		description: 'This one goes to 11',
		// allowIn: ['INPUT', 'SELECT', 'TEXTAREA'],
		callback: function() {
			$scope.openHistoryBox()
		}
	});

	// add shipping information to the order
	NexoAPI.events.addFilter( 'before_submit_order', ( data ) => {
		
		data.order_details[ 'shipping' ]		=	new Object;

		_.each([ 
			'name', 'enterprise', 'address_1', 'price',
			'city', 'country', 'pobox', 'title',
			'state', 'surname', 'email', 'phone',
			'address_2', 
			'id'
		], ( field ) => {
			data.order_details[ 'shipping' ][ field ] 		=	$scope[ field ] == null ? '' : $scope[ field ];
		});

		return data;
	});

	// Clear shipping information when the cart is resetted
	NexoAPI.events.addAction( 'reset_cart', () => {
		if( typeof $scope.cancelShipping != 'undefined' ) {
			$scope.cancelShipping();
		}		
	});

	// reset default URL when cart is reset
	NexoAPI.events.addAction( 'reset_cart', function(){
		// NexoAPI.events.removeFilter( 'process_data' );
		$scope.price   	=	0; // reset the shipping price
		$( '.cart-shipping-amount' ).html( $filter( 'moneyFormat' )( $scope.price ) );
	});	

	/**
	 * Events
	 * @since 3.8.2
	**/

	$rootScope.$on( 'open-history-box', function(){
		$scope.openHistoryBox();
	});
}]);
</script>
