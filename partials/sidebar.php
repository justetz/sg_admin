<?php
    function addActiveClassAppropriately($page) {
        if(explode("?", $_SERVER['REQUEST_URI'], 2)[0] == $page) {
            return "class='active'";
        } else {
            return "";
        }
    }

    function addActiveClassAppropriatelyMultiple($pages) {
        foreach($pages as $page) {
            $class = addActiveClassAppropriately($page);
            if(strlen($class) > 0) return $class;
        }

        return "";
    }

    function addActiveClassForProfileAppropriately() {
        if (explode("?", $_SERVER['REQUEST_URI'], 2)[0]  == '/person.php' && isset($_GET['rcsId']) && $_GET['rcsId'] == strtolower(phpCAS::getUser())) {
            return "class='active'";
        } else {
            return "";
        }
    }

    function addActiveClassForPersonPageAppropriately() {
        if (explode("?", $_SERVER['REQUEST_URI'], 2)[0]  == '/person.php' && (!isset($_GET['rcsId']) || $_GET['rcsId'] != strtolower(phpCAS::getUser()))) {
            return "class='active'";
        } else {
            return "";
        }
    }
?>

<div class="sidebar" data-color="red">
    <div class="sidebar-wrapper">
        <div class="logo">
            <a href="/person.php?rcsId=<?=strtolower(phpCAS::getUser())?>" class="simple-text">
                SG Management Tool
            </a>
        </div>

        <ul class="nav">
            <li <?=addActiveClassForProfileAppropriately()?>>
                <a href="/person.php?rcsId=<?=strtolower(phpCAS::getUser())?>">
                    <i class="pe-7s-home"></i>
                    <p>My Profile</p>
                </a>
            </li>
            <?php if (IS_AUTHORIZED) { ?>
                <li <?=addActiveClassAppropriatelyMultiple(["/sessions.php", "/body.php", "/session.php", "/subbody.php"])?>>
                    <a href="/sessions.php">
                        <i class="pe-7s-network"></i>
                        <p>Bodies &amp; Sessions</p>
                    </a>
                </li>
                <li <?=addActiveClassAppropriately("/people.php")?> <?=addActiveClassForPersonPageAppropriately()?>>
                    <a href="/people.php">
                        <i class="pe-7s-user"></i>
                        <p>People &amp; Memberships</p>
                    </a>
                </li>
                <li <?=addActiveClassAppropriatelyMultiple(["/positions.php", "/position.php"])?>>
                    <a href="/positions.php">
                        <i class="pe-7s-portfolio"></i>
                        <p>Positions</p>
                    </a>
                </li>
                <li <?=addActiveClassAppropriatelyMultiple(["/meetings.php", "/meeting.php"])?>>
                    <a href="/meetings.php">
                        <i class="pe-7s-date"></i>
                        <p>Meetings &amp; Events</p>
                    </a>
                </li>
                <li <?=addActiveClassAppropriatelyMultiple(["/actions.php", "/action.php"])?>>
                    <a href="/actions.php">
                        <i class="pe-7s-hammer"></i>
                        <p>Actions</p>
                    </a>
                </li>
                <li <?=addActiveClassAppropriatelyMultiple(["/projects.php", "/project.php"])?>>
                    <a href="/projects.php">
                        <i class="pe-7s-note2"></i>
                        <p>Projects</p>
                    </a>
                </li>
                <li <?=addActiveClassAppropriatelyMultiple(["/updates.php", "/update.php"])?>>
                    <a href="/updates.php">
                        <i class="pe-7s-news-paper"></i>
                        <p>Updates</p>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>