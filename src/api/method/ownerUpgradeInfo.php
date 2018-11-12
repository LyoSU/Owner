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
        'description' => 'Сейчас невозможно начать улучшение'
    ];
}else{

    $html = '<form id="upgradeForm" onSubmit="ownerUpgrade()" action="javascript:void(0);"><input id="upgrade-id" type="hidden" value="'.$owner['id'].'">';

    if($owner['build'] == 0){
        if($owner['level'] < 30){
            $time = 2000*$owner['level']*1.5;
            $cost = pow(($owner['level']+1), 1.9)*14;
            $rent = pow(($owner['level']+1), 0.75)*1.2;
            
            $html .= '<b>После улучшения:</b><br>';
            $html .= 'Арендная плата: ' . number_format($rent, 2, ',', '\'') . '<i class="dollar icon"></i>/час';
            $html .= '<hr><b>Цена улучшения:</b> ' . number_format($cost, 2, ',', '\'') . '<i class="dollar icon"></i><br><b>Время улучшения:</b> ' . hTime($time);
            $html .= '<hr><button class="fluid ui positive labeled icon button">Улучшить<i class="angle double up icon"></i></button></form>';
        }else{
            $html .= '<b>У вас максимальный уровень</b>';
        }
    }elseif($owner['build'] == 1){
        $price = 5;
        $officeLevelSum = R::getCell( 'SELECT SUM(`level`) FROM `owner` WHERE `build` = ? AND `user` = ?', [ 1, $user['id'] ] );
        $cost = number_format(pow($officeLevelSum+1, 1.15)*$price*1, 2, ',', '\'');

        $html .= '
            <script>
                $("#upgrade-num").on("input keyup", function() {
                    num = parseInt(this.value);
                    if(num > 99) num = 99;
                    $(this).val(num);
                    $("#upgrade-sum").number(Math.pow('.$officeLevelSum.'+num, 1.15)*'.$price.'*num, 2, ",", "\'");
                    $("#upgrade-time").text(hTime(num*3600*1.5));
                });
            </script>
        '; 

        $html .= '<b>Введи желаемое количество домов для расширения офиса:</b><hr><div class="ui mini input"><input id="upgrade-num" type="number" min="1" max="99" size="2" value="1"></div> <b>= <span id="upgrade-sum">' . $cost . '</span></b><i class="dollar icon"></i><hr><b>Время улучшения:</b> <span id="upgrade-time">1 час 30 минут</span>';
        $html .= '<hr><button class="fluid ui positive labeled icon button">Расширить<i class="angle double up icon"></i></button></form>';
    }

    $return = [
        'ok' => true,
        'result' => [
            'html' => $html
        ]
    ];
}