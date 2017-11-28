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
                                        <li role="presentation" <?=(isset($_GET['section']) && $_GET['section'] == 'positions') ? 'class="active"' : ''?>>
                                            <a href="<?=toggleGetParam('section','positions')?>">Positions</a>
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
                            <?php } else if(isset($_GET['section']) && $_GET['section'] == 'positions') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Positions</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Position</th>
                                                    <th>Body</th>
                                                    <th>Is Voting?</th>
                                                    <th>Is Officer?</th>
                                                    <th>Is Presiding Officer?</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $positions = Positions::read([
                                                    "sort" => "name"
                                                ]);

                                                foreach($positions as $p) {
                                                    echo "<tr>";
                                                    echo "<td>$p[name]</td>";
                                                    echo "<td>" . $p['body']['name'] . "</td>";
                                                    echo "<td>" . ($p['voting'] ? "<span class='text-success-readable'>Yes</span>" : "<span class='text-danger-readable'>No</span>") . "</td>";
                                                    echo "<td>" . ($p['officer'] ? "<span class='text-success-readable'>Yes</span>" : "<span class='text-danger-readable'>No</span>") . "</td>";
                                                    echo "<td>" . ($p['presidingOfficer'] ? "<span class='text-success-readable'>Yes</span>" : "<span class='text-danger-readable'>No</span>") . "</td>";
                                                    echo "<td><a href='/position.php?id=$p[id]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a></td>";
                                                    echo "</tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="col-md-4">

                            <?=generateAddMembershipCard('create_membership', null, null, null)?>
<!--                            <div class="card">-->
<!--                                <div class="header">-->
<!--                                    <h4 class="title">Add Membership</h4>-->
<!--                                </div>-->
<!--                                <div class="content">-->
<!--                                    <form>-->
<!--                                        <div class="form-group">-->
<!--                                            <label>RCS ID --><?//=$requiredIndicator?><!--</label>-->
<!--                                            <input type="text" name="rcsId" class="form-control" placeholder="RCS ID">-->
<!--                                        </div>-->
<!--                                        <div class="form-group">-->
<!--                                            <label>Position --><?//=$requiredIndicator?><!--</label>-->
<!--                                            <select name="positionId" class="form-control">-->
<!--                                                <option disabled selected>Select a position...</option>-->
<!--                                                --><?php
//                                                    $presidingOfficerOptions = "";
//                                                    $officerOptions = "";
//                                                    $votingOptions = "";
//
//                                                    $presidingOfficerCount = 0;
//                                                    $officerCount = 0;
//                                                    $votingCount = 0;
//
//                                                    foreach($positions as $p) {
//                                                        $option = "<option value='$p[id]'>$p[name]</option>";
//
//                                                        if($p['presidingOfficer']) {
//                                                            $presidingOfficerOptions .= $option;
//                                                            $presidingOfficerCount++;
//                                                        } else if($p['officer']) {
//                                                            $officerOptions .= $option;
//                                                            $officerCount++;
//                                                        } else if($p['voting']) {
//                                                            $votingOptions .= $option;
//                                                            $votingCount++;
//                                                        }
//                                                    }
//
//                                                    if($presidingOfficerCount > 0) {
//                                                        echo "<optgroup label='Presiding Officer" . ($presidingOfficerCount > 1 ? 's' : '') ."'>$presidingOfficerOptions</optgroup>";
//                                                    }
//
//                                                    if($officerCount > 0) {
//                                                        echo "<optgroup label='Officer" . ($officerCount > 1 ? 's' : '') ."'>$officerOptions</optgroup>";
//                                                    }
//
//                                                    if($votingCount > 0) {
//                                                        echo "<optgroup label='Voting Position" . ($votingCount > 1 ? 's' : '') ."'>$votingOptions</optgroup>";
//                                                    }
//                                                ?>
<!--                                            </select>-->
<!--                                        </div>-->
<!--                                        <div class="form-group">-->
<!--                                            <label>Membership-Specific Title</label>-->
<!--                                            <input type="text" name="name" class="form-control" placeholder="Optional">-->
<!--                                        </div>-->
<!--                                        <div class="form-group">-->
<!--                                            <label>Start Date --><?//=$requiredIndicator?><!--</label>-->
<!--                                            <input type="date" name="startDate" class="form-control" placeholder="YYYY-MM-DD" value="--><?//=date('Y-m-d')?><!--">-->
<!--                                        </div>-->
<!--                                        <div class="form-group">-->
<!--                                            <label>End Date</label>-->
<!--                                            <input type="date" name="endDate" class="form-control" placeholder="YYYY-MM-DD">-->
<!--                                        </div>-->
<!---->
<!--                                        <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Add Member</button>-->
<!--                                        <div class="clearfix"></div>-->
<!--                                    </form>-->
<!--                                </div>-->
<!--                            </div>-->
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