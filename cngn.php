<?php

    //namespace CNGN;

    class CNGN {

        public $FO = [];
        public $sigma = [];
        public $condition = "";
        public $results = [];
        public $messages = [];
        public $f = "";
        public $g = "";
        public $vars;

        function __construct($index_cnt)
        {
            $this->messages[] = "Error: " ;
            $this->register_vars($index_cnt);
        }

        public function string_replace($replacements, $template) {
            return preg_replace_callback('/{(.+?)}/',
                     function($matches) use ($replacements) {
                return $replacements[$matches[1]];
            }, $template);
        }

        public function load_vars(array $placements) : void
        {
            foreach ($placements as $k => $v)
            {
                $hex = 'x' . dechex($k);
                $this->vars[$hex] = $v;
            }
            return;
        }

        public function register_vars($index_cnt)
        {
            $x = 0;
            while ($x < $index_cnt)
            {
                $hex = 'x' . dechex($x);
                $this->vars[$hex] = false;
                $x++;
            }
        }

        public function add_vars(int $index_cnt)
        {
            $x = count($this->vars);
            $s = $x;
            do
            {
                $hex = 'x' . dechex($s);
                $this->vars[$hex] = false;
                $s++;
            } while ($s < $x + $index_cnt);
        }

        /*
        *
        * Parse string of {xFA} x-hex values
        * and replace with $vars values 
        * 
        */
        public function stringParse(string $string)
        {
            if ($string == "")
            {
                $this->msg(0, 'Empty string given, try string_parse(string)\n\tUse a valid {x00} to place the variable\n\tThese are keys in $vars');
                return false;
            }
            $x = 0;
            while (strpos($string, "{x") !== false)
            {
                $string = $this->string_replace($this->vars, $string);
                $x++;
            }
            return $string;
        }

        /*
        *
        * Echo message at $msg_id
        * 
        */
        public function msg(int $msg_id, string $arb_msg = "")
        {
            echo $this->messages[$msg_id] . $arb_msg;
            return;
        }

        /**
         * use a space between each binary command
         */
        public function _n(string $j, array $sequence)
        {
            $j = explode(" ", $j);
            
            while (count($j) > 0)
            {
                //echo $j[0] . " ";
                $t = $j[0];
                array_shift($j);
                if ($t == "00000")   // s1 * s2 
                {
                    $this->sigma[] = $this->sum_rule($sequence[0]);
                    array_shift($sequence);
                    continue;
                }
                else if ($t == "00001")   // s1 - s2
                {
                    $this->sigma[] = $this->diff_rule($sequence[0]);
                    array_shift($sequence);
                    continue;
                }
                else if ($t == "00010" && sizeof($sequence) >= 2)   // s1 ^ s2
                    $this->sigma[] = $this->power_rule(array_slice($sequence,0,2));
                else if ($t == "00011")   // s1 * s2
                {
                    $this->sigma[] = $this->product_rule($sequence[0]);
                    array_shift($sequence);
                    continue;
                }
                else if ($t == "00100")   // s1 / s2
                {
                    $this->sigma[] = $this->quotient_rule($sequence[0]);
                    array_shift($sequence);
                    continue;
                }
                else if ($t == "00101")   // s1 * s2
                {
                    $this->sigma[] = $this->chain_rule($sequence[0]);
                    array_shift($sequence);
                    continue;
                }
                else if ($t == "00110")   // ^2
                    $this->sigma[] = pow($sequence[0], $sequence[1]);
                else if ($t == "00111")   // s1 + s2
                    $this->sigma[] = (float)($sequence[0] + $sequence[1]);
                else if ($t == "01000")   // s1 - s2
                    $this->sigma[] = (float)($sequence[0] - $sequence[1]);
                else if ($t == "01001")   // s1 * s2
                    $this->sigma[] = (float)($sequence[0] * $sequence[1]);
                else if ($t == "01010" && $sequence[1] != 0)   // s1 / s2
                    $this->sigma[] = (float)($sequence[0] / $sequence[1]);
                else if ($t == "01011")   // s1 > s2
                    $this->condition .= ($sequence[0] > $sequence[1]);
                else if ($t == "01100")   // s1 < s2
                    $this->condition .= ($sequence[0] < $sequence[1]);
                else if ($t == "01101")   // s1 * s2
                    $this->condition .= ($sequence[0] >= $sequence[1]);
                else if ($t == "01110")   // s1 >= s2
                    $this->condition .= ($sequence[0] <= $sequence[1]);
                else if ($t == "01111")   // s1 != s2
                    $this->condition .= ($sequence[0] != $sequence[1]);
                else if ($t == "10000")   // s1 != s2
                    $this->condition .= ($sequence[0] == $sequence[1]);
                else if ($t == "10001")   // s1 && s2
                    $this->condition .= ((bool)substr($this->condition,-1) && $sequence[0] == $sequence[1]);
                else if ($t == "10010")   // s1 || s2
                    $this->condition .= ((bool)substr($this->condition,-1) || $sequence[0] == $sequence[1]);
                else if ($t == "10011")   // s1 ^ s2
                    $this->condition .= ((bool)substr($this->condition,-1) ^ $sequence[0] == $sequence[1]);
                array_shift($sequence);
                array_shift($sequence);
            }
        }

        /*
        *
        * get function of g() -- Use {x} wherever you need your variable
        * 
        */
        public function get_f_of(float $x)
        {
            if ($this->f == "")
            {
                $this->msg(0, "No function given, try set_f_of(string x)\n\tUse {x} to place the variable.");
                exit(0);
            }
            $arry = array("x" => $x);
            $v = ($this->string_replace($arry, $this->f));
            return eval("return $v;");
        }

        /*
        *
        * set function of f() -- Use {x} wherever you need your variable
        * 
        */
        public function set_f_of(string $ev)
        {
            $this->f = $ev;
        }

        /*
        *
        * get function of g() -- Use {x} wherever you need your variable
        * 
        */
        public function get_g_of(float $x)
        {
            if ($this->g == "")
            {
                $this->msg(0, "No function given, try set_g_of(string x)\n\tUse {x} to place the variable");
                exit(0);
            }
            $arry = array("x" => $x);
            $v = ($this->string_replace($arry, $this->g));

            return eval("return $v;");
        }

        /*
        *
        * set function of g()
        * 
        */
        public function set_g_of(string $ev)
        {
            $this->g = $ev;
        }

        /*
        *
        * Condition d/dx [f(x)+g(x)]
        * 
        */
        public function sum_rule(int $sequence)
        {
            $tmp1 = $this->get_f_of((float)$sequence);
            $tmp2 = $this->get_g_of((float)$sequence);

            return $tmp1 + $tmp2;
        }

        /*
        *
        * Condition d/dx [f(x)-g(x)]
        * 
        */
        public function diff_rule(int $sequence)
        {
            $tmp1 = $this->get_f_of((float)$sequence);
            $tmp2 = $this->get_g_of((float)$sequence);
            
            return $tmp1 - $tmp2;
        }

        /*
        *
        * Condition d/dx [x^n]
        * 
        */
        public function power_rule(array $sequence)
        {
            $tmp = $sequence;

            return (float)(pow((int)$tmp[0],(int)$tmp[1]-1) * (float)$tmp[1]);
        }

        /*
        *
        * Condition d/dx [f(x)g(x)]
        * 
        */
        public function product_rule(float $sequence)
        {

            // f'(x)                // f(x)
            $tmp_f = $this->get_f_of((float)$sequence);
            // g'(x)                // g(x)
            $tmp_g = $this->get_g_of((float)$sequence);
            
            $tmp_ff = $this->get_f_of((float)$tmp_f);
            $tmp_gg = $this->get_g_of((float)$tmp_g);
            $final1a = $tmp_ff * $tmp_g;
            $final1b = $tmp_f * $tmp_gg;
            return $final1b + $final1a;
        }

        /*
        *
        * Condition d/dx [f(g(x))]
        * 
        */
        public function chain_rule(int $sequence)
        {
            
            // g'(x)                // g(x)
            $tmp_g = (float)($this->get_g_of($sequence));
            
            // f'(x)                // f(x)
            $tmp_f = (float)($this->get_f_of($tmp_g));

            $tmp_ff = ($this->get_f_of($tmp_f));
            $tmp_gg = ($this->get_g_of($tmp_f));

            return $tmp_ff * $tmp_gg;
        }

        /*
        *
        * Condition d/dx [f(x)/g(x)]
        * 
        */
        public function quotient_rule(int $sequence)
        {

            $tmp_f = (float)$this->get_f_of((float)$sequence);
            $tmp_g = (float)$this->get_g_of((float)$sequence);

            $tmp_ff = (float)$this->get_f_of($tmp_f);
            $tmp_gg = (float)$this->get_g_of($tmp_g);
            
            $final1a = $tmp_ff * $tmp_g;
            $final1b = $tmp_f * $tmp_gg;
            
            $final2 = $final1a * $final1b;
            $answer = $final2 / ($tmp_g * $tmp_g);
            return ($answer);
        }
    }
?>