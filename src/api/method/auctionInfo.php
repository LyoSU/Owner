<?

$owner = R::findOne('owner', 'id = ?', [ $_POST['id'] ]);
$auctionLeader = R::findOne('auction', 'owner = ? AND type = 1', [ $owner['id'] ]);

if(!isset($owner)){
    $return = [
        'ok' => false,
        'description' => 'Владение не найдено'
    ];
}else{

    $bid = $auctionLeader['bid']*1.10;

    $html = '<form id="auctionForm" onSubmit="auctionBid()" action="javascript:void(0);"><div class="fluid ui left icon action input"><input id="auction-id" type="hidden" value="'.$owner['id'].'"><input id="auction-bid" type="number" placeholder="Сумма ставки" min="'.$bid.'" step="any" value="'.$bid.'" required><i class="dollar icon"></i><button class="ui teal right button">Подтвердить</button></div></form>';
    $html .= '<div class="ui mini message"><i class="exclamation icon"></i>Ставка должна быть как минимум на 10% больше текущей.</div>';

    $return = [
        'ok' => true,
        'result' => [
            'html' => $html
        ]
    ];
}