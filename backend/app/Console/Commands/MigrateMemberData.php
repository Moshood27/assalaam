<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\QardHasan;
use App\Models\Contribution;
use App\Models\Scheme;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateMemberData extends Command
{
    protected $signature = 'app:migrate-member-data {--dry-run} {--branch=1}';
    protected $description = 'Migrate member loan and contribution records from text data';

    private $schemesMap = [];
    private $branchId;

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $this->branchId = $this->option('branch');

        $data = <<<'EOD'
ABDULAZEEZ KADRI OLADIMEJI	001	 33,500.00 	 33,500.00 	 2,000.00 	 30,000.00 	7/12/2025	7/12/2026	8,000.00	 22,000.00 	 -   	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	08066067163
ADEKUNLE MARUF	022	 292,022.00 	 292,022.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   		 -   	 -   	 -   	08033683138
OYEWUSI KABIRU	011	 72,150.00 	 72,150.00 	 -   	 250,000.00 	7/8/2025	7/8/2026	15,000.00	 235,000.00 	 59,075.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08167089026
OJENIYI KAMORUDEEN	019	 113,575.00 	 113,575.00 	 -   	 -   	0	0	0.00	 -   	 125,500.00 	 531,241.50 	 -   	 -   	 -   	 -   	 -   	 -   	07030056150
ADEGBITE ABDULHAKEEM	053	 20,275.00 	 20,275.00 	 -   	 145,000.00 	9/6/2023	9/6/2024	110,000.00	 35,000.00 	 -   	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	08089171362
ISIAKA KAFILAT	063	 51,800.00 	 51,800.00 	 -   	 210,000.00 	7/12/2024	7/12/2025	160,000.00	 50,000.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08105984457
KAMORUDEEN ADIGUN O.	054	 432,125.00 	 432,125.00 	 -   	 1,390,000.00 	25/6/2025	25/10/2026	700,000.00	 690,000.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08033645361
OYEBANJI AZEEZ	018	 205,900.00 	 205,900.00 	 2,000.00 	 780,000.00 	5/11/2025	5/11/2026	230,000.00	 550,000.00 	 -   	 12,325.40 	 -   	 -   	 -   	 -   	 -   	 -   	08039097430
BASHIRU LUKMAN	010	 243,385.00 	 243,385.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	07031644929
AKANDE RUKAYAT	047	 53,845.00 	 53,845.00 	 -   	 200,000.00 	12/12/2025	12/12/2026	0.00	 200,000.00 	 1,500.00 	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	07034350923
JIMOH MUDASIRU OLAIDE	012	 26,200.00 	 26,200.00 	 -   	 100,000.00 	15/3/2025	14/3/2026	20,000.00	 80,000.00 	 -   	 -   	 1,074,000.00 	6/7/2025	 934,000.00 	 -   	 -   	 -   	07066235443
ABDULLAHI I. AHMAD 	004	 4,281.00 	 4,281.00 	 -   	 -   	0	0	0.00	 -   	 -   	 10,750.00 	 -   	 -   	 -   	 -   	 -   	 -
KHALID RUQAYAT	057	 17,200.00 	 17,200.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	07069000414
ABDULRAHEEM SHAKIRAT	007	 5,799.50 	 5,799.50 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08162286778
OLALEKAN IDAYAT ABIOLA		 5,000.00 	 5,000.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08034842618
POPOOLA SHERIFAT		 30,850.00 	 30,850.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	07036690899
AYEDUN NAJEEMDEEN	009	 202,935.00 	 202,935.00 	 -   	 -   	0	0	0.00	 -   	 -   	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	07058579101
MUSTAPHA MUNIRUDEEN OLALEKAN		 20,650.00 	 20,650.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	07068408494
OLORUNDARE BASHIRAT ABIDEMI		 5,000.00 	 5,000.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	09136269413
ADEPEJU AFEEZ		 4,750.00 	 4,750.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	07039402941
GANIYU AFEES OLALEKAN		 72,375.00 	 72,375.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	07062223489
AJIBADE FAOSIYAH	023	 53,085.00 	 53,085.00 	 -   	 185,000.00 	11/9/2025	11/9/2026	94,000.00	 91,000.00 	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08160246102
YUSUF TAOFEQ	050	 90,700.00 	 90,700.00 	 -   	 330,000.00 	19/7/2024	19/7/2025	314,250.00	 15,750.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	07068861093
DAUDA RASHIDAT	025	 65,325.40 	 65,325.40 	 -   	 210,000.00 	16/7/2025	16/7/2026	116,500.00	 93,500.00 	 11,800.00 	 3,488.40 	 -   	 -   	 -   	 -   	 -   	 -   	07043653431
SALAWU TAOFIK	045	 1,088.00 	 1,088.00 	 -   	 -   	0	0	0.00	 -   	 -   	 2,116.30 	 -   	 -   	 -   	 -   	 -   	 -   	08060633133
ABDULATEEF SAHEED	028	 21,841.00 	 21,841.00 	 -   	 144,000.00 	16/9/2021	16/9/2022	50,000.00	 94,000.00 	 -   	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	08032566098
HAMMED DAUDA SOLA	062	 240.00 	 240.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 2,000.00 	 -   	 -   	 -   	 -   	 -   	08102021540
OYEWALE RAFAT		 18,180.00 	 18,180.00 	 -   	 66,665.00 	26/10/2015	26/10/2016	16,700.00	 49,965.00 	 -   	 -   	 2,000.00 	 -   	 -   	 -   	 -   	 -
ADEYEYE ADEDUNMOLA AFEES	026	 8,300.00 	 8,300.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08137759317
AJAYI JELILAT	008	 1,325.00 	 1,325.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08149123865
SULAIMAN ABDULATEEF	014	 130,968.00 	 130,968.00 	 -   	 608,000.00 	11/5/2022	11/9/2023	330,000.00	 278,000.00 	 -   	 2,325.40 	 -   	 -   	 -   	 -   	 -   	 -   	07033854748
OLADELE NAHEEM	016	 13,571.00 	 13,571.00 	 -   	 200,000.00 	21/12/2014	21/3/2016	103,800.00	 96,200.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -
ALIYU ABDULAKEEM	005	 171,983.00 	 171,983.00 	 -   	 680,000.00 	11/9/2025	11/9/2026	5,000.00	 675,000.00 	 -   	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	07032554129
ADEMOLA SARAFADEEEN	042	 23,363.00 	 23,363.00 	 -   	 -   	0	0	0.00	 -   	 -   	 16,350.00 	 -   	 -   	 -   	 -   	 -   	 -   	08064260245
KAZEEM ABDULLAHI BABATUNDE		 1,000.00 	 1,000.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	07064729632
SULAIMAN ISHAQ	044	 14,468.00 	 14,468.00 	 -   	 -   	0	0	0.00	 -   	 -   	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	08132341558
SAKARIYAU TAIWO	021	 90,450.00 	 90,450.00 	 -   	 -   	0	0	0.00	 -   	 -   	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	07034276237
OUINLOLA LATIFAT	027	 9,902.29 	 9,902.29 	 -   	 50,000.00 	8/11/2021	8/11/2022	33,500.00	 16,500.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08052435224
AZEEZ ABDULJELEEL ABIDEMI	046	 16,350.00 	 16,350.00 	 -   	 -   	0	0	0.00	 -   	 10,000.00 	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	07068351061
BELLO RUKAYAT	048	 103,950.00 	 103,950.00 	 -   	 -   	0	0	0.00	 -   	 -   	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	08060365161
ADEYEMI ABDULHAKEEM	009	 71,621.00 	 71,621.00 	 -   	 315,000.00 	6/7/2020	6/9/2021	167,000.00	 148,000.00 	 -   	 2,325.40 	 -   	 -   	 -   	 -   	 -   	 -   	08060681923
ALABI KABIRAT	015	 32,275.00 	 32,275.00 	 -   	 142,000.00 	13/4/2021	13/4/2022	133,000.00	 9,000.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08134084898
ABUBAKAR MARYAM		 38,850.00 	 38,850.00 	 -   	 100,000.00 	13/2/2026	13/2/2027	0.00	 100,000.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08061201733
RAJI AISHA	024	 142,117.00 	 142,117.00 	 -   	 200,000.00 	13/2/2026	13/2/2027	129,000.00	 71,000.00 	 10,000.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08066916188
DAUDA MUSA OLALERE		 222,500.00 	 222,500.00 	 -   	 600,000.00 	11/9/2025	11/9/2026	350,000.00	 250,000.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08033736033
OWOLABI ABDULAZEEZ	033	 209,635.00 	 209,635.00 	 -   	 -   	0	0	0.00	 -   	 -   	 2,325.40 	 -   	 -   	 -   	 -   	 -   	 -   	08136302727
ADENIJI WASIU		 36,825.00 	 36,825.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -
ADEWUYI KAFAYAT	055	 162,475.00 	 162,475.00 	 -   	 600,000.00 	5/11/2025	5/11/2026	250,000.00	 350,000.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08065247226
HAMZAT SEMIU OLADEJO	070	 620,880.00 	 620,880.00 	 -   	 1,512,920.00 	5/11/2025	5/3/2027	675,000.00	 837,920.00 	 -   	 4,233.00 	 -   	 -   	 -   	 -   	 -   	 -   	08078238180
ADEDOKUN BUKOLA AZEEZ	003	 381,170.00 	 381,170.00 	 2,000.00 	 1,500,000.00 	16/4/2026	16/6/2027	1,500,000.00	 -   	 -   	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	08036513167
SALAMI SURAJUDEEN KOLA	039	 70,000.00 	 70,000.00 	 -   	 200,000.00 	16/4/2026	16/4/2027	200,000.00	 -   	 -   	 3,488.40 	 -   	 -   	 -   	 -   	 -   	 -   	07065866713
ADEWALE KABIR K.	059	 52,829.90 	 52,829.90 	 -   	 200,000.00 	10/1/2023	10/1/2024	101,250.00	 98,750.00 	 -   	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	08061347419
ABDULAZEEZ GANIYAT	056	 109,190.00 	 109,190.00 	 -   	 436,000.00 	16/4/2026	16/4/2027	436,000.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08033453852
OYENIYI SOLIAT YETUNDE		 83,015.00 	 83,015.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08102115726
ABDULGANIYU MONSURAT A.	002	 117,750.00 	 117,750.00 	 -   	 460,000.00 	14/1/2026	14/1/2027	50,000.00	 410,000.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08166818814
AHMED AMINAT ODUNOLA	037	 50,525.00 	 50,525.00 	 -   	 -   	0	0	0.00	 -   	 -   	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	07034278521
LIASU TAOFEEK DAYO	060	 348,615.00 	 348,615.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08061177293
USMAN ISMAIL	06	 420,370.00 	 420,370.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08032978863
MOSHOOD KAMORUDEN INAOLAJI	061	 324,540.00 	 324,540.00 	 -   	 1,250,000.00 	14/5/2025	14/9/2026	725,000.00	 525,000.00 	 -   	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	08035211807
AZEEZ MISBAUDEEN	045	 370,325.00 	 370,325.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	07032545987
ADEROJU MUIDEEN	058	 374,950.00 	 374,950.00 	 -   	 1,250,000.00 	9/10/2025	9/1/2027	600,000.00	 650,000.00 	 -   	 2,325.42 	 -   	 -   	 -   	 -   	 -   	 -   	08034133750
SHITTU AKEEM	0	 27,500.00 	 27,500.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	09032518461
AWODEJI NURUDEEN AYOBAMI	064	 76,749.00 	 76,749.00 	 -   	 -   	0	0	0.00	 -   	 -   	 2,325.49 	 -   	 -   	 -   	 -   	 -   	 -   	07068396053
ADESINA YINUSA	013	 606,675.00 	 606,675.00 	 -   	 2,000,000.00 	13/2/2026	13/2/2027	2,000,000.00	 -   	 -   	 73,614.40 	 -   	 -   	 -   	 -   	 -   	 -   	08068384999
AROGUNDADE WASILAT	041	 129,150.00 	 129,150.00 	 -   	 430,000.00 	16/7/2025	16/7/2026	387,000.00	 43,000.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08060875541
SANNI LATIFAT BUKOLA	038	 90,175.00 	 90,175.00 	 -   	 266,000.00 	16/7/2025	16/7/2026	175,000.00	 91,000.00 	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08167129289
NIFAJI ABDULATEEF	022	 665.29 	 665.29 	 -   	 -   	0	0	0.00	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	 -   	08032150328
OLARIBIGBE FARIDAH	049	 16,515.00 	 16,515.00 	 -   	 -   	0	0	0.00	 -   	 -   	 -   		 -   	 -   	 -   	 -   	 -   	07069477549
EOD;

        $lines = explode("\n", $data);
        $this->info("Found " . count($lines) . " lines of data.");

        if (config('database.default') === 'mysql' && config('database.connections.mysql.host') === 'db') {
            // We are likely in a terminal that can't reach the 'db' host directly
            // but might be able to reach it via 127.0.0.1 if port-forwarded
            $this->warn("Detected 'db' as mysql host. If connection fails, please ensure port forwarding or run within the container.");
        }

        $this->setupSchemes();

        $processedCount = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = explode("\t", $line);
            if (count($parts) < 10) {
                // Try splitting by 3+ spaces if tabs are missing (sometimes tabs are converted to spaces in terminals)
                $parts = preg_split('/\s{3,}/', $line);
            }

            // Trim each part
            $parts = array_map('trim', $parts);

            if (count($parts) < 10) {
                $this->warn("Skipping line due to insufficient parts (" . count($parts) . "): " . $line);
                continue;
            }

            // Expected columns:
            // 0: NAME
            // 1: CARD NO (Ignore)
            // 2: ORDINARY SAVINGS
            // 3: SHARE CAPITAL
            // 4: BUILDING FUND
            // 5: LOAN GRANTED
            // 6: DATE OF LOAN
            // 7: EXPIRY DATE
            // 8: AMOUNT PAID TILL DATE
            // 9: LOAN OUTSTANDING
            // 10: SPECIAL SAVINGS
            // 11: BUSINESS INVESTMENT
            // 12: BUSINESS VENTURE WITH THE SOCIETY(PARTNERSHIP)
            // 13: DATE OF PARTNERSHIP BUSINESS
            // 14: OUTSTANDING BALANCE
            // 15: DAWAH FUND PAYMENT
            // 16: AGM PAYMENT
            // 17: LAND PAYMENT
            // 18: PHONE NO

            $name = trim($parts[0]);
            $phone = (count($parts) >= 19) ? trim($parts[18]) : null;

            if ($phone) {
                // Basic phone validation/cleaning
                $phone = preg_replace('/[^0-9]/', '', $phone);
                if (strlen($phone) < 10) {
                    $phone = null;
                }
            }

            $this->comment("Processing: $name ($phone)");

            if (!$dryRun) {
                DB::beginTransaction();
                try {
                    $user = $this->findOrCreateUser($name, $phone, trim($parts[1] ?? ''));

                    $dateOfLoan = $this->parseDate($parts[6]);

                    // Process Loan
                    $loanGranted = $this->parseAmount($parts[5]);
                    if ($loanGranted > 0) {
                        $this->importLoan($user, $parts);
                    }

                    // Process Contributions
                    $contributions = [
                        'Ordinary Savings' => $this->parseAmount($parts[2]),
                        'Share Capital' => $this->parseAmount($parts[3]),
                        'Building Fund' => $this->parseAmount($parts[4]),
                        'Special Savings' => isset($parts[10]) ? $this->parseAmount($parts[10]) : 0,
                        'Business Investment' => isset($parts[11]) ? $this->parseAmount($parts[11]) : 0,
                        'Partnership' => isset($parts[12]) ? $this->parseAmount($parts[12]) : 0,
                        'Dawah Fund' => isset($parts[15]) ? $this->parseAmount($parts[15]) : 0,
                        'AGM' => isset($parts[16]) ? $this->parseAmount($parts[16]) : 0,
                        'Land Payment' => isset($parts[17]) ? $this->parseAmount($parts[17]) : 0,
                    ];

                    foreach ($contributions as $schemeName => $amount) {
                        if ($amount > 0) {
                            $this->importContribution($user, $schemeName, $amount, $dateOfLoan);
                        }
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("Error processing $name: " . $e->getMessage());
                    continue;
                }
            } else {
                $this->info(" [DRY-RUN] Would process $name ($phone)");
            }

            $processedCount++;
        }

        $this->info("Migration completed. Processed $processedCount members.");
        return self::SUCCESS;
    }

    private function setupSchemes()
    {
        $schemes = [
            'Ordinary Savings', 'Share Capital', 'Building Fund', 'Special Savings',
            'Business Investment', 'Partnership', 'Dawah Fund', 'AGM', 'Land Payment'
        ];

        foreach ($schemes as $name) {
            $scheme = Scheme::firstOrCreate(['name' => $name], ['active' => true, 'min_amount' => 0]);
            $this->schemesMap[$name] = $scheme->id;
        }
    }

    private function findOrCreateUser($name, $phone, $cardNo)
    {
        $name = trim($name);
        $nameParts = explode(' ', $name);
        $surname = array_shift($nameParts);
        $otherNames = implode(' ', $nameParts);

        $user = null;
        if ($phone) {
            $user = User::where('phone', $phone)->first();
        }

        if (!$user && $cardNo && $cardNo !== '-' && $cardNo !== '') {
            $user = User::where('membership_number', $cardNo)->first();
        }

        if (!$user) {
            // Try matching by surname and first part of other names
            $firstName = $nameParts[0] ?? '';
            $user = User::where('surname', $surname)
                ->where('other_names', 'like', $firstName . '%')
                ->first();
        }

        if (!$user) {
            $user = User::where('name', 'like', "%$name%")->first();
        }

        if (!$user) {
            $email = Str::slug($name) . '@attaqwacooposg.com';
            $baseEmail = $email;
            $i = 1;
            while (User::where('email', $email)->exists()) {
                $email = str_replace('@', $i . '@', $baseEmail);
                $i++;
            }

            $user = User::create([
                'name' => $name,
                'surname' => $surname,
                'other_names' => $otherNames,
                'membership_number' => ($cardNo && $cardNo !== '-' && $cardNo !== '') ? $cardNo : null,
                'phone' => $phone ?? '0000000000',
                'email' => $email,
                'password' => bcrypt('password'),
                'branch_id' => $this->branchId,
                'approval_status' => 'approved',
            ]);
            $this->line(" Created new user: $name (Member No: " . ($user->membership_number ?? 'N/A') . ")");
        } else {
            // Update existing user with membership number if missing
            if ($cardNo && $cardNo !== '-' && $cardNo !== '' && (empty($user->membership_number) || $user->membership_number == '')) {
                $user->membership_number = $cardNo;
                $user->save();
            }
            $this->line(" Found existing user: {$user->name}");
        }

        return $user;
    }

    private function importLoan($user, $parts)
    {
        $principal = $this->parseAmount($parts[5]);
        $paid = $this->parseAmount($parts[8]);
        $dateOfLoan = $this->parseDate($parts[6]);
        $expiryDate = $this->parseDate($parts[7]);

        $installments = 12;
        if ($dateOfLoan && $expiryDate) {
            $installments = (int) $dateOfLoan->diffInMonths($expiryDate);
            if ($installments <= 0) $installments = 12;
        }

        $perInstallment = round($principal / $installments, 2);

        $admin = User::where('is_admin', true)->where('id', '!=', $user->id)->first() ?? User::where('is_admin', true)->first();

        $loan = QardHasan::create([
            'user_id' => $user->id,
            'qard_id_string' => 'MGR-' . strtoupper(Str::random(6)),
            'principal_amount' => $principal,
            'total_installments' => $installments,
            'per_installment' => $perInstallment,
            'interval' => 'monthly',
            'paid_amount' => $paid,
            'status' => ($principal - $paid <= 0) ? 'completed' : 'active',
            'created_at' => $dateOfLoan,
            'approved_at' => $dateOfLoan,
            'approved_by' => $admin?->id,
            'received_at' => $dateOfLoan,
        ]);

        if ($paid > 0) {
            \App\Models\QardHasanRepayment::create([
                'qard_hasan_id' => $loan->id,
                'amount' => $paid,
                'reference' => 'MIGRATION-' . Str::random(10),
                'status' => 'success',
                'paid_at' => $dateOfLoan,
            ]);
        }

        $this->line("  Imported loan: ₦" . number_format($principal, 2) . " (Paid: ₦" . number_format($paid, 2) . ")");
    }

    private function importContribution($user, $schemeName, $amount, $date = null)
    {
        Contribution::create([
            'user_id' => $user->id,
            'scheme_id' => $this->schemesMap[$schemeName],
            'amount' => $amount,
            'status' => 'success',
            'reference' => 'MIGRATION-' . Str::random(10),
            'created_at' => $date ?? now(),
        ]);
        $this->line("  Imported $schemeName: ₦" . number_format($amount, 2));
    }

    private function parseAmount($val)
    {
        $val = trim($val);
        if ($val === '-' || $val === '') return 0.0;
        $val = str_replace(',', '', $val);
        return (float) $val;
    }

    private function parseDate($val)
    {
        $val = trim($val);
        if ($val === '0' || empty($val)) return now();

        try {
            return Carbon::createFromFormat('j/n/Y', $val);
        } catch (\Exception $e) {
            return now();
        }
    }
}
