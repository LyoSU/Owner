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
        'description' => 'Сейчас невозможно сдать в аренду'
    ];
}elseif($owner['build'] > 1){
    $return = [
        'ok' => false,
        'description' => 'Это нельзя сдать в аренду'
    ];
}else{

    $html = '';
    $rentLevel = [];
    
    if( $owner['build'] == 1 ){
        $linkCollection = R::findCollection('link', '`build` = ?',[ $owner['id'] ]);
        while ( $link = $linkCollection->next() ) {
            $linkOwner = R::findOne('owner', 'id = ?', [ $link['house'] ]);
            if( $linkOwner['action'] == 0 ) $rentLevel[] = $linkOwner['level'];
        }
    }

    foreach (Game::$rents as $type => $value) {
        if( $owner['build'] == 1 ){
            $rent = 0;
            foreach ($rentLevel as &$level) $rent += Game::rentCost($level, $type);
        }else{
            $rent = Game::rentCost($owner['level'], $type);
        }
        $html .= '<div class="popup-block" onclick="ownerRent(\'' . $owner['id'] . '\', ' . $type . ')"><table style="width: 100%;"><tr><td style="width: 75%;"><b>Сдать в аренду на ' . $value['hour'] . ' ' . declOfNum($value['hour'], ['час', 'часа', 'часов']) . '</b><br>Доходность ' . ($value['factor']*100) . '%</td><td style="text-align: right;">' . number_format($rent, 2, ',', '\'') . '<i class="dollar sign icon"></i></td></tr></table></div><hr>';
    }

    $return = [
        'ok' => true,
        'result' => [
            'html' => $html
        ]
    ];
}