<?php
require_once 'includes/auth.php';

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

blockUnauthorized();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    // $data = $_POST;
    // $transaction = $data['transaction'];
    // unset($data['transaction']);


    // if ($transaction == 'create_session') {
    //     if (isset($data['active']) && $data['active']) {
    //         markAllSessionsInactive($data);
    //     }
    //     $result = Sessions::create($data);
    // } else if ($transaction == 'mark_active') {
    //     markAllSessionsInactive($data);

    //     $session = Sessions::read([
    //         'bodyUniqueId' => $data['bodyUniqueId'],
    //         'uniqueId' => $data['uniqueId'],
    //     ])[0];

    //     $session['active'] = true;
    //     $result = Sessions::update($session);
    // } else {
    //     $result = false;
    // }
} else if(!isset($_GET['uniqueId'])) {
    header('location: ./sessions.php');
    exit;
}

$body = Bodies::getEntry($_GET['uniqueId']);
$prevSession = Sessions::read([
    'bodyUniqueId' => $body['uniqueId'],
    'active' => 'true',
])[0];
$memberships = Memberships::read([
    'bodyUniqueId' => $prevSession['bodyUniqueId'],
    'sessionUniqueId' => $prevSession['uniqueId'],
]);
$subbodies = Subbodies::read([
    'bodyUniqueId' => $prevSession['bodyUniqueId'],
    'sessionUniqueId' => $prevSession['uniqueId'],
]);

$pageTitle = "Post-Election Wizard - " . $body['name'];

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
                    <form method="post">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="content content-even">
                                        <ol class="breadcrumb">
                                            <li><a href="sessions.php">Bodies &amp; Sessions</a> <?=$alertsToDisplay?></li>
                                            <li><a href="body.php?uniqueId=<?=$body['uniqueId']?>"><?=$body['name']?></a></li>
                                            <li class="active">Post-Election Wizard</li>
                                        </ol>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Welcome to your new term!</h4>
                                    </div>
                                    <div class="content">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Previous Session</strong></p>
                                                <div class="form-group">
                                                    <label>Unique Identifier</label>
                                                    <input type="text" name="prevSessionFullUniqueId" class="form-control" value="<?=$prevSession['bodyUniqueId']?>/<?=$prevSession['uniqueId']?>" disabled style="color:black">
                                                </div>
                                                <div class="form-group">
                                                    <label>Session Name</label>
                                                    <input type="text" name="prevSessionName" class="form-control" value="<?=$prevSession['name']?>" disabled style="color:black">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>New Session</strong></p>
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
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Member Transferral</h4>
                                        <br>
                                        <p><em>Select all memberships you wish to copy from the <?=$prevSession['name']?> to your new session.</em></p>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Name</th>
                                                    <th>Position</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($memberships as $m) { ?>
                                                    <?php if (!isset($m['endDate'])) { ?>
                                                        <tr>
                                                            <td>
                                                                <div class="checkbox">
                                                                    <label>
                                                                        <input type="checkbox" data-toggle="checkbox" name="transfer_<?=$m['id']?>">
                                                                    </label>
                                                                </div>
                                                            </td>
                                                            <td><?=$m['person']['name']?></td>
                                                            <td><?=$m['name']?></td>
                                                        </tr>
                                                    <?php } ?>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Sub-Body Transferral</h4>
                                        <br>
                                        <p><em>Select all sub-bodies you wish to copy from the <?=$prevSession['name']?> to your new session.</em></p>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($subbodies as $sub) { ?>
                                                    <tr>
                                                        <td>
                                                            <div class="checkbox">
                                                                <label>
                                                                    <input type="checkbox" data-toggle="checkbox" name="transfer_<?=$sub['id']?>">
                                                                </label>
                                                            </div>
                                                        </td>
                                                        <td><?=$sub['name']?></td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php require_once 'partials/footer.php' ?>
        </div>
    </div>
    <?php require_once 'partials/scripts.php' ?>
</body>
</html>