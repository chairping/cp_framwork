<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/12
 * Time: 20:41
 */

namespace App\Module\Home\Controller;

use Core\ApiController;
use Lib\Email\PHPMailer;

class TestController extends ApiController {

    public function sendMail() {
        $mail = new PHPMailer(true);

        $mail->SMTPDebug = 1;                               // Enable verbose debug output

        $mail->IsSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.qq.com';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = '';                 // SMTP username
        $mail->Password = '';                           // SMTP password
        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 465;                                    // TCP port to connect to

        $mail->setFrom('@qq.com', '');
        $mail->addReplyTo('@qq.com', '');
        $mail->addAddress('@qq.com');               // Name is optional

        $mail->isHTML(true);                                  // Set email format to HTML

        $mail->Subject = ' Here is the subject';
        $mail->Body    = ' This is the HTML message body <b>in bold!</b>';
        $mail->AltBody = ' This is the body in plain text for non-HTML mail clients';

        if(!$mail->send()) {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
        } else {
            echo 'Message has been sent';
        }
    }
}
