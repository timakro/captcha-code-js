<?php
/*
Generate captchas that require the user to fix javascript syntax errors

Copyright (C) 2017 Tim Schumacher <tim@timakro.de>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


class CaptchaCodeJS
{
    const TYPE_INT = 0;
    const TYPE_BOOL = 1;
    const TYPE_STR = 2;
    const TYPE_IARR = 3;
    const TYPE_SARR = 3;

    private function choose_random($choices)
    {
        return $choices[mt_rand(0, count($choices) - 1)];
    }

    private function choose_random_weighted($weights)
    {
        $random = mt_rand(1, array_sum($weights));
        $total = 0;
        foreach($weights as $choice => $weight) {
            $total += $weight;
            if($total >= $random)
                return $choice;
        }
    }

    private function choose_random_sample($choices, $num)
    {
        $sample = [];
        for($i=0; $i<count($choices); $i++)
        {
            if($i < $num)
                $sample[$i] = $choices[$i];
            elseif(mt_rand(0, $i - 1) < $num)
                $sample[mt_rand(0, $num - 1)] = $choices[$i];
        }
        return $sample;
    }

    private function type_simple($type)
    {
        if($type === self::TYPE_INT)
            return strval(mt_rand(-255, 255));
        elseif($type === self::TYPE_BOOL)
            return mt_rand(0, 1) ? "true" : "false";
        elseif($type === self::TYPE_STR) {
            $text = "";
            $charnum = mt_rand(4,7);
            for($i=0; $i<$charnum; $i++) {
                $n = mt_rand(32, 94);
                if($n > 32) $n += 15;
                if($n > 57) $n += 7;
                if($n > 90) $n += 6;
                $text .= chr($n);
            }
            return "'".$text."'";
        }
    }

    private function type_arr($num, $type)
    {
        $elements = "";
        if($type === self::TYPE_IARR)     $innertype = self::TYPE_INT;
        elseif($type === self::TYPE_SARR) $innertype = self::TYPE_STR;
        for($i=0; $i<$num; $i++)
            $elements .= $this->type_simple($innertype).", ";
        return "[".substr($elements, 0, -2)."]";
    }

    private function conversion($vars, $num, $type)
    {
        $num -= 1;
        if($type === self::TYPE_INT) {
            $result = $this->choose_random_weighted([
                'len_str' => 3, 'len_iarr' => 1, 'len_sarr' => 1
            ]);
            if($result === 'len_str')
                return $this->expr($vars, $num, self::TYPE_STR).".length";
            elseif($result === 'len_iarr')
                return $this->expr($vars, $num, self::TYPE_IARR).".length";
            elseif($result === 'len_sarr')
                return $this->expr($vars, $num, self::TYPE_SARR).".length";
        }
        elseif($type === self::TYPE_BOOL) {
            return "!".$this->expr($vars, $num, self::TYPE_BOOL);
        }
        elseif($type === self::TYPE_STR) {
            $result = $this->choose_random_weighted([
                'lower' => 2, 'upper' => 2, 'iarr' => 1, 'sarr' => 1
            ]);
            if($result === 'lower')
                return $this->expr($vars, $num, self::TYPE_STR).".toLowerCase()";
            elseif($result === 'upper')
                return $this->expr($vars, $num, self::TYPE_STR).".toUpperCase()";
            elseif($result === 'iarr')
                return $this->expr($vars, $num, self::TYPE_IARR).".toString()";
            elseif($result === 'sarr')
                return $this->expr($vars, $num, self::TYPE_SARR).".toString()";
        }
        elseif($type === self::TYPE_IARR) {
            $result = $this->choose_random_weighted([
                'sort' => 1, 'reverse' => 1
            ]);
            if($result === 'sort')
                return $this->expr($vars, $num, self::TYPE_IARR).".sort()";
            elseif($result === 'reverse')
                return $this->expr($vars, $num, self::TYPE_IARR).".reverse()";
        }
        elseif($type === self::TYPE_SARR) {
            $result = $this->choose_random_weighted([
                'sort' => 1, 'reverse' => 1
            ]);
            if($result === 'sort')
                return $this->expr($vars, $num, self::TYPE_SARR).".sort()";
            elseif($result === 'reverse')
                return $this->expr($vars, $num, self::TYPE_SARR).".reverse()";
        }
    }

    private function combination($vars, $num, $type)
    {
        $num -= 1;
        $num1 = mt_rand(1, $num - 1);
        $num2 = $num - $num1;
        if($type === self::TYPE_INT) {
            $result = $this->choose_random_weighted([
                'add' => 2, 'sub' => 2, 'mul' => 2, 'div' => 2,
                'index_iarr' => 1, 'index_sarr' => 1
            ]);
            if($result === 'add')
                return "(".$this->expr($vars, $num1, self::TYPE_INT)." + ".$this->expr($vars, $num2, self::TYPE_INT).")";
            elseif($result === 'sub')
                return "(".$this->expr($vars, $num1, self::TYPE_INT)." - ".$this->expr($vars, $num2, self::TYPE_INT).")";
            elseif($result === 'mul')
                return "(".$this->expr($vars, $num1, self::TYPE_INT)." * ".$this->expr($vars, $num2, self::TYPE_INT).")";
            elseif($result === 'div')
                return "(".$this->expr($vars, $num1, self::TYPE_INT)." / ".$this->expr($vars, $num2, self::TYPE_INT).")";
            elseif($result === 'index_iarr')
                return $this->expr($vars, $num1, self::TYPE_IARR).".indexOf(".$this->expr($vars, $num2, self::TYPE_INT).")";
            elseif($result === 'index_sarr')
                return $this->expr($vars, $num1, self::TYPE_SARR).".indexOf(".$this->expr($vars, $num2, self::TYPE_STR).")";
        }
        elseif($type === self::TYPE_BOOL) {
            $result = $this->choose_random_weighted([
                'e_int' => 2, 'ne_int' => 2, 'g_int' => 2, 'l_int' => 2,
                'e_bool' => 1, 'ne_bool' => 1, 'and_bool' => 2, 'or_bool' => 2,
                'e_str' => 2, 'ne_str' => 2, 'endswith' => 2, 'startswith' => 2,
                'includes_str' => 2, 'includes_iarr' => 1, 'includes_sarr' => 1
            ]);
            if($result === 'e_int')
                return "(".$this->expr($vars, $num1, self::TYPE_INT)." === ".$this->expr($vars, $num2, self::TYPE_INT).")";
            elseif($result === 'ne_int')
                return "(".$this->expr($vars, $num1, self::TYPE_INT)." !== ".$this->expr($vars, $num2, self::TYPE_INT).")";
            elseif($result === 'g_int')
                return "(".$this->expr($vars, $num1, self::TYPE_INT)." > ".$this->expr($vars, $num2, self::TYPE_INT).")";
            elseif($result === 'l_int')
                return "(".$this->expr($vars, $num1, self::TYPE_INT)." < ".$this->expr($vars, $num2, self::TYPE_INT).")";
            elseif($result === 'e_bool')
                return "(".$this->expr($vars, $num1, self::TYPE_BOOL)." === ".$this->expr($vars, $num2, self::TYPE_BOOL).")";
            elseif($result === 'ne_bool')
                return "(".$this->expr($vars, $num1, self::TYPE_BOOL)." !== ".$this->expr($vars, $num2, self::TYPE_BOOL).")";
            elseif($result === 'and_bool')
                return "(".$this->expr($vars, $num1, self::TYPE_BOOL)." && ".$this->expr($vars, $num2, self::TYPE_BOOL).")";
            elseif($result === 'or_bool')
                return "(".$this->expr($vars, $num1, self::TYPE_BOOL)." || ".$this->expr($vars, $num2, self::TYPE_BOOL).")";
            elseif($result === 'e_str')
                return "(".$this->expr($vars, $num1, self::TYPE_STR)." === ".$this->expr($vars, $num2, self::TYPE_STR).")";
            elseif($result === 'ne_str')
                return "(".$this->expr($vars, $num1, self::TYPE_STR)." !== ".$this->expr($vars, $num2, self::TYPE_STR).")";
            elseif($result === 'endswith')
                return $this->expr($vars, $num1, self::TYPE_STR).".endsWith(".$this->expr($vars, $num2, self::TYPE_STR).")";
            elseif($result === 'startswith')
                return $this->expr($vars, $num1, self::TYPE_STR).".startsWith(".$this->expr($vars, $num2, self::TYPE_STR).")";
            elseif($result === 'includes_str')
                return $this->expr($vars, $num1, self::TYPE_STR).".includes(".$this->expr($vars, $num2, self::TYPE_STR).")";
            elseif($result === 'includes_iarr')
                return $this->expr($vars, $num1, self::TYPE_IARR).".includes(".$this->expr($vars, $num2, self::TYPE_INT).")";
            elseif($result === 'includes_sarr')
                return $this->expr($vars, $num1, self::TYPE_SARR).".includes(".$this->expr($vars, $num2, self::TYPE_STR).")";
        }
        elseif($type === self::TYPE_STR) {
            $result = $this->choose_random_weighted([
                'add_str' => 2, 'add_int' => 1, 'repeat' => 1,
                'join_iarr' => 1, 'join_sarr' => 1
            ]);
            if($result === 'add_str')
                return "(".$this->expr($vars, $num1, self::TYPE_STR)." + ".$this->expr($vars, $num2, self::TYPE_STR).")";
            elseif($result === 'add_int')
                return "(".$this->expr($vars, $num1, self::TYPE_STR)." + ".$this->expr($vars, $num2, self::TYPE_INT).")";
            elseif($result === 'repeat')
                return $this->expr($vars, $num1, self::TYPE_STR).".repeat(".$this->expr($vars, $num2, self::TYPE_INT).")";
            elseif($result === 'join_iarr')
                return $this->expr($vars, $num1, self::TYPE_IARR).".join(".$this->expr($vars, $num2, self::TYPE_STR).")";
            elseif($result === 'join_sarr')
                return $this->expr($vars, $num1, self::TYPE_SARR).".join(".$this->expr($vars, $num2, self::TYPE_STR).")";
        }
        elseif($type === self::TYPE_IARR) {
            return $this->expr($vars, $num1, self::TYPE_IARR).".concat(".$this->expr($vars, $num2, self::TYPE_IARR).")";
        }
        elseif($type === self::TYPE_SARR) {
            return $this->expr($vars, $num1, self::TYPE_SARR).".concat(".$this->expr($vars, $num2, self::TYPE_SARR).")";
        }
    }

    private function expr($vars, $num, $type)
    {
        $weights = [];
        if($num === 1 && ($type === self::TYPE_INT || $type === self::TYPE_BOOL || $type === self::TYPE_STR))
            $weights['type_simple'] = 1;
        if($num >= 1 && $num <= 3 && ($type === self::TYPE_IARR || $type === self::TYPE_SARR))
            $weights['type_arr'] = 1;
        if($num === 1 && in_array($type, $vars))
            $weights['variable'] = 4;
        if($num >= 2)
            $weights['conversion'] = 2;
        if($num >= 3)
            $weights['combination'] = 2;
        $result = $this->choose_random_weighted($weights);
        if($result === 'type_simple')
            return $this->type_simple($type);
        elseif($result === 'type_arr')
            return $this->type_arr($num, $type);
        elseif($result === 'variable') {
            $names = [];
            foreach($vars as $varname => $vartype)
                if($vartype === $type)
                    array_push($names, $varname);
            return $this->choose_random($names);
        }
        elseif($result === 'conversion')
            return $this->conversion($vars, $num, $type);
        elseif($result === 'combination')
            return $this->combination($vars, $num, $type);
    }

    private function var_name($vars, $short = false)
    {
        $text = null;
        while($text === null || in_array($text, array_keys($vars))) {
            $text = "";
            $charnum = $short ? mt_rand(1,2) : mt_rand(3,5);
            for($i=0; $i<$charnum; $i++) {
                $n = mt_rand(95, 121);
                if($n > 95) $n += 1;
                $text .= chr($n);
            }
        }
        return $text;
    }

    private function modify_var($vars, $type)
    {
        if($type === self::TYPE_INT) {
            $result = $this->choose_random_weighted([
                'set' => 1, 'add' => 2, 'sub' => 2, 'mul' => 2, 'div' => 2,
                'add1' => 1, 'sub1' => 1
            ]);
            if($result === 'set')
                return " = ".$this->expr($vars, 4, self::TYPE_INT);
            elseif($result === 'add')
                return " += ".$this->expr($vars, 3, self::TYPE_INT);
            elseif($result === 'sub')
                return " -= ".$this->expr($vars, 3, self::TYPE_INT);
            elseif($result === 'mul')
                return " *= ".$this->expr($vars, 3, self::TYPE_INT);
            elseif($result === 'div')
                return " /= ".$this->expr($vars, 3, self::TYPE_INT);
            elseif($result === 'add1')
                return "++";
            elseif($result === 'sub1')
                return "--";
        }
        elseif($type === self::TYPE_BOOL) {
            return " = ".$this->expr($vars, 4, self::TYPE_BOOL);
        }
        elseif($type === self::TYPE_STR) {
            $result = $this->choose_random_weighted([
                'set' => 1, 'add_str' => 3, 'add_int' => 2
            ]);
            if($result === 'set')
                return " = ".$this->expr($vars, 4, self::TYPE_STR);
            elseif($result === 'add_str')
                return " += ".$this->expr($vars, 3, self::TYPE_STR);
            elseif($result === 'add_int')
                return " += ".$this->expr($vars, 3, self::TYPE_INT);
        }
        elseif($type === self::TYPE_IARR) {
            $result = $this->choose_random_weighted([
                'set' => 1, 'sort' => 2, 'reverse' => 2, 'push' => 2
            ]);
            if($result === 'set')
                return " = ".$this->expr($vars, 4, self::TYPE_IARR);
            elseif($result === 'sort')
                return ".sort()";
            elseif($result === 'reverse')
                return ".reverse()";
            elseif($result === 'push')
                return ".reverse(".$this->expr($vars, 3, self::TYPE_INT).")";
        }
        elseif($type === self::TYPE_SARR) {
            $result = $this->choose_random_weighted([
                'set' => 1, 'sort' => 2, 'reverse' => 2, 'push' => 2
            ]);
            if($result === 'set')
                return " = ".$this->expr($vars, 4, self::TYPE_SARR);
            elseif($result === 'sort')
                return ".sort()";
            elseif($result === 'reverse')
                return ".reverse()";
            elseif($result === 'push')
                return ".reverse(".$this->expr($vars, 3, self::TYPE_STR).")";
        }
    }

    private function statement($vars, $num, $indent)
    {
        $code = "";
        $weights = [];
        if(in_array(self::TYPE_INT, $vars))
            $weights['for'] = 1;
        if(in_array(self::TYPE_BOOL, $vars)) {
            $weights['if'] = 1;
        }
        if(in_array(self::TYPE_STR, $vars) || in_array(self::TYPE_IARR, $vars) || in_array(self::TYPE_SARR, $vars))
            $weights['forof'] = 1;
        $result = $this->choose_random_weighted($weights);
        if($result === 'for') {
            $name = $this->var_name($vars, true);
            $increase = mt_rand(0, 1);
            $comp = $increase ? "<" : ">";
            $change = $increase ? "++" : "--";
            $code .= str_repeat($this->indent_str, $indent)."for(let $name=".$this->expr($vars, 2, self::TYPE_INT)."; $name$comp".$this->expr($vars, 2, self::TYPE_INT)."; $name$change)";
            $vars[$name] = self::TYPE_INT;
        }
        elseif($result === 'if')
            $code .= str_repeat($this->indent_str, $indent)."if(".$this->expr($vars, 3, self::TYPE_BOOL).")";
        elseif($result === 'forof') {
            $name = $this->var_name($vars, true);
            $type = mt_rand(self::TYPE_STR, self::TYPE_SARR);
            $innertype = $type;
            if($type === self::TYPE_IARR)     $innertype = self::TYPE_INT;
            elseif($type === self::TYPE_SARR) $innertype = self::TYPE_STR;
            $code .= str_repeat($this->indent_str, $indent)."for(let $name of ".$this->expr($vars, 3, $type).")";
            $vars[$name] = $innertype;
        }
        $code .= " {\n".$this->code($vars, $num, $indent + 1).str_repeat($this->indent_str, $indent)."}\n";
        return $code;
    }

    private function code($vars, $num, $indent)
    {
        $code = "";
        while($num > 0)
        {
            $weights = [];
            if(count($vars) <= 2)
                $weights['define_var'] = 1;
            if(!empty($vars))
                $weights['modify_var'] = 1;
            if($num >= 2 && !empty($vars))
                $weights['statement'] = 2;
            $result = $this->choose_random_weighted($weights);
            $num -= 1;
            if($result === 'define_var') {
                $name = $this->var_name($vars);
                $type = mt_rand(self::TYPE_INT, self::TYPE_SARR);
                $code .= str_repeat($this->indent_str, $indent)."let $name = ".$this->expr($vars, 5, $type).";\n";
                $vars[$name] = $type;
            }
            elseif($result === 'modify_var') {
                $name = $this->choose_random(array_keys($vars));
                $code .= str_repeat($this->indent_str, $indent).$name.$this->modify_var($vars, $vars[$name]).";\n";
            }
            elseif($result === 'statement') {
                $innernum = mt_rand(1, min($num, 3));
                $num -= $innernum;
                $code .= $this->statement($vars, $innernum, $indent);
            }
        }
        return $code;
    }

    function generate_new()
    {
        if($this->ran_seed !== null)
            mt_srand($this->ran_seed);
        $code = $this->code([], $this->lines, 0);
        $indexes = [];
        for($i=0; $i<strlen($code); $i++)
            if(in_array($code[$i], $this->del_chars))
                array_push($indexes, $i);
        $del_num = mt_rand($this->del_min, $this->del_max);
        $deleting = $this->choose_random_sample($indexes, $del_num);
        rsort($deleting);
        $solution = "";
        foreach($deleting as $d) {
            $solution = $code[$d].$solution;
            $code = substr_replace($code, "", $d, 1);
        }
        $question = $code."
There are $del_num characters missing in this JS code,
enter them in the order they are missing.";
        $this->question = $question;
        $this->solution = $solution;
    }

    function __construct(
            $lines = 5, $indent_str = "  ", $del_min = 4, $del_max = 5,
            $del_chars = ["{", "}", ".", "'", ";", ","], $ran_seed = null)
    {
        $this->lines = $lines;
        $this->indent_str = $indent_str;
        $this->del_min = $del_min;
        $this->del_max = $del_max;
        $this->del_chars = $del_chars;
        $this->ran_seed = $ran_seed;

        $this->generate_new();
    }
}

?>
