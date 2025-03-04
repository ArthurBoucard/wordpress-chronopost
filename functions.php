<?php

function generate_chronopost_label_for_seller($order_id) {
    error_log("🚀 START: Processing Chronopost Label for Order #$order_id");

    $order = wc_get_order($order_id);

    // Retrieve shipping details
    $recipient_name = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
    $recipient_address = $order->get_shipping_address_1();
    $recipient_zip = $order->get_shipping_postcode();
    $recipient_city = $order->get_shipping_city();
    $recipient_phone = $order->get_billing_phone();
    $recipient_email = $order->get_billing_email();
    $recipient_country = $order->get_shipping_country();

    // Retrieve sender details (this could be your shop's address or another fixed address)
    $shipper_name = "SOCIETE XXXX";  // Change this to your shop's name
    $shipper_address1 = "1 RUE DES FLEURS";  // Change this to your shop's address
    $shipper_zip = "68000";  // Change this to your shop's ZIP code
    $shipper_city = "COLMAR";  // Change this to your shop's city
    $shipper_country = "FR";  // Country code for France

    error_log("✅ Order details retrieved for #$order_id - Shipping to: $recipient_name, $recipient_address, $recipient_zip, $recipient_city");

    // Calculate total weight and dimensions (for multiple parcels if needed)
    $total_weight = 0;
    $total_length = 0;
    $total_width = 0;
    $total_height = 0;

    $parcel_details = [];
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $product_weight = floatval($product->get_weight());
        $quantity = intval($item->get_quantity());
        $product_length = floatval($product->get_length());  // Assuming your products have dimensions set
        $product_width = floatval($product->get_width());  // Assuming your products have dimensions set
        $product_height = floatval($product->get_height());  // Assuming your products have dimensions set

        // Add weight and dimensions
        $total_weight += $product_weight * $quantity;
        $total_length += $product_length * $quantity;
        $total_width += $product_width * $quantity;
        $total_height += $product_height * $quantity;

        // You could also store each parcel's details separately if you have more complex parcel info
        $parcel_details[] = [
            'weight' => $product_weight,
            'length' => $product_length,
            'width' => $product_width,
            'height' => $product_height,
            'quantity' => $quantity,
        ];
    }

    // Example: Use the first product's dimensions for simplicity
    error_log("✅ Calculated total weight: $total_weight kg, Dimensions: $total_length x $total_width x $total_height cm");

    // Prepare the SOAP request XML
    $xml_request = '<?xml version="1.0" encoding="UTF-8"?>
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cxf="http://cxf.shipping.soap.chronopost.fr/">
        <soapenv:Header/>
        <soapenv:Body>
            <cxf:shippingMultiParcelV4>
                <headerValue>
					<accountNumber>19869502</accountNumber>
					<idEmit>CHRFR</idEmit>
					<identWebPro></identWebPro>
					<subAccount></subAccount>
                </headerValue>

                <shipperValue>
                    <shipperAdress1>' . $shipper_address1 . '</shipperAdress1>
                    <shipperCity>' . $shipper_city . '</shipperCity>
                    <shipperZipCode>' . $shipper_zip . '</shipperZipCode>
                    <shipperCountry>' . $shipper_country . '</shipperCountry>
                    <shipperName>' . $shipper_name . '</shipperName>
                    <shipperPhone>' . $order->get_billing_phone() . '</shipperPhone>
                    <shipperEmail>' . $order->get_billing_email() . '</shipperEmail>
                </shipperValue>

                <customerValue>
                    <customerAdress1>' . $recipient_address . '</customerAdress1>
                    <customerCity>' . $recipient_city . '</customerCity>
                    <customerZipCode>' . $recipient_zip . '</customerZipCode>
                    <customerCountry>' . $recipient_country . '</customerCountry>
                    <customerName>' . $recipient_name . '</customerName>
                    <customerPhone>' . $recipient_phone . '</customerPhone>
                    <customerEmail>' . $recipient_email . '</customerEmail>
                </customerValue>

                <recipientValue>
                    <recipientAdress1>' . $recipient_address . '</recipientAdress1>
                    <recipientCity>' . $recipient_city . '</recipientCity>
                    <recipientZipCode>' . $recipient_zip . '</recipientZipCode>
                    <recipientCountry>' . $recipient_country . '</recipientCountry>
                    <recipientName>' . $recipient_name . '</recipientName>
                    <recipientPhone>' . $recipient_phone . '</recipientPhone>
                    <recipientEmail>' . $recipient_email . '</recipientEmail>
                </recipientValue>

                <refValue>
                    <recipientRef>COMMANDE NUM ' . $order_id . '</recipientRef>
                    <shipperRef>REF EXPE</shipperRef>
                    <idRelais>1786S</idRelais>
                </refValue>

                <skybillValue>
                    <weight>' . $total_weight . '</weight>
                    <height>' . $total_height . '</height>
                    <length>' . $total_length . '</length>
                    <width>' . $total_width . '</width>
                    <productCode>5E</productCode>
                    <service>0</service>
                </skybillValue>

                <skybillParamsValue>
                    <duplicata>N</duplicata>
                    <mode>PPR</mode>
                    <withReservation>0</withReservation>
                </skybillParamsValue>

                <modeRetour>2</modeRetour>
                <numberOfParcel>' . count($parcel_details) . '</numberOfParcel>
                <version>2.0</version>
                <multiParcel>N</multiParcel>
            </cxf:shippingMultiParcelV4>
        </soapenv:Body>
    </soapenv:Envelope>';

    // Send SOAP request
    $api_url = "https://ws.chronopost.fr/shipping-cxf/ShippingServiceWS?wsdl";
    $response = wp_remote_post($api_url, array(
        'body' => $xml_request,
        'headers' => array(
            'Content-Type' => 'application/soap+xml;charset=UTF-8',
        ),
    ));

    // Handle the response
    if (is_wp_error($response)) {
        error_log("❌ Chronopost API Error: " . $response->get_error_message());
        return;
    }

    $response_data = wp_remote_retrieve_body($response);
    error_log("📩 API Response: " . print_r($response_data, true));

    // Process the response to get the label
    // (You'll need to extract the label or PDF link from the response)

    error_log("🎉 DONE: Chronopost Label Process Completed for Order #$order_id");
}
add_action('woocommerce_order_status_processing', 'generate_chronopost_label_for_seller');
