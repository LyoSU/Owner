<?

$recaptcha = json_decode(sendGet('https://www.google.com/recaptcha/api/siteverify?secret=6LfO92oUAAAAAI9FEyLhdwoOlHpzwo4UIorwu6kN&response='.$_POST['token'].'&remoteip='.$_SERVER['HTTP_X_REAL_IP']), true);

if( $recaptcha['success'] == 1 ){

    $grecaptcha = R::findOne('grecaptcha', 'user = ? && ip = ?', [ $user['id'], $_SERVER['HTTP_X_REAL_IP'] ]);
    if(!isset($grecaptcha)) $grecaptcha = R::dispense('grecaptcha');
    $grecaptcha['user'] = $user['id'];
    $grecaptcha['ip'] = $_SERVER['HTTP_X_REAL_IP'];
    $grecaptcha['score'] = $recaptcha['score'];
    $grecaptcha['time'] = time();
    R::store($grecaptcha);

    $return = [
        'ok' => true,
        'result' => [
            'ok'
        ]
    ];

}else{
    $return = [
        'ok' => false,
        'description' => 'captcha error'
    ];
}