<?php

class db
{
    var $con;

    function __construct()
    {
        $this->con = pg_connect("host=localhost port=5432 dbname=isidore user=robzeeman password=bonzo");
    }

    function get_manuscripts($count)
    {
        $result = pg_query($this->con, "SELECT id, shelfmark, bischoff, cla, material_type, no_of_folia, accepted_date, page_height_min, page_width_min, additional_observations, additional_content_scaled, physical_state_scaled physical_state, columns,  REPLACE (innovations, '&amp;', '&') as innovations, collection_larger_unit FROM manuscripts LIMIT $count");
        return $this->ass_arr($result);
    }

    function get_book_section($id)
    {
        $result = pg_query($this->con, "SELECT * FROM manuscripts");
        return $this->ass_arr($result);
    }

    function get_physical_state($id) {
        $result = $this->ass_arr(pg_query($this->con, "SELECT * FROM physical_state WHERE m_id = '$id'"));
        if (count($result)) {
            if (trim($result[0]["physical_state"]) == "") {
                return "fully preserved";
            } else {
                return $result[0]["physical_state"];
            }
        } else {
            //return "unknown";
            return "fully preserved";
        }
    }

    function get_current_places($id) {
        $result = pg_query($this->con, "SELECT place_name place FROM manuscripts m, manuscript_current_places ml, library l WHERE m.id ='$id' AND m.id = ml.m_id AND ml.lib_id = l.lib_id");
        return $this->ass_arr($result);
    }

    function get_books($id, $material_type)
    {
        $books = $this->get_book_list($id);
        $result = pg_query($this->con, "SELECT details FROM manuscripts_details_locations WHERE m_id = '$id' AND details NOT LIKE '%+%'");
        return $this->process_books($this->ass_arr($result), $books, $id);
    }

    function get_books_latin($id) {
        $retArray = array();
        $result = pg_query($this->con, "SELECT roman FROM manuscripts_books_included mb, books b WHERE mb.m_id = '$id' AND mb.b_id = b.id ORDER BY b.id");
        $books = $this->ass_arr($result);
        foreach ($books as $book) {
            $retArray[] = $book["roman"];
        }
        if (!count($retArray)) {
            return "Unknown";
        } else {
            return implode(", ", $retArray);
        }
    }

    function getRelAmount($id) {
        $result = $this->ass_arr(pg_query($this->con, "select count(*) as aantal from relationships where m_id = '$id'"));
        return $result[0]["aantal"];
    }

    function getAmountDigitized($id) {
        $result = $this->ass_arr(pg_query($this->con, "select count(*) as aantal from url where m_id = '$id' AND url_images <> ''"));
        if ($result[0]["aantal"] > 0) {
            return "yes";
        } else {
            return "no";
        }
    }

    function get_script($id)
    {
        $result = pg_query($this->con, "SELECT s.script FROM manuscripts m, manuscripts_scripts ms, scripts s WHERE m.id = '$id' AND m.id = ms.m_id AND ms.script_id = s.script_id ");
        $script = $this->ass_arr($result);
        if (count($script)) {
            return $script[0]["script"];
        } else {
            return "Unknown";
        }
    }

    function get_library($id)
    {
        $result = pg_query($this->con, "SELECT lib_name, latitude, longitude, place_name FROM manuscripts m, manuscript_current_places ml, library l WHERE m.id ='$id' AND m.id = ml.m_id AND ml.lib_id = l.lib_id");
        return $this->ass_arr($result);
    }

    function get_content_type($id)
    {
        $result = pg_query($this->con, "select c.content_type FROM manuscripts m, manuscripts_content_types mc, content_types c WHERE m.id = '$id' AND m.id = mc.m_id AND mc.type_id = c.type_id");
        $type = $this->ass_arr($result);
        if (count($type)) {
            return $type[0]["content_type"];
        } else {
            return "Unknown";
        }
    }

    function get_authors($id)
    {
        $result = pg_query($this->con, "SELECT DISTINCT CASE WHEN full_name_2 = '' THEN full_name_1 ELSE full_name_2 END AS author FROM manuscripts_viaf WHERE id = '$id'");
        return $this->ass_arr($result);
    }

    function get_absolute_places($id)
    {
        $result = pg_query($this->con, "SELECT place_absolute, country, latitude, longitude, certainty FROM manuscripts_absolute_places m, absolute_places ap WHERE m_id = '$id' AND m.place_id = ap.place_id");
        return $this->ass_arr($result);
    }

    function get_certainty($id) {
        $result = pg_query($this->con, "SELECT certainty FROM manuscripts_absolute_places WHERE m_id = '$id'");
        $values = $this->ass_arr($result);
        if (count($values)) {
            return $values[0]["certainty"];
        } else {
            return "";
        }
    }

    function get_scaled_places($id)
    {
        $result = pg_query($this->con, "SELECT place, latitude, longitude FROM manuscripts_scaled_places m, scaled_places ap WHERE m_id = '$id' AND m.place_id = ap.place_id");
        return $this->ass_arr($result);
    }

    function get_provenances($id)
    {
        $result = pg_query($this->con, "SELECT provenance FROM manuscripts_provenance_scaled m, provenance_scaled ap WHERE m_id = '$id' AND m.p_id = ap.p_id");
        return $this->ass_arr($result);
    }

    function get_sources_of_dating($id)
    {
        $result = pg_query($this->con, "SELECT source FROM manuscripts_source_of_dating m, source_of_dating ap WHERE m_id = '$id' AND m.s_id = ap.s_id");
        return $this->ass_arr($result);
    }

    function get_material_types($id)
    {
        $result = pg_query($this->con, "SELECT DISTINCT material_type FROM manuscripts_details_locations WHERE m_id = '$id'");
        return $this->ass_arr($result);
    }



    function get_scaled_dates($id)
    {
        $result = pg_query($this->con, "select date, CONCAT(lower_date, '-', upper_date) AS numerical_date from manuscripts_scaled_dates msd, scaled_dates sd where msd.m_id = '$id' AND msd.date_id = sd.date_id");
        return $this->ass_arr($result);
    }

    function getDiagrams($id) {
        $result = pg_query($this->con, "SELECT diagram_type FROM diagrams WHERE m_id = '$id'");
        return $this->ass_arr($result);
    }

    function getEasterTables($id) {
        $result = pg_query($this->con, "SELECT easter_table_type FROM easter_table WHERE m_id = '$id'");
        return $this->ass_arr($result);
    }

    function getAnnotations($id) {
        $result = pg_query($this->con, "SELECT amount, language FROM annotations WHERE m_id = '$id'");
        return $this->ass_arr($result);
    }

    function getRelations($id) {
        $result = pg_query($this->con, "SELECT DISTINCT reason FROM relationships WHERE m_id = '$id'");
        return $this->ass_arr($result);
    }

    function getInterpolations($id) {
        $retArray = array();
        $result = $this->ass_arr(pg_query($this->con, "SELECT interpolation FROM interpolations WHERE m_id = '$id'"));
        foreach ($result as $item) {
            if (strlen($item["interpolation"]) > 40) {
                $retArray[] = array("interpolation" => substr($item["interpolation"], 0, 40) . "...");
            } else {
                $retArray[] = $item;
            }
        }
        return $retArray;
    }

    function get_page_dimensions($id)
    {
        $result = pg_query($this->con, "SELECT page_height_min, page_width_min FROM manuscripts WHERE id='$id'");
        $dims = $this->ass_arr($result);
        $dims = $dims[0];
        if (is_numeric($dims["page_height_min"]) && is_numeric($dims["page_width_min"])) {
            $format = $dims["page_height_min"] + $dims["page_width_min"];
            if ($format < 300) {
                return "< 300 mm";
            } else {
                if ($format < 351) {
                    return "300-350 mm";
                } else {
                    if ($format < 401) {
                        return "351-400 mm";
                    } else {
                        if ($format < 451) {
                            return "401-450 mm";
                        } else {
                            if ($format < 501) {
                                return "451-500 mm";
                            } else {
                                if ($format < 551) {
                                    return "501-550 mm";
                                } else {
                                    if ($format < 601) {
                                        return "551-600 mm";
                                    } else {
                                        if ($format < 651) {
                                            return "601-650 mm";
                                        } else {
                                            return "> 651 mm";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        else {
                return "Unknown";
            }
        }

        function get_designed_as($id)
        {
            $result = pg_query($this->con, "select designed_as from manuscripts_designed_as md, designed_as d where md.m_id = '$id' AND md.design_id = d.design_id");
            $arr = $this->ass_arr($result);
            if (isset($arr[0]["designed_as"])) {
                return $arr[0]["designed_as"];
            } else {
                return "unknown";
            }

        }

        function get_xx($id)
        {
            $result = pg_query($this->con, "");
            return $this->ass_arr($result);
        }

        private
        function get_book_list($id)
        {
            $retArray = array();
            $result = pg_query($this->con, "SELECT b_id FROM manuscripts_books_included WHERE m_id = '$id'");
            $result = $this->ass_arr($result);
            foreach ($result as $item) {
                $retArray[] = $item["b_id"];
            }
            return $retArray;
        }

        private
        function process_books($list, $books, $id)
        {
            $selection = array();
            $tempArray = array();
            $retArray = array();
            foreach ($list as $item) {
                $item["details"] = str_replace("(", "", $item["details"]);
                $item["details"] = str_replace(")", "", $item["details"]);
                $parts = explode("-", $item["details"]);
                if (count($parts) == 1) {
                    $this->process_one($item["details"], $selection);
                } else {
                    $this->process_two($item["details"], $selection, $books);
                }

            }
            $retArray["details"] = array($this->convert2ranges($selection));
            //$retArray["details"] = $selection;
            return $retArray;
        }

        private
        function convert2ranges($items)
        {
            $retArray = array();
            foreach ($items as $item) {
                $parts = explode(".", $item);
                if (isset($parts[0]) && isset($parts[1]) && is_numeric($parts[0]) && is_numeric($parts[1])) {
                    $retArray[] = array("section" => ($parts[0] * 1000) + $parts[1]);
                }
            }
            return $retArray;
        }

        private
        function process_one($item, &$selection)
        {
            $parts = explode(".", $item);
            if (count($parts) > 1) {
                $element = $parts[0] . "." . $parts[1];
                if (!in_array($element, $selection)) {
                    $selection[] = $element;
                }
            }

        }

        private
        function process_two($item, &$selection, $books)
        {
            $parts = explode("-", $item);
            $part1 = explode(".", $parts[0]);
            $part2 = explode(".", $parts[1]);
            $condition = (count($part1) * 10) + count($part2);

            switch ($condition) {
                case 21:
                    $this->one_book_more_sections($item, $selection);
                    break;
                case 22:
                    $this->book_range($item, $selection, $books);
                    break;
                case 23:
                    $newItem = implode('.', $part1) . '-' . $part2[0] . "." . $part2[1];
                    $this->book_range($newItem, $selection, $books);
                case 31:
                    $this->process_one($item, $selection);
                    break;
                case 32:
                    $this->convert2book_range($item, $selection, $books);
                    break;
                case 33:
                    $newItem = $part1[0] . "." . $part1[1] . '-' . $part2[0] . "." . $part2[1];
                    $this->book_range($newItem, $selection, $books);
                    break;
            }
        }

        private
        function book_range($item, &$selection, $books)
        {
            $parts = explode("-", $item);
            $part1 = explode(".", $parts[0]);
            $part2 = explode(".", $parts[1]);

            if (in_array($part2[0], $books)) {
                $this->spread_books_sections($part1[0], $part1[1], $part2[0], $part2[1], $selection);
            } else {
                $new_item = implode('.', $part1) . '-' . $part2[0];
                $this->one_book_more_sections($new_item, $selection);
            }
        }

        private
        function convert2book_range($item, &$selection, $books)
        {
            $parts = explode("-", $item);
            $part1 = explode(".", $parts[0]);
            $part2 = explode(".", $parts[1]);

            if ($part1[0] > $part2[0] || !in_array($part2[0], $books)) {
                $item = $part1[0] . '.' . $part1[1] . '-' . $part2[0];
                $this->one_book_more_sections($item, $selection);
            } else {
                if ($this->is_in_book_range($part1[0], $part2[0], $books)) {
                    $this->spread_books_sections($part1[0], $part1[1], $part2[0], $part2[1], $selection);
                } else {
                    $new_item = implode('.', $part1) . '-' . $part2[0];
                    $this->one_book_more_sections($new_item, $selection);
                }
            }
        }

        private
        function is_in_book_range($book, $bookCandidate, $books)
        {
            for ($i = $book; $i <= $bookCandidate; $i++) {
                if (!in_array($i, $books)) {
                    return false;
                }
            }
            return true;
        }

        private
        function spread_books_sections($book1, $section1, $book2, $section2, &$selection)
        {
            if ($book1 < $book2) {
                for ($currentBook = $book1; $currentBook <= $book2; $currentBook++) {
                    switch ($currentBook) {
                        case $book1:
                            $this->start_range($currentBook, $section1, $selection);
                            break;
                        case $book2:
                            $this->end_range($currentBook, $section2, $selection);
                            break;
                        default:
                            $this->complete_range($currentBook, $selection);
                    }
                }
            } else {
                $new_item = $book1 . "." . $section1 . "-" . $section2;
                $this->one_book_more_sections($new_item, $selection);
            }
        }

        private
        function start_range($book, $from, &$selection)
        {
            $to = $this->get_sections_from_book($book);
            for ($i = $from; $i <= $to; $i++) {
                $selection[] = $book . "." . $i;
            }
        }

        private
        function complete_range($book, &$selection)
        {
            $to = $this->get_sections_from_book($book);
            for ($i = 1; $i <= $to; $i++) {
                $selection[] = $book . "." . $i;
            }
        }

        private
        function end_range($book, $to, &$selection)
        {
            for ($i = 1; $i <= $to; $i++) {
                $selection[] = $book . "." . $i;
            }
        }

        private
        function get_sections_from_book($book)
        {
            $result = pg_query($this->con, "SELECT sections FROM books WHERE id = $book");
            $result = $this->ass_arr($result);
            if (count($result)) {
                return $result[0]["sections"];
            } else {
                return 0;
            }
        }

        private
        function one_book_more_sections($item, &$selection)
        {
            $parts = explode(".", $item);
            $book = $parts[0];
            $range = explode("-", $parts[1]);

            if (is_numeric($range[0]) && is_numeric($range[1])) {
                $from = intval($range[0]);
                $to = intval($range[1]);
                for ($i = $from; $i <= $to; $i++) {
                    $element = $book . "." . $i;
                    if (!in_array($element, $selection)) {
                        $selection[] = $element;
                    }
                }
            } else {
                echo "Fout!\n";
                echo "$item\n";
            }
        }

        private
        function ass_arr($results)
        {
            $retArray = array();
            while ($row = pg_fetch_assoc($results)) {
                $retArray[] = $row;
            }
            return $retArray;
        }
    }