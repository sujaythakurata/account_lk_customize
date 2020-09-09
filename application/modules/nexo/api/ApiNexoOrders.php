<?php
use Carbon\Carbon;
class ApiNexoOrders extends Tendoo_Api
{
    public function full_order( $order_id )
    {
        $this->load->model( 'Nexo_Checkout' );
        $this->load->module_model( 'nexo', 'NexoCustomersModel', 'customerModel' );
        $this->load->module_model( 'nexo', 'Nexo_Orders_Model', 'orderModel' );
        
        $data        =    $this->events->apply_filters( 
            'loaded_order', 
            $this->Nexo_Checkout->get_order_products($order_id, true) 
        );

        if( $data ) {
            // load shippings
            /** 
             * get shippings linked to that order
             * @since 3.1
            **/

            foreach( ( array ) $data[ 'order' ] as $index => $_order ) {
                $shippings   =   $this->db->where( 'ref_order', $_order[ 'ID' ] )
                ->get( store_prefix() . 'nexo_commandes_shippings' )
                ->result_array();

                if( $shippings ) {
                    $data[ 'order' ][ $index ][ 'shipping' ]   =   $shippings[0];
                }

                $payments   =   $this->db->where( 'REF_COMMAND_CODE', $_order[ 'CODE' ] )
                    ->get( store_prefix()  . 'nexo_commandes_paiements' )
                    ->result_array();

                /**
                 * let's embed the coupon
                 * on the payment, if it's a coupon.
                 */
                foreach( $payments as &$payment ) {
                    $payment[ 'coupon' ]        =   [];
                    if ( $payment[ 'PAYMENT_TYPE' ] === 'coupon' ) {

                        $coupon    =    $this->db->where( 'ID', $payment[ 'REF_ID' ])
                            ->get( store_prefix() . 'nexo_coupons' )
                            ->result_array();

                        if ( $coupon ) {
                            $payment[ 'coupon' ]    =   $coupon[0];
                        }
                    }
                }
                
                $data[ 'order' ][ $index ][ 'payments' ]   =   $payments;                
            }

            $data[ 'order' ]    =   $data[ 'order' ][0];
            
            /**
             * load customer informations
             */
            $base           =   $this->customerModel->get( $_order[ 'REF_CLIENT' ] );

            $data[ 'customer' ]    =   [
                'informations'      =>  $base[0],
                'address'           =>  $this->customerModel->get_informations( $_order[ 'REF_CLIENT' ] ),
            ];

            /**
             * include refund made on this orders
             * @var array
             */
            $refunds    =   $this->db->where( 'REF_ORDER', $_order[ 'ID' ] )
                ->get( store_prefix() . 'nexo_commandes_refunds' )
                ->result_array();
            
            /**
             * Fill refunds with refunded items
             * but sometime they might not 
             * be provided
             * @var array
             */
            $refunds    =   array_map( function( &$refund ) {
                $items  =   $this->db->where( 'REF_REFUND', $refund[ 'ID' ] )
                    ->get( store_prefix() . 'nexo_commandes_refunds_products' )
                    ->result_array();
                
                $refund[ 'items' ]  =   $items;
                return $refund;
            }, $refunds );

            $data[ 'refunds' ]  =   $refunds;
            
            $this->response( $this->events->apply_filters( 'nexo_full_order', $data, $_order ), 200 );
        }      

		$this->__empty();
    }

    /**
     * get Orders
     * @return json
     */
    public function orders()
    {
        $orders     =   $this->db->get( store_prefix() . 'nexo_commandes' )
        ->result_array();

        return $this->response( $orders );
    }

    /**
     * Change order status
     * @param int order id
     * @return json
     */
    public function setOrderStatus( $order_id ) 
    {
        $this->load->module_model( 'nexo', 'NexoLogModel', 'history' );
        $this->load->model( 'Nexo_Checkout' );
        $status     =   $this->config->item( 'nexo_orders_status' );

        $order  =   $this->Nexo_Checkout->get_order( $order_id );

        if( $order[0][ 'STATUS' ] === $this->post( 'status' ) ) {
            return $this->response([
                'status'    =>  'failed',
                'message'   =>  __( 'Aucune modification à enregistrer !' )
            ]);
        }

        /**
         * If the order has been found, let's yet register his state.
         */
        if( $order ) {
            $this->history->log( 
                __( 'Mise à jour d\'une commande', 'nexo' ),
                sprintf( 
                    __( 'Le statut de la commande <strong>%s</strong> a changé de <strong>%s</strong> à <strong>%s</strong> par <strong>%s</strong>', 'nexo' ), 
                    $order[0][ 'CODE' ],
                    $status[ $order[0][ 'STATUS' ] ],
                    $status[ $this->post( 'status' ) ],
                    User::pseudo()
                )
            );
        }

        $this->db->where( 'ID', $order_id )
        ->update( store_prefix() . 'nexo_commandes', [
            'STATUS'    =>  $this->post( 'status' ),
            'DATE_MOD'  =>  date_now()
        ]);

        return $this->response([
            'status'    =>  'success',
            'message'   =>  __( 'Le statut de la commande a été mis à jour', 'nexo' )
        ]);
    }

    /**
     * Proceed to a payment of an order
     * @return json
     */
    public function payment( $order_id ) {
        
        $this->load->module_model( 'nexo', 'Nexo_Orders_Model', 'orderModel' );

        $response   =   $this->orderModel->addPayment( $order_id, $this->post( 'amount' ), $this->post( 'namespace' ) );

        $this->orderModel->watchIfCompleted( $order_id );

        if( $response[ 'status' ] === 'failed' ) {
            return $this->response( $response, 403 );
        }

        return $this->response( $response );
    }

    /**
     * Return a json object of 
     * payment made for an order
     * @return json
     */
    public function payments( $order_id ) 
    {
        $this->load->module_model( 'nexo', 'Nexo_Orders_Model', 'orderModel' );

        $response   =   $this->orderModel->getPayments( $order_id );

        if( @$response[ 'status' ] === 'failed' ) {
            return $this->response( $response, 403 );
        }

        return $this->response( $response );
    }

    /**
     * Make a partial refund
     * @param int order id
     * @return json
     */
    public function refund( $order_id ) 
    {
        $this->load->module_model( 'nexo', 'Nexo_Orders_Model', 'orderModel' );
        
        $total                      =   $this->post( 'total' );
        $sub_total                  =   $this->post( 'sub_total' );
        $shipping_fees              =   $this->post( 'shipping_fees' );
        $type                       =   $this->post( 'type' );
        $description                =   $this->post( 'description' );
        $payment_type               =   $this->post( 'payment_type' );
        $products                   =   $this->post( 'products' );
        $refund_shipping_fees       =   $this->post( 'refund_shipping_fees' );
        
        $response       =   $this->orderModel->refund( compact( 
            'shipping_fees', 
            'refund_shipping_fees', 
            'total', 
            'sub_total', 
            'order_id', 
            'type', 
            'description', 
            'payment_type', 
            'products' 
        ) );

        return $this->response( $response );
    }

    /**
     * Display the refund history registered for a specific order
     * @param int order id
     * @return json
     */
    public function refundHistory( $order_id )
    {
        $this->load->module_model( 'nexo', 'Nexo_Orders_Model', 'orderModel' );
        return $this->response( $this->orderModel->order_refunds( $order_id ) );
    }

    /*
        *@V15.01 pos screen
        *function to get refund details
        *@param refund id

    */


    public function refundDetails( $refund_id )
    {
        $this->load->module_model( 'nexo', 'Nexo_Orders_Model', 'orderModel' );
        return $this->response( $this->orderModel->get_refund( $refund_id ) );
    }




}