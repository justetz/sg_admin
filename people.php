<?php
require_once 'includes/auth.php';
require_once 'includes/api.php';
require_once 'includes/helpers.php';

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

$pageTitle = "People &amp; Memberships";

$people = json_decode(file_get_contents($API_BASE . "api/people?sort=rcsId"), true);
$positions = json_decode(file_get_contents($API_BASE . "api/positions?sort=-presidingOfficer,name"), true);

$peopleHeaderRow = '<tr>
        <th width="20%">Name</th>
        <th width="45%">Current Position(s)</th>
        <th width="23%">Email</th>
        <th></th>
    </tr>';

$currentPresidingRows = "";
$currentPeopleRows = "";
$pastPeopleRows = "";

foreach($people as $person) {
    $currentPositions = "";
    $currentPresidingPositions = "";
    $pastPositions = "";

    $rowBefore  = "<tr>";
    $rowBefore .= "<td>$person[name]</td>";
    $rowBefore .= "<td>";

    foreach($person['memberships'] as $m) {
        if($m['current']) {
            if($m['position']['presidingOfficer']) {
                if(strlen($currentPresidingPositions) > 0) {
                    $currentPresidingPositions .= ', ';
                }
                $currentPresidingPositions .= $m['name'];
            } else {
                if(strlen($currentPositions) > 0) {
                    $currentPositions .= ", ";
                }
                $currentPositions .= $m['name'];
            }
        } else {
            if(strlen($pastPositions) > 0) {
                $pastPositions .= ", ";
            }
            $pastPositions .= $m['name'];
        }
    }

    $rowAfter  = "</td>";

    $rowAfter .= "<td><a href='mailto:$person[rcsId]@rpi.edu'>$person[rcsId]@rpi.edu</a></td>";
    $rowAfter .= "<td><a href='person.php?rcsId=$person[rcsId]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a></td>";
    $rowAfter .= "</tr>";

    if(strlen($currentPresidingPositions) > 0) {
        $currentPresidingRows .= $rowBefore . $currentPresidingPositions . $rowAfter;
    }

    if(strlen($currentPositions) > 0 && strlen($currentPresidingPositions) == 0) {
        $currentPeopleRows .= $rowBefore . $currentPositions . $rowAfter;
    }

    if(strlen($pastPositions) > 0 && strlen($currentPositions) == 0 && strlen($currentPresidingPositions) == 0) {
        $pastPeopleRows .= $rowBefore . $pastPositions . $rowAfter;
    }
}
?>
<!doctype html>
<html lang="en">
<?php include_once 'partials/head.php' ?>
<body>
    <div class="wrapper">
        <?php include_once 'partials/sidebar.php' ?>
        <div class="main-panel">
            <?php include_once 'partials/nav.php' ?>
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="content content-even">
                                    <ol class="breadcrumb">
                                        <li class="active">People & Memberships</li>
                                    </ol>
                                </div>
                            </div>
                            <div class="card">
                                <div class="content content-even">
                                    <ul class="nav nav-pills">
                                        <li role="presentation" <?=(!isset($_GET['section']) || $_GET['section'] == 'current') ? 'class="active"' : ''?>>
                                            <a href="<?=toggleGetParam('section','current')?>">Current People</a>
                                        </li>
                                        <li role="presentation" <?=(isset($_GET['section']) && $_GET['section'] == 'past') ? 'class="active"' : ''?>>
                                            <a href="<?=toggleGetParam('section','past')?>">Past People</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <?php if(!isset($_GET['section']) || $_GET['section'] == 'current') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Current Presiding Officers</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <?=$peopleHeaderRow?>
                                            </thead>
                                            <tbody>
                                                <?=$currentPresidingRows?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Current People</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <?=$peopleHeaderRow?>
                                            </thead>
                                            <tbody>
                                                <?=$currentPeopleRows?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php } else if(isset($_GET['section']) && $_GET['section'] == 'past') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Past People</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <?=$peopleHeaderRow?>
                                            </thead>
                                            <tbody>
                                                <?=$pastPeopleRows?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="col-md-4">
                            <?=generateAddMembershipCard('create_membership', null, null, null)?>
                        </div>
                    </div>
                </div>
            </div>
        <?php require_once 'partials/footer.php' ?>
        </div>
    </div>
    <?php require_once 'partials/scripts.php' ?>
</body>
</html>