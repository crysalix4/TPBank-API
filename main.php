<?php
require_once("tpbank.php");
$username = ""; //sdt vpbank
$password = ""; // mật khẩu vpbank
if (empty($username) || empty($password)) {
    echo "Vui lòng nhập số điện thoại và mật khẩu.\n";
    exit;
}
$tpbank = new TpBank($username, $password);
$login = $tpbank->login();
if ($login) {
    $tpbank->setAccess_token($login);
    $tpbank->setAccountNo(62225102005);
    //$tpbank->generateqrcodepre(10000, 123456789);
    $getAccounts = $tpbank->bank_accounts();
    echo "Tìm thấy " . count($getAccounts) . " tài khoản\n";
    foreach ($getAccounts as $index => $account) {
        echo "Tài khoản " . ($index + 1) . ":\n";
        echo "Số tài khoản: " . $account['BBAN'] . "\n";
        echo "Số dư: " . $account['availableBalance'] . "\n";
        echo "------------------------\n";
    }
    echo "Nhập số tài khoản để nhận biến động số dư: ";
    $inputAccount = null;
    $readFds = [STDIN];
    $writeFds = null;
    $exceptFds = null;
    $hasChanges = stream_select($readFds, $writeFds, $exceptFds, 20);
    if ($hasChanges) {
        $inputAccount = trim(fgets(STDIN));
    } else {
        echo "\nThời gian chờ quá lâu. Tự động thoát chương trình.\n";
        exit;
    }
    $validAccount = false;
    $lastProcessedTransactionId = array();
    $lastRefreshTime = time();
    foreach ($getAccounts as $account) {
        if ($inputAccount == $account['BBAN']) {
            $validAccount = true;
            $tpbank->setAccountNo($inputAccount);
            echo "Bạn đã chọn tài khoản " . $tpbank->getAccountNo() . " để theo dõi biến động số dư.\n";
            while (true) {
                if (shouldRefreshToken($lastRefreshTime, 700)) {
                    $tpbank->refresh_access_token();
                    echo "Lấy token mới thành công\n";
                    $lastRefreshTime = time();
                }
                $transactions = $tpbank->account_transactions();
                if (isset($transactions['transactionInfos']) && !empty($transactions['transactionInfos'])) {
                    foreach ($transactions['transactionInfos'] as $transaction) {
                        if ($transaction['creditDebitIndicator'] == "CRDT") {
                            if (!in_array($transaction['reference'], $lastProcessedTransactionId)) {
                                echo "ID : " . $transaction['reference'] . "\n";
                                echo "Ngày : " . $transaction['bookingDate'] . "\n";
                                echo "Số tiền : " . $transaction['amount'] . " " . $transaction['currency'] . "\n";
                                echo "Mô tả: " . $transaction['description'] . "\n";
                                echo "------------------------\n";
                                print_r($transaction);
                                $lastProcessedTransactionId[] = $transaction['reference'];
                            }
                        }
                    }
                }
                sleep(5);
            }
        }
    }
    if (!$validAccount) {
        echo "Số tài khoản không hợp lệ.\n";
    }
} else {
    echo "Đăng nhập không thành công.\n";
}
function shouldRefreshToken($lastRefreshTime, $interval)
{
    $currentTime = time();
    return ($currentTime - $lastRefreshTime) >= $interval;
}
?>