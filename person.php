<?php
require_once 'includes/auth.php';
require_once 'includes/api.php';
require_once 'includes/helpers.php';

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    $data = $_POST;
    $transaction = $data['transaction'];
    unset($data['transaction']);

    if ($transaction == 'delete_membership') {
        $result = Memberships::delete($data);
    } else {
        $result = false;
    }
} else if(!isset($_GET['rcsId'])) {
    header('location: ./people.php');
    exit;
}

$person = json_decode(file_get_contents($API_BASE . "api/people/$_GET[rcsId]"), true);
$positions = json_decode(file_get_contents($API_BASE . "api/positions?sort=-presidingOfficer,name"), true);

$pageTitle = "Manage Person: $person[name]";
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
                                        <li><a href="people.php">People & Memberships</a></li>
                                        <li class="active"><?=$person['name']?></li>
                                    </ol>
                                </div>
                            </div>

                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Memberships</h4>
                                </div>
                                <div class="content table-responsive table-full-width">
                                    <table class="table table-hover table-striped">
                                        <thead>
                                            <th>Title</th>
                                            <th>Position</th>
                                            <th>Current</th>
                                            <th>Session</th>
                                            <th>Term</th>
                                            <th></th>
                                        </thead>
                                        <tbody>
                                            <?php
                                            foreach($person['memberships'] as $m) {
                                                echo "<tr>";
                                                echo "<td>$m[name]</td>";
                                                echo "<td><a href='position.php?id=$m[positionId]'>" . $m['position']['name'] . "</a></td>";
                                                echo "<td>" . ($m['current'] ? 'Yes' : 'No') . "</td>";
                                                echo "<td><a href='session.php?bodyUniqueId=$m[bodyUniqueId]&uniqueId=$m[sessionUniqueId]'>" . $m['session']['name'] . "</a></td>";
                                                echo "<td>$m[term]</td>";
                                                echo "<td>
                                                    <form class='form-inline' method='post' action='$_SERVER[REQUEST_URI]'>
                                                        <a href='$_SERVER[REQUEST_URI]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a>
                                                        
                                                        <input type=\"hidden\" name=\"transaction\" value=\"delete_membership\">
                                                        <input type=\"hidden\" name=\"id\" value=\"$m[id]\">
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
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Add Membership for <?=$person['name']?></h4>
                                </div>
                                <div class="content">
                                    <form>
                                        <div class="form-group">
                                            <label>RCS ID <?=$requiredIndicator?></label>
                                            <input type="text" name="rcsId" class="form-control" placeholder="RCS ID" disabled value="<?=$person['rcsId']?>">
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once 'partials/scripts.php' ?>
    <?=buildMessage($result, $_POST)?>
</body>
</html>