<?php
require_once 'includes/auth.php';
require_once 'includes/api.php';
require_once 'includes/helpers.php';

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
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
} else if (!isset($_GET['id'])) {
    header('location: ./people.php');
    exit;
} else {
    $result = false;
}

$position = Positions::getEntry($_GET['id']);
$memberships = Memberships::read([
    "positionId" => $_GET['id'],
    "sort" => "-endDate,-startDate,name"
]);

$pageTitle = "Manage Position: $position[name]";
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
                                    <li class="active"><?=$position['name']?></li>
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
                                    <th>Position</th>
                                    <th>Current</th>
                                    <th>Session</th>
                                    <th>Term</th>
                                    <th></th>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach($memberships as $m) {
                                        echo "<tr>";
                                        echo "<td><a href='person.php?rcsId=$m[personRcsId]'>" . $m['person']['name'] . "</a></td>";
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
                                <h4 class="title">Edit Position</h4>
                            </div>
                            <div class="content">
                                <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                    <div class="form-group">
                                        <label>Position Title <?=$requiredIndicator?></label>
                                        <input type="text" class="form-control" value="<?=$position['name']?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Position Title <?=$requiredIndicator?></label>
                                        <select class="form-control" name="bodyUniqueId">
                                            <?php
                                            $bodies = Bodies::read();

                                            if(!isset($position['bodyUniqueId'])) {
                                                echo "<option selected disabled></option>";
                                            }

                                            foreach($bodies as $b) {
                                                echo "<option value='$b[uniqueId]' " . ((isset($position['bodyUniqueId']) && $position['bodyUniqueId'] == $b['uniqueId']) ? 'selected' : '') . ">$b[name]</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="yearOnly" data-toggle="checkbox" <?=$position['voting'] ? 'checked' : ''?>> Is Voting
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="yearOnly" data-toggle="checkbox" <?=$position['officer'] ? 'checked' : ''?>> Is Officer
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="yearOnly" data-toggle="checkbox" <?=$position['presidingOfficer'] ? 'checked' : ''?>> Is Presiding Officer
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Update Position</button>
                                    <div class="clearfix"></div>
                                </form>
                            </div>
                        </div>
                        <div class="card">
                            <div class="header">
                                <h4 class="title">Add Membership</h4>
                            </div>
                            <div class="content">
                                <form>
                                    <div class="form-group">
                                        <label>RCS ID <?=$requiredIndicator?></label>
                                        <input type="text" name="rcsId" class="form-control" placeholder="RCS ID">
                                    </div>
                                    <div class="form-group">
                                        <label>Position <?=$requiredIndicator?></label>
                                        <input type="text" class="form-control" disabled value="<?=$position['name']?>">
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