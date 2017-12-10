<?php
require_once 'includes/auth.php';
require_once 'includes/api.php';
require_once 'includes/helpers.php';

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

blockUnauthorized();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    $data = $_POST;
    $transaction = $data['transaction'];
    unset($data['transaction']);

    if ($transaction == 'update_membership') {
        $result = Memberships::update($data);
    } else {
        $result = false;
    }
} else if(!isset($_GET['id'])) {
    header('location: ./people.php');
    exit;
} else {
    $result = false;
}

$membership = Memberships::getEntry($_GET['id']);

$pageTitle = "Manage Membership: $membership[name]";
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
                                    <li><a href="person.php?rcsId=<?=$membership['personRcsId']?>&section=memberships"><?=$membership['person']['name']?></a></li>
                                    <li class="active"><?=$membership['name']?></li>
                                </ol>
                            </div>
                        </div>

                        <div class="card">
                            <div class="header">
                                <h4 class="title">Membership Details</h4>
                            </div>
                            <div class="content content-even">
                                <form method="post">
                                    <div class="row">
                                        <div class="col-xs-4">
                                            <div class="form-group">
                                                <label for="personName">Person</label>
                                                <input type="text" class="form-control" name="personName" placeholder="Person Name" value="<?=$membership['person']['name']?>" disabled />
                                            </div>
                                        </div>
                                        <div class="col-xs-4">
                                            <div class="form-group">
                                                <label for="positionName">Position</label>
                                                <input type="text" class="form-control" name="positionName" placeholder="Position Name" value="<?=$membership['position']['name']?>" disabled />
                                            </div>
                                        </div>
                                        <div class="col-xs-4">
                                            <div class="form-group">
                                                <label for="personName">Session</label>
                                                <input type="text" class="form-control" name="sessionName" placeholder="Session Name" value="<?=$membership['session']['name']?>" disabled />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <div class="form-group">
                                                <label for="name">Membership-Specific Title</label>
                                                <input type="text" class="form-control" name="name" placeholder="Membership-Specific Title" value="<?=$membership['name']?>" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <label for="startDate">Start Date</label>
                                                <input type="date" class="form-control" name="startDate" placeholder="Start Date" value="<?=$membership['startDate']?>" />
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <label for="endDate">End Date</label>
                                                <input type="date" class="form-control" name="endDate" placeholder="End Date" value="<?=$membership['endDate']?>" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="yearOnly" data-toggle="checkbox" <?=$membership['yearOnly'] ? 'selected' : ''?>> Year Only
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="transaction" value="update_membership">
                                    <input type="hidden" name="id" value="<?=$_GET['id']?>">
                                    <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Update Membership</button>
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