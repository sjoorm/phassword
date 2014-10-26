#!/usr/bin/env/ php
<?php
/**
 * @author Alexey Tishchenko <tischenkoalexey1@gmail.com>
 * @oDesk https://www.odesk.com/users/~01ad7ed1a6ade4e02e 
 * @website https://sjoorm.com
 * date: 2014-10-01
 */

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
    echo "{$question}\n\033[s";
    $input = rtrim(fgets(STDIN));
    return $input === '' ? $defaultAnswer : $input;
}

$master = prompt('Master password:', null);
if(empty($master)) {
    echo "\033[31mError\033[0m: you should provide master password.\n";
    exit(1);
}

#DATA#
$dataEncrypted = '8Bk34/IkcVaDm/MTyKryJ0PIxF+CWTBv4NOaWelkyAwoTrqj82QwXyoUSxP/bcIMZv+Zwau+sDyOzcvSBJhp7g==';
$dataDecrypted = decrypt($dataEncrypted, $master);
$data = json_decode($dataDecrypted, true);
if(!isset($data) || !is_array($data)) {
    echo "\033[31mError\033[0m: incorrect master password.\n";
    exit(2);
}
$modified = false;

$menu = true;
$workflow = -1;
while($menu) {
    system('clear');
    switch ($workflow) {
        case 1:
            $amount = 0;
            foreach ($data as $key => $value) {
                echo "\033[33m$key\033[0m\n";
                ++$amount;
            }
            echo "Total: \033[34m$amount\033[0m key(s).\n";
            break;
        case 2:
            $key = prompt("Enter the key that you need.\033[33m", null);
            if ($key && isset($data[$key])) {
                echo "\033[u$key\033[0m => [\033[34m{$data[$key]}\033[0m]\n";
            } else {
                echo "\033[31mError\033[0m: key doesn't exist.\n";
            }
            break;
        case 3:
            $key = prompt("Enter the key that you need.\033[33m", null);
            if ($key && isset($data[$key])) {
                echo "\033[31mError\033[0m: key already exists.\n";
            } else {
                $value = prompt("\033[0mEnter value:\033[34m", null);
                if (empty($value)) {
                    echo "\033[31mError\033[0m: value is empty";
                } else {
                    $data[$key] = $value;
                    $modified = true;
                }
            }
            break;
        case 4:
            $key = prompt("Enter the key that you need.\033[33m", null);
            if ($key && isset($data[$key])) {
                unset($data[$key]);
                $modified = true;
            } else {
                echo "\033[31mError\033[0m: key doesn't exist.\n";
            }
            break;
        case 5:
            $masterNew = prompt('Enter new master password:', null);
            if(empty($masterNew)) {
                echo "\033[31mError\033[0m: password can not be blank.\n";
            } else {
                $master = $masterNew;
                $modified = true;
            }
            break;
        case 0:
            $menu = false;
            system('clear');
            break;
        default:
            echo "You must enter one of the following commands.\n";
            break;
    }
    if($menu) {
        $workflow = prompt(
            "\033[0m============================\n" .
            "What do you want to do?\n" .
            " 0 - Exit\n" .
            " 1 - List all keys\n" .
            " 2 - Show value by key\n" .
            " 3 - Add value by new key\n" .
            " 4 - Delete specified key\n" .
            " 5 - Change master password.\n", -1);
    }
}

if($modified) {
    $dataEncryptedNew = encrypt(json_encode($data), $master);
    $file = fopen(__FILE__, 'r');
    if ($file) {
        $content = [];
        while (!feof($file)) {
            $line = fgets($file);
            if ($line === "#DATA#\n") {
                fgets($file); //skip ENCRYPTED DATA line
                $content[] = $line;
                $content[] = "\$dataEncrypted = '$dataEncryptedNew';\n";
            } else {
                $content[] = $line;
            }
        }
        fseek($file, 0);
        fclose($file);
        $file = fopen(__FILE__, 'w');
        if($file) {
            foreach($content as $line) {
                fwrite($file, $line);
            }
            fclose($file);
        } else {
            echo "\033[31mError\033[0m: can not open script for writing.\n";
        }
    } else {
        echo "\033[31mError\033[0m: can not open script for reading.\n";
    }
}
