<?php
require_once 'includes/auth.php';
require_once 'includes/api.php';
require_once 'includes/helpers.php';

date_default_timezone_set('America/New_York');

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    $data = $_POST;
    $transaction = $data['transaction'];
    unset($data['transaction']);

    if ($transaction == 'create_agenda_item') {
        if ($data['parentId'] == -1) {
            if ($data['previousItem'] == -1) {
                $lastItem = AgendaItems::read([
                    'bodyUniqueId' => $_GET['bodyUniqueId'],
                    'sessionUniqueId' => $_GET['sessionUniqueId'],
                    'meetingNum' => $_GET['meetingNum'],
                    'sort' => '-order',
                    'count' => 1
                ]);

                if (count($lastItem) == 0) {
                    $data['order'] = 1;
                } else {
                    $data['order'] = $lastItem[0]['order'] + 1;
                }
            } else {
                $previousItemOrder = AgendaItems::getEntry($data['previousItem'])['order'];
                $data['order'] = $previousItemOrder;

                $itemsToIncrement = AgendaItems::read([
                    'bodyUniqueId' => $_GET['bodyUniqueId'],
                    'sessionUniqueId' => $_GET['sessionUniqueId'],
                    'meetingNum' => $_GET['meetingNum'],
                    'sort' => '-order'
                ]);

                foreach ($itemsToIncrement as $i) {
                    if ($i['order'] >= $previousItemOrder) {
                        $i['order']++;
                        AgendaItems::update($i);
                    }
                }
            }
        } else {
            $lastItem = AgendaItems::read([
                'bodyUniqueId' => $_GET['bodyUniqueId'],
                'sessionUniqueId' => $_GET['sessionUniqueId'],
                'meetingNum' => $_GET['meetingNum'],
                'parentId' => $data['parentId'],
                'sort' => '-order',
                'count' => 1
            ]);

            if (count($lastItem) == 0) {
                $data['order'] = 1;
            } else {
                $data['order'] = $lastItem[0]['order'] + 1;
            }
        }

        $result = AgendaItems::create($data);
    } else if ($transaction == 'duplicate_agenda') {
        $existingItems = AgendaItems::read([
            'bodyUniqueId' => $data['bodyUniqueId'],
            'sessionUniqueId' => $data['sessionUniqueId'],
            'meetingNum' => $data['meetingNum']
        ]);

        if (count($existingItems) > 0) {
            foreach($existingItems as $i) {
                AgendaItems::delete($i);
            }
        }

        recursivelyDuplicateAgenda($data['bodyUniqueId'], $data['sessionUniqueId'], $data['targetMeetingNum'], $data['meetingNum']);
        $result = ['message' => 'The agenda was successfully duplicated!'];
    } else if ($transaction == 'delete_agenda_item') {
        $result = AgendaItems::delete($data);
    } else if ($transaction == 'update_minutes') {
        $meeting = Meetings::getEntry("$data[bodyUniqueId]/$data[sessionUniqueId]/$data[meetingNum]");

        if(isset($data['minutesText']) && $meeting['minutesText'] != $data['minutesText']) {
            $meeting['minutesText'] = $data['minutesText'];
            $result = Meetings::update($meeting);
        }
    }
} else if(!isset($_GET['meetingNum']) || !isset($_GET['sessionUniqueId']) || !isset($_GET['bodyUniqueId'])) {
    header('location: ./sessions.php');
    exit;
} else {
    $result = false;
}

$meeting = Meetings::getEntry("$_GET[bodyUniqueId]/$_GET[sessionUniqueId]/$_GET[meetingNum]");
$actions = Actions::read([
    'bodyUniqueId' => $_GET['bodyUniqueId'],
    'sessionUniqueId' => $_GET['sessionUniqueId'],
    'meetingNum' => $_GET['meetingNum'],
    'sort' => 'actionNum',
]);

$agendaItems = loadAgendaItems();

$pageTitle = "Manage Meeting: " . constructMeetingTitle($meeting, $meeting['session']);

function loadAgendaItems ($parentId='[null,-1]') {
    $agendaItems = AgendaItems::read([
        'bodyUniqueId' => $_GET['bodyUniqueId'],
        'sessionUniqueId' => $_GET['sessionUniqueId'],
        'meetingNum' => $_GET['meetingNum'],
        'parentId' => $parentId,
        'sort' => 'order,name',
    ]);

    return $agendaItems;
}

function recursivelyBuildAgenda ($agendaItems, $depth=0) {
    $result = '';

    if(count($agendaItems) > 0) {
        foreach ($agendaItems as $index => $i) {
            $result .= "<tr>";

            $result .= "<td width='10%'><div class='btn-group'>";
            if ($index > 0) {
                $result .= "<a class='btn btn-xs btn-default' href=''><span class='fa fa-arrow-up'></span></a>";
            } else {
                $result .= "<a class='btn btn-xs btn-default disabled' href=''><span class='fa fa-arrow-up'></span></a>";
            }
            if ($index < count($agendaItems) - 1) {
                $result .= "<a class='btn btn-xs btn-default' href=''><span class='fa fa-arrow-down'></span></a>";
            } else {
                $result .= "<a class='btn btn-xs btn-default disabled'><span class='fa fa-arrow-down'></span></a>";
            }
            $result .= "</div></td>";

            $result .= "<td width='40%'>";
            if ($depth > 0) {
                $result .= "<span style='padding-left: " . (20 * $depth) . "px;'>$i[name]</span>";
            } else {
                $result .= "<strong>$i[name]</strong>";
            }
            $result .= "</td>";

            $result .= "<td width='40%'>";
            if (isset($i['presenter'])) {
                $result .= "<em>$i[presenter]</em>";
            } else {
                $result .= "<em class='text-muted'>None</em>";
            }
            $result .= "</td>";

            $result .= "<td width='10%'><div class='btn-group pull-right'>";

            if ($depth > 0) {
                $result .= "<a class='btn btn-xs btn-default' href=''><span class='fa fa-arrow-left'></span></a>";
            } else {
                $result .= "<a class='btn btn-xs btn-default disabled' href=''><span class='fa fa-arrow-left'></span></a>";
            }

            if ($index > 0) {
                $result .= "<a class='btn btn-xs btn-default' href=''><span class='fa fa-arrow-right'></span></a>";
            } else {
                $result .= "<a class='btn btn-xs btn-default disabled' href=''><span class='fa fa-arrow-right'></span></a>";
            }

            $result .= "</div></td>";

            $result .= "<td><form class='form-inline' method='post' onsubmit='return confirm(\"Are you sure you want to delete $i[name]?\");'>";
            $result .= "<input type='hidden' name='transaction' value='delete_agenda_item'>";
            $result .= "<input type='hidden' name='id' value='$i[id]'>";
            $result .= "<button type='submit' class='btn btn-xs btn-default'><span class='fa fa-trash'></span></button>";
            $result .= "</form></td>";

            $result .= "</tr>";

            $result .= recursivelyBuildAgenda(loadAgendaItems($i['id']), $depth + 1);
        }
    }

    return $result;
}

function recursivelyBuildAgendaSelect($agendaItems, $depth=0) {
    $result = '';

    foreach($agendaItems as $i) {
        $result .= "<option value='$i[id]'>";
        $result .= str_repeat('&mdash;', $depth);
        $result .= ($depth > 0) ? ' ' : '';
        $result .= "$i[name]</option>";
        $result .= recursivelyBuildAgendaSelect(loadAgendaItems($i['id']), $depth + 1);
    }

    return $result;
}

function recursivelyDuplicateAgenda($bodyUniqueId, $sessionUniqueId, $targetMeetingNum, $destinationMeetingNum, $parentId='[null,-1]', $newParentId='-1') {
    $targetItems = AgendaItems::read([
        'bodyUniqueId' => $bodyUniqueId,
        'sessionUniqueId' => $sessionUniqueId,
        'meetingNum' => $targetMeetingNum,
        'parentId' => $parentId,
        'sort' => 'order,name'
    ]);

    if(count($targetItems) > 0) {
        foreach ($targetItems as $i) {
            $id = $i['id'];
            unset($i['id']);
            if(isset($i['meeting'])) unset($i['meeting']);
            if(isset($i['createdAt'])) unset($i['createdAt']);
            if(isset($i['updatedAt'])) unset($i['updatedAt']);

            $i['meetingNum'] = intval($destinationMeetingNum);
            $i['parentId'] = $newParentId;
            $newItem = AgendaItems::create($i);
//            echo json_encode($i);

            recursivelyDuplicateAgenda($bodyUniqueId, $sessionUniqueId, $targetMeetingNum, $destinationMeetingNum, $id, $newItem['id']);
        }
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
                                    <li><a href="meetings.php">Meetings &amp; Events</a></li>
                                    <li><a href="meetings.php?bodyUniqueId=<?=$_GET['bodyUniqueId']?>&sessionUniqueId=<?=$_GET['sessionUniqueId']?>"><?=$meeting['session']['name']?></a></li>
                                    <li class="active"><?=constructMeetingTitle($meeting)?></li>
                                </ol>
                            </div>
                        </div>
                        <div class="card">
                            <div class="content content-even">
                                <ul class="nav nav-pills">
                                    <li role="presentation" <?=(!isset($_GET['section']) || $_GET['section'] == 'agenda') ? 'class="active"' : ''?>>
                                        <a href="<?=toggleGetParam('section','agenda')?>">Agenda</a>
                                    </li>
                                    <li role="presentation" <?=(isset($_GET['section']) && $_GET['section'] == 'actions') ? 'class="active"' : ''?>>
                                        <a href="<?=toggleGetParam('section','actions')?>">Actions</a>
                                    </li>
                                    <li role="presentation" <?=(isset($_GET['section']) && $_GET['section'] == 'attendance') ? 'class="active"' : ''?>>
                                        <a href="<?=toggleGetParam('section','attendance')?>">Attendance</a>
                                    </li>
                                    <li role="presentation" <?=(isset($_GET['section']) && $_GET['section'] == 'minutes') ? 'class="active"' : ''?>>
                                        <a href="<?=toggleGetParam('section','minutes')?>">Minutes</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <?php if(!isset($_GET['section']) || $_GET['section'] == 'agenda') { ?>
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Meeting Agenda</h4>
                                </div>
                                <div class="content table-responsive table-full-width">
                                    <table class="table table-hover table-striped">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Name</th>
                                                <th>Presenter</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?=recursivelyBuildAgenda($agendaItems)?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php } else if(isset($_GET['section']) && $_GET['section'] == 'minutes') { ?>
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Meeting Minutes</h4>
                                </div>
                                <div class="content content-even">
                                    <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                        <div class="form-group">
                                            <textarea title="Meeting Minutes" name="minutesText" rows="12" class="form-control" style="resize: vertical;" data-provide="markdown" data-iconlibrary="fa"><?=$meeting['minutesText']?></textarea>
                                        </div>

                                        <input type="hidden" name="transaction" value="update_minutes">
                                        <input type="hidden" name="bodyUniqueId" value="<?=$meeting['bodyUniqueId']?>">
                                        <input type="hidden" name="sessionUniqueId" value="<?=$meeting['sessionUniqueId']?>">
                                        <input type="hidden" name="meetingNum" value="<?=$meeting['meetingNum']?>">
                                        <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Update Minutes</button>
                                        <div class="clearfix"></div>
                                    </form>
                                </div>
                            </div>
                        <?php } else if(isset($_GET['section']) && $_GET['section'] == 'actions') { ?>
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Actions</h4>
                                </div>
                                <div class="content table-responsive table-full-width">
                                    <table class="table table-hover table-striped">
                                        <thead>
                                        <tr>
                                            <th>Indicator</th>
                                            <th>Description</th>
                                            <th>Vote Count</th>
                                            <th>Moved By</th>
                                            <th>Seconded By</th>
                                            <th></th>
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

                                            echo "<td>
                                                <form class='form-inline' method='post' action='$_SERVER[REQUEST_URI]' onsubmit='return confirm(\"Are you sure you want to delete \'$a[description]\'?\");'>
                                                    <a href='/action.php?bodyUniqueId=$a[bodyUniqueId]&sessionUniqueId=$a[sessionUniqueId]&meetingNum=$a[meetingNum]&actionNum=$a[actionNum]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a>

                                                    <input type=\"hidden\" name=\"transaction\" value=\"delete_action\">
                                                    <input type=\"hidden\" name=\"sessionUniqueId\" value=\"$_GET[sessionUniqueId]\">
                                                    <input type=\"hidden\" name=\"bodyUniqueId\" value=\"$_GET[bodyUniqueId]\">
                                                    <input type=\"hidden\" name=\"meetingNum\" value=\"$_GET[meetingNum]\">
                                                    <input type=\"hidden\" name=\"actionNum\" value=\"$a[actionNum]\">
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
                        <?php if(!isset($_GET['section']) || $_GET['section'] == 'agenda') { ?>
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Duplicate Agenda</h4>
                                </div>
                                <div class="content">
                                    <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                        <div class="form-group">
                                            <label for="duplicateMeetingNum">Meeting to Copy</label>
                                            <select class="form-control" name="targetMeetingNum" id="duplicateMeetingNum">
                                                <option selected disabled></option>
                                                <?php
                                                $meetings = Meetings::read([
                                                    "bodyUniqueId" => $_GET['bodyUniqueId'],
                                                    "sessionUniqueId" => $_GET['sessionUniqueId'],
                                                ]);

                                                foreach($meetings as $m) {
                                                    if($m['meetingNum'] != $_GET['meetingNum']) {
                                                        echo "<option value='$m[meetingNum]'>" . constructMeetingTitle($m) . "</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                            <p class="help-block small">
                                                <strong>Warning:</strong> this action will <strong>replace</strong> the current agenda for <?=constructMeetingTitle($meeting)?>, if any items currently exist for this meeting. If the target meeting has no agenda items, this meeting will have a blank agenda.
                                            </p>
                                        </div>

                                        <input type="hidden" name="transaction" value="duplicate_agenda">
                                        <input type="hidden" name="meetingNum" value="<?=$_GET['meetingNum']?>">
                                        <input type="hidden" name="sessionUniqueId" value="<?=$_GET['sessionUniqueId']?>">
                                        <input type="hidden" name="bodyUniqueId" value="<?=$_GET['bodyUniqueId']?>">
                                        <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Duplicate</button>
                                        <div class="clearfix"></div>
                                    </form>
                                </div>
                            </div>
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Add Agenda Item</h4>
                                </div>
                                <div class="content">
                                    <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                        <div class="form-group">
                                            <label>Name <?=$requiredIndicator?></label>
                                            <input type="text" name="name" class="form-control" placeholder="Name">
                                        </div>
                                        <div class="form-group">
                                            <label>Presenter</label>
                                            <input type="text" name="presenter" class="form-control" placeholder="Presenter">
                                        </div>
                                        <div class="form-group">
                                            <label>Insert After <?=$requiredIndicator?></label>
                                            <select name="previousItem" class="form-control">
                                                <?=recursivelyBuildAgendaSelect($agendaItems)?>
                                                <option value="-1" selected>End of Agenda</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Parent Item <?=$requiredIndicator?></label>
                                            <select name="parentId" class="form-control">
                                                <option value="-1" selected>(none)</option>
                                                <?=recursivelyBuildAgendaSelect($agendaItems)?>
                                            </select>
                                            <p class="help-block small">Setting a parent item will ignore the "Insert After" value.</p>
                                        </div>

                                        <input type="hidden" name="transaction" value="create_agenda_item">
                                        <input type="hidden" name="sessionUniqueId" value="<?=$_GET['sessionUniqueId']?>">
                                        <input type="hidden" name="bodyUniqueId" value="<?=$_GET['bodyUniqueId']?>">
                                        <input type="hidden" name="meetingNum" value="<?=$_GET['meetingNum']?>">
                                        <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Add Agenda Item</button>
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