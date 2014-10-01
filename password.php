<?php
/**
 * @author Alexey Tishchenko <tischenkoalexey1@gmail.com>
 * @oDesk https://www.odesk.com/users/~01ad7ed1a6ade4e02e 
 * @website https://sjoorm.com
 * date: 2014-10-01
 */
$master = $argv[1] ?: null;
if(empty($master)) {
    echo "Error: you should provide master password.\n";
    exit(1);
}

/**
 * Encryption
 * @param string $plainText
 * @param string $password
 * @return string
 */
function encrypt($plainText, $password) {
    $mcrypt = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CBC, '');
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($mcrypt), MCRYPT_RAND);
    mcrypt_generic_init($mcrypt, $password, $iv);
    $cipherText = $iv . mcrypt_generic($mcrypt, $plainText);
    mcrypt_generic_deinit($mcrypt);
    mcrypt_module_close($mcrypt);
    return base64_encode($cipherText);
}

/**
 * Decryption
 * @param string $cipherText base64 of IV+cipherText
 * @param string $password
 * @return string
 */
function decrypt($cipherText, $password) {
    $mcrypt = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CBC, '');
    $ivSize = mcrypt_enc_get_iv_size($mcrypt);
    $cipherTextClean = base64_decode($cipherText);
    $iv = substr($cipherTextClean, 0, $ivSize);
    $cipherText = substr($cipherTextClean, $ivSize);
    mcrypt_generic_init($mcrypt, $password, $iv);
    $result = rtrim(mdecrypt_generic($mcrypt, $cipherText));
    mcrypt_generic_deinit($mcrypt);
    mcrypt_module_close($mcrypt);
    return $result;
}

/**
 * Command line interactions
 * @param string $question
 * @param string $defaultAnswer
 * @return mixed
 */
function prompt($question, $defaultAnswer) {
    echo $question . "\n";
    $input = rtrim(fgets(STDIN));
    return $input ?: $defaultAnswer;
}

#DATA#
$dataEncrypted = 'eblEkH9n+K7htjDlZrBke00RR/0EJoSv5HtmL8AX9ehKOixnGF6ogLVgPE2lJ21nHKI11hneG96nK9wjKjmqkQ==';
$dataDecrypted = decrypt($dataEncrypted, $master);
$data = json_decode($dataDecrypted, true);
$modified = false;

$workflow = prompt("What do you want to do?\n 1 - List all keys\n 2 - Show value by key\n 3 - Add value by new key\n 4 - Delete specified key.", 0);
switch($workflow) {
    case '1':
        $amount = 0;
        foreach($data as $key => $value) {
            echo "[$key]\n";
            ++$amount;
        }
        echo "Total: $amount key(s).";
        break;
    case '2':
        $key = prompt('Enter the key that you need.', null);
        if($key && isset($data[$key])) {
            echo "{$data[$key]}\n";
        } else {
            echo "Error: key doesn't exist.\n";
            exit(2);
        }
        break;
    case '3':
        $key = prompt('Enter the key that you need.', null);
        if($key && isset($data[$key])) {
            echo "Error: key already exists.\n";
            exit(3);
        } else {
            $value = prompt('Enter value:', null);
            if(empty($value)) {
                echo "Error: value is empty";
                exit(3);
            } else {
                $data[$key] = $value;
                $modified = true;
            }
        }
        break;
    case '4':
        $key = prompt('Enter the key that you need.', null);
        if($key && isset($data[$key])) {
            unset($data[$key]);
            $modified = true;
        } else {
            echo "Error: key doesn't exist.\n";
            exit(4);
        }
        break;
    default:
        echo "Error: unknown command.\n";
        exit(5);
        break;
}

var_dump($data);

if($modified) {
    $dataNew = encrypt(json_encode($data), $master);
    var_dump($dataNew);
}
