<?php

/**
 * Host Account_Lanka Configs
**/

$config[ 'discounted_item_background' ]        =    '#DFF0D8';

/**
 * Order Types
**/

$config[ 'nexo_order_types' ]    =    array(
    'nexo_order_comptant'           =>    	get_instance()->lang->line('nexo_order_complete'),
    'nexo_order_advance'            =>    	get_instance()->lang->line('nexo_order_advance'),
    'nexo_order_devis'              =>    	get_instance()->lang->line('nexo_order_quote'),
	'nexo_order_refunded'			=>		get_instance()->lang->line( 'nexo_order_refunded' ),
	'nexo_order_partially_refunded'	=>		get_instance()->lang->line( 'nexo_order_partially_refunded' ),
);

/**
 * Discount Type
**/

$config[ 'nexo_discount_type' ]    =    array(
    'disable'        		=>    get_instance()->lang->line('disabled'),
    'amount'        		=>    get_instance()->lang->line('nexo_flat_discount'),
    'percent'        		=>    get_instance()->lang->line('nexo_percentage_discount')
);

/**
 * Account_Lanka True or False dropdown menu
**/

$config[ 'nexo_true_false' ]    =    array(
    'false'                 =>    get_instance()->lang->line('no'),
    'true'                  =>    get_instance()->lang->line('yes')
);

/**
 * Coupons Type
**/

$config[ 'coupon_type' ]    =   [
    'percentage'        =>  __( 'Pourcentage', 'nexo' ),
    'fixed'             =>  __( 'Montant Fixe', 'nexo' ),
];

/**
 * Payment Type
 *@V15.01 pos screen
 *add a new payment type 'refund' for refunded money
**/

$config[ 'nexo_payments_types' ]    =    array(
    'cash'            	=>    	get_instance()->lang->line('cash'),
	'creditcard'		=>		get_instance()->lang->line( 'creditcard' ),
    'cheque'			=>		get_instance()->lang->line( 'cheque' ),
    'bank'            	=>    	get_instance()->lang->line('bank_transfert'),
	'coupon'            =>      get_instance()->lang->line('coupon'),
	'cod'				=>		__( 'Paiement à la livraison', 'nexo' ),
	'refund'			=>		__("REFUND", 'nexo')
	// 'unknow'			=>		__( 'Inconnu', 'nexo' ),
	// 'payal'				=>		__( 'PayPal', 'nexo' )
);

$config[ 'nexo_orders_status' ]    =    [
	'pending'			=>	__( 'En attente', 'nexo' ),
	'processing' 		=>	__( 'En cours', 'nexo' ),
	'completed'			=>	__( 'Terminé', 'nexo' ),
	'shipped'			=>	__( 'Expédié', 'nexo' ),
	'delivered'			=>	__( 'Livrée', 'nexo' ),
	'error'				=>	__( 'Erreur', 'nexo' ),
];

$config[ 'nexo_all_payment_types' ] =   array(
    'cash'            	=>    	get_instance()->lang->line('cash'),
	'creditcard'		=>		get_instance()->lang->line( 'creditcard' ),
    'cheque'			=>		get_instance()->lang->line( 'cheque' ),
    'bank'            	=>    	get_instance()->lang->line('bank_transfert'),
    'multi'				=>		get_instance()->lang->line( 'multi' ),
	'coupon'            =>      get_instance()->lang->line( 'coupon' ),
	'cod'				=>		__( 'Paiement à la livraison', 'nexo' ),
	'unknow'			=>		__( 'Inconnu', 'nexo' ),
	// 'payal'				=>		__( 'PayPal', 'nexo' )
);

/**
 * Cart Animation
**/

$config[ 'nexo_cart_animation' ]            =    false; // was 'animated zoomIn';

/**
 * Supported Currency
**/

$config[ 'nexo_supported_currency' ]        =    array( 'usd', 'eur','gbp', 'sgd', 'myr' );

/**
 * Test Mode
**/

$config[ 'nexo_test_mode' ]            =    true;

/**
 * Sound Fx
 * Enable fx for 'success', 'info', 'warning', 'bootbox'
**/

$config[ 'nexo_sound_fx' ]        =    array( 'success', 'info', 'warning', 'bootbox' );

/**
 * Items Cache duration
**/

$config[ 'nexo_items_cache_lifetime' ]        =    86400; // 3 hours

/**
 * Widget Cache
**/

$config[ 'nexo_widget_cache_lifetime' ]        =    86400;

/**
 * Feed Max execution time
**/

$config[ 'feed_execution_time' ]  = 10; // seconds

/**
 * Dashboard Profile widget cashe lifetime
**/

$config[ 'profile_widget_cashier_sales_lifetime' ]    =    86400; // one day

/**
 * Balance Namespace
**/

$config[ 'nexo_balance_namespace' ]		=	array(
	'opening_balance'		=>	get_instance()->lang->line( 'balance_opening' ),
	'closing_balance'		=>	get_instance()->lang->line( 'balance_closing' )
);

/**
 * Account_Lanka Register Status
**/

$config[ 'nexo_registers_status' ]		=	array(
	'opened'			=>	get_instance()->lang->line( 'register_open' ),
	'closed'		=>	get_instance()->lang->line( 'register_closed' ),
	'locked'		=>	get_instance()->lang->line( 'register_locked' ),
);

$config[ 'nexo_registers_status_for_creating' ]		=	$config[ 'nexo_registers_status' ];

/**
 * Register Can only be closed or locked while creating. Its not allowed to open register
 * because register need an initial balance to start, which is required
**/

unset( $config[ 'nexo_registers_status_for_creating' ][ 'opened' ] );

/**
 * Invoice/Receipt
 * @since 2.7.9
**/

$config[ 'nexo_receipts_namespaces' ]		=	array(
	'custom'			=>	get_instance()->lang->line( 'custom_receipt' ),
	'default'			=>	get_instance()->lang->line( 'receipt_default' )
);

$config[ 'nexo_receipts_routes' ]			=	array(
	'default'			=>	MODULESPATH . '/nexo/views/invoices/default.php',
	'custom'			=>	null
);

/**
 * Account_Lanka Shop Status
 * @since 2.8.0
**/

$config[ 'nexo_shop_status' ]			=	array(
	'opened'			=>	get_instance()->lang->line( 'opened' ),
	'closed'			=>	get_instance()->lang->line( 'closed' ),
	'unavailable'		=>	get_instance()->lang->line( 'unavailable' )
);

/**
 * Enable Multi Store Services
 * @since 2.8.0
**/

$config[ 'nexo_multi_store_enabled' ]		=	true; // default false;

/**
 * Item Tpe
**/

$config[ 'nexo_item_type' ]		=	array(
	1	=>	get_instance()->lang->line( 'physical_item' ),
	2	=>	get_instance()->lang->line( 'numerical_item' ),
	3   => 	get_instance()->lang->line( 'grouped_item' ),
);

/**
 * Item Status
**/

$config[ 'nexo_item_status' ]		=	array(
	1	=>	get_instance()->lang->line( 'item_on_sale' ),
	2	=>	get_instance()->lang->line( 'item_out_of_stock_disabled' )
);

/**
 * Item Stock
**/

$config[ 'nexo_item_stock' ]		=	array(
	1	=>	get_instance()->lang->line( 'enabled' ),
	2	=>	get_instance()->lang->line( 'disabled' )
);

/**
 * Yes No
**/

$config[ 'nexo_yes_no' ]		=	array(
	1	=>	get_instance()->lang->line( 'yes' ),
	2	=>	get_instance()->lang->line( 'no' )
);

/**
 * Grocery Groups
**/

// Price Group

// 'TAUX_DE_MARGE', 'COUT_DACHAT',
// 'FRAIS_ACCESSOIRE',
// 'PRIX_DACHAT', 
$config[ 'nexo_item_price_group' ]	=	array(
	'REF_TAXE', 'TAX_TYPE', 'PRIX_DE_VENTE', 'PRIX_DE_VENTE_TTC', 
	// 'SHADOW_PRICE', @deprecated
	'PRIX_PROMOTIONEL', 'SPECIAL_PRICE_START_DATE', 'SPECIAL_PRICE_END_DATE'
);

// Stock Group
// 'DEFECTUEUX',
$config[ 'nexo_item_stock_group' ]	=	array(
	// removed 'REF_PROVIDER', 'QUANTITY',
	'STATUS', 'TYPE', 'STOCK_ENABLED', 'STOCK_ALERT', 'ALERT_QUANTITY'
);

// Caracteristiques
$config[ 'nexo_item_spec_group' ] 	=	array(
	'HAUTEUR', 'LARGEUR', 'POIDS', 'TAILLE', 'COULEUR', 'APERCU', 'DATE_CREATION', 'DATE_MOD', 'AUTHOR', 'DESCRIPTION'
);

// Details
$config[ 'nexo_item_details_group' ]=	array(
	// removed 'REF_SHIPPING', 
	'DESIGN', 'ALTERNATIVE_NAME', 'SKU', 'REF_CATEGORIE', 'AUTO_BARCODE', 'BARCODE_TYPE', 'CODEBAR',
	'EXPIRATION_DATE', 'ON_EXPIRE_ACTION'
);

// Barcode Type
$config[ 'nexo_barcode_supported' ]	=	array(
	// 'default'	=>	get_instance()->lang->line( 'default' ),
	'ean8'		=>	'EAN 8',
	'ean13'		=>	'EAN 13',
	'code_128'	=>	'Code 128',
	'type_msi'	=>	'TYPE MSI',
	'codabar'		=>	'CODABAR'
);

// Config
$config[ 'store_url' ] 			=	'https://server.nexopos.com/';
$config[ 'nexo_platform_url' ] 	=	is_file( FCPATH . '/.dev' ) ? 'http://laravel-5701.go/' : 'https://platform.nexopos.com/';
// $config[ 'store_url' ] 		=	'http://127.0.0.1:8000/';

$config[ 'stock-operation' ] 	=	[
	'transfert_in' 			=>	get_instance()->lang->line( 'transfert_in' ),
	'transfert_out' 		=>	get_instance()->lang->line( 'transfert_out' ),
	'transfert_rejected' 	=>	get_instance()->lang->line( 'transfert_rejected' ),
	'transfert_canceled'	=>	get_instance()->lang->line( 'transfert_canceled' ),
	'defective'				=>	get_instance()->lang->line( 'defective' ),
	'supply'				=>	get_instance()->lang->line( 'supply' ),
	'usable' 				=>	get_instance()->lang->line( 'usable' ),
	'adjustment'			=>	get_instance()->lang->line( 'adjustment' ),
	'import' 				=>	get_instance()->lang->line( 'import' ),
	'sale' 					=>	get_instance()->lang->line( 'sale' )
];

/**
 * Refresh Order Alert
 * @since 3.9.0
**/

$config[ 'refresh_alert_orders' ] 	= 	86400; // one day

/**
 * set the time before the pos search field detect idle action
 */
$config[ 'min_timebefore_search_field_idle' ] 	=	300; // micro seconds.

/**
 * Configure the printing timeout.
 * Somehosting might not release the
 * assets as fast as we could expect. 
 * 500 is then a value that should be increased. 
 * It shouldn't be 0 or less, otherwise the 
 * fallback is used (500)
 */
$config[ 'nexo_print_timeout' ] 	=	200; // it's expressed in milliseconds

/**
 * pos cache helps to make 
 * the system runs faster while being
 * on pos. This helps to enable or to disable
 */
$config[ 'nexo_pos_cache' ] 		= 	false;