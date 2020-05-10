<?php
	/* PHPTable - github.com/codeeverywhereca, Copyright 2020, Licensed under MIT */
	
	class Table {	
		public $maxColWidth = 25;
		public $Labels = [];
		public $Data = [];
		
		function setLabels(Array $data) {
			$this->Labels = array_map('trim', $data);
		}
		
		function addData(Array $data) {
			$this->Data[] = array_map('trim', $data);
			return array_combine($this->Labels, $this->Data[count($this->Data)-1]);
		}
		
		function find_max_column_lengths() {
			$buffer = $this->Data;
			$buffer[] = $this->Labels;
			
			for($x=0; $x<count($buffer); $x++)
				$buffer[$x] = array_map('strlen', $buffer[$x]);
			
			$transposed_array = call_user_func_array('array_map', array_merge(array(NULL), $buffer));
			return array_map(function($arr) {
				return max($arr) > $this->maxColWidth ? $this->maxColWidth : max($arr);
			}, $transposed_array);			
		}
		
		function print_f($mask, $data) {
			$args = array_merge([$mask], $data);
		    call_user_func_array('printf', $args);	
		}
		
		function print_mask($columnLengths) {
			$str = '';
			foreach($columnLengths as $col)
				$str .= "| %$col.".$col."s ";
			$str .= "|".PHP_EOL;
			return $str;
		}
		
		function truncate(Array $data, Array $columnLengths) {
			for($x=0; $x<count($data); $x++)
				if(strlen($data[$x]) > $columnLengths[$x] )
					$data[$x] = substr_replace($data[$x], '*', $columnLengths[$x]-1);
			return $data;
		}
				
		function Stats($label = null) {
			if(is_null($label))
				return print_r($this->Labels) && False;

			$index = array_flip($this->Labels)[trim($label)];
			
			$arr=[];
			for($x=0; $x<count($this->Data); $x++)
				$arr[] = (float) str_replace(Array(',', ' '), Array('', ''), $this->Data[$x][$index]);
			
			sort($arr);
			$res = Array(
				'   Min' => min($arr),
				'   Max' => max($arr),
				'   Sum' => array_sum($arr),
				'   Avg' => array_sum($arr) / count($this->Data),
				'Median' => $arr[floor(count($arr)/2)],
				' Count' => count($arr),
				' Range' => max($arr) - min($arr)
			);
			
			echo "+--------+".PHP_EOL."|  Label | {$this->Labels[$index]}";
			foreach($res as $label => $val)
				echo PHP_EOL."| $label | ".number_format($val, 3);
			echo PHP_EOL."+--------+".PHP_EOL.PHP_EOL;
			
			return $res;
		}
		
		function MinMaxScale($label = null) {
			$stats = $this->Stats($label);
			$index = array_flip($this->Labels)[trim($label)];			
			$min_max_scale = function($val, $min, $max) { return ($val-$min) / ($max-$min); };
			$this->Labels[$index] .= "+";
			
			for($x=0; $x<count($this->Data); $x++) {
				$value = (float) str_replace(Array(',', ' '), Array('', ''), $this->Data[$x][$index]);
				$this->Data[$x][$index] = $min_max_scale($value, $stats['   Min'], $stats['   Max']);
			}
			
			return true;
		}
		
		function Print($limit = 0) {
			if(count($this->Data)==0)
				return false;
			
			$columnLengths = $this->find_max_column_lengths();
			$print_mask = $this->print_mask($columnLengths);
			$maskLine = str_replace(Array(' ', '|'), Array('-', '+'), $print_mask);
			$separator = array_map(function($str) { return str_repeat('-', $str); }, $columnLengths);
			
			echo PHP_EOL.PHP_EOL;
						
			$this->print_f($maskLine, $separator);
			$this->print_f($print_mask, $this->Labels);
			$this->print_f($maskLine, $separator);
			
			if($limit != 0)
				$this->print_f($print_mask, explode(' ', str_repeat('... ', count($this->Labels))));
				
			foreach($this->Data as $i => $data) {
				if($limit != 0 && $i < count($this->Data)-$limit) continue;
				$this->print_f($print_mask, $this->truncate($data, $columnLengths));
			}
				
			$this->print_f($maskLine, $separator);						
			echo PHP_EOL."Total Results: ".count($this->Data).PHP_EOL.PHP_EOL;
			return $this->Data;
		}
		
		function export($filename = 'table-export.csv') {
			$file = fopen($filename, 'w');
			fputcsv($file, $this->Labels);
			foreach($this->Data as $data)
				fputcsv($file, $data);
			fclose($file);
		}
	}
?>
