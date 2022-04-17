<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://strangeprojects.herokuapp.com');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 60');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: AccountKey,x-requested-with, Content-Type, origin, authorization, accept, client-security-token, host, date, cookie, cookie2");

require_once("DB.php");
//DB(host, port, dbname, user, password)
$db = new DB("insert host", "insert port", "insert db name", "insert user", "insert password");

$arg_split = strpos($_SERVER['REQUEST_URI'], "?");
$req = "";
$args = [];
if ($arg_split !== false) {
    //this means we have arguments in the request url
    parse_str(substr($_SERVER['REQUEST_URI'], $arg_split + 1), $args);
    $req = substr($_SERVER['REQUEST_URI'], 0, $arg_split);
    //print_r($args);
} else {
    //this means we dont have arguments in the request url
    $req = $_SERVER['REQUEST_URI'];
}

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    if ($req == "/get_bodyparts") {
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        
        $bodyparts = $db->query('SELECT * FROM bodyparts');
        $response = "[";
        foreach ($bodyparts as $bodypart) {
            $response .= "{";
            $response .= '"id": ' . $bodypart['id'] . ',';
            $response .= '"name": "' . $bodypart['name'] . '"';
            $response .= "},";
        }
        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";
        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_get_equipment") {
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        $equipment = $db->query('SELECT * FROM equipment WHERE user_id IS NULL or user_id = :user_id', array(':user_id' => $user_id));
        $response = "[";
        foreach ($equipment as $equip) {
            $response .= "{";
            $response .= '"id": ' . $equip['id'] . ',';
            $response .= '"name": "' . $equip['name'] . '"';
            $response .= "},";
        }
        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";
        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_get_tracking_units") {
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        $tracking_units = $db->query('SELECT * FROM tracking_units WHERE user_id IS NULL or user_id = :user_id', array(':user_id' => $user_id));
        $response = "[";
        foreach ($tracking_units as $unit) {
            $response .= "{";
            $response .= '"id": ' . $unit['id'] . ',';
            $response .= '"name": "' . $unit['name'] . '"';
            $response .= "},";
        }
        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";
        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_tracked_workouts") {
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $tracked_workouts = $db->query('SELECT * FROM workout_tracker WHERE user_id = :user_id', array(':user_id' => $user_id));
        if (count($tracked_workouts) < 1) {
            //no tracked workouts found - handled on client side
            return;
        }
        $response = "[";
        foreach ($tracked_workouts as $tracked_workout) {
            $tracker_sets = $db->query('SELECT * FROM workout_tracker_sets
            WHERE tracker_id = :tracker_id', array(':tracker_id' => $tracked_workout['id']));


            //----------- get workout ---------------------------
            $workout = $db->query('SELECT * FROM workouts WHERE id = :workout_id', array(':workout_id' => $tracked_workout['workout_id']))[0];
            $workout_rating = $db->query('SELECT id, rating FROM workout_ratings WHERE workout_id = :workout_id AND user_id = :user_id', array(':workout_id' => $tracked_workout['workout_id'], ':user_id' => $user_id))[0];

            $exercises = $db->query('SELECT workout_exercises.exercise_id as id, exercises.name as ex_name, exercises.user_id as usr_id, exercise_information.note as note, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name, tracking_units.id as track_unit_id, tracking_units.name as track_unit_name, exercise_ratings.rating as ex_rating, workout_exercises.sets as sets FROM workout_exercises
                INNER JOIN exercises ON workout_exercises.exercise_id = exercises.id
                INNER JOIN exercise_information ON workout_exercises.exercise_id = exercise_information.exercise_id
                INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
                INNER JOIN equipment ON exercises.equipment_id = equipment.id
                INNER JOIN tracking_units ON exercises.tracking_unit_id = tracking_units.id
                INNER JOIN exercise_ratings ON workout_exercises.exercise_id = exercise_ratings.exercise_id
                WHERE workout_exercises.workout_id = :workout_id
                AND exercise_ratings.user_id = :user_id', array(':workout_id' => $tracked_workout['workout_id'], ':user_id' => $user_id));
            $ordered_exercises = $exercises;
            if (strlen($workout['exercise_order']) > 1) {
                $order = explode(",", $workout['exercise_order']);
                $ordered_exercises = array();
                for ($i = 0; $i < count($order); $i++) {
                    foreach ($exercises as $e) {
                        if ($e['id'] == $order[$i]) {
                            array_push($ordered_exercises, $e);
                            break;
                        }
                    }
                }
            }

            $workout_exercises = "[";
            foreach ($ordered_exercises as $exercise) {

                $bodypart = "{";
                $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
                $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
                $bodypart .= "}";

                $equipment = "{";
                $equipment .= '"id": ' . $exercise['eq_id'] . ',';
                $equipment .= '"name": "' . $exercise['eq_name'] . '"';
                $equipment .= "}";

                $tracking_unit = "{";
                $tracking_unit .= '"id": ' . $exercise['track_unit_id'] . ',';
                $tracking_unit .= '"name": "' . $exercise['track_unit_name'] . '"';
                $tracking_unit .= "}";

                $workout_exercises .= "{";

                $workout_exercise = "{";
                $workout_exercise .= '"id": ' . $exercise['id'] . ',';
                $workout_exercise .= '"name": "' . $exercise['ex_name'] . '",';
                $workout_exercise .= '"bodypart": ' . $bodypart . ',';
                $workout_exercise .= '"equipment": ' . $equipment . ',';
                $workout_exercise .= '"tracking_unit": ' . $tracking_unit . ',';
                $workout_exercise .= '"rating": "' . $exercise['ex_rating'] . '",';
                $workout_exercise .= '"note": "' . $exercise['note'] . '",';
                if ($exercise['usr_id']) {
                    $workout_exercise .= '"global": false';
                } else {
                    $workout_exercise .= '"global": true';
                }
                $workout_exercise .= "}";

                $workout_exercises .= '"exercise": ' . $workout_exercise . ',';
                $workout_exercises .= '"sets": ' . $exercise['sets'] . '';

                $workout_exercises .= "},";
            }
            $workout_exercises = substr($workout_exercises, 0, strlen($workout_exercises) - 1);
            $workout_exercises .= "]";

            $workout_response = "{";
            $workout_response .= '"id": ' . $workout['id'] . ',';
            $workout_response .= '"name": "' . $workout['name'] . '",';
            $workout_response .= '"exercise_order": "' . $workout['exercise_order'] . '",';
            $workout_response .= '"rating": "' . $workout_rating['rating'] . '",';
            if ($workout['user_id']) {
                $workout_response .= '"global": false,';
            } else {
                $workout_response .= '"global": true,';
            }
            if ($workout['public']) {
                $workout_response .= '"public": true,';
            } else {
                $workout_response .= '"public": false,';
            }
            $workout_response .= '"exercises": ' . $workout_exercises;
            $workout_response .= "}";
            //----------------- get workout END --------------


            $workout_tracker_sets = "[";
            foreach ($tracker_sets as $tracker_set) {

                $workout_tracker_sets .= "{";

                $workout_tracker_sets .= '"id": ' . $tracker_set['set_id'] . ',';
                $workout_tracker_sets .= '"tracker_id": ' . $tracker_set['tracker_id'] . ',';
                $workout_tracker_sets .= '"workout_id": ' . $tracker_set['workout_id'] . ',';
                $workout_tracker_sets .= '"exercise_id": ' . $tracker_set['exercise_id'] . ',';
                $workout_tracker_sets .= '"reps": ' . $tracker_set['reps'] . ',';
                $workout_tracker_sets .= '"weight": "' . $tracker_set['weight'] . '",';
                $workout_tracker_sets .= '"feeling": ' . $tracker_set['feeling'];

                $workout_tracker_sets .= "},";
            }
            $workout_tracker_sets = substr($workout_tracker_sets, 0, strlen($workout_tracker_sets) - 1);
            $workout_tracker_sets .= "]";


            $response .= "{";
            $response .= '"id": ' . $tracked_workout['id'] . ',';
            $response .= '"workout": ' . $workout_response . ',';
            $response .= '"start": "' . $tracked_workout['start_time'] . '",';
            $response .= '"end": "' . $tracked_workout['end_time'] . '",';

            $response .= '"tracker_sets": ' . $workout_tracker_sets;
            $response .= "},";
        }

        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";
        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_all_workouts") {
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        //special case -> rest day -> no exercises for rest day !
        $workouts = $db->query('SELECT * FROM workouts 
        WHERE (user_id IS NULL OR user_id = -1 OR user_id = :user_id)
        AND id IN (SELECT workout_id FROM workout_exercises WHERE exercise_id != 0) or id = 1', array(':user_id' => $user_id));
        if (count($workouts) < 1) {
            //no workouts found - handled on client side
            return;
        }

        $response = "[";
        foreach ($workouts as $workout) {
            if ($workout['id'] == 1) {
                //rest day
                $response .= "{";
                $response .= '"id": ' . $workout['id'] . ',';
                $response .= '"name": "' . $workout['name'] . '",';
                $response .= '"exercise_order": "' . $workout['exercise_order'] . '",';
                $response .= '"rating": 0,';
                $response .= '"global": false,';
                $response .= '"public": false,';
                $response .= '"exercises": []';
                $response .= "},";
            } else {
                $workout_rating = $db->query('SELECT id, rating FROM workout_ratings WHERE workout_id = :workout_id AND user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id))[0];

                $exercises = $db->query('SELECT workout_exercises.exercise_id as id, exercises.name as ex_name, exercises.user_id as usr_id, exercise_information.note as note, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name, tracking_units.id as track_unit_id, tracking_units.name as track_unit_name, exercise_ratings.rating as ex_rating, workout_exercises.sets as sets FROM workout_exercises
                INNER JOIN exercises ON workout_exercises.exercise_id = exercises.id
                INNER JOIN exercise_information ON workout_exercises.exercise_id = exercise_information.exercise_id
                INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
                INNER JOIN equipment ON exercises.equipment_id = equipment.id
                INNER JOIN tracking_units ON exercises.tracking_unit_id = tracking_units.id
                INNER JOIN exercise_ratings ON workout_exercises.exercise_id = exercise_ratings.exercise_id
                WHERE workout_exercises.workout_id = :workout_id
                AND exercise_ratings.user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id));

                $ordered_exercises = $exercises;
                if (strlen($workout['exercise_order']) > 1) {
                    $order = explode(",", $workout['exercise_order']);
                    $ordered_exercises = array();
                    for ($i = 0; $i < count($order); $i++) {
                        foreach ($exercises as $e) {
                            if ($e['id'] == $order[$i]) {
                                array_push($ordered_exercises, $e);
                                break;
                            }
                        }
                    }
                }
                $workout_exercises = "[";
                foreach ($ordered_exercises as $exercise) {

                    $bodypart = "{";
                    $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
                    $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
                    $bodypart .= "}";

                    $equipment = "{";
                    $equipment .= '"id": ' . $exercise['eq_id'] . ',';
                    $equipment .= '"name": "' . $exercise['eq_name'] . '"';
                    $equipment .= "}";

                    $tracking_unit = "{";
                    $tracking_unit .= '"id": ' . $exercise['track_unit_id'] . ',';
                    $tracking_unit .= '"name": "' . $exercise['track_unit_name'] . '"';
                    $tracking_unit .= "}";

                    $workout_exercises .= "{";

                    $workout_exercise = "{";
                    $workout_exercise .= '"id": ' . $exercise['id'] . ',';
                    $workout_exercise .= '"name": "' . $exercise['ex_name'] . '",';
                    $workout_exercise .= '"bodypart": ' . $bodypart . ',';
                    $workout_exercise .= '"equipment": ' . $equipment . ',';
                    $workout_exercise .= '"tracking_unit": ' . $tracking_unit . ',';
                    $workout_exercise .= '"rating": "' . $exercise['ex_rating'] . '",';
                    $workout_exercise .= '"note": "' . $exercise['note'] . '",';
                    if ($exercise['usr_id']) {
                        $workout_exercise .= '"global": false';
                    } else {
                        $workout_exercise .= '"global": true';
                    }
                    $workout_exercise .= "}";

                    $workout_exercises .= '"exercise": ' . $workout_exercise . ',';
                    $workout_exercises .= '"sets": ' . $exercise['sets'] . '';

                    $workout_exercises .= "},";
                }
                $workout_exercises = substr($workout_exercises, 0, strlen($workout_exercises) - 1);
                $workout_exercises .= "]";

                $response .= "{";
                $response .= '"id": ' . $workout['id'] . ',';
                $response .= '"name": "' . $workout['name'] . '",';
                $response .= '"exercise_order": "' . $workout['exercise_order'] . '",';
                $response .= '"rating": "' . $workout_rating['rating'] . '",';
                if ($workout['user_id']) {
                    $response .= '"global": false,';
                } else {
                    $response .= '"global": true,';
                }
                if ($workout['public']) {
                    $response .= '"public": true,';
                } else {
                    $response .= '"public": false,';
                }
                $response .= '"exercises": ' . $workout_exercises;
                $response .= "},";
            }
        }

        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";
        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_workouts") {
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $workouts = $db->query('SELECT * FROM workouts 
        WHERE (user_id IS NULL OR user_id = :user_id)
        AND id IN (SELECT workout_id FROM workout_exercises WHERE exercise_id != 0)', array(':user_id' => $user_id));
        if (count($workouts) < 1) {
            //no workouts found - handled on client side
            return;
        }

        $response = "[";
        foreach ($workouts as $workout) {
            $workout_rating = $db->query('SELECT id, rating FROM workout_ratings WHERE workout_id = :workout_id AND user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id))[0];

            $exercises = $db->query('SELECT workout_exercises.exercise_id as id, exercises.name as ex_name, exercises.user_id as usr_id, exercise_information.note as note, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name, tracking_units.id as track_unit_id, tracking_units.name as track_unit_name, exercise_ratings.rating as ex_rating, workout_exercises.sets as sets FROM workout_exercises
            INNER JOIN exercises ON workout_exercises.exercise_id = exercises.id
            INNER JOIN exercise_information ON workout_exercises.exercise_id = exercise_information.exercise_id
            INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
            INNER JOIN equipment ON exercises.equipment_id = equipment.id
            INNER JOIN tracking_units ON exercises.tracking_unit_id = tracking_units.id
            INNER JOIN exercise_ratings ON workout_exercises.exercise_id = exercise_ratings.exercise_id
            WHERE workout_exercises.workout_id = :workout_id
            AND exercise_ratings.user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id));

            $ordered_exercises = $exercises;
            if (strlen($workout['exercise_order']) > 1) {
                $order = explode(",", $workout['exercise_order']);
                $ordered_exercises = array();
                for ($i = 0; $i < count($order); $i++) {
                    foreach ($exercises as $e) {
                        if ($e['id'] == $order[$i]) {
                            array_push($ordered_exercises, $e);
                            break;
                        }
                    }
                }
            }
            $workout_exercises = "[";
            foreach ($ordered_exercises as $exercise) {

                $bodypart = "{";
                $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
                $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
                $bodypart .= "}";

                $equipment = "{";
                $equipment .= '"id": ' . $exercise['eq_id'] . ',';
                $equipment .= '"name": "' . $exercise['eq_name'] . '"';
                $equipment .= "}";

                $tracking_unit = "{";
                $tracking_unit .= '"id": ' . $exercise['track_unit_id'] . ',';
                $tracking_unit .= '"name": "' . $exercise['track_unit_name'] . '"';
                $tracking_unit .= "}";

                $workout_exercises .= "{";

                $workout_exercise = "{";
                $workout_exercise .= '"id": ' . $exercise['id'] . ',';
                $workout_exercise .= '"name": "' . $exercise['ex_name'] . '",';
                $workout_exercise .= '"bodypart": ' . $bodypart . ',';
                $workout_exercise .= '"equipment": ' . $equipment . ',';
                $workout_exercise .= '"tracking_unit": ' . $tracking_unit . ',';
                $workout_exercise .= '"rating": "' . $exercise['ex_rating'] . '",';
                $workout_exercise .= '"note": "' . $exercise['note'] . '",';
                if ($exercise['usr_id']) {
                    $workout_exercise .= '"global": false';
                } else {
                    $workout_exercise .= '"global": true';
                }
                $workout_exercise .= "}";

                $workout_exercises .= '"exercise": ' . $workout_exercise . ',';
                $workout_exercises .= '"sets": ' . $exercise['sets'] . '';

                $workout_exercises .= "},";
            }
            $workout_exercises = substr($workout_exercises, 0, strlen($workout_exercises) - 1);
            $workout_exercises .= "]";

            $response .= "{";
            $response .= '"id": ' . $workout['id'] . ',';
            $response .= '"name": "' . $workout['name'] . '",';
            $response .= '"exercise_order": "' . $workout['exercise_order'] . '",';
            $response .= '"rating": "' . $workout_rating['rating'] . '",';
            if ($workout['user_id']) {
                $response .= '"global": false,';
            } else {
                $response .= '"global": true,';
            }
            if ($workout['public']) {
                $response .= '"public": true,';
            } else {
                $response .= '"public": false,';
            }
            $response .= '"exercises": ' . $workout_exercises;
            $response .= "},";
        }

        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";
        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_workout") {
        $workout_id = $args['workout_id'];
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        $workout = $db->query('SELECT * FROM workouts WHERE id = :workout_id', array(':workout_id' => $workout_id))[0];
        if (count($workout) < 1) {
            //workout not found - handled on client side
            return;
        }
        $workout_rating = $db->query('SELECT id, rating FROM workout_ratings WHERE workout_id = :workout_id AND user_id = :user_id', array(':workout_id' => $workout_id, ':user_id' => $user_id))[0];

        $exercises = $db->query('SELECT workout_exercises.exercise_id as id, exercises.name as ex_name, exercises.user_id as usr_id, exercise_information.note as note, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name, tracking_units.id as track_unit_id, tracking_units.name as track_unit_name, exercise_ratings.rating as ex_rating, workout_exercises.sets as sets FROM workout_exercises
                INNER JOIN exercises ON workout_exercises.exercise_id = exercises.id
                INNER JOIN exercise_information ON workout_exercises.exercise_id = exercise_information.exercise_id
                INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
                INNER JOIN equipment ON exercises.equipment_id = equipment.id
                INNER JOIN tracking_units ON exercises.tracking_unit_id = tracking_units.id
                INNER JOIN exercise_ratings ON workout_exercises.exercise_id = exercise_ratings.exercise_id
                WHERE workout_exercises.workout_id = :workout_id
                AND exercise_ratings.user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id));
        $ordered_exercises = $exercises;
        if (strlen($workout['exercise_order']) > 1) {
            $order = explode(",", $workout['exercise_order']);
            $ordered_exercises = array();
            for ($i = 0; $i < count($order); $i++) {
                foreach ($exercises as $e) {
                    if ($e['id'] == $order[$i]) {
                        array_push($ordered_exercises, $e);
                        break;
                    }
                }
            }
        }

        $workout_exercises = "[";
        foreach ($ordered_exercises as $exercise) {

            $bodypart = "{";
            $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
            $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
            $bodypart .= "}";

            $equipment = "{";
            $equipment .= '"id": ' . $exercise['eq_id'] . ',';
            $equipment .= '"name": "' . $exercise['eq_name'] . '"';
            $equipment .= "}";

            $tracking_unit = "{";
            $tracking_unit .= '"id": ' . $exercise['track_unit_id'] . ',';
            $tracking_unit .= '"name": "' . $exercise['track_unit_name'] . '"';
            $tracking_unit .= "}";

            $workout_exercises .= "{";

            $workout_exercise = "{";
            $workout_exercise .= '"id": ' . $exercise['id'] . ',';
            $workout_exercise .= '"name": "' . $exercise['ex_name'] . '",';
            $workout_exercise .= '"bodypart": ' . $bodypart . ',';
            $workout_exercise .= '"equipment": ' . $equipment . ',';
            $workout_exercise .= '"tracking_unit": ' . $tracking_unit . ',';
            $workout_exercise .= '"rating": "' . $exercise['ex_rating'] . '",';
            $workout_exercise .= '"note": "' . $exercise['note'] . '",';
            if ($exercise['usr_id']) {
                $workout_exercise .= '"global": false';
            } else {
                $workout_exercise .= '"global": true';
            }
            $workout_exercise .= "}";

            $workout_exercises .= '"exercise": ' . $workout_exercise . ',';
            $workout_exercises .= '"sets": ' . $exercise['sets'] . '';

            $workout_exercises .= "},";
        }
        $workout_exercises = substr($workout_exercises, 0, strlen($workout_exercises) - 1);
        $workout_exercises .= "]";

        $response = "{";
        $response .= '"id": ' . $workout['id'] . ',';
        $response .= '"name": "' . $workout['name'] . '",';
        $response .= '"exercise_order": "' . $workout['exercise_order'] . '",';
        $response .= '"rating": "' . $workout_rating['rating'] . '",';
        if ($workout['user_id']) {
            $response .= '"global": false,';
        } else {
            $response .= '"global": true,';
        }
        if ($workout['public']) {
            $response .= '"public": true,';
        } else {
            $response .= '"public": false,';
        }

        $response .= '"exercises": ' . $workout_exercises;
        $response .= "}";
        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_exercises") {
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        $exercises = $db->query('SELECT exercises.id, exercises.name as ex_name, exercises.note, exercises.user_id as usr_id, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name, tracking_units.id as track_unit_id, tracking_units.name as track_unit_name, exercise_ratings.rating as ex_rating FROM exercises
                INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
                INNER JOIN equipment ON exercises.equipment_id = equipment.id
                INNER JOIN tracking_units ON exercises.tracking_unit_id = tracking_units.id
                INNER JOIN exercise_ratings ON exercises.id = exercise_ratings.exercise_id
                WHERE (exercises.user_id IS NULL OR exercises.user_id = :user_id) 
                AND exercise_ratings.user_id = :user_id', array(':user_id' => $user_id));
        if (count($exercises) < 1) {
            //No exercises found - handled on client side
            return;
        }
        $response = "[";
        foreach ($exercises as $exercise) {
            $bodypart = "{";
            $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
            $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
            $bodypart .= "}";

            $equipment = "{";
            $equipment .= '"id": ' . $exercise['eq_id'] . ',';
            $equipment .= '"name": "' . $exercise['eq_name'] . '"';
            $equipment .= "}";

            $tracking_unit = "{";
            $tracking_unit .= '"id": ' . $exercise['track_unit_id'] . ',';
            $tracking_unit .= '"name": "' . $exercise['track_unit_name'] . '"';
            $tracking_unit .= "}";

            $response .= "{";
            $response .= '"id": ' . $exercise['id'] . ',';
            $response .= '"name": "' . $exercise['ex_name'] . '",';
            $response .= '"bodypart": ' . $bodypart . ',';
            $response .= '"equipment": ' . $equipment . ',';
            $response .= '"tracking_unit": ' . $tracking_unit . ',';
            $response .= '"rating": "' . $exercise['ex_rating'] . '",';
            $response .= '"note": "' . $exercise['note'] . '",';
            if ($exercise['usr_id']) {
                $response .= '"global": false';
            } else {
                $response .= '"global": true';
            }
            $response .= "},";
        }
        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";
        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_exercise") {
        $exercise_id = $args['exercise_id'];
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        $exercise = $db->query('SELECT exercises.id, exercises.name as ex_name, exercises.user_id as usr_id, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name, tracking_units.id as track_unit_id, tracking_units.name as track_unit_name, exercise_information.note as note, exercise_information.rep_range as rep_range, exercise_information.weight_step_size as weight_step_size, exercise_information.public as public,exercise_information.description as ex_desc, exercise_information.visual_path as vis_path, exercise_ratings.rating as ex_rating FROM exercises
            INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
            INNER JOIN equipment ON exercises.equipment_id = equipment.id
            INNER JOIN tracking_units ON exercises.tracking_unit_id = tracking_units.id
            INNER JOIN exercise_information ON exercises.id = exercise_information.exercise_id
            INNER JOIN exercise_ratings ON exercises.id = exercise_ratings.exercise_id
            WHERE exercises.id = :exercise_id 
            AND exercise_ratings.user_id = :user_id', array(':user_id' => $user_id, ':exercise_id' => $exercise_id));
        if (count($exercise) < 1) {
            //No exercises found - handled on client side
            return;
        }
        $exercise = $exercise[0];
        $bodypart = "{";
        $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
        $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
        $bodypart .= "}";

        $equipment = "{";
        $equipment .= '"id": ' . $exercise['eq_id'] . ',';
        $equipment .= '"name": "' . $exercise['eq_name'] . '"';
        $equipment .= "}";

        $tracking_unit = "{";
        $tracking_unit .= '"id": ' . $exercise['track_unit_id'] . ',';
        $tracking_unit .= '"name": "' . $exercise['track_unit_name'] . '"';
        $tracking_unit .= "}";


        $response = "{";
        $response .= '"id": ' . $exercise['id'] . ',';
        $response .= '"name": "' . $exercise['ex_name'] . '",';
        $response .= '"bodypart": ' . $bodypart . ',';
        $response .= '"equipment": ' . $equipment . ',';
        $response .= '"tracking_unit": ' . $tracking_unit . ',';
        $response .= '"rating": "' . $exercise['ex_rating'] . '",';
        $response .= '"note": "' . $exercise['note'] . '",';

        if ($exercise['usr_id']) {
            $response .= '"global": false,';
        } else {
            $response .= '"global": true,';
        }
        if ($exercise['public']) {
            $response .= '"public": true,';
        } else {
            $response .= '"public": false,';
        }

        $response .= '"rep_range": "' . $exercise['rep_range'] . '",';
        $response .= '"weight_step_size": ' . $exercise['weight_step_size'] . ',';
        $response .= '"description": "' . $exercise['ex_desc'] . '",';
        $response .= '"visual_path": "' . $exercise['vis_path'] . '"';

        $response .= "}";
        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_exercise_history") {
        $exercise_id = $args['exercise_id'];
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        $exercises = $db->query('SELECT Distinct(workout_tracker_sets.set_id), workout_tracker_sets.tracker_id, workout_tracker_sets.workout_id, workout_tracker_sets.exercise_id, workout_tracker_sets.reps, workout_tracker_sets.weight, workout_tracker_sets.feeling, workout_tracker.start_time as tracker_date, workouts.name as workout_name, workoutplans.name as workoutplan_name FROM workout_tracker_sets
                INNER JOIN workout_tracker ON workout_tracker_sets.tracker_id = workout_tracker.id
                INNER JOIN workouts ON workout_tracker_sets.workout_id = workouts.id
                LEFT JOIN workoutplans ON workout_tracker.workoutplan_id = workoutplans.id
                WHERE workout_tracker_sets.exercise_id = :exercise_id
                AND workout_tracker.user_id = :user_id', array(':exercise_id' => $exercise_id, ':user_id' => $user_id));
        if (count($exercises) < 1) {
            //No exercise History found - handled on client side
            return;
        }
        $response = "[";
        foreach ($exercises as $exercise) {

            $tracker_set = "{";
            $tracker_set .= '"id": ' . $exercise['set_id'] . ',';
            $tracker_set .= '"tracker_id": ' . $exercise['tracker_id'] . ',';
            $tracker_set .= '"workout_id": ' . $exercise['workout_id'] . ',';
            $tracker_set .= '"exercise_id": ' . $exercise['exercise_id'] . ',';
            $tracker_set .= '"reps": "' . $exercise['reps'] . '",';
            $tracker_set .= '"weight": "' . $exercise['weight'] . '",';
            $tracker_set .= '"feeling": ' . $exercise['feeling'];
            $tracker_set .= "}";
            $response .= "{";
            $response .= '"tracker_set": ' . $tracker_set . ',';
            $response .= '"tracker_workout_name": "' . $exercise['workout_name'] . '",';
            if ($exercise['workoutplan_name'] == "") {
                $response .= '"tracker_workoutplan_name": null,';
            } else {
                $response .= '"tracker_workoutplan_name": "' . $exercise['workoutplan_name'] . '",';
            }

            $response .= '"tracker_date": "' . $exercise['tracker_date'] . '"';
            $response .= "},";
        }
        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";
        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_exercise_search") {
        $search_term = $args['search_term'];
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        //$user_id = 1;
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        $search = '%' . $search_term . '%';
        $exercises = $db->query("SELECT exercises.id, exercises.name as ex_name, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name FROM exercises
                INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
                INNER JOIN equipment ON exercises.equipment_id = equipment.id
                WHERE (exercises.user_id IS NULL OR exercises.user_id = :user_id) 
                AND lower(exercises.name) LIKE :search_term", array(':user_id' => $user_id, ':search_term' => $search));
        if (count($exercises) < 1) {
            //No exercise found matching the search term - handled on client side
            return;
        }

        $response = "[";
        foreach ($exercises as $exercise) {
            $bodypart = "{";
            $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
            $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
            $bodypart .= "}";

            $equipment = "{";
            $equipment .= '"id": ' . $exercise['eq_id'] . ',';
            $equipment .= '"name": "' . $exercise['eq_name'] . '"';
            $equipment .= "}";


            $response .= "{";
            $response .= '"id": ' . $exercise['id'] . ',';
            $response .= '"name": "' . $exercise['ex_name'] . '",';
            $response .= '"bodypart": ' . $bodypart . ',';
            $response .= '"equipment": ' . $equipment;

            $response .= "},";
        }
        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";

        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_workout_search") {
        $search_term = $args['search_term'];
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        $search = '%' . $search_term . '%';
        $workouts = $db->query("SELECT * FROM workouts 
                WHERE lower(name) LIKE :search_term
                AND (user_id IS NULL OR user_id = :user_id)
                AND id IN (SELECT workout_id FROM workout_exercises WHERE exercise_id != 0)", array(':user_id' => $user_id, ':search_term' => $search));
        if (count($workouts) < 1) {
            //No workout found matching the search term - handled on client side
            return;
        }

        $response = "[";
        foreach ($workouts as $workout) {

            $exercises = $db->query('SELECT workout_exercises.exercise_id as id, exercises.name as ex_name, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name, workout_exercises.sets as sets FROM workout_exercises
                    INNER JOIN exercises ON workout_exercises.exercise_id = exercises.id
                    INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
                    INNER JOIN equipment ON exercises.equipment_id = equipment.id
                    WHERE workout_exercises.workout_id = :workout_id', array(':workout_id' => $workout['id']));

            $ordered_exercises = $exercises;
            if (strlen($workout['exercise_order']) > 1) {
                $order = explode(",", $workout['exercise_order']);
                $ordered_exercises = array();
                for ($i = 0; $i < count($order); $i++) {
                    foreach ($exercises as $e) {
                        if ($e['id'] == $order[$i]) {
                            array_push($ordered_exercises, $e);
                            break;
                        }
                    }
                }
            }
            $workout_exercises = "[";
            foreach ($ordered_exercises as $exercise) {

                $bodypart = "{";
                $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
                $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
                $bodypart .= "}";

                $equipment = "{";
                $equipment .= '"id": ' . $exercise['eq_id'] . ',';
                $equipment .= '"name": "' . $exercise['eq_name'] . '"';
                $equipment .= "}";


                $workout_exercises .= "{";

                $workout_exercise = "{";
                $workout_exercise .= '"id": ' . $exercise['id'] . ',';
                $workout_exercise .= '"name": "' . $exercise['ex_name'] . '",';
                $workout_exercise .= '"bodypart": ' . $bodypart . ',';
                $workout_exercise .= '"equipment": ' . $equipment;
                $workout_exercise .= "}";

                $workout_exercises .= '"exercise": ' . $workout_exercise . ',';
                $workout_exercises .= '"sets": ' . $exercise['sets'] . '';

                $workout_exercises .= "},";
            }
            $workout_exercises = substr($workout_exercises, 0, strlen($workout_exercises) - 1);
            $workout_exercises .= "]";

            $response .= "{";
            $response .= '"id": ' . $workout['id'] . ',';
            $response .= '"name": "' . $workout['name'] . '",';
            if ($workout['user_id']) {
                $response .= '"global": false,';
            } else {
                $response .= '"global": true,';
            }
            if ($workout['public']) {
                $response .= '"public": true,';
            } else {
                $response .= '"public": false,';
            }
            $response .= '"exercises": ' . $workout_exercises;
            $response .= "},";
        }

        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";

        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_user_search") {
        $search_term = $args['search_term'];
        $search = '%' . $search_term . '%';
        $users = $db->query('SELECT id, username FROM users WHERE lower(username) LIKE :search_term', array(':search_term' => $search));
        if (count($users) < 1) {
            //No user found matching the search term - handled on client side
            return;
        }
        $response = "[";
        foreach ($users as $user) {
            $response .= "{";
            $response .= '"id": ' . $user['id'] . ',';
            $response .= '"username": "' . $user['username'] . '"';
            $response .= "},";
        }
        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";

        http_response_code(200);
        echo $response;
    } else if ($req == "/check_username_avail") {
        $username = $args['username'];
        $response = "{";

        if (!$db->query('SELECT username FROM users WHERE username=:username', array(':username' => $username))) {

            $response .= '"available": "' . true . '"';
        } else {
            $response .= '"available": "' . null . '"';
        }
        $response .= "}";
        http_response_code(200);
        echo $response;
    } else if ($req == "/check_email_avail") {
        $email = $args['email'];
        $response = "{";

        if (!$db->query('SELECT email FROM users WHERE email=:email', array(':email' => $email))) {

            $response .= '"available": "' . true . '"';
        } else {
            $response .= '"available": "' . null . '"';
        }
        $response .= "}";
        http_response_code(200);
        echo $response;
    } else if ($req == "/check_password") {
        $password = $args['password'];
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $response = "{";
        $hash = $db->query('SELECT password FROM users WHERE id=:user_id', array(':user_id' => $user_id))[0]['password'];
        if (password_verify($password, $hash)) {
            $response .= '"correct": "' . true . '"';
        } else {
            $response .= '"correct": "' . null . '"';
        }
        $response .= "}";
        http_response_code(200);
        echo $response;
    } else if ($req == "/is_logged_in") {
        $user_verification = $args['user_verification'];
        $token = $args['token'];
        $response = "{";

        if ($db->query('SELECT user_id FROM login_tokens WHERE token=:token and user_verification = :user_verification', array(':token' => hash_hmac('sha512', $token, 'insert secret key'), ':user_verification' => $user_verification))) {

            $response .= '"loggedIn": true';
        } else {
            $response .= '"loggedIn": false';
        }
        $response .= "}";
        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_build_workoutplan") {
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $workoutplan_id =  $db->query('SELECT MAX(id) FROM workoutplans WHERE user_id = :user_id', array(':user_id' => $user_id))[0][0];

        $workout = $db->query('SELECT * FROM workouts WHERE id = 1')[0];

        $workout_response = "{";
        $workout_response .= '"id": ' . $workout['id'] . ',';
        $workout_response .= '"name": "' . $workout['name'] . '",';
        $workout_response .= '"exercise_order": "' . $workout['exercise_order'] . '",';
        $workout_response .= '"rating": 0,';
        $workout_response .= '"global": false,';
        if ($workout['public']) {
            $workout_response .= '"public": true,';
        } else {
            $workout_response .= '"public": false,';
        }
        $workout_response .= '"exercises": []';
        $workout_response .= "}";

        $workoutplan = $db->query('SELECT * FROM workoutplans WHERE id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id))[0];

        $workoutplan_weeks = $db->query('SELECT * FROM workoutplan_weeks WHERE workoutplan_id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id));

        $workoutplan_weeks_response = "[";
        foreach ($workoutplan_weeks as $workoutplan_week) {
            $workoutplan_days = $db->query('SELECT * FROM workoutplan_days WHERE workoutplan_week_id = :workoutplan_week_id', array(':workoutplan_week_id' => $workoutplan_week['id']));

            $workoutplan_days_response = "[";
            foreach ($workoutplan_days as $workoutplan_day) {
                $workoutplan_days_response .= "{";
                $workoutplan_days_response .= '"id": ' . $workoutplan_day['id'] . ',';
                $workoutplan_days_response .= '"day": ' . $workoutplan_day['day'] . ',';
                $workoutplan_days_response .= '"note": "' . $workoutplan_day['note'] . '",';
                $workoutplan_days_response .= '"workout": ' . $workout_response;
                $workoutplan_days_response .= "},";
            }
            $workoutplan_days_response = substr($workoutplan_days_response, 0, strlen($workoutplan_days_response) - 1);
            $workoutplan_days_response .= "]";

            $workoutplan_weeks_response .= "{";
            $workoutplan_weeks_response .= '"id": ' . $workoutplan_week['id'] . ',';
            if ($workoutplan_week['deload']) {
                $workoutplan_weeks_response .= '"deload": true,';
            } else {
                $workoutplan_weeks_response .= '"deload": false,';
            }

            $workoutplan_weeks_response .= '"days": ' . $workoutplan_days_response;
            $workoutplan_weeks_response .= "},";
        }
        $workoutplan_weeks_response = substr($workoutplan_weeks_response, 0, strlen($workoutplan_weeks_response) - 1);
        $workoutplan_weeks_response .= "]";

        $response = "{";
        $response .= '"id": ' . $workoutplan['id'] . ',';
        $response .= '"name": "' . $workoutplan['name'] . '",';
        $response .= '"duration": ' . $workoutplan['duration'] . ',';
        $response .= '"target": ' . $workoutplan['target'] . ',';
        $response .= '"note": "' . $workoutplan['note'] . '",';
        $response .= '"weeks": ' . $workoutplan_weeks_response;
        $response .= "}";

        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_edit_workoutplan") {
        $workoutplan_id = $args['workoutplan_id'];
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $workoutplan = $db->query('SELECT * FROM workoutplans WHERE id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id))[0];

        $workoutplan_weeks = $db->query('SELECT * FROM workoutplan_weeks WHERE workoutplan_id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id));

        $workoutplan_weeks_response = "[";
        foreach ($workoutplan_weeks as $workoutplan_week) {
            $workoutplan_days = $db->query('SELECT * FROM workoutplan_days WHERE workoutplan_week_id = :workoutplan_week_id', array(':workoutplan_week_id' => $workoutplan_week['id']));

            $workoutplan_days_response = "[";
            foreach ($workoutplan_days as $workoutplan_day) {

                $workout = $db->query('SELECT * FROM workouts WHERE id = :workout_id', array(':workout_id' => $workoutplan_day['workout_id']))[0];

                $workout_response = "{";
                $workout_response .= '"id": ' . $workout['id'] . ',';
                $workout_response .= '"name": "' . $workout['name'] . '",';
                $workout_response .= '"exercise_order": "' . $workout['exercise_order'] . '",';

                if ($workoutplan_day['workout_id'] == 1) {
                    //special case Rest Day
                    $workout_response .= '"rating": 0,';
                    $workout_response .= '"global": false,';
                    $workout_response .= '"exercises": []';
                } else {
                    $workout_rating = $db->query('SELECT id, rating FROM workout_ratings WHERE workout_id = :workout_id AND user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id))[0];

                    $exercises = $db->query('SELECT workout_exercises.exercise_id as id, exercises.name as ex_name, exercises.user_id as usr_id, exercise_information.note as note, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name, tracking_units.id as track_unit_id, tracking_units.name as track_unit_name, exercise_ratings.rating as ex_rating, workout_exercises.sets as sets FROM workout_exercises
                            INNER JOIN exercises ON workout_exercises.exercise_id = exercises.id
                            INNER JOIN exercise_information ON workout_exercises.exercise_id = exercise_information.exercise_id
                            INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
                            INNER JOIN equipment ON exercises.equipment_id = equipment.id
                            INNER JOIN tracking_units ON exercises.tracking_unit_id = tracking_units.id
                            INNER JOIN exercise_ratings ON workout_exercises.exercise_id = exercise_ratings.exercise_id
                            WHERE workout_exercises.workout_id = :workout_id
                            AND exercise_ratings.user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id));
                    $ordered_exercises = $exercises;
                    if (strlen($workout['exercise_order']) > 1) {
                        $order = explode(",", $workout['exercise_order']);
                        $ordered_exercises = array();
                        for ($i = 0; $i < count($order); $i++) {
                            foreach ($exercises as $e) {
                                if ($e['id'] == $order[$i]) {
                                    array_push($ordered_exercises, $e);
                                    break;
                                }
                            }
                        }
                    }

                    $workout_exercises = "[";
                    foreach ($ordered_exercises as $exercise) {

                        $bodypart = "{";
                        $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
                        $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
                        $bodypart .= "}";

                        $equipment = "{";
                        $equipment .= '"id": ' . $exercise['eq_id'] . ',';
                        $equipment .= '"name": "' . $exercise['eq_name'] . '"';
                        $equipment .= "}";

                        $tracking_unit = "{";
                        $tracking_unit .= '"id": ' . $exercise['track_unit_id'] . ',';
                        $tracking_unit .= '"name": "' . $exercise['track_unit_name'] . '"';
                        $tracking_unit .= "}";

                        $workout_exercises .= "{";

                        $workout_exercise = "{";
                        $workout_exercise .= '"id": ' . $exercise['id'] . ',';
                        $workout_exercise .= '"name": "' . $exercise['ex_name'] . '",';
                        $workout_exercise .= '"bodypart": ' . $bodypart . ',';
                        $workout_exercise .= '"equipment": ' . $equipment . ',';
                        $workout_exercise .= '"tracking_unit": ' . $tracking_unit . ',';
                        $workout_exercise .= '"rating": "' . $exercise['ex_rating'] . '",';
                        $workout_exercise .= '"note": "' . $exercise['note'] . '",';
                        if ($exercise['usr_id']) {
                            $workout_exercise .= '"global": false';
                        } else {
                            $workout_exercise .= '"global": true';
                        }
                        $workout_exercise .= "}";

                        $workout_exercises .= '"exercise": ' . $workout_exercise . ',';
                        $workout_exercises .= '"sets": ' . $exercise['sets'] . '';

                        $workout_exercises .= "},";
                    }
                    $workout_exercises = substr($workout_exercises, 0, strlen($workout_exercises) - 1);
                    $workout_exercises .= "]";


                    $workout_response .= '"rating": "' . $workout_rating['rating'] . '",';
                    if ($workout['user_id']) {
                        $workout_response .= '"global": false,';
                    } else {
                        $workout_response .= '"global": true,';
                    }
                    if ($workout['public']) {
                        $workout_response .= '"public": true,';
                    } else {
                        $workout_response .= '"public": false,';
                    }
                    $workout_response .= '"exercises": ' . $workout_exercises;
                }

                $workout_response .= "}";

                $workoutplan_days_response .= "{";
                $workoutplan_days_response .= '"id": ' . $workoutplan_day['id'] . ',';
                $workoutplan_days_response .= '"day": ' . $workoutplan_day['day'] . ',';
                $workoutplan_days_response .= '"note": "' . $workoutplan_day['note'] . '",';
                $workoutplan_days_response .= '"workout": ' . $workout_response;
                $workoutplan_days_response .= "},";
            }
            $workoutplan_days_response = substr($workoutplan_days_response, 0, strlen($workoutplan_days_response) - 1);
            $workoutplan_days_response .= "]";

            $workoutplan_weeks_response .= "{";
            $workoutplan_weeks_response .= '"id": ' . $workoutplan_week['id'] . ',';
            if ($workoutplan_week['deload']) {
                $workoutplan_weeks_response .= '"deload": true,';
            } else {
                $workoutplan_weeks_response .= '"deload": false,';
            }
            $workoutplan_weeks_response .= '"days": ' . $workoutplan_days_response;
            $workoutplan_weeks_response .= "},";
        }
        $workoutplan_weeks_response = substr($workoutplan_weeks_response, 0, strlen($workoutplan_weeks_response) - 1);
        $workoutplan_weeks_response .= "]";

        $response = "{";
        $response .= '"id": ' . $workoutplan['id'] . ',';
        $response .= '"name": "' . $workoutplan['name'] . '",';
        $response .= '"duration": ' . $workoutplan['duration'] . ',';
        $response .= '"target": ' . $workoutplan['target'] . ',';
        $response .= '"note": "' . $workoutplan['note'] . '",';
        $response .= '"weeks": ' . $workoutplan_weeks_response;
        $response .= "}";

        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_workoutplans") {
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $workoutplans =  $db->query('SELECT * FROM workoutplans WHERE user_id = :user_id', array(':user_id' => $user_id));
        if (count($workoutplans) < 1) {
            //No workoutplans found - handled on client side
            return;
        }
        $response = "[";
        foreach ($workoutplans as $workoutplan) {
            $workoutplan_weeks = $db->query('SELECT * FROM workoutplan_weeks WHERE workoutplan_id = :workoutplan_id', array(':workoutplan_id' => $workoutplan['id']));

            $workoutplan_weeks_response = "[";
            foreach ($workoutplan_weeks as $workoutplan_week) {
                $workoutplan_days = $db->query('SELECT * FROM workoutplan_days WHERE workoutplan_week_id = :workoutplan_week_id', array(':workoutplan_week_id' => $workoutplan_week['id']));

                $workoutplan_days_response = "[";
                foreach ($workoutplan_days as $workoutplan_day) {
                    //get workout of day
                    $workout = $db->query('SELECT * FROM workouts WHERE id = :workout_id', array(':workout_id' => $workoutplan_day['workout_id']))[0];
                    $workout_rating = $db->query('SELECT id, rating FROM workout_ratings WHERE workout_id = :workout_id AND user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id));
                    //get exercises of workout
                    $exercises = $db->query('SELECT workout_exercises.exercise_id as id, exercises.name as ex_name, exercises.user_id as usr_id, exercise_information.note as note, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name, tracking_units.id as track_unit_id, tracking_units.name as track_unit_name, exercise_ratings.rating as ex_rating, workout_exercises.sets as sets FROM workout_exercises
                            INNER JOIN exercises ON workout_exercises.exercise_id = exercises.id
                            INNER JOIN exercise_information ON workout_exercises.exercise_id = exercise_information.exercise_id
                            INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
                            INNER JOIN equipment ON exercises.equipment_id = equipment.id
                            INNER JOIN tracking_units ON exercises.tracking_unit_id = tracking_units.id
                            INNER JOIN exercise_ratings ON workout_exercises.exercise_id = exercise_ratings.exercise_id
                            WHERE workout_exercises.workout_id = :workout_id
                            AND exercise_ratings.user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id));
                    $ordered_exercises = $exercises;
                    if (strlen($workout['exercise_order']) > 1) {
                        $order = explode(",", $workout['exercise_order']);
                        $ordered_exercises = array();
                        for ($i = 0; $i < count($order); $i++) {
                            foreach ($exercises as $e) {
                                if ($e['id'] == $order[$i]) {
                                    array_push($ordered_exercises, $e);
                                    break;
                                }
                            }
                        }
                    }
                    //build workout exercises
                    $workout_exercises = "[";
                    foreach ($ordered_exercises as $exercise) {

                        $bodypart = "{";
                        $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
                        $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
                        $bodypart .= "}";

                        $equipment = "{";
                        $equipment .= '"id": ' . $exercise['eq_id'] . ',';
                        $equipment .= '"name": "' . $exercise['eq_name'] . '"';
                        $equipment .= "}";

                        $tracking_unit = "{";
                        $tracking_unit .= '"id": ' . $exercise['track_unit_id'] . ',';
                        $tracking_unit .= '"name": "' . $exercise['track_unit_name'] . '"';
                        $tracking_unit .= "}";

                        $workout_exercises .= "{";

                        $workout_exercise = "{";
                        $workout_exercise .= '"id": ' . $exercise['id'] . ',';
                        $workout_exercise .= '"name": "' . $exercise['ex_name'] . '",';
                        $workout_exercise .= '"bodypart": ' . $bodypart . ',';
                        $workout_exercise .= '"equipment": ' . $equipment . ',';
                        $workout_exercise .= '"tracking_unit": ' . $tracking_unit . ',';
                        $workout_exercise .= '"rating": "' . $exercise['ex_rating'] . '",';
                        $workout_exercise .= '"note": "' . $exercise['note'] . '",';
                        if ($exercise['usr_id']) {
                            $workout_exercise .= '"global": false';
                        } else {
                            $workout_exercise .= '"global": true';
                        }
                        $workout_exercise .= "}";

                        $workout_exercises .= '"exercise": ' . $workout_exercise . ',';
                        $workout_exercises .= '"sets": ' . $exercise['sets'] . '';

                        $workout_exercises .= "},";
                    }
                    //only delete comma if there are exercises -> for special case rest day
                    if (count($ordered_exercises) > 0) {
                        $workout_exercises = substr($workout_exercises, 0, strlen($workout_exercises) - 1);
                    }

                    $workout_exercises .= "]";

                    $workout_response = "{";
                    $workout_response .= '"id": ' . $workout['id'] . ',';
                    $workout_response .= '"name": "' . $workout['name'] . '",';
                    $workout_response .= '"exercise_order": "' . $workout['exercise_order'] . '",';
                    if ($workout_rating) {
                        $workout_response .= '"rating": ' . $workout_rating[0]['rating'] . ',';
                    } else {
                        $workout_response .= '"rating": 0,';
                    }
                    if ($workout['user_id']) {
                        $workout_response .= '"global": false,';
                    } else {
                        $workout_response .= '"global": true,';
                    }
                    if ($workout['public']) {
                        $workout_response .= '"public": true,';
                    } else {
                        $workout_response .= '"public": false,';
                    }

                    $workout_response .= '"exercises": ' . $workout_exercises;
                    $workout_response .= "}";

                    $workoutplan_days_response .= "{";
                    $workoutplan_days_response .= '"id": ' . $workoutplan_day['id'] . ',';
                    $workoutplan_days_response .= '"day": ' . $workoutplan_day['day'] . ',';
                    $workoutplan_days_response .= '"note": "' . $workoutplan_day['note'] . '",';
                    $workoutplan_days_response .= '"workout": ' . $workout_response;
                    $workoutplan_days_response .= "},";
                }
                $workoutplan_days_response = substr($workoutplan_days_response, 0, strlen($workoutplan_days_response) - 1);
                $workoutplan_days_response .= "]";

                $workoutplan_weeks_response .= "{";
                $workoutplan_weeks_response .= '"id": ' . $workoutplan_week['id'] . ',';
                if ($workoutplan_week['deload']) {
                    $workoutplan_weeks_response .= '"deload": true,';
                } else {
                    $workoutplan_weeks_response .= '"deload": false,';
                }
                $workoutplan_weeks_response .= '"days": ' . $workoutplan_days_response;
                $workoutplan_weeks_response .= "},";
            }
            $workoutplan_weeks_response = substr($workoutplan_weeks_response, 0, strlen($workoutplan_weeks_response) - 1);
            $workoutplan_weeks_response .= "]";


            $response .= "{";
            $response .= '"id": ' . $workoutplan['id'] . ',';
            $response .= '"name": "' . $workoutplan['name'] . '",';
            $response .= '"duration": ' . $workoutplan['duration'] . ',';
            $response .= '"target": ' . $workoutplan['target'] . ',';
            $response .= '"note": "' . $workoutplan['note'] . '",';
            $response .= '"weeks": ' . $workoutplan_weeks_response;
            $response .= "},";
        }
        $response = substr($response, 0, strlen($response) - 1);
        $response .= "]";

        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_workoutplan") {
        $workoutplan_id = $args['workoutplan_id'];
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $workoutplan =  $db->query('SELECT * FROM workoutplans WHERE id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id))[0];
        if (count($workoutplan) < 1) {
            //workoutplan not found - handled on client side
            return;
        }
        $workoutplan_weeks = $db->query('SELECT * FROM workoutplan_weeks WHERE workoutplan_id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id));

        $workoutplan_weeks_response = "[";
        foreach ($workoutplan_weeks as $workoutplan_week) {
            $workoutplan_days = $db->query('SELECT * FROM workoutplan_days WHERE workoutplan_week_id = :workoutplan_week_id', array(':workoutplan_week_id' => $workoutplan_week['id']));

            $workoutplan_days_response = "[";
            foreach ($workoutplan_days as $workoutplan_day) {
                //get workout of day
                $workout = $db->query('SELECT * FROM workouts WHERE id = :workout_id', array(':workout_id' => $workoutplan_day['workout_id']))[0];
                $workout_rating = $db->query('SELECT id, rating FROM workout_ratings WHERE workout_id = :workout_id AND user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id));
                //get exercises of workout
                $exercises = $db->query('SELECT workout_exercises.exercise_id as id, exercises.name as ex_name, exercises.user_id as usr_id, exercise_information.note as note, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name, tracking_units.id as track_unit_id, tracking_units.name as track_unit_name, exercise_ratings.rating as ex_rating, workout_exercises.sets as sets FROM workout_exercises
                        INNER JOIN exercises ON workout_exercises.exercise_id = exercises.id
                        INNER JOIN exercise_information ON workout_exercises.exercise_id = exercise_information.exercise_id
                        INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
                        INNER JOIN equipment ON exercises.equipment_id = equipment.id
                        INNER JOIN tracking_units ON exercises.tracking_unit_id = tracking_units.id
                        INNER JOIN exercise_ratings ON workout_exercises.exercise_id = exercise_ratings.exercise_id
                        WHERE workout_exercises.workout_id = :workout_id
                        AND exercise_ratings.user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id));
                $ordered_exercises = $exercises;
                if (strlen($workout['exercise_order']) > 1) {
                    $order = explode(",", $workout['exercise_order']);
                    $ordered_exercises = array();
                    for ($i = 0; $i < count($order); $i++) {
                        foreach ($exercises as $e) {
                            if ($e['id'] == $order[$i]) {
                                array_push($ordered_exercises, $e);
                                break;
                            }
                        }
                    }
                }
                //build workout exercises
                $workout_exercises = "[";
                foreach ($ordered_exercises as $exercise) {

                    $bodypart = "{";
                    $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
                    $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
                    $bodypart .= "}";

                    $equipment = "{";
                    $equipment .= '"id": ' . $exercise['eq_id'] . ',';
                    $equipment .= '"name": "' . $exercise['eq_name'] . '"';
                    $equipment .= "}";

                    $tracking_unit = "{";
                    $tracking_unit .= '"id": ' . $exercise['track_unit_id'] . ',';
                    $tracking_unit .= '"name": "' . $exercise['track_unit_name'] . '"';
                    $tracking_unit .= "}";

                    $workout_exercises .= "{";

                    $workout_exercise = "{";
                    $workout_exercise .= '"id": ' . $exercise['id'] . ',';
                    $workout_exercise .= '"name": "' . $exercise['ex_name'] . '",';
                    $workout_exercise .= '"bodypart": ' . $bodypart . ',';
                    $workout_exercise .= '"equipment": ' . $equipment . ',';
                    $workout_exercise .= '"tracking_unit": ' . $tracking_unit . ',';
                    $workout_exercise .= '"rating": "' . $exercise['ex_rating'] . '",';
                    $workout_exercise .= '"note": "' . $exercise['note'] . '",';
                    if ($exercise['usr_id']) {
                        $workout_exercise .= '"global": false';
                    } else {
                        $workout_exercise .= '"global": true';
                    }
                    $workout_exercise .= "}";

                    $workout_exercises .= '"exercise": ' . $workout_exercise . ',';
                    $workout_exercises .= '"sets": ' . $exercise['sets'] . '';

                    $workout_exercises .= "},";
                }
                //only delete comma if there are exercises -> for special case rest day
                if (count($ordered_exercises) > 0) {
                    $workout_exercises = substr($workout_exercises, 0, strlen($workout_exercises) - 1);
                }

                $workout_exercises .= "]";

                $workout_response = "{";
                $workout_response .= '"id": ' . $workout['id'] . ',';
                $workout_response .= '"name": "' . $workout['name'] . '",';
                $workout_response .= '"exercise_order": "' . $workout['exercise_order'] . '",';
                if ($workout_rating) {
                    $workout_response .= '"rating": ' . $workout_rating[0]['rating'] . ',';
                } else {
                    $workout_response .= '"rating": 0,';
                }
                if ($workout['user_id']) {
                    $workout_response .= '"global": false,';
                } else {
                    $workout_response .= '"global": true,';
                }
                if ($workout['public']) {
                    $workout_response .= '"public": true,';
                } else {
                    $workout_response .= '"public": false,';
                }
                $workout_response .= '"exercises": ' . $workout_exercises;
                $workout_response .= "}";

                $workoutplan_days_response .= "{";
                $workoutplan_days_response .= '"id": ' . $workoutplan_day['id'] . ',';
                $workoutplan_days_response .= '"day": ' . $workoutplan_day['day'] . ',';
                $workoutplan_days_response .= '"note": "' . $workoutplan_day['note'] . '",';
                $workoutplan_days_response .= '"workout": ' . $workout_response;
                $workoutplan_days_response .= "},";
            }
            $workoutplan_days_response = substr($workoutplan_days_response, 0, strlen($workoutplan_days_response) - 1);
            $workoutplan_days_response .= "]";

            $workoutplan_weeks_response .= "{";
            $workoutplan_weeks_response .= '"id": ' . $workoutplan_week['id'] . ',';
            if ($workoutplan_week['deload']) {
                $workoutplan_weeks_response .= '"deload": true,';
            } else {
                $workoutplan_weeks_response .= '"deload": false,';
            }
            $workoutplan_weeks_response .= '"days": ' . $workoutplan_days_response;
            $workoutplan_weeks_response .= "},";
        }
        $workoutplan_weeks_response = substr($workoutplan_weeks_response, 0, strlen($workoutplan_weeks_response) - 1);
        $workoutplan_weeks_response .= "]";


        $response = "{";
        $response .= '"id": ' . $workoutplan['id'] . ',';
        $response .= '"name": "' . $workoutplan['name'] . '",';
        $response .= '"duration": ' . $workoutplan['duration'] . ',';
        $response .= '"target": ' . $workoutplan['target'] . ',';
        $response .= '"note": "' . $workoutplan['note'] . '",';
        $response .= '"weeks": ' . $workoutplan_weeks_response;
        $response .= "}";



        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_workoutplan_tracker") {

        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $workoutplan_tracker_id =  $db->query('SELECT MAX(id) FROM workoutplan_tracker WHERE user_id = :user_id AND active = true', array(':user_id' => $user_id))[0][0];
        if (!$workoutplan_tracker_id) {
            //no active workoutplan - handled on client side
            return;
        }
        $workoutplan_tracker = $db->query('SELECT * FROM workoutplan_tracker WHERE id = :workoutplan_tracker_id ', array(':workoutplan_tracker_id' => $workoutplan_tracker_id))[0];

        //check if workoutplan is still relevant -> end_date > current date 
        //otherwise set to inactive

        $current_date = date('Y-m-d h:i:s');
        if ($current_date > $workoutplan_tracker['end_time']) {
            $db->query('UPDATE workoutplan_tracker SET active = false WHERE id = :workoutplan_tracker_id', array(':workoutplan_tracker_id' => $workoutplan_tracker_id));

            $workoutplan_tracker_id =  $db->query('SELECT MAX(id) FROM workoutplan_tracker WHERE user_id = :user_id AND active = true', array(':user_id' => $user_id))[0][0];
            if (!$workoutplan_tracker_id) {
                //no active workoutplan - handled on client side
                return;
            }
            $workoutplan_tracker = $db->query('SELECT * FROM workoutplan_tracker WHERE id = :workoutplan_tracker_id ', array(':workoutplan_tracker_id' => $workoutplan_tracker_id))[0];
        }

        $workoutplan_id = $workoutplan_tracker['workoutplan_id'];
        //----- workoutplan response start ---------

        $workoutplan =  $db->query('SELECT * FROM workoutplans WHERE id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id))[0];
        if (count($workoutplan) < 1) {
            //we have a workoutplan tracker but the workoutplan does not exist
            http_response_code(500);
            echo '{"error": "Workoutplan for workoutplan tracker does not exist."}';
            return;
        }
        $workoutplan_weeks = $db->query('SELECT * FROM workoutplan_weeks WHERE workoutplan_id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id));

        $workoutplan_weeks_response = "[";
        foreach ($workoutplan_weeks as $workoutplan_week) {
            $workoutplan_days = $db->query('SELECT * FROM workoutplan_days WHERE workoutplan_week_id = :workoutplan_week_id', array(':workoutplan_week_id' => $workoutplan_week['id']));

            $workoutplan_days_response = "[";
            foreach ($workoutplan_days as $workoutplan_day) {
                //get workout of day
                $workout = $db->query('SELECT * FROM workouts WHERE id = :workout_id', array(':workout_id' => $workoutplan_day['workout_id']))[0];
                $workout_rating = $db->query('SELECT id, rating FROM workout_ratings WHERE workout_id = :workout_id AND user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id));
                //get exercises of workout
                $exercises = $db->query('SELECT workout_exercises.exercise_id as id, exercises.name as ex_name, exercises.user_id as usr_id, exercise_information.note as note, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name, tracking_units.id as track_unit_id, tracking_units.name as track_unit_name, exercise_ratings.rating as ex_rating, workout_exercises.sets as sets FROM workout_exercises
                        INNER JOIN exercises ON workout_exercises.exercise_id = exercises.id
                        INNER JOIN exercise_information ON workout_exercises.exercise_id = exercise_information.exercise_id
                        INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
                        INNER JOIN equipment ON exercises.equipment_id = equipment.id
                        INNER JOIN tracking_units ON exercises.tracking_unit_id = tracking_units.id
                        INNER JOIN exercise_ratings ON workout_exercises.exercise_id = exercise_ratings.exercise_id
                        WHERE workout_exercises.workout_id = :workout_id
                        AND exercise_ratings.user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id));
                $ordered_exercises = $exercises;
                if (strlen($workout['exercise_order']) > 1) {
                    $order = explode(",", $workout['exercise_order']);
                    $ordered_exercises = array();
                    for ($i = 0; $i < count($order); $i++) {
                        foreach ($exercises as $e) {
                            if ($e['id'] == $order[$i]) {
                                array_push($ordered_exercises, $e);
                                break;
                            }
                        }
                    }
                }
                //build workout exercises
                $workout_exercises = "[";
                foreach ($ordered_exercises as $exercise) {

                    $bodypart = "{";
                    $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
                    $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
                    $bodypart .= "}";

                    $equipment = "{";
                    $equipment .= '"id": ' . $exercise['eq_id'] . ',';
                    $equipment .= '"name": "' . $exercise['eq_name'] . '"';
                    $equipment .= "}";

                    $tracking_unit = "{";
                    $tracking_unit .= '"id": ' . $exercise['track_unit_id'] . ',';
                    $tracking_unit .= '"name": "' . $exercise['track_unit_name'] . '"';
                    $tracking_unit .= "}";

                    $workout_exercises .= "{";

                    $workout_exercise = "{";
                    $workout_exercise .= '"id": ' . $exercise['id'] . ',';
                    $workout_exercise .= '"name": "' . $exercise['ex_name'] . '",';
                    $workout_exercise .= '"bodypart": ' . $bodypart . ',';
                    $workout_exercise .= '"equipment": ' . $equipment . ',';
                    $workout_exercise .= '"tracking_unit": ' . $tracking_unit . ',';
                    $workout_exercise .= '"rating": "' . $exercise['ex_rating'] . '",';
                    $workout_exercise .= '"note": "' . $exercise['note'] . '",';
                    if ($exercise['usr_id']) {
                        $workout_exercise .= '"global": false';
                    } else {
                        $workout_exercise .= '"global": true';
                    }
                    $workout_exercise .= "}";

                    $workout_exercises .= '"exercise": ' . $workout_exercise . ',';
                    $workout_exercises .= '"sets": ' . $exercise['sets'] . '';

                    $workout_exercises .= "},";
                }
                //only delete comma if there are exercises -> for special case rest day
                if (count($ordered_exercises) > 0) {
                    $workout_exercises = substr($workout_exercises, 0, strlen($workout_exercises) - 1);
                }

                $workout_exercises .= "]";

                $workout_response = "{";
                $workout_response .= '"id": ' . $workout['id'] . ',';
                $workout_response .= '"name": "' . $workout['name'] . '",';
                $workout_response .= '"exercise_order": "' . $workout['exercise_order'] . '",';
                if ($workout_rating) {
                    $workout_response .= '"rating": ' . $workout_rating[0]['rating'] . ',';
                } else {
                    $workout_response .= '"rating": 0,';
                }
                if ($workout['user_id']) {
                    $workout_response .= '"global": false,';
                } else {
                    $workout_response .= '"global": true,';
                }
                if ($workout['public']) {
                    $workout_response .= '"public": true,';
                } else {
                    $workout_response .= '"public": false,';
                }
                $workout_response .= '"exercises": ' . $workout_exercises;
                $workout_response .= "}";

                $workoutplan_days_response .= "{";
                $workoutplan_days_response .= '"id": ' . $workoutplan_day['id'] . ',';
                $workoutplan_days_response .= '"day": ' . $workoutplan_day['day'] . ',';
                $workoutplan_days_response .= '"note": "' . $workoutplan_day['note'] . '",';
                $workoutplan_days_response .= '"workout": ' . $workout_response;
                $workoutplan_days_response .= "},";
            }
            $workoutplan_days_response = substr($workoutplan_days_response, 0, strlen($workoutplan_days_response) - 1);
            $workoutplan_days_response .= "]";

            $workoutplan_weeks_response .= "{";
            $workoutplan_weeks_response .= '"id": ' . $workoutplan_week['id'] . ',';
            if ($workoutplan_week['deload']) {
                $workoutplan_weeks_response .= '"deload": true,';
            } else {
                $workoutplan_weeks_response .= '"deload": false,';
            }
            $workoutplan_weeks_response .= '"days": ' . $workoutplan_days_response;
            $workoutplan_weeks_response .= "},";
        }
        $workoutplan_weeks_response = substr($workoutplan_weeks_response, 0, strlen($workoutplan_weeks_response) - 1);
        $workoutplan_weeks_response .= "]";


        $workoutplan_response = "{";
        $workoutplan_response .= '"id": ' . $workoutplan['id'] . ',';
        $workoutplan_response .= '"name": "' . $workoutplan['name'] . '",';
        $workoutplan_response .= '"duration": ' . $workoutplan['duration'] . ',';
        $workoutplan_response .= '"target": ' . $workoutplan['target'] . ',';
        $workoutplan_response .= '"note": "' . $workoutplan['note'] . '",';
        $workoutplan_response .= '"weeks": ' . $workoutplan_weeks_response;
        $workoutplan_response .= "}";

        //----- workoutplan response end ---------

        //----- tracked workouts response start ---------
        $tracked_workouts_response = "[";
        $workoutplan_tracked_workouts = $db->query('SELECT * FROM workout_tracker WHERE user_id = :user_id AND workoutplan_id = :workoutplan_id AND start_time > :workoutplan_tracker_start_time', array(':user_id' => $user_id, ':workoutplan_id' => $workoutplan_id, ':workoutplan_tracker_start_time' => date("Y-m-d h:i:s", $workoutplan_tracker['start'])));

        if (count($workoutplan_tracked_workouts) < 1) {
            //no tracked workouts - handled on the client side
        } else {

            foreach ($workoutplan_tracked_workouts as $tracked_workout) {
                $tracker_sets = $db->query('SELECT * FROM workout_tracker_sets
                        WHERE tracker_id = :tracker_id', array(':tracker_id' => $tracked_workout['id']));


                //----------- get workout ---------------------------
                $workout = $db->query('SELECT * FROM workouts WHERE id = :workout_id', array(':workout_id' => $tracked_workout['workout_id']))[0];
                $workout_rating = $db->query('SELECT id, rating FROM workout_ratings WHERE workout_id = :workout_id AND user_id = :user_id', array(':workout_id' => $tracked_workout['workout_id'], ':user_id' => $user_id))[0];

                $exercises = $db->query('SELECT workout_exercises.exercise_id as id, exercises.name as ex_name, exercises.user_id as usr_id, exercise_information.note as note, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name, tracking_units.id as track_unit_id, tracking_units.name as track_unit_name, exercise_ratings.rating as ex_rating, workout_exercises.sets as sets FROM workout_exercises
                            INNER JOIN exercises ON workout_exercises.exercise_id = exercises.id
                            INNER JOIN exercise_information ON workout_exercises.exercise_id = exercise_information.exercise_id
                            INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
                            INNER JOIN equipment ON exercises.equipment_id = equipment.id
                            INNER JOIN tracking_units ON exercises.tracking_unit_id = tracking_units.id
                            INNER JOIN exercise_ratings ON workout_exercises.exercise_id = exercise_ratings.exercise_id
                            WHERE workout_exercises.workout_id = :workout_id
                            AND exercise_ratings.user_id = :user_id', array(':workout_id' => $tracked_workout['workout_id'], ':user_id' => $user_id));
                $ordered_exercises = $exercises;
                if (strlen($workout['exercise_order']) > 1) {
                    $order = explode(",", $workout['exercise_order']);
                    $ordered_exercises = array();
                    for ($i = 0; $i < count($order); $i++) {
                        foreach ($exercises as $e) {
                            if ($e['id'] == $order[$i]) {
                                array_push($ordered_exercises, $e);
                                break;
                            }
                        }
                    }
                }

                $workout_exercises = "[";
                foreach ($ordered_exercises as $exercise) {

                    $bodypart = "{";
                    $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
                    $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
                    $bodypart .= "}";

                    $equipment = "{";
                    $equipment .= '"id": ' . $exercise['eq_id'] . ',';
                    $equipment .= '"name": "' . $exercise['eq_name'] . '"';
                    $equipment .= "}";

                    $tracking_unit = "{";
                    $tracking_unit .= '"id": ' . $exercise['track_unit_id'] . ',';
                    $tracking_unit .= '"name": "' . $exercise['track_unit_name'] . '"';
                    $tracking_unit .= "}";

                    $workout_exercises .= "{";

                    $workout_exercise = "{";
                    $workout_exercise .= '"id": ' . $exercise['id'] . ',';
                    $workout_exercise .= '"name": "' . $exercise['ex_name'] . '",';
                    $workout_exercise .= '"bodypart": ' . $bodypart . ',';
                    $workout_exercise .= '"equipment": ' . $equipment . ',';
                    $workout_exercise .= '"tracking_unit": ' . $tracking_unit . ',';
                    $workout_exercise .= '"rating": "' . $exercise['ex_rating'] . '",';
                    $workout_exercise .= '"note": "' . $exercise['note'] . '",';
                    if ($exercise['usr_id']) {
                        $workout_exercise .= '"global": false';
                    } else {
                        $workout_exercise .= '"global": true';
                    }
                    $workout_exercise .= "}";

                    $workout_exercises .= '"exercise": ' . $workout_exercise . ',';
                    $workout_exercises .= '"sets": ' . $exercise['sets'] . '';

                    $workout_exercises .= "},";
                }
                $workout_exercises = substr($workout_exercises, 0, strlen($workout_exercises) - 1);
                $workout_exercises .= "]";

                $workout_response = "{";
                $workout_response .= '"id": ' . $workout['id'] . ',';
                $workout_response .= '"name": "' . $workout['name'] . '",';
                $workout_response .= '"exercise_order": "' . $workout['exercise_order'] . '",';
                $workout_response .= '"rating": "' . $workout_rating['rating'] . '",';
                if ($workout['user_id']) {
                    $workout_response .= '"global": false,';
                } else {
                    $workout_response .= '"global": true,';
                }
                if ($workout['public']) {
                    $workout_response .= '"public": true,';
                } else {
                    $workout_response .= '"public": false,';
                }
                $workout_response .= '"exercises": ' . $workout_exercises;
                $workout_response .= "}";
                //----------------- get workout END --------------


                $workout_tracker_sets = "[";
                foreach ($tracker_sets as $tracker_set) {

                    $workout_tracker_sets .= "{";

                    $workout_tracker_sets .= '"id": ' . $tracker_set['set_id'] . ',';
                    $workout_tracker_sets .= '"tracker_id": ' . $tracker_set['tracker_id'] . ',';
                    $workout_tracker_sets .= '"workout_id": ' . $tracker_set['workout_id'] . ',';
                    $workout_tracker_sets .= '"exercise_id": ' . $tracker_set['exercise_id'] . ',';
                    $workout_tracker_sets .= '"reps": ' . $tracker_set['reps'] . ',';
                    $workout_tracker_sets .= '"weight": "' . $tracker_set['weight'] . '",';
                    $workout_tracker_sets .= '"feeling": ' . $tracker_set['feeling'];

                    $workout_tracker_sets .= "},";
                }
                $workout_tracker_sets = substr($workout_tracker_sets, 0, strlen($workout_tracker_sets) - 1);
                $workout_tracker_sets .= "]";


                $tracked_workouts_response .= "{";
                $tracked_workouts_response .= '"id": ' . $tracked_workout['id'] . ',';
                $tracked_workouts_response .= '"workout": ' . $workout_response . ',';
                $tracked_workouts_response .= '"start": "' . $tracked_workout['start_time'] . '",';
                $tracked_workouts_response .= '"end": "' . $tracked_workout['end_time'] . '",';

                $tracked_workouts_response .= '"tracker_sets": ' . $workout_tracker_sets;
                $tracked_workouts_response .= "},";
            }

            $tracked_workouts_response = substr($tracked_workouts_response, 0, strlen($tracked_workouts_response) - 1);
        }
        $tracked_workouts_response .= "]";

        //----- tracked workouts response end ---------

        $response = "{";
        $response .= '"id": ' . $workoutplan_tracker['id'] . ',';
        $response .= '"workoutplan": ' . $workoutplan_response . ',';
        $response .= '"start": "' . $workoutplan_tracker['start_time'] . '",';
        $response .= '"end": "' . $workoutplan_tracker['end_time'] . '",';
        $response .= '"active": ' . $workoutplan_tracker['active'] . ',';
        $response .= '"tracked_workouts": ' . $tracked_workouts_response;
        $response .= "}";


        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_get_workout_tracker") {
        $token = $args['token'];

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $tracker_id =  $db->query('SELECT MAX(id) FROM workout_tracker WHERE user_id = :user_id', array(':user_id' => $user_id))[0][0];
        if (!$tracker_id) {
            http_response_code(401);
            echo '{"error": "No tracker found."}';
            return;
        }
        $tracker = $db->query('SELECT * FROM workout_tracker WHERE id = :tracker_id', array(':tracker_id' => $tracker_id))[0];
        
        //check if tracker was started within the last 2min
        $current_date = gmdate('Y-m-d h:i:s');
        $threshold_date = date('Y-m-d h:i:s', strtotime('-2 minutes', strtotime($current_date)));
        if ($threshold_date > $tracker['start_time']) {
            http_response_code(401);
            echo '{"error": "Tracker was started too long ago - not valid (start time: '.$tracker['start_time'].')."}';
            return;
        }
        $tracker_sets = $db->query('SELECT * FROM workout_tracker_sets
                    WHERE tracker_id = :tracker_id ORDER BY set_id', array(':tracker_id' => $tracker_id));

        $recent = $db->query('SELECT * FROM workout_tracker_sets
                    WHERE tracker_id IN (SELECT MAX(id) FROM workout_tracker WHERE user_id = :user_id AND id != :tracker_id AND workout_id = :workout_id) ORDER BY set_id', array(':user_id' => $user_id, ':tracker_id' => $tracker_id, ':workout_id' => $tracker['workout_id']));
        if (count($recent) > 1 && count($tracker_sets) == count($recent)) {
            //get weight and rep values from recently tracked exercises
            if ($tracker['workoutplan_id']) {
                //if deload -> half the weight
                //need a way to check if the workout is a deload workout -> pass workoutplan_day_id instead of

                //else progressive overload -> higher weights

                if ($tracker['deload']) {
                    for ($i = 0; $i < count($tracker_sets); $i++) {
                        $tracker_sets[$i]['reps'] = $recent[$i]['reps'];
                        $tracker_sets[$i]['weight'] = ((int)$recent[$i]['weight']) / 2;
                        $tracker_sets[$i]['feeling'] = $recent[$i]['feeling'];
                    }
                } else {
                    //TODO
                    //select rep range + weight step size from exercises and then calc weight with that information
                    //if within rep range -> increase reps by 1
                    //if top of rep range reached -> increase weight by given step size (if none given increase by 1kg)

                    for ($i = 0; $i < count($tracker_sets); $i++) {
                        $exercise_info = $db->query('SELECT * FROM exercise_information WHERE exercise_id = :exercise_id', array(':exercise_id' => $tracker_sets[$i]['exercise_id']))[0];

                        $range = explode('-', $exercise_info['rep_range']);
                        $weight_step = (int)$exercise_info['weight_step_size'];
                        $reps = (int)$recent[$i]['reps'];
                        $weight = (int)$recent[$i]['weight'];
                        if (count($range) == 2) {
                            $from = (int)$range[0];
                            $to = (int)$range[1];
                            if ($reps >= $to) {
                                $reps = $from;
                                if ($weight_step > 0) {
                                    $weight = $weight + $weight_step;
                                } else {
                                    $weight = $weight + 1;
                                }
                            } else if ($reps < $to) {
                                $reps = $reps + 1;
                            }
                        }
                        $tracker_sets[$i]['reps'] = $reps;
                        $tracker_sets[$i]['weight'] = $weight;
                        $tracker_sets[$i]['feeling'] = $recent[$i]['feeling'];
                    }
                }
            } else {
                for ($i = 0; $i < count($tracker_sets); $i++) {
                    $tracker_sets[$i]['reps'] = $recent[$i]['reps'];
                    $tracker_sets[$i]['weight'] = $recent[$i]['weight'];
                    $tracker_sets[$i]['feeling'] = $recent[$i]['feeling'];
                }
            }
        }
        //----------- get workout ---------------------------
        $workout = $db->query('SELECT * FROM workouts WHERE id = :workout_id', array(':workout_id' => $tracker['workout_id']))[0];
        $workout_rating = $db->query('SELECT id, rating FROM workout_ratings WHERE workout_id = :workout_id AND user_id = :user_id', array(':workout_id' => $tracker['workout_id'], ':user_id' => $user_id))[0];

        $exercises = $db->query('SELECT workout_exercises.exercise_id as id, exercises.name as ex_name, exercises.user_id as usr_id, exercise_information.note as note, bodyparts.id as bp_id, bodyparts.name as bp_name, equipment.id as eq_id, equipment.name as eq_name, tracking_units.id as track_unit_id, tracking_units.name as track_unit_name, exercise_ratings.rating as ex_rating, workout_exercises.sets as sets FROM workout_exercises
                INNER JOIN exercises ON workout_exercises.exercise_id = exercises.id
                INNER JOIN exercise_information ON workout_exercises.exercise_id = exercise_information.exercise_id
                INNER JOIN bodyparts ON exercises.bodypart_id = bodyparts.id
                INNER JOIN equipment ON exercises.equipment_id = equipment.id
                INNER JOIN tracking_units ON exercises.tracking_unit_id = tracking_units.id
                INNER JOIN exercise_ratings ON workout_exercises.exercise_id = exercise_ratings.exercise_id
                WHERE workout_exercises.workout_id = :workout_id
                AND exercise_ratings.user_id = :user_id', array(':workout_id' => $workout['id'], ':user_id' => $user_id));
        $ordered_exercises = $exercises;
        if (strlen($workout['exercise_order']) > 1) {
            $order = explode(",", $workout['exercise_order']);
            $ordered_exercises = array();
            for ($i = 0; $i < count($order); $i++) {
                foreach ($exercises as $e) {
                    if ($e['id'] == $order[$i]) {
                        array_push($ordered_exercises, $e);
                        break;
                    }
                }
            }
        }
        $workout_exercises = "[";
        foreach ($ordered_exercises as $exercise) {

            $bodypart = "{";
            $bodypart .= '"id": ' . $exercise['bp_id'] . ',';
            $bodypart .= '"name": "' . $exercise['bp_name'] . '"';
            $bodypart .= "}";

            $equipment = "{";
            $equipment .= '"id": ' . $exercise['eq_id'] . ',';
            $equipment .= '"name": "' . $exercise['eq_name'] . '"';
            $equipment .= "}";

            $tracking_unit = "{";
            $tracking_unit .= '"id": ' . $exercise['track_unit_id'] . ',';
            $tracking_unit .= '"name": "' . $exercise['track_unit_name'] . '"';
            $tracking_unit .= "}";

            $workout_exercises .= "{";

            $workout_exercise = "{";
            $workout_exercise .= '"id": ' . $exercise['id'] . ',';
            $workout_exercise .= '"name": "' . $exercise['ex_name'] . '",';
            $workout_exercise .= '"bodypart": ' . $bodypart . ',';
            $workout_exercise .= '"equipment": ' . $equipment . ',';
            $workout_exercise .= '"tracking_unit": ' . $tracking_unit . ',';
            $workout_exercise .= '"rating": "' . $exercise['ex_rating'] . '",';
            $workout_exercise .= '"note": "' . $exercise['note'] . '",';
            if ($exercise['usr_id']) {
                $workout_exercise .= '"global": false';
            } else {
                $workout_exercise .= '"global": true';
            }
            $workout_exercise .= "}";

            $workout_exercises .= '"exercise": ' . $workout_exercise . ',';
            $workout_exercises .= '"sets": ' . $exercise['sets'] . '';

            $workout_exercises .= "},";
        }
        $workout_exercises = substr($workout_exercises, 0, strlen($workout_exercises) - 1);
        $workout_exercises .= "]";

        $workout_response = "{";
        $workout_response .= '"id": ' . $workout['id'] . ',';
        $workout_response .= '"name": "' . $workout['name'] . '",';
        $workout_response .= '"exercise_order": "' . $workout['exercise_order'] . '",';
        $workout_response .= '"rating": "' . $workout_rating['rating'] . '",';
        if ($workout['user_id']) {
            $workout_response .= '"global": false,';
        } else {
            $workout_response .= '"global": true,';
        }
        if ($workout['public']) {
            $workout_response .= '"public": true,';
        } else {
            $workout_response .= '"public": false,';
        }
        $workout_response .= '"exercises": ' . $workout_exercises;
        $workout_response .= "}";
        //----------------- get workout END --------------

        $workout_tracker_sets = "[";
        foreach ($tracker_sets as $tracker_set) {

            $workout_tracker_sets .= "{";

            $workout_tracker_sets .= '"id": ' . $tracker_set['set_id'] . ',';
            $workout_tracker_sets .= '"tracker_id": ' . $tracker_set['tracker_id'] . ',';
            $workout_tracker_sets .= '"workout_id": ' . $tracker_set['workout_id'] . ',';
            $workout_tracker_sets .= '"exercise_id": ' . $tracker_set['exercise_id'] . ',';
            $workout_tracker_sets .= '"reps": ' . $tracker_set['reps'] . ',';
            $workout_tracker_sets .= '"weight": "' . $tracker_set['weight'] . '",';
            $workout_tracker_sets .= '"feeling": ' . $tracker_set['feeling'];

            $workout_tracker_sets .= "},";
        }
        $workout_tracker_sets = substr($workout_tracker_sets, 0, strlen($workout_tracker_sets) - 1);
        $workout_tracker_sets .= "]";


        $response = "{";
        $response .= '"id": ' . $tracker_id . ',';
        $response .= '"workout": ' . $workout_response . ',';
        $response .= '"start": "' . $tracker['start_time'] . '",';
        $response .= '"end": "' . $tracker['end_time'] . '",';
        if ($tracker['workoutplan_id'] == null) {
            $response .= '"workoutplan_id": null,';
        } else {
            $response .= '"workoutplan_id": ' . $tracker['workoutplan_id'] . ',';
        }
        $response .= '"tracker_sets": ' . $workout_tracker_sets;
        $response .= "}";

        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_other_user_information") {
        $user_id = $args['user_id'];
        $token = $args['token'];
        $user_id_check = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];


        $user_information = $db->query('SELECT * FROM user_information WHERE user_id = :user_id', array(':user_id' => $user_id))[0];
        $user = $db->query('SELECT * FROM users WHERE id = :user_id', array(':user_id' => $user_id))[0];

        $following = $db->query('SELECT * FROM followers WHERE user_id = :user_id AND follower_id = :follower_id', array(':user_id' => $user_id, ':follower_id' => $user_id_check));

        $response = "{";
        $response .= '"id": ' . $user_id . ',';
        $response .= '"username": "' . $user['username'] . '",';
        $response .= '"email": "' . $user['email'] . '",';
        $response .= '"first_name": "' . $user_information['first_name'] . '",';
        $response .= '"last_name": "' . $user_information['last_name'] . '",';
        $response .= '"profile_picture_path": "' . $user_information['profile_picture_path'] . '",';
        if ($user_id == $user_id_check) {
            $response .= '"own_profile": true,';
            $response .= '"following": false';
        } else {
            $response .= '"own_profile": false,';
            if (count($following) > 0) {
                $response .= '"following": true';
            } else {
                $response .= '"following": false';
            }
        }
        $response .= "}";



        http_response_code(200);
        echo $response;
    } else if ($req == "/angular_user_information") {
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $user_information = $db->query('SELECT * FROM user_information WHERE user_id = :user_id', array(':user_id' => $user_id))[0];
        $user = $db->query('SELECT * FROM users WHERE id = :user_id', array(':user_id' => $user_id))[0];

        $response = "{";
        $response .= '"id": ' . $user_id . ',';
        $response .= '"username": "' . $user['username'] . '",';
        $response .= '"email": "' . $user['email'] . '",';
        $response .= '"first_name": "' . $user_information['first_name'] . '",';
        $response .= '"last_name": "' . $user_information['last_name'] . '",';
        $response .= '"profile_picture_path": "' . $user_information['profile_picture_path'] . '",';
        $response .= '"own_profile": true';

        $response .= "}";

        http_response_code(200);
        echo $response;
    }
} else if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if ($req == "/angular_exercise_add") {
        $request = file_get_contents("php://input");
        $request = json_decode($request);

        $token = $request->token;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $exercise = $request->exercise;

        $name = $exercise->name;
        $bodyp = $exercise->bodypart;
        $bodypart_id = $bodyp->id;
        $bodypart_name = $bodyp->name;
        $equip = $exercise->equipment;
        $equipment_id = $equip->id;
        $equipment_name = $equip->name;
        $tracking_unit = $exercise->tracking_unit;
        $tracking_unit_id = $tracking_unit->id;
        $tracking_unit_name = $tracking_unit->name;
        $note = $exercise->note;

        $db->query("INSERT INTO exercises VALUES (default, :name, :bodypart_id, :equipment_id, :tracking_unit_id, :user_id, :note)", array(':name' => $name, ':bodypart_id' => $bodypart_id, ':equipment_id' => $equipment_id, ':tracking_unit_id' => $tracking_unit_id, ':user_id' => $user_id, ':note' => $note));

        $exercise_id = $db->query('SELECT MAX(id) FROM exercises')[0][0];
        $db->query('INSERT INTO exercise_ratings VALUES (default, :exercise_id, :user_id, 0)', array(':exercise_id' => $exercise_id, ':user_id' => $user_id));
        $db->query("INSERT INTO exercise_information VALUES (:exercise_id, '', '', '','',0, false)", array(':exercise_id' => $exercise_id));

        $bodypart = "{";
        $bodypart .= '"id": ' . $bodypart_id . ',';
        $bodypart .= '"name": "' . $bodypart_name . '"';
        $bodypart .= "}";

        $equipment = "{";
        $equipment .= '"id": ' . $equipment_id . ',';
        $equipment .= '"name": "' . $equipment_name . '"';
        $equipment .= "}";

        $tracking_unit = "{";
        $tracking_unit .= '"id": ' . $tracking_unit_id . ',';
        $tracking_unit .= '"name": "' . $tracking_unit_name . '"';
        $tracking_unit .= "}";


        $response = "{";
        $response .= '"id": ' . $exercise_id . ',';
        $response .= '"name": "' . $name . '",';
        $response .= '"bodypart": ' . $bodypart . ',';
        $response .= '"equipment": ' . $equipment . ',';
        $response .= '"tracking_unit": ' . $tracking_unit . ',';
        $response .= '"rating": 0,';
        $response .= '"note": "' . $note . '",';
        $response .= '"global": false';
        $response .= "}";


        http_response_code(200);
        echo $response;
    }else if ($req == "/angular_exercise_duplicate") {
        $request = file_get_contents("php://input");
        $request = json_decode($request);

        $token = $request->token;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $exercise = $request->exercise;

        $name = $exercise->name;
        $bodyp = $exercise->bodypart;
        $bodypart_id = $bodyp->id;
        $bodypart_name = $bodyp->name;
        $equip = $exercise->equipment;
        $equipment_id = $equip->id;
        $equipment_name = $equip->name;
        $tracking_unit = $exercise->tracking_unit;
        $tracking_unit_id = $tracking_unit->id;
        $tracking_unit_name = $tracking_unit->name;
        $note = $exercise->note;

        $db->query("INSERT INTO exercises VALUES (default, :name, :bodypart_id, :equipment_id, :tracking_unit_id, :user_id, '')", array(':name' => $name, ':bodypart_id' => $bodypart_id, ':equipment_id' => $equipment_id, ':tracking_unit_id' => $tracking_unit_id, ':user_id' => $user_id));
        $exercise_id = $db->query('SELECT MAX(id) FROM exercises')[0][0];
        $db->query('INSERT INTO exercise_ratings VALUES (default, :exercise_id, :user_id, 0)', array(':exercise_id' => $exercise_id, ':user_id' => $user_id));
        $db->query("INSERT INTO exercise_information VALUES (:exercise_id, '', '', '','',0, false)", array(':exercise_id' => $exercise_id));
    }else if ($req == "/angular_workout_add") {
        $request = file_get_contents("php://input");
        $request = json_decode($request);

        $token = $request->token;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $workout = $request->workout;

        $name = $workout->name;
        $exercise_order = $workout->exercise_order;

        //exercises array
        $exercises = $workout->exercises;

        $db->query('INSERT INTO workouts VALUES (default, :name, :exercise_order, :user_id, false)', array(':name' => $name, ':exercise_order' => $exercise_order, ':user_id' => $user_id));
        $workout_id = $db->query('SELECT MAX(id) FROM workouts')[0][0];
        $db->query('INSERT INTO workout_ratings VALUES (default, :workout_id, :user_id, 0)', array(':workout_id' => $workout_id, ':user_id' => $user_id));
        $workout_exercises = "[";
        foreach ($exercises as $exercise) {
            $db->query('INSERT INTO workout_exercises VALUES (:workout_id, :exercise_id, :sets)', array(':workout_id' => $workout_id, ':exercise_id' => $exercise->exercise->id, ':sets' => $exercise->sets));

            $bodypart = "{";
            $bodypart .= '"id": ' . $exercise->exercise->bodypart->id . ',';
            $bodypart .= '"name": "' . $exercise->exercise->bodypart->name . '"';
            $bodypart .= "}";

            $equipment = "{";
            $equipment .= '"id": ' . $exercise->exercise->equipment->id . ',';
            $equipment .= '"name": "' . $exercise->exercise->equipment->name . '"';
            $equipment .= "}";

            $tracking_unit = "{";
            $tracking_unit .= '"id": ' . $exercise->exercise->tracking_unit->id . ',';
            $tracking_unit .= '"name": "' . $exercise->exercise->tracking_unit->name . '"';
            $tracking_unit .= "}";

            $workout_exercises .= "{";

            $workout_exercise = "{";
            $workout_exercise .= '"id": ' . $exercise->exercise->id . ',';
            $workout_exercise .= '"name": "' . $exercise->exercise->name . '",';
            $workout_exercise .= '"bodypart": ' . $bodypart . ',';
            $workout_exercise .= '"equipment": ' . $equipment . ',';
            $workout_exercise .= '"tracking_unit": ' . $tracking_unit . ',';
            $workout_exercise .= '"rating": "' . $exercise->exercise->rating . '",';
            $workout_exercise .= '"note": "' . $exercise->exercise->note . '",';
            if ($exercise->exercise->global) {
                $workout_exercise .= '"global": false';
            } else {
                $workout_exercise .= '"global": true';
            }


            $workout_exercise .= "}";

            $workout_exercises .= '"exercise": ' . $workout_exercise . ',';
            $workout_exercises .= '"sets": ' . $exercise->sets;

            $workout_exercises .= "},";
        }
        $workout_exercises = substr($workout_exercises, 0, strlen($workout_exercises) - 1);
        $workout_exercises .= "]";


        $response = "{";
        $response .= '"id": ' . $workout_id . ',';
        $response .= '"name": "' . $name . '",';
        $response .= '"exercise_order": "' . $exercise_order . '",';
        $response .= '"rating": 0,';
        $response .= '"global": false,';
        $response .= '"public": false,';
        $response .= '"exercises": ' . $workout_exercises;
        $response .= "}";
        http_response_code(200);
        echo $response;
    }else if ($req == "/angular_workout_duplicate") {

        $request = file_get_contents("php://input");
        $request = json_decode($request);

        $token = $request->token;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $workout = $request->workout;

        $name = $workout->name;
        $exercise_order = $workout->exercise_order;
        $rating = $workout->rating;

        //exercises array
        $exercises = $workout->exercises;

        $db->query('INSERT INTO workouts VALUES (default, :name, :exercise_order, :user_id, false)', array(':name' => $name, ':exercise_order' => $exercise_order, ':user_id' => $user_id));
        $workout_id = $db->query('SELECT MAX(id) FROM workouts')[0][0];
        $db->query('INSERT INTO workout_ratings VALUES (default, :workout_id, :user_id, 0)', array(':workout_id' => $workout_id, ':user_id' => $user_id));

        foreach ($exercises as $exercise) {
            $db->query('INSERT INTO workout_exercises VALUES (:workout_id, :exercise_id, :sets)', array(':workout_id' => $workout_id, ':exercise_id' => $exercise->exercise->id, ':sets' => $exercise->sets));
        }
    }else if ($req == "/login") {

        $request = file_get_contents("php://input");
        $request = json_decode($request);
        $username = $request->username;
        $password = $request->password;
        $user_verification = $request->user_verification;

        if ($db->query('SELECT username FROM users WHERE username=:username', array(':username' => $username))) {
            $hash = $db->query('SELECT password FROM users WHERE username=:username', array(':username' => $username))[0]['password'];
            if (password_verify($password, $hash)) {
                $user_id = $db->query('SELECT id FROM users WHERE username=:username', array(':username' => $username))[0]['id'];
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    // Rehash the password and update the database.
                    $newHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);
                    $db->query('UPDATE users SET
                            password = :password WHERE id = :id', array(':password' => $newHash, ':id' => $user_id));
                }

                $cstrong = True;
                $token = bin2hex(openssl_random_pseudo_bytes(64, $cstrong));

                $db->query('INSERT INTO login_tokens VALUES (default, :token, :user_verification, :user_id)', array(':token' => hash_hmac('sha512', $token, 'insert secret key'), ':user_verification' => $user_verification, ':user_id' => $user_id));
                
                $response = "{";
                $response .= '"redirect": "/dashboard",';
                $response .= '"user_id": ' . $user_id . ',';
                $response .= '"valid": true,';
                $response .= '"token": "' . $token . '"';
                $response .= "}";

                http_response_code(200);
                echo $response;
            } else {

                $response = "{";
                $response .= '"redirect": "",';
                $response .= '"user_id": 0,';
                $response .= '"valid": false,';
                $response .= '"token": ""';
                $response .= "}";

                http_response_code(200);
                echo $response;
            }
        } else {
            $response = "{";
            $response .= '"redirect": "",';
            $response .= '"user_id": 0,';
            $response .= '"valid": false,';
            $response .= '"token": ""';
            $response .= "}";

            http_response_code(200);
            echo $response;
        }
    }else if ($req == "/update_cookies") {

        $request = file_get_contents("php://input");
        $request = json_decode($request);
        $token = $request->token;

        if ($db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))) {
            $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];

            $cstrong = True;
            $new_token = bin2hex(openssl_random_pseudo_bytes(64, $cstrong));

            $db->query('INSERT INTO login_tokens VALUES (default, :token, :user_id)', array(':token' => hash_hmac('sha512', $new_token, 'insert secret key'), ':user_id' => $user_id));
            $db->query('DELETE FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')));

            $values_spid = "{";
            $values_spid .= '"expires": ' . (time() + 60 * 60 * 24 * 7) . ',';
            $values_spid .= '"path": "/",';
            $values_spid .= '"domain": "' . NULL . '",';
            $values_spid .= '"secure": ' . true . ',';
            $values_spid .= '"httponly": ' . true . ',';
            $values_spid .= '"samesite": "Strict"';
            $values_spid .= "}";

            $spid = "{";
            $spid .= '"name": "SPID",';
            $spid .= '"value": "' . $new_token . '",';
            $spid .= '"attributes": ' . $values_spid;
            $spid .= "}";

            $values_spid_ = "{";
            $values_spid_ .= '"expires": ' . (time() + 60 * 60 * 24 * 3) . ',';
            $values_spid_ .= '"path": "/",';
            $values_spid_ .= '"domain": "' . NULL . '",';
            $values_spid_ .= '"secure": ' . true . ',';
            $values_spid_ .= '"httponly": ' . true . ',';
            $values_spid_ .= '"samesite": "Strict"';
            $values_spid_ .= "}";

            $spid_ = "{";
            $spid_ .= '"name": "SPID_",';
            $spid_ .= '"value": 1,';
            $spid_ .= '"attributes": ' . $values_spid_;
            $spid_ .= "}";

            $response = "{";
            $response .= '"redirect": "/dashboard",';
            $response .= '"cookie_spid": ' . $spid . ',';
            $response .= '"cookie_spid_": ' . $spid_;
            $response .= "}";

            http_response_code(200);
            echo $response;
        } else {
            http_response_code(409);
            echo '{ "Error": "Token does not exist!" }';
        }
    }else if ($req == "/angular_start_tracker") {
        $request = file_get_contents("php://input");
        $request = json_decode($request);
        $workout_id = $request->workout_id;
        $workoutplan_id = $request->workoutplan_id;
        $token = $request->token;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        if ($workoutplan_id == 0) {
            $workoutplan_id = null;
        }
        $db->query('INSERT INTO workout_tracker VALUES (default, :workout_id, NOW(), NOW(), :user_id, :workoutplan_id)', array(':workout_id' => $workout_id, ':user_id' => $user_id,  ':workoutplan_id' => $workoutplan_id));
        $tracker_id = $db->query('SELECT MAX(id) FROM workout_tracker WHERE workout_id = :workout_id AND user_id = :user_id', array(':workout_id' => $workout_id, ':user_id' => $user_id))[0][0];
        $exercises = $db->query('SELECT workout_exercises.exercise_id as id, workout_exercises.sets as sets FROM workout_exercises
                        WHERE workout_exercises.workout_id = :workout_id', array(':workout_id' => $workout_id));

        foreach ($exercises as $exercise) {
            for ($i = 0; $i < $exercise['sets']; $i++) {
                $db->query(
                    "INSERT INTO workout_tracker_sets VALUES (default, :tracker_id, :workout_id, :exercise_id, '0', '0',0)",
                    array(':tracker_id' => $tracker_id, ':workout_id' => $workout_id, ':exercise_id' => $exercise['id'])
                );
            }
        }
    }else if ($req == "/angular_create_workoutplan") {

        $request = file_get_contents("php://input");
        $request = json_decode($request);
        $name = $request->name;
        $duration = $request->duration;
        $token = $request->token;

        //---------- add target in future -------------------
        //$target = $request->target;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $db->query("INSERT INTO workoutplans VALUES (default, :name, :duration, 0, '', :user_id)", array(':name' => $name, ':duration' => $duration, ':user_id' => $user_id));
        $workoutplan_id =  $db->query('SELECT MAX(id) FROM workoutplans WHERE user_id = :user_id', array(':user_id' => $user_id))[0][0];

        //add empty weeks and days for the workoutplan to DB
        for ($i = 0; $i < $duration; $i++) {
            $db->query('INSERT INTO workoutplan_weeks VALUES (default, false, :workoutplan_id)', array(':workoutplan_id' => $workoutplan_id));
            $workoutplan_week_id =  $db->query('SELECT MAX(id) FROM workoutplan_weeks WHERE workoutplan_id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id))[0][0];

            for ($day = 0; $day < 7; $day++) {
                $db->query("INSERT INTO workoutplan_days VALUES (default, :day, '', 1, :workoutplan_week_id)", array(':day' => $day, ':workoutplan_week_id' => $workoutplan_week_id));
            }
        }
    }else if ($req == "/angular_workoutplan_duplicate") {
        $request = file_get_contents("php://input");
        $request = json_decode($request);

        $token = $request->token;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $workoutplan = $request->workoutplan;

        $name = $workoutplan->name;
        $duration = $workoutplan->duration;
        $target = $workoutplan->target;
        $note = $workoutplan->note;


        $weeks = $workoutplan->weeks;

        $db->query('INSERT INTO workoutplans VALUES (default, :name, :duration, :target, :note, :user_id)', array(':name' => $name, ':duration' => $duration, ':target' => $target, ':note' => $note, ':user_id' => $user_id));
        $workoutplan_id = $db->query('SELECT MAX(id) FROM workoutplans')[0][0];


        foreach ($weeks as $week) {
            //set deload for all weeks
            $deload = $week->deload;
            if (!$deload) {
                $deload = 0;
            }
            $db->query('INSERT INTO workoutplan_weeks VALUES (default, :deload, :workoutplan_id)', array(':deload' => $deload, ':workoutplan_id' => $workoutplan_id));
            $workoutplan_week_id =  $db->query('SELECT MAX(id) FROM workoutplan_weeks WHERE workoutplan_id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id))[0][0];
            foreach ($week->days as $day) {
                $db->query("INSERT INTO workoutplan_days VALUES (default, :day, :note, :workout_id, :workoutplan_week_id)", array(':day' => $day->day, ':note' => $day->note, ':workout_id' => $day->workout->id, ':workoutplan_week_id' => $workoutplan_week_id));
            }
        }
    }else if ($req == "/angular_workoutplan_add_week") {

        $request = file_get_contents("php://input");
        $request = json_decode($request);
        $workoutplan_id = $request->workoutplan_id;
        $token = $request->token;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $db->query('INSERT INTO workoutplan_weeks VALUES (default, false, :workoutplan_id)', array(':workoutplan_id' => $workoutplan_id));
        $workoutplan_week_id =  $db->query('SELECT MAX(id) FROM workoutplan_weeks WHERE workoutplan_id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id))[0][0];

        for ($day = 0; $day < 7; $day++) {
            $db->query("INSERT INTO workoutplan_days VALUES (default, :day, '', 1, :workoutplan_week_id)", array(':day' => $day, ':workoutplan_week_id' => $workoutplan_week_id));
        }

        $workoutplan_week = $db->query('SELECT * FROM workoutplan_weeks WHERE id = :workoutplan_week_id', array(':workoutplan_week_id' => $workoutplan_week_id))[0];


        $workoutplan_days = $db->query('SELECT * FROM workoutplan_days WHERE workoutplan_week_id = :workoutplan_week_id', array(':workoutplan_week_id' => $workoutplan_week['id']));

        $workout = $db->query('SELECT * FROM workouts WHERE id = 1')[0];

        $workout_response = "{";
        $workout_response .= '"id": ' . $workout['id'] . ',';
        $workout_response .= '"name": "' . $workout['name'] . '",';
        $workout_response .= '"exercise_order": "' . $workout['exercise_order'] . '",';
        $workout_response .= '"rating": 0,';
        $workout_response .= '"global": false,';
        $workout_response .= '"exercises": []';
        $workout_response .= "}";


        $workoutplan_days_response = "[";
        foreach ($workoutplan_days as $workoutplan_day) {
            $workoutplan_days_response .= "{";
            $workoutplan_days_response .= '"id": ' . $workoutplan_day['id'] . ',';
            $workoutplan_days_response .= '"day": ' . $workoutplan_day['day'] . ',';
            $workoutplan_days_response .= '"note": "' . $workoutplan_day['note'] . '",';
            $workoutplan_days_response .= '"workout": ' . $workout_response;
            $workoutplan_days_response .= "},";
        }
        $workoutplan_days_response = substr($workoutplan_days_response, 0, strlen($workoutplan_days_response) - 1);
        $workoutplan_days_response .= "]";

        $response = "{";
        $response .= '"id": ' . $workoutplan_week['id'] . ',';
        if ($workoutplan_week['deload']) {
            $response .= '"deload": true,';
        } else {
            $response .= '"deload": false,';
        }

        $response .= '"days": ' . $workoutplan_days_response;
        $response .= "}";


        http_response_code(200);
        echo $response;
    }else if ($req == "/angular_activate_workoutplan") {

        $request = file_get_contents("php://input");
        $request = json_decode($request);
        $workoutplan_id = $request->workoutplan_id;
        $token = $request->token;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $duration = $db->query('SELECT duration FROM workoutplans WHERE id=:id', array(':id' => $workoutplan_id))[0]['duration'];
        //date_default_timezone_set('MET');
        $start_date = date('Y-m-d h:i:s');
        $end = Date('Y-m-d', strtotime("+" . ($duration * 7) . " days"));
        $end_date =  $end . " 23:59:59";

        $db->query('INSERT INTO workoutplan_tracker VALUES (default, :workoutplan_id, :start_date,:end_date,true,:user_id)', array(':workoutplan_id' => $workoutplan_id, ':start_date' => $start_date, ':end_date' => $end_date, ':user_id' => $user_id));
    }else if ($req == "/register") {
        //registering is currently deactivated
        return;
        $request = file_get_contents("php://input");
        $request = json_decode($request);
        $username = $request->username;
        $email = $request->email;
        $password = $request->password;

        $db->query('INSERT INTO users VALUES (default, :username, :password, :email)', array(':username' => $username, ':password' => password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]), ':email' => $email));
        $user_id =  $db->query('SELECT MAX(id) FROM users WHERE username = :username', array(':username' => $request->username))[0][0];
        $db->query("INSERT INTO user_information VALUES (:user_id, '', '', '')", array(':user_id' => $user_id));
        //create initial ratings for all global exercises, workouts etc.
        $exercises = $db->query('SELECT * from exercises where user_id is null');
        foreach ($exercises as $exercise) {
            $db->query('INSERT INTO exercise_ratings VALUES (default, :exercise_id, :user_id, 0)', array(':exercise_id' => $exercise['id'], ':user_id' => $user_id));
        }

        $workouts = $db->query('SELECT * from workouts where user_id is null');
        foreach ($workouts as $workout) {
            $db->query('INSERT INTO workout_ratings VALUES (default, :workout_id, :user_id, 0)', array(':workout_id' => $workout['id'], ':user_id' => $user_id));
        }
    }else if ($req == "/angular_follow_user") {
        $request = file_get_contents("php://input");
        $request = json_decode($request);

        $token = $request->token;

        $user_id_check = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $user_id = $request->user_id;

        if ($user_id_check == $user_id) {
            //wanna follow yourself ?!
            return;
        }
        $db->query('INSERT INTO followers VALUES (:user_id, :follower_id)', array(':user_id' => $user_id, ':follower_id' => $user_id_check));
    }else if ($req == "/angular_image_upload") {
        $file_name = $_FILES['fileKey']['name'];

        echo $file_name;
        return;

        $token = $_POST['token'];

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        //get file extension
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);

        //target folder
        $target_path = "../data/" . $user_id . '/';
        if (!file_exists('../data/')) {
            mkdir('../data/', 0777, true);
        }
        //create user folder
        if (!file_exists($target_path)) {
            mkdir($target_path, 0777, true);
        }

        $target_path = $target_path . 'profile_image/';
        //create user profile image folder
        if (!file_exists($target_path)) {
            mkdir($target_path, 0777, true);
        }

        //replace special chars in the file name
        $actual_fname = preg_replace('/[^A-Za-z0-9\-]/', '', $file_name);

        //set random unique name why because file name duplicate will replace
        //the existing files
        $modified_fname = uniqid(rand(10, 200)) . '-' . rand(1000, 1000000) . '-' . $actual_fname;

        //set target file path
        $target_path = $target_path . basename($modified_fname) . "." . $ext;

        $result = array();

        //move the file to target folder
        if (move_uploaded_file($_FILES['fileKey']['tmp_name'], $target_path)) {
            $image_path = str_replace("..", "", $target_path);
            $db->query('UPDATE user_information SET profile_picture_path = :image_path WHERE user_id = :user_id', array(':image_path' => $image_path, ':user_id' => $user_id));
        }
    }else if ($_GET['url'] == "angular_exercise_visual_upload") {
        $file_name = $_FILES['fileKey']['name'];
        $exercise_id = $_POST['exercise_id'];
        $token = $_POST['token'];

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        //get file extension
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);

        //target folder
        $target_path = "../data/exercises_data/exercise_" . $exercise_id . '/';
        if (!file_exists('../data/exercises_data/')) {
            mkdir('../data/exercises_data/', 0777, true);
        }
        //create user folder
        if (!file_exists($target_path)) {
            mkdir($target_path, 0777, true);
        }

        $target_path = $target_path . 'visuals/';
        //create user profile image folder
        if (!file_exists($target_path)) {
            mkdir($target_path, 0777, true);
        }

        //replace special chars in the file name
        $actual_fname = preg_replace('/[^A-Za-z0-9\-]/', '', $file_name);

        //set random unique name why because file name duplicate will replace
        //the existing files
        $modified_fname = uniqid(rand(10, 200)) . '-' . rand(1000, 1000000) . '-' . $actual_fname;

        //set target file path
        $target_path = $target_path . basename($modified_fname) . "." . $ext;

        $result = array();

        //move the file to target folder
        if (move_uploaded_file($_FILES['fileKey']['tmp_name'], $target_path)) {
            $image_path = str_replace("..", "", $target_path);
            $db->query('UPDATE exercise_information SET visual_path = :image_path WHERE exercise_id = :exercise_id', array(':image_path' => $image_path, ':exercise_id' => $exercise_id));
        }
    }
    
} else if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {

    $response = "{";
    $response .= 'Content-Type: "application/json,"';
    $response .= 'Access-Control-Allow-Origin: "https://strangeprojects.herokuapp.com",';
    $response .= 'Access-Control-Allow-Credentials: true,';
    $response .= 'Access-Control-Max-Age: 60,';
    $response .= 'Access-Control-Allow-Methods: "GET, POST, PUT, DELETE, OPTIONS",';
    $response .= 'Access-Control-Allow-Headers: "AccountKey,x-requested-with, Content-Type, origin, authorization, accept, client-security-token, host, date, cookie, cookie2"';

    $response .= "}";
    http_response_code(200);
    echo $response;
} else if ($_SERVER['REQUEST_METHOD'] == "PUT") {
    if ($req == "/angular_exercise_update") {
        $request = file_get_contents("php://input");
        $request = json_decode($request);

        $token = $request->token;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        $exercise = $request->exercise;
        $exercise_id = $exercise->id;
        $name = $exercise->name;
        $bodyp = $exercise->bodypart;
        $bodypart_id = $bodyp->id;
        $bodypart_name = $bodyp->name;
        $equip = $exercise->equipment;
        $equipment_id = $equip->id;
        $equipment_name = $equip->name;
        $tracking_unit = $exercise->tracking_unit;
        $tracking_unit_id = $tracking_unit->id;
        $tracking_unit_name = $tracking_unit->name;
        $note = $exercise->note;
        $public = $exercise->public;
        $rep_range = $exercise->rep_range;
        $weight_step_size = $exercise->weight_step_size;
        $rating = $exercise->rating;
        $description = $exercise->description;

        if (!$public) {
            $public = 0;
        }

        $db->query('UPDATE exercises SET name = :name,
                bodypart_id = :bodypart_id,
                equipment_id = :equipment_id,
                tracking_unit_id = :tracking_unit_id
                WHERE id=:exercise_id', array(':name' => $name, ':bodypart_id' => $bodypart_id, ':equipment_id' => $equipment_id, ':tracking_unit_id' => $tracking_unit_id, ':exercise_id' => $exercise_id));

        $db->query('UPDATE exercise_information SET note = :note,
                public = :public,
                rep_range = :rep_range,
                weight_step_size = :weight_step_size,
                description = :description
                WHERE exercise_id=:exercise_id', array(':note' => $note, ':public' => $public, ':rep_range' => $rep_range, ':weight_step_size' => $weight_step_size, ':description' => $description, ':exercise_id' => $exercise_id));

        if ($rating > 10) {
            $rating = 10;
        }
        if ($rating < 0) {
            $rating = 0;
        }
        $db->query('UPDATE exercise_ratings SET rating = :rating WHERE exercise_id = :exercise_id AND user_id = :user_id', array(':rating' => $rating, ':exercise_id' => $exercise_id, ':user_id' => $user_id));
    }else if ($req == "/angular_workout_update") {
        $request = file_get_contents("php://input");
        $request = json_decode($request);

        $token = $request->token;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $valid_exercise_order = true;

        $workout_req = $request->workout;

        $workout_id = $workout_req->id;
        $name = $workout_req->name;
        $exercise_order = $workout_req->exercise_order;
        $rating = $workout_req->rating;
        $public = $workout_req->public;

        //exercises array
        $exercises = $workout_req->exercises;

        $exercises_arr = [];

        $db_exercises = $db->query('SELECT exercise_id FROM workout_exercises
            WHERE workout_id = :workout_id', array(':workout_id' => $workout_id));

        $db_exercise_arr = [];

        foreach ($db_exercises as $e) {
            array_push($db_exercise_arr, $e[0]);
        }

        //check for new exercises -> push them into array
        $new_exercises = [];
        $new_exercises_sets = [];
        foreach ($exercises as $exercise) {
            if (!in_array($exercise->exercise->id, $db_exercise_arr)) {
                //push new exercise to new_exercises array
                array_push($new_exercises, $exercise->exercise->id);
                array_push($new_exercises_sets, $exercise->sets);
            }
            //push all exercises to exercises array
            array_push($exercises_arr, $exercise->exercise->id);
        }

        $deleted_exercises = [];
        //check for deleted exercises -> push them into array
        foreach ($db_exercises as $e) {
            if (!in_array($e[0], $exercises_arr)) {
                //push deleted exercise to delted exercises array
                array_push($deleted_exercises, $e[0]);
            }
        }

        //check if order is valid
        //-> insert new exercises
        for ($i = 0; $i < count($new_exercises); $i++) {
            $db->query('INSERT INTO workout_exercises VALUES (:workout_id, :exercise_id, :sets)', array(':workout_id' => $workout_id, ':exercise_id' => $new_exercises[$i], ':sets' => $new_exercises_sets[$i]));
        }
        //-> remove deleted exercises
        for ($i = 0; $i < count($deleted_exercises); $i++) {
            $db->query('DELETE FROM workout_exercises WHERE workout_id = :workout_id AND exercise_id = :exercise_id', array(':workout_id' => $workout_id, ':exercise_id' => $deleted_exercises[$i],));
        }

        //-> check exercise order -> if something wrong -> generate new one
        $exercise_order_check = $db->query('SELECT exercise_order FROM workouts WHERE id = :workout_id ', array(':workout_id' => $workout_id))[0][0];

        if (hash_equals($exercise_order_check, $exercise_order)) {
            //same order, nothing changed
        }

        $db_ex_order_arr = explode(',', $exercise_order_check);
        $ex_order_arr = explode(',', $exercise_order);

        //check if amount of exercises in exercises array equals amount of exercises in exercise_order
        if (count($ex_order_arr) != count($exercises_arr)) {
            //echo "BAD REQUEST: Wrong exercise count!";
            $valid_exercise_order = false;
        }

        //check if there are exercises in exercise order which are not in the exercise array
        for ($i = 0; $i < count($ex_order_arr); $i++) {
            if (!in_array($ex_order_arr[$i], $exercises_arr)) {
                //echo "BAD REQUEST: Wrong exercise in exercise order!";
                $valid_exercise_order = false;
            }
        }

        if (!$public) {
            $public = 0;
        }
        //update everything
        if ($valid_exercise_order) {
            $db->query('UPDATE workouts SET name = :name,
                exercise_order = :exercise_order,
                public = :public
                WHERE id=:workout_id', array(':name' => $name, ':exercise_order' => $exercise_order, ':public' => $public, ':workout_id' => $workout_id));
        } else {
            $new_exercise_order = implode(",", $exercises_arr);
            $db->query('UPDATE workouts SET name = :name,
                exercise_order = :exercise_order,
                public = :public
                WHERE id=:workout_id', array(':name' => $name, ':exercise_order' => $new_exercise_order, ':public' => $public, ':workout_id' => $workout_id));
        }

        if ($rating > 10) {
            $rating = 10;
        }
        if ($rating < 0) {
            $rating = 0;
        }

        $db->query('UPDATE workout_ratings SET rating = :rating WHERE workout_id = :workout_id AND user_id = :user_id', array(':rating' => $rating, ':workout_id' => $workout_id, ':user_id' => $user_id));

        foreach ($exercises as $exercise) {
            $db->query('UPDATE workout_exercises SET sets = :sets WHERE workout_id = :workout_id AND exercise_id = :exercise_id', array(':sets' => $exercise->sets, ':workout_id' => $workout_id, ':exercise_id' => $exercise->exercise->id));
        }
    }else if ($req == "/angular_workoutplan_update") {
        $request = file_get_contents("php://input");
        $request = json_decode($request);

        $token = $request->token;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $workoutplan = $request->workoutplan;

        $workoutplan_id = $workoutplan->id;
        $name = $workoutplan->name;
        $duration = $workoutplan->duration;
        $note = $workoutplan->note;

        $weeks = $workoutplan->weeks;

        $db->query('UPDATE workoutplans SET name = :name,
                note = :note,
                duration = :duration
                WHERE id=:workoutplan_id', array(':name' => $name, ':note' => $note, ':duration' => $duration, ':workoutplan_id' => $workoutplan_id));


        foreach ($weeks as $week) {
            //set deload for all weeks
            $deload = $week->deload;
            if (!$deload) {
                $deload = 0;
            }
            $db->query('UPDATE workoutplan_weeks SET
                    deload = :deload
                    WHERE id=:week_id', array(':deload' => $deload, ':week_id' => $week->id));

            foreach ($week->days as $day) {
                $db->query('UPDATE workoutplan_days SET
                    note = :note,
                    workout_id = :workout_id
                    WHERE id=:day_id', array(':note' => $day->note, ':day_id' => $day->id, ':workout_id' => $day->workout->id));
            }
        }
    }else if ($req == "/angular_stop_tracker") {
        $request = file_get_contents("php://input");
        $request = json_decode($request);

        $token = $request->token;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        $tracker = $request->tracker;
        $tracker_id = $tracker->id;
        $tracker_sets = $tracker->tracker_sets;

        $db->query('UPDATE workout_tracker SET
                end_time = NOW()
                WHERE id = :tracker_id', array(':tracker_id' => $tracker_id));

        foreach ($tracker_sets as $tracker_set) {

            if ((int)$tracker_set->feeling > 100) {
                $tracker_set->feeling = 100;
            }
            if ((int)$tracker_set->feeling < 0) {
                $tracker_set->feeling = 0;
            }
            $db->query(
                'UPDATE workout_tracker_sets SET 
                    reps = :reps,
                    weight = :weight,
                    feeling = :feeling 
                    WHERE set_id = :set_id',
                array(':set_id' => $tracker_set->id, ':reps' => (int)$tracker_set->reps, ':weight' => (int)$tracker_set->weight, ':feeling' => (int)$tracker_set->feeling)
            );
        }
    }else if ($req == "/angular_user_profile_update") {
        $request = file_get_contents("php://input");
        $request = json_decode($request);

        $token = $request->token;

        $user_id_check = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $user_profile = $request->user_profile;
        $user_id = $user_profile->id;
        $username = $user_profile->username;
        $email = $user_profile->email;
        $first_name = $user_profile->first_name;
        $last_name = $user_profile->last_name;

        if ($user_id_check != $user_id) {
            http_response_code(401);
            echo '{"error": "User logged in not the same as User requesting!"}';
            return;
        }


        $db->query('UPDATE users SET username = :username,
                email = :email
                WHERE id=:user_id', array(':username' => $username, ':email' => $email, ':user_id' => $user_id));

        $db->query('UPDATE user_information SET first_name = :first_name,
                last_name = :last_name
                WHERE user_id=:user_id', array(':first_name' => $first_name, ':last_name' => $last_name, ':user_id' => $user_id));
    }else if ($req == "/angular_password_change") {
        $request = file_get_contents("php://input");
        $request = json_decode($request);

        $token = $request->token;

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $password = $request->password;

        $db->query('UPDATE users SET password = :password
                WHERE id=:user_id', array(':password' => password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]), ':user_id' => $user_id));
    }

} else if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
    if ($req == "/angular_unfollow_user") {
        $user_id = $args['user_id'];
        $token = $args['token'];

        $user_id_check = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        if ($user_id_check == $user_id) {
            //wanna unfollow yourself ?!
            return;
        }

        $db->query('DELETE FROM followers WHERE user_id = :user_id AND follower_id = :follower_id', array(':user_id' => $user_id, ':follower_id' => $user_id_check));
    } else if ($req == "/angular_exercise_delete") {
        $exercise_id = $args['exercise_id'];
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        if ($db->query('SELECT user_id FROM exercises WHERE id = :exercise_id', array(':exercise_id' => $exercise_id))[0]['user_id'] == null) {
            //global exercise -> only delete users rating
            //$db->query('DELETE FROM exercise_ratings WHERE exercise_id = :exercise_id AND user_id = :user_id', array(':exercise_id' => $exercise_id, ':user_id' => $user_id));
        } else {
            //only delete exercise if not global + delete all ratings
            $db->query('DELETE FROM exercises WHERE id = :exercise_id', array(':exercise_id' => $exercise_id));
            $db->query('DELETE FROM exercise_ratings WHERE exercise_id = :exercise_id', array(':exercise_id' => $exercise_id));
            $db->query('DELETE FROM exercise_information WHERE exercise_id = :exercise_id', array(':exercise_id' => $exercise_id));
        }
    } else if ($req == "/angular_workout_delete") {
        $workout_id = $args['workout_id'];
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        if ($db->query('SELECT user_id FROM workouts WHERE id = :workout_id', array(':workout_id' => $workout_id))[0]['user_id'] == null) {
            //global workout -> only delete users rating
            $db->query('DELETE FROM workout_ratings WHERE workout_id = :workout_id AND user_id = :user_id', array(':workout_id' => $workout_id, ':user_id' => $user_id));
        } else {
            //only delete workout if not global + delete all ratings
            $db->query('DELETE FROM workout_ratings WHERE workout_id = :workout_id', array(':workout_id' => $workout_id));
            $db->query('DELETE FROM workout_exercises WHERE workout_id = :workout_id', array(':workout_id' => $workout_id));
            $db->query('DELETE FROM workouts WHERE id = :workout_id', array(':workout_id' => $workout_id));
            $db->query('DELETE FROM workout_tracker WHERE workout_id = :workout_id', array(':workout_id' => $workout_id));
            $db->query('DELETE FROM workout_tracker_sets WHERE workout_id = :workout_id', array(':workout_id' => $workout_id));
        }

        //$db->query('DELETE FROM workout_ratings WHERE workout_id = :workout_id', array(':workout_id' => $workout_id));
        //$db->query('DELETE FROM workout_exercises WHERE workout_id = :workout_id', array(':workout_id' => $workout_id));
        //$db->query('DELETE FROM workouts WHERE id = :workout_id', array(':workout_id' => $workout_id));

    } else if ($req == "/angular_workout_exercise_delete") {
        $workout_id = $args['workout_id'];
        $exercise_id = $args['exercise_id'];
        $token = $args['token'];

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        if ($db->query('SELECT user_id FROM workouts WHERE id = :workout_id', array(':workout_id' => $workout_id))[0]['user_id'] == null) {
            //global workout
            return;
        }

        $exercise_order = $db->query('SELECT exercise_order FROM workouts WHERE id = :workout_id ', array(':workout_id' => $workout_id))[0][0];
        $ex_order_arr = explode(',', $exercise_order);
        $new_order = [];

        for ($i = 0; $i < count($ex_order_arr); $i++) {
            if ($ex_order_arr[$i] == $exercise_id) {
                continue;
            }
            array_push($new_order, $ex_order_arr[$i]);
        }

        $db->query('DELETE FROM workout_exercises WHERE workout_id = :workout_id AND exercise_id = :exercise_id', array(':workout_id' => $workout_id, ':exercise_id' => $exercise_id));

        $new_exercise_order = implode(",", $new_order);
        $db->query('UPDATE workouts SET exercise_order = :exercise_order
                WHERE id=:workout_id', array(':exercise_order' => $new_exercise_order, ':workout_id' => $workout_id));
    } else if ($req == "/angular_workoutplan_delete") {

        $workoutplan_id = $args['workoutplan_id'];
        $token = $args['token'];
        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }

        $workoutplan_weeks = $db->query('SELECT * FROM workoutplan_weeks WHERE workoutplan_id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id));

        foreach ($workoutplan_weeks as $workoutplan_week) {
            $db->query('DELETE FROM workoutplan_days WHERE workoutplan_week_id = :workoutplan_week_id', array(':workoutplan_week_id' => $workoutplan_week['id']));
        }
        $db->query('DELETE FROM workoutplan_weeks WHERE workoutplan_id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id));
        $db->query('DELETE FROM workoutplans WHERE id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id));
        $db->query('DELETE FROM workoutplan_tracker WHERE workoutplan_id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id));
    } else if ($req == "/angular_workoutplan_remove_week") {
        $workoutplan_id = $args['workoutplan_id'];
        $token = $args['token'];

        $user_id = $db->query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')))[0]['user_id'];
        if (!$user_id) {
            http_response_code(401);
            echo '{"error": "Not logged in."}';
            return;
        }
        $workoutplan_week_id =  $db->query('SELECT MAX(id) FROM workoutplan_weeks WHERE workoutplan_id = :workoutplan_id', array(':workoutplan_id' => $workoutplan_id))[0][0];
        $db->query('DELETE FROM workoutplan_days WHERE workoutplan_week_id = :workoutplan_week_id', array(':workoutplan_week_id' => $workoutplan_week_id));
        $db->query('DELETE FROM workoutplan_weeks WHERE id = :workoutplan_week_id', array(':workoutplan_week_id' => $workoutplan_week_id));
    } else if ($req == "/logout") {
        $token = $args['token'];

        $db->query('DELETE FROM login_tokens WHERE token=:token', array(':token' => hash_hmac('sha512', $token, 'insert secret key')));
    }
} else {
    http_response_code(501);
    echo '[{"error": "Request not supported - '.$_SERVER['REQUEST_URI'].'"}]';
}
