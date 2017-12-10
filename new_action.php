<?php
require_once 'includes/auth.php';
require_once 'includes/api.php';
require_once 'includes/helpers.php';

date_default_timezone_set('America/New_York');

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

$possibleActionStatuses = ["Pending", "Passed", "Failed", "Postponed", "Tabled", "Moved"];

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    $data = $_POST;
    $transaction = $data['transaction'];
    unset($data['transaction']);

    if ($transaction == 'create_action') {
        if(empty($data['actionText']) || empty($data['actionDescription']) || empty($data['actionStatus'])
            || (empty($data['moving']) && empty($data['movingOtherEntity'])) || empty($data['seconding'])) {
            $result = ['message' => 'Not all required fields have been filled out!'];
        } else {
            $meetingActions = Actions::read([
                "meetingNum" => $data['meetingNum'],
                "sort" => "-actionNum"
            ]);

            if(count($meetingActions) == 0) {
                $actionNum = 1;
            } else {
                $actionNum = $meetingActions[0]['actionNum'] + 1;
            }

            $newAction = [
                "bodyUniqueId" => $data['bodyUniqueId'],
                "sessionUniqueId" => $data['sessionUniqueId'],
                "meetingNum" => $data['meetingNum'],
                "actionNum" => $actionNum,
                "description" => $data['actionDescription'],
                "text" => $data['actionText'],
                "status" => $data['actionStatus'],
                "votesFor" => isset($data['votesFor']) ? intval($data['votesFor']) : 0,
                "votesAgainst" => isset($data['votesAgainst']) ? intval($data['votesAgainst']) : 0,
                "abstentions" => isset($data['abstentions']) ? intval($data['abstentions']) : 0
            ];

            if(!empty($data['movingOtherEntity'])) {
                $newAction['movingOtherEntity'] = $data['movingOtherEntity'];
            } else {
                if(isset($data['moving'])) {
                    $movingDetails = explode('~', $data['moving'], 2);
                    if($movingDetails[0] == 'membership') {
                        $newAction['movingMemberId'] = $movingDetails[1];
                    } else if($movingDetails[0] == 'subbody') {
                        $newAction['movingSubbodyUniqueId'] = $movingDetails[1];
                    }
                }

                if(isset($data['seconding'])) {
                    $secondingDetails = explode('~', $data['seconding'], 2);
                    if($secondingDetails[0] == 'membership') {
                        $newAction['secondingMemberId'] = $secondingDetails[1];
                    }
                }
            }

            $result = Actions::create($newAction);

            setcookie('SGMS-Success-Message', "The action $newAction[description] was successfully created!.", time() + 30);
            header("location: ./action.php?bodyUniqueId=$newAction[bodyUniqueId]&sessionUniqueId=$newAction[sessionUniqueId]&meetingNum=$newAction[meetingNum]&actionNum=$newAction[actionNum]");
            exit;
        }
    }
} else {
    $result = false;
}

$pageTitle = "Create Action";

if (isset($_GET['bodyUniqueId'])) {
    $body = Bodies::getEntry($_GET['bodyUniqueId']);

    if (isset($_GET['sessionUniqueId'])) {
        $session = Sessions::getEntry("$_GET[bodyUniqueId]/$_GET[sessionUniqueId]");

        if (isset($_GET['meetingNum'])) {
            $meeting = Meetings::getEntry("$_GET[bodyUniqueId]/$_GET[sessionUniqueId]/$_GET[meetingNum]");
        } else {
            $meeting = null;
        }
    } else {
        $session = null;
        $meeting = null;
    }
} else {
    $body = null;
    $session = null;
    $meeting = null;
}

function isSubmissionAttempt () {
    return $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction']) && $_POST['transaction'] == 'create_action';
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
                <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                    <div class="row">
                        <div class="col-md-11">
                            <div class="card">
                                <div class="content content-even">
                                    <ol class="breadcrumb">
                                        <li><a href="actions.php">Actions</a></li>
                                        <?php
                                            if (!isset($_GET['bodyUniqueId']) && !isset($_GET['sessionUniqueId']) && !isset($_GET['meetingNum'])) {
                                                echo "<li class='active'>Create</li>";
                                            } else {
                                                echo "<li><a href='new_action.php'>Create</a></li>";

                                                if (isset($_GET['bodyUniqueId']) && !isset($_GET['sessionUniqueId']) && !isset($_GET['meetingNum'])) {
                                                    echo "<li class='active'>$body[name]</li>";
                                                } else {
                                                    echo "<li><a href='new_action.php?bodyUniqueId=$body[uniqueId]'>$body[name]</a></li>";

                                                    if (isset($_GET['bodyUniqueId']) && isset($_GET['sessionUniqueId']) && !isset($_GET['meetingNum'])) {
                                                        echo "<li class='active'>$session[name]</li>";
                                                    } else {
                                                        echo "<li><a href='new_action.php?bodyUniqueId=$body[uniqueId]&sessionUniqueId=$session[uniqueId]'>$session[name]</a></li>";

                                                        if (isset($_GET['bodyUniqueId']) && isset($_GET['sessionUniqueId']) && isset($_GET['meetingNum'])) {
                                                            echo "<li class='active'>" . constructMeetingTitle($meeting) . "</li>";
                                                        } else {
                                                            echo "<li><a href='new_action.php'>" . constructMeetingTitle($meeting) . "</a></li>";
                                                        }
                                                    }
                                                }
                                            }
                                        ?>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="card">
                                <div class="content content-even">
                                    <?php
                                        if (!isset($_GET['bodyUniqueId']) || !isset($_GET['sessionUniqueId']) || !isset($_GET['meetingNum'])) {
                                            echo "<button type='button' class='btn btn-primary btn-fill btn-block btn-borderless' disabled>Create</button>";
                                        } else {
                                            echo "<input type='hidden' name='transaction' value='create_action'>";
                                            echo "<button type='submit' class='btn btn-primary btn-fill btn-block btn-borderless'>Create</button>";
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php if (!isset($_GET['bodyUniqueId']) || !isset($_GET['sessionUniqueId']) || !isset($_GET['meetingNum'])) {?>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">1. Select a Body</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table">
                                            <tbody>
                                            <?php
                                            $bodies = Bodies::read();

                                            foreach($bodies as $b) {
                                                echo "<tr><td><a href='new_action.php?bodyUniqueId=$b[uniqueId]'>";
                                                if(isset($_GET['bodyUniqueId']) && $_GET['bodyUniqueId'] == $b['uniqueId']) {
                                                    echo "<strong>$b[name]</strong>";
                                                } else {
                                                    echo "$b[name]";
                                                }
                                                echo "</a></td></tr>";
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">2. Select a Session</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table">
                                            <tbody>
                                            <?php
                                            if (isset($_GET['bodyUniqueId'])) {
                                                $sessions = Sessions::read([
                                                    "bodyUniqueId" => $_GET['bodyUniqueId'],
                                                    "sort" => "-name"
                                                ]);

                                                foreach ($sessions as $s) {
                                                    echo "<tr><td><a href='new_action.php?bodyUniqueId=$s[bodyUniqueId]&sessionUniqueId=$s[uniqueId]'>";
                                                    if (isset($_GET['sessionUniqueId']) && $_GET['bodyUniqueId'] == $s['bodyUniqueId'] && $_GET['sessionUniqueId'] == $s['uniqueId']) {
                                                        echo "<strong>$s[name]</strong>";
                                                    } else {
                                                        echo "$s[name]";
                                                    }
                                                    echo "</a></td></tr>";
                                                }

                                                if (count($sessions) == 0) {
                                                    echo "<tr><td class='text-center'><em>No sessions exist for this body!</em></td></tr>";
                                                }
                                            } else {
                                                echo "<tr><td class='text-center'><em>Select a body to proceed!</em></td></tr>";
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">3. Select a Meeting</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table">
                                            <tbody>
                                            <?php
                                            if (isset($_GET['bodyUniqueId']) && isset($_GET['sessionUniqueId'])) {
                                                $meetings = Meetings::read([
                                                    "bodyUniqueId" => $_GET['bodyUniqueId'],
                                                    "sessionUniqueId" => $_GET['sessionUniqueId'],
                                                    "sort" => "-meetingNum"
                                                ]);

                                                foreach ($meetings as $m) {
                                                    echo "<tr><td><a href='new_action.php?bodyUniqueId=$m[bodyUniqueId]&sessionUniqueId=$m[sessionUniqueId]&meetingNum=$m[meetingNum]'>";
                                                    echo constructMeetingTitle($m) . " <span class='text-muted'>($m[displayDate])</span>";
                                                    echo "</a></td></tr>";
                                                }

                                                if (count($meetings) == 0) {
                                                    echo "<tr><td class='text-center'><em>No meetings exist for this session!</em></td></tr>";
                                                }
                                            } else {
                                                echo "<tr><td class='text-center'><em>Select a body and a session to proceed!</em></td></tr>";
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php } else { ?>
                            <?php
                                $memberships = Memberships::read([
                                    "bodyUniqueId" => $_GET['bodyUniqueId'],
                                    "sessionUniqueId" => $_GET['sessionUniqueId'],
                                ]);

                                $positions = Positions::read([
                                    "bodyUniqueId" => $_GET['bodyUniqueId'],
                                    "voting" => "true",
                                ]);

                                $subbodies = Subbodies::read([
                                    "bodyUniqueId" => $_GET['bodyUniqueId'],
                                    "sessionUniqueId" => $_GET['sessionUniqueId'],
                                ]);
                            ?>
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Action Text</h4>
                                    </div>
                                    <div class="content content-even">
                                        <div class="form-group">
                                            <textarea title="Action Text" name="actionText" rows="36" class="form-control" data-provide="markdown"
                                                  data-iconlibrary="fa"><?=isSubmissionAttempt() ? $_POST["actionText"] : ''?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Action Details</h4>
                                    </div>
                                    <div class="content">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="bodyName">Body</label>
                                                    <input name="bodyName" type="text" class="form-control" value="<?=$body['name']?>" disabled>
                                                    <input name="bodyUniqueId" type="hidden" value="<?=$body['uniqueId']?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="sessionName">Session</label>
                                                    <input name="sessionName" type="text" class="form-control" value="<?=$session['name']?>" disabled>
                                                    <input name="sessionUniqueId" type="hidden" value="<?=$session['uniqueId']?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="actionDescription">Description <?=$requiredIndicator?></label>
                                                    <input name="actionDescription" type="text" class="form-control"
                                                           value="<?=isSubmissionAttempt() ? $_POST["actionDescription"] : ''?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="meetingName">Meeting</label>
                                                    <input name="meetingName" type="text" class="form-control" value="<?=constructMeetingTitle($meeting) . " ($meeting[displayDate])"?>" disabled>
                                                    <input name="meetingNum" type="hidden" value="<?=$meeting['meetingNum']?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Action Outcome</h4>
                                    </div>
                                    <div class="content">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="votesFor">Votes For <?=$requiredIndicator?></label>
                                                    <input name="votesFor" id="votesFor" type="number" class="form-control"
                                                           value="<?=isSubmissionAttempt() ? $_POST["votesFor"] : ''?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="votesAgainst">Votes Against <?=$requiredIndicator?></label>
                                                    <input name="votesAgainst" id="votesAgainst" type="number" class="form-control"
                                                           value="<?=isSubmissionAttempt() ? $_POST["votesAgainst"] : ''?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="abstentions">Abstentions <?=$requiredIndicator?></label>
                                                    <input name="abstentions" id="abstentions" type="number" class="form-control"
                                                           value="<?=isSubmissionAttempt() ? $_POST["abstentions"] : ''?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="actionStatus">Status <?=$requiredIndicator?></label>
                                                    <select name="actionStatus" class="form-control">
                                                        <?php
                                                        if(!isSubmissionAttempt() || !isset($_POST['actionStatus'])) {
                                                            echo "<option selected disabled></option>";
                                                        }

                                                        foreach ($possibleActionStatuses as $s) {
                                                            echo "<option value='$s'" . (isSubmissionAttempt() && isset($_POST['actionStatus']) && $_POST['actionStatus'] == $s ? ' selected' : '') . ">$s</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Set Moved & Seconded</h4>
                                    </div>
                                    <div class="content">
                                        <?php
                                        $moverActiveId = '';
                                        $seconderActiveId = '';

                                        if(isSubmissionAttempt() && isset($_POST['moving'])) {
                                            $moverActiveId = explode("~", $_POST['moving'], 2)[1];
                                        }

                                        if(isSubmissionAttempt() && isset($_POST['seconding'])) {
                                            $seconderActiveId = explode("~", $_POST['seconding'], 2)[1];
                                        }
                                        ?>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="movingMemberId">Moved By <?=$requiredIndicator?></label>
                                                    <select name="moving" class="form-control">
                                                        <option disabled selected></option>
                                                        <?=buildMembershipOptions($positions, $memberships, ["meeting" => $meeting], $moverActiveId, $subbodies)?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="secondingMemberId">Seconded By <?=$requiredIndicator?></label>
                                                    <select name="seconding" class="form-control">
                                                        <option disabled selected></option>
                                                        <?=buildMembershipOptions($positions, $memberships, ["meeting" => $meeting], $seconderActiveId)?>
                                                    </select>
                                                    <p class="help-block small">If you selected a Sub-Body for the 'Moved By' field, the 'Seconded By' field will be ignored.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="movingOtherEntity">Other Moving Entity</label>
                                                    <input type="text" name="movingOtherEntity" class="form-control" value="<?=(isSubmissionAttempt() && isset($_POST['movingOtherEntity']) ? $_POST['movingOtherEntity'] : '')?>">
                                                    <p class="help-block small">If you enter in a value here, the 'Moved By' and 'Seconded By' fields will be ignored.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </form>
            </div>
        </div>
        <?php require_once 'partials/footer.php' ?>
    </div>
</div>
<?php require_once 'partials/scripts.php' ?>
<?=buildMessage($result, $_POST)?>
</body>
</html>