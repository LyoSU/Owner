<?
$owner = R::findOne('owner', 'id = ?  && `user` = ?', [ $_POST['id'], $user['id'] ]);

if(!isset($owner)){
    $return = [
        'ok' => false,
        'description' => 'Не удалось найти владение'
    ];
}else{

    $data = json_decode(base64_decode($owner['data']),true);

    $newFinance = R::dispense('finance');
    $newFinance['user'] = $user['id'];
    $newFinance['owner'] = $owner['id'];
    $newFinance['type'] = 6;
    $newFinance['sum'] = $data['cash'];
    $newFinance['time'] = time();
    R::store($newFinance);

    $user['money'] += $data['cash'];
    R::store($user);

    $data['cash'] = 0;
    $owner['data'] = base64_encode(json_encode($data));
    R::store($owner);

    $return = [
        'ok' => true,
        'result' => [
            'html' => 'Вы забрали кэш'
        ]
    ];

}