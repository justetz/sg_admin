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
?>

<div class="sidebar" data-color="red">
    <div class="sidebar-wrapper">
        <div class="logo">
            <a href="http://www.creative-tim.com" class="simple-text">
                SG Management Tool
            </a>
        </div>

        <ul class="nav">
            <li <?=addActiveClassAppropriately("/dashboard.php")?>>
                <a href="/dashboard.php">
                    <i class="pe-7s-graph"></i>
                    <p>Dashboard</p>
                </a>
            </li>
            <li <?=addActiveClassAppropriatelyMultiple(["/sessions.php", "/body.php", "/session.php", "/subbody.php"])?>>
                <a href="/sessions.php">
                    <i class="pe-7s-network"></i>
                    <p>Bodies &amp; Sessions</p>
                </a>
            </li>
            <li <?=addActiveClassAppropriatelyMultiple(["/people.php", "/person.php"])?>>
                <a href="/people.php">
                    <i class="pe-7s-user"></i>
                    <p>People &amp; Memberships</p>
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
        </ul>
    </div>
</div>