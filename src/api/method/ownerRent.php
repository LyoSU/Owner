<?
$owner = R::findOne('owner', 'id = ?  && `user` = ?', [ $_POST['id'], $user['id'] ]);

if(!isset($owner)){
    $return = [
        'ok' => false,
        'description' => 'Не удалось найти владение'
    ];
}else{
    if( $owner['build'] == 1 ){

        $o = [];

        $linkCollection = R::findCollection('link', '`build` = ?',[ $owner['id'] ]);
        while ( $link = $linkCollection->next() ) {
            $linkOwner = R::findOne('owner', 'id = ?', [ $link['house'] ]);
            if( $linkOwner['action'] == 0 ){
                $returnRent = Game::ownerRent($user['id'], $linkOwner['id'], $_POST['period']);
                if( $returnRent['ok'] == true ) $o[$linkOwner['id']] = $returnRent;
            }
        }

        $html = 'Сдано в аренду <b>' . count($o) . '</b> домов';

        $return = [
            'ok' => true,
            'result' => [
                'html' => $html,
                'owner' => $o
            ]
        ];
    }else{
        $return = Game::ownerRent($user['id'], $_POST['id'], $_POST['period']);
    }
}