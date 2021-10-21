<?php
// Heading
$_['heading_title']  = 'Notify.Events';

// Tabs
$_['page_title_index']        = 'About';
$_['page_title_channel_form'] = 'Channel Form';
$_['page_title_channels']     = 'Channels';
$_['page_title_events']       = 'Events';
$_['page_title_event_form']   = 'Event Form';
$_['page_title_test']         = 'Test';

/*~~~~~~~~*/
/* Common */
/*~~~~~~~~*/

$_['common_add_btn_text']        = 'Add New';
$_['common_delete_btn_text']     = 'Delete';
$_['common_delete_confirm_text'] = 'Are you sure?';

$_['back_to_events']   = 'Back to Events';
$_['back_to_channels'] = 'Back to Channels';
$_['save_event']       = 'Save Event';

// General buttons

$_['add_btn_text']    = 'Add';
$_['back_btn_text']   = 'Back';
$_['delete_btn_text'] = 'Delete';


// 404
$_['object_not_found']      = 'Object not found';
$_['channel_not_found']     = 'Selected Channel not found, please try again';
$_['error_required_params'] = 'This field is required';

// Priority list
$_['priority_highest'] = 'Highest';
$_['priority_high']    = 'High';
$_['priority_normal']  = 'Normal';
$_['priority_low']     = 'Low';
$_['priority_lowest']  = 'Lowest';

// Text
$_['text_extension']   = 'Extensions';
$_['text_success']     = 'Success: You have modified channel!';
$_['text_edit']        = 'Edit Channel';

/*~~~~~~~~*/
/* Events */
/*~~~~~~~~*/

// Interface
$_['event_list']                   = 'Event List';
$_['channels_list']                = 'Channel List';
$_['add_event_modal_title']        = 'Select event type';
$_['event_added_successfully']     = 'Success: Event was saved!';
$_['event_deleted_successfully']   = 'Success: Event was deleted!';
$_['channel_added_successfully']   = 'Success: Channel was saved!';
$_['channel_deleted_successfully'] = 'Success: Channel was deleted!';
$_['event_class_undefined']        = 'You need to choose event type!';
$_['create_a_channel_first']       = 'You need to create a Channel first!';

$_['column_enabled']  = 'Enabled';
$_['column_title']    = 'Title';
$_['column_channels'] = 'Channels';
$_['column_priority'] = 'Priority';
$_['column_action']   = 'Action';

$_['new_event_heading']   = 'New Event';
$_['new_channel_heading'] = 'New Channel';

// List
$_['event_group_product']  = 'Product';
$_['event_group_order']    = 'Order';
$_['event_group_user']     = 'User';
$_['product_out_of_stock'] = 'Out Of Stock';
$_['order_new']            = 'New Order';
$_['order_status_change']  = 'Order status change';
$_['return_new']           = 'New Return';
$_['return_status_change'] = 'Return status change';
$_['user_new']             = 'New User';

// Default Subjects
$_['product_out_of_stock_ds'] = '[[store_name]]: Product [product_name] is out of stock';
$_['order_new_ds']            = '[[store_name]]: New order #[order_id]';
$_['order_status_change_ds']  = '[[store_name]]: Status for order #[order_id] was changed';
$_['return_new_ds']           = '[[store_name]]: New return #[return_id]';
$_['return_status_change_ds'] = '[[store_name]]: Status for return #[return_id] was changed';
$_['user_new_ds']             = '[[store_name]]: New user "[customer_email]" is registered';

// Default Messages
$_['product_out_of_stock_dm'] = "Product \"[product_name]\" on \"[store_name]\" is <b>out of stock</b>";
$_['order_new_dm']            = "You have a new order #[order_id] on \"[store_name]\"\nTotal: <b>[order_total] [order_currency]</b>\nPayment method: <b>[order_payment_method]</b>";
$_['order_status_change_dm']  = "Status for order #[order_id] on \"[store_name]\" has been changed to <b>[order_status]</b>";
$_['return_new_dm']           = "You have a <b>new return request</b> #[return_id] on \"[store_name]\"";
$_['return_status_change_dm'] = "Status for return #[return_id] on \"[store_name]\" has been changed to <b>[return_status]</b>";
$_['user_new_dm']             = "New user is registered: <a href=\"mailto:[customer_email]\">[customer_email]</a>";

// Tag groups
$_['gtl_store'] = 'Store';
$_['gtl_order'] = 'Order';
$_['gtl_order_payment'] = 'Payment';
$_['gtl_order_shipping'] = 'Shipping';
$_['gtl_customer'] = 'Customer';
$_['gtl_product'] = 'Product';
$_['gtl_return'] = 'Return';

// Tag labels
$_['tl_store_name'] = 'Name';
$_['tl_store_url']  = 'Url';

$_['tl_order_id']          = 'ID';
$_['tl_order_total']       = 'Total';
$_['tl_order_status']      = 'Status';
$_['tl_order_currency']    = 'Currency';
$_['tl_order_created_at']  = 'Created At';

$_['tl_order_payment_firstname'] = 'Firstname';
$_['tl_order_payment_lastname']  = 'Lastname';
$_['tl_order_payment_company']   = 'Company';
$_['tl_order_payment_postcode']  = 'Postcode';
$_['tl_order_payment_city']      = 'City';
$_['tl_order_payment_zone']      = 'Zone';
$_['tl_order_payment_country']   = 'Country';
$_['tl_order_payment_method']    = 'Method';

$_['tl_order_shipping_firstname'] = 'Firstname';
$_['tl_order_shipping_lastname']  = 'Lastname';
$_['tl_order_shipping_company']   = 'Company';
$_['tl_order_shipping_postcode']  = 'Postcode';
$_['tl_order_shipping_city']      = 'City';
$_['tl_order_shipping_zone']      = 'Zone';
$_['tl_order_shipping_country']   = 'Country';
$_['tl_order_shipping_method']    = 'Method';

$_['tl_return_id']         = 'ID';
$_['tl_return_reason']     = 'Reason';
$_['tl_return_action']     = 'Action';
$_['tl_return_status']     = 'Status';
$_['tl_return_created_at'] = 'Created At';
$_['tl_return_comment']    = 'Comment';

$_['tl_product_id']           = 'ID';
$_['tl_product_name']         = 'Name';
$_['tl_product_model']        = 'Model';
$_['tl_product_quantity']     = 'Quantity';
$_['tl_product_stock_status'] = 'Stock status';
$_['tl_product_manufacturer'] = 'Manufacturer';
$_['tl_product_price']        = 'Price';

$_['tl_customer_id']           = 'ID';
$_['tl_customer_firstname']    = 'Firstname';
$_['tl_customer_lastname']     = 'Lastname';
$_['tl_customer_email']        = 'Email';
$_['tl_customer_telephone']    = 'Telephone status';


/*~~~~~~*/
/* Test */
/*~~~~~~*/

$_['test_default_subject'] = 'Example subject';
$_['test_default_message'] = 'Example message';
$_['test_message_sent_successfully'] = 'Test message sent successfully';
$_['select_channel_for_test_text']   = 'Select a Channel to send test notification';
