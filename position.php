<?php
require_once 'includes/auth.php';
require_once 'includes/sg_data_php_driver/api.php';
require_once 'includes/helpers.php';

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

$result = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    $data = $_POST;
    $transaction = $data['transaction'];
    unset($data['transaction']);

    if ($transaction == 'update_position') {
        standardizeCheckboxInput($data, 'voting');
        standardizeCheckboxInput($data, 'officer');
        standardizeCheckboxInput($data, 'presidingOfficer');
        $result = Positions::update($data);
    } else if ($transaction == 'create_membership') {
        $result = Memberships::create($data);
    }
} else if (!isset($_GET['id'])) {
    header('location: ./people.php');
    exit;
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
                                    <li><a href="positions.php">Positions</a></li>
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
                                    <th>Person</th>
                                    <th>Current</th>
                                    <th>Session</th>
                                    <th>Term</th>
                                    <th></th>
                                    </thead>
                                    <tbody>
                                    <?php
                                    if(count($memberships) == 0) {
                                        echo "<tr class='text-muted text-center'><td colspan='6'><em>$position[name] does not have any associated memberships!</em></td></tr>";
                                    }

                                    foreach($memberships as $m) {
                                        echo "<tr>";
                                        echo "<td><a href='person.php?rcsId=$m[personRcsId]'>" . $m['person']['name'] . "</a></td>";
                                        echo "<td>" . ($m['current'] ? 'Yes' : 'No') . "</td>";
                                        echo "<td><a href='session.php?bodyUniqueId=$m[bodyUniqueId]&uniqueId=$m[sessionUniqueId]'>" . $m['session']['name'] . "</a></td>";
                                        echo "<td>$m[term]</td>";
                                        echo "<td>
                                                    <form class='form-inline' method='post' action='$_SERVER[REQUEST_URI]'>
                                                        <a href='membership.php?id=$m[id]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a>
                                                        
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
                                        <label for="positionName">Position Title <?=$requiredIndicator?></label>
                                        <input type="text" class="form-control" name="name" id="positionName" value="<?=$position['name']?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="bodyName">Body <?=$requiredIndicator?></label>
                                        <input type="text" class="form-control" id="bodyName" disabled value="<?=$position['body']['name']?>">
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="voting" data-toggle="checkbox" <?=$position['voting'] ? 'checked' : ''?>> Is Voting
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="officer" data-toggle="checkbox" <?=$position['officer'] ? 'checked' : ''?>> Is Officer
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="presidingOfficer" data-toggle="checkbox" <?=$position['presidingOfficer'] ? 'checked' : ''?>> Is Presiding Officer
                                        </label>
                                    </div>

                                    <input type="hidden" name="transaction" value="update_position">
                                    <input type="hidden" name="id" value="<?=$position['id']?>">
                                    <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Update Position</button>
                                    <div class="clearfix"></div>
                                </form>
                            </div>
                        </div>
                        <?=generateAddMembershipCard('create_membership', $position['bodyUniqueId'], null, $position['id'], null)?>
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