<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">

  <title>Relógio de Oração</title>
  <meta name="description" content="Batista Renascer - Relógio de Oração">
  <meta name="author" content="Batista Renascer">
  
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
</head>

<body>
	
<?php

echo "<div class='total'>Relógio de Oração IBR</div></br></br>";

$json = file_get_contents('https://spreadsheets.google.com/feeds/list/1So-FGlrmkfGZjzWAmvfF3P2VB5AoIiClRJCtYPX932Q/default/public/values?alt=json');
$data = json_decode($json,true);

$inicioHoracao = array();
$terminoHoracao = array();

$totalDePessoas = count($data['feed']['entry']);

for($i=0; $i<$totalDePessoas; $i++) {

    if($data['feed']['entry'][$i]['gsx$horáriodeiníciodaoração']['$t'] == "")
        break;

    $inicioHoracaoDado = $data['feed']['entry'][$i]['gsx$horáriodeiníciodaoração']['$t'];
    $terminoHoracaoDado = $data['feed']['entry'][$i]['gsx$horáriodetérminodaoração']['$t'];

    $inicioHoracaoD = substr($inicioHoracaoDado, 0, 5);
    $terminoHoracaoD = substr($terminoHoracaoDado, 0, 5);
    
    if($terminoHoracaoD == "00:00"){
        $terminoHoracaoD = "24:00";
    }

    array_push($inicioHoracao, $inicioHoracaoD);
    array_push($terminoHoracao, $terminoHoracaoD);
}

$inicio_oracao = $inicioHoracao;
$fim_oracao = $terminoHoracao;

global $matrizHorarios;
global $matrizLotes;
global $matrizFinal;
global $grafico;
global $gfc;

$matrizHorarios = montaMatrizHorarios();
$matrizLotes = montaMatrizLotes($inicio_oracao, $fim_oracao);
$matrizFinal = montaMatrizFinal();

echo "<div class='total'>Pessoas cadastradas: <div style='font-size: 2em;'>" . count($inicio_oracao) . "</div></div></br>";

$horariosPreenchidos = preencheHorario($matrizHorarios, $matrizLotes, $matrizFinal, $inicio_oracao);

/*
 * Dados para o gráfico
 */
$grafico = array();
$parcial = 0;
for($i=0; $i<24; $i++){
    $media = 0; $parcial = 0;
    $j = $i*12;
    $y = $j+12;
    for($j; $j<$y; $j++){
        $parcial += $horariosPreenchidos[$j];
    }
    $media = $parcial/12;
    $medias = number_format($media, 2, '.', ' ');
    array_push($grafico, $medias);
}
for($t = 0; $t<24; $t++){
    $l = $t*60;
    $h = date('H:i', mktime(00, $l));
    $gfc .= "['".$h."',  ".$grafico[$t]."]";
    if($t != 23) {
        $gfc .= ",";
    }
}

echo "<div id='chart_div'></div></br>";

geraHoraEmHora($horariosPreenchidos);

echo "</br></br>";
echo "<div class='total2'>Relatório de 5 em 5 minutos:</div>";
echo "</br></br>";

geraHoras($horariosPreenchidos);

//geraGrafico($matrizFinal, $grafico);

function search( $haystack, $needle, $index = NULL ) {

    if( is_null( $haystack ) ) {
        return -1;
    }
    $arrayIterator = new \RecursiveArrayIterator( $haystack );
    $iterator = new \RecursiveIteratorIterator( $arrayIterator );

    while( $iterator -> valid() ) {
        if( ( ( isset( $index ) and ( $iterator -> key() == $index ) ) or
            ( ! isset( $index ) ) ) and ( $iterator -> current() == $needle ) ) {
            return $arrayIterator -> key();
        }
        $iterator -> next();
    }
    return -1;
}

/**
 * Matriz com os horários de oracao e a quantidades de 5 min
 * @param type $inicio_oracao
 * @param type $fim_oracao
 * @return array
 */
function montaMatrizLotes($inicio_oracao, $fim_oracao){

    $matriz = array();
    //percorrer os horarios de oracao e montrar a matriz de loteamentos (5min)
    for ($i=0; $i < count($inicio_oracao); $i++){

        $ini = $inicio_oracao[$i];
        $fim = $fim_oracao[$i];
        $tempoTotal = tempoDeOracaoMin($ini, $fim);
        $lotes = tempoDeOracaoLotes($tempoTotal);

        $loteamento = 0;
        for($j=1; $j <= $lotes; $j++){
            $loteamento++;
        }
        array_push($matriz, $loteamento);
    }
    return $matriz;
}

function montaMatrizHorarios() {
    $master = array();
    for($i=0; $i<=1435; $i++){
        if(($i%5)==0){
            $h = mktime(00, $i);
            array_push($master, date('H:i', $h));
        }
    }
    return $master;
}

function montaMatrizFinal() {
    $master = array();
    for($i=0; $i<=1435; $i++){
        if(($i%5)==0){
            array_push($master, 0);
        }
    }
    return $master;
}

function preencheHorario($matrizHorarios, $matrizLotes, $matrizFinal, $inicio_oracao) {
    for($i=0; $i<count($inicio_oracao); $i++){
        $var = 0;
        $indice = search($matrizHorarios, $inicio_oracao[$i]);
        for($j=0; $j < $matrizLotes[$i]; $j++){
            $var = $matrizFinal[$indice+$j];
            $var++;
            $matrizFinal[$indice+$j] = $var;
        }
    }
    return $matrizFinal;
}

/**
 * Retorna o tempo de oração em minutos
 * @param string $ini Horário inicial de oração
 * @param string $fim Horário final de oração
 * @return data em minutos
 */
function tempoDeOracaoMin($ini, $fim) {
    $horaFim = converteStringParaData($fim);
    $horaInicio = converteStringParaData($ini);
    return ($horaFim - $horaInicio)/60;
}

/**
 * Retorna a entrada de hora da planilha convertida em mktime
 * @param string $hora
 * @return data
 */
function converteStringParaData($hora) {
    $thora = explode(":", $hora);
    $nhora = mktime($thora[0], $thora[1]);
    return $nhora;
}

/**
 * Retorna o tempo de oração em Lotes de 5 min
 * @param data $tempoTotal
 * @return int
 */
function tempoDeOracaoLotes($tempoTotal) {
    return $tempoTotal/5;
}

/**
 * Função para gerar as horas do relógio de oração
 * @param type $teste
 */
function geraHoras($matrizFinal) {
    echo '<table>';
    for($i=0; $i<=287; $i++){
        $var = 0;
        $l = $i*5; //de 5 em 5
        echo "<tr>";
            $h = date('H:i', mktime(00, $l));
            if($matrizFinal[$i] == 0){
                echo "<td class='semninguem'>" . $h . " " ."</td>";
            } else {
                echo "<td class='comalguem'>" . $h . " " ."</td>";
            }

            for($k=1; $k<=$matrizFinal[$i]; $k++){
                echo "<td class='comalguem'>";
                if($k == $matrizFinal[$i]){
                    echo $k;
                }
                echo "</td>";
            }
        echo "</tr>";
    }
    echo "</table>";
    //print_r($matrizFinal);

}

function geraHoraEmHora($matrizFinal) {
    echo '<table>';
    //$grafico = array();
    for($i=0; $i<24; $i++){

        $l = $i*60; $parcial = 0;
        $h = date('H:i', mktime(00, $l));

        echo "<tr>";
            echo "<td class='comalguem'>" . $h . " " ."</td>";

            $j = $i*12;
            $y = $j+12;
            for($j, $d=0; $j<$y; $j++, $d++){
                $parcial += $matrizFinal[$j];
            }
            $media = $parcial/12;
            $mediaInt = (int)$media;
            for($k=0; $k<=$mediaInt; $k++){
                echo "<td style='width:".$media."%' class='comalguemporcento'>";
                if($k == $mediaInt){
                    $media = number_format($media, 2, ',', ' ');
                    echo "<div style='text-align: right;'>" . $media . "%</div>";
                    //array_push($grafico, $media);
                }
                echo "</td>";
            }
        echo "</tr>";
    }
    echo "</table>";
    //print_r($matrizFinal);
    //print_r($grafico);
}

?>

    <style>
    body {
        width: 100%;
    }
    table, th, td {
        width: 100%;
        border-bottom: 1px solid #dddddd;
        border-collapse: collapse;
        font-weight: bold;
    }
    th, td {
        text-align: left;
        width: 10px;
    }
    .total {
        font-size: 25px;
        font-weight: bold;
        text-align: center;
    }
    .total2 {
        font-size: 22px;
        font-weight: bold;
    }
    .semninguem {
        color: red;
        background: rgba(255,0,0,0.19);
    }
    .comalguem {
        color: #ffffff;
        background: rgba(0,128,0,0.39);
    }
    .comalguemporcento {
        color: #FFFFFF;
        background: green;
    }
    #chart_div {
        width: 100%; height: 500px;
    }
    </style>

    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);
      
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Horário', 'Pessoas'],<?php echo $gfc; ?>
        ]);

        var options = {
          title: 'Pessoas inscritas - Média por hora',
          hAxis: {title: 'Média de pessoas em oração',  titleTextStyle: {color: '#333'}},
          vAxis: {minValue: 0}
        };

        var chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
    
</body>
</html>