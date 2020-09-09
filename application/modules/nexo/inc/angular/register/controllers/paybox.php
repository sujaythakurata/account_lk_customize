<?php
global 	$Options,
$PageNow,
$register_id,
$current_register;
?>
<script>
var paybox 	=	{
	store_id 			:	'<?php echo get_store_id();?>',
	decimal_precision	:	'<?php echo store_option( 'decimal_precision', 0 );?>',
	textDomain: {
		unableToProceed: `<?php echo __( 'Impossible de continuer', 'nexo' );?>`,
		canDeleteReadOnlyPayment: `<?php echo __( 'Vous ne pouvez pas supprimer un paiement en lecture seule. Si vous souhaitez faire un remboursement, accédez aux options de la commande depuis la liste des commandes.', 'gastro' );?>`
	}
}
/**
* Load PHP dependency
**/

var dependency						=
<?php echo json_encode(
	$dependencies	=	$this->events->apply_filters(
		'paybox_dependencies',
		array( '$timeout', '$scope', '$compile', '$filter', '$http', '$rootScope', 'serviceKeyboardHandler', 'serviceNumber', 'hotkeys' )
	)
);?>;

/**
* Create closure
**/

const PayBoxController						=	function( <?php echo implode( ',', $dependencies );?> ) {

	PayBoxController.prototype[ 'scope' ] 	=	$scope;
	// NexoAPI.events.doAction( 'nexopos_paybox_loaded', $scope );
	$scope.addPaymentDisabled				=	false;
	$scope.cashPaidAmount					=	0;
	$scope.currentPaymentIndex				=	null;
	$scope.defaultAddPaymentText			=	'<?php echo _s( 'Ajouter', 'nexo' );?>';
	$scope.defaultAddPaymentClass			=	'success';
	$scope.editModeEnabled					=	false;
	$scope.paymentTypes						=	<?php echo json_encode( $this->events->apply_filters( 'nexo_payments_types', $this->config->item( 'nexo_payments_types' ) ) );?>;
	$scope.paymentTypesObject				= 	new Object;
	$scope.paymentList						=	[];
	$scope.showCancelEditionButton			=	false;
	$scope.windowHeight						=	window.innerHeight;
	$scope.wrapperHeight					=	$scope.windowHeight - ( ( 56 * 2 ) + 30 );
	$scope.data 							=	{
		'foo'	:	'bar'
	};

	_.each( $scope.paymentTypes, function( value, key ) {
		$scope.paymentTypesObject	=	_.extend( $scope.paymentTypesObject, _.object( [ key ], [{
			active	:	false,
			text	:	value
		}]));
	});

	// Allow custom entry on the payementTypesObject;
	$scope.paymentTypesObject		=	NexoAPI.events.applyFilters( 'nexo_payments_types_object', $scope.paymentTypesObject );

	// Create an accessible object
	v2Checkout.paymentTypesObject					=	$scope.paymentTypesObject;

	/**
	 * Proceed to a full checkout
	 */
	$scope.fullPayment 			=	function( paymentNamespace ) {
		$scope.addPayment( paymentNamespace, Math.abs( NexoAPI.round( $scope.cart.balance ) ) );
		$timeout( () => {
			$( '[data-bb-handler="confirm"]' ).trigger( 'click' );
		}, 200 );
	}

	$scope.is  	=	function( size ) {
		return layout.is( size );
	}

	/**
	* Add Payment
	**/
	$scope.addPayment								=	function( payment_namespace, payment_amount, meta ) {

		
		const proceed 	=	true;
		const result 	=	NexoAPI.events.applyFilters( 'allow_payment', { proceed, payment_namespace, payment_amount, meta });
		
		if ( result.proceed === false ) {
			return NexoAPI.Notify().warning( '<?php echo _s( 'Attention', 'nexo' );?>', result.message || '<?php echo _s( 'Impossible d\'ajouter ce paiement', 'nexo' );?>' );
		} 

		if( payment_amount <= 0 || ( isNaN( NexoAPI.round( payment_amount ) ) && isNaN( NexoAPI.round( payment_amount ) ) ) ) {
			NexoAPI.Notify().warning( '<?php echo _s( 'Attention', 'nexo' );?>', '<?php echo _s( 'Le montant spécifié est incorrecte', 'nexo' );?>' );
			$scope.paidAmount	=	0;
			return false;
		}

		if( $scope.editModeEnabled ) {

			$scope.paymentList[ $scope.currentPaymentIndex ].amount	=	$scope.paidAmount;
			$scope.cancelPaymentEdition();

		} else {

			if( $scope.cart.paidSoFar >  ( Math.abs( $scope.cart.value ) + $scope.cart.VAT ) ) {

				NexoAPI.Notify().warning(
					'<?php echo _s( 'Attention', 'nexo' );?>',
					'<?php echo _s( 'Vous ne pouvez plus ajouter de paiement supplémentaire.', 'nexo' );?>'
				);

				return false;
			}

			$scope.paymentList.push({
				namespace		:	payment_namespace,
				text			:	_.propertyOf( $scope.paymentTypes )( payment_namespace ),
				amount			:	payment_amount,
				meta 			:	meta
			});

			

		}

		$scope.paidAmount	=	0; // reset paid amount
		$scope.refreshPaidSoFar();
		// Trigger Action added on cart
		NexoAPI.events.doAction( 'cart_add_payment', [ $scope.cart.paidSoFar, $scope.cart.balance, $scope ]);

	};



	/**
	* bind Keyboard Events
	**/

	$scope.bindKeyBoardEvent	=	function( $event ){

		for(let i = 0; i<10; i++ ) {
			hotkeys.add({
				combo: '' + i + '',
				description: 'This one goes to 11',
				callback: function() {
					if( angular.element( '.payboxwrapper' ).length > 0 ) {
						$scope.keyboardInput( i );
					} else if( angular.element( '[name="item_sku_barcode"]' ).val() != '' ){
						if( i != 0 ) {
							angular.element( '#filter-list .item-visible' ).eq( i - 1 ).trigger( 'click' );
						}						
					}
				}
			});
		}

		hotkeys.add({
			combo: '.',
			description: 'This one goes to 11',
			callback: function() {
				if( angular.element( '.payboxwrapper' ).length > 0 ) {
					$scope.keyboardInput( '.' );
				}
			}
		});

		hotkeys.add({
			combo: 'backspace',
			description: 'This one goes to 11',
			callback: function() {
				if( angular.element( '.payboxwrapper' ).length > 0 ) {
					$scope.keyboardInput( "back" )
				}
			}
		});

		hotkeys.add({
			combo: '+',
			description: 'This one goes to 11',
			callback: function() {
				setTimeout(() => {
					angular.element( '.enable_barcode_search' ).trigger( 'click' );
				},100);
			}
		});

		hotkeys.add({
			combo: '<?php echo @$Options[ 'search_item' ] == null ? "shift+f" : @$Options[ 'search_item' ];?>',
			description: 'This one goes to 11',
			callback: function() {
				setTimeout(() => {
					angular.element( '[name="item_sku_barcode"]' ).val('');
					angular.element( '[name="item_sku_barcode"]' ).focus();
				},100)
			}
		});

		hotkeys.add({
			combo: 'escape',
			description: 'This one goes to 11',
			allowIn: ['INPUT', 'SELECT', 'TEXTAREA'],
			callback: function( event ) {
				angular.element( event.target ).blur();

				if( angular.element( '.modal-dialog' ).length > 0 ) {
					$( 'div.bootbox div.modal-footer button[data-bb-handler="cancel" ]' ).trigger( 'click' );
				}
			}
		});

		hotkeys.add({
			combo: 'enter',
			description: 'This one goes to 11',
			allowIn: ['INPUT', 'SELECT', 'TEXTAREA'],
			callback: function( e ) {

				if( angular.element( '.payboxwrapper' ).length > 0 ) {
					if( NexoAPI.round( $scope.paidAmount ) > 0 ) {
						if( $scope.defaultSelectedPaymentNamespace != 'coupon' ) {
							$scope.addPayment( $scope.defaultSelectedPaymentNamespace, $scope.paidAmount );
						}						
					} else {
						angular.element( '[data-bb-handler="confirm"]' ).trigger( 'click' );
					}
				} else {
					if( angular.element( '.modal-dialog' ).length > 0 ) {
						angular.element( 'div.bootbox div.modal-footer button[data-bb-handler="confirm" ]' ).trigger( 'click' );
					}
				}

				// avoid to unblur  when the search field is used
				if( $( e.srcElement ).attr( 'name' ) == 'item_sku_barcode' ) {
					return false;
				}

				angular.element( event.target ).blur();
			}
		});

		hotkeys.add({
			combo: '<?php echo @$Options[ 'open_paywindow' ] == null ? "shift+p" : @$Options[ 'open_paywindow' ];?>',
			// allowIn: ['INPUT', 'SELECT', 'TEXTAREA'],
			description: 'Launch Payment',
			callback: function() {
				if( angular.element( '.payboxwrapper' ).length == 0 ) {
					$scope.openPayBox();
				}
			}
		});		

		hotkeys.add({
			combo: '<?php echo @$Options[ 'sales_list' ] == null ? "home" : @$Options[ 'sales_list' ];?>',
			description: 'This one goes to 11',
			callback: function() {
				$( '.home_btn' ).trigger( 'click' );
			}
		});

		hotkeys.add({
			combo: '<?php echo @$Options[ 'order_note' ] == null ? "shift+n" : @$Options[ 'order_note' ];?>',
			description: 'Open order Note',
			// allowIn: ['INPUT', 'SELECT', 'TEXTAREA'],
			callback: function() {
				$( '[data-set-note]' ).trigger( 'click' );

				setTimeout( () => {
					$( '[order_note]' ).select();
				}, 500 )
			}
		});

		hotkeys.add({
			combo: '<?php echo @$Options[ 'add_customer' ] == null ? "shift+c" : @$Options[ 'add_customer' ];?>',
			description: 'To add a customer',
			// allowIn: ['INPUT', 'SELECT', 'TEXTAREA'],
			callback: function() {
				$( '.cart-add-customer' ).trigger( 'click' );

				setTimeout( () => {
					$( '[name="customer_name"]' ).select();
				}, 500 )
			}
		});

		hotkeys.add({
			combo: '<?php echo @$Options[ 'void_order' ] == null ? "del" : @$Options[ 'void_order' ];?>',
			description: 'To void an order',
			// allowIn: ['INPUT', 'SELECT', 'TEXTAREA'],
			callback: function() {
				$( '#cart-return-to-order' ).trigger( 'click' );
			}
		});

		hotkeys.add({
			combo: '<?php echo @$Options[ 'close_register' ] == null ? "shift+4" : @$Options[ 'close_register' ];?>',
			description: 'To close a register',
			// allowIn: ['INPUT', 'SELECT', 'TEXTAREA'],
			callback: function() {
				$( '.close_register' ).trigger( 'click' );

				setTimeout( () => {
					$( '.open_balance' ).select();
				}, 800 )
			}
		});

		hotkeys.add({
			combo: '<?php echo @$Options[ 'close_register' ] == null ? "shift+d" : @$Options[ 'close_register' ];?>',
			description: 'To add discount',
			// allowIn: ['INPUT', 'SELECT', 'TEXTAREA'],
			callback: function() {
				$( '#cart-discount-button' ).trigger( 'click' );

				setTimeout( () => {
					$( '.percentage_discount' ).click();
					$( '[name="discount_value]').select();
				}, 800 )
			}
		});

		hotkeys.add({
			combo: '<?php echo @$Options[ 'cancel_discount' ] == null ? "shift+del" : @$Options[ 'cancel_discount' ];?>',
			description: 'To void an order',
			// allowIn: ['INPUT', 'SELECT', 'TEXTAREA'],
			callback: function() {
				$( '.cart-discount' ).trigger( 'click' );
			}
		});
	};

	/**
	* Cancel Payment Edition
	**/

	$scope.cancelPaymentEdition	=	function( paymentNamespace ){
		$scope.editModeEnabled			=	false;
		$scope.showCancelEditionButton	=	false;
		$scope.defaultAddPaymentText	=	'<?php echo _s( 'Ajouter', 'nexo' );?>';
		$scope.defaultAddPaymentClass	=	'success';
		$scope.paidAmount				=	0;

		if( typeof paymentNamespace != 'undefined' ) {
			$scope.selectPayment( paymentNamespace );
		}
	};


	/**
	* Confirm Order
	* @param bool action
	**/
	$scope.canConfirmOrder 		=	true;
	$scope.confirmOrder			=	function( action ) {
		if ( $scope.canConfirmOrder ) {
			if( action ) {
				if( $scope.cart.paidSoFar > 0 ) {

					var payment_means			=	$scope.paymentList[ $scope.paymentList.length - 1 ].namespace; // use the payment name as the order payment type
					var order_items				=	new Array;

					_.each( v2Checkout.CartItems, function( value, key ){

						var ArrayToPush			=	{
							...value,
							id 						:	value.ID,
							qte_added 				:	value.QTE_ADDED,
							codebar 				:	value.CODEBAR || value.REF_PRODUCT_CODEBAR,
							sale_price 				:	value.PROMO_ENABLED ? value.PRIX_PROMOTIONEL : ( v2Checkout.CartShadowPriceEnabled ? value.SHADOW_PRICE : ( v2Checkout.CartShowNetPrice ? value.PRIX_DE_VENTE_BRUT : value.PRIX_DE_VENTE_TTC ) ),
							qte_sold 				:	value.QUANTITE_VENDU,
							qte_remaining 			:	value.QUANTITE_RESTANTE,
							// @since 2.8.2
							stock_enabled 			:	value.STOCK_ENABLED,
							// @since 2.9.0
							discount_type 			:	value.DISCOUNT_TYPE,
							category_id 			: 	value.REF_CATEGORIE,
							original 				: 	value,
							discount_amount			:	value.DISCOUNT_AMOUNT,
							discount_percent 		:	value.DISCOUNT_PERCENT,
							metas 					:	typeof value.metas == 'undefined' ? {} : value.metas,
							name 					:	value.DESIGN,
							alternative_name 		:	value.ALTERNATIVE_NAME,
							inline 					:	typeof value.INLINE != 'undefined' ? value.INLINE : 0, // if it's an inline item
							tax 					:	parseFloat( value.PRIX_DE_VENTE_TTC ) - parseFloat( value.PRIX_DE_VENTE_BRUT ),
							total_tax				:	( parseFloat( value.PRIX_DE_VENTE_TTC ) - parseFloat( value.PRIX_DE_VENTE_BRUT ) ) * parseFloat( value.QTE_ADDED ),
						};

						// improved @since 2.7.3
						// add meta by default
						ArrayToPush.metas	=	NexoAPI.events.applyFilters( 'items_metas', ArrayToPush.metas );

						order_items.push( ArrayToPush );
					});

					var order_details					=	new Object;

					order_details.TOTAL				=	NexoAPI.round( v2Checkout.CartToPay );
					order_details.NET_TOTAL 		=	NexoAPI.round( v2Checkout.CartValue );
					order_details.REMISE			=	NexoAPI.round( v2Checkout.CartRemise );
					// @since 2.9.6
					if( v2Checkout.CartRemiseType == 'percentage' ) {
						order_details.REMISE_PERCENT	=	NexoAPI.round( v2Checkout.CartRemisePercent );
						order_details.REMISE			=	0;
					} else if( v2Checkout.CartRemiseType == 'flat' ) {
						order_details.REMISE_PERCENT	=	0;
						order_details.REMISE			=	NexoAPI.round( v2Checkout.CartRemise );
					} else {
						order_details.REMISE_PERCENT	=	0;
						order_details.REMISE			=	0;
					}

					order_details.REMISE_TYPE			=	v2Checkout.CartRemiseType;
					// @endSince
					order_details.RABAIS			=	NexoAPI.round( v2Checkout.CartRabais );
					order_details.RISTOURNE			=	NexoAPI.round( v2Checkout.CartRistourne );
					// @since 3.11.7
					order_details.REF_TAX 			=	v2Checkout.REF_TAX;
					order_details.TVA				=	NexoAPI.round( v2Checkout.CartVAT );
					order_details.REF_CLIENT			=	v2Checkout.CartCustomerID == null ? v2Checkout.customers.DefaultCustomerID : v2Checkout.CartCustomerID;
					order_details.PAYMENT_TYPE		=	$scope.paymentList.length == 1 ? $scope.paymentList[0].namespace : 'multi'; // v2Checkout.CartPaymentType;
					order_details.GROUP_DISCOUNT		=	NexoAPI.round( v2Checkout.CartGroupDiscount );
					order_details.DATE_CREATION		=	v2Checkout.CartDateTime.format( 'YYYY-MM-DD HH:mm:ss' )
					order_details.ITEMS				=	order_items;
					order_details.DEFAULT_CUSTOMER	=	v2Checkout.DefaultCustomerID;
					order_details.DISCOUNT_TYPE		=	'<?php echo @$Options[ store_prefix() . 'discount_type' ];?>';
					order_details.HMB_DISCOUNT		=	'<?php echo @$Options[ store_prefix() . 'how_many_before_discount' ];?>';
					// @since 2.7.5
					order_details.REGISTER_ID		=	'<?php echo $register_id;?>';

					// @since 2.7.1, send editable order to Rest Server
					// @deprecated
					order_details.EDITABLE_ORDERS	=	<?php echo json_encode( $this->events->apply_filters( 'order_editable', array( 'nexo_order_devis' ) ) );?>;

					// @since 2.7.3 add Order note
					order_details.DESCRIPTION		=	v2Checkout.CartNote;

					// @since 2.9.0
					order_details.TITRE				=	v2Checkout.CartTitle;

					// @since 2.8.2 add order meta
					this.CartMetas					=	NexoAPI.events.applyFilters( 'order_metas', v2Checkout.CartMetas );
					order_details.metas				=	v2Checkout.CartMetas;

					if( _.indexOf( _.keys( $scope.paymentTypes ), payment_means ) != -1 ) {

						order_details.SOMME_PERCU		=	NexoAPI.round( $scope.cart.paidSoFar );
						order_details.SOMME_PERCU 		=	isNaN( order_details.SOMME_PERCU ) ? 0 : order_details.SOMME_PERCU;

					} else {
						// Handle for custom Payment Means
						if( NexoAPI.events.applyFilters( 'check_payment_mean', [ false, payment_means ] )[0] == true ) {

							/**
							* Make sure to return order_details
							**/

							order_details		=	NexoAPI.events.applyFilters( 'payment_mean_checked', [ order_details, payment_means ] )[0];

						} else {

							NexoAPI.Bootbox().alert( '<?php echo _s('Impossible de reconnaitre le moyen de paiement.', 'nexo');?>' );
							return false;

						}
					}

					// Queue Payment
					order_details.payments				=	$scope.paymentList;

					var ProcessObj	=	NexoAPI.events.applyFilters( 'process_data', {
						url			:	v2Checkout.ProcessURL,
						type		:	v2Checkout.ProcessType
					});

					const finalURL 	=	ProcessObj.url.replace( '{author_id}', v2Checkout.CartAuthorID );

					// if we're updating an item, then we should keep the TYPE provided
					if( ProcessObj.type == 'PUT' ) {
						order_details.TYPE 			=	v2Checkout.CartType || null;
					}

					// Filter Submited Details
					if( order_details.SOMME_PERCU < order_details.TOTAL && '<?php echo store_option( 'disable_partial_order', 'no' );?>' == 'yes' ) {
						NexoAPI.Notify().warning( 
							'<?php echo _s('Une erreur s\'est produite', 'nexo');?>', 
							'<?php echo _s('Les commandes partielles ont été déscactivées.', 'nexo');?>' 
						);
						return false;
					}

					order_details	=	NexoAPI.events.applyFilters( 'before_submit_order', { order_details, saving_order : false } ).order_details;

					NexoAPI.events.doAction( 'submit_order' );

					$scope.canConfirmOrder 		=	false;
					v2Checkout.paymentWindow.showSplash();
					NexoAPI.Toast()( '<?php echo _s('Paiement en cours...', 'nexo');?>' );
					HttpRequest[ ProcessObj.type.toLowerCase() ]( finalURL, order_details ).then( result => {
						// fix issue when saving an order after having made a payment
						const returned 					=	result.data;
						$scope.cashPaidAmount			=	0;
						$scope.paymentList				=	[];
						$scope.canConfirmOrder 			=	true;

						// If order is not more editable
						<?php include( dirname( __FILE__ ) . '/__paybox-print.php' );?>
					}).catch( error => {
						$scope.canConfirmOrder 		=	true;
						v2Checkout.paymentWindow.hideSplash();
						error 	=	NexoAPI.events.applyFilters( 'pos_error_response', error );

						if ( error !== false ) {
							NexoAPI.Notify().warning( '<?php echo _s('Une erreur s\'est produite', 'nexo');?>', error.response.message || `<?php echo _s('Le paiement n\'a pas pu être effectuée.', 'nexo');?>` );
						}
					});
				} else {
					NexoAPI.Notify().warning( '<?php echo _s('Une erreur s\'est produite', 'nexo');?>', '<?php echo _s( 'Vous ne pouvez pas valider une commande qui n\'a pas reçu de paiement. Si vous souhaitez enregistrer cette commande, fermer la fenêtre de paiement et cliquez sur le bouton "En attente".', 'nexo' );?>' );
					return false;
				}
			} else {
				// When a paybox is being closed
				NexoAPI.events.doAction( 'close_paybox', $scope );
			}	
		}
	};

	/**
	* Edit Payment
	**/

	$scope.editPayment			=	function( index ){
		// let use controll whether they would allow specific payement done
		if( NexoAPI.events.applyFilters( 'allow_payment_edition', [ true, $scope.paymentList[ index ].namespace ] )[0] == true ) {
			$scope.selectPayment( $scope.paymentList[ index ].namespace );
			$scope.editModeEnabled			=	true;
			$scope.showCancelEditionButton	=	true;
			$scope.defaultAddPaymentText 	=	'<?php echo _s( 'Modifier', 'nexo' );?>';
			$scope.defaultAddPaymentClass	=	'info';
			$scope.currentPaymentIndex		=	index;
			$scope.paidAmount				=	$scope.paymentList[ index ].amount;
		}
	};

	/**
	* Keyboard Input
	**/

	$scope.keyboardInput		=	function( char, field, add ) {
		// if the field is not visible, we'll ignore
		// the function
		if ( $( '[ng-model="paidAmount"]' ).length === 0 ) {
			return;
		}

		if( typeof $scope.paidAmount	==	'undefined' ) {
			$scope.paidAmount	=	''; // reset paid amount
		}

		if( $scope.paidAmount 	==	0 ) {
			$scope.paidAmount	=	'';
		}

		if( char == 'clear' ) {
			$scope.paidAmount	=	'';
		} else if( char == '.' ) {
			$scope.paidAmount	+=	'.';
		} else if( char == 'back' ) {
			$scope.paidAmount	=	$scope.paidAmount.substr( 0, $scope.paidAmount.length - 1 );
		} else if( typeof char == 'number' ) {
			if( add ) {
				$scope.paidAmount	=	$scope.paidAmount == '' ? 0 : $scope.paidAmount;
				$scope.paidAmount	=	NexoAPI.round( $scope.paidAmount ) + NexoAPI.round( char );
			} else {
				$scope.paidAmount	=	$scope.paidAmount + '' + char;
			}
		}
	};

	/**
	 *  Open Coupon Box
	 *  @param
	 *  @return
	**/

	$scope.openCouponBox		=	function(){
		// alert( 'ok' );
	}

	$scope.totalPaid 			=	function() {
		if ( $scope.paymentList.length > 0 ) {
			return $scope.paymentList.map( payment => parseFloat( payment.amount ) )
				.reduce( ( before, after ) => before + after );
		}
		return 0;
	}

	/*
		*@V15.01 pos screen
		*add refund_money to display on modal
	*/

	$scope.defineCartValues 	=	function() {
		$scope.cart					=	{
			value						:		v2Checkout.CartValue,
			discount					:		v2Checkout.CartDiscount,
			netPayable					:		v2Checkout.CartToPay,
			itemsVAT 					:		v2Checkout.CartItemsVAT,
			VAT							:		v2Checkout.CartVAT,
			shipping    				:		v2Checkout.CartShipping ,
			refund: v2Checkout.refund_money
			// @since 3.1.3
		};
	}

	$scope.cancelDiscount 		=	function() {
		swal({
			title: `<?php echo __( 'Veuillez Confirmer', 'nexo' );?>`,
			text: `<?php echo __( 'Souhaitez-vous supprimer la remise ?', 'nexo' );?>`,
			showCancelButton: true
		}).then( result => {
			if ( result.value ) {
				v2Checkout.CartRemise			=	0;
				v2Checkout.CartRemiseType		=	'';
				v2Checkout.CartRemiseEnabled	=	false;
				v2Checkout.CartRemisePercent	=	0;
				v2Checkout.refreshCartValues();
				$timeout( () => {
					$scope.defineCartValues();
					$scope.refreshPaidSoFar();
					NexoAPI.events.doAction( 'paybox_discount_cancelled' );
				}, 100 );
			}
		})
	}

	/**
	* Open Box Main Function
	*
	**/

	$scope.openPayBox		=	function() {

		/**
		 * A script can lock the paybox 
		**/

		if( ! NexoAPI.events.applyFilters( 'openPayBox', true ) ) {
			return false;
		}

		$scope.defineCartValues();
		$scope.cashPaidAmount			=	0;
		$scope.paymentList				=	[];

		// Refresh Paid so far
		$scope.refreshPaidSoFar();

		/*
			*@V15.01 pos screen
			*check the if refund amount >0 then payment added else not.
			*add a new type of payment "refund" in $scope.paymentTypes
		*/
		if(v2Checkout.refund_money>0){
			$scope.addPayment("refund", v2Checkout.refund_money);
		}

		if( v2Checkout.isCartEmpty() ) {
			NexoAPI.Toast()( '<?php echo _s( 'Vous ne pouvez pas payer une commande sans article. Veuillez ajouter au moins un article', 'nexo' );?>' );
			NexoAPI.events.doAction( 'close_paybox', $scope );
			return false;
		}

		NexoAPI.Bootbox().confirm({
			message 			:	'<div class="payboxwrapper"><pay-box-content/></div>',
			title			:	'<?php echo _s( 'Paiement de la commande', 'nexo' );?>',
			buttons: {
				confirm: {
					label: '<span class="hidden-xs"><?php echo _s( 'Valider la commande', 'nexo' );?></span><span class="fa fa-shopping-cart"></i></span>',
					className: 'btn-success'
				},
				cancel: {
					label: '<?php echo _s( 'Fermer', 'nexo' );?>',
					className: 'btn-default'
				}
			},
			callback		:	function( action ) {
				return $scope.confirmOrder( action );
			},
			className 		:	'paxbox-box'
		});

		$( '.payboxwrapper' ).html( $compile( $( '.payboxwrapper' ).html() )($scope) );
		$( '.paxbox-box .modal-content > .modal-footer' ).html( $compile( $( '.paxbox-box .modal-content > .modal-footer' ).html() )($scope) );

		angular.element( '.paxbox-box' ).find( '.modal-dialog' ).css( 'width', '98%' );
		angular.element( '.paxbox-box' ).find( '.modal-body' ).css( 'padding-top', '0px' );
		angular.element( '.paxbox-box' ).find( '.modal-body' ).css( 'padding-bottom', '0px' );
		angular.element( '.paxbox-box' ).find( '.modal-body' ).css( 'padding-left', '0px' );
		angular.element( '.paxbox-box' ).find( '.modal-body' ).css( 'height', $scope.wrapperHeight );
		angular.element( '.paxbox-box' ).find( '.modal-body' ).css( 'overflow-x', 'hidden' );

		// Select first payment available
		var paymentTypesNamespaces	=	Object.keys( $scope.paymentTypes );

		$timeout( function(){
			$scope.selectPayment( paymentTypesNamespaces[0] );
			angular.element( '.cart-details' ).attr( 'style', 'height:' + ( $scope.wrapperHeight ) + 'px;padding-left:0;overflow-y:scroll;overflow-x:hidden' );
		}, 300 );

		// Add Filter - Nuwan Sampath Edit discount 
		// angular.element( '.paxbox-box .modal-footer' ).prepend( '<div class="pay_box_footer pull-left">' + NexoAPI.events.applyFilters( 'pay_box_footer', '' ) + '</div>' );

		NexoAPI.events.doAction( 'pay_box_loaded', $scope );

		v2Checkout.CartPayments.forEach( payment => {
			if ( payment.namespace !== 'coupon' ) {
				$scope.addPayment( payment.namespace, payment.amount, payment.meta )
			} else if ( payment.namespace === 'coupon' ) {
				$scope.couponDetails 	=	[ payment.meta.coupon ];
				$scope.applyCoupon({ payment : payment.meta.payment, coupon : payment.meta.coupon });
			}
		});
	};

	/**
	* Refresh Box
	**/

	$scope.refreshBox		=	function(){
		$( '.payboxwrapper' ).html( $compile( $( '.payboxwrapper' ).html() )($scope) );
	};

	/**
	* Refresh Paid So Far
	**/

	$scope.refreshPaidSoFar		=	function(){
		$scope.cart.paidSoFar		=	$scope.totalPaid();
		$scope.cart.balance			=	$scope.cart.paidSoFar - ( v2Checkout.CartValueRRR + $scope.cart.VAT + $scope.cart.shipping + $scope.cart.itemsVAT);
	};

	/**
	* Remove Payment
	**/

	$scope.removePayment	=	function( index ){

		$scope.cancelPaymentEdition();

		var removed 		=	$scope.paymentList[ index ];

		if ( removed.meta.readOnly ) {
			return swal({
				title: paybox.textDomain.unableToProceed,
				text: paybox.textDomain.canDeleteReadOnlyPayment
			});
		}

		$scope.paymentList.splice( index, 1 );

		$scope.refreshPaidSoFar();

		NexoAPI.events.doAction( 'cart_remove_payment', [ $scope.cart.paidSoFar, $scope.cart.balance, $scope, removed ]);
	};

	/**
	* Select Payment
	**/

	$scope.selectPayment		=	function( namespace ) {

		// if edit mode is enabled, disable selection
		if( $scope.editModeEnabled ) {
			NexoAPI.Bootbox().confirm( '<?php echo _s( 'Souhaitez-vous annuler la modification ?', 'nexo' );?>', function( action ) {
				if( action ) {
					$scope.cancelPaymentEdition( namespace );
				}
			});
			return false;
		}

		// reset payment options
		_.each( $scope.paymentTypesObject, function( value, key ) {
			_.propertyOf( $scope.paymentTypesObject )( key ).active = false;
		});

		// set payment option active
		if( _.propertyOf( $scope.paymentTypesObject )( namespace ) ) {
			_.propertyOf( $scope.paymentTypesObject )( namespace ).active = true;

			$scope.defaultSelectedPaymentNamespace	=	namespace;
			$scope.defaultSelectedPaymentText		=	_.propertyOf( $scope.paymentTypesObject )( namespace ).text;
		}

		/**
		* Add event when payment is selected
		* @deprecated since 3.15.9
		**/
		// if ( [ 'account' ].includes( namespace ) ) {
		// 	$( '.paxbox-box div.modal-footer button[data-bb-handler="confirm"]' ).attr( 'disabled', 'disabled' )
		// } else {
		// 	$( '.paxbox-box div.modal-footer button[data-bb-handler="confirm"]' ).removeAttr( 'disabled' );
		// }

		NexoAPI.events.doAction( 'pos_select_payment', [ $scope, namespace ] );
	}

	// Inject method within payBox controller
	<?php $this->events->do_action( 'angular_paybox_footer' );?>

	$scope.bindKeyBoardEvent();

	/**
	 * Emit Methods
	**/

	$rootScope.$on( 'payBox.openPayBox', function(){
		$scope.openPayBox();
	});

	<?php if ( 
		store_option( 'nexo_cashier_session_counted', 'no' ) === 'yes' && 
		store_option( 'nexo_enable_registers' ) === 'oui' 
	):?>
	
	/**
	 * @since 3.13.2
	 * Implementing IDLE Cashier watcher
	 */
	let idleCounter 	=	<?php echo store_option( 'nexo_cashier_idle_after', 5 );?>;
	let idleMinutes 	=	0;
	$scope.IdleWatcher 	=	function() {
		const interval 	=	setInterval( () => {
			idleMinutes++;

			if ( idleMinutes >= idleCounter ) {
				HttpRequest.get( 'api/nexopos/registers/idle/<?php echo $register_id;?><?php echo store_get_param('?');?>' ).then( result => {
					NexoAPI.Toast()( result.data.message );
					swal({
						title: `<?php echo __( 'La session s\'est arrêtée !', 'nexo' );?>`,
						type: 'info',
						html:
							`<?php echo __( 'Une inactivitée à été détectée. La session a été arrêtée. Vous pouvez a tout moment continuer votre session', 'nexo' );?>`,
						showCloseButton: true,
						showCancelButton: false,
						focusConfirm: true,
						showLoaderOnConfirm: true,
						confirmButtonText:
							`<?php echo __( 'Reprendre la session', 'nexo' );?>`,
						preConfirm: () => {
							return HttpRequest.get( 'api/nexopos/registers/active/<?php echo $register_id;?><?php echo store_get_param('?');?>' ).then( result => {
								NexoAPI.Toast()( result.data.message );
								$scope.IdleWatcher();
							}).catch( result => {
								NexoAPI.Toast()( result.data.message );
							});
						}
					});
				});
				clearInterval( interval );
			}
		}, 60000 );

		$( document ).mousemove( function() {
			idleMinutes 	=	0;
		});
		$( document ).keypress( function() {
			idleMinutes 	=	0;
		})
	}
	$scope.IdleWatcher();
	<?php endif;?>
	
	/**
	 * Listen to discount 
	 * change to update the paybox
	 */
	NexoAPI.events.addAction( 'after_discount_refresh', () => {
		const payments 			=	Object.values( $scope.paymentTypesObject ).map( ( payment, index ) => {
			const namespace 	=	Object.keys( $scope.paymentTypesObject )[ index ];
			const newPayment 	=	{ ...payment, namespace };
			$scope.paymentTypesObject[ namespace ] 	=	newPayment;
			return newPayment;
		});

		$timeout( function() {
			$scope.defineCartValues();
			$scope.refreshPaidSoFar();
			const payment 	=	payments.filter( payment => payment.active )[0];
			if ( payment ) {
				$scope.selectPayment( payment.namespace );
			}
		}, 100 );
	})

	};

	/**
	* Add closure to dependency
	**/
	dependency.push( PayBoxController );

	/**
	* Load PayBox Controller
	**/
	tendooApp.controller( 'payBox', dependency );



	</script>
