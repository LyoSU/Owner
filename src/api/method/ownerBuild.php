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
        'description' => 'Сейчас невозможно начать строительство'
    ];
}elseif($owner['build'] > 0){
    $return = [
        'ok' => false,
        'description' => 'Здесь уже построено здание'
    ];
}else{

    $build = Game::$buildings[$_POST['build']];

    $level = 1;

    if( $_POST['build'] == 1 ){
        $officeLevelSum = R::getCell( 'SELECT SUM(`level`) FROM `owner` WHERE `build` = ? AND `user` = ?', [ 1, $user['id'] ] );
        $level = 5;
        if( $officeLevelSum < 1 ) $build['time'] = 60;
        $build['cost'] = pow($officeLevelSum+5, 1.15)*$build['cost']*5*1.5;
    }

    if( !isset($build) ){
        $return = [
            'ok' => false,
            'description' => 'Это невозможно построить'
        ];
    }elseif( $user['money'] < $build['cost'] ){
        $return = [
            'ok' => false,
            'description' => 'Недостаточно средств для строительства'
        ];
    }else{
        $user['money'] -= $build['cost'];
        R::store($user);
        
        $owner['build'] = $_POST['build'];
        $owner['level'] = $level;
        $owner['action'] = 1;
        $owner['action_end'] = time()+$build['time'];
        R::store($owner);

        $newFinance = R::dispense('finance');
        $newFinance['user'] = $user['id'];
        $newFinance['owner'] = $owner['id'];
        $newFinance['type'] = 2;
        $newFinance['sum'] = -$build['cost'];
        $newFinance['data'] = base64_encode(json_encode(['build' => $_POST['build']]));
        $newFinance['time'] = time();
        R::store($newFinance);
        
        R::exec( 'DELETE FROM `link` WHERE `house` = ?', [ $owner['id'] ]);

        $return = [
            'ok' => true,
            'result' => [
                'html' => 'Началось строительство',
                'place_id' => $owner['place_id']
            ]
        ];
    }
}