<?php

class paypal extends \CI_Controller
{

  
    //sandbox
    public $client_id = "AT8zrP4-BKxseYdexPY0FoW19EZevYU4RBWdQomKanSUycjLtmB4m4QQrJviSd6f9NSnzdPZ8GviDPqr";
    public $client_secret = "EDVbQeQQSgmQBzDdASWCwxNl3ZfgAZoxY0OrqI7RO3ifwhNVFx-R3niM0ILAnW6xOJODFdJlNRbpbqij";


    function __construct()
    {
        parent::__construct();
    }

    public function createPlan()
    {

        $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential($this->client_id, $this->client_secret)
        );

        // Create a new billing plan
        $plan = new \PayPal\Api\Plan();
        $plan->setName('Plan Name: Complete Instagram Plan')
            ->setDescription('Plan Description: Complete Instagram Plan.')
            ->setType('infinite');

        // Set billing plan definitions
        $paymentDefinition = new \PayPal\Api\PaymentDefinition();
        $paymentDefinition->setName('Payment Definition Name: Angie Payments')
            ->setType('REGULAR')
            ->setFrequency('Month')
            ->setFrequencyInterval('1')
            ->setAmount(new \PayPal\Api\Currency(array('value' => 100, 'currency' => 'USD')));


        // Set merchant preferences
        $merchantPreferences = new \PayPal\Api\MerchantPreferences();
        $merchantPreferences->setReturnUrl(base_url('paypal/cofirmAgreement'))
            ->setCancelUrl(base_url('paypal/cancelUrl'))
            ->setAutoBillAmount('yes')
            ->setInitialFailAmountAction('CONTINUE')
            ->setMaxFailAttempts('0')
            ->setSetupFee(new \PayPal\Api\Currency(array('value' => 50, 'currency' => 'USD')));

        $plan->setPaymentDefinitions(array($paymentDefinition));
        $plan->setMerchantPreferences($merchantPreferences);

        try {
            $output = $plan->create($apiContext);
            echo 'PlanId:' . $output->getId();

            echo '<br/>Going to activate it:<br/>';

            $patch = new \PayPal\Api\Patch();
            $patch->setOp('replace')
                ->setPath('/')
                ->setValue(new \PayPal\Common\PayPalModel('{"state": "ACTIVE"}'));

            $patchRequest = new \PayPal\Api\PatchRequest();
            $patchRequest->addPatch($patch);

            $resActivate = $output->update($patchRequest, $apiContext);

            $planList2 = \PayPal\Api\Plan::all(array('page_size' => 10, 'status' => 'ACTIVE'), $apiContext);

            echo '<pre>';
            print_r($planList2);


        } catch (Exception $ex) {
            echo '<pre>';
            print_r($ex);
            exit(1);
        }

    }

    public function subscribe()
    {
        $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential($this->client_id, $this->client_secret)
        );

        /**
         * Paypal workaround
         * bill the user in the next month. For the current month we use the setup fee.
         */
        $now = new \DateTime("now");
        $now->modify("+1 month");
        $agreement = new \PayPal\Api\Agreement();
        $agreement->setName('Agreement Name: Angie Subscription for Complete followers plan')
            ->setDescription('Agreement Description: Angie Subscription for Complete followers plan')
            ->setStartDate($now->format(DateTime::ISO8601));


        // Add Plan ID
        $plan = new \PayPal\Api\Plan();
        $plan->setId("P-5L314948BH724424STPMQFKI");
        $agreement->setPlan($plan);

        // Add Payer
        $payer = new \PayPal\Api\Payer();
        $payer->setPaymentMethod('paypal');
        $agreement->setPayer($payer);

        // ### Create Agreement
        try {
            // Please note that as the agreement has not yet activated, we wont be receiving the ID just yet.
            $agreement = $agreement->create($apiContext);
            header('Location:  '.  $agreement->getApprovalLink());
            exit();
        } catch (Exception $ex) {
            echo '<pre>';
            print_r($ex);
        }
    }

    public function cofirmAgreement(){
        echo '<pre>';
        $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential($this->client_id, $this->client_secret)
        );


        $token = $_GET['token'];
        $agreement = new \PayPal\Api\Agreement();
        try {
            // ## Execute Agreement
            // Execute the agreement by passing in the token
            $agreement->execute($token, $apiContext);
        } catch (Exception $ex) {
            print_r($ex);

        }

        try {
            $agreement = \PayPal\Api\Agreement::get($agreement->getId(), $apiContext);
            print_r($agreement);
        } catch (Exception $ex) {
            print_r($ex);
        }

    }

}

?>
