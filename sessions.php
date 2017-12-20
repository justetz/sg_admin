<?php
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

if (!phpCAS::isAuthenticated()) {
    header('location: ./index.php');
    exit;
}

blockUnauthorized();

$pageTitle = "Bodies &amp; Sessions";

function shouldExpandBody ($uniqueId) {
    return isset($_GET['expand-' . $uniqueId]) && $_GET['expand-' . $uniqueId];
}

function urlToggleExpand ($uniqueId) {
    $newGet = $_GET;

    if (isset($_GET["expand-$uniqueId"]) && $_GET["expand-$uniqueId"]) {
        unset($newGet["expand-$uniqueId"]);
    } else {
        $newGet["expand-$uniqueId"] = true;
    }

    return "/sessions.php" . (count($newGet) ? ("?" . http_build_query($newGet)) : '');
}

$bodies = Bodies::read();

$presidingOfficerIds = [];
foreach($bodies as $b) {
    if(count($b['presidingOfficers']) > 0) {
        $presidingOfficerIds[] = $b['presidingOfficers'][0]['id'];
    }
}

$presidingOfficers = Memberships::read([
    "positionId" => json_encode($presidingOfficerIds)
]);

$presidingOfficerMap = [];
foreach($presidingOfficers as $o) {
    $presidingOfficerMap[$o['bodyUniqueId'] . '/' . $o['sessionUniqueId']] = [
        "name" => $o['person']['name'],
        "rcsId" => $o['person']['rcsId']
    ];
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
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="content content-even">
                                    <ol class="breadcrumb">
                                        <li class="active">Bodies &amp; Sessions</li>
                                    </ol>
                                </div>
                            </div>
                            <div class="card">
                                <div class="content content-even">
                                    <ul class="nav nav-pills">
                                        <li role="presentation" <?=(!isset($_GET['section']) || $_GET['section'] == 'active') ? 'class="active"' : ''?>>
                                            <a href="<?=toggleGetParam('section','active')?>">Active Sessions</a>
                                        </li>
                                        <li role="presentation" <?=(isset($_GET['section']) && $_GET['section'] == 'inactive') ? 'class="active"' : ''?>>
                                            <a href="<?=toggleGetParam('section','inactive')?>">Inactive Sessions</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <?php if(!isset($_GET['section']) || $_GET['section'] == 'active') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Active Sessions &amp; Sub-Bodies</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Name</th>
                                                    <th>Body</th>
                                                    <th>Presiding Officer</th>
                                                    <th>Unique Identifier</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    $activeSessions = Sessions::read([
                                                        "active" => "true"
                                                    ]);

                                                    foreach($activeSessions as $s) {
                                                        echo "<tr>";
                                                        echo "<td><a href='" . urlToggleExpand($s['bodyUniqueId']) . "' class='btn btn-default btn-xs'><span class='fa fa-caret-" . (shouldExpandBody($s['bodyUniqueId']) ? "down" : "right") . "'></span></a></td>";
                                                        echo "<td>$s[name]</td>";
                                                        echo "<td>" . $s['body']['name'] . "</td>";
                                                        echo "<td><a href='/person.php?rcsId=" . $presidingOfficerMap[$s['bodyUniqueId'] . '/' . $s['uniqueId']]['rcsId'] . "'>" . $presidingOfficerMap[$s['bodyUniqueId'] . '/' . $s['uniqueId']]['name'] . "</a></td>";
                                                        echo "<td><span class='text-muted'>$s[bodyUniqueId]/</span>$s[uniqueId]</td>";
                                                        echo "<td><a href='/session.php?bodyUniqueId=$s[bodyUniqueId]&uniqueId=$s[uniqueId]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a></td>";
                                                        echo "</tr>";

                                                        if(shouldExpandBody($s['bodyUniqueId'])) {
                                                            $subbodies = Subbodies::read([
                                                                "bodyUniqueId" => $s['bodyUniqueId'],
                                                                "sessionUniqueId" => $s['uniqueId']
                                                            ]);

                                                            foreach($subbodies as $sub) {
                                                                echo "<tr>";
                                                                echo "<td></td>";
                                                                echo "<td style='padding-left: 30px;' colspan='2'>$sub[name]</td>";

                                                                echo "<td>";
                                                                if(isset($sub['presidingOfficerPositionId'])) {
                                                                    if(count($sub['presidingOfficerPosition']['memberships']) == 0) {
                                                                        echo "<em>vacant</em>";
                                                                    } else {
                                                                        echo "<a href='/person.php?rcsId=" . $sub['presidingOfficerPosition']['memberships'][0]['personRcsId'] . "'>";
                                                                        echo $sub['presidingOfficerPosition']['memberships'][0]['person']['name'];
                                                                        echo "</a>";
                                                                    }
                                                                }
                                                                echo "</td>";

                                                                echo "<td><span class='text-muted'>$sub[bodyUniqueId]/$sub[sessionUniqueId]/</span>$sub[uniqueId]</td>";
                                                                echo "<td><a href='/subbody.php?bodyUniqueId=$s[bodyUniqueId]&sessionUniqueId=$sub[sessionUniqueId]&uniqueId=$sub[uniqueId]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a></td>";
                                                                echo "</tr>";
                                                            }

                                                            if(count($subbodies) == 0) {
                                                                echo "<tr class='text-muted'><td></td><td colspan='5'><em>No subbodies exist for this session of the " . $s['body']['name'] . "</em></td></tr>";
                                                            }
                                                        }
                                                    }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php } else if(isset($_GET['section']) && $_GET['section'] == 'inactive') { ?>
                                <div class="card">
                                    <div class="header">
                                        <h4 class="title">Inactive Sessions</h4>
                                    </div>
                                    <div class="content table-responsive table-full-width">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Body</th>
                                                    <th>Presiding Officer</th>
                                                    <th>Unique Identifier</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    $inactiveSessions = Sessions::read([
                                                        "active" => "false",
                                                        "sort" => "-name"
                                                    ]);

                                                    foreach($inactiveSessions as $s) {
                                                        echo "<tr>";
                                                        echo "<td>$s[name]</td>";
                                                        echo "<td>" . $s['body']['name'] . "</td>";
                                                        if(isset($presidingOfficerMap[$s['bodyUniqueId'] . '/' . $s['uniqueId']])) {
                                                            echo "<td><a href='/person.php?rcsId=" . $presidingOfficerMap[$s['bodyUniqueId'] . '/' . $s['uniqueId']]['rcsId'] . "'>" . $presidingOfficerMap[$s['bodyUniqueId'] . '/' . $s['uniqueId']]['name'] . "</a></td>";
                                                        } else {
                                                            echo "<td><em>vacant</em></td>";
                                                        }
                                                        echo "<td><span class='text-muted'>$s[bodyUniqueId]/</span>$s[uniqueId]</td>";
                                                        echo "<td><a href='/session.php?bodyUniqueId=$s[bodyUniqueId]&uniqueId=$s[uniqueId]' class='btn btn-primary btn-xs'><span class='fa fa-gear'></span> Manage</a></td>";
                                                        echo "</tr>";
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
                                <div class="header">
                                    <h4 class="title">Student Government Bodies</h4>
                                </div>
                                <div class="content table-responsive table-full-width">
                                    <table class="table table-hover table-striped">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Unique Identifier</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                foreach($bodies as $b) {
                                                    echo "<tr>";
                                                    echo "<td>$b[name]</td>";
                                                    echo "<td>$b[uniqueId]</td>";
                                                    echo "<td><a class='btn btn-primary btn-xs' href='/body.php?uniqueId=$b[uniqueId]'><span class='fa fa-gear'></span> Manage</a></td>";
                                                    echo "</tr>";
                                                }
                                            ?>
                                        </tbody>
                                    </table>

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
