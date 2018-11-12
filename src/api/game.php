<?
class Game
{

    static public $buildings = [
        1 => [
            'name' => 'Офис',
            'desc' => 'Необходим для для сдачи домов в аренду.',
            'distance' => 1000,
            'house' => 1,
            'time' => 43200,
            'cost' => 5,
            'own' => true,
        ],
        2 => [
            'name' => 'Кафе',
            'desc' => 'Приносит доход от домов на маленьком расстоянии.',
            'distance' => 150,
            'house' => 5,
            'time' => 86400,
            'cost' => 500,
            'own' => false,
        ]
    ];

    static public $rents = [
        1 => ['hour' => 1, 'factor' => 3],
        2 => ['hour' => 2, 'factor' => 2.5],
        3 => ['hour' => 4, 'factor' => 2],
        4 => ['hour' => 8, 'factor' => 1.5],
        5 => ['hour' => 16, 'factor' => 1]
    ];

    static public $tax = [
        'route' => 30,
        'locality' => 20,
        'administrative_area_level_1' => 15,
        'administrative_area_level_2' => 10,
        'country' => 5
    ];

    static public function ownerCost($owner_id){
        
    }

    static public function rentCost($level, $type){
        return pow($level, 0.75)*1.2*Game::$rents[$type]['hour']*Game::$rents[$type]['factor'];
    }

    static public function ownerName($id){
        $owner = R::findOne('owner', 'id = ?', [ $id ]);
        $place_id = $owner['place_id'];
        if(isset($owner['name'])) $name = $owner['name'];
        else $name = '[Неизвестное]';
        return "<a class='link' onclick='markerOpen(\"$place_id\", ".$owner['lat'].", ".$owner['lng'].")'>$name</a>";
    }

    static public function ownerRecount($id){
        $owner = R::findOne('owner', 'id = ?', [ $id ]);
        if( $owner['type'] == 1 ){
            if( $owner['build'] == 0 ){
                foreach (Game::$buildings as $num => $build) {
                    $getBoundsCoords = getBoundsCoords($owner['lat'], $owner['lng'], $build['distance']);
                    if($build['own'] == true) $link = R::findOne('link', '`type` = ? AND `house` = ?', [ $num, $owner['id'] ]);
                    if( !isset($link) ){
                        $structure = R::findCollection(
                            'owner',
                            ' (false = ? OR `user` = ?) AND `type` = ? AND `build` = ? AND `lat` < ? AND `lng` < ? AND `lat` > ? AND `lng` > ? ORDER BY sqrt((?-`lat`)^2 + (`lng`-?)^2)',
                            [
                                $build['own'],
                                $owner['user'],
                                1,
                                $num,
                                $getBoundsCoords['ne']['lat'],
                                $getBoundsCoords['ne']['lng'],
                                $getBoundsCoords['sw']['lat'],
                                $getBoundsCoords['sw']['lng'],
                                $owner['lat'],
                                $owner['lng']
                            ]
                        );

                        while ( $so = $structure->next() ){
                            $soLink = R::findOne('link', '`build` = ? AND `house` = ?', [ $so['id'], $owner['id'] ]);
                            if(!isset($soLink)){
                                $buildOwner = R::findOne('owner', 'id = ?', [ $so['id'] ]);
                                $buildNum = R::count('link', 'build = ?', [ $so['id'] ]);
                                if($buildNum < $buildOwner['level']*$build['house']){
                                    $newLink = R::dispense('link');
                                    $newLink['type'] = $num;
                                    $newLink['build'] = $so['id'];
                                    $newLink['house'] = $owner['id'];
                                    $newLink['time'] = time();
                                    R::store($newLink);
                                    break;
                                }
                            }
                        }
                    }
                }
            }else{
                $getBoundsCoords = getBoundsCoords($owner['lat'], $owner['lng'], Game::$buildings[$owner['build']]['distance']);
    
                $ownerCollection = R::findCollection(
                    'owner',
                    '`user` = ? AND `type` = ? AND `build` = ? AND `lat` < ? AND `lng` < ? AND `lat` > ? AND `lng` > ? ORDER BY sqrt((?-`lat`)^2 + (`lng`-?)^2)',
                    [
                        $owner['user'],
                        1,
                        0,
                        $getBoundsCoords['ne']['lat'],
                        $getBoundsCoords['ne']['lng'],
                        $getBoundsCoords['sw']['lat'],
                        $getBoundsCoords['sw']['lng'],
                        $owner['lat'],
                        $owner['lng']
                    ]
                );
                
                while ( $row = $ownerCollection->next() ){
                    Game::ownerRecount($row['id']);
                }
            }
        }
    }

    static public function ownerRent($user_id, $owner_id, $period){
        
        $ownerUser = R::findOne('user', '`id` = ?', [ $user_id ]);
        $owner = R::findOne('owner', 'id = ?  && `user` = ?', [ $owner_id, $ownerUser['id'] ]);
        $ownerData = json_decode(base64_decode($owner['data']),true);

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
        }elseif($owner['build'] > 0){
            $return = [
                'ok' => false,
                'description' => 'Это нельзя сдать в аренду'
            ];
        }else{
            $link = R::findOne('link', 'type = 1 AND house = ?', [ $owner['id'] ]);

            if(!isset($link)){
                $return = [
                    'ok' => false,
                    'description' => 'Дом не обслуживается'
                ];
            }else{
                
                $type = intval($period);
                $rent = Game::rentCost($owner['level'], $type);

                $ownerUser['money'] += $rent;
                R::store($ownerUser);

                $newFinance = R::dispense('finance');
                $newFinance['user'] = $ownerUser['id'];
                $newFinance['owner'] = $owner['id'];
                $newFinance['type'] = 4;
                $newFinance['sum'] = $rent;
                $newFinance['time'] = time();
                R::store($newFinance);
                
                $owner['action'] = 3;
                $owner['action_end'] = time()+Game::$rents[$type]['hour']*3600;

                if( !isset($ownerData['rent']['hour']) ) $ownerData['rent']['hour'] = 0;
                if( !isset($ownerData['rent']['sum']) ) $ownerData['rent']['sum'] = 0;

                $ownerData['rent']['hour'] += Game::$rents[$type]['hour'];
                $ownerData['rent']['sum'] += $rent;

                $owner['data'] = base64_encode(json_encode($ownerData));
                R::store($owner);

                $html = "Дом <b>" . $owner['name'] . "</b> успешно сдан в аренду";
                
                $return = [
                    'ok' => true,
                    'result' => [
                        'html' => $html
                    ]
                ];
            }
        }

        return $return;
    }
}

