<?php

define("RABBITMQ_SERVER", "127.0.0.1");


try {



    $connectionRequired = true;
    $connectionAttempts = 0;

    // This outer loop enabled the script to try to reconnect in case of failure
    while ($connectionRequired && $connectionAttempts < 30) {
        $connectionAttempts++;
        $foundQueue = false;
        try {



            //Establish connection AMQP
            $connection = new AMQPConnection();
            $connection->setHost(RABBITMQ_SERVER);
            $connection->setLogin('test');
            $connection->setPassword('test');

            $connection->setVhost("/");
            $connection->connect();


            //Create and declare channel
            $channel = new AMQPChannel($connection);

            //AMQPC Exchange is the publishing mechanism
            $exchange = new AMQPExchange($channel);

            /* * CALLBACK FUNCTION  * */


            $callback_func = function(AMQPEnvelope $message, AMQPQueue $q) use (&$max_consume) {

                $start = microtime(true);

                $deliveryTag = $message->getDeliveryTag();


                $messageBody = $message->getBody();

                //$balanceDataArr = json_decode($balanceDataRaw,true);

                //$customerID = intval($balanceDataArr["c"]);
                //$amount = floatval($balanceDataArr["a"]);


                $procedureStatus = "unknown";


                //if(deductCustomerBalance($customerID,$amount)){
                 //   $q->ack($deliveryTag);
                 //   $procedureStatus = "successfully processed with ACK";
                //}else{
//
                //    $q->reject($deliveryTag);
                //    $procedureStatus = "rejected";
               // }


                $q->ack($deliveryTag);


                // finalize

                $time_end = microtime(true);
                $total_time = ($time_end - $start) * 1000;
                $total_time = round($total_time,2);
                $total_time = $total_time . "ms";

                if($deliveryTag % 100 == 0)
                    echo "-> [$deliveryTag] Got Event: result [$messageBody],  Execution time: " . $total_time . PHP_EOL;


            };



            /* * CALLBACK FUNCTION  * */

            try{
                $exchange_name = 'TAP.Events';
                $routing_key = '*.*.*.*.*';

                $queue = new AMQPQueue($channel);

                $queue->setName($exchange_name);
                $queue->setFlags(AMQP_DURABLE);
                $queue->declareQueue();

                //$channel->exchange_declare($exchange_name, AMQPExchangeType::DIRECT, false, true, false);


                $queue->bind($exchange_name,$routing_key);
                echo ' [*] Waiting for messages. To exit press CTRL+C ', PHP_EOL;
                $queue->consume($callback_func, AMQP_NOPARAM,"php_script_consumer");
            }catch(AMQPQueueException $ex){
                print_r($ex);
            }catch(Exception $ex){
                print_r($ex);
            }
            echo 'Close connection...', PHP_EOL;
            $queue->cancel();
            $connection->disconnect();

        } catch(exception $e) {
            // Failed to get connection.
            // Best practice is to catch the specific exceptions and handle accordingly.
            // Either handle the message (and exit) or retry

            if ($connectionAttempts<5) {
                sleep(5);  // Time should greacefully decrade based on "connectionAttempts"
            } elseif ($connectionAttempts < 5 ) {
                $connectionRequired = false;
            } else {
                throw ($e);
            }

        }

    }

    // You'll end here on a graceful exit

} catch (Exception $e) {
    // You'll end up here if something's gone wrong

}



?>