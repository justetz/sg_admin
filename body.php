<?php
require_once 'includes/auth.php';
require_once 'includes/api.php';

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

blockUnauthorized();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    $data = $_POST;
    $transaction = $data['transaction'];
    unset($data['transaction']);


    if ($transaction == 'create_session') {
        $succeeded = create_session($data);
    } else {
        $succeeded = false;
    }

    if (!$succeeded) {
        $alertsToDisplay .= "Error";
    } else {
        $alertsToDisplay .= "Success";
    }
} else if(!isset($_GET['uniqueId'])) {
    header('location: ./sessions.php');
    exit;
}

$body = json_decode(file_get_contents($API_BASE . "api/bodies/" . $_GET['uniqueId']), true);

$pageTitle = "Edit Body: " . $body['name'];

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
                                        <li><a href="sessions.php">Bodies &amp; Sessions</a></li>
                                        <li class="active"><?=$body['name']?></li>
                                    </ol>
                                </div>
                            </div>
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Edit Body Settings</h4>
                                </div>
                                <div class="content">
                                    <form>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Unique Identifier</label>
                                                    <input type="text" class="form-control" disabled placeholder="Company" value="<?=$body['uniqueId']?>">
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label>Body Name</label>
                                                    <input type="text" name="name" class="form-control" placeholder="Body Name (e.g. 'Student Government')" value="<?=$body['name']?>">
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Update Body</button>
                                        <div class="clearfix"></div>
                                    </form>
                                </div>
                            </div>
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Sessions</h4>
                                </div>
                                <div class="content table-responsive table-full-width">
                                    <table class="table table-hover table-striped">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Unique Identifier</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                foreach($body['sessions'] as $s) {
                                                    echo "<tr>";
                                                    echo "<td>$s[name] " . ($s['active'] ? '<span class="text-muted">(Active)</span>' : '') . "</td>";
                                                    echo "<td><span class='text-muted'>$s[bodyUniqueId]/</span>$s[uniqueId]</td>";
                                                    echo "<td><div class='btn-group btn-group-xs'>
                                                        <a class='btn btn-default btn-xs" . ($s["active"] ? " disabled" : "") . "'>Make Active</a>
                                                        <a href='session.php?bodyUniqueId=$s[bodyUniqueId]&uniqueId=$s[uniqueId]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a>
                                                        </div></td>";
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
                                    <h4 class="title">Create New Session</h4>
                                </div>
                                <div class="content">
                                    <form>
                                        <div class="form-group">
                                            <label>Unique Identifier</label>
                                            <div class="input-group">
                                                <span class="input-group-addon" id="session-unique-id-prefix"><?=$body['uniqueId']?>/</span>
                                                <input type="text" name="uniqueId" class="form-control" placeholder="uniqueId" aria-describedby="session-unique-id-prefix">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Session Name</label>
                                            <input type="text" name="name" class="form-control" placeholder="Session Name">
                                        </div>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" data-toggle="checkbox"> Set as Active Session
                                            </label>
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-sm pull-right">Create Session</button>
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