<?php
require_once 'includes/auth.php';
require_once 'includes/sg_data_php_driver/api.php';
require_once 'includes/helpers.php';

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

if (isset($_GET['section']) && $_GET['section'] != 'profile') {
    blockUnauthorized();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction'])) {
    $data = $_POST;
    $transaction = $data['transaction'];
    unset($data['transaction']);

    if($transaction == 'update_biography') {
        $targetPerson = People::getEntry($data['rcsId']);

        if(isset($data['biography']) && $targetPerson['biography'] != $data['biography'])
            $targetPerson['biography'] = $data['biography'];

        $result = People::update($targetPerson);
    } else if ($transaction == 'update_profile') {
        $targetPerson = People::getEntry($data['rcsId']);

        if(isset($data['name']) && $targetPerson['name'] != $data['name'])
            $targetPerson['name'] = $data['name'];

        if(isset($data['email']) && $targetPerson['email'] != $data['email'])
            $targetPerson['email'] = $data['email'];

        if(isset($data['hometown']) && $targetPerson['hometown'] != $data['hometown'])
            $targetPerson['hometown'] = $data['hometown'];

        if(isset($data['classYear']) && $targetPerson['classYear'] != $data['classYear'])
            $targetPerson['classYear'] = $data['classYear'];

        if(isset($data['major']) && $targetPerson['major'] != $data['major'])
            $targetPerson['major'] = $data['major'];

        if(isset($data['committees']) && $targetPerson['committees'] != $data['committees'])
            $targetPerson['committees'] = $data['committees'];

        if(isset($data['campusInvolvements']) && $targetPerson['campusInvolvements'] != $data['campusInvolvements'])
            $targetPerson['campusInvolvements'] = $data['campusInvolvements'];

        $result = People::update($targetPerson);
    } else if ($transaction == 'create_membership') {
        $result = Memberships::create($data);
    } else if ($transaction == 'delete_membership') {
        $result = Memberships::delete($data);
    } else {
        $result = false;
    }
} else if(!isset($_GET['rcsId'])) {
    header('location: ./people.php');
    exit;
} else {
    $result = false;
}

$person = People::getEntry("$_GET[rcsId]");

if (!isset($person['rcsId']) && isset($person["message"]) && $person['message'] == 'Not Found') {
    // Person does not currently exist, create them with Club Management System data.
    $result = People::create(['rcsId' => $_GET['rcsId']]);

    if(isset($result['message']) && $result['message'] == 'Not Found') {
        header('location: ./people.php');
        exit;
    }

    $person = People::getEntry("$_GET[rcsId]");
}

$positions = Positions::read([
    "sort" => "-presidingOfficer,name"
]);

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
                            <?php if (IS_AUTHORIZED) { ?>
                                <div class="card">
                                    <div class="content content-even">
                                        <ol class="breadcrumb">
                                            <li><a href="people.php">People & Memberships</a></li>
                                            <li class="active"><?=$person['name']?></li>
                                        </ol>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="content content-even">
                                        <ul class="nav nav-pills">
                                            <li role="presentation" <?=(!isset($_GET['section']) || $_GET['section'] == 'profile') ? 'class="active"' : ''?>>
                                                <a href="<?=toggleGetParam('section','profile')?>">Profile</a>
                                            </li>
                                            <li role="presentation" <?=(isset($_GET['section']) && $_GET['section'] == 'memberships') ? 'class="active"' : ''?>>
                                                <a href="<?=toggleGetParam('section','memberships')?>">Memberships</a>
                                            </li>
                                            <li role="presentation" <?=(isset($_GET['section']) && $_GET['section'] == 'actions') ? 'class="active"' : ''?>>
                                                <a href="<?=toggleGetParam('section','actions')?>">Actions</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if(!isset($_GET['section']) || $_GET['section'] == 'profile') { ?>
                                <?php if(!IS_AUTHORIZED && count($person['memberships']) == 0) { ?>
                                    <div class="card">
                                        <div class="header">
                                            <h4 class="title">Hello!</h4>
                                        </div>
                                        <div class="content content-even">
                                            <p>It appears you are not a member of student government, nor have you been in the past. If you believe this is in error, please contact the Student Government.</p>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <div class="card">
                                        <div class="header">
                                            <h4 class="title">About</h4>
                                        </div>
                                        <div class="content content-even">
                                            <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                                <div class="form-group">
                                                    <textarea title="Biography" name="biography" id="biography" rows="24" class="form-control" data-provide="markdown"
                                                              data-iconlibrary="fa"><?=$person["biography"]?></textarea>
                                                </div>

                                                <input type="hidden" name="transaction" value="update_biography">
                                                <input type="hidden" name="rcsId" value="<?=$person['rcsId']?>">
                                                <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Update About</button>
                                                <div class="clearfix"></div>
                                            </form>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } else if(isset($_GET['section']) && $_GET['section'] == 'memberships') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Memberships</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <th>Membership Title</th>
                                                <th>Position</th>
                                                <th>Current</th>
                                                <th>Session</th>
                                                <th>Term</th>
                                                <th></th>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if(count($person['memberships']) == 0) {
                                                    echo "<tr class='text-muted text-center'><td colspan='6'><em>$person[name] does not have any associated memberships!</em></td></tr>";
                                                }

                                                foreach($person['memberships'] as $m) {
                                                    echo "<tr>";
                                                    echo "<td>$m[name]</td>";
                                                    echo "<td><a href='position.php?id=$m[positionId]'>" . $m['position']['name'] . "</a></td>";
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
                            <?php } else if(isset($_GET['section']) && $_GET['section'] == 'actions') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Actions Sponsored by <?=$person['name']?></h4>
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
                                                $membershipIds = [];
                                                foreach($person['memberships'] as $m) {
                                                    $membershipIds[] = $m['id'];
                                                }
                                                $actions = Actions::read(['movingMemberId' => json_encode($membershipIds)]);

                                                if(count($actions) == 0) {
                                                    echo "<tr class='text-muted text-center'><td colspan='6'><em>No actions found for $person[name]!</em></td></tr>";
                                                } else {
                                                    foreach ($actions as $a) {
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

                                                        echo "<td><a href='/action.php?bodyUniqueId=$a[bodyUniqueId]&sessionUniqueId=$a[sessionUniqueId]&meetingNum=$a[meetingNum]&actionNum=$a[actionNum]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a></td>";
                                                        echo "</tr>";
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="content content-even">
                                    <div class="row">
                                        <div class="col-xs-3">
                                            <img class="img-responsive" src="http://photos.sg.rpi.edu/headshot_<?=$person['rcsId']?>.jpg">
                                        </div>
                                        <div class="col-xs-9">
                                            <h4 class="title"><?=$person['name']?></h4>
                                            <?php $displayEmail = (isset($person['email']) ? $person['email'] : "$person[rcsId]@rpi.edu"); ?>
                                            <p><a href="mailto:<?=$displayEmail?>"><?=$displayEmail?></a></p>
                                            <a href="http://test.sg.rpi.edu/people/member_detail.php?rcsId=<?=$person['rcsId']?>" class="btn btn-primary btn-sm"><i class="fa fa-user"></i> View Public Profile</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if(!isset($_GET['section']) || $_GET['section'] == 'profile') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Profile</h4>
                                    </div>
                                    <div class="content content-even">
                                        <form method="post" action="<?=$_SERVER['REQUEST_URI']?>">
                                            <div class="form-group">
                                                <label for="displayRcsId">RCS ID</label>
                                                <input type="text" class="form-control" name="displayRcsId" id="displayRcsId" value="<?=$person['rcsId']?>" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label for="name">Person Name <?=$requiredIndicator?></label>
                                                <input type="text" class="form-control" name="name" id="name" value="<?=$person['name']?>">
                                            </div>
                                            <?php if(IS_AUTHORIZED || count($person['memberships']) > 0) { ?>
                                                <div class="form-group">
                                                    <label for="email">Email <?=$requiredIndicator?></label>
                                                    <input type="text" class="form-control" name="email" id="email" value="<?=$person['email']?>">
                                                    <p class="help-block small">If omitted, your email will be set to your default RPI email.</p>
                                                </div>
                                                <div class="form-group">
                                                    <label for="hometown">Hometown</label>
                                                    <input type="text" class="form-control" name="hometown" id="hometown" value="<?=$person['hometown']?>">
                                                </div>
                                                <div class="form-group">
                                                    <label for="classYear">Class Year / Cohort</label>
                                                    <input type="text" class="form-control" name="classYear" id="classYear" value="<?=$person['classYear']?>">
                                                </div>
                                                <div class="form-group">
                                                    <label for="classYear">Major(s)</label>
                                                    <input type="text" class="form-control" name="major" id="major" value="<?=$person['major']?>">
                                                    <p class="help-block small">Separate multiple majors by semicolon.</p>
                                                </div>
                                                <div class="form-group">
                                                    <label for="committees">Committees</label>
                                                    <textarea class="form-control" name="committees" id="committees" rows="2"><?=$person['committees']?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label for="campusInvolvements">Campus Involvements</label>
                                                    <textarea class="form-control" name="campusInvolvements" id="campusInvolvements" rows="2"><?=$person['campusInvolvements']?></textarea>
                                                </div>
                                            <?php } ?>

                                            <input type="hidden" name="transaction" value="update_profile">
                                            <input type="hidden" name="rcsId" value="<?=$person['rcsId']?>">
                                            <button type="submit" class="btn btn-primary btn-sm btn-fill pull-right">Update Profile</button>
                                            <div class="clearfix"></div>
                                        </form>
                                    </div>
                                </div>
                            <?php } else if(isset($_GET['section']) && $_GET['section'] == 'memberships') { ?>
                                <?=generateAddMembershipCard('create_membership', null, null, null, $person['rcsId'])?>
                            <?php } else if(isset($_GET['section']) && $_GET['section'] == 'actions') { ?>
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Create Action</h4>
                                </div>
                                <div class="content content-even">
                                    <p class="text-muted">To add an action, visit the Create Action page, which can be found by clicking the button below.</p>
                                    <a href="new_action.php" class="btn btn-primary btn-fill btn-sm pull-right">Create Action</a>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                            <?php } ?>
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