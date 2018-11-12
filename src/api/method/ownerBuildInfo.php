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
}else{

    $html = '';

    foreach (Game::$buildings as $build => $value) {
        if( $build == 1 ){
            $officeLevelSum = R::getCell( 'SELECT SUM(`level`) FROM `owner` WHERE `build` = ? AND `user` = ?', [ 1, $user['id'] ] );
            if( $officeLevelSum < 1 ) $value['time'] = 60;
            $value['cost'] = pow($officeLevelSum+5, 1.15)*$value['cost']*5*1.5;
            $officeLevelSum*1;
        }

        $html .= '<div class="popup-block" onclick="ownerBuild(\'' . $owner['id'] . '\', ' . $build . ')"><img src="https://ua.lyo.su/owner/style/images/icon/' . (100+$build) . '.png" width="15px" height="15px"> <b>' . $value['name'] . '</b><br>' . $value['desc'] . '<hr><b>Радиус работы:</b> ' . $value['distance'] . ' ' . declOfNum($value['distance'], ['метр', 'метра', 'метров']) . '<br><b>Время строительства:</b> ' . hTime($value['time']) . '<br><b>Цена строительства:</b> ' . number_format($value['cost'], 2, ',', '\'') . '<i class="dollar sign icon"></i></div><hr>';
    }

    $return = [
        'ok' => true,
        'result' => [
            'html' => $html
        ]
    ];
}