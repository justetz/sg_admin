<?php
require_once 'includes/auth.php';
require_once 'includes/api.php';
require_once 'includes/helpers.php';

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

$alertsToDisplay = "";

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    $data = $_POST;
    $transaction = $data['transaction'];
    unset($data['transaction']);

    if ($transaction == 'update_session') {
        $result = Sessions::update($data);
    } else if($transaction == 'create_membership') {
        $result = Memberships::create($data);
    } else if($transaction == 'create_subbody') {
        $result = Subbodies::create($data);
    } else if($transaction == 'create_meeting') {
        $result = Meetings::create($data);
    } else if($transaction == 'delete_meeting') {
        $result = Meetings::delete($data);
    } else {
        $result = false;
    }
} else if(!isset($_GET['uniqueId']) || !isset($_GET['bodyUniqueId'])) {
    header('location: ./sessions.php');
    exit;
} else {
    $result = false;
}

$session = Sessions::getEntry("$_GET[bodyUniqueId]/$_GET[uniqueId]");

$subbodies = Subbodies::read([
    "bodyUniqueId" => $_GET['bodyUniqueId'],
    "sessionUniqueId" => $_GET['uniqueId'],
]);

$memberships = Memberships::read([
    "bodyUniqueId" => $_GET['bodyUniqueId'],
    "sessionUniqueId" => $_GET['uniqueId'],
    "sort" => "name,personRcsId",
]);

$positions = Positions::read([
    "bodyUniqueId" => $_GET['bodyUniqueId'],
    "sort" => "-presidingOfficer,name",
]);

$meetings = Meetings::read([
    "bodyUniqueId" => $_GET['bodyUniqueId'],
    "sessionUniqueId" => $_GET['uniqueId'],
]);

$pageTitle = "Manage Session: " . $session['name'];

$officersTable = "";
$votingMembersTable = "";
$otherMembersTable = "";

$officersCount = 0;
$votingMembersCount = 0;
$otherMembersCount = 0;
$membershipCount = 0;

$membershipHeaderRow = '<tr>
    <th width="20%">Name</th>
    <th width="40%">Position</th>
    <th width="20%">Term</th>
    <th width="20%"></th>
</tr>';

foreach($memberships as $m) {
    $row  = "<tr>";
    $row .= "<td>" . $m['person']['name'] . "</td>";
    $row .= "<td><a href='position.php?id=$m[positionId]'>$m[name]" . ($m['position']['presidingOfficer'] ? " <span class='text-muted'>(Presiding Officer)</span>" : "") . "</a></td>";
    $row .= "<td>$m[term]</td>";
//    $row .= "<td><a href='mailto:" . $m['person']['rcsId'] . "@rpi.edu'>" . $m['person']['rcsId'] . "@rpi.edu</a></td>";
    $row .= "<td><a href='/person.php?rcsId=$m[personRcsId]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a></td>";
    $row .= "</tr>";

    if($m['current']) {
        if($m['position']['officer']) {
            $officersTable .= $row;
            $officersCount++;
        } else if($m['position']['voting']) {
            $votingMembersTable .= $row;
            $votingMembersCount++;
        } else {
            $otherMembersTable .= $row;
            $otherMembersCount++;
        }

        $membershipCount++;
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
                                        <li><a href="sessions.php">Bodies &amp; Sessions</a> <?=$alertsToDisplay?></li>
                                        <li><a href="body.php?uniqueId=<?=$session['bodyUniqueId']?>"><?=$session['body']['name']?></a></li>
                                        <li class="active"><?=$session['name']?></li>
                                    </ol>
                                </div>
                            </div>
                            <div class="card">
                                <div class="content content-even">
                                    <ul class="nav nav-pills">
                                        <li role="presentation" <?=(!isset($_GET['section']) || $_GET['section'] == 'membership') ? 'class="active"' : ''?>>
                                            <a href="<?=toggleGetParam('section','membership')?>">Membership</a>
                                        </li>
                                        <li role="presentation" <?=(isset($_GET['section']) && $_GET['section'] == 'subbodies') ? 'class="active"' : ''?>>
                                            <a href="<?=toggleGetParam('section','subbodies')?>">Sub-Bodies</a>
                                        </li>
                                        <li role="presentation" <?=(isset($_GET['section']) && $_GET['section'] == 'meetings') ? 'class="active"' : ''?>>
                                            <a href="<?=toggleGetParam('section','meetings')?>">Meetings</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <?php if(!isset($_GET['section']) || $_GET['section'] == 'membership') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Current Officers</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <?=$membershipHeaderRow?>
                                            </thead>
                                            <tbody>
                                                <?=$officersTable?>
                                                <?=$officersCount == 0 ? '<td colspan="4" class="text-center"><em class="text-muted">No officers are currently active in this session!</em></td>' : ''?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Current Voting Members</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <?=$membershipHeaderRow?>
                                            </thead>
                                            <tbody>
                                                <?=$votingMembersTable?>
                                                <?=$votingMembersCount == 0 ? '<td colspan="4" class="text-center"><em class="text-muted">No voting members are currently active in this session!</em></td>' : ''?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php if($otherMembersCount > 0) { ?>
                                    <div class="card">
                                        <div class="header">
                                            <h4 class="title">Current Other Members</h4>
                                        </div>
                                        <div class="content table-responsive table-full-width">
                                            <table class="table table-hover table-striped">
                                                <thead>
                                                    <tr>
                                                       <?=$membershipHeaderRow?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?=$otherMembersTable?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } else if(isset($_GET['section']) && $_GET['section'] == 'subbodies') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Sub-Bodies</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Presiding Officer</th>
                                                    <th>Unique Identifier</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    foreach($subbodies as $sub) {
                                                        echo "<tr>";
                                                        echo "<td>$sub[name]</td>";
                                                        echo "<td>";
                                                        if(isset($sub['presidingOfficerPositionId'])) {
                                                            if(count($sub['presidingOfficerPosition']['memberships']) == 0) {
                                                                echo "<em>vacant</em>";
                                                            } else {
                                                                echo "<a href='/person.php?rcsId=" . $sub['presidingOfficerPosition']['memberships'][0]['personRcsId'] . "'>";
                                                                echo $sub['presidingOfficerPosition']['memberships'][0]['person']['name'];
                                                                echo "</a>";
                                                            }
                                                        } else {
                                                            echo "<em>no presiding position</em>";
                                                        }
                                                        echo "</td>";
                                                        echo "<td><span class='text-muted'>$session[fullUniqueId]/</span>$sub[uniqueId]</td>";
                                                        echo "<td><a href='/subbody.php?bodyUniqueId=$sub[bodyUniqueId]&sessionUniqueId=$sub[sessionUniqueId]&uniqueId=$sub[uniqueId]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a></td>";
                                                        echo "</tr>";
                                                    }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php } else if(isset($_GET['section']) && $_GET['section'] == 'meetings') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Meetings</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Meeting</th>
                                                    <th>Date</th>
                                                    <th>Location</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    foreach($meetings as $m) {
                                                        echo "<tr>";
                                                        echo "<td>$session[name] - Meeting #$m[meetingNum]</td>";
                                                        echo "<td>$m[displayDate]</td>";
                                                        echo "<td>$m[location]</td>";
                                                        echo "<td></td>";
                                                        echo "<td>
                                                            <form class='form-inline' method='post' action='$_SERVER[REQUEST_URI]' onsubmit='return confirm(\"Are you sure you want to delete $session[name] - Meeting #$m[meetingNum]?\");'>
                                                                <a href='/meeting.php?bodyUniqueId=$m[bodyUniqueId]&sessionUniqueId=$m[sessionUniqueId]&meetingNum=$m[meetingNum]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a>
                                                                
                                                                <input type=\"hidden\" name=\"transaction\" value=\"delete_meeting\">
                                                                <input type=\"hidden\" name=\"sessionUniqueId\" value=\"$_GET[uniqueId]\">
                                                                <input type=\"hidden\" name=\"bodyUniqueId\" value=\"$_GET[bodyUniqueId]\">
                                                                <input type=\"hidden\" name=\"meetingNum\" value=\"$m[meetingNum]\">
                                                                <button class=\"btn btn-default btn-xs\" type='submit'><span class='fa fa-trash'></span></button>
                                                            </form>
                                                        </td>";
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
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Session Membership Details</h4>
                                </div>
                                <div class="content table-responsive table-full-width">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td>Total Membership</td>
                                                <td><?=$membershipCount?></td>
                                            </tr>
                                            <tr>
                                                <td>Officer Membership</td>
                                                <td><?=$officersCount?></td>
                                            </tr>
                                            <tr>
                                                <td>Voting Membership</td>
                                                <td><?=$votingMembersCount?></td>
                                            </tr>
                                            <tr>
                                                <td>Simple Majority</td>
                                                <td><?=ceil($votingMembersCount*(1/2))?></td>
                                            </tr>
                                            <tr>
                                                <td>Three-Fifths (3/5) Majority</td>
                                                <td><?=ceil($votingMembersCount*(3/5))?></td>
                                            </tr>
                                            <tr>
                                                <td>Two-Thirds (2/3) Majority</td>
                                                <td><?=ceil($votingMembersCount*(2/3))?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Edit Session Settings</h4>
                                </div>
                                <div class="content">
                                    <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Body Identifier <?=$requiredIndicator?></label>
                                                    <input type="text" class="form-control" disabled placeholder="Company" value="<?=$session['bodyUniqueId']?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Unique Identifier <?=$requiredIndicator?></label>
                                                    <input type="text" class="form-control" disabled placeholder="Company" value="<?=$session['uniqueId']?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Session Name <?=$requiredIndicator?></label>
                                                    <input type="text" name="name" class="form-control" placeholder="Session Name (e.g. 'Student Government')" value="<?=$session['name']?>">
                                                </div>
                                            </div>
                                        </div>

                                        <input type="hidden" name="transaction" value="update_session">
                                        <input type="hidden" name="uniqueId" value="<?=$_GET['uniqueId']?>">
                                        <input type="hidden" name="bodyUniqueId" value="<?=$_GET['bodyUniqueId']?>">
                                        <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Update Body</button>
                                        <div class="clearfix"></div>
                                    </form>
                                </div>
                            </div>
                            <?php if (!isset($_GET['section']) || $_GET['section'] == 'membership') { ?>
                                <?=generateAddMembershipCard('create_membership', $_GET['bodyUniqueId'], $_GET['uniqueId'], null)?>
                            <?php } else if (false) {//(!isset($_GET['section']) || $_GET['section'] == 'membership') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Add Member</h4>
                                    </div>
                                    <div class="content">
                                        <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                            <div class="form-group">
                                                <label>RCS ID <?=$requiredIndicator?></label>
                                                <input type="text" name="personRcsId" class="form-control" placeholder="RCS ID">
                                            </div>
                                            <div class="form-group">
                                                <label>Position <?=$requiredIndicator?></label>
                                                <select name="positionId" class="form-control">
                                                    <option disabled selected>Select a position...</option>
                                                    <?php
                                                        $presidingOfficerOptions = "";
                                                        $officerOptions = "";
                                                        $votingOptions = "";

                                                        $presidingOfficerCount = 0;
                                                        $officerCount = 0;
                                                        $votingCount = 0;

                                                        foreach($positions as $p) {
                                                            $option = "<option value='$p[id]'>$p[name]</option>";

                                                            if($p['presidingOfficer']) {
                                                                $presidingOfficerOptions .= $option;
                                                                $presidingOfficerCount++;
                                                            } else if($p['officer']) {
                                                                $officerOptions .= $option;
                                                                $officerCount++;
                                                            } else if($p['voting']) {
                                                                $votingOptions .= $option;
                                                                $votingCount++;
                                                            }
                                                        }

                                                        if($presidingOfficerCount > 0) {
                                                            echo "<optgroup label='Presiding Officer" . ($presidingOfficerCount > 1 ? 's' : '') ."'>$presidingOfficerOptions</optgroup>";
                                                        }

                                                        if($officerCount > 0) {
                                                            echo "<optgroup label='Officer" . ($officerCount > 1 ? 's' : '') ."'>$officerOptions</optgroup>";
                                                        }

                                                        if($votingCount > 0) {
                                                            echo "<optgroup label='Voting Position" . ($votingCount > 1 ? 's' : '') ."'>$votingOptions</optgroup>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Membership-Specific Title</label>
                                                <input type="text" name="name" class="form-control" placeholder="Optional">
                                            </div>
                                            <div class="form-group">
                                                <label>Start Date <?=$requiredIndicator?></label>
                                                <input type="date" name="startDate" class="form-control" placeholder="YYYY-MM-DD" value="<?=date('Y-m-d')?>">
                                            </div>
                                            <div class="form-group">
                                                <label>End Date</label>
                                                <input type="date" name="endDate" class="form-control" placeholder="YYYY-MM-DD">
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="yearOnly" data-toggle="checkbox"> Year Only
                                                </label>
                                            </div>

                                            <input type="hidden" name="transaction" value="create_membership">
                                            <input type="hidden" name="sessionUniqueId" value="<?=$_GET['uniqueId']?>">
                                            <input type="hidden" name="bodyUniqueId" value="<?=$_GET['bodyUniqueId']?>">
                                            <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Add Member</button>
                                            <div class="clearfix"></div>
                                        </form>
                                    </div>
                                </div>
                            <?php } else if(isset($_GET['section']) && $_GET['section'] == 'subbodies') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Create New Sub-Body</h4>
                                    </div>
                                    <div class="content">
                                        <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                            <div class="form-group">
                                                <label>Unique Identifier <?=$requiredIndicator?></label>
                                                <div class="input-group">
                                                    <span class="input-group-addon" id="subbody-unique-id-prefix"><?=$session['fullUniqueId']?>/</span>
                                                    <input type="text" name="uniqueId" class="form-control" placeholder="uniqueId" aria-describedby="subbody-unique-id-prefix">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Sub-Body Name <?=$requiredIndicator?></label>
                                                <input type="text" name="name" class="form-control" placeholder="Sub-Body Name">
                                            </div>

                                            <input type="hidden" name="transaction" value="create_subbody">
                                            <input type="hidden" name="sessionUniqueId" value="<?=$_GET['uniqueId']?>">
                                            <input type="hidden" name="bodyUniqueId" value="<?=$_GET['bodyUniqueId']?>">
                                            <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Create Sub-Body</button>
                                            <div class="clearfix"></div>
                                        </form>
                                    </div>
                                </div>
                            <?php } else if(isset($_GET['section']) && $_GET['section'] == 'meetings') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Add Meeting</h4>
                                    </div>
                                    <div class="content">
                                        <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                            <div class="form-group">
                                                <label>Meeting Date <?=$requiredIndicator?></label>
                                                <input type="date" name="date" class="form-control">
                                            </div>
                                            <div class="form-group">
                                                <label>Meeting Location <?=$requiredIndicator?></label>
                                                <input type="text" name="location" class="form-control" placeholder="Location">
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="copyPreviousLocation" data-toggle="checkbox"> Copy Previous Meeting's Location
                                                </label>
                                            </div>

                                            <input type="hidden" name="transaction" value="create_meeting">
                                            <input type="hidden" name="sessionUniqueId" value="<?=$_GET['uniqueId']?>">
                                            <input type="hidden" name="bodyUniqueId" value="<?=$_GET['bodyUniqueId']?>">
                                            <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Add Meeting</button>
                                            <div class="clearfix"></div>
                                        </form>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once 'partials/footer.php' ?>
        </div>
    </div>
    <?php require_once 'partials/scripts.php' ?>
    <?=buildMessage($result, $_POST)?>

</body>
</html>