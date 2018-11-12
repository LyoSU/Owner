<?
$owner = R::findOne('owner', 'id = ?  && `user` = ?', [ $_POST['id'], $user['id'] ]);

if(!isset($owner)){
    $return = [
        'ok' => false,
        'description' => 'Не удалось найти владение'
    ];
}elseif($owner['action'] > 0){
    $return = [
        'ok' => false,
        'description' => 'Сейчас невозможно начать улучшение'
    ];
}elseif($owner['build'] == 0 && $owner['level'] > 29){
    $return = [
        'ok' => false,
        'description' => 'Максимальный уровень'
    ];
}else{

    if($owner['build'] == 0){
        $time = 2000*$owner['level']*1.5;
        
        $level = $owner['level']+1;
        $cost = pow(($owner['level']+1), 1.9)*14;
    }elseif($owner['build'] == 1){
        $num = intval($_POST['num']);
        if($num < 0) $num = 1;
        if($num > 99) $num = 99;

        $time = $num*3600*1.5;

        $level = $owner['level']+$num;
        $price = 5;
        $officeLevelSum = R::getCell( 'SELECT SUM(`level`) FROM `owner` WHERE `build` = ? AND `user` = ?', [ 1, $user['id'] ] );
        $cost = pow($officeLevelSum+$num, 1.15)*$price*$num;
    }else{

    }

    if( $user['money'] < $cost ){
        $return = [
            'ok' => false,
            'description' => 'Недостаточно средств для улучшения'
        ];
    }else{
        $user['money'] -= $cost;
        R::store($user);
        
        $newFinance = R::dispense('finance');
        $newFinance['user'] = $user['id'];
        $newFinance['owner'] = $owner['id'];
        $newFinance['type'] = 3;
        $newFinance['sum'] = -$cost;
        $newFinance['data'] = base64_encode(json_encode(['level' => $level]));
        $newFinance['time'] = time();
        R::store($newFinance);
        
        $owner['level'] = $level;
        $owner['action'] = 2;
        $owner['action_end'] = time()+$time;
        R::store($owner);

        $html = 'Началось улучшение';

        $return = [
            'ok' => true,
            'result' => [
                'place_id' => $owner['place_id'],
                'html' => $html
            ]
        ];
    }
}