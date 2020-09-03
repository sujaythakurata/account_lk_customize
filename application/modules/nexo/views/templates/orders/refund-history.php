
<div id="refund-history-wrapper" class="d-flex flex-column h-100 p-4" style="overflow-y: auto;height: 100%;">
    <div class="row">
        <div class="col-md-4 mb-4 col-lg-4 col-xl-2" v-for="history in histories" style="display:none">
            <div class="card">
                <div class="card-body" style="height: 150px">
                    <h4 class="card-title mb-0"><?php echo sprintf( __( 'Remboursement : %s' ), '{{ history.DATE_CREATION }}' );?></h4>
                    <small class="card-subtitle mb-2 text-muted">{{ getRefundType( history.TYPE ) }} &mdash; {{ history.author.name }}</small>
                    <p class="card-text"><?php echo sprintf( __( 'Raison : %s', 'nexo' ), '{{ history.DESCRIPTION }}' );?></p>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><?php echo __( 'Sous Total', 'nexo' );?> <span class="pull-right">{{ history.SUB_TOTAL | moneyFormat }}</span></li>
                    <li class="list-group-item"><?php echo __( 'Livraison', 'nexo' );?> <span class="pull-right">{{ history.SHIPPING | moneyFormat }}</span></li>
                    <li class="list-group-item"><?php echo __( 'Total', 'nexo' );?> <span class="pull-right">{{ history.TOTAL | moneyFormat }}</span></li>
                    <li class="list-group-item p-2">
                        <bs4-button-toggle @clicked="submitPrintJob( $event, history )" label="<?php echo __( 'Imprimer le ticket', 'nexo' );?>" :options="printOptions"></bs4-button-toggle>
                    </li>
                </ul>
            </div>
        </div>

        <div class="d-flex flex-column h-100 p-4" v-for="history in histories" style="margin-left: 25px; overflow-y: auto;" v-for="history in histories">
          <div class="card" style="padding:3px; border-radius: 3px; border-color: #eee; border-width: 1px; width: 82mm;">
            <div id="refundPrintContent" style="width: 80mm;" >

              <div class="col-lg-12 col-xs-12 col-sm-12 col-md-12" style="margin-top:25px">
        				<?php if( store_option( 'url_to_logo' ) != null ):?>
        				<div class="text-center">
        					<img src="<?php echo store_option( 'url_to_logo' );?>" style="display:inline-block;<?php echo store_option( 'logo_height' ) != null ? 'height:' . store_option( 'logo_height' ) . 'px' : '';?>
        						;<?php echo store_option( 'logo_width' ) != null ? 'width:' . store_option( 'logo_width' ) . 'px' : '';?>" />
        				</div>
        				<?php else:?>
        				<h2 class="text-center">{{ history.STORE_NAME }}</h2>
        				<?php endif;?>
        			</div>

              <!--
              <textarea name="name" rows="8" cols="80"><?php echo('{{ history }}');?></textarea>
              -->


              <div>
                <table class="table pl-3" style="font-size:11px;margin:0">
                  <tr>
                    <th style="width: 85px;"></th>
                    <th style="width: 4px;"></th>
                    <th></th>
                  </tr>
                  <tr>
                    <td style="border:0; font-weight: 700; font-size:1.5em; width:50%;">Sales Details</td>
                  </tr>
                  <tr>
                    <td style="border:0"><b>Sales Code</b></td>
                    <td style="border:0">:</td>
                    <td style="border:0">{{ history.CODE }}</td>
                  </tr>

                  <tr>
                    <td style="border:0"><b>Sales Date</b></td>
                    <td style="border:0">:</td>
                    <td style="border:0">{{ refundDateFormat(history.DATE_CREATION) }}</td>
                  </tr>

                  <tr>
                    <td style="border:0"><b>Sales Time</b></td>
                    <td style="border:0">:</td>
                    <td style="border:0">{{ refundTimeFormat(history.DATE_CREATION) }}</td>
                  </tr>
                  <tr>
                    <td style="border:0; font-weight: bold;">Sales Cashier</td>
                    <td style="border:0">:</td>
                    <td style="border:0">{{history.author.name}}</td>
                  </tr>
                  <tr>
                    <td style="border:0; font-weight: 700; font-size:1.5em; width:50%;">Refund Details</td>
                  </tr>
                  <tr>
                    <td style="border:0; font-weight: bold;">
                      Refund Code
                    </td>
                    <td style="border:0">:</td>
                    <td style="border:0;">{{history.ID}}</td>
                  </tr>
                  <tr>
                    <td style="border:0; font-weight: bold;">Refund Date</td>
                    <td style="border:0">:</td>
                    <td style="border:0">{{ refundDateFormat(history.DATE_CREATION) }}</td>
                  </tr>
                  <tr>
                    <td style="border:0; font-weight: bold;">Refund Time</td>
                    <td style="border:0">:</td>
                    <td style="border:0">{{ refundTimeFormat(history.DATE_CREATION) }}</td>
                  </tr>
                  <tr>
                    <td style="border:0; font-weight: bold;">Refund Type</td>
                    <td style="border:0">:</td>
                    <td style="border:0">{{history.TYPE}}</td>
                  </tr>
                  <tr>
                    <td style="border:0; font-weight: bold;">Refund Cashier</td>
                    <td style="border:0">:</td>
                    <td style="border:0">{{history.author.name}}</td>
                  </tr>
                </table>

                <table class="table pl-3" style="font-size:11px;margin-top:10px">
                  <tr>
                    <th style="width: 45mm;text-align:left">Item</th>
                    <th style="width: 15mm; text-align:right">Qty</th>
                    <th style="text-align:right">Subtotal</th>
                  </tr>

                  <tr v-for="item in history.items">
                    <td>{{ item.NAME }}</td>
                    <td style="text-align:right">{{ item.QUANTITY }}</td>
                    <td style="text-align:right">{{ item.TOTAL_PRICE }}</td>
                  </tr>

                </table>

                <h3 style="margin-top:70px" class="text-center">Refund</h3>

                <table class="table pl-3" style="font-size:11px;margin:0;">
                  <tr>
                    <th style="width: 85px;"></th>
                    <th style="width: 4px;"></th>
                    <th></th>
                  </tr>
                  <tr>
                    <td style="border:0"><b>Subtotal</b></td>
                    <td style="border:0"><b>:</b></td>
                    <td style="border:0"><b>{{ history.SUB_TOTAL }}</b></td>
                  </tr>

                  <tr>
                    <td style="border:0"><b>Total</b></td>
                    <td style="border:0"><b>:</b></td>
                    <td style="border:0"><b>{{ history.TOTAL }}</b></td>
                  </tr>

                </table>

        			</div>
        </div>
        <button onclick="printDiv('refundPrintContent')"  class="btn btn-secondary" style="margin-top:50px;">Print</button>
      </div>
    </div>
</div>
