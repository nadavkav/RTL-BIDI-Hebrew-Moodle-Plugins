dynamic class pointRoll extends MovieClip {
	public function onRollOver():Void {
		this.gotoAndStop(2);
	}
	public function onRollOut():Void {
		this.gotoAndStop(1);
	}
	public function onDragOut():Void {
		this.gotoAndStop(1);
	}
}
