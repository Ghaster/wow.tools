<?php
require_once("../inc/header.php");
?>

<link href="/db/css/style.css?v=<?=filemtime("/var/www/wow.tools/db/css/style.css")?>" rel="stylesheet"><?php
if (!empty($_GET['id'])) {
    $q = $pdo->prepare("SELECT json FROM wowdata.creatures WHERE id = ?");
    $q->execute([$_GET['id']]);

    $creature = json_decode($q->fetch(PDO::FETCH_ASSOC)['json'], true);
    if (empty($creature)) {
        die("Creature not found!");
    }

    if (!empty($creature['CreatureDisplayInfoID[0]'])) {
        $cdi = $pdo->prepare("SELECT filedataid FROM wowdata.creaturemodeldata WHERE id IN (SELECT ModelID FROM wowdata.creaturedisplayinfo WHERE ID = ?)");
        $cdi->execute([$creature['CreatureDisplayInfoID[0]']]);
        $cdirow = $cdi->fetch(PDO::FETCH_ASSOC);
        if (!empty($cdirow)) {
            $filedataid = $cdirow['filedataid'];
        }
    }

    ?>
<div class='container-fluid'>
<h2><?=$creature['Name[0]']?> <small>&lt;<?=$creature['Title']?>&gt;</small></h2>
    <?php if (!empty($filedataid)) { ?>
<iframe width='950' height='700' src='https://wow.tools/mv/?filedataid=<?=$filedataid?>&type=m2&embed=true'></iframe>
    <?php } ?>
</div>
    <?php
    print_r($creature);
    die();
}
?>
<div class='container-fluid'>
    <h3>Creatures</h3>
    <table class='table table-striped' id='creatures'>
        <thead><tr><th style='width: 100px'>ID</th><th>Name</th><th style='width: 120px'>First seen build</th><th style='width: 120px'>Last update build</th></tr>
    </table>
    <div id="creatures_preview" style="display: block;"></div>
</div>
<script type='text/javascript'>
var Elements = {};

(function() {
var searchHash = location.hash.substr(1),
searchString = searchHash.substr(searchHash.indexOf('search=')).split('&')[0].split('=')[1];

if(searchString != undefined && searchString.length > 0){
    searchString = decodeURIComponent(searchString);
}

var page = (parseInt(searchHash.substr(searchHash.indexOf('page=')).split('&')[0].split('=')[1], 10) || 1) - 1;
var sortCol = searchHash.substr(searchHash.indexOf('sort=')).split('&')[0].split('=')[1];
if(!sortCol){
    sortCol = 0;
}

var sortDesc = searchHash.substr(searchHash.indexOf('desc=')).split('&')[0].split('=')[1];
if(!sortDesc){
    sortDesc = "asc";
}

Elements.table = $('#creatures').DataTable({
    "processing": true,
    "serverSide": true,
    "search": { "search": searchString },
    "ajax": "/db/creature_api.php",
    "pageLength": 25,
    "displayStart": page * 25,
    "autoWidth": false,
    "pagingType": "input",
    "orderMulti": false,
    "order": [[sortCol, sortDesc]]
});

$('#creatures').on( 'draw.dt', function () {
    var currentSearch = encodeURIComponent($("#creatures_filter label input").val());
    var currentPage = $('#creatures').DataTable().page() + 1;

    var sort = $('#creatures').DataTable().order();
    var sortCol = sort[0][0];
    var sortDir = sort[0][1];

    var url = "search=" + currentSearch + "&page=" + currentPage + "&sort=" + sortCol +"&desc=" + sortDir;

    window.location.hash = url;

    $("[data-toggle=popover]").popover();
});

$('#creatures').on('click', 'tbody tr td', function() {
    $("#creatures_preview").html("Loading..");
    var data = Elements.table.row($(this).parent()).data();
    loadCreatureInfo(data[0])
    .then(data => {
        renderCreatureInfo(data); // JSON data parsed by `response.json()` call
    });

    $(".selected").removeClass("selected");
    $(this).parent().addClass('selected');
});

}());

async function loadCreatureInfo(id){
    const response = await fetch("/db/creature_api.php?id=" + id, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    });
    return response.json();
}

function renderCreatureInfo(info){
    let result = "";
    result += "<h2>" + info["Name[0]"];

    if(info["Title"] != ""){
        result += "<small>&lt;" + info['Title'] + "&gt;</small>"
    }

    result += "</h2>";
    result += "<iframe width='950' height='700' src='https://wow.tools/mv/?filedataid=" + info["filedataid"] + "&type=m2&embed=true'></iframe><div id='tableContainer'><table class='table table-sm table-striped table-hover' id='creatureInfoTable'></table></div>";

    $("#creatures_preview").html(result);

    Object.keys(info).forEach(function (key) {
        const val = info[key];
        if(val != ""){
            $("#creatureInfoTable").append("<tr><td>" + key + "</td><td>" + val + "</td></tr>");
        }

    });
}
function locationHashChanged(event) {
    var searchHash = location.hash.substr(1),
    searchString = searchHash.substr(searchHash.indexOf('search=')).split('&')[0].split('=')[1];

    if(searchString != undefined && searchString.length > 0){
        searchString = decodeURIComponent(searchString);
    }

    if($("#creatures_filter label input").val() != searchString){
        $('#creatures').DataTable().search(searchString).draw(false);
    }
    var page = (parseInt(searchHash.substr(searchHash.indexOf('page=')).split('&')[0].split('=')[1], 10) || 1) - 1;
    if($('#creatures').DataTable().page() != page){
        $('#creatures').DataTable().page(page).draw(false);
    }

    var sortCol = searchHash.substr(searchHash.indexOf('sort=')).split('&')[0].split('=')[1];
    if(!sortCol){
        sortCol = 0;
    }

    var sortDesc = searchHash.substr(searchHash.indexOf('desc=')).split('&')[0].split('=')[1];
    if(!sortDesc){
        sortDesc = "asc";
    }

    var curSort = $('#creatures').DataTable().order();
    if(sortCol != curSort[0][0] || sortDesc != curSort[0][1]){
        $('#creatures').DataTable().order([sortCol, sortDesc]).draw(false);
    }
}

window.onhashchange = locationHashChanged;
</script>