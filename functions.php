<?php
define('IMG_DIR', "/Users/robzeeman/Documents/DI/isidore/images/target/");

$db = new db();
$files = scandir(IMG_DIR);


function formDate($d, $m, $y)
{
    if ($d < 1 || $d > 31) {
        $d = "??";
    } else {
        $d = str_pad($d, 2, "0", STR_PAD_LEFT);
    }
    if ($m < 1 || $m > 12) {
        $m = "??";
    } else {
        $m = str_pad($m, 2, "0", STR_PAD_LEFT);
    }
    if ($y < 500) {
        $y = "????";
    }
    return "$d-$m-$y";
}

function put_mapping()
{
    $mapping = file_get_contents(MAPPING_FILE);
    $ch = curl_init();
    $options = array('Content-type: application/json', 'Content-Length: ' . strlen($mapping));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $options);
    curl_setopt($ch, CURLOPT_URL, MAPPING_URL);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $mapping);
    curl_exec($ch);
    echo "Index mapping sent.\n";
}


function publish($passage, $url)
{
    $json_struc = json_encode($passage);
    $options = array('Content-type: application/json', 'Content-Length: ' . strlen($json_struc));
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $options);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_struc);
    //curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    echo $response;
    curl_close($ch);
    //echo "$id indexed\n";
}

function indexManuscripts($count)
{
    global $db;
    put_mapping();
    $manuscripts = $db->get_manuscripts($count);
    foreach ($manuscripts as $manuscript) {
        $item = build_item($manuscript);
        publish($item, INDEX_URL);
        //print_r($item);
    }
}

function build_item($manuscript)
{
    global $db;
    global $files;

    $item = $manuscript;
    $id = $manuscript["id"];
    $item["books"] = $db->get_books($id, $manuscript["material_type"]);
    $item["books_latin"] = $db->get_books_latin($id);
    $item["script"] = $db->get_script($id);
    $item["library"] = $db->get_library($id);
    $item["content_type"] = $db->get_content_type($id);
    $item["authors"] = $db->get_authors($id);
    $item["absolute_places"] = $db->get_absolute_places($id);
    $item["scaled_places"] = $db->get_scaled_places($id);
    $item["scaled_dates"] = simplify_dates($db->get_scaled_dates($id));
    $item["designed_as"] = $db->get_designed_as($id);
    $item["page_dimensions"] = $db->get_page_dimensions($id);
    //$item["physical_state"] = $db->get_physical_state($id);
    $item["physical_state"] = $manuscript["physical_state"];
    $item["layout"] = get_layout($manuscript["columns"]);
    //$item["provenance"] = process_str($manuscript["provenance"]);
    $item["provenances"] = $db->get_provenances($id);
    //$item["material_type"] = process_str($manuscript["material_type"]);
    $item["certainty"] = $db->get_certainty($id);
    $item["material_types"] = $db->get_material_types($id);
    $item["has_innovations"] = getYN($manuscript["innovations"]);
    // Relations
    $aantal = $db->getRelAmount($id);
    if ($aantal == 0) {
        $item["has_relations"] = 'no';
    } else {
        $item["has_relations"] = 'yes';
    }

    // Fragment (y/n)
    $item["is_fragment"] = getFragment($manuscript["physical_state"]);
    // Diagrams
    $values = $db->getDiagrams($id);
    if (isset($values[0]["diagram_type"])) {
        $item["has_diagrams"] = getYN($values[0]["diagram_type"]);
        $item["diagrams"] = $values;
    } else {
        $item["has_diagrams"] = "no";
        $item["diagrams"] = array();
    }
    // Easter tables
    $values = $db->getEasterTables($id);
    if (isset($values[0]["easter_table_type"])) {
        $item["has_easter_tables"] = getYN($values[0]["easter_table_type"]);
        $item["easter_tables"] = $values;
    } else {
        $item["has_easter_tables"] = "no";
        $item["easter_tables"] = array();
    }
    // Annotations
    $values = $db->getAnnotations($id);
    if (isset($values[0]["amount"])) {
        $item["has_annotations"] = getYN($values[0]["amount"]);
        $item["annotations"] = $values;
    } else {
        $item["has_annotations"] = "no";
        $item["annotations"] = array();
    }

    // Interpolations
    $values = $db->getInterpolations($id);
    if (isset($values[0]["interpolation"])) {
        $item["has_interpolations"] = getYN($values[0]["interpolation"]);
        $item["interpolations"] = $values;
    } else {
        $item["has_interpolations"] = "no";
        $item["interpolations"] = array();
    }


    $item["sources_of_dating"] = $db->get_sources_of_dating($id);
    if (substr($manuscript["material_type"], 0, 4) == "full") {
        $item["excluded"] = "no";
    } else {
        $item["excluded"] = "yes";
    }

    $item["part"] = is_available($manuscript["collection_larger_unit"]);
    $item["digitized"] = is_digitized($id);
    $item["current_places"] = $db->get_current_places($id);
    if (in_array($id . ".jpg", $files)) {
        $item["image"] = $id . ".jpg";
    } else {
        $item["image"] = "no_image.jpg";
    }
    //print_r($item);
    return $item;
}

function getYN($str) {
    if (strlen(trim($str)) > 0) {
        return "yes";
    } else {
        return "no";
    }
}

function getFragment($value) {
    if (strtolower($value) == "fragment") {
        return "yes";
    } else {
        return "no";
    }
}


function simplify_dates($results) {
    $retArray = array();
    foreach ($results as $result) {
        $buffer = array();
        $buffer["date"] = oneCentury($result["date"]);
        $tmp = dateRanges($result["numerical_date"]);
        if (count($tmp) == 2) {
            $buffer["lower"] = $tmp["lower"];
            $buffer["upper"] = $tmp["upper"];
        } else {
            $buffer["lower"] = 0;
            $buffer["upper"] = 0;
        }
        $retArray[] = $buffer;
    }
    return $retArray;
}

function dateRanges($date) {
    $parts = explode("-", $date);
    if (count($parts) == 2) {
        if (is_numeric($parts[0]) && is_numeric($parts[1])) {
            return array("lower" => $parts[0], "upper" => $parts[1]);
        } else {
            return array();
        }
    } else {
        return array();
    }
}

function oneCentury($date) {
    $part = substr($date, 0, 4);
    switch($part) {
        case "7th ":
            return " 7th century";
        case "8th ":
            return " 8th century";
        case "9th ":
            return " 9th century";
        case "10th":
            return "10th century";
        case "11th":
            return "11th century";
        default:
            return "Unknown";
    }
}

function is_digitized($id) {
    global $db;

   return $db->getAmountDigitized($id);
}

function is_available($str) {
    if (is_null($str) || $str == "") {
        return "no";
    } else {
        return "yes";
    }
}

function is_image($str) {
    if (is_null($str) || $str == "") {
        return false;
    } else {
        return true;
    }
}

function process_str($str) {
    if ($str == "" || is_null($str)) {
        return "Unknown";
    } else {
        return $str;
    }
}

function get_layout($value) {
    $retValue = "";
    switch($value) {
        case "1":
            $retValue = "one column";
            break;
        case "2":
            $retValue = "two columns";
            break;
        case "3":
            $retValue = "three columns";
            break;
        case "4":
            $retValue = "four columns";
            break;
        default:
            $retValue = "unknown";
            break;
    }
    return $retValue;
}


