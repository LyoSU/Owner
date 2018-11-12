<?
$place_id = $_POST['place_id'];
$place = json_decode(sendGet("https://maps.googleapis.com/maps/api/place/details/json?placeid=$place_id&key=$gma_key&language=ru"), true)['result'];

$owner = R::findOne('owner', 'place_id = ? && `user` = ?', [ $place_id, $user['id'] ]);

if( !isset($place) ){
    $return = [
        'ok' => false,
        'description' => 'Место не найдено'
    ];
}elseif( isset( $owner ) ){
    $return = [
        'ok' => false,
        'description' => 'Место уже приобретено'
    ];
}else{
    $types = ['street_number' => 1, 'premise' => 1, 'route' => 2, 'locality' => 3, 'administrative_area_level_2' => 4, 'administrative_area_level_1' => 5, 'country' => 6];
    $typeCost = [1 => 15, 2 => 800, 3 => 3000, 4 => 20000, 5 => 50000, 250000 ];

    $type = $types[$place['address_components'][0]['types'][0]];
    $cost = $typeCost[$type];

    $user['money'] -= $cost;
    
    if( !isset($type) ){
        $return = [
            'ok' => false,
            'description' => 'Не удалось определить тип'
        ];
    }elseif( $user['money'] < 0 ){
        $return = [
            'ok' => false,
            'description' => 'Недостаточно средств для совершения покупки'
        ];
    }else{
        
        R::store($user);

        $lat = $place['geometry']['location']['lat'];
        $lng = $place['geometry']['location']['lng'];

        $data = [];

        $type_place_id = ['street_number' => 0, 'route' => 0, 'locality' => 0, 'administrative_area_level_1' => 0, 'administrative_area_level_2' => 0, 'country' => 0];

        $geo = json_decode(sendGet("https://maps.googleapis.com/maps/api/geocode/json?&latlng=$lat,$lng&key=$gma_key&language=ru"), true)['results'];

        foreach ($geo as &$v) if( isset($types[$v['types'][0]]) && $types[$v['types'][0]] > $type )
            $type_place_id[$v['types'][0]] = $v['place_id'];

        if( $type == 1 ){
            foreach ($place['address_components'] as &$v) 
                if( $v['types'][0] !== 'street_number' )
                    $name[] = $v['long_name'];
            
            $name = urlencode(implode(', ', $name));
            $type_place_id['route'] = json_decode(sendGet("https://maps.googleapis.com/maps/api/geocode/json?&address=$name&key=$gma_key&language=ru"), true)['results'][0]['place_id'];
        }

        if( $type == 1 ) $action_end = time();
        else $action_end = time()+86400*3;

        $newOwner = R::dispense('owner');
        $newOwner['place_id'] = $place_id;
        $newOwner['user'] = $user['id'];
        $newOwner['type'] = $type;
        $newOwner['level'] = 1;
        $newOwner['action'] = 1;
        $newOwner['action_end'] = $action_end;
        $newOwner['data'] = base64_encode(json_encode($data));
        $newOwner['name'] = $place['address_components'][0]['short_name'];
        $newOwner['long_name'] = $place['address_components'][0]['long_name'];
        $newOwner['route'] = $type_place_id['route'];
        $newOwner['locality'] = $type_place_id['locality'];
        $newOwner['administrative_area_level_1'] = $type_place_id['administrative_area_level_1'];
        $newOwner['administrative_area_level_2'] = $type_place_id['administrative_area_level_2'];
        $newOwner['country'] = $type_place_id['country'];
        $newOwner['lat'] = $lat;
        $newOwner['lng'] = $lng;
        $newOwner['time'] = time();
        R::store($newOwner);

        $id = R::getInsertID();

        $newFinance = R::dispense('finance');
        $newFinance['user'] = $user['id'];
        $newFinance['owner'] = $id;
        $newFinance['type'] = 1;
        $newFinance['sum'] = -$cost;
        $newFinance['time'] = time();
        R::store($newFinance);

        if( $type > 1 ){
            $auction = R::dispense('auction');
            $auction['owner'] = $id;
            $auction['user'] = $user['id'];
            $auction['type'] = 1;
            $auction['bid'] = $cost*0.7;
            $auction['time'] = time();
            R::store($auction);
        }
        
        $return = [
            'ok' => true,
            'result' => [
                'html' => 'Началось строительство',
                'place_id' => $place_id,
                'lat' => $lat,
                'lng' => $lng,
                'type' => $type
            ]
        ];
    }

}