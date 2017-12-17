<?php
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

blockUnauthorized();

$result = false;

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    $data = $_POST;
    $transaction = $data['transaction'];
    unset($data['transaction']);

    if ($transaction == 'create_position') {
        standardizeCheckboxInput($data, 'voting');
        standardizeCheckboxInput($data, 'officer');
        standardizeCheckboxInput($data, 'presidingOfficer');
        $result = Positions::create($data);
    }
}

$pageTitle = "Positions";

$params = [
    'sort' => 'name',
];

if(isset($_GET['bodyUniqueId'])) {
    $params['bodyUniqueId'] = $_GET['bodyUniqueId'];

    $body = Bodies::getEntry($_GET['bodyUniqueId']);
} else {
    $body = false;
}

$positions = Positions::read($params);

$bodies = Bodies::read();

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
                                    <?php
                                    if(isset($_GET['bodyUniqueId'])) {
                                        echo "<li><a href='/positions.php'>$pageTitle</a></li>";
                                        echo "<li class='active'>$body[name]</li>";
                                    } else {
                                        echo "<li class='active'>$pageTitle</li>";
                                    }
                                    ?>

                                </ol>
                            </div>
                        </div>
                        <div class="card">
                            <div class="header">
                                <h4 class="title">Positions</h4>
                            </div>
                            <div class="content table-responsive table-full-width">
                                <table class="table table-hover table-striped">
                                    <thead>
                                    <tr>
                                        <th width="40%">Position</th>
                                        <th width="20%">Body</th>
                                        <th>Is Voting?</th>
                                        <th>Is Officer?</th>
                                        <th>Is Presiding?</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach($positions as $p) {
                                        echo "<tr>";
                                        echo "<td>$p[name]</td>";
                                        echo "<td><a href='body.php?uniqueId=$p[bodyUniqueId]'>" . $p['body']['name'] . "</a></td>";
                                        echo "<td>" . ($p['voting'] ? "<span class='text-success-readable'>Yes</span>" : "<span class='text-danger-readable'>No</span>") . "</td>";
                                        echo "<td>" . ($p['officer'] ? "<span class='text-success-readable'>Yes</span>" : "<span class='text-danger-readable'>No</span>") . "</td>";
                                        echo "<td>" . ($p['presidingOfficer'] ? "<span class='text-success-readable'>Yes</span>" : "<span class='text-danger-readable'>No</span>") . "</td>";
                                        echo "<td><a href='/position.php?id=$p[id]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a></td>";
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
                                <h4 class="title">Filter by Body</h4>
                            </div>
                            <div class="content table-responsive table-full-width">
                                <table class="table">
                                    <tbody>
                                    <?php
                                    foreach($bodies as $b) {
                                        echo "<tr><td><a href='positions.php?bodyUniqueId=$b[uniqueId]'>";
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
                        <div class="card">
                            <div class="header">
                                <h4 class="title">Create Position</h4>
                            </div>
                            <div class="content content-even">
                                <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                    <div class="form-group">
                                        <label for="positionName">Position Title <?=$requiredIndicator?></label>
                                        <input name="name" id="positionName" type="text" class="form-control" placeholder="Position Title">
                                    </div>
                                    <div class="form-group">
                                        <label for="bodyUniqueId">Body <?=$requiredIndicator?></label>
                                        <select name="bodyUniqueId" id="bodyUniqueId" class="form-control">
                                            <option selected disabled>Select a body...</option>
                                            <?php
                                            foreach ($bodies as $b) {
                                                echo "<option value='$b[uniqueId]'>$b[name]</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="voting" data-toggle="checkbox"> Is Voting
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="officer" data-toggle="checkbox"> Is Officer
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="presidingOfficer" data-toggle="checkbox"> Is Presiding Officer
                                        </label>
                                    </div>

                                    <input type="hidden" name="transaction" value="create_position">
                                    <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Create Position</button>
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