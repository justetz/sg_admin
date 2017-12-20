<?php
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

date_default_timezone_set('America/New_York');

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

blockUnauthorized();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    $data = $_POST;
    $transaction = $data['transaction'];
    unset($data['transaction']);

    if ($transaction == 'create_update') {
        if(ini_get('file_uploads') == 1 && isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
            $data['image'] = file_get_contents($_FILES['image']['tmp_name']);
        }

        if(isset($data['title']) && empty($data['title'])) unset($data['title']);
        if(isset($data['text']) && empty($data['text'])) unset($data['text']);

        if(!empty($data['sessionFullUniqueId'])) {
            $uniqueIds = explode('/', $data['sessionFullUniqueId'], 2);
            $data['bodyUniqueId'] = $uniqueIds[0];
            $data['sessionUniqueId'] = $uniqueIds[1];
        }

        if(isset($data['displayContact'])) {
            $data['displayContact'] = $data['displayContact'] != 'off';
        }

        $result = Updates::create($data);

        echo json_encode($result); exit;
    }
} else {
    $result = false;
}

$pageTitle = "Create Update";

function isSubmissionAttempt () {
    return $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction']) && $_POST['transaction'] == 'create_update';
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
                <form method="post" action="<?=$_SERVER['REQUEST_URI']?>" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-11">
                            <div class="card">
                                <div class="content content-even">
                                    <ol class="breadcrumb">
                                        <li><a href="/updates.php">Updates</a></li>
                                        <li class='active'>Create</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="card">
                                <div class="content content-even">
                                    <input type='hidden' name='transaction' value='create_update'>
                                    <button type='submit' class='btn btn-primary btn-fill btn-block btn-borderless'>Create</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Text</h4>
                                </div>
                                <div class="content content-even">
                                    <div class="form-group">
                                        <textarea title="Update Text" name="text" rows="36" class="form-control" data-provide="markdown"
                                              data-iconlibrary="fa"><?=isSubmissionAttempt() ? $_POST["text"] : ''?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Details</h4>
                                </div>
                                <div class="content">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="title">Title <?=$requiredIndicator?></label>
                                                <input name="title" id="title" type="text" class="form-control"
                                                       value="<?=isSubmissionAttempt() ? $_POST["title"] : ''?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="sessionFullUniqueId">Session <?=$requiredIndicator?></label>
                                                <select name="sessionFullUniqueId" id="sessionFullUniqueId" class="form-control">
                                                    <option disabled selected></option>
                                                    <?php
                                                    $bodies = Bodies::read();

                                                    foreach($bodies as $b) {
                                                        echo "<optgroup label='$b[name]'>";

                                                        foreach($b['sessions'] as $s) {
                                                            echo "<option value='$s[fullUniqueId]'" .
                                                                (isSubmissionAttempt() && $s['fullUniqueId'] == $_POST['fullUniqueId'] ? ' selected' : '') . ">$s[name]</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Featured Image</h4>
                                </div>
                                <div class="content content-even">
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <div class="form-group">
                                                <label for="image">Upload Image <?=$requiredIndicator?></label>
                                                <input type="file" name="image" id="image">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Contact Person</h4>
                                </div>
                                <div class="content content-even">
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <div class="form-group">
                                                <label for="contactRcsId">Contact Person RCS ID</label>
                                                <input type="text" class="form-control" id="contactRcsId" value="<?=strtolower(phpCAS::getUser())?>" disabled>
                                                <input type="hidden" name="contactRcsId" value="<?=strtolower(phpCAS::getUser())?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <div class="form-group">
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" name="displayContact" title="Display Contact Person" data-toggle="checkbox" <?=isSubmissionAttempt() && isset($_POST['displayContact']) && $_POST['displayContact'] ? 'checked' : ''?>> Display Contact Person
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php require_once 'partials/footer.php' ?>
    </div>
</div>
<?php require_once 'partials/scripts.php' ?>
<?=buildMessage($result, $_POST)?>
</body>
</html>