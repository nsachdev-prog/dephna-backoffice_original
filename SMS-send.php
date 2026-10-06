<?php
    $tmClient = new TMRestClient("$SMSUsername", "$SMSPassword", 'production');
    try {
        $credits = $tmClient->getCredits();
        if ($credits > 0) {
            $ex = $tmClient->sendSMS("$SMSmessage", "$SMSto", "$SMSOriginator");
        } else {
            $SMSresultMessage .= "No SMS credits available<br>\n";
        }
        if (isset($ex['error'])) {
            // handle the error
            $SMSresultMessage .= "An error occurred: {$ex['error']}<br>{$ex['error_description']}<br>\n";
        } elseif (isset($ex['credits_used'])) {
            $SMSresultMessage .= "SMS Sent. Used {$ex['credits_used']} credits, message ID {$ex['message_id']}, scheduled ID {$ex['scheduled_id']}<br>\n";   
        }
    } catch (Exception $ex) {
        // handle a possible error
        $SMSresultMessage .= "An error occurred: {$ex->getCode()}, {$ex->getMessage()}";
    }
?>