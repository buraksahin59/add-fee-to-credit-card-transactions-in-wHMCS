<?php
use WHMCS\Database\Capsule;

add_hook('ClientAreaPageViewInvoice', 1, function($vars) {
    $invoiceId = $vars['invoiceid'];
    $clientId = $vars['userid'];
    $paymentmodule = $vars['paymentmodule'];
    $comission = 5;
    $comissionText = "Kredi Kartı (PayTR) ile Ödeme Kom. (%{$comission})";
    $actualLink = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";


    // Get invoice details
    $invoice = Capsule::table('tblinvoices')->find($invoiceId);

    // Check if the new gateway is 'CreditCard'
    if ($paymentmodule == 'paytr') {
        // Check if 'Kredi Kartı Komisyonu' line item already exists
        $existingItem = Capsule::table('tblinvoiceitems')
            ->where('invoiceid', $invoiceId)
            ->where('description', $comissionText)
            ->first();

        // If 'Kredi Kartı Komisyonu' line item doesn't exist, add it
        if (!$existingItem) {
            $invoiceSubtotal = $invoice->subtotal;
            // $invoiceTotal = $invoice->total;
            $commissionAmount = $invoiceSubtotal*($comission/100); // %5 komisyon oranı
            $commissionItem = [
                'invoiceid' => $invoiceId,
                'userid' => $clientId,
                'type' => 'Item',
                'description' => $comissionText,
                'amount' => $commissionAmount,
                'taxed' => 1,
            ];
            Capsule::table('tblinvoiceitems')->insert($commissionItem);

            // Get invoice details
            $invoices = Capsule::table('tblinvoiceitems')->where('invoiceid', $invoiceId)->get();
            // print_r($invoices);
            
            // Set 0 amounts
            $newSubtotal = 0;
            $newTotal = 0;

            // Sum to subtotals, taxes and total amounts
            foreach ( $invoices as $invoice_item ) {
                $newSubtotal += $invoice_item->amount;
                // if ( $invoice_item->taxed == 1 ) {
                    $newTax += $invoice_item->amount*0.2;
                // }
                $newTotal = $newSubtotal + $newTax;
            }

            // Update invoice total
            Capsule::table('tblinvoices')
                ->where('id', $invoiceId)
                ->update(['subtotal' => $newSubtotal, 'total' => $newTotal, 'tax' => $newTax]);

            // Refresh Page
            header("location: {$actualLink}");
        }
    } else {
        // Check if 'Kredi Kartı Komisyonu' line item already exists
        $existingItem = Capsule::table('tblinvoiceitems')
            ->where('invoiceid', $invoiceId)
            ->where('description', $comissionText)
            ->first();

        // If 'Kredi Kartı Komisyonu' line item exist, delete it
        if ($existingItem) {
            $invoiceSubtotal = $invoice->subtotal;
            // $invoiceTotal = $invoice->total;
            $commissionAmount = $invoiceSubtotal*($comission/100); // %5 komisyon oranı

            // Payment method is not 'CreditCard', check if 'Kredi Kartı Komisyonu' line item exists and delete it
            Capsule::table('tblinvoiceitems')
                ->where('invoiceid', $invoiceId)
                ->where('description', $comissionText)
                ->delete();

            // Get invoice details
            $invoices = Capsule::table('tblinvoiceitems')->where('invoiceid', $invoiceId)->get();

            // Set 0 amounts
            $newSubtotal = 0;
            $newTotal = 0;

            // Sum to subtotals, taxes and total amounts
            foreach ( $invoices as $invoice_item ) {
                $newSubtotal += $invoice_item->amount;
                if ( $invoice_item->taxed == 1 ) {
                    $newTax += $invoice_item->amount*0.2;
                }
                $newTotal = $newSubtotal + $newTax;
            }

            // Update invoice total
            Capsule::table('tblinvoices')
                ->where('id', $invoiceId)
                ->update(['subtotal' => $newSubtotal, 'total' => $newTotal, 'tax' => $newTax]);

            // Refresh Page
            header("location: {$actualLink}");
        }

    }
});
