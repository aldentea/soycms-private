<?php
class DateLabel extends HTMLLabel{

	private $defaultFormat = null;
	private $year;
	private $month;
	private $day;
	private $time = null;

	function execute(){

		//日時データ：textまたはyear,month,dayで指定
		if(strlen($this->time)==0){
			if(strlen($this->text)){
				$this->time = $this->text;
				$this->year = date("Y",$this->time);
				$this->month = date("n",$this->time);
				$this->day = date("j",$this->time);
			}else{
				$this->time = mktime(0,0,0,max(1,$this->month),max(1,$this->day),$this->year);
			}
		}

		//フォーマット
		$format = $this->getAttribute("cms:format");
		if(strlen($format)==0){
			if(is_null($this->defaultFormat) || strlen($this->defaultFormat) == 0){
				$format = "Y-m-d H:i:s";
			}else{
				$format = $this->defaultFormat;
			}
		}

		//条件付きフォーマット
		$format = preg_replace("/%DATE:([^%]*)%/e","date('\\1',\$this->time)",$format);
		$format = preg_replace('/%Y:([^%]*)%/e',"strlen(\$this->year)  ? date('\\1',\$this->time) : ''",$format);
		$format = preg_replace('/%M:([^%]*)%/e',"strlen(\$this->month) ? date('\\1',\$this->time) : ''",$format);
		$format = preg_replace('/%D:([^%]*)%/e',"strlen(\$this->day)    ? date('\\1',\$this->time) : ''",$format);


		$this->setText(date($format,$this->time));

		parent::execute();
	}

	function getDefaultFormat() {
		return $this->defaultFormat;
	}
	function setDefaultFormat($defaultFormat) {
		$this->defaultFormat = $defaultFormat;
	}

	function setYear($v){
		$this->year = $v;
	}
	function setMonth($v){
		$this->month = $v;
	}
	function setDay($v){
		$this->day = $v;
	}
}
?>