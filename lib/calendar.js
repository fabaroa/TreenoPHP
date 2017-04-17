var currShowing = new Object();

function getDaysInMonth() {
	if(this.isLeap()) {
		return this.daysInMonthLeap[this.monthNum];
	}
	return this.daysInMonth[this.monthNum];
}

function isLeap() {
	var ret = false;
	if (this.year % 400 == 0) {
		ret = true;
	} else if ((this.year % 4 == 0) && (this.year % 100 != 0)) {
		ret = true;
	}
	return ret;
}

function getMonthObj() {
	var firstDay = new Date(this.year, this.monthNum, 1);
	var startingDay = firstDay.getDay();
	var monthObj = new Object();
	var weekNum = 0;
	var dayInWeek = startingDay;
	monthObj[weekNum] = new Object();
	for (var i = 1; i <= this.days; i++) {
		monthObj[weekNum][dayInWeek] = i;
		if (dayInWeek == 6) {
			dayInWeek = 0;
			weekNum++;
			if(i != this.days) {
				monthObj[weekNum] = new Object();
			}
		} else {
			dayInWeek++;
		}
	}
	return monthObj;
}

function drawCalendar() {
	this.container.className = 'monthDiv';
	var mChange = document.createElement('table');
	mChange.className = 'mChange';
	var yChange = document.createElement('table');
	yChange.className = 'yChange';
	
	var myCal = this;

	var currRow = mChange.insertRow(mChange.rows.length);
	var tmpImg = document.createElement('img');
	tmpImg.src = '../images/tri-left.gif';
	var tmpCell = currRow.insertCell(currRow.cells.length);
	tmpCell.appendChild(tmpImg);
	tmpCell.onclick = function() {
		prevMonth(myCal);
	}
	tmpCell = currRow.insertCell(currRow.cells.length);
	tmpCell.appendChild(document.createTextNode(this.monthStr));
	tmpCell.className = 'monthCell';
	tmpImg = document.createElement('img');
	tmpImg.src = '../images/tri-right.gif';
	tmpCell = currRow.insertCell(currRow.cells.length);
	tmpCell.appendChild(tmpImg);
	tmpCell.onclick = function() {
		nextMonth(myCal);
	}
	this.container.appendChild(mChange);

	currRow = yChange.insertRow(yChange.rows.length);
	tmpImg = document.createElement('img');
	tmpImg.src = '../images/tri-left.gif';
	tmpCell = currRow.insertCell(currRow.cells.length);
	tmpCell.appendChild(tmpImg);
		tmpCell.onclick = function() {
		prevYear(myCal);
	}
	tmpCell = currRow.insertCell(currRow.cells.length);
	tmpCell.appendChild(document.createTextNode(this.year));
	tmpCell.className = 'yearCell';
	tmpImg = document.createElement('img');
	tmpImg.src = '../images/tri-right.gif';
	tmpCell = currRow.insertCell(currRow.cells.length);
	tmpCell.appendChild(tmpImg);
	tmpCell.onclick = function() {
		nextYear(myCal);
	}
	this.container.appendChild(yChange);
	
	var clearDiv = document.createElement('div');
	clearDiv.appendChild(document.createTextNode('\u00a0'));
	clearDiv.style.clear = 'both';
	this.container.appendChild(clearDiv);
	
	var monthTable = document.createElement('table');
	monthTable.className = 'monthTable';
	var currRow;
	var i;
	var currCell;
	var myTxt;
	currRow = monthTable.insertRow(monthTable.rows.length);
	currRow.className = 'weekRow';
	for(i = 0; i < this.daysOfWeek.length; i++) {
		currCell = currRow.insertCell(currRow.cells.length);
		currCell.appendChild(document.createTextNode(this.daysOfWeek[i]));
	}
	for (week in this.monthObj) {
		currRow = monthTable.insertRow(monthTable.rows.length);
		for (i = 0; i < 7; i++) {
			currCell = currRow.insertCell(currRow.cells.length);
			if(this.monthObj[week][i]) {
				myTxt =  document.createTextNode(this.monthObj[week][i]);
				if(this.year == this.currYear &&
					this.monthNum == this.currMonth && 
						this.monthObj[week][i] == this.currDate) {
					
					currCell.className = 'currDate';
				} else {
					currCell.className = 'anyDate';
				}
				currCell.onmouseover = calMouseOver;
				currCell.onmouseout = calMouseOut;
				currCell.myCal = myCal;
				currCell.onclick = selectDate;
				currCell.appendChild(myTxt);
			}
		}
	}
	this.container.appendChild(monthTable);
}

function calMouseOver() {
	this.oldClass = this.className;
	this.className += ' calMouseOver';
}

function calMouseOut() {
	this.className = this.oldClass;
}

function selectDate() {
	var myMonth = this.myCal.monthNum + 1;
	if(myMonth < 10) {
		myMonth = '0' + myMonth;
	}
	var myDate = this.firstChild.nodeValue;
	if(myDate < 10) {
		myDate = '0' + myDate; 
	}
	var myStr = this.myCal.year + '-' + myMonth + '-' + myDate;
	this.myCal.input.value = myStr;
	if(!this.myCal.container.whereID) {
		if (this.myCal.container.shim) {
			document.body.removeChild (this.myCal.container.shim);
		}
		document.body.removeChild(this.myCal.container);
	} else {
		if (this.myCal.container.shim) {
			document.getElementById (this.myCal.container.whereID).removeChild
				(this.myCal.container.shim);
		}
		document.getElementById(this.myCal.container.whereID).removeChild(this.myCal.container);
	}
	currShowing[this.myCal.input.id] = null;
	this.myCal.input.focus();
	this.myCal = null;
	if( typeof( window[ 'workflowID' ] ) != "undefined" ) {	
		if( workflowID ) {
			updateDueDate(workflowID);
		}
	}
}

function Calendar(month, year, myDiv, myInput) {
	var months = new Array('January', 'February', 'March', 'April', 'May',
						   'June', 'July', 'August', 'September', 'October',
						   'November', 'December');
	this.daysInMonthLeap = new Array(31, 29, 31, 30, 31, 30, 
									 31, 31, 30, 31, 30, 31);
	this.daysInMonth = new Array(31, 28, 31, 30, 31, 30,
								 31, 31, 30, 31, 30, 31);
	this.daysOfWeek = new Array('Sun', 'Mon', 'Tue',
								'Wed', 'Thu', 'Fri', 'Sat');

	this.getDaysInMonth = getDaysInMonth;
	this.isLeap = isLeap;
	this.getMonthObj = getMonthObj;
	this.drawCalendar = drawCalendar;
	this.container = myDiv;
	this.input = myInput;
	var tmpDate = new Date();
	this.currMonth = tmpDate.getMonth();
	this.currDate = tmpDate.getDate();
	this.currYear = tmpDate.getFullYear();
	if (year > 9999) {
		year = 9999;
	} else if (year < 0) {
		year = 0;
	}
	if (month > 11) {
		month = 11;
	} else if (month < 0) {
		month = 0;
	}
	this.year = year;
	this.monthNum = month;
	this.monthStr = months[month];
	this.days = this.getDaysInMonth();
	this.monthObj = this.getMonthObj();
	this.drawCalendar();
}

function nextMonth(myCalendar) {
	var nextMonth = myCalendar.monthNum + 1;
	var nextYear = myCalendar.year;
	if(nextMonth > 11) {
		nextMonth = 0;
		nextYear++;
	}
	replaceCalendar(myCalendar, nextMonth, nextYear);
}

function prevMonth(myCalendar) {
	var prevMonth = myCalendar.monthNum - 1;
	var prevYear = myCalendar.year;
	if(prevMonth < 0) {
		prevMonth = 11;
		prevYear--;
	}
	replaceCalendar(myCalendar, prevMonth, prevYear);
}

function nextYear(myCalendar) {
	var nextMonth = myCalendar.monthNum;
	var nextYear = myCalendar.year + 1;
	replaceCalendar(myCalendar, nextMonth, nextYear);
}

function prevYear(myCalendar) {
	var prevMonth = myCalendar.monthNum;
	var prevYear = myCalendar.year - 1;
	replaceCalendar(myCalendar, prevMonth, prevYear);
}

function replaceCalendar(oldCalendar, month, year) {
	while(oldCalendar.container.hasChildNodes()) {
		oldCalendar.container.removeChild(oldCalendar.container.firstChild);
	}
	var newCal = new Calendar(month, year, oldCalendar.container,
							  oldCalendar.input);
	oldCalendar = null;
}

function dispCurrMonth() {
	if(this.workflowID) {
		workflowID = this.workflowID;
	}

	var inputBox = this.input;
	if(this.whereID) {
		var whereID = this.whereID;
	}
	if(currShowing[inputBox.id]) {
		if(!whereID) {
			if (currShowing[inputBox.id].shim) {
				document.body.removeChild (currShowing[inputBox.id].shim);
			}
			document.body.removeChild(currShowing[inputBox.id]);
		} else {
			if (currShowing[inputBox.id].shim) {
				document.getElementById (whereID).removeChild
					(currShowing[inputBox.id].shim);
			}
			document.getElementById(whereID).removeChild(currShowing[inputBox.id]);		
		}
		currShowing[inputBox.id] = null;
	} else {
		var currDate = new Date();
		var newDiv = document.createElement('div');
		newDiv.style.visibility = 'hidden';
		new Calendar(currDate.getMonth(), currDate.getFullYear(), newDiv, inputBox);
		if(!whereID) {
			document.body.appendChild(newDiv);
		} else {
			document.getElementById(whereID).appendChild(newDiv);
		}
		newDiv.style.position = 'absolute';
		newDiv.style.zIndex = 100;
		var tmpVal = 0;
		var el = inputBox;
		while (el) {
			tmpVal += el.offsetLeft;
			el = el.offsetParent;
		}
		if(whereID) {
			tmpVal -= document.getElementById(whereID).scrollLeft;
			var newRight = newDiv.offsetWidth + tmpVal;
			var difference = newRight -
				document.getElementById(whereID).offsetWidth;
			if(difference > 0) {
				tmpVal -= difference;
			}
		}
		newDiv.style.left = tmpVal + 'px';
		tmpVal = 0;
		el = inputBox;
		while (el) {
			tmpVal += el.offsetTop;
			el = el.offsetParent;
		}
		if (whereID) {
			tmpVal -= document.getElementById(whereID).scrollTop;
		}
		newDiv.style.top = tmpVal + inputBox.offsetHeight + 'px';
		if(newDiv.offsetLeft < 0) {
			newDiv.style.left = '0px';
		}
		newDiv.style.width = (newDiv.offsetWidth+25) + 'px';
		var iframe = document.createElement ('iframe');
		iframe.style.display = 'none';
		iframe.style.left = '0px';
		iframe.style.position = 'absolute';
		iframe.style.top = '0px';
		iframe.src = 'javascript:false;';
		iframe.frameborder = '0';
		iframe.style.border = '0px';
		iframe.scrolling = 'no';
		if(!whereID) {
			document.body.appendChild(iframe);
		} else {
			document.getElementById(whereID).appendChild(iframe);
		}
		iframe.style.top = newDiv.style.top;
		iframe.style.left = newDiv.style.left;
		iframe.style.width = newDiv.offsetWidth + 'px';
		iframe.style.height = newDiv.offsetHeight + 'px';
		iframe.style.zIndex = newDiv.style.zIndex - 1;
		newDiv.style.visibility = 'visible';
		iframe.style.display = 'block';
		newDiv.shim = iframe;
		currShowing[inputBox.id] = newDiv;
		newDiv.whereID = whereID;
	}
}

function validateDate()
{
	this.msg = 'Date must be of form YYYY-MM-DD.';
	if(this.value.length == 0) {
		return true;
	}
	if(this.value.length != 10) {
		return false;
	}
	if(this.value.charAt(4) != '-' || this.value.charAt(7) != '-') {
		return false;
	}
	if(!(parseInt(this.value.replace('-', '')) > 0)) {
		return false;
	}
	return true;
}
