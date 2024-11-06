<?php

class ZarinPal
{

    /**
     * مرچنت ایدی زرین پال.
     * @var string
     */
    public $fullBaseUrl = "https://example.com/";// آدرس کامل وبسایت. برای مثال ->
    private $MerchantId = 'MerchantID'; // مرچنت آیدی (باید از خود زرین پال بگیرینش)
    private $CallBackUrl = "https://example.com/test.php";// صفحه ای که کاربر بعد از پرداخت به صورت پیشفرض بهش انتقال پیدا میکنه. برای مثال ->
    private $Email = "";
    /** Optional
     * آدرس ایمیل کاربر(اختیاری) درصورت عدم وجود ایمیل برای کاربر خالی باشد
     * must is null or Email format
     */
    private $Amount = "100"; //هزینه ای که باید پرداخت شود(به تومان).


    /**
     * __cunstruct
     *
     * @merchantId : zarinpal merchantId(string)
     */
    public function __cunstruct($merchantId = '')
    {
        // اگه مرچنت آیدی نیاز بود تغییر کنه موقع صدا زدن کلاس بهش پاس بدید بجای مقدار پیش فرض قرار میده.
        if (!empty($merchantId))
            $this->MerchantId = $merchantId;

    }


    /**
     * @throws SoapFault
     *
     *
     */
    public function startPayment($description, $callbackURL = '', $amount = '', $email = '')
    {
        // اگه هر مقداری از مقدار های زیر نیاز بود تغییر کنه موقع صدا زدن این تابع بهش پاس بدید. خودش بجای مقدار پیش فرض قرار میده.
        if (!empty($callbackURL))
            $this->CallBackUrl = $callbackURL;

        if (!empty($amount))
            $this->Amount = $amount;

        if (!empty($email))
            $this->Email = $email;

        $_SESSION['amount'] = $this->Amount;
        $_SESSION['payment_code'] = rand(0, 9999) . time();//اینجا کد پیگیری ساخته میشه و توی سشن ریخته میشه، شما میتونید توی دیتابیس ذخیره کنید همه این مقدار ها رو.
        //zarinpal
        $client = new SoapClient('https://www.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);
        $result = $client->PaymentRequest(
            [
                'MerchantID' => $this->MerchantId,
                'Amount' => $this->Amount,
                'Email' => $this->Email,
                'Description' => $description,
                'CallbackURL' => $this->CallBackUrl,
            ]
        );
        if ($result->Status == 100) {
            Header('Location: https://www.zarinpal.com/pg/StartPay/' . $result->Authority);//انتقال به صفحه پرداخت
        } else {
            echo 'ERR: ' . $result->Status;
        }
    }

    /**
     * @throws SoapFault
     */
    // این دوتا مقدار به صورت $_GET ارسال میشه به صفحه ای که برای CallbackUrl ثبت کردید. برای بررسی نتیجه برای این تابع بفرستید
    public function verifyPayment($Status, $Authority)
    {
        if ($Status == "OK") {
            //zarinpal
            // بررسی پرداخت شدن یا نشدن.
            $client = new SoapClient('https://www.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);
            $result = $client->PaymentVerification(
                [
                    'MerchantID' => $this->MerchantId,
                    'Authority' => $Authority,
                    'Amount' => $_SESSION['amount'],
                ]
            );
            if ($result->Status == 100) {
                $ref_id = $result->RefID;
                $payment_code = $_SESSION['payment_code'];
                showMessage($payment_code . 'پرداخت با موفقیت انجام شد. کد پیگیری: ', 'تبریک!');
                exit;
            } else {
                showMessage('پرداخت انجام نشد', 'خطا!');
                exit;
            }
        } else {
            showMessage('پرداخت انجام نشد', 'خطا!');
            exit;
        }
    }


}