<?php
$requiredIndicator = '<sup class="text-danger">*</sup>';
define('REQUIRED_INDICATOR', $requiredIndicator);

date_default_timezone_set('America/New_York');

function toggleGetParam($key, $newValue) {
    $newGet = $_GET;

    if(!isset($newValue)) {
        unset($newGet[$key]);
    } else {
        $newGet[$key] = $newValue;
    }

    return strtok($_SERVER["REQUEST_URI"], '?') . (count($newGet) ? ("?" . http_build_query($newGet)) : '');
}

function buildMessage ($result, $data) {
    if(!isset($result) || !isset($data) || !isset($data['transaction'])) {
        if(isset($_COOKIE['SGMS-Success-Message'])) {
            $message = $_COOKIE['SGMS-Success-Message'];
            return "<script type='text/javascript'>
                $.notify({
                    message: '$message'
                },{
                    type: 'success',
                    timer: 4000,
                    placement: {
                        from: 'top',
                        align: 'center'
                    }
                });
            </script>";
        }

        return '';
    } else if(isset($result['message'])) {
        $message = "$result[message]: ";
        $i = 0;

        foreach($result['errors'] as $error) {
            if($i > 0) {
                $message .= ', ';
            }

            if($i == count($result['errors']) - 1) {
                $message .= 'and ';
            }

            $message .= "$error[message]";
            $i++;
        }

        return "<script type='text/javascript'>
            $.notify({
                message: '$message'
            },{
                type: 'danger',
                timer: 4000,
                placement: {
                    from: 'top',
                    align: 'center'
                }
            });
        </script>";
    } else if(!isset($result['errors'])) {
        $transactionParts = explode('_', $data['transaction'], 2);
        $message = "The $transactionParts[1] was successfully $transactionParts[0]d!";

        return "<script type='text/javascript'>
            $.notify({
                message: '$message'
            },{
                type: 'success',
                timer: 4000,
                placement: {
                    from: 'top',
                    align: 'center'
                }
            });
        </script>";
    }
}

function constructMeetingTitle ($meeting, $session=false) {
    if (!$session) {
        return "Meeting #$meeting[meetingNum]";
    }

    return "$session[name] - Meeting #$meeting[meetingNum]";
}

function generateAddMembershipCard ($transaction, $bodyUniqueId=null, $sessionUniqueId=null, $positionId=null) {
    $positionOptions = '';

    if(!isset($positionId)) {
        $parameters = [];
        if(isset($bodyUniqueId)) $parameters["bodyUniqueId"] = $bodyUniqueId;
        $parameters["sort"] = "-presidingOfficer,name";

        $positions = Positions::read($parameters);

        $presidingOfficerOptions = '';
        $officerOptions = '';
        $votingOptions = '';

        $presidingOfficerCount = 0;
        $officerCount = 0;
        $votingCount = 0;

        foreach ($positions as $p) {
            $option = "<option value='$p[id]'>$p[name]";

            if (!isset($bodyUniqueId)) {
                $option .= ' (' . $p['body']['name'] . ')';
            }

            $option .= "</option>";

            if ($p['presidingOfficer']) {
                $presidingOfficerOptions .= $option;
                $presidingOfficerCount++;
            } else if ($p['officer']) {
                $officerOptions .= $option;
                $officerCount++;
            } else if ($p['voting']) {
                $votingOptions .= $option;
                $votingCount++;
            }
        }

        if ($presidingOfficerCount > 0) {
            $positionOptions .= "<optgroup label='Presiding Officer" . ($presidingOfficerCount > 1 ? 's' : '') . "'>$presidingOfficerOptions</optgroup>";
        }

        if ($officerCount > 0) {
            $positionOptions .= "<optgroup label='Officer" . ($officerCount > 1 ? 's' : '') . "'>$officerOptions</optgroup>";
        }

        if ($votingCount > 0) {
            $positionOptions .= "<optgroup label='Voting Position" . ($votingCount > 1 ? 's' : '') . "'>$votingOptions</optgroup>";
        }
    } else {
        $position = Positions::getEntry($positionId);
    }

    $todayDate = date('Y-m-d');

    $addMembershipCard = "<div class='header'><h4 class='title'>Add Membership</h4></div>";

    $addMembershipCard .= "<div class='content'><form method='post' action='$_SERVER[REQUEST_URI]'>";

    $addMembershipCard .= "<div class='form-group'>";
    $addMembershipCard .= "    <label>RCS ID " . REQUIRED_INDICATOR . "</label>";
    $addMembershipCard .= "    <input type='text' name='personRcsId' class='form-control' placeholder='RCS ID'>";
    $addMembershipCard .= "</div>";

    if (isset($sessionUniqueId)) {
        $addMembershipCard .= "<input type='hidden' name='sessionUniqueId' value='$sessionUniqueId'>";
    }

    $addMembershipCard .= "<div class='form-group'>";
    $addMembershipCard .= "    <label>Position " . REQUIRED_INDICATOR . "</label>";
    $addMembershipCard .= "    <select name='positionId' class='form-control'>";
    $addMembershipCard .= "        <option disabled selected>Select a position...</option>";
    $addMembershipCard .= "        $positionOptions";
    $addMembershipCard .= "    </select>";
    if (!isset($sessionUniqueId)) {
        $addMembershipCard .= "<p class='help-block small'>This will add the membership to the current session of the respective body.</p>";
    }
    $addMembershipCard .= "</div>";

    $addMembershipCard .= "<div class='form-group'>";
    $addMembershipCard .= "    <label>Membership-Specific Title</label>";
    $addMembershipCard .= "    <input type='text' name='name' class='form-control' placeholder='Optional'>";
    $addMembershipCard .= "</div>";

    $addMembershipCard .= "<div class='form-group'>";
    $addMembershipCard .= "    <label>Start Date " . REQUIRED_INDICATOR . "</label>";
    $addMembershipCard .= "    <input type='date' name='startDate' class='form-control' placeholder='YYYY-MM-DD' value='$todayDate'>";
    $addMembershipCard .= "</div>";

    $addMembershipCard .= "<div class='form-group'>";
    $addMembershipCard .= "    <label>End Date</label>";
    $addMembershipCard .= "    <input type='date' name='endDate' class='form-control' placeholder='YYYY-MM-DD'>";
    $addMembershipCard .= "</div>";

    $addMembershipCard .= "<div class='checkbox'>";
    $addMembershipCard .= "    <label>";
    $addMembershipCard .= "        <input type='checkbox' name='yearOnly' data-toggle='checkbox'> Year Only";
    $addMembershipCard .= "    </label>";
    $addMembershipCard .= "</div>";

    $addMembershipCard .= "<input type='hidden' name='transaction' value='$transaction'>";
    $addMembershipCard .= "<button type='submit' class='btn btn-primary btn-sm btn-fill pull-right'>Add Member</button>";
    $addMembershipCard .= "<div class='clearfix'></div>";
    $addMembershipCard .= "</form></div>";

    return "<div class='card'>$addMembershipCard</div>";
}

function buildMembershipOptions ($positions, $memberships, $action=null, $activeId=null, $subbodies=null) {
    $result = '';

    foreach($positions as $p) {
        $options = '';

        foreach ($memberships as $m) {
            if ($m['positionId'] == $p['id'] && $m['position']['voting'] && (!isset($action) || $m['startDate'] <= $action['meeting']['date']) && (!isset($action) || $m['current'] || $m['endDate'] >= $action['meeting']['date'])) {
                $options .= "<option value='membership~$m[id]'";

                if(isset($activeId) && $m['id'] == $activeId) {
                    $options .= " selected";
                }

                $options .= ">" . $m['person']['name'] . "</option>";
            }
        }

        if(strlen($options) > 0) {
            $result .= "<optgroup label='$p[name]'>$options</optgroup>";
        }
    }

    if(isset($subbodies)) {
        $options = '';

        foreach($subbodies as $s) {
            $options .= "<option value='subbody~$s[uniqueId]'";

            if(isset($activeId) && $s['uniqueId'] == $activeId) {
                $options .= " selected";
            }

            $options .= ">$s[name]</option>";
        }

        if(strlen($options) > 0) {
            $result .= "<optgroup label='Sub-Bodies'>$options</optgroup>";
        }
    }

    return $result;
}