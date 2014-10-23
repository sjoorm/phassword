<?php
/**
 * @author Alexey Tishchenko <tischenkoalexey1@gmail.com>
 * @oDesk https://www.odesk.com/users/~01ad7ed1a6ade4e02e 
 * @website https://sjoorm.com
 * date: 2014-10-01
 */
echo "Master password:\n";
$master = fgets(STDIN);
$master = substr($master, 0, strlen($master) - 1);
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
$dataEncrypted = '9oog/rrwC/Wj7r9X1pB4mMBtzA4n4j5dgnTUhtbr9Peqa8PumoB8RE51MhpD16u4NJpzLrmLUkRCzFfDZjEUVw==';
$dataDecrypted = decrypt($dataEncrypted, $master);
$data = json_decode($dataDecrypted, true);
if(!isset($data) || !is_array($data)) {
    echo "Error: incorrect master password.\n";
    exit(2);
}
$modified = false;

$menu = true;
while($menu) {
    $workflow = prompt("What do you want to do?\n 0 - Exit\n 1 - List all keys\n 2 - Show value by key\n 3 - Add value by new key\n 4 - Delete specified key.", 0);
    switch ($workflow) {
        case '1':
            $amount = 0;
            foreach ($data as $key => $value) {
                echo "[$key]\n";
                ++$amount;
            }
            echo "Total: $amount key(s).\n";
            break;
        case '2':
            $key = prompt('Enter the key that you need.', null);
            if ($key && isset($data[$key])) {
                echo "{$data[$key]}\n";
            } else {
                echo "Error: key doesn't exist.\n";
            }
            break;
        case '3':
            $key = prompt('Enter the key that you need.', null);
            if ($key && isset($data[$key])) {
                echo "Error: key already exists.\n";
            } else {
                $value = prompt('Enter value:', null);
                if (empty($value)) {
                    echo "Error: value is empty";
                } else {
                    $data[$key] = $value;
                    $modified = true;
                }
            }
            break;
        case '4':
            $key = prompt('Enter the key that you need.', null);
            if ($key && isset($data[$key])) {
                unset($data[$key]);
                $modified = true;
            } else {
                echo "Error: key doesn't exist.\n";
            }
            break;
        case 0:
            $menu = false;
            break;
        default:
            echo "Error: unknown command.\n";
            break;
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
            echo "Error: can not open script for writing.\n";
        }
    } else {
        echo "Error: can not open script for reading.\n";
    }
}
