<?
ini_set("log_errors", 1);
ini_set("error_log", "error.log");

require '../config.php';
require '../func.php';

$headers = apache_request_headers();

header('Content-Type: application/json');

if( isset($_COOKIE['user_id']) && isset($_SESSION) && $_SESSION['session'] == $_GET['session'] ) $user = R::findOne('user', 'id = ? && session = ?', [$_COOKIE['user_id'], $_GET['session']]);

if( !isset($user) ){
    $return = [
        'ok' => false,
        'description' => 'BAD_SESSION'
    ];
}else{
    
    if( isset($_POST['in_grecaptcha']) OR isset($_POST['grecaptcha']) ){
        if( isset($_SESSION['CSRF']) ) $CSRF = $_SESSION['CSRF'];
        $_SESSION['CSRF'] = md5(uniqid('OwnerMap', true));
    }

    if( isset($_POST['in_grecaptcha']) ){
        $recaptcha = json_decode(sendGet('https://www.google.com/recaptcha/api/siteverify?secret=6LdWE2wUAAAAADTIsfZ7xZHgZ8_HGTeT3sB7FHmi&response='.$_POST['in_grecaptcha'].'&remoteip='.$_SERVER['HTTP_X_REAL_IP']), true);
        file_put_contents('log.txt', json_encode($recaptcha).PHP_EOL, FILE_APPEND | LOCK_EX);

        if( $recaptcha['success'] == 1 ) $grecaptcha_success = true;
        else $grecaptcha_success = false;
    }elseif( isset($_POST['grecaptcha']) ){
        $recaptcha = json_decode(sendGet('https://www.google.com/recaptcha/api/siteverify?secret=6LfO92oUAAAAAI9FEyLhdwoOlHpzwo4UIorwu6kN&response='.$_POST['grecaptcha'].'&remoteip='.$_SERVER['HTTP_X_REAL_IP']), true);
        file_put_contents('log.txt', json_encode($recaptcha).PHP_EOL, FILE_APPEND | LOCK_EX);

        if( $recaptcha['success'] == 1 ){
            $grecaptcha = R::findOne('grecaptcha', 'user = ? && ip = ?', [ $user['id'], $_SERVER['HTTP_X_REAL_IP'] ]);
            if(!isset($grecaptcha)) $grecaptcha = R::dispense('grecaptcha');
            $grecaptcha['user'] = $user['id'];
            $grecaptcha['ip'] = $_SERVER['HTTP_X_REAL_IP'];
            $grecaptcha['score'] = $recaptcha['score'];
            $grecaptcha['time'] = time();
            R::store($grecaptcha);

            if( $recaptcha['action'] == $_GET['method'] && $grecaptcha['score'] > 0.4 ) $grecaptcha_success = true;
            else $grecaptcha_success = false;
        }
    }

    // $grecaptcha['score'] = 0;

    $availableMethods = [ 'grecaptcha', 'userRestart', 'popupOwner', 'popupAuction', 'popupFinance', 'structurePoint', 'structureInfo', 'structureBuy', 'ownerRent', 'ownerRentInfo', 'ownerBuild', 'ownerBuildInfo', 'auctionBid', 'auctionInfo', 'ownerUpgrade', 'ownerUpgradeInfo', 'serviceInfo', 'cashGet' ];
    
    $importantMethods = [ 'structureBuy', 'ownerBuild', 'ownerUpgrade', 'ownerRent', 'auctionBid', 'cashGet' ];
    
    if( !in_array($_GET['method'], $availableMethods) ){
        $return = [
            'ok' => false,
            'description' => 'Method not found'
        ];
    }elseif( in_array($_GET['method'], $importantMethods) && $headers['CSRF'] !== $CSRF){
        $return = [
            'ok' => false,
            'description' => 'Не кликай так быстро'
        ];
    }elseif( in_array($_GET['method'], $importantMethods) && ( !isset($recaptcha) OR $grecaptcha_success == false ) ){

        $description = 'Проверка на бота пройдена неудачно!<br>Проводим дополнительную проверку...';
        $description .= '
        <script src="https://www.google.com/recaptcha/api.js?onload=validate" async defer></script>
        <script>

            var data = '.json_encode($_POST).';

            function onSubmit(token) {
                data["in_grecaptcha"] = token;
                $("#mini-popup").modal("hide");
                ownerApi(
                    "'.$_GET['method'].'",
                    data,
                    function(api){
                        $("#mini-popup").modal("hide");
                        toastr.success(api.html);
                    }
                );
            }
            
            function validate() {
                grecaptcha.execute();
            }
        </script>
        
        <div id="recaptcha" class="g-recaptcha" data-sitekey="6LdWE2wUAAAAABWdcc7cUAPp6h56jTcrKxbIq1gI" data-callback="onSubmit" data-size="invisible"></div>
        ';

        $return = [
            'ok' => false,
            'description' => $description
        ];
    }else{
        require 'game.php';
        $Game = new Game();
        require 'method/' . $_GET['method'] . '.php';
    }
}

header('CSRF: '.$_SESSION['CSRF']);

echo json_encode($return);