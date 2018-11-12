<?
$owner = R::findOne('owner', 'id = ?', [ $_POST['id'] ]);
$auctionLeader = R::findOne('auction', 'owner = ? AND type = 1', [ $owner['id'] ]);

if(!isset($owner)){
    $return = [
        'ok' => false,
        'description' => 'Владение не найдено'
    ];
}else{

    $bid = intval($_POST['bid']);

    if(($auctionLeader['bid']*1.10-1) > $bid){
        $return = [
            'ok' => false,
            'description' => 'Ставка должна быть как минимум на 10% больше текущей'
        ];
    }elseif( $user['money'] < $bid ){
        $return = [
            'ok' => false,
            'description' => 'Недостаточно средств для повышения ставки'
        ];
    }else{

        $user['money'] -= $bid;
        R::store($user);

        if( $owner['user'] !== $user['id'] && $owner['action_end']-time() < 43200 ){
            $owner['action_end'] = time()+43200;
            R::store($owner);
        }

        $newFinance = R::dispense('finance');
        $newFinance['user'] = $user['id'];
        $newFinance['owner'] = $owner['id'];
        $newFinance['type'] = 5;
        $newFinance['sum'] = -$bid;
        $newFinance['time'] = time();
        R::store($newFinance);
        
        $leaderInfo = R::load('user', $auctionLeader['user']);
        $leaderInfo['money'] += $auctionLeader['bid'];
        R::store($leaderInfo);

        $auctionLeader['type'] = 0;
        R::store($auctionLeader);
        
        $newFinance = R::dispense('finance');
        $newFinance['user'] = $leaderInfo['id'];
        $newFinance['owner'] = $owner['id'];
        $newFinance['type'] = 5;
        $newFinance['sum'] = $auctionLeader['bid'];
        $newFinance['time'] = time();
        R::store($newFinance);

        $auction = R::findOne('auction', 'owner = ? AND user = ?', [ $owner['id'], $user['id'] ]);
        if( !isset($auction) ) $auction = R::dispense('auction');
        $auction['owner'] = $owner['id'];
        $auction['user'] = $user['id'];
        $auction['type'] = 1;
        $auction['bid'] = $bid;
        $auction['time'] = time();
        R::store($auction);

        $html = 'Ваша ставка принята';

        $return = [
            'ok' => true,
            'result' => [
                'html' => $html
            ]
        ];
    }
}
