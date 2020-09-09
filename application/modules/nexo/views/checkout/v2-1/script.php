<?php
$this->load->module_config('nexo', 'nexo');
global $Options, $store_id, $current_register;
?>
<script type="text/javascript">

  document.addEventListener('keydown', function(event) {
    if( event.keyCode == 13 || event.keyCode == 17 || event.keyCode == 74 )
      event.preventDefault();
  });

"use strict";

var v2CheckoutTextDomain 		=	{
	addProduct 					:	`<?php _e('Veuillez ajouter un produit...', 'nexo');?>`,
	editPriceNotExistingItems 	:	`<?php echo __( 'Impossible de modifier le produit, il n\'existe plus sur le panier', 'nexo' );?>`,
	anErrorOccured 				: 	'<?php echo _s( 'Une erreur s\'est produite', 'nexo' );?>',
	notEnoughQuantities 		: 	'<?php echo addslashes(__( 'La quantité restante du produit n\'est pas suffisante.', 'nexo' ) );?>',
	setZeroRemovesItem 			: 	'<?php echo addslashes(__('Défininr "0" comme quantité, retirera le produit du panier. Voulez-vous continuer ?', 'nexo'));?>',
	sockExhausted 				: 	'<?php echo addslashes(__('Stock épuisé', 'nexo'));?>',
	unableToAddTheProduct  		: 	'<?php echo addslashes(__('Impossible d\'ajouter l\'article', 'nexo'));?>', 
	unableToFindTheProduct 		: 	'<?php echo addslashes(__('Impossible de récupérer l\'article, ce dernier est introuvable, indisponible ou le code envoyé est incorrecte.', 'nexo'));?>',
	productStockExhausted 		: 	'<?php echo addslashes(__('Impossible d\'ajouter ce produit, car son stock est épuisé.', 'nexo'));?>',
	wrongQuantityProvided 		: 	`<?php echo __( 'Veuillez fournir une quantité valide.', 'nexo' );?>`,
}
var v2CheckoutOptions 			=	{
	taxes 						:		<?php echo json_encode( $taxes );?>,
	registerID 					:		`<?php echo $register_id;?>`,
	taxType 					:		'<?php echo store_option( 'nexo_vat_type' );?>',
	orderedCategories 			: 		<?php echo json_encode( $orderedCategories );?>,
	vatPercent 					: 		<?php echo in_array(@$Options[ store_prefix() . 'nexo_vat_percent' ], array( null, '' )) ? 0 : @$Options[ store_prefix() . 'nexo_vat_percent' ];?>,
	showItemVat 				: 		<?php echo store_option( 'nexo_vat_type' ) == 'item_vat' ? 'true': 'false';?>,
	showNetPrice 				: 		<?php echo store_option( 'nexo_show_net_price', 'yes' ) === 'yes' ? 'true': 'false';?>,
	showRemainingQuantity 		: 		<?php echo store_option( 'nexo_show_remaining_qte', 'no' ) === 'yes' ? 'true': 'false';?>
}
var v2Checkout					=	new function(){

	this.ProductListWrapper		=	'#product-list-wrapper';
	this.CartTableBody			=	'#cart-table-body';
	this.ItemsListSplash		=	'#product-list-splash';
	this.CartTableWrapper		=	'#cart-details-wrapper';
	this.CartTableBody			=	'#cart-table-body';
	this.CartDiscountButton		=	'#cart-discount-button';
	this.ProductSearchInput		=	'#search-product-code-bar';
	this.ItemSettings			=	'.item-list-settings';
	this.ItemSearchForm			=	'#search-item-form';
	this.CartPayButton			=	'#cart-pay-button';
	this.CartCancelButton		=	'#cart-return-to-order';
	this.CartRegisterID  		=	v2CheckoutOptions.registerID;
	this.From 					=	null;
	this.itemsStock 			=	new Object;
	this.angular 				=	new Object;
	// @since 3.x
	this.enableBarcodeSearch	=	false;
	/**
	 * @since 3.11.7
	 */
	this.taxes 					=	v2CheckoutOptions.taxes;
	this.orderedCategories 		=	v2CheckoutOptions.orderedCategories;
	this.showRemainingQuantity 	=	v2CheckoutOptions.showRemainingQuantity;
	this.CartVATType			=	v2CheckoutOptions.taxType;
	this.CartVATPercent			=	v2CheckoutOptions.vatPercent;
	this.CartShowItemVAT  		=	v2CheckoutOptions.showItemVat;
	this.CartShowNetPrice  		=	v2CheckoutOptions.showNetPrice;

	this.REF_TAX 				=	0;
	this.CartPayments 			=	[];
	this.CartDeliveryInfo 		=	new Object;
	this.refund_money = 0;
	this.refund_qty = 0;
	this.refund_id_array = new Array();
	this.net_pay = 0;

	if( this.CartVATType == '' ) {
		this.CartVATType		=	'disabled';
	}

	$("#cart-table-notice").remove();

	/*
		*@V15.01 pos screen
		*get refund details

	*/

	this.refund_details = function(refund_id){
		if(!this.refund_id_array.includes(refund_id)){
			
			let url = `<?php echo site_url(array('api', 'nexopos', 'orders', 'refund_money'));?>/${refund_id}/?store_id=<?php echo $store_id?>`;
			$.ajax({
				url:url,
				method:'GET',
				error:(err)=>{console.log(err);},
				success:(res)=>{
					if(res['TOTAL']){
						this.refund_id_array.push(refund_id);
						this.makeRefundTable(res);
					}
					else
					NexoAPI.Toast()( '<?php echo _s( 'No refund data might be Refund ID is Invalid', 'nexo' );?>' );
				}
			});
		}else{
			NexoAPI.Toast()( '<?php echo _s( 'Refund alreday Added', 'nexo' );?>' );
		}

	}

	/*
		*@V15.01 pos screen
		*make table to display refund
	*/

	this.makeRefundTable = function(res){
		this.refund_money += parseInt(res['TOTAL']);
		this.refund_qty += 1;
		let refund_ = NexoAPI.DisplayMoney(this.refund_money);
		$(".refund-body").empty();
		let tr = `
			<tr style="font-weight:bold;">
				<td style="width:190px; margin-left:.5em;padding-left:.5em;">REFUND AMOUNT</td>
				<td class="text-center hidden-xs" style="width:110px;">${refund_}</td>
				<td class="text-center hidden-xs" style="width:100px;">${this.refund_qty}</td>
				<td class="text-right"style="width:100px;padding-right:.5em;">- ${refund_}</td>
			</tr>
		`;

		$(".refund-body").append(tr);
		NexoAPI.Toast()( '<?php echo _s( 'Refund Added', 'nexo' );?>' );
		this.addFooterRefund();
		this.refreshCartValues();
		this.refreshCartVisualValues();

	}

	/*
		*@V15.01 pos screen
		*remove refund and reset all values when cancel button click
	*/
	this.removeRefund = function(){
		$(".refund-body").empty();
		$(".refund-footer").remove();
		this.refund_qty = 0;
		this.refund_money = 0;
		this.refund_id_array = new Array();
		this.net_pay = 0;
		this.refreshCartVisualValues();
	}
	/*
		*@V15.01 pos screen
		*add refund at cart book footer
	*/
	this.addFooterRefund = function(){
		$(".refund-footer").remove();
		let tr = `
			<tr class="active refund-footer" style="font-weight:bold;">
				<td></td>
				<td></td>
				<td class="text-right">Refund</td>
				<td class="text-right">
					<span class="pull-right">- $ ${this.refund_money}</span>
				</td>
			<tr>
		`;
		$(".refund-mate").before(tr);

	}



	/**
	 *  Add on cart
	 *  @param object item to fetch
	 *  @return void
	 *  @deprecated
	**/

	this.addOnCart 				=	function(_item, codebar, qte_to_add, allow_increase, filter) {

		/**
		* If Item is "On Sale"
		**/

		if( _item.length > 0 && _item[0].STATUS == '1' ) {

			var InCart				=	false;
			var InCartIndex			=	null;

			// Let's check whether an item is already added to cart
			_.each( v2Checkout.CartItems, function( value, _index ) {
				// let check whether the item is an inline item
				// the item must not be inline to be added over an existing item
				if( value.CODEBAR == _item[0].CODEBAR && ! value.INLINE ) {
					InCartIndex		=	_index;
					InCart			=	true;
				}
			});

			if( InCart ) {

				// if increase is disabled, we set value
				var comparison_qte	=	allow_increase == true ? parseInt( v2Checkout.CartItems[ InCartIndex ].QTE_ADDED ) + parseInt( qte_to_add ) : qte_to_add;

				/**
				* 	For "Out of Stock" notice to work, item must be physical
				* 	and Stock management must be enabled
				**/

				if(
					parseInt( _item[0].QUANTITE_RESTANTE ) - ( comparison_qte ) < 0
					&& _item[0].TYPE == '1'
					&& _item[0].STOCK_ENABLED == '1'
				) {
					NexoAPI.Notify().warning(
						v2CheckoutTextDomain.anErrorOccured,
						v2CheckoutTextDomain.notEnoughQuantities
					);
				} else {
					if( allow_increase ) {
						// Fix concatenation when order was edited
						v2Checkout.CartItems[ InCartIndex ].QTE_ADDED	=	parseInt( v2Checkout.CartItems[ InCartIndex ].QTE_ADDED );
						v2Checkout.CartItems[ InCartIndex ].QTE_ADDED	+=	parseInt( qte_to_add );
					} else {
						if( qte_to_add > 0 ){
							v2Checkout.CartItems[ InCartIndex ].QTE_ADDED	=	parseInt( qte_to_add );
						} else {
							NexoAPI.Bootbox().confirm( v2CheckoutTextDomain.setZeroRemovesItem, function( response ) {
								// Delete item from cart when confirmed
								if( response ) {
									v2Checkout.CartItems.splice( InCartIndex, 1 );
									v2Checkout.buildCartItemTable();
								}
							});
						}
					}
				}
			} else {
				if( 
					parseInt( _item[0].QUANTITE_RESTANTE ) - qte_to_add < 0 
					&& _item[0].TYPE == '1'
					&& _item[0].STOCK_ENABLED == '1'
				) {
					NexoAPI.Notify().warning(
						v2CheckoutTextDomain.sockExhausted,
						v2CheckoutTextDomain.productStockExhausted
					);
				} else {
					// improved @since 2.7.3
					// add meta by default
					var ItemMeta	=	NexoAPI.events.applyFilters( 'items_metas', [] );

					var FinalMeta	=	[ [ 'QTE_ADDED' ], [ qte_to_add ] ] ;

					_.each( ItemMeta, function( value, key ) {
						FinalMeta[0].push( _.keys( value )[0] );
						FinalMeta[1].push( _.values( value )[0] );
					});

					// @since 2.9.0
					// add unit item discount
					_item[0].DISCOUNT_TYPE		=	'percentage'; // has two type, "percent" and "flat";
					_item[0].DISCOUNT_AMOUNT	=	0;
					_item[0].DISCOUNT_PERCENT	=	0;

					v2Checkout.CartItems.unshift( _.extend( _item[0], _.object( FinalMeta[0], FinalMeta[1] ) ) );
				}
			}

			// Add Item To Cart
			NexoAPI.events.doAction( 'add_to_cart', v2Checkout );

			// Build Cart Table Items
			v2Checkout.refreshCart();
			v2Checkout.buildCartItemTable();

		} else {
			NexoAPI.Notify().error( 
				v2CheckoutTextDomain.unableToAddTheProduct, 
				v2CheckoutTextDomain.unableToFindTheProduct
			);
		}
	}

	/**
	 * Check product expiration
	 * @param object intance of PosItem
	 * @return boolean
	 */
	this.hasProductExpired 	=	function( item ) {
		if ( moment( item.EXPIRATION_DATE ).isBefore( tendoo.date ) ) {
			if ( item.ON_EXPIRE_ACTION == 'lock_sales' ) {
				return true;
			}
		}
		return false;
	}

	/**
	* Reloaded Add To Cart
	* @param object
	* @return void
	**/

	this.addToCart 	=	function({ item, barcode, quantity = 1, index = null, increase = true, filter = null}) {

		// If we're just adding new quantity (not increasing). We should restore already added quantity
		// if the item has already been added.
		let currentQty 	=	0;

		if ( index != null ) {
			item 		=	this.CartItems[ index ];
			currentQty 	=	parseInt( this.CartItems[ index ].QTE_ADDED );
		}

		/**
		 * We might check here is the item is available for sale or not
		 */

		if( this.hasProductExpired( item ) ) {
			return NexoAPI.Notify().error( 
				'<?php echo addslashes( __('Impossible d\'ajouter l\'article', 'nexo'));?>', 
				'<?php echo addslashes(__('La date d\'expiration du produit a été atteint. Ce dernier ne peut pas être vendu. Veuillez contacter l\'administrateur pour plus d\'information.', 'nexo'));?>' 
			);
		}

		/**
		* If Item is "On Sale"
		**/

		if ( item.STATUS == '1' ) {
			let remainingQuantity 	=	this.itemsStock[ item.CODEBAR ];
			let testQuantity 		=	quantity;

			/**
			 * If the item type is grouped 
			 * the  we'll check the stock for each item if the stock
			 */

			// if( increase && index ) {
			// 	if ( item.TYPE == '3' ) {
			// 	} else {
			// 		// if we're just increasing an existing item.
			// 		// proceed to some check before
			// 		testQuantity 		=	parseInt( v2Checkout.CartItems[ index ].QTE_ADDED ) + quantity;
			// 	}
			// }
			if ( item.TYPE == '3' ) {
				
				let hasLowStock 	=	false;
				let hasExpiredItem 	=	false;

				if ( item.INCLUDED_ITEMS === undefined ) {
					return NexoAPI.Notify().error( 
						`<?php echo __( 'Impossible d\'ajouter le produit', 'nexo' );?>`,
						`<?php echo __( 'Ce produit groupé n\'est composé d\'aucun produit. Veuillez ajouter un produit et essayez à nouveau.', 'nexo' );?>`
					);
				}

				item.INCLUDED_ITEMS.forEach( _item => {
					
					// check for the included item expiration
					if ( this.hasProductExpired( _item ) ) {
						NexoAPI.Notify().error( 
							'<?php echo addslashes( __( 'Impossible d\'ajouter l\'article', 'nexo'));?>', 
							'<?php echo addslashes(__('La date d\'expiration du produit <strong>{item_name}</strong>, inclus dans ce groupe a été atteint. Ce dernier ne peut pas être vendu.', 'nexo'));?>'.replace( '{item_name}', _item.DESIGN )
						);
						hasExpiredItem 	=	true;
					} else { 
						// even if the item has expired, maybe the sales aren't locked
						// if included items are physical
						// and the main item has stock enabled
						if ( _item.TYPE == '1' && item.STOCK_ENABLED == '1' ) {
							let testIncludedItemQuantity 	=	this.itemsStock[ _item.CODEBAR ] - NexoAPI.round( _item.quantity );
							if ( testIncludedItemQuantity >= 0 ) {
								this.itemsStock[ _item.CODEBAR ] 	=	testIncludedItemQuantity;
							} else {
								NexoAPI.Notify().warning(
									'<?php echo addslashes( __('Stock épuisé', 'nexo'));?>',
									'<?php echo addslashes( __( 'Le produit <strong>{item_name}</strong> inclus dans ce groupe à un stock épuisé.', 'nexo'));?>'.replace( '{item_name}', _item.DESIGN )
								);
								hasLowStock 	=	true;
							}
						}
					}
				});

				if ( hasLowStock || hasExpiredItem ) {
					return false;
				}

				// after having checked the stock for the items included
				// we're adding the item to the cart
				this.__addItem({ item, index, quantity, increase });

			} else { // this include physical and digital only
				if( 
					remainingQuantity - testQuantity < 0 
					&& item.TYPE === '1'
					&& item.STOCK_ENABLED === '1' 
					&& increase === true
				) {
					NexoAPI.Notify().warning(
						'<?php echo addslashes( __( 'Stock épuisé', 'nexo'));?>',
						'<?php echo addslashes( __( 'Impossible d\'ajouter ce produit, car son stock est épuisé.', 'nexo'));?>'
					);
				} else if ( 
						testQuantity > ( remainingQuantity + currentQty ) &&
						item.TYPE === '1' &&
						item.STOCK_ENABLED === '1' &&
						increase === false
					) {
					NexoAPI.Notify().warning(
						'<?php echo addslashes( __( 'Stock épuisé', 'nexo'));?>',
						'<?php echo addslashes( __( 'Impossible d\'ajouter ce produit, car son stock est épuisé.', 'nexo'));?>'
					);
				} else {

					// update grid item remaining quantity when the stock management of this item is enabled
					if( item.STOCK_ENABLED == '1' && item.TYPE == '1' ) {
						if( increase ) {
							this.itemsStock[ item.CODEBAR ] 	=	remainingQuantity - quantity;
						} else {
							// restore added quantity
							this.itemsStock[ item.CODEBAR ] 	=	( remainingQuantity + currentQty ) - quantity;
						}
					}

					this.__addItem({ item, index, quantity, increase });
				}
			}

		} else {
			NexoAPI.Notify().error( '<?php echo addslashes(__('Impossible d\'ajouter l\'article', 'nexo'));?>', '<?php echo addslashes(__('Impossible de récupérer l\'article, ce dernier est introuvable, indisponible ou le code envoyé est incorrecte.', 'nexo'));?>' );
		}
	}

	/**
	 * Add item private 
	 */
	this.__addItem 				=	function({ item, index, quantity, increase }) {
		
		let currentItem;

		/**
		* If item already exist on the cart. 
		* Then we can increase the quantity if that item is not an inline item
		* @since 3.10.1
		**/
		if( currentItem = this.getItem( item.CODEBAR ) && index === null ) {
			// only works for item which are'nt inline
			if( currentItem.INLINE != '1' || currentItem.INLINE == undefined ) {	
				index 	=	currentItem.LOOP_INDEX; // provided by this.getItem(...);
			}
		}
		
		if( index != null ) {
			if( this.CartItems[ index ] ) {
				if( increase == true ) {
					this.CartItems[ index ].QTE_ADDED 	+=	quantity
				} else {
					this.CartItems[ index ].QTE_ADDED 	=	quantity
				}
			}
		} else {
			// improved @since 2.7.3
			// add meta by default
			var ItemMeta	=	NexoAPI.events.applyFilters( 'items_metas', [] );
			var FinalMeta	=	[ [ 'QTE_ADDED' ], [ quantity ] ] ;

			_.each( ItemMeta, function( value, key ) {
				FinalMeta[0].push( _.keys( value )[0] );
				FinalMeta[1].push( _.values( value )[0] );
			});
 
			// @since 2.9.0
			// add unit item discount
			item.DISCOUNT_TYPE		=	'percentage'; // has two type, "percent" and "flat";
			item.DISCOUNT_AMOUNT	=	0;
			item.DISCOUNT_PERCENT	=	0;

			let newItem 			=	_.extend( item, _.object( FinalMeta[0], FinalMeta[1] ) );

			v2Checkout.CartItems.unshift( newItem );
		}


		NexoAPI.events.doAction( 'add_to_cart', v2Checkout );

		// Build Cart Table Items
		v2Checkout.refreshCart();
		v2Checkout.buildCartItemTable();
	}

	/**
	* Show Product List Splash
	**/

	this.showSplash				=	function( position ){
		if( position == 'right' ) {
			// Simulate Show Splash
			$( this.ItemsListSplash ).show();
			$( this.ProductListWrapper ).find( '.box-body' ).css({'visibility' :'hidden'});
		}
	};

	/**
	* Hid Splash
	**/

	this.hideSplash				=	function( position ){
		if( position == 'right' ) {
			// Simulate Show Splash
			$( this.ItemsListSplash ).hide();
			$( this.ProductListWrapper ).find( '.box-body' ).css({'visibility' :'visible'});
		}
	};

	/**
	* Close item options
	**/

	this.bindHideItemOptions		=	function(){
		$( '.close-item-options' ).bind( 'click', function(){
			$( v2Checkout.ItemSettings ).trigger( 'click' );
		});
	}

	/**
	* Bind Add To Item
	*
	* @return void
	**/

	this.bindAddToItems			=	function(){
		$( '#filter-list' ).find( '.filter-add-product[data-category-name]' ).each( function(){
			$( this ).bind( 'click', function(){
				var codebar	=	$( this ).attr( 'data-codebar' );
				v2Checkout.retreiveItem( codebar );
			});
		});
	};

	/**
	 * Retreive item on db
	 * Each retreive add item as single entry
	 * @param string barcode
	 * @return void
	**/

	this.retreiveItem 			=	function( barcode, callback = null, index ) {
		$.ajax( '<?php echo site_url(array( 'rest', 'nexo', 'item' ));?>/' + barcode + '/sku-barcode<?php echo store_get_param( '?' );?>', { 
			success 	:	( items ) => {
				this.treatFoundItem({ item : items[0], callback, index })
			},
			error 		:	( result ) => {
				if ( result.status == 404 ) {
					return NexoAPI.Toast()( '<?php echo __( 'Impossible de retrouver le produit ou code barre incorrect', 'nexo' );?>' );
				}
			}
		});
	}

	/**
	* Bind Add Reduce Actions on Cart table items
	**/

	this.bindAddReduceActions	=	function(){

		$( '#cart-table-body .item-reduce' ).each(function(){
			$( this ).bind( 'click', function(){
				
				let parent	=	$( this ).closest( 'tr' );
				let index 	=	$( this ).closest( 'tr' ).attr( 'cart-item-id' );
				
				_.each( v2Checkout.CartItems, ( value, loop_index ) => {
					if( typeof loop_index != 'undefined' ) {
						if( loop_index == index ) {

							let status		=	NexoAPI.events.applyFilters( 'reduce_from_cart', {
								barcode 	:	value.CODEBAR,
								item 		:	value,
								proceed 	:	true
							});

							if( status.proceed == true ) {
								
								value.QTE_ADDED--;

								/**
								 * Handling grouped items
								 */
								if ( value.TYPE == '3' ) {
									value.INCLUDED_ITEMS.forEach( _item => {
										v2Checkout.itemsStock[ _item.CODEBAR ] += NexoAPI.round( _item.quantity );
									});
								}
								
								// If item reach "0";
								if( parseInt( value.QTE_ADDED ) == 0 ) {
									v2Checkout.CartItems.splice( loop_index, 1 );
								}

								// restore removed quantity
								let remainingQuantity 	=	v2Checkout.itemsStock[ value.CODEBAR ];

								// if item is physical and stock is enabled
								if( value.STOCK_ENABLED == '1' && value.TYPE == '1' ) {
									v2Checkout.itemsStock[ value.CODEBAR ] 	=	remainingQuantity + 1;
								}					
							}	
						}
					}
				});

				// Add Item To Cart
				NexoAPI.events.doAction( 'reduce_from_cart', v2Checkout );

				v2Checkout.buildCartItemTable();
			});
		});

		$( '#cart-table-body .item-add' ).each( function() {
			$( this ).bind( 'click', function() {
				var parent		=	$( this ).closest( 'tr' );
				let index 		=	$( this ).closest( 'tr' ).attr( 'cart-item-id' );
				// check if item is INLINE.
				let item 		=	v2Checkout.CartItems[ index ];
				let barcode 	=	NexoAPI.events.applyFilters( 'nexo_pos_barcode_attribute', $( parent ).data( 'item-barcode' ), {
					item, parent, index
				});

				if( item.INLINE ) {
					v2Checkout.addToCart({
						item,
						index,
						increase 	:	true
					});
				} else {
					v2Checkout.retreiveItem( barcode, ( item ) => {
						v2Checkout.addToCart({
							item,
							index,
							increase 	:	true
						});
					}, index );
				}
			});
		});
	};

	/**
	* Bind Add by input
	**/

	this.bindAddByInput			=	function(){
		var currentInputValue	=	0;
		$( '[name="shop_item_quantity"]' ).bind( 'focus', function(){
			currentInputValue	=	$( this ).val();
		});
		$( '[name="shop_item_quantity"]' ).bind( 'change', function(){
			var parent 			=	$( this ).closest( 'tr' );
			var value 			=	parseInt( $( this ).val() );
			var barcode			=	$( parent ).data( 'item-barcode' );
			let index 			=	$( parent ).attr( 'cart-item-id' );
			if( value > 0 ) {
				v2Checkout.addToCart({
					index,
					barcode,
					quantity  	:	value,
					increase  	:	false
				})
			} else {
				NexoAPI.Toast()( v2CheckoutTextDomain.wrongQuantityProvided );
				$( this ).val( currentInputValue );
			}
		});

		<?php if (@$Options[ store_prefix() . 'nexo_enable_numpad' ] != 'non'):?>
		// Bind Num padd
		$( '[name="shop_item_quantity"]' ).bind( 'click', function(){
			v2Checkout.showNumPad( $( this ), '<?php echo addslashes(__('Définir la quantité à  ajouter', 'nexo'));?>', null, false );
			setTimeout( () => {
				$( '[name="numpad_field"]' ).select();
			}, 500 );
		});
		<?php endif;?>
	}

	/**
	* Bind Add Note
	* @since 2.7.3
	**/

	this.bindAddNote			=	function(){
		$( '[data-set-note]' ).bind( 'click', function(){

			var	dom		=	'<h4 class="text-center"><?php echo _s( 'Ajouter une note à la commande', 'nexo' );?></h4>' +
			'<div class="form-group">' +
			'<label for="exampleInputFile"><?php echo _s( 'Note de la commande', 'nexo' );?></label>' +
			'<textarea class="form-control" order_note rows="10"></textarea>' +
			'<p class="help-block"><?php echo _s( 'Cette note sera rattachée à la commande en cours.', 'nexo' );?></p>' +
			'</div>';

			NexoAPI.Bootbox().confirm( dom, function( action ) {
				if( action ) {
					v2Checkout.CartNote		=	$( '[order_note]' ).val();
				}
			});

			$( '[order_note]' ).val( v2Checkout.CartNote );
		});
	};

	/**
	* Bind hover product
	* @since 3.0.19
    **/

	this.bindHoverItemName 		=	function(){

		if( ! NexoAPI.events.applyFilters( 'hover_item_name', true ) ) {
			return false;
		}

		$( '[cart-item]' ).each( function() {
			$( this ).on( 'mouseenter', function() {
				// item-name
				let item 	=	v2Checkout.getItem( $( this ).attr( 'data-item-barcode' ) );

				if( item ) {
					let speed;
					let length 	=	item.DESIGN.length;
					
					switch( true ) {
						case ( length >= 20 && length < 25 ) : speed 	=	1;break;
						case ( length >= 25 && length < 40 ) : speed 	=	2;break;
						case ( length >= 40 && length < 50 ) : speed 	=	3;break;
						case ( length >= 50 && length < 60 ) : speed 	=	4;break;
						case ( length >= 60 ) : speed 	=	5;break;
						default : speed 	=	4;break;
					}

					if( length > 23 ) {
						$( this ).find( '.item-name' ).attr( 'previous', htmlEntities( $( this ).find( '.item-name' ).html() ) );
						$( this ).find( '.item-name' ).html( '<marquee class="marquee_me" behavior="alternate" scrollamount="' + speed + '" direction="left" style="width:100%;float:left;">' + item.DESIGN + '</marquee>' );
					}
				}
			});			
		})

		$( '[cart-item]' ).each( function() {
			$( this ).on( 'mouseleave', function() {
				let old_previous 	=	htmlEntities( $( this ).find( '.item-name' ).html() ); 
				
				// to avoid displaying empty string
				if( old_previous != '' && typeof $( this ).find( '.item-name' ).attr( 'previous' ) != 'undefined' ) {
					$( this ).find( '.item-name' ).html( EntitiesHtml( $( this ).find( '.item-name' ).attr( 'previous' ) ) );
					$( this ).find( '.item-name' ).attr( 'previous', old_previous );
				}	
			});		
		});
	}

	/**
	* Bind Category Action
	* @since 2.7.1
	**/

	this.bindCategoryActions	=	function(){
		$( '.slick-wrapper' ).remove(); // empty all
		$( '.add_slick_inside' ).html( '<div class="slick slick-wrapper"></div>' );

		// Build New category wrapper @since 2.7.1
		this.orderedCategories.forEach( cat => {
			/**
			* display the category only if it has a product
			*/
			const ids 	=	Object.keys( this.ItemsCategories ).map( id => parseInt( id ) );

			if ( ids.includes( parseInt( cat.ID ) ) ) {
				$( '.slick-wrapper' ).append( `
					<div 
					data-category-name="${cat.NOM.toLowerCase()}"
					data-cat-id="${cat.ID}" class="text-center slick-item">${cat.NOM}</div>
				` );
			}
		});

		$('.slick').slick({
			infinite			: 	false,
			arrows			:	false,
			slidesToShow		: 	2,
			slidesToScroll	: 	2,
			variableWidth : true
		});

		$( '.slick-item' ).bind( 'click', function(){

			var categories	=	new Array;
			var proceed		=	true;

			if( $( this ).hasClass( 'slick-item-active' ) ) {
				proceed		=	false;
			}

			$( '.slick-item.slick-item-active' ).each( function(){
				$( this ).removeClass( 'slick-item-active' );
			});

			if( ! $( this ).hasClass( 'slick-item-active' ) && proceed == true ) {
				$( this ).toggleClass( 'slick-item-active' );
				categories.push( $( this ).data( 'cat-id' ) );
			}

			v2Checkout.ActiveCategories		=	categories;
			v2Checkout.filterItems( categories );
		});

		/**
		 * if there is a default enabled category
		 * let's click that to make it enabled 
		 * by default
		 */
		const enabledCategories 	=	this.orderedCategories.filter( cat => cat.ENABLED === 'true' );
		if ( enabledCategories.length > 0 ) {
			enabledCategories.forEach( cat => {
				$( `[data-cat-id="${cat.ID}"]`).trigger( 'click' );
			})
		} 

		// Bind Next button
		$( '.cat-next' ).bind( 'click', function(){
			$('.slick').slick( 'slickNext' );
		});
		// Bind Prev button
		$( '.cat-prev' ).bind( 'click', function(){
			$('.slick').slick( 'slickPrev' );
		});
	}

	/**
	* Bind Change Unit Price
	* @since 2.9.0
	**/

	this.bindChangeUnitPrice	=	function(){

		<?php if( @$Options[ store_prefix() . 'unit_price_changing' ] == 'yes' ):?>

		$( '.item-unit-price' ).bind( 'click', function(){

			var itemIndex		=	$(this).closest( 'tr' ).attr( 'cart-item-id' );

			if( ! itemIndex ) {
				NexoAPI.Toast()( v2CheckoutTextDomain.editPriceNotExistingItems );
				return false;
			}

			var currentItem 				=	v2Checkout.CartItems[ itemIndex ];
			var promo_start					= 	moment( currentItem.SPECIAL_PRICE_START_DATE );
			var promo_end					= 	moment( currentItem.SPECIAL_PRICE_END_DATE );

			var MainPrice					= 	NexoAPI.round( v2Checkout.CartShowNetPrice ? currentItem.PRIX_DE_VENTE_BRUT : currentItem.PRIX_DE_VENTE_TTC )
			var Discounted					= 	'';
			var CustomBackground			=	'';
			currentItem.PROMO_ENABLED		=	false;

			if( promo_start.isBefore( v2Checkout.CartDateTime ) ) {
				if( promo_end.isSameOrAfter( v2Checkout.CartDateTime ) ) {
					currentItem.PROMO_ENABLED	=	true;
					const oldMainPrice 	=	MainPrice;
					MainPrice			=	NexoAPI.round( currentItem.PRIX_PROMOTIONEL );
					Discounted			=	'<small><del>' + NexoAPI.DisplayMoney( NexoAPI.round( oldMainPrice ) ) + '</del></small>';
					CustomBackground	=	'background:<?php echo $this->config->item('discounted_item_background');?>';
				}
			}

			// @since 2.7.1
			if( v2Checkout.CartShadowPriceEnabled ) {
				MainPrice			=	NexoAPI.round( currentItem.SHADOW_PRICE );
			}

			$( this ).replaceWith( '<td width="110"><div class="input-group input-group-sm"><input type="number" value="' + MainPrice + '" class="unit-price-form form-control" aria-describedby="sizing-addon3"></div></td>' );

			// Select field content
			$( '.unit-price-form' ).select();

			$( '.unit-price-form' ).bind( 'blur', function(){

				if( ! isNaN( NexoAPI.round( $( this ).val() ) ) ) {
					$( this ).closest( 'td' ).replaceWith( '<td width="110" class="text-center item-unit-price"  style="line-height:30px;">' + NexoAPI.DisplayMoney( $( this ).val() ) + '</td>' );
				} else {
					$( this ).closest( 'td' ).replaceWith( '<td width="110" class="text-center item-unit-price"  style="line-height:30px;">' + NexoAPI.DisplayMoney( MainPrice ) + '</td>' );
				}

				if( v2Checkout.CartShadowPriceEnabled ) {
					currentItem.SHADOW_PRICE	=	$( this ).val();
				} else {
					if( promo_start.isBefore( v2Checkout.CartDateTime ) ) {
						if( promo_end.isSameOrAfter( v2Checkout.CartDateTime ) ) {
							currentItem.PRIX_PROMOTIONEL	=	$( this ).val();
						}
					} else {
						currentItem.PRIX_DE_VENTE		=	$( this ).val();

						let tax 	=	v2Checkout.taxes.filter( tax => tax.ID === currentItem.REF_TAXE )[0];

						if( tax !== undefined ) {
							let taxType 	=	currentItem.TAX_TYPE;
							let taxValue 	=	( 
								NexoAPI.round( currentItem.PRIX_DE_VENTE ) * NexoAPI.round( tax.RATE )
							) / 100;

							if( taxType === 'inclusive' ) {
								let originalPrice 	=	currentItem.PRIX_DE_VENTE;
								currentItem.PRIX_DE_VENTE_TTC	=	originalPrice;
								currentItem.PRIX_DE_VENTE_BRUT	=	NexoAPI.round( originalPrice ) - taxValue;
							} else if ( taxType === 'exclusive' ) {
								currentItem.PRIX_DE_VENTE_TTC	=	NexoAPI.round( currentItem.PRIX_DE_VENTE ) + taxValue;
								currentItem.PRIX_DE_VENTE_BRUT	=	NexoAPI.round( currentItem.PRIX_DE_VENTE );
							}
						} else {
							currentItem.PRIX_DE_VENTE_TTC	=	$( this ).val();
							currentItem.PRIX_DE_VENTE_BRUT	=	$( this ).val();
						}
					}
				}

				v2Checkout.buildCartItemTable();
			});
		});

		<?php endif;?>
	}

	/**
	* Bind remove cart group discount
	**/

	this.bindRemoveCartGroupDiscount	=	function(){
		$( '.btn.cart-group-discount' ).each( function(){
			if( ! $( this ).hasClass( 'remove-action-bound' ) ) {
				$( this ).addClass( 'remove-action-bound' );
				$( this ).bind( 'click', function(){
					NexoAPI.Bootbox().confirm( '<?php echo addslashes(__('Souhaitez-vous annuler la réduction de groupe ?', 'nexo'));?>', function( action ) {
						if( action == true ) {
							v2Checkout.cartGroupDiscountReset();
							v2Checkout.refreshCartValues();
						}
					})
				});
			}
		});
	};

	/**
	* Bind Remove Cart Remise
	* Let use to cancel a discount directly from the cart table, when it has been added
	**/

	this.bindRemoveCartRemise	=	function(){
		$( '.btn.cart-discount-button' ).each( function(){
			if( ! $( this ).hasClass( 'remove-action-bound' ) ) {
				$( this ).addClass( 'remove-action-bound' );
				$( this ).bind( 'click', function(){
					NexoAPI.Bootbox().confirm( '<?php echo addslashes(__('Souhaitez-vous annuler cette remise ?', 'nexo'));?>', function( action ) {
						if( action == true ) {
							v2Checkout.CartRemise			=	0;
							v2Checkout.CartRemiseType		=	'';
							v2Checkout.CartRemiseEnabled	=	false;
							v2Checkout.CartRemisePercent	=	0;
							v2Checkout.refreshCartValues();
						}
					})
				});
			}
		});
	};

	/**
	* Bind Remove Cart Ristourne
	**/

	this.bindRemoveCartRistourne=	function(){
		$( '.btn.cart-ristourne' ).each( function(){
			if( ! $( this ).hasClass( 'remove-action-bound' ) ) {
				$( this ).addClass( 'remove-action-bound' );
				$( this ).bind( 'click', function(){
					NexoAPI.Bootbox().confirm( '<?php echo addslashes(__('Souhaitez-vous annuler cette ristourne ?', 'nexo'));?>', function( action ) {
						if( action == true ) {
							v2Checkout.CartRistourne		=	0;
							v2Checkout.CartRistourneEnabled	=	false;
							v2Checkout.refreshCartValues();
						}
					})
				});
			}
		});
	};

	/**
	* Bind Add Discount
	**/

	this.bindAddDiscount		=	function( config ){
		var	DiscountDom			=
		'<div id="discount-box-wrapper">' +
		'<h4 class="text-center"><?php echo addslashes(__('Appliquer une remise', 'nexo'));?><span class="discount_type"></h4><br>' +
		'<div class="input-group input-group-lg">' +
		'<span class="input-group-btn">' +
		'<button class="btn btn-default percentage_discount" type="button"><?php echo addslashes(__('Pourcentage', 'nexo'));?></button>' +
		'</span>' +
		'<input type="number" name="discount_value" class="form-control" placeholder="<?php echo addslashes(__('Définir le montant ou le pourcentage ici...', 'nexo'));?>">' +
		'<span class="input-group-btn">' +
		'<button class="btn btn-default flat_discount" type="button"><?php echo addslashes(__('Espèces', 'nexo'));?></button>' +
		'</span>' +
		'</div>' +
		'<br>' +
		'<div class="row">' +
		'<div class="col-lg-12">' +
		'<div class="row">' +
		'<div class="col-lg-2 col-md-2 col-xs-2">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad7" value="<?php echo addslashes(__('7', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-2 col-md-2 col-xs-2">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad8" value="<?php echo addslashes(__('8', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-2 col-md-2 col-xs-2">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad9" value="<?php echo addslashes(__('9', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-6 col-md-6 col-xs-6">' +
		'<input type="button" class="btn btn-warning btn-block btn-lg numpaddel" value="<?php echo addslashes(__('Retour arrière', 'nexo'));?>"/>' +
		'</div>' +
		'</div>' +
		'<br>'+
		'<div class="row">' +
		'<div class="col-lg-2 col-md-2 col-xs-2">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad4" value="<?php echo addslashes(__('4', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-2 col-md-2 col-xs-2">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad5" value="<?php echo addslashes(__('5', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-2 col-md-2 col-xs-2">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad6" value="<?php echo addslashes(__('6', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-6 col-md-6 col-xs-6">' +
		'<input type="button" class="btn btn-danger btn-block btn-lg numpadclear" value="<?php echo addslashes(__('Vider', 'nexo'));?>"/>' +
		'</div>' +
		'</div>' +
		'<br>'+
		'<div class="row">' +
		'<div class="col-lg-2 col-md-2 col-xs-2">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad1" value="<?php echo addslashes(__('1', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-2 col-md-2 col-xs-2">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad2" value="<?php echo addslashes(__('2', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-2 col-md-2 col-xs-2">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad3" value="<?php echo addslashes(__('3', 'nexo'));?>"/>' +
		'</div>' +
		'</div>' +
		'<br>' +
		'<div class="row">' +
		'<div class="col-lg-2 col-md-2 col-xs-2">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad00" value="<?php echo addslashes(__('00', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-4 col-md-6 col-xs-6">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad0" value="<?php echo addslashes(__('0', 'nexo'));?>"/>' +
		'</div>' +
		'</div>' +
		'</div>' +
		'</div>' +
		'</div>';

		config					=	_.extend( {}, config );

		NexoAPI.Bootbox().confirm( DiscountDom, function( action ) {
			if( action == true ) {

				var value	=	$( '[name="discount_value"]' ).val();

				if( typeof config.onExit	==	'function' ) {
					config.onExit( value );
				}
			}
		});

		$( '.percentage_discount' ).bind( 'click', function(){
			if( ! $( this ).hasClass( 'active' ) ) {
				if( $( '.flat_discount' ).hasClass( 'active' ) ) {
					$( '.flat_discount' ).removeClass( 'active' );
				}

				$( this ).addClass( 'active' );

				// Proceed a quick check on the percentage value
				$( '[name="discount_value"]' ).select();

				if( typeof config.onPercentDiscount	==	'function' ) {
					config.onPercentDiscount();
				}

				$( '.discount_type' ).html( '<?php echo addslashes(__(' : <span class="label label-primary">au pourcentage</span>', 'nexo'));?>' );
			}
		});

		$( '.flat_discount' ).bind( 'click', function(){
			if( ! $( this ).hasClass( 'active' ) ) {
				if( $( '.percentage_discount' ).hasClass( 'active' ) ) {
					$( '.percentage_discount' ).removeClass( 'active' );
				}

				$( this ).addClass( 'active' );

				$( '[name="discount_value"]' ).select();

				if( typeof config.onFixedDiscount	==	'function' ) {
					config.onFixedDiscount();
				}

				$( '.discount_type' ).html( '<?php echo addslashes(__(' : <span class="label label-info">à prix fixe</span>', 'nexo'));?>' );
			}
		});

		// Fillback form
		if( typeof config.beforeLoad == 'function' ) {
			config.beforeLoad();
		}

		$( '[name="discount_value"]' ).bind( 'blur', function(){

			if( NexoAPI.round( $( this ).val() ) < 0 ) {
				$( this ).val( 0 );
			}

			if( typeof config.beforeLoad == 'function' ) {
				config.onFieldBlur();
			}
		});

		for( var i = 0; i <= 9; i++ ) {
			$( '#discount-box-wrapper' ).find( '.numpad' + i ).bind( 'click', function(){
				var current_value	=	$( '[name="discount_value"]' ).val();
				current_value	=	current_value == '0' ? '' : current_value;
				$( '[name="discount_value"]' ).val( current_value + $( this ).val() );
			});
		}

		$( '.numpadclear' ).bind( 'click', function(){
			$( '[name="discount_value"]' ).val(0);
		});

		$( '.numpad00' ).bind( 'click', function(){
			var current_value	=	$( '[name="discount_value"]' ).val();
			current_value	=	current_value == '0' ? '' : current_value;
			$( '[name="discount_value"]' ).val( current_value + '00' );
		});

		$( '.numpaddot' ).bind( 'click', function(){
			var current_value	=	$( '[name="discount_value"]' ).val();
			current_value	=	current_value == '0' ? '' : current_value;
			$( '[name="discount_value"]' ).val( current_value + '...' );
		});

		$( '.numpaddel' ).bind( 'click', function(){
			var numpad_value	=	$( '[name="discount_value"]' ).val();
			numpad_value	=	numpad_value.substr( 0, numpad_value.length - 1 );
			numpad_value 	= 	numpad_value == '' ? 0 : numpad_value;
			$( '[name="discount_value"]' ).val( numpad_value );
		});

		setTimeout( () => {
			// Select field content
			$( '[name="discount_value"]' ).select();
		}, 500 );
	};

	/**
	* Bind Quick Edit item
	*
	**/

	this.bindQuickEditItem		=	function(){
		$( '.quick_edit_item' ).bind( 'click', function(){

			var CartItem		=	$( this ).closest( '[cart-item]' );
			var Barcode			=	$( CartItem ).data( 'item-barcode' );
			var CurrentItem		=	false;

			_.each( v2Checkout.CartItems, function( value, key ) {
				if( typeof value != 'undefined' ) {
					if( value.CODEBAR == Barcode ) {
						CurrentItem		=	value;
						return;
					}
				}
			});

			// @remove
			if( v2Checkout.CartShadowPriceEnabled == false ) {
				window.open( '<?php echo site_url('dashboard/nexo/items/edit');?>/' + CurrentItem.ID, '__blank' );
				return;
			}

			if( CurrentItem != false ) {
				var dom				=	'<h4 class="text-center"><?php echo _s( 'Modifier l\'article :', 'nexo' );?> ' + CurrentItem.DESIGN + '</h4>' +

				'<div class="input-group">' +
				'<span class="input-group-addon" id="basic-addon1"><?php echo _s( 'Prix de vente', 'nexo' );?></span>' +
				'<input type="text" class="current_item_price form-control" placeholder="<?php echo _s( 'Définir un prix de vente', 'nexo' );?>" aria-describedby="basic-addon1">' +
				'<span class="input-group-addon"><?php echo _s( 'Seuil :', 'nexo' );?> <span class="sale_price"></span></span>' +
				'</div>';

			} else {

				NexoAPI.Bootbox().alert( '<?php echo _s( 'Produit introuvable', 'nexo' );?>' );

				var dom				=	'';
			}

			// <?php echo site_url('dashboard/nexo/produits/lists/edit');?>

			NexoAPI.Bootbox().confirm( dom, function( action ) {
				if( action ) {
					if( NexoAPI.round( $( '.current_item_price' ).val() ) < NexoAPI.round( CurrentItem.PRIX_DE_VENTE_TTC ) ) {
						NexoAPI.Bootbox().alert( '<?php echo _s( 'Le nouveau prix ne peut pas être inférieur au prix minimal (seuil)', 'nexo' );?>' );
						return false;
					} else {
						_.each( v2Checkout.CartItems, function( value, key ) {
							if( typeof value != 'undefined' ) {
								if( value.CODEBAR == CurrentItem.CODEBAR ) {
									value.SHADOW_PRICE	=	NexoAPI.round( $( '.current_item_price' ).val() );
									return;
								}
							}
						});
						// Refresh Cart
						v2Checkout.buildCartItemTable();
					}
				}
			});

			$( '.sale_price' ).html( NexoAPI.DisplayMoney( v2Checkout.CartShowNetPrice ? CurrentItem.PRIX_DE_VENTE_BRUT : CurrentItem.PRIX_DE_VENTE_TTC ) );
			$( '.current_item_price' ).val( CurrentItem.SHADOW_PRICE );

		});
	};

	/**
	* BindToggle Comptact Mode
	**/

	this.bindToggleComptactMode	=	function(){
		$( '.toggleCompactMode' ).bind( 'click', function(){
			v2Checkout.toggleCompactMode();
		});
	}

	/**
	* Bind Unit Item Discount
	* @return void
	* @since 2.9.0
	**/

	this.bindUnitItemDiscount 	=	function(){
		$( '.item-discount' ).bind( 'click', function(){

			let index 			=	$( this ).closest( 'tr' ).attr( 'cart-item-id' );
			const item			=	v2Checkout.CartItems[ index ];
			var salePrice		=	v2Checkout.getItemSalePrice( item );

			DiscountPopup.open( 'item', { salePrice, item, index, title : `<?php echo __( 'Remise sur produit', 'nexo' );?>` });
		});
	};

	/**
	* Build Cart Item table
	* @return void
	**/

	this.buildCartItemTable		=	function() {

		NexoAPI.events.doAction( 'before_cart_refreshed', v2Checkout );

		// Empty Cart item table first
		this.emptyCartItemTable();
		this.CartValue		=	0;
		var _tempCartValue	=	0;
		this.CartTotalItems	=	0;
		this.CartItemsVAT 	=	0;

		if( _.toArray( this.CartItems ).length > 0 ){
			// reset item vat
			_.each( this.CartItems, function( value, key ) {

				var promo_start			= 	moment( value.SPECIAL_PRICE_START_DATE );
				var promo_end			= 	moment( value.SPECIAL_PRICE_END_DATE );
				var itemVat 			=	0

				if( v2Checkout.CartShowItemVAT && value.metas !== undefined && value.metas.tax !== undefined ) {
					itemVat 					=	value.metas.tax.VALUE;
					v2Checkout.CartItemsVAT	 	+=	NexoAPI.round( itemVat );
				}

				var MainPrice			= 	NexoAPI.round( v2Checkout.CartShowNetPrice ? value.PRIX_DE_VENTE_BRUT : value.PRIX_DE_VENTE_TTC );
				var Discounted			= 	'';
				var CustomBackground	=	'';

				value.PROMO_ENABLED	=	false;

				if( promo_start.isBefore( v2Checkout.CartDateTime ) && promo_end.isSameOrAfter( v2Checkout.CartDateTime ) ) {
					value.PROMO_ENABLED	=	true;
					const oldMain 		=	MainPrice;
					MainPrice			=	NexoAPI.round( value.PRIX_PROMOTIONEL );
					Discounted			=	'<small><del>' + NexoAPI.DisplayMoney( NexoAPI.round( oldMain ) ) + '</del></small>';
					CustomBackground	=	'background:<?php echo $this->config->item('discounted_item_background');?>';
				}

				// @since 2.7.1
				if( v2Checkout.CartShadowPriceEnabled ) {
					MainPrice			=	NexoAPI.round( value.SHADOW_PRICE );
				}

				// <span class="btn btn-primary btn-xs item-reduce hidden-sm hidden-xs">-</span> <input type="number" style="width:40px;border-radius:5px;border:solid 1px #CCC;" maxlength="3"/> <span class="btn btn-primary btn-xs   hidden-sm hidden-xs">+</span>

				// <?php echo site_url('dashboard/nexo/produits/lists/edit');?>
				// /' + value.ID + '

				// :: alert( value.DESIGN.length );
				var item_design		=	NexoAPI.events.applyFilters( 'cart_item_name', {
					original 			:	value.DESIGN || value.NAME,
					displayed 			:	value.DESIGN || value.NAME
				}); // .length > 20 ? '<span style="text-overflow:hidden">' + value.DESIGN.substr( 0, 20 ) + '</span>' : value.DESIGN ;

				var DiscountAmount	=	value.DISCOUNT_TYPE	== 'percentage' ? value.DISCOUNT_PERCENT + '%' : NexoAPI.DisplayMoney( value.DISCOUNT_AMOUNT );

				/**
				 * if the net price is disabled, we still want to
				 * the net total price to compute well
				 */
				var itemSubTotal	=	MainPrice * parseInt( value.QTE_ADDED );

				if( value.DISCOUNT_TYPE == 'percentage' && NexoAPI.round( value.DISCOUNT_PERCENT ) > 0 ) {
					var itemPercentOff	=	( itemSubTotal * NexoAPI.round( value.DISCOUNT_PERCENT ) ) / 100;
					itemSubTotal	-=	itemPercentOff;
				} else if( value.DISCOUNT_TYPE == 'flat' && NexoAPI.round( value.DISCOUNT_AMOUNT ) > 0 ) {
					var itemPercentOff	=	 ( NexoAPI.round( value.DISCOUNT_AMOUNT ) * parseInt( value.QTE_ADDED ) );
					itemSubTotal	-=	itemPercentOff;
				}

				// <marquee class="marquee_me" behavior="alternate" scrollamount="4" direction="left" style="width:100%;float:left;">Earl Klugh - HandPucked</marquee>

				$( '#cart-table-body' ).find( '.table' ).append(
					'<tr cart-item-id="' + key + '" cart-item data-line-weight="' + ( MainPrice * parseInt( value.QTE_ADDED ) ) + '" data-item-barcode="' + value.CODEBAR + '">' +
						'<td width="200" class="text-left" style="line-height:30px;"><p style="width:45px;margin:0px;float:left">' + NexoAPI.events.applyFilters( 'cart_before_item_name', '' ) + '</p><p style="text-transform: uppercase;float:left;width:76%;margin-bottom:0px;" class="item-name">' + item_design.displayed + '</p></td>' +
						'<td width="110" class="text-center item-unit-price hidden-xs"  style="line-height:30px;">' + NexoAPI.DisplayMoney( MainPrice ) + ' ' + Discounted + '</td>' +
						'<td width="100" class="text-center item-control-btns">' +
						'<div class="input-group input-group-sm">' +
						'<span class="input-group-btn item-control-btns-wrapper">' +
							'<button class="btn btn-default item-reduce">-</button>' +
							'<button name="shop_item_quantity" value="' + value.QTE_ADDED + '" class="btn btn-default" style="width:50px;">' + value.QTE_ADDED + '</button>' +
							'<button class="btn btn-default item-add">+</button>' +
						'</span>' +
						'</td>' +
						<?php if( @$Options[ store_prefix() . 'unit_item_discount_enabled' ] == 'yes' ):?>
						'<td width="90" class="text-center item-discount"  style="line-height:28px;"><span class="btn btn-default btn-sm">' + DiscountAmount + '</span></td>' +
						<?php endif;?>
						'<td width="100" class="text-right item-total-price" style="line-height:30px;">' + NexoAPI.DisplayMoney( itemSubTotal ) + '</td>' +
					'</tr>'
				);

				_tempCartValue	+=	( ( ! v2Checkout.CartShowNetPrice ? itemSubTotal - itemVat : itemSubTotal ) ); // MainPrice * parseInt( value.QTE_ADDED )

				// Just to count all products
				v2Checkout.CartTotalItems	+=	parseInt( value.QTE_ADDED );
			});
			
			this.CartValue	=	_tempCartValue;

		} else {
			$( this.CartTableBody ).find( '.table>tbody' ).html( `<tr id="cart-table-notice"><td colspan="4">${v2CheckoutTextDomain.addProduct}</td></tr>` );
		}

		this.bindAddReduceActions();
		this.bindQuickEditItem();
		this.bindAddByInput();
		this.refreshCartValues();
		this.bindChangeUnitPrice(); // @since 2.9.0
		this.bindUnitItemDiscount();
		this.bindHoverItemName(); // @since 3.0.19

		// @since 2.7.3
		// trigger action when cart is refreshed
		NexoAPI.events.doAction( 'cart_refreshed', v2Checkout );
	}

	/**
	* Calculate Cart discount
	**/

	this.calculateCartDiscount		=	function( value ) {

		if( value == '' || value == '0' ) {
			this.CartRemiseEnabled	=	false;
		}

		// Display Notice
		if( $( '.cart-discount-remove-wrapper' ).find( '.cart-discount-button' ).length > 0 ) {
			$( '.cart-discount-remove-wrapper' ).find( '.cart-discount-button' ).remove();
		}

		if( this.CartRemiseEnabled == true ) {

			if( this.CartRemiseType == 'percentage' ) {
				if( typeof value != 'undefined' ) {
					this.CartRemisePercent	=	NexoAPI.round( value );
				}

				// Only if the cart is not empty
				if( this.CartValue > 0 ) {
					this.CartRemise			=	( this.CartRemisePercent * this.CartValue ) / 100;
				} else {
					this.CartRemise			=	0;
				}

				if( this.CartRemiseEnabled ) {
					$( '.cart-discount-remove-wrapper' ).prepend( '<span style="cursor: pointer;margin:0px 2px;margin-top: -4px;" class="animated bounceIn btn btn-danger btn-xs cart-discount-button"><i class="fa fa-times"></i></span>' );
				}

			} else if( this.CartRemiseType == 'flat' ) {
				if( typeof value != 'undefined' ) {
					this.CartRemise 			=	NexoAPI.round( value );
				}

				if( this.CartRemiseEnabled ) {
					$( '.cart-discount-remove-wrapper' ).prepend( '<span style="cursor: pointer;margin:0px 2px;margin-top: -4px;" class="animated bounceIn btn btn-danger btn-xs cart-discount-button"><i class="fa fa-times"></i></span>' );
				}
			}

		}

		this.bindRemoveCartRemise();
	}

	/**
	* Calculate cart ristourne
	**/

	this.calculateCartRistourne		=	function(){
		// alert( 'ok' );

		// Will be overwritten by enabled ristourne
		this.CartRistourne			=	0;

		$( '.cart-discount-notice-area' ).find( '.cart-ristourne' ).remove();

		if( this.CartRistourneEnabled ) {

			if( this.CartRistourneType == 'percent' ) {

				if( this.CartRistournePercent != '' ) {
					this.CartRistourne	=	( NexoAPI.round( this.CartRistournePercent ) * this.CartValue ) / 100;
				}

				if( this.CartRistourne > 0 ) {
					$( '.cart-discount-notice-area' ).prepend( '<span style="cursor: pointer; margin:0px 2px;margin-top: -4px;" class="animated bounceIn btn expandable btn-info btn-xs cart-ristourne"><i class="fa fa-remove"></i> <?php echo addslashes(__('Ristourne : ', 'nexo'));?>' + this.CartRistournePercent + '%</span>' );
				}

			} else if( this.CartRistourneType == 'amount' ) {
				if( this.CartRistourneAmount != '' ) {
					this.CartRistourne	=	NexoAPI.round( this.CartRistourneAmount );
				}

				if( this.CartRistourne > 0 ) {
					$( '.cart-discount-notice-area' ).prepend( '<span style="cursor: pointer;margin:0px 2px;margin-top: -4px;" class="animated bounceIn btn expandable btn-info btn-xs cart-ristourne"><i class="fa fa-remove"></i> <?php echo addslashes(__('Ristourne : ', 'nexo'));?>' + NexoAPI.DisplayMoney( this.CartRistourne ) + '</span>' );
				}
			}

			this.bindRemoveCartRistourne();
		}
	}

	/**
	* Calculate Group Discount
	**/

	this.calculateCartGroupDiscount	=	function(){

		$( '.cart-discount-notice-area' ).find( '.cart-group-discount' ).remove();

		if( this.CartGroupDiscountEnabled == true ) {
			if( this.CartGroupDiscountType == 'percent' ) {
				if( this.CartGroupDiscountPercent != '' ) {
					this.CartGroupDiscount		=	( NexoAPI.round( this.CartGroupDiscountPercent ) * this.CartValue ) / 100;

					$( '.cart-discount-notice-area' ).append( '<p style="cursor: pointer; margin:0px 2px;margin-top: -4px;" class="animated bounceIn btn btn-warning expandable btn-xs cart-group-discount"><i class="fa fa-remove"></i> <?php echo addslashes(__('Remise de groupe : ', 'nexo'));?>' + this.CartGroupDiscountPercent + '%</p>' );
				}
			} else if( this.CartGroupDiscountType == 'amount' ) {
				if( this.CartGroupDiscountAmount != '' ) {
					this.CartGroupDiscount		=	NexoAPI.round( this.CartGroupDiscountAmount )	;

					$( '.cart-discount-notice-area' ).append( '<p style="cursor: pointer; margin:0px 2px;margin-top: -4px;" class="animated bounceIn btn btn-warning expandable btn-xs cart-group-discount"><i class="fa fa-remove"></i> <?php echo addslashes(__('Remise de groupe : ', 'nexo'));?>' + NexoAPI.DisplayMoney( this.CartGroupDiscountAmount ) + '</p>' );
				}
			}

			this.bindRemoveCartGroupDiscount();
		}
	};

	/**
	* Calculate Cart VAT
	**/

	this.calculateCartVAT		=	function(){
		if( this.CartVATType === 'fixed' ) {
			this.CartVAT		=	NexoAPI.round( ( this.CartVATPercent * this.CartValueRRR ) / 100 );
		} else if ( this.CartVATType === 'variable' ) {
			let index;
			if( [ 'xs', 'sm', 'md', 'lg' ].indexOf( layout.screenIs ) != -1 ) {
				index 	=	$( '.taxes_small select' ).val();
			} else {
				index 	=	$( '.taxes_large select' ).val();
			}

			if ( index != '' ) {
				let tax 	=	this.taxes[ index ];
				if ( tax ) {
					this.CartVAT		=	NexoAPI.round( ( NexoAPI.round( tax.RATE ) * this.CartValueRRR ) / 100 );
				}
			} else {
				this.CartVAT = 0;
			}
		}
	};

	/**
	* Cancel an order and return to order list
	**/

	this.cartCancel				=	function(){
		NexoAPI.Bootbox().confirm( '<?php echo _s('Souhaitez-vous annuler cette commande ?', 'nexo');?>', function( action ) {
			if( action == true ) {
				v2Checkout.resetCart();
			}
		});
	}

	/**
	* Cart Group Reset
	**/

	this.cartGroupDiscountReset			=	function(){
		this.CartGroupDiscount				=	0; // final amount
		this.CartGroupDiscountAmount		=	0; // Amount set on each group
		this.CartGroupDiscountPercent		=	0; // percent set on each group
		this.CartGroupDiscountType			=	null; // Discount type
		this.CartGroupDiscountEnabled		=	false;

		$( '.cart-discount-notice-area' ).find( '.cart-group-discount' ).remove();
	}

	/**
	* Submit order
	* @param object payment mean
	* @deprecated
	**/

	this.cartSubmitOrder			=	function( payment_means, {
		allow_printing = true,
		saving_order = true
	}){
		var order_items				=	new Array;

		_.each( this.CartItems, function( value, key ){

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
				discount_amount			:	value.DISCOUNT_AMOUNT,
				discount_percent 		:	value.DISCOUNT_PERCENT,
				category_id				:	value.REF_CATEGORIE,
				original 				: 	value,
				metas 					:	typeof value.metas == 'undefined' ? {} : value.metas,
				// @since 3.1
				name 					:	value.DESIGN,
				alternative_name 		:	value.ALTERNATIVE_NAME, // @since 3.11.8
				inline 					:	typeof value.INLINE != 'undefined' ? value.INLINE : 0, // if it's an inline item
				tax 					:	parseFloat( value.PRIX_DE_VENTE_TTC ) - parseFloat( value.PRIX_DE_VENTE_BRUT ),
				total_tax				:	( parseFloat( value.PRIX_DE_VENTE_TTC ) - parseFloat( value.PRIX_DE_VENTE_BRUT ) ) * parseFloat( value.QTE_ADDED ),
			};

			// improved @since 2.7.3
			// add meta by default
			ArrayToPush.metas	=	NexoAPI.events.applyFilters( 'items_metas', ArrayToPush.metas );

			order_items.push( ArrayToPush );
		});

		let order_details					=	new Object;
		order_details.TOTAL					=	NexoAPI.round( this.CartToPay );
		order_details.NET_TOTAL 			=	NexoAPI.round( this.CartValue );
		order_details.REMISE_TYPE			=	this.CartRemiseType;

		// @since 2.9.6
		if( this.CartRemiseType == 'percentage' ) {
			order_details.REMISE_PERCENT	=	NexoAPI.round( this.CartRemisePercent );
			order_details.REMISE			=	0;
		} else if( this.CartRemiseType == 'flat' ) {
			order_details.REMISE_PERCENT	=	0;
			order_details.REMISE			=	NexoAPI.round( this.CartRemise );
		} else {
			order_details.REMISE_PERCENT	=	0;
			order_details.REMISE			=	0;
		}
		// @endSince
		order_details.RABAIS			=	NexoAPI.round( this.CartRabais );
		order_details.RISTOURNE			=	NexoAPI.round( this.CartRistourne );
		order_details.TVA				=	NexoAPI.round( this.CartVAT );
		// @since 3.11.7
		order_details.REF_TAX 			=	this.REF_TAX;
		order_details.REF_CLIENT		=	this.CartCustomerID == null ? this.customers.DefaultCustomerID : this.CartCustomerID;
		order_details.PAYMENT_TYPE		=	this.CartPaymentType;
		order_details.GROUP_DISCOUNT	=	NexoAPI.round( this.CartGroupDiscount );
		order_details.DATE_CREATION		=	this.CartDateTime.format( 'YYYY-MM-DD HH:mm:ss' )
		order_details.ITEMS				=	order_items;
		order_details.DEFAULT_CUSTOMER	=	this.DefaultCustomerID;
		order_details.DISCOUNT_TYPE		=	'<?php echo @$Options[ store_prefix() . 'discount_type' ];?>';
		order_details.HMB_DISCOUNT		=	'<?php echo @$Options[ store_prefix() . 'how_many_before_discount' ];?>';
		// @since 2.7.5
		order_details.REGISTER_ID		=	this.CartRegisterID;

		// @since 2.7.1, send editable order to Rest Server
		order_details.EDITABLE_ORDERS	=	<?php echo json_encode( $this->events->apply_filters( 'order_editable', array( 'nexo_order_devis' ) ) );?>;

		// @since 2.7.3 add Order note
		order_details.DESCRIPTION		=	this.CartNote;

		// @since 2.9.0
		order_details.TITRE				=	this.CartTitle;

		// @since 2.8.2 add order meta
		this.CartMetas					=	NexoAPI.events.applyFilters( 'order_metas', this.CartMetas );
		order_details.metas				=	this.CartMetas;

		if( payment_means == 'cash' ) {

			order_details.SOMME_PERCU		=	NexoAPI.round( this.CartPerceivedSum );
			order_details.SOMME_PERCU 		=	isNaN( order_details.SOMME_PERCU ) ? 0 : order_details.SOMME_PERCU;

		} else if( payment_means == 'cheque' || payment_means == 'bank' ) {

			order_details.SOMME_PERCU		=	NexoAPI.round( this.CartToPay );

		} else if( payment_means == 'stripe' ) {
			if( this.CartAllowStripeSubmitOrder == true ) {

				order_details.SOMME_PERCU		=	NexoAPI.round( this.CartToPay );

			} else {
				NexoAPI.Notify().info( '<?php echo _s('Attention', 'nexo');?>', '<?php echo _s('La carte de crédit doit d\'abord être facturée avant de valider la commande.', 'nexo');?>' );
				return false;
			}
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

		var ProcessObj	=	NexoAPI.events.applyFilters( 'process_data', {
			url 	:	this.ProcessURL,
			type 	:	this.ProcessType
		});

		order_details.payments 		=	v2Checkout.CartPayments;

		// Filter Submited Details
		order_details	=	NexoAPI.events.applyFilters( 'before_submit_order', { order_details, allow_printing, saving_order } ).order_details;

		v2Checkout.paymentWindow.showSplash();
		
		const finalURL 	=	ProcessObj.url.replace( '{author_id}', this.CartAuthorID );
		const result 	=	HttpRequest[ ProcessObj.type.toLowerCase() ]( finalURL, order_details )
		result.then( returned => {
			returned 	=	returned.data;
			if ( allow_printing ) {
				<?php include( MODULESPATH . 'nexo/inc/angular/register/controllers/__paybox-print.php' );?>
			} else {
				v2Checkout.paymentWindow.hideSplash();
				v2Checkout.paymentWindow.close();
				v2Checkout.resetCart();
				NexoAPI.Toast()( `<?php echo __( 'La commande a été enregistrée', 'nexo' );?>` );
			}
		}).catch( error => {
			v2Checkout.paymentWindow.hideSplash();
			error 	=	NexoAPI.events.applyFilters( 'pos_error_response', error );

			if ( error !== false ) {
				NexoAPI.Notify().warning( '<?php echo _s('Une erreur s\'est produite', 'nexo');?>', error.response.message || `<?php echo _s('Le paiement n\'a pas pu être effectuée.', 'nexo');?>` );
			}
		});

		return result;
	};

	/**
	 *  Check Item Stock
	 *  @return void
	**/

	this.checkItemsStock			=	function( items ) {
		var stockToReport			=	new Array;
		_.each( items, function( value, key ) {
			var alertQuantity 	=	parseFloat( value.ALERT_QUANTITY );
			var currentQuantity	=	parseFloat( value.QUANTITE_RESTANTE );
			if( alertQuantity >= currentQuantity && value.STOCK_ALERT == 'enabled' ) {
				stockToReport.push({
					'id'		:	value.ID,
					'design'	:	value.DESIGN
				});
			}
		});

		if( stockToReport.length > 0 ) {
			$.ajax({
				url		:	'<?php echo site_url( array( 'rest', 'nexo', 'stock_report' ) );?>?store_id=<?php echo $store_id == null ? 0 : $store_id;?>',
				method	:	'POST',
				data	:	{
					'reported_items'	:	stockToReport
				}
			});
		}
	}

	/**
	* Customer DropDown Menu
	* @deprecated
	**/

	this.customers			=	new function(){

		this.DefaultCustomerID	=	'<?php echo @$Options[ store_prefix() . 'default_compte_client' ];?>';

		/**
		* Bind
		* @deprecated
		**/

		this.bind				=	function(){
			// $('.dropdown-bootstrap').selectpicker({
			// 	style: 'btn-default',
			// 	size: 4
			// });


			// if( typeof $( '.customers-list' ).attr( 'change-bound' ) == 'undefined' ) {
			// 	$( '.customers-list' ).bind( 'change', function(){
			// 		v2Checkout.customers.bindSelectCustomer( $( this ).val() );
			// 	});
			// 	$( '.customers-list' ).attr( 'change-bound', 'true' );
			// }
		}

		/**
		* Bind select customer
		* Check if a specific customer due to his purchages or group
		* should have a discount
		**/

		this.bindSelectCustomer	=	function( customer_id ){
			// Reset Ristourne if enabled
			v2Checkout.CartRistourneEnabled				=	false;

			// DISCOUNT_ACTIVE
			$.ajax( '<?php echo site_url(array( 'rest', 'nexo', 'customer' ));?>/' + customer_id + '?<?php echo store_get_param( null );?>', {
				error		:	function(){
					v2Checkout.showError( 'ajax_fetch' );
				},
				dataType	:	'json',
				success		:	function( data ) {
					if( data.length > 0 ){
						if( customer_id != this.DefaultCustomerID ) {
							v2Checkout.customers.check_discounts( data );
							v2Checkout.customers.check_groups_discounts( data );
						}
						// Exect action on selecting customer
						v2Checkout.CartCustomerID	=	data[0].ID;
						NexoAPI.events.doAction( 'select_customer', data );
						v2Checkout.refreshCartValues();
					}
				}
			});
		};

		/**
		* Check discount for the customer
		* @param object customer data
		* @return void
		**/

		this.check_discounts			=	function( object ) {
			if( typeof object == 'object' ) {
				_.each( object, function( value, key ) {
					// Restore orginal customer discount
					if( NexoAPI.round( v2Checkout.CartRistourneCustomerID ) == NexoAPI.round( value.ID ) ) {
						v2Checkout.restoreCustomRistourne();
						v2Checkout.buildCartItemTable();
						v2Checkout.refreshCart();
					} else {
						if( value.DISCOUNT_ACTIVE == '1' ) {
							v2Checkout.restoreDefaultRistourne();
							v2Checkout.CartRistourneEnabled 	=	true;
						}
					}
				});

				// Refresh Cart value;
				v2Checkout.refreshCartValues();
			}
		};

		/**
		* Check discount for user group
		* @param object customer data
		* @return void
		**/

		this.check_groups_discounts		=	function( object ){

			// Reset Groups Discounts
			v2Checkout.cartGroupDiscountReset();

			if( typeof object == 'object' ) {

				_.each( object, function( Customer, key ) {
					// Default customer can't benefit from group discount
					if( Customer.ID != v2Checkout.customers.DefaultCustomerID ) {
						// Looping each groups to check whether this customer belong to one existing group
						_.each( v2Checkout.CustomersGroups, function( Group, Key ) {
							if( Customer.REF_GROUP == Group.ID ) {
								// if group discount is enabled
								if( Group.DISCOUNT_ENABLE_SCHEDULE == 'true' ) {
									if(
										moment( Group.DISCOUNT_START ).isSameOrBefore( v2Checkout.CartDateTime ) == false ||
										moment( Group.DISCOUNT_END ).endOf( 'day' ).isSameOrAfter( v2Checkout.CartDateTime ) == false
									) {
										/**
										* Time Range is incorrect to enable Group discount
										**/

										console.log( 'time is incorrect for group discount' );

										return;
									}
								}

								// If current customer belong to this group, let see if this group has active discount
								if( Group.DISCOUNT_TYPE == 'percent' ) {
									v2Checkout.CartGroupDiscountType	=	Group.DISCOUNT_TYPE;
									v2Checkout.CartGroupDiscountPercent	=	Group.DISCOUNT_PERCENT;
									v2Checkout.CartGroupDiscountEnabled	=	true;
								} else if( Group.DISCOUNT_TYPE == 'amount' ) {
									v2Checkout.CartGroupDiscountType	=	Group.DISCOUNT_TYPE;
									v2Checkout.CartGroupDiscountAmount	=	Group.DISCOUNT_AMOUNT;
									v2Checkout.CartGroupDiscountEnabled	=	true;
								}
							}
						});
					}
				});

				// Refresh Cart value;
				v2Checkout.refreshCartValues();
			}
		};

		/**
		* Get Customers Groups
		**/

		this.getGroups					=	function(){
			$.ajax( '<?php echo site_url(array( 'rest', 'nexo', 'customers_groups' ));?>?store_id=<?php echo $store_id == null ? 0 : $store_id;?>', {
				dataType		:	'json',
				success			:	function( customers ){

					v2Checkout.CustomersGroups	=	customers;

				},
				error			:	function(){
					NexoAPI.Bootbox().alert( '<?php echo addslashes(__('Une erreur s\'est produite durant la récupération des groupes des clients', 'nexo'));?>' );
				}
			});
		}

		/**
		* Start
		**/

		this.run						=	function(){
			this.getGroups();
			// this.bind();
		};
	}

	/**
	* Display Items on the grid
	* @param Array
	* @return void
	**/

	this.displayItems			=	function( json ) {
		if( json.length > 0 ) {
			// Empty List
			$( '#filter-list' ).html( '' );

			_.each( json, ( value, key ) => {

				/**
				* We test item quantity of skip that test if item is not countable.
				* value.TYPE = 0 means item is physical, = 1 means item is numerical
				* value.STATUS = 0 means item is on sale, = 1 means item is disabled
				* the index "3" represent the grouped items
				**/

				if( ( 
					( parseInt( value.QUANTITE_RESTANTE ) > 0 && value.TYPE == '1' && value.STOCK_ENABLED === '1' ) || 
					( value.TYPE == '1' && value.STOCK_ENABLED === '2' ) || 
					( [ '2', '3' ].indexOf( value.TYPE ) != -1 ) ) 
					&& value.STATUS == '1' 
				) {

					var promo_start	= moment( value.SPECIAL_PRICE_START_DATE );
					var promo_end	= moment( value.SPECIAL_PRICE_END_DATE );
					var MainPrice	= NexoAPI.round( v2Checkout.CartShowNetPrice ? value.PRIX_DE_VENTE_BRUT : value.PRIX_DE_VENTE_TTC )
					var Discounted	= '';
					var CustomBackground	=	'';
					var ImagePath			=	value.APERCU == '' ? '<?php echo '../../../modules/nexo/images/default.png';?>'  : value.APERCU;

					if( promo_start.isBefore( v2Checkout.CartDateTime ) ) {
						if( promo_end.isSameOrAfter( v2Checkout.CartDateTime ) ) {
							MainPrice			=	NexoAPI.round( value.PRIX_PROMOTIONEL );
							Discounted			=	'<small style="color: #999;border: solid 1px #dadada; border-radius: 5px;padding: 2px;position: absolute;box-shadow: 0px 0px 5px 1px #988f8f;top: 10px;left: 10px;z-index: 800;    background: #EEE;"><del>' + NexoAPI.DisplayMoney( NexoAPI.round( v2Checkout.CartShowNetPrice ? value.PRIX_DE_VENTE_BRUT : value.PRIX_DE_VENTE_TTC ) ) + '</del></small>';
							// CustomBackground	=	'background:<?php echo $this->config->item('discounted_item_background');?>';
						}
					}

					/**
					 * Let's check if the item has expired
					 */
					let itemClass 	=	'';
					if ( moment( value.EXPIRATION_DATE ).isBefore( tendoo.date ) ) {
						itemClass 	=	'expired-item';
					}

					// @since 2.7.1
					if( v2Checkout.CartShadowPriceEnabled ) {
						MainPrice			=	NexoAPI.round( value.SHADOW_PRICE );
					}

					// style="max-height:100px;"
					// alert( value.DESIGN.length );
					var design	=	value.DESIGN.length > 15 ? '<span class="marquee_me">' + value.DESIGN + '</span>' : value.DESIGN;

					// Reshresh JSon data
					value.MAINPRICE 			=	MainPrice;
					v2Checkout.ItemsCategories	=	_.extend( v2Checkout.ItemsCategories, _.object( [ value.REF_CATEGORIE ], [ value.NOM ] ) );
					const catName 				=	v2Checkout.ItemsCategories[ value.REF_CATEGORIE ].toLowerCase();
					$( '#filter-list' ).append(
						NexoAPI.events.applyFilters( 'pos_item_template', {
							template : `
							<div class="col-lg-2 col-md-3 col-xs-4 ${itemClass} shop-items filter-add-product noselect text-center" data-order="${value.ORDER}" data-codebar="${value.CODEBAR}" style="${CustomBackground};padding:5px; border-right: solid 1px #DEDEDE;border-bottom: solid 1px #DEDEDE;" data-design="${value.DESIGN.toLowerCase()}" data-cat-id="${value.REF_CATEGORIE}" data-category-name="' + catName  + '" data-sku="${value.SKU.toLowerCase()}">
								<img data-original="<?php echo get_store_upload_url() . 'items-images/';?>${ImagePath}" width="100" style="display: block;width: 100%;min-height: 141px;max-height:141px;" class="img-responsive img-rounded lazy">
								<div class="caption text-center" style="padding: 2px;overflow: hidden;position: absolute;bottom: 15px;z-index: 99999;width: 95%;background: #ffffffc9;"><strong class="item-grid-title">${design}</strong><br>
									<span class="align-center">${NexoAPI.DisplayMoney( MainPrice )}</span>${Discounted + ( this.showRemainingQuantity ? ` (${value.QUANTITE_RESTANTE})` : '' )}
								</div>
							</div>
							`, 
							...{ value, MainPrice, itemClass, v2Checkout, ImagePath, Discounted, design, CustomBackground }
						}).template
					);

					this.itemsStock[ value.CODEBAR ] 		=	NexoAPI.round( value.QUANTITE_RESTANTE ) - parseFloat( value.HOLD_QUANTITY === null ? 0 : value.HOLD_QUANTITY );
				}
			});

			this.POSItems 		=	json;

			// Bind Categorie @since 2.7.1
			v2Checkout.bindCategoryActions();

			// Add Lazy @since 2.6.1
			$("img.lazy").lazyload({
				failure_limit : 10,
				effect : "fadeIn",
				load : function( e ){
					$( this ).removeAttr( 'width' );
				},
				container : $( '#filter-list' )
			});

			// Bind Add to Items
			this.bindAddToItems();
			// @since 2.9.9
			this.checkItemsStock( json );
		} else {
			NexoAPI.Bootbox().alert( '<?php echo addslashes(__('Vous ne pouvez pas procéder à une vente, car aucun article n\'est disponible pour la vente.', 'nexo' ));?>' );
		}

		$( '.filter-add-product' ).each( function(){
			$(this).bind( 'mouseenter', function(){
				$( this ).find( '.marquee_me' ).replaceWith( '<marquee class="marquee_me" behavior="alternate" scrollamount="4" direction="left" style="width:100%;float:left;">' + $( this ).find( '.marquee_me' ).html() + '</marquee>' );
			})
		});

		$( '.filter-add-product' ).each( function(){
			$(this).bind( 'mouseleave', function(){
				$( this ).find( '.marquee_me' ).replaceWith( '<span class="marquee_me">' + $( this ).find( '.marquee_me' ).html() + '</span>' );
			})
		});
	};

	/**
	* Empty cart item table
	*
	**/

	this.emptyCartItemTable		=	function() {
		$( '#cart-table-body' ).find( '[cart-item]' ).remove();
	};

	/**
		* Treat Found item
		* @param object item
		* @since 3.1
		* @return void
	**/

	this.treatFoundItem 		=	function({ item, barcode, quantity, increase, index = null, callback = null }){
		
		/**
		* Filter item when is loaded
		**/
		item 			=	NexoAPI.events.applyFilters( 'item_loaded', item );

		/**
		* Override Add Item default Feature
		**/

		if( NexoAPI.events.applyFilters( 'override_add_item' , { 
			item,
			proceed 			: false, 
			quantity,
			increase,
			index
		}).proceed == true ) {
			return;
		}

		if( typeof callback == 'function' ) {
			callback( item );
		} else {
			this.addToCart({ item : item })
		}

		// this.addToCart({ item, barcode, quantity, increase });
	}

	/**
	* Fix Product Height
	**/

	this.fixHeight				=	function(){
		this.paymentWindow.hideSplash();
	};

	/**
	* Filter Item
	*
	* @param string
	* @return void
	**/

	this.filterItems			=	function( content ) {
		content					=	_.toArray( content );
		if( content.length > 0 ) {
			$( '#product-list-wrapper' ).find( '.shop-items[data-cat-id]' ).hide();
			_.each( content, function( value, key ){
				$( '#product-list-wrapper' ).find( '.shop-items[data-cat-id="' + value + '"]' ).show();
				// const products 	=	$( `[data-cat-id="${value}"]` );
				// products.sort( function( a, b ) {
				// 	console.log( $( a ) );
				// 	if( $( a ).data( 'order' ) === 'null' ) {
				// 		return 1;
				// 	}
				// 	const param1 	=	parseInt( $(a).data( 'order' )  );
				// 	const param2 	=	parseInt( $(b).data( 'order' ) );
				// 	if ( param1 > param2 ) {
				// 		return 1;
				// 	}
				// 	if ( param1 < param2 ) {
				// 		return -1
				// 	}
				// 	if ( param1 === param2 ) {
				// 		return 0;
				// 	}
				// });
			});
		} else {
			$( '#product-list-wrapper' ).find( '.shop-items[data-cat-id]' ).show();
		}
		
		$("img.lazy").lazyload({
			failure_limit : 10,
			effect : "show",
			load : function( e ){
				$( this ).removeAttr( 'width' );
			},
			container : $( '#filter-list' )
		});
	}

	/**
	* Get Items
	**/

	this.getItems				=	function( beforeCallback, afterCallback){
		$.ajax('<?php echo $this->events->apply_filters( 'nexo_checkout_item_url', site_url([ 'rest', 'nexo', 'item' ]) ) . '?store_id=' . $store_id;?>', { // _with_meta
			beforeSend	:	function(){
				if( typeof beforeCallback == 'function' ) {
					beforeCallback();
				}
			},
			error	:	function(){
				NexoAPI.Bootbox().alert( '<?php echo addslashes(__('Une erreur s\'est produite durant la récupération des produits', 'nexo'));?>' );
			},
			success: function( content ){
				$( this.ItemsListSplash ).hide();
				$( this.ProductListWrapper ).find( '.box-body' ).css({'visibility' :'visible' });

				v2Checkout.displayItems( content );

				if( typeof afterCallback == 'function' ) {
					afterCallback();
				}
			},
			dataType:"json"
		});
	};

	/**
	* Get Item
	* get item from cart
	**/

	this.getItem				=	function( barcode ) {
		for( var i = 0; i < this.CartItems.length ; i++ ) {
			if( this.CartItems[i].CODEBAR == barcode ) {
				this.CartItems[i].LOOP_INDEX 	=	i;
				return this.CartItems[i];
			}
		}
		return false;
	}

	/**
	* Get Item Sale Price
	* @param object item
	* @return float main item price
	**/

	this.getItemSalePrice			=	function( itemObj ) {
		var promo_start				= 	moment( itemObj.SPECIAL_PRICE_START_DATE );
		var promo_end				= 	moment( itemObj.SPECIAL_PRICE_END_DATE );

		var MainPrice				= 	NexoAPI.round( v2Checkout.CartShowNetPrice ? itemObj.PRIX_DE_VENTE_BRUT : itemObj.PRIX_DE_VENTE_TTC )
		var Discounted				= 	'';
		var CustomBackground		=	'';
		itemObj.PROMO_ENABLED	=	false;

		if( promo_start.isBefore( v2Checkout.CartDateTime ) ) {
			if( promo_end.isSameOrAfter( v2Checkout.CartDateTime ) ) {
				itemObj.PROMO_ENABLED	=	true;
				MainPrice				=	NexoAPI.round( itemObj.PRIX_PROMOTIONEL );
			}
		}

		// @since 2.7.1
		if( v2Checkout.CartShadowPriceEnabled ) {
			MainPrice			=	NexoAPI.round( itemObj.SHADOW_PRICE );
		}
		return MainPrice;
	}

	/**
	* Init Cart Date
	*
	**/

	this.initCartDateTime		=	function(){
		this.CartDateTime			=	moment( '<?php echo date_now();?>' );
		$( '.content-header h1' ).append( '<small class="pull-right" id="cart-date" style="display:none;line-height: 30px;"></small>' );

		setInterval( function(){
			v2Checkout.CartDateTime.add( 1, 's' );
			// YYYY-MM-DD
			$( '#cart-date' ).html( v2Checkout.CartDateTime.format( 'HH:mm:ss' ) );
		},1000 );

		setTimeout( function(){
			$( '#cart-date' ).show( 500 );
		}, 1000 );
	};

	/**
	* Is Cart empty
	* @return boolean
	**/

	this.isCartEmpty			=	function(){
		if( _.toArray( this.CartItems ).length > 0 ) {
			return false;
		}
		return true;
	}

	/**
	* Display item Settings
	* this option let you select categories to displays
	**/

	this.itemsSettings					=	function(){
		this.buildItemsCategories( '.categories_dom_wrapper' );
	};

	/**
	* Show Numpad
	**/

	this.showNumPad				=	function( object, text, object_wrapper, real_time ){
		// Field
		var field				=	real_time == true ? object : '[name="numpad_field"]';

		// If real time editing is enabled
		var input_field			=	! real_time ?
		'<div class="form-group">' +
		'<input type="text" class="form-control input-lg" name="numpad_field"/>' +
		'</div>' : '';

		var NumPad				=
		'<div id="numpad">' +
		'<h4 class="text-center">' + ( text ? text : '' ) + '</h4><br>' +
		input_field	+
		'<div class="row">' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad numpad7" value="<?php echo addslashes(__('7', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad numpad8" value="<?php echo addslashes(__('8', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad numpad9" value="<?php echo addslashes(__('9', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad numpadplus" value="<?php echo addslashes(__('+', 'nexo'));?>"/>' +
		'</div>' +
		'</div>' +
		'<br>'+
		'<div class="row">' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad numpad4" value="<?php echo addslashes(__('4', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad numpad5" value="<?php echo addslashes(__('5', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad numpad6" value="<?php echo addslashes(__('6', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad numpadminus" value="<?php echo addslashes(__('-', 'nexo'));?>"/>' +
		'</div>' +
		'</div>' +
		'<br>'+
		'<div class="row">' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad numpad1" value="<?php echo addslashes(__('1', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad numpad2" value="<?php echo addslashes(__('2', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad numpad3" value="<?php echo addslashes(__('3', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<input type="button" class="btn btn-warning btn-block btn-lg numpad numpaddel" value="&larr;"/>' +
		'</div>' +
		'</div>' +
		'<br/>' +
		'<div class="row">' +
		'<div class="col-lg-6 col-md-6 col-xs-6">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad numpad0" value="<?php echo addslashes(__('0', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<input type="button" class="btn btn-default btn-block btn-lg numpad numpaddot" value="<?php echo addslashes(__('.', 'nexo'));?>"/>' +
		'</div>' +
		'<div class="col-lg-3 col-md-3 col-xs-3">' +
		'<button type="button" class="btn btn-danger btn-block btn-lg numpad numpadclear"><i class="fa fa-eraser"></i></button></div>' +
		'</div>' +
		'</div>'
		'</div>';

		if( $( object_wrapper ).length > 0 ) {
			$( object_wrapper ).html( NumPad );
		} else {
			NexoAPI.Bootbox().confirm( NumPad, function( action ) {
				if( action == true ) {
					$( object ).val( $( field ).val() );
					$( object ).trigger( 'change' );
				}
			});

			$( '#numpad' ).closest( '.bootbox' ).css({
				'display': 'flex',
    			'align-items': 'center',
			});
		}

		if( $( field ).val() == '' ) {
			$( field ).val(0);
		}

		var selectedValue 	=	false;

		$( field ).select( function() {
			selectedValue 	=	true;
		});

		$( field ).blur( function() {
			setTimeout(() => {
				selectedValue 	=	false;
			}, 1000 )
			if( $( this ).val() == '' ) {
				$( this ).val(0);
			}
		});

		$( field ).click(function () {
			$(this).select();
		});

		$( field ).trigger( 'click' );

		$( field ).val( $( object ).val() );

		for( var i = 0; i <= 9; i++ ) {
			$( '#numpad' ).find( '.numpad' + i ).bind( 'click', function(){
				var current_value	=	$( field ).val();
				current_value	=	current_value == '0' ? '' : current_value;
				$( field ).val( selectedValue ? $( this ).val() : current_value + $( this ).val() );
				selectedValue 	=	false;
			});
		}

		$( '.numpadclear' ).bind( 'click', function(){
			$( field ).val(0);
		});

		$( '.numpadplus' ).bind( 'click', function(){
			var numpad_value	=	NexoAPI.round( $( field ).val() );
			$( field ).val( ++numpad_value );
		});

		$( '.numpadminus' ).bind( 'click', function(){
			var numpad_value	=	NexoAPI.round( $( field ).val() );
			$( field ).val( --numpad_value );
		});

		$( '.numpaddot' ).bind( 'click', function(){
			var current_value	=	$( field ).val();
			current_value	=	current_value == '' ? 0 : NexoAPI.round( current_value );
			//var numpad_value	=	NexoAPI.round( $( field ).val() );
			$( field ).val( selectedValue ? '.' : current_value + '.' );
		});

		$( '.numpaddel' ).bind( 'click', function(){
			var numpad_value	=	$( field ).val();
			numpad_value	=	numpad_value.substr( 0, numpad_value.length - 1 );
			numpad_value 	= 	numpad_value == '' ? 0 : numpad_value;
			$( field ).val( numpad_value );
		});
	};

	/**
	* Display specific error
	**/

	this.showError				=	function( error_type ) {
		if( error_type == 'ajax_fetch' ) {
			NexoAPI.Bootbox().alert( '<?php echo addslashes(__('Une erreur s\'est produite durant la récupération des données', 'nexo'));?>' );
		}
	}

	/**
	* Search Item
	**/

	this.searchItems					=	function( value ){
		console.log( 'FIX THIS' );
	};

	/**
	* Quick Search Items
	* @param
	**/

	this.quickItemSearch			=	function( value ) {
		if( value.length <= 3 ) {
			$( '.filter-add-product' ).each( function(){
				$( this ).show();
				$( this ).addClass( 'item-visible' );
				$( this ).removeClass( 'item-hidden' );
				$( this ).find( '.floatting-shortcut' ).remove();
			});
		} else {
			let i 	=	1;
			$( '.filter-add-product' ).each( function(){
				// Filter Item
				if(
					$( this ).attr( 'data-design' ).search( value.toLowerCase() ) == -1 &&
					$( this ).attr( 'data-category-name' ).search( value.toLowerCase() ) == -1 &&
					$( this ).attr( 'data-codebar' ).search( value.toLowerCase() ) == -1 && // Scan, also item Barcode
					$( this ).attr( 'data-sku' ).search( value.toLowerCase() ) == -1  // Scan, also item SKU
				) {
					$( this ).hide();
					$( this ).addClass( 'item-hidden' );
					$( this ).removeClass( 'item-visible' );
				} else {
					$( this ).show();
					$( this ).addClass( 'item-visible' );
					$( this ).removeClass( 'item-hidden' );
					$( this ).find( '.floatting-shortcut' ).remove();
					$( this ).append( '<span class="floatting-shortcut">' + i + '</span>' );
					i++;
				}					
			});
		}
	}

	/**
	* Payment
	**/

	this.paymentWindow					=	new function(){
		/// Display Splash
		this.showSplash			=	function(){
			if( $( '.nexo-overlay' ).length == 0 ) {
				$( 'body' ).append( '<div class="nexo-overlay"></div>');
				$( '.nexo-overlay').css({
					'width' : '100%',
					'height' : '100%',
					'background': 'rgba(0, 0, 0, 0.5)',
					'z-index'	: 5000,
					'position' : 'absolute',
					'top'	:	0,
					'left' : 0,
					'display' : 'none'
				}).fadeIn( 500 );

				$( '.nexo-overlay' ).append( '<i class="fa fa-refresh fa-spin nexo-refresh-icon" style="color:#FFF;font-size:50px;"></i>');

				$( '.nexo-refresh-icon' ).css({
					'position' : 'absolute',
					'top'	:	'50%',
					'left' : '50%',
					'margin-top' : '-25px',
					'margin-left' : '-25px',
					'width' : '44px',
					'height' : '50px'
				})
			}
		}

		// Hide splash
		this.hideSplash			=	function(){
			$( '.nexo-overlay' ).fadeOut( 400, function(){
				$( this ).remove();
			} );
		}

		this.close				=	function(){
			$( '.paxbox-box [data-bb-handler="cancel"]' ).trigger( 'click' );
		};
	};

	/**
	* Refresh Cart
	*
	**/

	this.refreshCart			=	function(){
		if( this.isCartEmpty() ) {
			$( '#cart-table-notice' ).show();
		} else {
			$( '#cart-table-notice' ).hide();
		}
	};

	/**
	* Refresh Cart Values
	*
	**/

	this.refreshCartValues		=	function(){

		this.calculateCartDiscount();
		this.calculateCartRistourne();
		this.calculateCartGroupDiscount();

		this.CartDiscount		=	NexoAPI.round( this.CartRemise + this.CartRabais + this.CartRistourne + this.CartGroupDiscount );
		this.CartValueRRR		=	NexoAPI.round( this.CartValue - this.CartDiscount );
		
		this.calculateCartVAT();
		/*
			*@V15.01 pos screen
			*subtract the refund from net payable amount
		*/
		this.CartToPay			=	( this.CartValueRRR + this.CartVAT + this.CartItemsVAT + this.CartShipping );
		this.net_pay = ( this.CartValueRRR + this.CartVAT + this.CartItemsVAT + this.CartShipping-this.refund_money );

		<?php if( in_array(strtolower(@$Options[ store_prefix() . 'nexo_currency_iso' ]), $this->config->item('nexo_supported_currency')) ) {
			?>
			this.CartToPayLong		=	numeral( this.CartToPay ).multiply(100).value();
			<?php
		} else {
			?>
			this.CartToPayLong		=	NexoAPI.Format( this.CartToPay, '0.00' );
			<?php
		};?>
		
		//@since 3.0.19
		let itemsNumber 	=	0;
		_.each( this.CartItems, ( item ) => {
			itemsNumber 	+=	parseInt( item.QTE_ADDED );
		});			
		$( '.items-number' ).html( itemsNumber );

		this.refreshCartVisualValues();

		NexoAPI.events.applyFilters( 'refresh_cart_values', this.CartItems );
	};

	this.refreshCartVisualValues 		=	function() {
		$( '.cart-value' ).html( NexoAPI.DisplayMoney( this.CartValue ) );
		$( '.cart-vat' ).html( NexoAPI.DisplayMoney( this.CartVAT ) );
		$( '.cart-discount' ).html( NexoAPI.DisplayMoney( this.CartDiscount ) );
		//$( '.cart-topay' ).html( NexoAPI.DisplayMoney( this.CartToPay ) );
		$( '.cart-topay' ).html( NexoAPI.DisplayMoney( this.net_pay ) );
		$( '.cart-item-vat' ).html( NexoAPI.DisplayMoney( this.CartItemsVAT ) );
	}

	/**
	* use saved discount (automatic discount)
	**/

	this.restoreCustomRistourne			=	function(){
		<?php if (isset($order)):?>
		<?php if (floatval( ( int ) @$order[ 'order' ][0][ 'RISTOURNE' ]) > 0):?>
		this.CartRistourneEnabled		=	true;
		this.CartRistourneType			=	'amount';
		this.CartRistourneAmount		=	NexoAPI.round( <?php echo floatval($order[ 'order' ][0][ 'RISTOURNE' ]);?> );
		this.CartRistourneCustomerID	=	'<?php echo $order[ 'order' ][0][ 'REF_CLIENT' ];?>';
		<?php endif;?>
		<?php endif;?>
	}

	/**
	* Restore default discount (automatic discount)
	**/

	this.restoreDefaultRistourne		=	function(){
		this.CartRistourneType			=	'<?php echo @$Options[ store_prefix() . 'discount_type' ];?>';
		this.CartRistourneAmount		=	'<?php echo @$Options[ store_prefix() . 'discount_amount' ];?>';
		this.CartRistournePercent		=	'<?php echo @$Options[ store_prefix() . 'discount_percent' ];?>';
		this.CartRistourneEnabled		=	false;
		this.CartRistourne				=	0;
	};

	/**
	* Reset Object
	**/

	this.resetCartObject			=	function(){
		this.ItemsCategories		=	new Object;
		this.CartItems				=	new Array;
		this.CustomersGroups		=	new Array;
		this.ActiveCategories		=	new Array;
		this.itemsStock 			=	new Object;
		this.CartPayments 			=	new Array;
		this.CartDeliveryInfo 		=	new Object;
		// Restore Cart item table
		this.buildCartItemTable();
		// Load Customer and groups
		this.customers.run();
		// Build Items
		this.getItems(null, function(){
			v2Checkout.hideSplash( 'right' );
		});
	};

	/**
	* Reset Cart
	**/

	this.resetCart					=	function(){

		this.CartValue				=	0;
		this.CartValueRRR			=	0;
		this.CartVAT				=	0;
		this.CartDiscount			=	0;
		this.CartToPay				=	0;
		this.CartToPayLong			=	0;
		this.CartRabais			=	0;
		this.CartTotalItems			=	0;
		this.CartRemise			=	0;
		this.CartPerceivedSum		=	0;
		this.CartCreance			=	0;
		this.CartToPayBack			=	0;
		// @since 2.9.6
		this.CartRabaisPercent		=	0;
		this.CartRistournePercent	=	0;
		this.CartRemisePercent		=	0;
		this.POSItems				=	[];
		// @since 3.1.3
		this.CartShipping   		=	0;
		this.CartItemsVAT 			=	0;
		this.CartType 				=	null;
		this.From 					=	null;
		this.CartAuthorID 			=	<?php echo User::id();?>;

		// @since 3.11.7
		this.REF_TAX 				=	0;

		this.ProcessURL					=	"<?php echo site_url(array( 'rest', 'nexo', 'order', '{author_id}' ));?>?store_id=<?php echo get_store_id();?>";
		this.ProcessType				=	'POST';
		
		this.CartRemiseType				=	'';
		this.CartRemiseEnabled			=	false;
		this.CartRemisePercent			=	0;
		this.CartPaymentType			=	null;
		this.CartShadowPriceEnabled		=	<?php echo @$Options[ store_prefix() . 'nexo_enable_shadow_price' ] == 'yes' ? 'true' : 'false';?>;
		this.CartCustomerID				=	<?php echo @$Options[ store_prefix() . 'default_compte_client' ] != null ? $Options[ store_prefix() . 'default_compte_client' ] : 'null';?>;
		this.CartAllowStripeSubmitOrder	=	false;

		this.cartGroupDiscountReset();
		this.resetCartObject();
		this.restoreDefaultRistourne();
		this.refreshCartValues();

		//add removeRefund function to remove and reset the refund
		this.removeRefund();

		// @since 2.7.3
		this.CartNote				=	'';

		// @since 2.9.0
		this.CartTitle				=	'';

		// @since 2.8.2
		this.CartMetas				=	{};

		// Reset Cart
		NexoAPI.events.doAction( 'reset_cart', this );
	}

	/**
	 * Setup Taxes
	 * @return void
	 */
	this.setupTaxes 			=	function(){
		NexoAPI.events.addAction( 'pos_loaded', () => {
			this.taxes.forEach( ( tax, index ) => {
				$( '.taxes_select' ).append( '<option value="' + index + '">' + tax.NAME + '</option>' );
			});
			
			$( '.taxes_select' ).each( function() {
				$( this ).change( function() {
					let index 	=	$( this ).val();

					let tax 				=	v2Checkout.taxes[ index ];
					
					if ( tax ) {
						v2Checkout.REF_TAX 		=	tax.ID;
					}

					v2Checkout.refreshCartValues();
				});
			})

			this.resetTaxes();
		});

		NexoAPI.events.addAction( 'reset_cart', () => {
			this.resetTaxes();
		});
	}

	this.resetTaxes 				=	function() {
		/**
		* Trigger click to enforce tax selection
		*/
		$( '.taxes_select' ).val( $( '.taxes_select' ).val() );
		$( '.taxes_select' ).trigger( 'change' );
	}

	/**
	* Run Checkout
	**/
	this.run							=	function(){

		this.resetCart();
		this.initCartDateTime();
		this.bindHideItemOptions();
		// @since 2.7.3
		this.bindAddNote();
		this.setupTaxes();

		this.CartStartAnimation			=	'<?php echo $this->config->item('nexo_cart_animation');?>';

		$( this.ProductListWrapper ).removeClass( this.CartStartAnimation ).css( 'visibility', 'visible').addClass( this.CartStartAnimation );
		$( this.CartTableWrapper ).removeClass( this.CartStartAnimation ).css( 'visibility', 'visible').addClass( this.CartStartAnimation );

		/*this.getItems(null, function(){ // ALREADY Loaded while resetting cart
			v2Checkout.hideSplash( 'right' );
		});*/

		$( this.CartCancelButton ).bind( 'click', function(){
			v2Checkout.cartCancel();
		});

		/**
		* Search Item Feature
		**/
		$( this.ItemSearchForm ).bind( 'submit', function(){
			v2Checkout.retreiveItem( $( '[name="item_sku_barcode"]' ).val() );
			$( '[name="item_sku_barcode"]' ).val('');
			return false;
		});

		$( '.enable_barcode_search' ).bind( 'click', function(){
			if( $( this ).hasClass( 'active' ) ) {
				$( this ).removeClass( 'active' );
				v2Checkout.enableBarcodeSearch 	=	false;
			} else {
				$( this ).addClass( 'active' );
				v2Checkout.enableBarcodeSearch 	=	true;
				$( '[name="item_sku_barcode"]' ).focus();
			}
		});

		// check if the button is clicked
		<?php if( store_option( 'enable_quick_search', 'no' ) === 'yes' ):?>
		$( '.enable_barcode_search' ).trigger( 'click' );
		<?php endif;?>

		/**
		* Filter Item
		**/
		let addItemTimeout;

		$( this.ItemSearchForm ).bind( 'keyup', function(){
			if( v2Checkout.enableBarcodeSearch == false ) {
				v2Checkout.quickItemSearch( $( '[name="item_sku_barcode"]' ).val() );
			}

			// Add found item on the cart
			// @since 3.0.19
			if( typeof this.addItemTimeout == 'undefined' ) {
				this.addItemTimeout 	=	5;
			}

			window.clearTimeout( addItemTimeout );

			addItemTimeout 	=	window.setTimeout( () => {
				if( $( '.filter-add-product.item-visible' ).length == 1 ) {
					// when an item is found, just blur the field to avoid multiple quantity adding
					$( '.filter-add-product.item-visible' ).click();
					$( '[name="item_sku_barcode"]' ).val('');
					v2Checkout.quickItemSearch( '' );
				}
			}, 500 );
		});

		/**
		* Cart Item Settings
		**/
		$( this.ItemSettings ).bind( 'click', function(){
			v2Checkout.itemsSettings();
		});

		// Bind toggle compact mode
		this.bindToggleComptactMode();

		/**
			* Avoid Closing windows
			* If the cart is not empty
			*/
		$(window).on("beforeunload", function() {
			if( ! v2Checkout.isCartEmpty() ) {
				return "<?php echo addslashes(__('Le processus de commande a commencé. Si vous continuez, vous perdrez toutes les informations non enregistrées', 'nexo'));?>";
			}
		})

		/**
		* we would like to make sure the dom has loaded
		* we can also load order edited
		*/
		setTimeout( () => {
			this.toggleCompactMode(true);
			NexoAPI.events.doAction( 'pos_loaded', v2Checkout );
		}, 1000 );
	}

	/**
	* Toggle Compact Mode
	**/

	this.toggleCompactMode		=	function(){
		$( '.content-header' ).css({
			'padding'	:	0,
			'height'	:	0
		});

		$( '.content-header > h1' ).remove();
		$( '.main-footer' ).hide(0);
		$( '.main-sidebar' ).hide(0);
		$( '.main-footer > *' ).remove();
		$( '.main-header' ).css({
			'min-height' : 0,
			'overflow': 'hidden'
		}).animate({
			'height' : '0'
		}, 0 );

		$( '.content-wrapper' ).addClass( 'new-wrapper' ).removeClass( 'content-wrapper' );
		$( '.new-wrapper' ).css({
			'height'	:	'100%',
			'min-height'	:	'100%'
		});

		$( '.new-wrapper' ).find( '.content' ).css( 'background', 'rgb(211, 223, 228)' );
		this.CompactMode	=	false;
		this.fixHeight();
	}

	this.adjustForMobile 		=	function() {

		if ( $( '.checkout-header' ).attr( 'has-switched-to-mobile' ) === undefined ) {
			$( '.checkout-header' ).attr( 'has-switched-to-mobile', true );
			$( '.checkout-header' ).removeAttr( 'has-switched-to-desktop' );

			$( '.checkout-header' ).css({
				'width' : '100%',
				'margin': 0,
				'overflow-x': 'auto',
				'position':	'relative'
			});

			$( '.checkout-header > div' ).removeClass( 'col-lg-6' ).addClass( 'was-col-lg-6' );

			$( '.checkout-header .right-button-columns' ).children().each( function() {
				$( this ).appendTo( $( '.left-button-columns' ) );
				$( this ).addClass( 'should-restore-to-col-2' )
			});

			$( '.checkout-header > div' ).eq(1).hide();

			$( '.checkout-header > div' ).each( function() {
				let childrenWidth 	=	0;
				$( this ).children().each( function() {
					childrenWidth 	+=	( $( this ).outerWidth() + 15 );
				})
				$( this ).width( childrenWidth );
			});
		}
	}

	this.adjustForDesktop 		=	function() {
		if ( $( '.checkout-header' ).attr( 'has-switched-to-desktop' ) === undefined ) {
			$( '.checkout-header' ).attr( 'has-switched-to-desktop', true );
			$( '.checkout-header' ).removeAttr( 'has-switched-to-mobile' );

			$( '.checkout-header > div' ).eq(1).show();

			$( '.checkout-header > div' ).eq(0).children( '.should-restore-to-col-2').each( function() {
				$( this ).removeClass( 'should-restore-to-col-2' ).appendTo( $( '.checkout-header > div' ).eq(1) );
			});

			$( '.checkout-header > div' ).each( function() {
				$( this ).removeAttr( 'style' );
				$( this ).addClass( 'col-lg-6' );
				$( this ).removeClass( 'was-col-lg-6' );
			});

			$( '.checkout-header' ).removeAttr( 'style' );
			// $( '.checkout-header' ).css({ 
			// 	'padding-bottom' : '15px'
			// });
		}
	}
};

$( document ).ready(function(e) {
	v2Checkout.run();

});

/**
* Filters
**/

// Default order printable
NexoAPI.events.addFilter( 'test_order_type', function( data ) {
	data[1].order_type == 'nexo_order_comptant';
	return data;
});

// Return default data values
NexoAPI.events.addFilter( 'callback_message', function( data ) {
	return data;
});

// Filter for edit item
NexoAPI.events.addFilter( 'cart_before_item_name', function( item_name ) {
	return '<a class="btn btn-sm btn-default quick_edit_item" href="javascript:void(0)" style="float:left;vertical-align:inherit;margin-right:10px;"><i class="fa fa-edit"></i></a> ' + item_name;
});

NexoAPI.events.addFilter( 'cart_item_name', ( data ) => {
	data.displayed 		=	data.displayed.length > 23 ? data.displayed.substr( 0, 18 ) + '...' : data.displayed;
	return data;
});
var Responsive 			=  function(){
	this.screenIs 		=   '';
	this.detect 		=	function(){
		if ( window.innerWidth < 544 ) {
			this.screenIs         =   'xs';
		} else if ( window.innerWidth >= 544 && window.innerWidth < 768 ) {
			this.screenIs         =   'sm';
		} else if ( window.innerWidth >= 768 && window.innerWidth < 992 ) {
			this.screenIs         =   'md';
		} else if ( window.innerWidth >= 992 && window.innerWidth < 1200 ) {
			this.screenIs         =   'lg';
		} else if ( window.innerWidth >= 1200 ) {
			this.screenIs         =   'xg';
		}
	}

	this.is 			=   function( value ) {
		if ( value === undefined ) {
			return this.screenIs;
		} else {
			return this.screenIs === value;
		}
	}

	$( window ).resize( () => {
		this.detect();
	});

	this.detect();
}

var counter         =   0;
var layout 			=	new Responsive();

setInterval( function(){
	if( $( '.enable_barcode_search' ).hasClass( 'active' ) ) {
		if( layout.is( 'md' ) || layout.is( 'lg' ) || layout.is( 'xg' ) ) {
			if( _.indexOf([ 'TEXTAREA', 'INPUT', 'SELECT'], $( ':focus' ).prop( 'tagName' ) ) == -1 || $( ':focus' ).prop( 'tagName' ) == undefined ) {                
				if( counter == 1 ) {
					$( '[name="item_sku_barcode"]' ).focus();
					counter     =   0;
				}
				counter++;
			} 
		}
	}

	if ( layout.is( 'sm' ) || layout.is( 'xs' ) || layout.is( 'md' ) ) {
		v2Checkout.adjustForMobile();
	} else {
		v2Checkout.adjustForDesktop();
	}
}, 1000 );

// we might rather submit the field if the barcode 
// is completely inputted and the field becode idle
<?php if ( store_option( 'auto_submit_barcode_entry', 'yes' ) === 'yes' ):?>
	// if we have this option enabled, we can then 
	// submit all entries if that option is enabled
	// we assume a barcode should have at least 3 letters
	var timer = null;
	$( '[name="item_sku_barcode"]' ).keyup(function() {
		if ( $( '.enable_barcode_search' ).hasClass( 'active' ) ) {
			if (timer) {
				clearTimeout(timer);
			}
			timer = setTimeout(function() {
				if ( $( '[name="item_sku_barcode"]' ).val().length >= 3 ) {
					$( v2Checkout.ItemSearchForm ).submit();
				}
			}, <?php echo $this->config->item( 'min_timebefore_search_field_idle' ) ? $this->config->item( 'min_timebefore_search_field_idle' ) : 300;?> );
		}
	});
	
<?php endif;?>	

function htmlEntities(str) {
    return $( '<div/>' ).text( str ).html()
}

function EntitiesHtml(str) {
    return $( '<div/>' ).html( str ).text();
}
</script>
<?php include_once( dirname( __FILE__ ) . '/print-debug.php' );?>