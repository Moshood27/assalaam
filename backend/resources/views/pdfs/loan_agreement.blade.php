<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Agreement - {{ $loan->qard_id_string }}</title>
    <style>
        @page {
            margin: 0.5in;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 14pt;
            margin: 0;
            font-weight: bold;
        }
        .header h2 {
            font-size: 12pt;
            margin: 2px 0;
            font-weight: bold;
        }
        .header p {
            margin: 1px 0;
            font-size: 8pt;
        }
        .motto {
            font-style: italic;
            font-weight: bold;
            margin-top: 5px;
        }
        .title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            margin: 15px 0;
            font-size: 12pt;
            text-transform: uppercase;
        }
        .underline {
            border-bottom: 1px solid #000;
            display: inline-block;
            padding: 0 5px;
            min-width: 50px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 4px;
            text-align: left;
            font-size: 8pt;
        }
        .signature-row {
            margin-top: 30px;
            width: 100%;
        }
        .signature-box {
            display: inline-block;
            width: 32%;
            text-align: center;
            vertical-align: top;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 2px;
            font-weight: bold;
            font-size: 8pt;
        }
        .page-break {
            page-break-after: always;
        }
        .section-title {
            font-weight: bold;
            margin-top: 10px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    @php
        $eligibility = $user->savingsSharesEligibility();
    @endphp

    <!-- PAGE 1: LOAN BOND -->
    <div class="header">
        <h1>CENTRE FOR ISLAMIC CALLERS OF NIGERIA (CICN)</h1>
        <h2>AT-TAQWA ISLAMIC COOPERATIVE UNIT</h2>
        <p>ADDRESS: C/O AT-TAQWA BASIC ACADEMY, SUMONU BROTHERS' AVENUE, OFF BARUWA STREET, WOLEOLA ESTATE, OSOGBO.</p>
        <p class="motto">Motto: “Helping One Another in Righteousness and Piety”</p>
    </div>

    <div class="title">LOAN BOND</div>

    <p>
        I, <span class="underline" style="min-width: 300px;">{{ $user->full_name }}</span> with membership card No <span class="underline" style="min-width: 100px;">{{ $user->membership_number }}</span> of <span class="underline" style="min-width: 150px;">{{ $user->branch?->name ?? '____________________' }}</span>
    </p>

    <p>
        today <span class="underline" style="min-width: 150px;">{{ now()->format('jS F, Y') }}</span> apply for a loan of <span class="underline" style="min-width: 200px;">{{ number_format($loan->principal_amount, 2) }}</span> Naira
    </p>

    <p>
        (N <span class="underline" style="min-width: 100px;">{{ number_format($loan->principal_amount, 2) }}</span>    K <span class="underline" style="min-width: 30px;">00</span>)
    </p>

    <div class="section-title">PURPOSE OF THIS REQUEST</div>
    <p>1. ________________________________________________________________________________________________________________________</p>
    <p>2. ________________________________________________________________________________________________________________________</p>

    <p>
        I also submit below the name of the following members as my sureties and I, oblige to the bye-law and resolution of the society to refund the
        Loan <span class="underline" style="min-width: 50px;">{{ $loan->total_installments }}</span> month after the granting date.
    </p>

    <table>
        <tr>
            <th style="width: 50%;">Surety's Name Sign & Date</th>
            <th style="width: 50%;">Surety's Name Sign & Date</th>
        </tr>
        <tr>
            <td style="height: 50px;">
                @if($loan->guarantors->count() > 0)
                    {{ $loan->guarantors[0]->full_name }} ({{ $loan->guarantors[0]->membership_number }})<br>
                    Date: {{ $loan->guarantors[0]->pivot->responded_at ? \Carbon\Carbon::parse($loan->guarantors[0]->pivot->responded_at)->format('d/m/Y') : '________________' }}
                @endif
            </td>
            <td style="height: 50px;">
                @if($loan->guarantors->count() > 1)
                    {{ $loan->guarantors[1]->full_name }} ({{ $loan->guarantors[1]->membership_number }})<br>
                    Date: {{ $loan->guarantors[1]->pivot->responded_at ? \Carbon\Carbon::parse($loan->guarantors[1]->pivot->responded_at)->format('d/m/Y') : '________________' }}
                @endif
            </td>
        </tr>
    </table>

    <p>
        Applicants' Shares <span class="underline" style="min-width: 100px;">{{ number_format($eligibility['shares'] ?? 0, 2) }}</span>
        Savings <span class="underline" style="min-width: 100px;">{{ number_format($eligibility['savings'] ?? 0, 2) }}</span>
        Total <span class="underline" style="min-width: 100px;">{{ number_format($eligibility['base'] ?? 0, 2) }}</span>
    </p>

    <p>Any arrears' unpaid <span class="underline" style="min-width: 400px;">________________________________________________________________________________</span></p>

    <p>REMARKS BY FIN.SECRETARY</p>
    <p>Remark Chairman LMC:- ________________________________________________________________________________</p>
    <p>Remark Chairman FFC:- ________________________________________________________________________________</p>

    <div class="signature-row" style="margin-top: 40px;">
        <div class="signature-box">
            <div class="signature-line">Chief Fin. Sec's Sign & Date</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">President Sign & Date</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Chief Treasurer's Sign & Date</div>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- PAGE 2: COLLATERAL SECURITY FORM -->
    <div class="header">
        <p style="font-weight: bold; font-size: 10pt;">IN THE NAME OF ALLAH THE BENEFICENT THE MERCIFUL</p>
        <h1>CENTRE FOR ISLAMIC CALLERS OF NIGERIA (CICN)</h1>
        <p>P. O. BOX 1440, OSOGBO</p>
        <h2>AT-TAQWA ISLAMIC COOPERATIVE UNIT</h2>
        <p>ADDRESS: C/O AT-TAQWA BASIC ACADEMY, SUMONU BROTHERS' AVENUE, OFF BARUWA STREET, WOLEOLA ESTATE, OSOGBO.</p>
        <p class="motto">Motto: “Helping One Another in Righteousness and Piety”</p>
    </div>

    <div class="title">COLLATERAL SECURITY FORM</div>

    <div class="section-title">SECTION A:</div>
    <p>Borrower's Name <span class="underline" style="min-width: 400px;">{{ $user->full_name }}</span></p>
    <p>Amount Requested: <span class="underline" style="min-width: 200px;">₦{{ number_format($loan->principal_amount, 2) }}</span></p>
    <p>Amount granted: <span class="underline" style="min-width: 200px;">₦{{ number_format($loan->principal_amount, 2) }}</span></p>
    <p>Date: <span class="underline" style="min-width: 200px;">{{ $loan->approved_at ? $loan->approved_at->format('d/m/Y') : now()->format('d/m/Y') }}</span></p>

    <p style="font-size: 8pt; margin-top: 10px;">
        In accordance with section 19 (b) of the bye-law of the above named society, with state that “the borrower should have collateral for security of the loan given to him/her.”
    </p>

    <div class="section-title">SECTION B: In line with the above provision of the Bye-Law, therefore:</div>
    <p>I Mr/Mrs. <span class="underline" style="min-width: 300px;">{{ $user->full_name }}</span> I hereby declare the following:</p>
    <ol style="font-size: 8.5pt;">
        <li>That I am a member of the above named cooperative society.</li>
        <li>That I am prepared to submit my personal belongings stated here under as collateral for the above loan granted in case of my default.</li>
        <li>That I have not submitted this same property(ies) as collateral else where for the purpose of any loan issues.</li>
        <li>That the property so submitted could be sold or mention as may be appropriate to recover the value of loan granted.</li>
        <li>That the following persons stood as my sureties/Guarantors.</li>
    </ol>

    <p>(1) <span class="underline" style="min-width: 200px;">{{ $loan->guarantors->count() > 0 ? $loan->guarantors[0]->full_name : '_________________________' }}</span> Signature <span class="underline" style="min-width: 80px;"></span> Date <span class="underline" style="min-width: 80px;"></span></p>
    <p>(2) <span class="underline" style="min-width: 200px;">{{ $loan->guarantors->count() > 1 ? $loan->guarantors[1]->full_name : '_________________________' }}</span> Signature <span class="underline" style="min-width: 80px;"></span> Date <span class="underline" style="min-width: 80px;"></span></p>

    <div class="section-title">SECTION C</div>
    <p>The items/receipts of the properties submitted by me as collateral are here under itemized.</p>

    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Name of Property</th>
                <th style="width: 30%;">Date Purchased</th>
                <th style="width: 30%;">Present Condition</th>
            </tr>
        </thead>
        <tbody>
            <tr><td style="height: 25px;">(1)</td><td></td><td></td></tr>
            <tr><td style="height: 25px;">(2)</td><td></td><td></td></tr>
            <tr><td style="height: 25px;">(3)</td><td></td><td></td></tr>
            <tr><td style="height: 25px;">(4)</td><td></td><td></td></tr>
        </tbody>
    </table>

    <div style="width: 100%; margin-top: 20px;">
        <div style="display: inline-block; width: 45%;">
            <div class="signature-line" style="margin-top: 30px;">Signed By The Borrower</div>
            <p>Date: <span class="underline" style="min-width: 100px;">{{ now()->format('d/m/Y') }}</span></p>
        </div>
        <div style="display: inline-block; width: 45%; float: right;">
            <p>Witness Name: <span class="underline" style="min-width: 150px;"></span></p>
            <div class="signature-line" style="margin-top: 30px;">Sign & Date</div>
        </div>
    </div>
    <p style="font-size: 7pt; font-style: italic; margin-top: 10px;">(these shall be embossed with N50 postal stamp by the borrower, as endorsed by the witness)</p>

    <div class="section-title">SECTION D</div>
    <p>Remarks: The above submitted items/receipts have been physically inspected found to be in order by the Executive Committee.</p>

    <div class="signature-row" style="margin-top: 30px;">
        <div class="signature-box">
            <div class="signature-line">Financial Secretary</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Chairman</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Treasurer</div>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- PAGE 3: SURETY'S INFORMATION -->
    <div class="header">
        <h1>CENTRE FOR ISLAMIC CALLERS OF NIGERIA (CICN)</h1>
        <h2>AT-TAQWA ISLAMIC COOPERATIVE UNIT</h2>
        <p>ADDRESS: C/O AT-TAQWA NURSERY AND PRIMARY SCHOOL, SUMONU BROTHERS AVENUE, OFF BARUWA STREET, WOLEOLA ESTATE, OSOGBO, OSUN STATE.</p>
        <p class="motto">Motto: “Helping One Another in Righteousness and Piety”</p>
    </div>

    <div class="title">SURETY'S INFORMATION</div>

    @foreach($loan->guarantors as $index => $guarantor)
        @php
            $gEligibility = $guarantor->savingsSharesEligibility();
        @endphp
        <div style="font-weight: bold; margin-top: 15px;">
            {{ $index + 1 }}{{ $index == 0 ? 'ST' : ($index == 1 ? 'ND' : 'RD') }} SURETY
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ARM <span class="underline" style="min-width: 80px;"></span>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; CARD NO <span class="underline" style="min-width: 80px;">{{ $guarantor->membership_number }}</span>
        </div>
        <p>NAME: <span class="underline" style="min-width: 300px;">{{ $guarantor->full_name }}</span></p>
        <p>SHARES: <span class="underline" style="min-width: 200px;">₦{{ number_format($gEligibility['shares'] ?? 0, 2) }}</span></p>
        <p>SAVINGS: <span class="underline" style="min-width: 200px;">₦{{ number_format($gEligibility['savings'] ?? 0, 2) }}</span></p>

        <div style="font-weight: bold; font-size: 8pt; margin-top: 5px;">LOAN STATUS</div>
        <table>
            <thead>
                <tr>
                    <th>DATE GRANTED</th>
                    <th>AMOUNT GRANTED</th>
                    <th>AMOUNT PAID</th>
                    <th>EXPECTED TO HAVE PAID</th>
                    <th>DEFAULTED AMOUNT</th>
                    <th>LOAN BALANCE</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    @php $gLoan = $guarantor->qardHasans()->whereIn('status', ['active', 'defaulted'])->first(); @endphp
                    @if($gLoan)
                        <td>{{ $gLoan->approved_at ? $gLoan->approved_at->format('d/m/Y') : ($gLoan->received_at ? $gLoan->received_at->format('d/m/Y') : '') }}</td>
                        <td>{{ number_format($gLoan->principal_amount, 2) }}</td>
                        <td>{{ number_format($gLoan->paid_amount, 2) }}</td>
                        <td>{{ number_format($gLoan->getExpectedAmountToDate(), 2) }}</td>
                        <td>{{ number_format($gLoan->getOverdueAmount(), 2) }}</td>
                        <td>{{ number_format($gLoan->remaining_principal, 2) }}</td>
                    @else
                        <td style="height: 20px;"></td><td></td><td></td><td></td><td></td><td></td>
                    @endif
                </tr>
            </tbody>
        </table>
        @if(!$loop->last) <hr style="border: 0.5px dashed #ccc;"> @endif
    @endforeach

    <div style="margin-top: 20px;">
        <p>CHAIRMAN COMMENT ________________________________________________________________________________</p>
        <p>FIN. SEC. COMMENT ________________________________________________________________________________</p>
    </div>

    <div style="border: 1px solid #000; padding: 10px; margin-top: 15px;">
        <p>APPLICANT: ACCOUNT NAME <span class="underline" style="min-width: 150px;">{{ $user->account_name }}</span>   BANK NAME <span class="underline" style="min-width: 150px;">{{ $user->bank_name }}</span></p>
        <p>ACCOUNT NUMBER <span class="underline" style="min-width: 200px;">{{ $user->account_number }}</span></p>
        <p>SAVING/CURRENT ACCOUNT <span class="underline" style="min-width: 150px;"></span></p>
    </div>

    <div style="margin-top: 40px; width: 200px;">
        <div class="signature-line">Chairman</div>
    </div>
</body>
</html>
