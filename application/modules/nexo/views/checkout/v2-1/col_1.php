<?php global $Options;?>
<ul class="nav nav-tabs tab-cart hidden-lg hidden-md"> <!--  -->
    <li ng-click="showPart( 'cart', $event );" class="{{ cartIsActive }}"><a href="#"><?php echo __( 'Panier', 'nexo' );?>  <span class="label label-primary total-items-label">0</span></a></li>
    <li ng-click="showPart( 'grid', $event );" class="{{ gridIsActive }}"><a href="#"><?php echo __( 'Produits', 'nexo' );?></a></li>
</ul>
<div class="box mb-0 box-primary direct-chat direct-chat-primary" id="cart-details-wrapper"> <!-- style="visibility:hidden" -->
    <div class="box-header with-border" id="cart-header">
        <form action="#" method="post">
            
            <div class="input-group" ng-controller="cartToolBox">
                
                <span class="input-group-btn">
                    
                </span>

                <select data-live-search="true" name="customer_id" title="<?php _e('Veuillez choisir un client', 'nexo' );?>" class="form-control customers-list dropdown-bootstrap">
                    <option value="">
                        <?php _e('Sélectionner un client', 'nexo');?>
                    </option>
                </select>

                <span class="input-group-btn">
                    <?php if( @$Options[ store_prefix() . 'disable_customer_creation' ] != 'yes' ):?>

                    <button type="button" class="btn btn-default" ng-click="openCreatingUser()" title="<?php _e( 'Ajouter un client', 'nexo' );?>">
                        <i class="fa fa-user"></i>
                        <span class="hidden-sm hidden-xs"><?php _e('Ajouter un client', 'nexo');?></span>
                        <span class="hidden-lg hidden-md">+1</span>
                    </button>

                    <?php endif;?>
                    <?php foreach( $this->events->apply_filters( 'nexo_cart_buttons', [])  as $button ):;?>
                        <?php echo $button;?>
                    <?php endforeach;?>

                    <!-- Should be moved to nexo_cart_buttons -->
                    <?php if( @$Options[ store_prefix() . 'disable_shipping' ] != 'yes' ):?>
                    <button type="button" class="btn btn-default" ng-click="openDelivery()" title="<?php _e( 'Livraison', 'nexo' );?>">
                        <i class="fa fa-truck"></i>
                        <span class="hidden-sm hidden-xs"><?php _e('Livraison', 'nexo');?></span>
                    </button>
                    <?php endif;?>

                    <?php if( @$Options[ store_prefix() . 'disable_quick_item' ] != 'yes' ):?>
                    <button type="button" class="btn btn-default" ng-click="openAddQuickItem()" title="<?php _e( 'Produit', 'nexo' );?>">
                        <i class="fa fa-plus"></i>
                        <span class="hidden-sm hidden-xs"><?php _e('Produit', 'nexo');?></span>
                    </button>
                    <?php endif;?>
                    
                    <!-- refund button --> 
                    <button type="button" class="btn btn-default" ng-click="openRefund()" style="font-weight:bold;" title="REFUND">
                        <i class="fa fa-undo"></i>
                        <span class="hidden-sm hidden-xs"><?php _e('REFUND', 'nexo');?></span>
                    </button>
                    <!-- end refund button -->

                </span>
			</div>
        </form>
    </div>
    <!-- /.box-header -->
    <div class="box-body">
        <table class="table" id="cart-item-table-header">
            <thead>
                <tr class="active">
                    <td width="200" class="text-left"><?php _e('Article', 'nexo');?></td>
                    <td width="120" class="text-center hidden-xs"><?php _e('Prix Unitaire', 'nexo');?></td>
                    <td width="100" class="text-center"><?php _e('Quantité', 'nexo');?></td>
                    <?php if( @$Options[ store_prefix() . 'unit_item_discount_enabled' ] == 'yes' ):?>
                    <td width="90" class="text-center"><?php _e('Remise', 'nexo');?></td>
                    <?php endif;?>
                    <td width="100" class="text-right"><?php _e('Prix Total', 'nexo');?></td>
                </tr>
            </thead>
        </table>
        <div class="direct-chat-messages" id="cart-table-body" style="padding:0px;display: flex; flex-direction:column">
            <table class="table" style="margin-bottom:0;">
                <tbody>
                    <tr id="cart-table-notice">
                        <td colspan="4"><?php _e('Veuillez ajouter un produit...', 'nexo');?></td>
                    </tr>
                </tbody>
            </table>
            <table id="refund" style="width:100%;">
                <tbody class="refund-body"></tbody>
            </table>
            <!-- <div class="refund-body" style="display: flex;flex-direction: column;width:100%;font-weight:bold;"></div> -->
        </div>
        <table class="table" id="cart-details">
            <tfoot class="hidden-xs hidden-sm hidden-md">
                <tr class="active">
                    <td width="200" class="text-left"><?php echo __( 'Nombre de produits', 'nexo' );?> ( <span class="items-number">0</span> )</td>
                    <td width="150" class="text-right hidden-xs"></td>
                    <td width="150" class="text-right"><?php
                        if ( in_array( store_option( 'nexo_vat_type' ),  [ 'fixed', 'variable', 'item_vat' ], true ) ) {
                            _e('Net hors taxe', 'nexo');                            
                        } else {
                            _e('Sous Total', 'nexo');
                        }
                        ?></td>
                    <td width="90" class="text-right"><span class="cart-value"></span></td>
                </tr>

                <tr class="active">
                    <td></td>
                    <td></td>
                    <td class="text-right cart-discount-notice-area"><?php _e('Remise sur le panier', 'nexo');?></td>
                    <td class="text-right cart-discount-remove-wrapper"><span class="cart-discount pull-right"></span></td>
                </tr>

                <?php if ( store_option( 'disable_shipping' ) != 'yes' ):?>
                <tr class="active">
                    <td></td>
                    <td></td>
                    <td class="">
                        <span class="pull-right"><?php echo __( 'Livraison', 'nexo' );?> </span>
                    </td>
                    <td class="text-right"><span class="pull-right cart-shipping-amount"></span></td>
                </tr>
                <?php endif;?>

                <tr class="success taxes_large refund-mate">
                    <?php if ( (  in_array( store_option( 'nexo_vat_type' ),  [ 'fixed', 'variable', 'item_vat' ], true )  && ! empty( store_option( 'nexo_vat_percent' ) ) ) || store_option( 'disable_shipping' ) != 'yes' ):?>
                        <?php if( store_option( 'disable_shipping' ) != 'yes' ):?>
                            <?php if ( in_array( store_option( 'nexo_vat_type' ),  [ 'fixed', 'variable', 'item_vat' ], true )  && ! empty( store_option( 'nexo_vat_percent' ) ) ):?>
                                <?php include( dirname( __FILE__ ) . '/tax-display.php' );?>
                            <?php else:?>
                                <td></td>
                            <?php endif;?>
                            <td></td>
                        <?php else:?>
                            <?php if ( in_array( store_option( 'nexo_vat_type' ),  [ 'fixed', 'variable', 'item_vat' ], true )  && ! empty( store_option( 'nexo_vat_percent' ) ) ):?>
                                <?php include( dirname( __FILE__ ) . '/tax-display.php' );?>
                                <td></td>
                            <?php else:?>
                                <td></td>
                                <td></td>
                            <?php endif;?>
                        <?php endif;?>
                    <?php else:?>
                        <td></td>
                        <td></td>
                    <?php endif;?>
                    <td class="text-right">
                        <strong>
                        <?php _e('Net à payer', 'nexo');?>
                        </strong></td>
                    <td class="text-right"><span class="cart-topay pull-right"></span></td>
                </tr>
            </tfoot>
            <tfoot class="hidden-lg">
                <tr class="active">
                    <td>
                        <span class="hidden-xs">
                        <?php
                        if ( in_array( store_option( 'nexo_vat_type' ),  [ 'fixed', 'variable' ], true ) ) {
                            _e('Net hors taxe', 'nexo');
                        } else {
                            _e('Sous Total', 'nexo');
                        }
                        ?> 
                        </span>                    
                        <span class="cart-value pull-right"></span>
                    </td>
                    <td><?php _e('Remise', 'nexo');?><span class="cart-discount pull-right"></span></td>
                </tr>
                <tr class="active taxes_small">
                    <?php if ( in_array( store_option( 'nexo_vat_type' ),  [ 'fixed', 'variable', 'item_vat' ], true ) ) : ?>
                        <?php include( dirname( __FILE__ ) . '/tax-display.php' );?>
                    <?php else:?>
                    <td></td>
                    <?php endif;?>                        
                    <td><?php _e('à payer', 'nexo');?> <span class="cart-topay pull-right"></span></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <!-- /.box-body -->
    <div class="box-footer" id="cart-panel">
        <div class="btn-group btn-group-justified" role="group" aria-label="...">
			<?php echo $this->events->apply_filters( 'before_cart_pay_button', '' );?>
            <?php echo $this->events->apply_filters( 'cart_pay_button', $this->load->module_view( 'nexo', 'checkout.v2-1.cart_pay_button', null, true ) );?>
            <?php echo $this->events->apply_filters( 'before_cart_save_button', '' );?>
            <?php echo $this->events->apply_filters( 'cart_hold_button', $this->load->module_view( 'nexo', 'checkout.v2-1.cart_hold_button', null, true ) );?>
            <?php echo $this->events->apply_filters( 'before_cart_discount_button', '' );?>
            <?php echo $this->events->apply_filters( 'cart_discount_button', $this->load->module_view( 'nexo', 'checkout.v2-1.cart_discount_button', null, true ) );?>
            <?php echo $this->events->apply_filters( 'before_cart_cancel_button', '' );?>
            <?php echo $this->events->apply_filters( 'cart_cancel_button', $this->load->module_view( 'nexo', 'checkout.v2-1.cart_cancel_button', null, true ) );?>
        </div>
    </div>
    <!-- /.box-footer-->
</div>
<?php if (@$Options[ store_prefix() . 'nexo_enable_stripe' ] != 'no'):?>
<script type="text/javascript" src="https://checkout.stripe.com/checkout.js"></script>
<script type="text/javascript">
	'use strict';
	// Close Checkout on page navigation:
	$(window).on('popstate', function() {
		v2Checkout.stripe.handler.close();
	});
</script>
<?php endif;?>
<style type="text/css">
.slick-item {
    padding:0px 20px;
    font-size:20px;
    line-height:40px;
    border-right:solid 1px #EEE;
    margin-right:-1px;
}
.expandable {
	width: 19%;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    transition-property: width;
	transition-duration: 2s;
}
.item-grid-title {
	width: 19%;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    transition-property: width;
	transition-duration: 2s;
}
.item-grid-price {
	width: 19%;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    transition-property: width;
	transition-duration: 2s;
}
.expandable:hover{
	overflow: visible;
    white-space: normal;
    width: auto;
}
.shop-items:hover {
	background:#FFF;
	cursor:pointer;
	box-shadow:inset 5px 5px 100px #EEE;
}
.noselect {
  -webkit-touch-callout: none; /* iOS Safari */
  -webkit-user-select: none;   /* Chrome/Safari/Opera */
  -khtml-user-select: none;    /* Konqueror */
  -moz-user-select: none;      /* Firefox */
  -ms-user-select: none;       /* Internet Explorer/Edge */
  user-select: none;           /* Non-prefixed version, currently
                                  not supported by any browser */
}
.img-responsive {
    margin: 0 auto;
}
.modal-dialog {
	margin: 10px auto !important;
}

/**
 Account.lk 2.7.1
**/

#cart-table-body .table>thead>tr>th, .table>tbody>tr>th, .table>tfoot>tr>th, .table>thead>tr>td, .table>tbody>tr>td, .table>tfoot>tr>td {
    border-bottom: 1px solid #f4f4f4;
	margin-bottom:-1px;
}
.box {
	border-top: 0px solid #d2d6de;
}
</style>
