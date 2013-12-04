<?php
/*
Plugin Name: WooCommerce Recent Orders API Endpoint
Description: Adds an API endpoint at /api/recent-orders/$n
Version: 1.0
Author: Aman Sihint
Author URL: http://arcpixel.com
*/

class RecentOrders_API_Endpoint{
	
	/** Hook WordPress
	*	@return void
	*/
	public function __construct(){
		add_filter('query_vars', array($this, 'add_query_vars'), 0);
		add_action('parse_request', array($this, 'sniff_requests'), 0);
		add_action('woocommerce_init', array($this, 'add_endpoint'), 0);
	}	
	
	/** Add public query vars
	*	@param array $vars List of current public query vars
	*	@return array $vars 
	*/
	public function add_query_vars($vars){
		$vars[] = '__api';
		$vars[] = 'orders';
		return $vars;
	}
	
	/** Add API Endpoint
	*	@return void
	*/
	public function add_endpoint(){
		add_rewrite_rule('^api/recent-orders/?([0-9]+)?/?','index.php?__api=1&orders=$matches[1]','top');
	}

	/**	Sniff Requests	
	* 	If $_GET['__api'] is set, we will handle the request to get recent orders details
	*	@return die if API request
	*/
	public function sniff_requests(){
		global $wp;
		if(isset($wp->query_vars['__api'])){
			$this->handle_request();			
		}
	}
	
	/** Handle Requests
	*	@return void 
	*/
	protected function handle_request(){
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            $this->getRecentOrders();
		}        
	}
    
    /** Get Recent Orders
	*	@return void 
	*/
	public function getRecentOrders(){
		global $wp;
		$orders = $wp->query_vars['orders'];
		if(!$orders) {
            $orders = get_option('posts_per_page');  
		}			
		
		$args = array(
            'post_type' => 'shop_order',
            'post_status' => 'publish',            
            'posts_per_page' => -1,
            'order'     => 'DESC',
            'orderby'   => 'ID'
        );
        
        $my_query = new WP_Query($args);
        
        $customer_orders = $my_query->posts;
        
        $orders = array();
        
        foreach ($customer_orders as $customer_order) {
            $order = new WC_Order();            
            $order->populate($customer_order);
            $orders[] = $order;                    
        }
        
        if(is_array($orders) && count($orders > 0)) {            
            $this->send_response('200 OK', json_encode($orders));
        } else {            
			$this->send_response('Something went wrong!');
        }
	}
	
	/** Response Handler
	*	This sends a JSON response to the browser
	*/
	protected function send_response($msg, $orders){
		$response['message'] = $msg;		
        $response['orders'] = $orders;
		
			
		header('content-type: application/json; charset=utf-8');
	    echo json_encode($response)."\n";
	    exit;
	}
}

new RecentOrders_API_Endpoint();
