<?php
require_once 'includes/auth.php';
require_once 'includes/api.php';
require_once 'includes/helpers.php';

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

$result = false;

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    $data = $_POST;
    $transaction = $data['transaction'];
    unset($data['transaction']);

    if($transaction == 'create_project') {
        $uniqueIds = explode('/', $data['fullUniqueId'], 2);
        unset($data['fullUniqueId']);

        $data['bodyUniqueId'] = $uniqueIds[0];
        $data['sessionUniqueId'] = $uniqueIds[1];
        $result = Projects::create($data);
    } else if($transaction == 'delete_project') {
        $result = Projects::delete($data);
    }
}

$pageTitle = "Projects";

$projects = Projects::read();

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
                                    <?php if(isset($_GET['bodyUniqueId']) && isset($_GET['sessionUniqueId'])) { ?>
                                        <li><a href="meetings.php"><?=$pageTitle?></a></li>
                                        <li class="active"><?=$session['name']?></li>
                                    <?php } else { ?>
                                        <li class="active"><?=$pageTitle?></li>
                                    <?php } ?>
                                </ol>
                            </div>
                        </div>
                        <div class="card">
                            <div class="header">
                                <h4 class="title">Projects</h4>
                            </div>
                            <div class="content table-responsive table-full-width">
                                <table class="table table-hover table-striped">
                                    <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Sessions</th>
                                        <th>Assigned Sub-Body</th>
                                        <th>Contact Person</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    if(count($projects) == 0) {
                                        echo "<tr class='text-muted text-center'><td colspan='5'><em>No projects were found!</em></td></tr>";
                                    } else {
                                        foreach ($projects as $p) {
                                            echo "<tr>";
                                            echo "<td>$p[name]</td>";
                                            echo "<td>" . $p['session']['name'] . "</td>";
                                            echo "<td>" . (isset($p['subbodyUniqueId']) ? ("<a href='/subbody.php?bodyUniqueId=$p[bodyUniqueId]&sessionUniqueId=$p[sessionUniqueId]&uniqueId=$p[subbodyUniqueId]'>" . $p['subbody']['name'] . "</a>") : "None") . "</td>";
                                            echo "<td>" . (isset($p['contactPersonRcsId']) ? ("<a href='/person.php?rcsId=$p[contactPersonRcsId]'>" . $p['contactPerson']['name'] . "</a>") : "None") . "</td>";
                                            echo "<td>
                                                            <form class='form-inline' method='post' action='$_SERVER[REQUEST_URI]' onsubmit='return confirm('Are you sure you want to delete $p[name]?');'>
                                                                <a href='/project.php?id=$p[id]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a>
                                                                
                                                                <input type='hidden' name='transaction' value='delete_project'>
                                                                <input type='hidden' name='id' value='$p[id]'>
                                                                <button class='btn btn-default btn-xs' type='submit'><span class='fa fa-trash'></span></button>
                                                            </form>
                                                        </td>";
                                            echo "</tr>";
                                        }
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
                                <h4 class="title">Add Project</h4>
                            </div>
                            <div class="content">
                                <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                    <div class="form-group">
                                        <label for="projectName">Name <?=$requiredIndicator?></label>
                                        <input type="text" name="name" id="projectName" class="form-control" placeholder="Name">
                                    </div>
                                    <div class="form-group">
                                        <label for="projectDescription">Description <?=$requiredIndicator?></label>
                                        <textarea name="description" id="projectDescription" class="form-control" placeholder="Description"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="projectFullUniqueId">Session</label>
                                        <select class="form-control" name="fullUniqueId" id="projectFullUniqueId">
                                            <option selected disabled>Select a session...</option>
                                            <?php
                                            $bodies = Bodies::read();

                                            foreach($bodies as $b) {
                                                echo "<optgroup label='$b[name]'>";
                                                foreach($b['sessions'] as $s) {
                                                    echo "<option value='$s[fullUniqueId]'>$s[name]</option>";
                                                }
                                                echo "</optgroup>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <input type="hidden" name="transaction" value="create_project">
                                    <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Add Project</button>
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
