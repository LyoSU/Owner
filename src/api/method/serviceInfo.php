<?
$owner = R::findOne('owner', 'id = ?  && `user` = ?', [ $_POST['id'], $user['id'] ]);

if(!isset($owner)){
    $return = [
        'ok' => false,
        'description' => 'Не удалось найти владение'
    ];
}elseif($owner['build'] == 0){
    $return = [
        'ok' => false,
        'description' => 'Чо? Это и есть дом'
    ];
}else{

    $html = '';

    if( $owner['build'] == 1 ) $html = '<button class="fluid mini ui blue button" onclick="ownerRentInfo(\'' . $owner['id'] . '\')">Сдать в аренду все свободные дома</button><hr>';

    $linkCollection = R::findCollection('link', '`build` = ? ORDER BY `time` DESC LIMIT 100',[ $owner['id'] ]);
    while ( $link = $linkCollection->next() ) {
        $linkOwner = R::findOne('owner', 'id = ?', [ $link['house'] ]);

        if(($linkOwner['action'] == 1 OR $linkOwner['action'] == 2) && $linkOwner['type'] == 1) $icon = 0;
        elseif($linkOwner['build'] == 0) $icon = $linkOwner['type'];
        else $icon = $linkOwner['type']*100+$linkOwner['build'];

        $html .= "<img src='https://ua.lyo.su/owner/style/images/icon/$icon.png' width='15px' height='15px'> <b>" . Game::ownerName($linkOwner['id']) . "</b><hr>";
    }

    $return = [
        'ok' => true,
        'result' => [
            'html' => $html
        ]
    ];
}