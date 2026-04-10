<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Murabahah Agreement - {{ $order->reference }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; line-height: 1.6; color: #333; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #1a5632; }
        .header p { margin: 5px 0; font-size: 14px; }
        .section { margin-bottom: 20px; }
        .section-title { font-weight: bold; text-decoration: underline; margin-bottom: 10px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { margin-top: 50px; }
        .signatures { margin-top: 30px; display: table; width: 100%; }
        .sig-box { display: table-cell; width: 50%; padding-top: 40px; border-top: 1px solid #000; text-align: center; }
        .sig-spacer { display: table-cell; width: 10%; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Islamic Cooperative Society</h1>
        <p>123 Shariah Way, Lagos, Nigeria</p>
        <p>Email: info@coop.ng | Phone: +234 800 123 4567</p>
        <h2>MURABAHAH (COST-PLUS PROFIT) FINANCING AGREEMENT</h2>
    </div>

    <div class="section">
        <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($order->created_at)->toDateString() }}</p>
        <p><strong>Reference Number:</strong> {{ $order->reference }}</p>
    </div>

    <div class="section">
        <p class="section-title">1. THE PARTIES</p>
        <p>This Agreement is made between:</p>
        <p><strong>The Financier:</strong> Islamic Cooperative Society (hereinafter referred to as "the Cooperative").</p>
        <p><strong>The Member (Purchaser):</strong> {{ $user->name }} (Membership No: {{ $user->membership_number }}).</p>
    </div>

    <div class="section">
        <p class="section-title">2. SUBJECT MATTER (THE ASSET)</p>
        <p>The Cooperative agrees to purchase and subsequently sell to the Member the following asset(s):</p>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Unit Price (₦)</th>
                    <th>Line Total (₦)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" style="text-align: right;">Grand Total:</th>
                    <th>₦ {{ number_format($order->total_amount, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="section">
        <p class="section-title">3. PRICE AND PAYMENT TERMS</p>
        <p>The Cooperative has purchased the asset(s) at a cost of <strong>₦ {{ number_format($order->total_cost, 2) }}</strong> and sells it to the Member at a total price of <strong>₦ {{ number_format($order->total_amount, 2) }}</strong>, which includes a markup (profit) of <strong>₦ {{ number_format($order->total_profit, 2) }}</strong>.</p>
        <p>The Member agrees to pay the total amount in <strong>{{ $order->meta['financing']['months'] ?? 0 }}</strong> monthly installments as per the following schedule:</p>
        <table>
            <thead>
                <tr>
                    <th>Installment</th>
                    <th>Due Date</th>
                    <th>Amount (₦)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->meta['financing']['schedule'] ?? [] as $inst)
                <tr>
                    <td>{{ $inst['installment'] }}</td>
                    <td>{{ $inst['due_date'] }}</td>
                    <td>{{ number_format($inst['amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <p class="section-title">4. DECLARATION</p>
        <p>The Member hereby confirms that they have selected the asset(s) and requested the Cooperative to purchase them for the purpose of this Murabahah transaction. The Member accepts the asset(s) and agrees to the payment terms stipulated above.</p>
    </div>

    <div class="footer">
        <div class="signatures">
            <div class="sig-box">
                <p>For Islamic Cooperative Society</p>
                <p>(Authorized Signatory)</p>
            </div>
            <div class="sig-spacer"></div>
            <div class="sig-box">
                <p>{{ $user->name }}</p>
                <p>(The Member)</p>
            </div>
        </div>
    </div>
</body>
</html>
