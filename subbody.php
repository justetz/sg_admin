<?php
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

date_default_timezone_set('America/New_York');

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

blockUnauthorized();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    $data = $_POST;
    $transaction = $data['transaction'];
    unset($data['transaction']);


    if($transaction == 'delete_subbody') {
        $info = Subbodies::getEntry("$data[bodyUniqueId]/$data[sessionUniqueId]/$data[uniqueId]");
        $result = Subbodies::delete($data);

        setcookie('SGMS-Success-Message', "The sub-body entitled $info[name] was successfully deleted.", time() + 30);

        header("location: ./session.php?bodyUniqueId=$data[bodyUniqueId]&uniqueId=$data[sessionUniqueId]&section=subbodies");
        exit;
    } else {
        $result = false;
    }
} else if(!isset($_GET['uniqueId']) && !isset($_GET['sessionUniqueId']) && !isset($_GET['bodyUniqueId'])) {
    header('location: ./sessions.php');
    exit;
} else if(!isset($_GET['uniqueId']) && !isset($_GET['sessionUniqueId'])) {
    header("location: ./body.php?bodyUniqueId=$_GET[bodyUniqueId]");
} else if(!isset($_GET['uniqueId'])) {
    header("location: ./session.php?bodyUniqueId=$_GET[bodyUniqueId]&uniqueId=$_GET[sessionUniqueId]");
}

$subbody = Subbodies::getEntry("$_GET[bodyUniqueId]/$_GET[sessionUniqueId]/$_GET[uniqueId]");
$memberships = []; //json_decode(file_get_contents($API_BASE . "api/subbody_memberships"), true);
$meetings = []; //json_decode(file_get_contents($API_BASE . "api/subbody_meetings"), true);

$pageTitle = "Manage Sub-Body: " . $subbody['name'];

$membersTable = "";

$membershipCount = 0;
$officersCount = isset($subbody['presidingOfficerPositionId']) ? count($subbody['presidingOfficerPosition']['memberships']) : 0;

$membershipHeaderRow = '<tr>
    <th width="20%">Name</th>
    <th width="40%">Position</th>
    <th width="20%">Term</th>
    <th width="20%">Email</th>
</tr>';

foreach($memberships as $m) {
    $row  = "<tr>";
    $row .= "<td>" . $m['person']['name'] . "</td>";
    $row .= "<td>$m[name]" . ($m['position']['presidingOfficer'] ? " <span class='text-muted'>(Presiding Officer)</span>" : "") . "</td>";
    $row .= "<td>$m[term]</td>";
    if($m['person']['email']) {
        $row .= "<td><a href='mailto:" . $m['person']['email'] . "'>" . $m['person']['email'] . "</a></td>";
    } else {
        $row .= "<td><a href='mailto:" . $m['person']['rcsId'] . "@rpi.edu'>" . $m['person']['rcsId'] . "@rpi.edu</a></td>";
    }
    $row .= "</tr>";

    if($m['current']) {
        $membersTable .= $row;
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
                                        <li><a href="sessions.php">Bodies &amp; Sessions</a></li>
                                        <li><a href="body.php?uniqueId=<?=$subbody['bodyUniqueId']?>"><?=$subbody['session']['body']['name']?></a></li>
                                        <li><a href="session.php?bodyUniqueId=<?=$subbody['bodyUniqueId']?>&uniqueId=<?=$subbody['sessionUniqueId']?>"><?=$subbody['session']['name']?></a></li>
                                        <li class="active"><?=$subbody['name']?></li>
                                    </ol>
                                </div>
                            </div>
                            <div class="card">
                                <div class="content content-even">
                                    <ul class="nav nav-pills">
                                        <li role="presentation" <?=(!isset($_GET['section']) || $_GET['section'] == 'membership') ? 'class="active"' : ''?>>
                                            <a href="<?=toggleGetParam('section','membership')?>">Membership</a>
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
                                        <h4 class="title">Current Members</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <?=$membershipHeaderRow?>
                                            </thead>
                                            <tbody>
                                                <?php if(isset($subbody['presidingOfficerPositionId'])) { ?>
                                                    <?php
                                                    if($subbody['presidingOfficerPosition']['memberships'][0]['person']['email']) {
                                                        $presidingOfficerEmail = $subbody['presidingOfficerPosition']['memberships'][0]['person']['email'];
                                                    } else {
                                                        $presidingOfficerEmail = $subbody['presidingOfficerPosition']['memberships'][0]['person']['rcsId'] . '@rpi.edu';
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td><?=$subbody['presidingOfficerPosition']['memberships'][0]['person']['name']?></td>
                                                        <td><?=$subbody['presidingOfficerPosition']['memberships'][0]['name']?> <span class='text-muted'>(Presiding Officer)</span></td>
                                                        <td><?=$subbody['presidingOfficerPosition']['memberships'][0]['term']?></td>
                                                        <td><a href="mailto:<?=$presidingOfficerEmail?>"><?=$presidingOfficerEmail?></a></td>
                                                    </tr>
                                                <?php } ?>
                                                <?=$membersTable?>
                                                <?=(!isset($subbody['presidingOfficerPositionId']) && $membershipCount == 0) ? '<td colspan="4" class="text-center"><em class="text-muted">No members are currently active in this session!</em></td>' : ''?>
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
                                                        echo "<td><a href='/meeting.php?bodyUniqueId=$m[bodyUniqueId]&sessionUniqueId=$m[sessionUniqueId]&meetingNum=$m[meetingNum]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a></td>";
                                                        echo "</tr>";
                                                    }

                                                    if(count($meetings) == 0) {
                                                        echo "<tr><td colspan='4' class='text-center'><em class='text-muted'>No meetings have been created for this sub-body!</em></td></tr>";
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
                                    <h4 class="title">Sub-Body Membership Details</h4>
                                </div>
                                <div class="content table-responsive table-full-width">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td>Total Membership</td>
                                                <td><?= ($membershipCount + $officersCount) ?></td>
                                            </tr>
                                            <tr>
                                                <td>Officer Membership</td>
                                                <td><?=$officersCount?></td>
                                            </tr>
                                            <tr>
                                                <td>Voting Membership</td>
                                                <td><?=$membershipCount?></td>
                                            </tr>
                                            <tr>
                                                <td>Simple Majority</td>
                                                <td><?=ceil($membershipCount*(1/2))?></td>
                                            </tr>
                                            <tr>
                                                <td>Three-Fifths (3/5) Majority</td>
                                                <td><?=ceil($membershipCount*(3/5))?></td>
                                            </tr>
                                            <tr>
                                                <td>Two-Thirds (2/3) Majority</td>
                                                <td><?=ceil($membershipCount*(2/3))?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Edit Sub-Body Settings</h4>
                                </div>
                                <div class="content">
                                    <form>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Body Identifier <?=$requiredIndicator?></label>
                                                    <input type="text" class="form-control" disabled placeholder="Company" value="<?=$subbody['bodyUniqueId']?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Session Identifier <?=$requiredIndicator?></label>
                                                    <input type="text" class="form-control" disabled placeholder="Company" value="<?=$subbody['sessionUniqueId']?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Sub-Body Identifier <?=$requiredIndicator?></label>
                                                    <input type="text" class="form-control" disabled placeholder="Company" value="<?=$subbody['uniqueId']?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Sub-Body Name <?=$requiredIndicator?></label>
                                                    <input type="text" name="name" class="form-control" placeholder="Sub-Body Name" value="<?=$subbody['name']?>">
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Update Sub-Body</button>
                                    </form>
                                </div>
                                <div class="header">
                                    <hr>
                                    <h4 class="title">Delete Sub-Body</h4>
                                </div>
                                <div class="content">
                                    <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                        <input type="hidden" name="transaction" value="delete_subbody">
                                        <input type="hidden" name="uniqueId" value="<?=$_GET['uniqueId']?>">
                                        <input type="hidden" name="sessionUniqueId" value="<?=$_GET['sessionUniqueId']?>">
                                        <input type="hidden" name="bodyUniqueId" value="<?=$_GET['bodyUniqueId']?>">
                                        <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Delete Sub-Body</button>
                                        <div class="clearfix"></div>
                                    </form>
                                </div>
                            </div>
                            <?php if(!isset($_GET['section']) || $_GET['section'] == 'membership') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Add Member</h4>
                                    </div>
                                    <div class="content">
                                        <form>
                                            <div class="form-group">
                                                <label>RCS ID <?=$requiredIndicator?></label>
                                                <input type="text" name="rcsId" class="form-control" placeholder="RCS ID">
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

                                            <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Add Member</button>
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
                                        <form>
                                            <div class="form-group">
                                                <label>Meeting Date <?=$requiredIndicator?></label>
                                                <input type="date" name="date" class="form-control">
                                            </div>
                                            <div class="form-group">
                                                <label>Meeting Location <?=$requiredIndicator?></label>
                                                <input type="text" name="date" class="form-control" placeholder="Location">
                                            </div>

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