<?php
require_once 'includes/auth.php';
require_once 'includes/sg_data_php_driver/api.php';
require_once 'includes/helpers.php';

date_default_timezone_set('America/New_York');

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

blockUnauthorized();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {

} else {
    $result = false;
}

$pageTitle = "Actions";

$parameters = [];

$breadcrumbs = '';

if(isset($_GET['bodyUniqueId'])) {
    $parameters['bodyUniqueId'] = $_GET['bodyUniqueId'];

    $breadcrumbs .= "<li><a href='actions.php'>Actions</a></li>";

    if(isset($_GET['sessionUniqueId'])) {
        $parameters['sessionUniqueId'] = $_GET['sessionUniqueId'];

        if(isset($_GET['meetingNum'])) {
            $parameters['meetingNum'] = $_GET['meetingNum'];

            $meeting = Meetings::getEntry("$_GET[bodyUniqueId]/$_GET[sessionUniqueId]/$_GET[meetingNum]");
            $pageTitle .= ": " . constructMeetingTitle($meeting, $meeting['session']);

            $breadcrumbs .= "<li><a href='actions.php?bodyUniqueId=$_GET[bodyUniqueId]&sessionUniqueId=$_GET[sessionUniqueId]'>" . $meeting['session']['name'] . "</a></li>";
            $breadcrumbs .= "<li class='active'>" . constructMeetingTitle($meeting) . "</li>";
        } else {
            $session = Sessions::getEntry("$_GET[bodyUniqueId]/$_GET[sessionUniqueId]");
            $pageTitle .= ": $session[name]";

            $breadcrumbs .= "<li class='active'>$session[name]</li>";
        }
    } else {
        header("location: ./actions.php");
        exit;
    }
} else if(isset($_GET['sessionUniqueId']) || isset($_GET['meetingNum'])) {
    header("location: ./actions.php");
    exit;
} else {
    $breadcrumbs .= "<li class='active'>Actions</li>";
}

if(isset($_GET['meetingNum'])) {
    if(!isset($_GET['sessionUniqueId'])) {
        if(!isset($_GET['bodyUniqueId'])) {
            header('location: ./actions.php');
            exit;
        } else {}
        header('location: ./actions.php');
        exit;
    }

    $parameters['meetingNum'] = $_GET['meetingNum'];
}

$actions = Actions::read($parameters);

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
                    <div class="col-md-11">
                        <div class="card">
                            <div class="content content-even">
                                <ol class="breadcrumb">
                                    <?=$breadcrumbs?>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="card">
                            <div class="content content-even">
                                <a href="/new_action.php" class="btn btn-primary btn-fill btn-block btn-borderless">Create</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-10">
                        <div class="card">
                            <div class="content table-responsive table-full-width">
                                <table class="table table-hover table-striped">
                                    <thead>
                                    <tr>
                                        <th>Indicator</th>
                                        <th>Description</th>
                                        <th>Vote Count</th>
                                        <th>Moved By</th>
                                        <th>Seconded By</th>
                                        <th width="10%"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach($actions as $a) {
                                        echo "<tr>";
                                        echo "<td>$a[actionIndicator]</td>";
                                        echo "<td>$a[description]</td>";
                                        echo "<td>$a[votesFor]&ndash;$a[votesAgainst]&ndash;$a[abstentions]</td>";

                                        if(isset($a['movingMemberId'])) {
                                            echo "<td><a href='/person.php?rcsId=" . $a['movingMember']['person']['rcsId'] . "'>" . $a['movingMember']['person']['name'] . "</a></td>";
                                            echo "<td><a href='/person.php?rcsId=" . $a['secondingMember']['person']['rcsId'] . "'>" . $a['secondingMember']['person']['name'] . "</a></td>";
                                        } else if(isset($a['movingSubbodyUniqueId'])) {
                                            echo "<td><a href='/subbody.php?bodyUniqueId=$a[bodyUniqueId]&sessionUniqueId=$a[sessionUniqueId]&uniqueId=$a[movingSubbodyUniqueId]'>" . $a['subbody']['name'] . "</a></td>";
                                            echo "<td><em>N/A</em></td>";
                                        } else if($a['movingOtherEntity']) {
                                            echo "<td>$a[movingOtherEntity]</td>";
                                            echo "<td><em>N/A</em></td>";
                                        } else {
                                            echo "<td><em>TBD</em></td>";
                                            echo "<td><em>TBD</em></td>";
                                        }

                                        echo "<td><a href='/action.php?bodyUniqueId=$a[bodyUniqueId]&sessionUniqueId=$a[sessionUniqueId]&meetingNum=$a[meetingNum]&actionNum=$a[actionNum]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a></td>";
                                        echo "</tr>";
                                    }

                                    if(count($actions) == 0) {
                                        echo "<tr><td colspan='6' class='text-center'><em>No actions were found!</em></td></tr>";
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card">
                            <div class="header">
                                <h4 class="title">Filter by Session</h4>
                            </div>
                            <div class="content table-responsive table-full-width">
                                <table class="table">
                                    <tbody>
                                    <?php
                                    $sessions = Sessions::read([
                                        "active" => "true",
                                        "sort" => "-name"
                                    ]);

                                    foreach($sessions as $s) {
                                        echo "<tr><td><a href='actions.php?bodyUniqueId=$s[bodyUniqueId]&sessionUniqueId=$s[uniqueId]'>";
                                        if(isset($_GET['sessionUniqueId']) && $_GET['bodyUniqueId'] == $s['bodyUniqueId'] && $_GET['sessionUniqueId'] == $s['uniqueId']) {
                                            echo "<strong>$s[name]</strong>";
                                        } else {
                                            echo "$s[name]";
                                        }
                                        echo "</a></td></tr>";
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php if(isset($_GET['sessionUniqueId'])) { ?>
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Filter by Meeting</h4>
                                </div>
                                <div class="content table-responsive table-full-width">
                                    <table class="table">
                                        <tbody>
                                            <?php
                                            $meetings = Meetings::read([
                                                "bodyUniqueId" => $_GET['bodyUniqueId'],
                                                "sessionUniqueId" => $_GET['sessionUniqueId'],
                                                "sort" => "-meetingNum"
                                            ]);

                                            foreach($meetings as $m) {
                                                echo "<tr><td><a href='actions.php?bodyUniqueId=$m[bodyUniqueId]&sessionUniqueId=$m[sessionUniqueId]&meetingNum=$m[meetingNum]'>";
                                                if(isset($_GET['meetingNum']) && $_GET['bodyUniqueId'] == $m['bodyUniqueId'] && $_GET['sessionUniqueId'] == $m['sessionUniqueId'] && $_GET['meetingNum'] == $m['meetingNum']) {
                                                    echo "<strong>" . constructMeetingTitle($m). "</strong>";
                                                } else {
                                                    echo constructMeetingTitle($m);
                                                }
                                                echo "</a></td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
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
</body>
</html>