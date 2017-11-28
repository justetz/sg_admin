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

    if ($transaction == 'update_action') {
        $targetAction = Actions::getEntry("$data[bodyUniqueId]/$data[sessionUniqueId]/$data[meetingNum]/$data[actionNum]");

        if (isset($data['actionDescription']) && $data['actionDescription'] != $targetAction['description']) {
            $targetAction['description'] = $data['actionDescription'];
        }

        if (isset($data['actionMeetingNum']) && $data['actionMeetingNum'] != $targetAction['meetingNum']) {
            $targetAction['meetingNum'] = $data['actionMeetingNum'];
        }

        Actions::update($targetAction);
    } else if($transaction == 'update_action_outcome') {
        $targetAction = Actions::getEntry("$data[bodyUniqueId]/$data[sessionUniqueId]/$data[meetingNum]/$data[actionNum]");

        if (isset($data['actionStatus']) && $data['actionStatus'] != $targetAction['status'] && in_array($data['actionStatus'], $possibleActionStatuses)) {
            $targetAction['status'] = $data['actionStatus'];
        }

        if (isset($data['votesFor']) && intval($data['votesFor']) != intval($targetAction['votesFor']) && intval($data['votesFor']) >= 0) {
            $targetAction['votesFor'] = intval($data['votesFor']);
        } else if (!isset($data['votesFor']) && !isset($targetAction['votesFor'])) {
            $targetAction['votesFor'] = 0;
        }

        if (isset($data['votesAgainst']) && $data['votesAgainst'] != $targetAction['votesAgainst'] && $data['votesAgainst'] >= 0) {
            $targetAction['votesAgainst'] = $data['votesAgainst'];
        } else if (!isset($data['votesAgainst']) && !isset($targetAction['votesAgainst'])) {
            $targetAction['votesAgainst'] = 0;
        }

        if (isset($data['abstentions']) && $data['abstentions'] != $targetAction['abstentions'] && $data['abstentions'] >= 0) {
            $targetAction['abstentions'] = $data['abstentions'];
        } else if (!isset($data['abstentions']) && !isset($targetAction['abstentions'])) {
            $targetAction['abstentions'] = 0;
        }

        Actions::update($targetAction);
    } else if($transaction == 'update_action_text') {
        $targetAction = Actions::getEntry("$data[bodyUniqueId]/$data[sessionUniqueId]/$data[meetingNum]/$data[actionNum]");

        if($data['actionText'] != $targetAction['text']) {
            $targetAction['text'] = $data['actionText'];
            Actions::update($targetAction);
        }
    } else if ($transaction == 'update_action_mover') {
        $targetAction = Actions::getEntry("$data[bodyUniqueId]/$data[sessionUniqueId]/$data[meetingNum]/$data[actionNum]");

        if(isset($data['movingOtherEntity']) && strlen($data['movingOtherEntity']) > 0) {
            if(isset($targetAction['movingMemberId'])) unset($targetAction['movingMemberId']);
            if(isset($targetAction['secondingMemberId'])) unset($targetAction['secondingMemberId']);
            if(isset($targetAction['movingSubbodyUniqueId'])) unset($targetAction['movingSubbodyUniqueId']);

            $targetAction['movingOtherEntity'] = $data['movingOtherEntity'];
        } else if(isset($data['moving'])) {
            $movingComponents = explode('~', $data['moving'], 2);
            if($movingComponents[0] == 'subbody') {
                $targetAction['movingSubbodyUniqueId'] = $movingComponents[1];

                if(isset($targetAction['movingMemberId'])) unset($targetAction['movingMemberId']);
                if(isset($targetAction['secondingMemberId'])) unset($targetAction['secondingMemberId']);
            } else if($movingComponents[0] == 'membership') {
                $targetAction['movingMemberId'] = $movingComponents[1];

                if(isset($data['seconding'])) {
                    $secondingComponents = explode('~', $data['seconding'], 2);

                    $targetAction['secondingMemberId'] = $secondingComponents[1];
                }

                if(isset($targetAction['movingSubbodyUniqueId'])) unset($targetAction['movingSubbodyUniqueId']);
            }
        }

        Actions::update($targetAction);
    }

} else if(!isset($_GET['actionNum']) || !isset($_GET['meetingNum']) || !isset($_GET['sessionUniqueId']) || !isset($_GET['bodyUniqueId'])) {
    header('location: ./new_action.php');
    exit;
} else {
    $result = false;
}

$action = Actions::getEntry("$_GET[bodyUniqueId]/$_GET[sessionUniqueId]/$_GET[meetingNum]/$_GET[actionNum]");
$session = Sessions::getEntry("$_GET[bodyUniqueId]/$_GET[sessionUniqueId]");
$meetings = Meetings::read([
    "bodyUniqueId" => $_GET['bodyUniqueId'],
    "sessionUniqueId" => $_GET['sessionUniqueId'],
]);
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

$pageTitle = "Manage Action: $action[description] ($action[actionIndicator])";

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
                                    <li><a href="actions.php">Actions</a></li>
                                    <li><a href="actions.php?bodyUniqueId=<?=$_GET['bodyUniqueId']?>&sessionUniqueId=<?=$_GET['sessionUniqueId']?>"><?=$session['name']?></a></li>
                                    <li><a href="actions.php?bodyUniqueId=<?=$_GET['bodyUniqueId']?>&sessionUniqueId=<?=$_GET['sessionUniqueId']?>&meetingNum=<?=$_GET['meetingNum']?>"><?=constructMeetingTitle($action['meeting'])?></a></li>
                                    <li class="active"><?=$action['actionIndicator']?></li>
                                </ol>
                            </div>
                        </div>
                        <div class="card">
                            <div class="header">
                                <h4 class="title">Action Text</h4>
                            </div>
                            <div class="content content-even">
                                <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                    <div class="form-group">
                                        <textarea title="Action Text" name="actionText" rows="12" class="form-control" style="resize: vertical;" data-provide="markdown" data-iconlibrary="fa"><?=$action['text']?></textarea>
                                    </div>

                                    <input type="hidden" name="transaction" value="update_action_text">
                                    <input type="hidden" name="bodyUniqueId" value="<?=$_GET['bodyUniqueId']?>">
                                    <input type="hidden" name="sessionUniqueId" value="<?=$_GET['sessionUniqueId']?>">
                                    <input type="hidden" name="meetingNum" value="<?=$_GET['meetingNum']?>">
                                    <input type="hidden" name="actionNum" value="<?=$_GET['actionNum']?>">
                                    <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Update Text</button>
                                    <div class="clearfix"></div>
                                </form>
                                <hr>
                                <h5>Current Text:</h5>
                                <blockquote class="action">
                                <?=$action['textHtml']?>
                                </blockquote>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Moved By</h4>
                                    </div>
                                    <div class="content content-even">
                                        <p>
                                            <?php
                                                if(isset($action['movingMemberId'])) {
                                                    echo "<a href='person.php?rcsId=" . $action['movingMember']['personRcsId'] . "'>" . $action['movingMember']['person']['name'] . "</a>";
                                                    echo "<br><span class='text-muted small'>" . $action['movingMember']['name'] . "</span>";
                                                } else if(isset($action['movingSubbodyUniqueId'])) {
                                                    echo "<a href='subbody.php?bodyUniqueId=$action[bodyUniqueId]&sessionUniqueId=$action[sessionUniqueId]&uniqueId=$action[movingSubbodyUniqueId]'>" . $action['subbody']['name'] . "</a>";
                                                    $presidingOfficers = Memberships::read(["positionId" => $action['subbody']['presidingOfficerPositionId']]);

                                                    foreach($presidingOfficers as $p) {
                                                        if($p['startDate'] <= $action['meeting']['date'] && ($p['current'] || $p['endDate'] >= $action['meeting']['date'])) {
                                                            echo "<br><span class='text-muted small'>Presiding Officer: " . $p['person']['name'] . "</span>";
                                                        }
                                                    }
                                                } else if(isset($action['movingOtherEntity'])) {
                                                    echo $action['movingOtherEntity'] . "<br><span class='text-muted small'>(Other entity)</span>";
                                                } else {
                                                    echo 'TBD<br>';
                                                }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Seconded By</h4>
                                    </div>
                                    <div class="content content-even">
                                        <p>
                                            <?php
                                            if(isset($action['secondingMemberId'])) {
                                                echo "<a href='person.php?rcsId=" . $action['secondingMember']['personRcsId'] . "'>" . $action['secondingMember']['person']['name'] . "</a>";
                                                echo "<br><span class='text-muted small'>" . $action['secondingMember']['name'] . "</span>";
                                            } else if(isset($action['movingSubbodyUniqueId']) || isset($action['movingOtherEntity'])) {
                                                echo 'N/A<br><span class="text-muted small">(' .
                                                    (isset($action['movingOtherEntity']) ? 'Other entites' : 'Sub-Bodies') .
                                                    ' do not require a second.)</span>';
                                            } else {
                                                echo 'TBD<br>';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Vote Count</h4>
                                    </div>
                                    <div class="content content-even">
                                        <p>
                                            <?=$action['votesFor']?>&ndash;<?=$action['votesAgainst']?>&ndash;<?=$action['abstentions']?>
                                            <br>
                                            <span class="text-muted small">(For&ndash;Against&ndash;Abstentions)</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="header">
                                <h4 class="title">Edit Action Details</h4>
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
                                                <label for="actionDescription">Description <?=$requiredIndicator?></label>
                                                <input name="actionDescription" type="text" class="form-control" value="<?=$action['description']?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="actionMeetingNum">Meeting <?=$requiredIndicator?></label>
                                                <select name="actionMeetingNum" class="form-control">
                                                    <?php
                                                    foreach($meetings as $m) {
                                                        echo "<option value='$m[meetingNum]'" . ($_GET['meetingNum'] == $m['meetingNum'] ? ' selected' : '') . ">" . constructMeetingTitle($m) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="transaction" value="update_action">
                                    <input type="hidden" name="bodyUniqueId" value="<?=$_GET['bodyUniqueId']?>">
                                    <input type="hidden" name="sessionUniqueId" value="<?=$_GET['sessionUniqueId']?>">
                                    <input type="hidden" name="meetingNum" value="<?=$_GET['meetingNum']?>">
                                    <input type="hidden" name="actionNum" value="<?=$_GET['actionNum']?>">
                                    <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Update Action</button>
                                    <div class="clearfix"></div>
                                </form>
                            </div>
                        </div>
                        <div class="card">
                            <div class="header">
                                <h4 class="title">Action Outcome</h4>
                            </div>
                            <div class="content">
                                <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="votesFor">Votes For <?=$requiredIndicator?></label>
                                                <input name="votesFor" id="votesFor" type="number" class="form-control" value="<?=$action['votesFor']?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="votesFor">Votes Against <?=$requiredIndicator?></label>
                                                <input name="votesFor" id="votesFor" type="number" class="form-control" value="<?=$action['votesAgainst']?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="votesFor">Abstentions <?=$requiredIndicator?></label>
                                                <input name="votesFor" id="votesFor" type="number" class="form-control" value="<?=$action['abstentions']?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="actionStatus">Status <?=$requiredIndicator?></label>
                                                <select name="actionStatus" class="form-control">
                                                    <?php
                                                    if(!isset($action['status']) || !in_array($action['status'], $possibleActionStatuses)) {
                                                        echo "<option selected disabled></option>";
                                                    }

                                                    foreach ($possibleActionStatuses as $s) {
                                                        echo "<option value='$s'" . ($s == $action['status'] ? ' selected': '') . ">$s</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>


                                    <input type="hidden" name="transaction" value="update_action_outcome">
                                    <input type="hidden" name="bodyUniqueId" value="<?=$_GET['bodyUniqueId']?>">
                                    <input type="hidden" name="sessionUniqueId" value="<?=$_GET['sessionUniqueId']?>">
                                    <input type="hidden" name="meetingNum" value="<?=$_GET['meetingNum']?>">
                                    <input type="hidden" name="actionNum" value="<?=$_GET['actionNum']?>">
                                    <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Update</button>
                                    <div class="clearfix"></div>
                                </form>
                            </div>
                        </div>
                        <div class="card">
                            <div class="header">
                                <h4 class="title">Edit Moved & Seconded</h4>
                            </div>
                            <div class="content">
                                <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="movingMemberId">Moved By <?=$requiredIndicator?></label>
                                                <select name="moving" class="form-control">
                                                    <?=(!isset($action['movingMemberId']) ? '<option disabled selected></option>' : '')?>
                                                    <?=buildMembershipOptions($positions, $memberships, $action, (isset($action['movingMemberId']) ? $action['movingMemberId'] : $action['movingSubbodyUniqueId']), $subbodies)?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="secondingMemberId">Seconded By <?=$requiredIndicator?></label>
                                                <select name="seconding" class="form-control">
                                                    <option <?=(!isset($action['secondingMemberId']) ? 'selected' : '')?>></option>
                                                    <?=buildMembershipOptions($positions, $memberships, $action, $action['secondingMemberId'])?>
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
                                                <input type="text" name="movingOtherEntity" class="form-control" value="<?=(isset($action['movingOtherEntity']) ? $action['movingOtherEntity'] : '')?>">
                                                <p class="help-block small">If you enter in a value here, the 'Moved By' and 'Seconded By' fields will be ignored.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="transaction" value="update_action_mover">
                                    <input type="hidden" name="bodyUniqueId" value="<?=$_GET['bodyUniqueId']?>">
                                    <input type="hidden" name="sessionUniqueId" value="<?=$_GET['sessionUniqueId']?>">
                                    <input type="hidden" name="meetingNum" value="<?=$_GET['meetingNum']?>">
                                    <input type="hidden" name="actionNum" value="<?=$_GET['actionNum']?>">
                                    <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Update</button>
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
</body>
</html>