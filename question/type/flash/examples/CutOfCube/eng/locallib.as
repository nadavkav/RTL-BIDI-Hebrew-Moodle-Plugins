/***************************************************
 * This file is a example of "Flash" question type
 * for LMS Moodle.
 *
 * @author Petrov Aleksandr, Russia, Novosibirsk, 2009
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 ***************************************************/

_root._lockroot = true;

function init() {
	_root.ga = 20;
	_root.acts = new Array("Choose or add a point", "The one point selected, choose another", "Choose straight line", "Straight line selected, choose point");
	_root.studentPanel.act.text = _root.acts[0];
	_root.e = 300;
	_root.eps = 0.1;
	_root.numPoints = 0;
	_root.numLines = 0;
	_root.points = new Array();
	for (i=1; i<=10000; i++) {
		_root["point"+i].removeMovieClip();
	}
	_root.lines = new Array();
	_root.qp = new Array();
	_root.markedPoints = 0;
	_root.pointIndex1 = 0;
	_root.pointIndex2 = 0;
	_root.target = _root;
	_root.xc = 200;
	_root.yc = 300;
	_root.xz = 0.1;
	_root.yz = 0.3;
	_root.scale = 1.5;
	
}

function eqv(a, b) {
	if (Math.abs(a-b)<_root.eps) {
		return true;
	} else {
		return false;
	}
}

function drawPoints(num) {
	for (i=1; i<=numPoints; i++) {
		if (i<=num) {
			points[i] = calc(points[i]);
		} else {
			points[i]._x = -100;
		}
	}
}

function addLine(p1, p2, type, num, par, p3) {
	numLines++;
	var line = {beg:p1, end:p2, t:type, n:num, p:par, cnt:p3};
	if (type == undefined) {
		line.t = "v";
	}
	lines[numLines] = line;
	_root.userActs.push(new Array("line", line));
	trace('line '+numLines+' '+p1+' '+p2);
	// Save and send user actions to Moodle (MoodleIntegration)
	sendUserAnswer();
}

function drawLines(num) {
	minx = 0;
	miny = 0;
	maxx = 600;
	maxy = 460;
	target.lineStyle(1, 0x0000ff, _root.ga);
	for (i=1; i<=num; i++) {
		if (lines[i].p) {
			_x1 = points[lines[i].beg]._x;
			_y1 = points[lines[i].beg]._y;
			_x2 = points[lines[i].end]._x;
			_y2 = points[lines[i].end]._y;
			dx = _x2-_x1;
			dy = _y2-_y1;
			x1 = points[lines[i].cnt]._x;
			y1 = points[lines[i].cnt]._y;
			x2 = x1+dx;
			y2 = y1+dy;
		} else {
			x1 = points[lines[i].beg]._x;
			y1 = points[lines[i].beg]._y;
			x2 = points[lines[i].end]._x;
			y2 = points[lines[i].end]._y;
		}
		if (x1 == x2) {
			target.moveTo(x1, miny);
			target.lineTo(x1, maxy);
		} else if (y1 == y2) {
			target.moveTo(minx, y1);
			target.lineTo(maxx, y1);
		} else {
			yb = ((minx-x1)*(y2-y1))/(x2-x1)+y1;
			ye = ((maxx-x1)*(y2-y1))/(x2-x1)+y1;
			xb = minx;
			xe = maxx;
			if (yb<miny) {
				yb = miny;
				xb = ((miny-y1)*(x2-x1))/(y2-y1)+x1;
			}
			if (yb>maxy) {
				yb = maxy;
				xb = ((maxy-y1)*(x2-x1))/(y2-y1)+x1;
			}
			if (ye<miny) {
				ye = miny;
				xe = ((miny-y1)*(x2-x1))/(y2-y1)+x1;
			}
			if (ye>maxy) {
				ye = maxy;
				xe = ((maxy-y1)*(x2-x1))/(y2-y1)+x1;
			}
			target.moveTo(xb, yb);
			target.lineTo(xe, ye);
		}
	}
	target.lineStyle(1, 0x0000ff, 100);
	for (i=1; i<=num; i++) {
		if (!lines[i].p) {
			px1 = points[lines[i].beg]._x;
			py1 = points[lines[i].beg]._y;
			px2 = points[lines[i].end]._x;
			py2 = points[lines[i].end]._y;
			switch (lines[i].t) {
			case "v" :
				target.moveTo(px1, py1);
				target.lineTo(px2, py2);
				break;
			case "n" :
				var t = 5;
				var dx = (px2-px1)/t;
				var dy = (py2-py1)/t;
				for (var j = 0; j<t; j++) {
					var x1 = dx*j;
					var y1 = dy*j;
					var x2 = dx*(j+0.5);
					var y2 = dy*(j+0.5);
					target.moveTo(px1+x1, py1+y1);
					target.lineTo(px1+x2, py1+y2);
				}
				break;
			case "u" :
				trace(_root.xz);
				if (lines[i].n>=_root.xz) {
					target.moveTo(px1, py1);
					target.lineTo(px2, py2);
				} else {
					var t = 5;
					var dx = (px2-px1)/t;
					var dy = (py2-py1)/t;
					for (var j = 0; j<t; j++) {
						var x1 = dx*j;
						var y1 = dy*j;
						var x2 = dx*(j+0.5);
						var y2 = dy*(j+0.5);
						target.moveTo(px1+x1, py1+y1);
						target.lineTo(px1+x2, py1+y2);
					}
				}
				break;
			default :
				target.moveTo(px1, py1);
				target.lineTo(px2, py2);
				break;
			}
		}
	}
}

function calc(point) {
	point._x = xc+(point.x-point.z*xz)*scale;
	point._y = yc+(-point.y+point.z*yz)*scale;
	return point;
}

function calc2(point) {
	point.xp = xc+(point.x-point.z*xz)*scale;
	point.yp = yc+(-point.y+point.z*yz)*scale;
	return point;
}

function drawCoords() {
	target.lineStyle(1, 0x000000, 10);
	target.moveTo(xc, yc);
	target.lineTo(xc+200*scale, yc);
	target.moveTo(xc, yc);
	target.lineTo(xc, yc-200*scale);
	target.moveTo(xc, yc);
	target.lineTo(xc-200*xz*scale, yc+200*yz*scale);
}

function drawAll(nP, nL) {
	xz = polzunx.head._x/200;
	yz = polzuny.head._x/200;
	scale = 1+(polzuns.head._x/80);
	target.clear();
	drawCoords();
	drawPoints(nP);
	drawLines(nL);
	// cut of cube
	if (_root.grad != undefined) {
		_root.grad.clear();
		drawSech();
	}
}

_root.onMouseMove = function() {
	_root["point"+pointIndex1].onRollOver();
};

_root.onMouseUp = function() {
	if (_root.check_box.checked) {
		// parallel
		// 1. find line 
		trace(findLines);
		if (findLines == 0) {
			x = _root._xmouse;
			y = _root._ymouse;
			i = 1;
			findLines = 0;
			while (findLines<1 && i<=numLines) {
				if (lines[i].p) {
					_x1 = points[lines[i].beg]._x;
					_y1 = points[lines[i].beg]._y;
					_x2 = points[lines[i].end]._x;
					_y2 = points[lines[i].end]._y;
					dx = _x2-_x1;
					dy = _y2-_y1;
					x1 = points[lines[i].cnt]._x;
					y1 = points[lines[i].cnt]._y;
					x2 = x1+dx;
					y2 = y1+dy;
				} else {
					x1 = points[lines[i].beg]._x;
					y1 = points[lines[i].beg]._y;
					x2 = points[lines[i].end]._x;
					y2 = points[lines[i].end]._y;
				}
				expr = (x-x1)*(y2-y1)-(y-y1)*(x2-x1);
				if (Math.abs(expr)<=e) {
					trace("expr = "+expr);
					if (findLines == 0) {
						lineIndex1 = i;
						findLines++;
						studentPanel.act.text = acts[3];
					}
				}
				i++;
			}
		} else {
			// 2. find point if finded line	
			if (findLines == 1) {
				i = 1;
				findPoint = 0;
				while (findPoint == 0 && i<=numPoints) {
					if (_root["point"+i].hitTest(_root._xmouse, _root._ymouse, true)) {
						findPoint = i;
					}
					i++;
				}
				if (findPoint>0) {
					// create line by lineIndex1 and findPoint
					addLine(lines[lineIndex1].beg, lines[lineIndex1].end, "v", 0, true, findPoint);
					for (j=1; j<=numPoints; j++) {
						_root["point"+j].onRollOut();
					}
					drawAll(numPoints, numLines);
					// clearing
					findLines = 0;
					findPoint = 0;
					_root.check_box.gotoAndStop(1);
					studentPanel.act.text = acts[0];
				}
			}
		}
	} else {
		if (markedPoints<2) {
			i = 1;
			findPoint = 0;
			while (findPoint == 0 && i<=numPoints) {
				if (_root["point"+i].hitTest(_root._xmouse, _root._ymouse, true)) {
					findPoint = i;
				}
				i++;
			}
			if (findPoint>0 && markedPoints == 0) {
				pointIndex1 = findPoint;
				studentPanel.act.text = acts[1];
				markedPoints++;
			} else if (findPoint>0 && markedPoints == 1 && findPoint != pointIndex1) {
				pointIndex2 = findPoint;
				addLine(pointIndex1, pointIndex2);
				studentPanel.act.text = acts[0];
				markedPoints = 0;
				pointIndex1 = 0;
				pointIndex2 = 0;
				for (j=1; j<=numPoints; j++) {
					_root["point"+j].onRollOut();
				}
				drawAll(numPoints, numLines);
			} else {
				// rut point
				studentPanel.act.text = acts[0];
				_root.markedPoints = 0;
				_root.pointIndex1 = 0;
				_root.pointIndex2 = 0;
				for (j=1; j<=numPoints; j++) {
					_root["point"+j].onRollOut();
				}
				x = _root._xmouse;
				y = _root._ymouse;
				i = 1;
				findLines = 0;
				while (findLines<2 && i<=numLines) {
					if (lines[i].p) {
						_x1 = points[lines[i].beg]._x;
						_y1 = points[lines[i].beg]._y;
						_x2 = points[lines[i].end]._x;
						_y2 = points[lines[i].end]._y;
						dx = _x2-_x1;
						dy = _y2-_y1;
						x1 = points[lines[i].cnt]._x;
						y1 = points[lines[i].cnt]._y;
						x2 = x1+dx;
						y2 = y1+dy;
					} else {
						x1 = points[lines[i].beg]._x;
						y1 = points[lines[i].beg]._y;
						x2 = points[lines[i].end]._x;
						y2 = points[lines[i].end]._y;
					}
					expr = (x-x1)*(y2-y1)-(y-y1)*(x2-x1);
					if (Math.abs(expr)<=e) {
						if (findLines == 0) {
							lineIndex1 = i;
							findLines++;
						} else if (findLines == 1) {
							lineIndex2 = i;
							findLines++;
						}
					}
					i++;
				}
				if (findLines>=2) {
					// findLines>=2
					// 1 line
					if (lines[lineIndex1].p) {
						_x1 = points[lines[lineIndex1].beg].x;
						_y1 = points[lines[lineIndex1].beg].y;
						_z1 = points[lines[lineIndex1].beg].z;
						_x2 = points[lines[lineIndex1].end].x;
						_y2 = points[lines[lineIndex1].end].y;
						_z2 = points[lines[lineIndex1].end].z;
						dx = _x2-_x1;
						dy = _y2-_y1;
						dz = _z2-_z1;
						x1 = points[lines[lineIndex1].cnt].x;
						y1 = points[lines[lineIndex1].cnt].y;
						z1 = points[lines[lineIndex1].cnt].z;
						x2 = x1+dx;
						y2 = y1+dy;
						z2 = z1+dz;
					} else {
						x1 = points[lines[lineIndex1].beg].x;
						y1 = points[lines[lineIndex1].beg].y;
						z1 = points[lines[lineIndex1].beg].z;
						x2 = points[lines[lineIndex1].end].x;
						y2 = points[lines[lineIndex1].end].y;
						z2 = points[lines[lineIndex1].end].z;
					}
					// 2 line
					if (lines[lineIndex2].p) {
						_x1 = points[lines[lineIndex2].beg].x;
						_y1 = points[lines[lineIndex2].beg].y;
						_z1 = points[lines[lineIndex2].beg].z;
						_x2 = points[lines[lineIndex2].end].x;
						_y2 = points[lines[lineIndex2].end].y;
						_z2 = points[lines[lineIndex2].end].z;
						dx = _x2-_x1;
						dy = _y2-_y1;
						dz = _z2-_z1;
						x3 = points[lines[lineIndex2].cnt].x;
						y3 = points[lines[lineIndex2].cnt].y;
						z3 = points[lines[lineIndex2].cnt].z;
						x4 = x3+dx;
						y4 = y3+dy;
						z4 = z3+dz;
					} else {
						x3 = points[lines[lineIndex2].beg].x;
						y3 = points[lines[lineIndex2].beg].y;
						z3 = points[lines[lineIndex2].beg].z;
						x4 = points[lines[lineIndex2].end].x;
						y4 = points[lines[lineIndex2].end].y;
						z4 = points[lines[lineIndex2].end].z;
					}
					//
					a1 = x2-x1;
					b1 = y2-y1;
					c1 = z2-z1;
					a2 = x4-x3;
					b2 = y4-y3;
					c2 = z4-z3;
					a3 = x3-x1;
					b3 = y3-y1;
					c3 = z3-z1;
					t = (x4-x1)*((y2-y1)*(z3-z1)-(y3-y1)*(z2-z1))-(y4-y1)*((x2-x1)*(z3-z1)-(x3-x1)*(z2-z1))+(z4-z1)*((x2-x1)*(y3-y1)-(x3-x1)*(y2-y1));
					//trace('t = '+t);
					trace("t = "+t);
					if (Math.abs(t)<=0.00001) {
						finded = false;
						if (a2*b1-a1*b2 != 0) {
							finded = true;
							t2 = (a1*b3-a3*b1)/(a2*b1-a1*b2);
							x = x3+t2*a2;
							y = y3+t2*b2;
							z = z3+t2*c2;
						} else if (a2*c1-a1*c2 != 0) {
							finded = true;
							t2 = (a1*c3-a3*c1)/(a2*c1-a1*c2);
							x = x3+t2*a2;
							y = y3+t2*b2;
							z = z3+t2*c2;
						} else if (b2*c1-b1*c2 != 0) {
							finded = true;
							t2 = (b1*c3-b3*c1)/(b2*c1-b1*c2);
							x = x3+t2*a2;
							y = y3+t2*b2;
							z = z3+t2*c2;
						}
						if (finded) {
							numPoints++;
							attachMovie("point", "point"+numPoints, 100+numPoints, {_x:-100, _y:-100});
							points[numPoints] = _root["point"+numPoints];
							points[numPoints].x = x;
							points[numPoints].y = y;
							points[numPoints].z = z;
							points[numPoints] = calc(points[numPoints]);
							trace('point '+numPoints+' = '+points[numPoints].x+' '+points[numPoints].y+' '+points[numPoints].z);
							
							var p = {x:x, y:y, z:z};
							userActs.push(new Array("point", p));
							// Save and send user actions to Moodle (MoodleIntegration)
							sendUserAnswer();
						}
					}
				}
			}
		}
	}
}

function drawSech() {
	for (i=1; i<=_root.numPointsSech; i++) {
		_root.qp[i] = calc2(_root.qp[i]);
	}
	if (Question.FillCorrect) {
		color = 0x00FF00;
	} else {
		color = 0x0000FF;
	}
	with (_root.grad) {
		lineStyle(0, 0xffffff, 0);
		colors = [color, color];
		alphas = [30, 50];
		ratios = [0, 0xFF];
		matrix = {a:200, b:0, c:0, d:0, e:200, f:0, g:200, h:200, i:1};
		beginGradientFill("linear", colors, alphas, ratios, matrix);
		moveTo(_root.qp[_root.numPointsSech].xp, _root.qp[_root.numPointsSech].yp);
		for (i=1; i<=_root.numPointsSech; i++) {
			lineTo(_root.qp[i].xp, _root.qp[i].yp);
		}
		endFill();
	}
}
