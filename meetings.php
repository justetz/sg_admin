<?php
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

blockUnauthorized();

$pageTitle = "Meetings &amp; Events";
$result = false;

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    $data = $_POST;
    $transaction = $data['transaction'];
    unset($data['transaction']);

    if ($transaction == 'create_meeting') {
        $uniqueIds = explode('/', $data['fullUniqueId'], 2);
        unset($data['fullUniqueId']);

        $meetings = Meetings::read([
            'bodyUniqueId' => $uniqueIds[0],
            'sessionUniqueId' => $uniqueIds[1],
            'sort' => '-meetingNum,-date'
        ]);

        $newMeetingNum = -1;
        for($i = 0; $i < count($meetings); $i++) {
            if($meetings[$i]['date'] <= $data['date']) {
                $newMeetingNum = $meetings[$i]['meetingNum'] + 1;
                break;
            }
        }

        if($newMeetingNum == -1) {
            $newMeetingNum = 1;
        }

        $data['bodyUniqueId'] = $uniqueIds[0];
        $data['sessionUniqueId'] = $uniqueIds[1];
        $data['meetingNum'] = $newMeetingNum;

        $result = Meetings::create($data);

        if($i > 0) {
            for($j = ($i - 1); $j >= 0; $j--) {
                $meetings[$j]['meetingNum'] = $meetings[$j]['meetingNum'] + 1;

                Meetings::update($meetings[$j]);
            }
        }
    } else if($transaction == 'delete_meeting') {
        Meetings::delete($data);
    }
}

if (isset($_GET['bodyUniqueId']) && isset($_GET['sessionUniqueId'])) {
    $meetingParameters['bodyUniqueId'] = $_GET['bodyUniqueId'];
    $meetingParameters['sessionUniqueId'] = $_GET['sessionUniqueId'];
    $meetings = Meetings::read([
        "bodyUniqueId" => $_GET["bodyUniqueId"],
        "sessionUniqueId" => $_GET["sessionUniqueId"],
        "sort" => "-date"
    ]);

    $session = Sessions::getEntry("$_GET[bodyUniqueId]/$_GET[sessionUniqueId]/");
    $sessions = null;
} else {
    $meetings = Meetings::read([
        "sort" => "-date"
    ]);

    $sessions = Sessions::read([
        "active" => "true"
    ]);
    $session = null;
}

$meetingsTableHeading = "<thead>
    <tr>
        <th>Meeting</th>
        <th>Session</th>
        <th>Date</th>
        <th>Location</th>
        <th></th>
    </tr>
</thead>";

$upcomingMeetingsTable = "";
$pastMeetingsTable = "";

foreach($meetings as $m) {
    $row = "<tr>";
    $row .= "<td>" . constructMeetingTitle($m) . "</td>";
    $row .= "<td>" . $m['session']['name'] . "</td>";
    $row .= "<td>$m[displayDate]</td>";
    $row .= "<td>$m[location]</td>";
    $row .= "<td>
            <form class='form-inline' method='post' action='$_SERVER[REQUEST_URI]' onsubmit='return confirm(\"Are you sure you want to delete " . constructMeetingTitle($m, $m['session']) . "?\");'>
                <a href='meeting.php?bodyUniqueId=$m[bodyUniqueId]&sessionUniqueId=$m[sessionUniqueId]&meetingNum=$m[meetingNum]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a>
    
                <input type=\"hidden\" name=\"transaction\" value=\"delete_meeting\">
                <input type=\"hidden\" name=\"sessionUniqueId\" value=\"$m[sessionUniqueId]\">
                <input type=\"hidden\" name=\"bodyUniqueId\" value=\"$m[bodyUniqueId]\">
                <input type=\"hidden\" name=\"meetingNum\" value=\"$m[meetingNum]\">
                <button class=\"btn btn-default btn-xs\" type='submit'><span class='fa fa-trash'></span></button>
            </form>
        </td>";
    $row .= "</tr>";

    if((new DateTime($m['date'])) >= (new DateTime())) {
        $upcomingMeetingsTable .= $row;
    } else {
        $pastMeetingsTable .= $row;
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
                                    <?php if(isset($_GET['bodyUniqueId']) && isset($_GET['sessionUniqueId'])) { ?>
                                        <li><a href="meetings.php"><?=$pageTitle?></a></li>
                                        <li class="active"><?=$session['name']?></li>
                                    <?php } else { ?>
                                        <li class="active"><?=$pageTitle?></li>
                                    <?php } ?>
                                </ol>
                            </div>
                        </div>
                        <div class="card">
                            <div class="header">
                                <h4 class="title">Upcoming Meetings</h4>
                            </div>
                            <div class="content table-responsive table-full-width">
                                <table class="table table-hover table-striped">
                                    <?=$meetingsTableHeading?>
                                    <tbody>
                                        <?=$upcomingMeetingsTable?>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                        <div class="card">
                            <div class="header">
                                <h4 class="title">Past Meetings</h4>
                            </div>
                            <div class="content table-responsive table-full-width">
                                <table class="table table-hover table-striped">
                                    <?=$meetingsTableHeading?>
                                    <tbody>
                                    <?=$pastMeetingsTable?>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="header">
                                <h4 class="title">Add Meeting</h4>
                            </div>
                            <div class="content">
                                <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                    <div class="form-group">
                                        <label for="fullUniqueId">Session <?=$requiredIndicator?></label>
                                        <?php if(isset($_GET['bodyUniqueId']) && isset($_GET['sessionUniqueId'])) { ?>
                                            <input class="form-control" disabled value="<?=$session['name']?>">
                                        <?php } else { ?>
                                            <select name="fullUniqueId" id="fullUniqueId" class="form-control">
                                                <option selected disabled></option>
                                                <?php
                                                foreach($sessions as $s) {
                                                    echo "<option value='$s[fullUniqueId]'>$s[name]</option>";
                                                }
                                                ?>
                                            </select>
                                        <?php } ?>
                                    </div>
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
