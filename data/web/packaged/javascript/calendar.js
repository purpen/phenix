Calendar=function(a,b,c,d){if(this.activeDiv=null,this.currentDateEl=null,this.getDateStatus=null,this.getDateToolTip=null,this.getDateText=null,this.timeout=null,this.onSelected=c||null,this.onClose=d||null,this.dragging=!1,this.hidden=!1,this.minYear=1970,this.maxYear=2050,this.dateFormat=Calendar._TT.DEF_DATE_FORMAT,this.ttDateFormat=Calendar._TT.TT_DATE_FORMAT,this.isPopup=!0,this.weekNumbers=!0,this.firstDayOfWeek="number"==typeof a?a:Calendar._FD,this.showsOtherMonths=!1,this.dateStr=b,this.ar_days=null,this.showsTime=!1,this.time24=!0,this.yearStep=2,this.hiliteToday=!0,this.multiple=null,this.table=null,this.element=null,this.tbody=null,this.firstdayname=null,this.monthsCombo=null,this.yearsCombo=null,this.hilitedMonth=null,this.activeMonth=null,this.hilitedYear=null,this.activeYear=null,this.dateClicked=!1,"undefined"==typeof Calendar._SDN){"undefined"==typeof Calendar._SDN_len&&(Calendar._SDN_len=3);for(var e=new Array,f=8;f>0;)e[--f]=Calendar._DN[f].substr(0,Calendar._SDN_len);Calendar._SDN=e,"undefined"==typeof Calendar._SMN_len&&(Calendar._SMN_len=3),e=new Array;for(var f=12;f>0;)e[--f]=Calendar._MN[f].substr(0,Calendar._SMN_len);Calendar._SMN=e}},Calendar._C=null,Calendar.is_ie=/msie/i.test(navigator.userAgent)&&!/opera/i.test(navigator.userAgent),Calendar.is_ie5=Calendar.is_ie&&/msie 5\.0/i.test(navigator.userAgent),Calendar.is_opera=/opera/i.test(navigator.userAgent),Calendar.is_khtml=/Konqueror|Safari|KHTML/i.test(navigator.userAgent),Calendar.getAbsolutePos=function(a){var b=0,c=0,d=/^div$/i.test(a.tagName);d&&a.scrollLeft&&(b=a.scrollLeft),d&&a.scrollTop&&(c=a.scrollTop);var e={x:a.offsetLeft-b,y:a.offsetTop-c};if(a.offsetParent){var f=this.getAbsolutePos(a.offsetParent);e.x+=f.x,e.y+=f.y}return e},Calendar.isRelated=function(a,b){var c=b.relatedTarget;if(!c){var d=b.type;"mouseover"==d?c=b.fromElement:"mouseout"==d&&(c=b.toElement)}for(;c;){if(c==a)return!0;c=c.parentNode}return!1},Calendar.removeClass=function(a,b){if(a&&a.className){for(var c=a.className.split(" "),d=new Array,e=c.length;e>0;)c[--e]!=b&&(d[d.length]=c[e]);a.className=d.join(" ")}},Calendar.addClass=function(a,b){Calendar.removeClass(a,b),a.className+=" "+b},Calendar.getElement=function(a){for(var b=Calendar.is_ie?window.event.srcElement:a.currentTarget;1!=b.nodeType||/^div$/i.test(b.tagName);)b=b.parentNode;return b},Calendar.getTargetElement=function(a){for(var b=Calendar.is_ie?window.event.srcElement:a.target;1!=b.nodeType;)b=b.parentNode;return b},Calendar.stopEvent=function(a){return a||(a=window.event),Calendar.is_ie?(a.cancelBubble=!0,a.returnValue=!1):(a.preventDefault(),a.stopPropagation()),!1},Calendar.addEvent=function(a,b,c){a.attachEvent?a.attachEvent("on"+b,c):a.addEventListener?a.addEventListener(b,c,!0):a["on"+b]=c},Calendar.removeEvent=function(a,b,c){a.detachEvent?a.detachEvent("on"+b,c):a.removeEventListener?a.removeEventListener(b,c,!0):a["on"+b]=null},Calendar.createElement=function(a,b){var c=null;return c=document.createElementNS?document.createElementNS("http://www.w3.org/1999/xhtml",a):document.createElement(a),"undefined"!=typeof b&&b.appendChild(c),c},Calendar._add_evs=function(el){with(Calendar)addEvent(el,"mouseover",dayMouseOver),addEvent(el,"mousedown",dayMouseDown),addEvent(el,"mouseout",dayMouseOut),is_ie&&(addEvent(el,"dblclick",dayMouseDblClick),el.setAttribute("unselectable",!0))},Calendar.findMonth=function(a){return"undefined"!=typeof a.month?a:"undefined"!=typeof a.parentNode.month?a.parentNode:null},Calendar.findYear=function(a){return"undefined"!=typeof a.year?a:"undefined"!=typeof a.parentNode.year?a.parentNode:null},Calendar.showMonthsCombo=function(){var a=Calendar._C;if(!a)return!1;var a=a,b=a.activeDiv,c=a.monthsCombo;a.hilitedMonth&&Calendar.removeClass(a.hilitedMonth,"hilite"),a.activeMonth&&Calendar.removeClass(a.activeMonth,"active");var d=a.monthsCombo.getElementsByTagName("div")[a.date.getMonth()];Calendar.addClass(d,"active"),a.activeMonth=d;var e=c.style;if(e.display="block",b.navtype<0)e.left=b.offsetLeft+"px";else{var f=c.offsetWidth;"undefined"==typeof f&&(f=50),e.left=b.offsetLeft+b.offsetWidth-f+"px"}e.top=b.offsetTop+b.offsetHeight+"px"},Calendar.showYearsCombo=function(a){var b=Calendar._C;if(!b)return!1;var b=b,c=b.activeDiv,d=b.yearsCombo;b.hilitedYear&&Calendar.removeClass(b.hilitedYear,"hilite"),b.activeYear&&Calendar.removeClass(b.activeYear,"active"),b.activeYear=null;for(var e=b.date.getFullYear()+(a?1:-1),f=d.firstChild,g=!1,h=12;h>0;--h)e>=b.minYear&&e<=b.maxYear?(f.innerHTML=e,f.year=e,f.style.display="block",g=!0):f.style.display="none",f=f.nextSibling,e+=a?b.yearStep:-b.yearStep;if(g){var i=d.style;if(i.display="block",c.navtype<0)i.left=c.offsetLeft+"px";else{var j=d.offsetWidth;"undefined"==typeof j&&(j=50),i.left=c.offsetLeft+c.offsetWidth-j+"px"}i.top=c.offsetTop+c.offsetHeight+"px"}},Calendar.tableMouseUp=function(ev){var cal=Calendar._C;if(!cal)return!1;cal.timeout&&clearTimeout(cal.timeout);var el=cal.activeDiv;if(!el)return!1;var target=Calendar.getTargetElement(ev);ev||(ev=window.event),Calendar.removeClass(el,"active"),(target==el||target.parentNode==el)&&Calendar.cellClick(el,ev);var mon=Calendar.findMonth(target),date=null;if(mon)date=new Date(cal.date),mon.month!=date.getMonth()&&(date.setMonth(mon.month),cal.setDate(date),cal.dateClicked=!1,cal.callHandler());else{var year=Calendar.findYear(target);year&&(date=new Date(cal.date),year.year!=date.getFullYear()&&(date.setFullYear(year.year),cal.setDate(date),cal.dateClicked=!1,cal.callHandler()))}with(Calendar)return removeEvent(document,"mouseup",tableMouseUp),removeEvent(document,"mouseover",tableMouseOver),removeEvent(document,"mousemove",tableMouseOver),cal._hideCombos(),_C=null,stopEvent(ev)},Calendar.tableMouseOver=function(a){var b=Calendar._C;if(b){var c=b.activeDiv,d=Calendar.getTargetElement(a);if(d==c||d.parentNode==c?(Calendar.addClass(c,"hilite active"),Calendar.addClass(c.parentNode,"rowhilite")):(("undefined"==typeof c.navtype||50!=c.navtype&&(0==c.navtype||Math.abs(c.navtype)>2))&&Calendar.removeClass(c,"active"),Calendar.removeClass(c,"hilite"),Calendar.removeClass(c.parentNode,"rowhilite")),a||(a=window.event),50==c.navtype&&d!=c){var e,f=Calendar.getAbsolutePos(c),g=c.offsetWidth,h=a.clientX,i=!0;h>f.x+g?(e=h-f.x-g,i=!1):e=f.x-h,0>e&&(e=0);for(var j=c._range,k=c._current,l=Math.floor(e/10)%j.length,m=j.length;--m>=0&&j[m]!=k;);for(;l-->0;)i?--m<0&&(m=j.length-1):++m>=j.length&&(m=0);var n=j[m];c.innerHTML=n,b.onUpdateTime()}var o=Calendar.findMonth(d);if(o)o.month!=b.date.getMonth()?(b.hilitedMonth&&Calendar.removeClass(b.hilitedMonth,"hilite"),Calendar.addClass(o,"hilite"),b.hilitedMonth=o):b.hilitedMonth&&Calendar.removeClass(b.hilitedMonth,"hilite");else{b.hilitedMonth&&Calendar.removeClass(b.hilitedMonth,"hilite");var p=Calendar.findYear(d);p&&p.year!=b.date.getFullYear()?(b.hilitedYear&&Calendar.removeClass(b.hilitedYear,"hilite"),Calendar.addClass(p,"hilite"),b.hilitedYear=p):b.hilitedYear&&Calendar.removeClass(b.hilitedYear,"hilite")}return Calendar.stopEvent(a)}},Calendar.tableMouseDown=function(a){return Calendar.getTargetElement(a)==Calendar.getElement(a)?Calendar.stopEvent(a):void 0},Calendar.calDragIt=function(a){var b=Calendar._C;if(!b||!b.dragging)return!1;var c,d;Calendar.is_ie?(d=window.event.clientY+document.body.scrollTop,c=window.event.clientX+document.body.scrollLeft):(c=a.pageX,d=a.pageY),b.hideShowCovered();var e=b.element.style;return e.left=c-b.xOffs+"px",e.top=d-b.yOffs+"px",Calendar.stopEvent(a)},Calendar.calDragEnd=function(ev){var cal=Calendar._C;if(!cal)return!1;with(cal.dragging=!1,Calendar)removeEvent(document,"mousemove",calDragIt),removeEvent(document,"mouseup",calDragEnd),tableMouseUp(ev);cal.hideShowCovered()},Calendar.dayMouseDown=function(ev){var el=Calendar.getElement(ev);if(el.disabled)return!1;var cal=el.calendar;if(cal.activeDiv=el,Calendar._C=cal,300!=el.navtype)with(Calendar)50==el.navtype?(el._current=el.innerHTML,addEvent(document,"mousemove",tableMouseOver)):addEvent(document,Calendar.is_ie5?"mousemove":"mouseover",tableMouseOver),addClass(el,"hilite active"),addEvent(document,"mouseup",tableMouseUp);else cal.isPopup&&cal._dragStart(ev);return-1==el.navtype||1==el.navtype?(cal.timeout&&clearTimeout(cal.timeout),cal.timeout=setTimeout("Calendar.showMonthsCombo()",250)):-2==el.navtype||2==el.navtype?(cal.timeout&&clearTimeout(cal.timeout),cal.timeout=setTimeout(el.navtype>0?"Calendar.showYearsCombo(true)":"Calendar.showYearsCombo(false)",250)):cal.timeout=null,Calendar.stopEvent(ev)},Calendar.dayMouseDblClick=function(a){Calendar.cellClick(Calendar.getElement(a),a||window.event),Calendar.is_ie&&document.selection.empty()},Calendar.dayMouseOver=function(a){var b=Calendar.getElement(a);return Calendar.isRelated(b,a)||Calendar._C||b.disabled?!1:(b.ttip&&("_"==b.ttip.substr(0,1)&&(b.ttip=b.caldate.print(b.calendar.ttDateFormat)+b.ttip.substr(1)),b.calendar.tooltips.innerHTML=b.ttip),300!=b.navtype&&(Calendar.addClass(b,"hilite"),b.caldate&&Calendar.addClass(b.parentNode,"rowhilite")),Calendar.stopEvent(a))},Calendar.dayMouseOut=function(ev){with(Calendar){var el=getElement(ev);return isRelated(el,ev)||_C||el.disabled?!1:(removeClass(el,"hilite"),el.caldate&&removeClass(el.parentNode,"rowhilite"),el.calendar&&(el.calendar.tooltips.innerHTML=_TT.SEL_DATE),stopEvent(ev))}},Calendar.cellClick=function(a,b){function c(a){var b=g.getDate(),c=g.getMonthDays(a);b>c&&g.setDate(c),g.setMonth(a)}var d=a.calendar,e=!1,f=!1,g=null;if("undefined"==typeof a.navtype){d.currentDateEl&&(Calendar.removeClass(d.currentDateEl,"selected"),Calendar.addClass(a,"selected"),e=d.currentDateEl==a,e||(d.currentDateEl=a)),d.date.setDateOnly(a.caldate),g=d.date;var h=!(d.dateClicked=!a.otherMonth);h||d.currentDateEl?f=!a.disabled:d._toggleMultipleDate(new Date(g)),h&&d._init(d.firstDayOfWeek,g)}else{if(200==a.navtype)return Calendar.removeClass(a,"hilite"),void d.callCloseHandler();g=new Date(d.date),0==a.navtype&&g.setDateOnly(new Date),d.dateClicked=!1;var i=g.getFullYear(),j=g.getMonth();switch(a.navtype){case 400:Calendar.removeClass(a,"hilite");var k=Calendar._TT.ABOUT;return"undefined"!=typeof k?k+=d.showsTime?Calendar._TT.ABOUT_TIME:"":k='Help and about box text is not translated into this language.\nIf you know this language and you feel generous please update\nthe corresponding file in "lang" subdir to match calendar-en.js\nand send it back to <mihai_bazon@yahoo.com> to get it into the distribution  ;-)\n\nThank you!\nhttp://dynarch.com/mishoo/calendar.epl\n',void alert(k);case-2:i>d.minYear&&g.setFullYear(i-1);break;case-1:j>0?c(j-1):i-->d.minYear&&(g.setFullYear(i),c(11));break;case 1:11>j?c(j+1):i<d.maxYear&&(g.setFullYear(i+1),c(0));break;case 2:i<d.maxYear&&g.setFullYear(i+1);break;case 100:return void d.setFirstDayOfWeek(a.fdow);case 50:for(var l=a._range,m=a.innerHTML,n=l.length;--n>=0&&l[n]!=m;);b&&b.shiftKey?--n<0&&(n=l.length-1):++n>=l.length&&(n=0);var o=l[n];return a.innerHTML=o,void d.onUpdateTime();case 0:if("function"==typeof d.getDateStatus&&d.getDateStatus(g,g.getFullYear(),g.getMonth(),g.getDate()))return!1}g.equalsTo(d.date)?0==a.navtype&&(f=e=!0):(d.setDate(g),f=!0)}f&&b&&d.callHandler(),e&&(Calendar.removeClass(a,"hilite"),b&&d.callCloseHandler())},Calendar.prototype.create=function(a){var b=null;a?(b=a,this.isPopup=!1):(b=document.getElementsByTagName("body")[0],this.isPopup=!0),this.date=this.dateStr?new Date(this.dateStr):new Date;var c=Calendar.createElement("table");this.table=c,c.cellSpacing=0,c.cellPadding=0,c.calendar=this,Calendar.addEvent(c,"mousedown",Calendar.tableMouseDown);var d=Calendar.createElement("div");this.element=d,d.className="calendar",this.isPopup&&(d.style.position="absolute",d.style.display="none"),d.appendChild(c);var e=Calendar.createElement("thead",c),f=null,g=null,h=this,i=function(a,b,c){return f=Calendar.createElement("td",g),f.colSpan=b,f.className="button",0!=c&&Math.abs(c)<=2&&(f.className+=" nav"),Calendar._add_evs(f),f.calendar=h,f.navtype=c,f.innerHTML="<div unselectable='on'>"+a+"</div>",f};g=Calendar.createElement("tr",e);var j=6;this.isPopup&&--j,this.weekNumbers&&++j,i("?",1,400).ttip=Calendar._TT.INFO,this.title=i("",j,300),this.title.className="title",this.isPopup&&(this.title.ttip=Calendar._TT.DRAG_TO_MOVE,this.title.style.cursor="move",i("&#x00d7;",1,200).ttip=Calendar._TT.CLOSE),g=Calendar.createElement("tr",e),g.className="headrow",this._nav_py=i("&#x00ab;",1,-2),this._nav_py.ttip=Calendar._TT.PREV_YEAR,this._nav_pm=i("&#x2039;",1,-1),this._nav_pm.ttip=Calendar._TT.PREV_MONTH,this._nav_now=i(Calendar._TT.TODAY,this.weekNumbers?4:3,0),this._nav_now.ttip=Calendar._TT.GO_TODAY,this._nav_nm=i("&#x203a;",1,1),this._nav_nm.ttip=Calendar._TT.NEXT_MONTH,this._nav_ny=i("&#x00bb;",1,2),this._nav_ny.ttip=Calendar._TT.NEXT_YEAR,g=Calendar.createElement("tr",e),g.className="daynames",this.weekNumbers&&(f=Calendar.createElement("td",g),f.className="name wn",f.innerHTML=Calendar._TT.WK);for(var k=7;k>0;--k)f=Calendar.createElement("td",g),k||(f.navtype=100,f.calendar=this,Calendar._add_evs(f));this.firstdayname=this.weekNumbers?g.firstChild.nextSibling:g.firstChild,this._displayWeekdays();var l=Calendar.createElement("tbody",c);for(this.tbody=l,k=6;k>0;--k){g=Calendar.createElement("tr",l),this.weekNumbers&&(f=Calendar.createElement("td",g));for(var m=7;m>0;--m)f=Calendar.createElement("td",g),f.calendar=this,Calendar._add_evs(f)}this.showsTime?(g=Calendar.createElement("tr",l),g.className="time",f=Calendar.createElement("td",g),f.className="time",f.colSpan=2,f.innerHTML=Calendar._TT.TIME||"&nbsp;",f=Calendar.createElement("td",g),f.className="time",f.colSpan=this.weekNumbers?4:3,function(){function a(a,b,c,d){var e=Calendar.createElement("span",f);if(e.className=a,e.innerHTML=b,e.calendar=h,e.ttip=Calendar._TT.TIME_PART,e.navtype=50,e._range=[],"number"!=typeof c)e._range=c;else for(var g=c;d>=g;++g){var i;i=10>g&&d>=10?"0"+g:""+g,e._range[e._range.length]=i}return Calendar._add_evs(e),e}var b=h.date.getHours(),c=h.date.getMinutes(),d=!h.time24,e=b>12;d&&e&&(b-=12);var i=a("hour",b,d?1:0,d?12:23),j=Calendar.createElement("span",f);j.innerHTML=":",j.className="colon";var k=a("minute",c,0,59),l=null;f=Calendar.createElement("td",g),f.className="time",f.colSpan=2,d?l=a("ampm",e?"pm":"am",["am","pm"]):f.innerHTML="&nbsp;",h.onSetTime=function(){var a,b=this.date.getHours(),c=this.date.getMinutes();d&&(a=b>=12,a&&(b-=12),0==b&&(b=12),l.innerHTML=a?"pm":"am"),i.innerHTML=10>b?"0"+b:b,k.innerHTML=10>c?"0"+c:c},h.onUpdateTime=function(){var a=this.date,b=parseInt(i.innerHTML,10);d&&(/pm/i.test(l.innerHTML)&&12>b?b+=12:/am/i.test(l.innerHTML)&&12==b&&(b=0));var c=a.getDate(),e=a.getMonth(),f=a.getFullYear();a.setHours(b),a.setMinutes(parseInt(k.innerHTML,10)),a.setFullYear(f),a.setMonth(e),a.setDate(c),this.dateClicked=!1,this.callHandler()}}()):this.onSetTime=this.onUpdateTime=function(){};var n=Calendar.createElement("tfoot",c);for(g=Calendar.createElement("tr",n),g.className="footrow",f=i(Calendar._TT.SEL_DATE,this.weekNumbers?8:7,300),f.className="ttip",this.isPopup&&(f.ttip=Calendar._TT.DRAG_TO_MOVE,f.style.cursor="move"),this.tooltips=f,d=Calendar.createElement("div",this.element),this.monthsCombo=d,d.className="combo",k=0;k<Calendar._MN.length;++k){var o=Calendar.createElement("div");o.className=Calendar.is_ie?"label-IEfix":"label",o.month=k,o.innerHTML=Calendar._SMN[k],d.appendChild(o)}for(d=Calendar.createElement("div",this.element),this.yearsCombo=d,d.className="combo",k=12;k>0;--k){var p=Calendar.createElement("div");p.className=Calendar.is_ie?"label-IEfix":"label",d.appendChild(p)}this._init(this.firstDayOfWeek,this.date),b.appendChild(this.element)},Calendar._keyEvent=function(a){function b(){l=e.currentDateEl;var a=l.pos;i=15&a,j=a>>4,k=e.ar_days[j][i]}function c(){var a=new Date(e.date);a.setDate(a.getDate()-m),e.setDate(a)}function d(){var a=new Date(e.date);a.setDate(a.getDate()+m),e.setDate(a)}var e=window._dynarch_popupCalendar;if(!e||e.multiple)return!1;Calendar.is_ie&&(a=window.event);var f=Calendar.is_ie||"keypress"==a.type,g=a.keyCode;if(a.ctrlKey)switch(g){case 37:f&&Calendar.cellClick(e._nav_pm);break;case 38:f&&Calendar.cellClick(e._nav_py);break;case 39:f&&Calendar.cellClick(e._nav_nm);break;case 40:f&&Calendar.cellClick(e._nav_ny);break;default:return!1}else switch(g){case 32:Calendar.cellClick(e._nav_now);break;case 27:f&&e.callCloseHandler();break;case 37:case 38:case 39:case 40:if(f){var h,i,j,k,l,m;for(h=37==g||38==g,m=37==g||39==g?1:7,b();;){switch(g){case 37:if(!(--i>=0)){i=6,g=38;continue}k=e.ar_days[j][i];break;case 38:--j>=0?k=e.ar_days[j][i]:(c(),b());break;case 39:if(!(++i<7)){i=0,g=40;continue}k=e.ar_days[j][i];break;case 40:++j<e.ar_days.length?k=e.ar_days[j][i]:(d(),b())}break}k&&(k.disabled?h?c():d():Calendar.cellClick(k))}break;case 13:f&&Calendar.cellClick(e.currentDateEl,a);break;default:return!1}return Calendar.stopEvent(a)},Calendar.prototype._init=function(a,b){var c=new Date,d=c.getFullYear(),e=c.getMonth(),f=c.getDate();this.table.style.visibility="hidden";var g=b.getFullYear();g<this.minYear?(g=this.minYear,b.setFullYear(g)):g>this.maxYear&&(g=this.maxYear,b.setFullYear(g)),this.firstDayOfWeek=a,this.date=new Date(b);{var h=b.getMonth(),i=b.getDate();b.getMonthDays()}b.setDate(1);var j=(b.getDay()-this.firstDayOfWeek)%7;0>j&&(j+=7),b.setDate(-j),b.setDate(b.getDate()+1);for(var k=this.tbody.firstChild,l=(Calendar._SMN[h],this.ar_days=new Array),m=Calendar._TT.WEEKEND,n=this.multiple?this.datesCells={}:null,o=0;6>o;++o,k=k.nextSibling){var p=k.firstChild;this.weekNumbers&&(p.className="day wn",p.innerHTML=b.getWeekNumber(),p=p.nextSibling),k.className="daysrow";for(var q,r=!1,s=l[o]=[],t=0;7>t;++t,p=p.nextSibling,b.setDate(q+1)){q=b.getDate();var u=b.getDay();p.className="day",p.pos=o<<4|t,s[t]=p;var v=b.getMonth()==h;if(v)p.otherMonth=!1,r=!0;else{if(!this.showsOtherMonths){p.className="emptycell",p.innerHTML="&nbsp;",p.disabled=!0;continue}p.className+=" othermonth",p.otherMonth=!0}if(p.disabled=!1,p.innerHTML=this.getDateText?this.getDateText(b,q):q,n&&(n[b.print("%Y%m%d")]=p),this.getDateStatus){var w=this.getDateStatus(b,g,h,q);if(this.getDateToolTip){var x=this.getDateToolTip(b,g,h,q);x&&(p.title=x)}w===!0?(p.className+=" disabled",p.disabled=!0):(/disabled/i.test(w)&&(p.disabled=!0),p.className+=" "+w)}p.disabled||(p.caldate=new Date(b),p.ttip="_",!this.multiple&&v&&q==i&&this.hiliteToday&&(p.className+=" selected",this.currentDateEl=p),b.getFullYear()==d&&b.getMonth()==e&&q==f&&(p.className+=" today",p.ttip+=Calendar._TT.PART_TODAY),-1!=m.indexOf(u.toString())&&(p.className+=p.otherMonth?" oweekend":" weekend"))}r||this.showsOtherMonths||(k.className="emptyrow")}this.title.innerHTML=Calendar._MN[h]+", "+g,this.onSetTime(),this.table.style.visibility="visible",this._initMultipleDates()},Calendar.prototype._initMultipleDates=function(){if(this.multiple)for(var a in this.multiple){var b=this.datesCells[a],c=this.multiple[a];c&&b&&(b.className+=" selected")}},Calendar.prototype._toggleMultipleDate=function(a){if(this.multiple){var b=a.print("%Y%m%d"),c=this.datesCells[b];if(c){var d=this.multiple[b];d?(Calendar.removeClass(c,"selected"),delete this.multiple[b]):(Calendar.addClass(c,"selected"),this.multiple[b]=a)}}},Calendar.prototype.setDateToolTipHandler=function(a){this.getDateToolTip=a},Calendar.prototype.setDate=function(a){a.equalsTo(this.date)||this._init(this.firstDayOfWeek,a)},Calendar.prototype.refresh=function(){this._init(this.firstDayOfWeek,this.date)},Calendar.prototype.setFirstDayOfWeek=function(a){this._init(a,this.date),this._displayWeekdays()},Calendar.prototype.setDateStatusHandler=Calendar.prototype.setDisabledHandler=function(a){this.getDateStatus=a},Calendar.prototype.setRange=function(a,b){this.minYear=a,this.maxYear=b},Calendar.prototype.callHandler=function(){this.onSelected&&this.onSelected(this,this.date.print(this.dateFormat))},Calendar.prototype.callCloseHandler=function(){this.onClose&&this.onClose(this),this.hideShowCovered()},Calendar.prototype.destroy=function(){var a=this.element.parentNode;a.removeChild(this.element),Calendar._C=null,window._dynarch_popupCalendar=null},Calendar.prototype.reparent=function(a){var b=this.element;b.parentNode.removeChild(b),a.appendChild(b)},Calendar._checkCalendar=function(a){var b=window._dynarch_popupCalendar;if(!b)return!1;for(var c=Calendar.is_ie?Calendar.getElement(a):Calendar.getTargetElement(a);null!=c&&c!=b.element;c=c.parentNode);return null==c?(window._dynarch_popupCalendar.callCloseHandler(),Calendar.stopEvent(a)):void 0},Calendar.prototype.show=function(){for(var a=this.table.getElementsByTagName("tr"),b=a.length;b>0;){var c=a[--b];Calendar.removeClass(c,"rowhilite");for(var d=c.getElementsByTagName("td"),e=d.length;e>0;){var f=d[--e];Calendar.removeClass(f,"hilite"),Calendar.removeClass(f,"active")}}this.element.style.display="block",this.hidden=!1,this.isPopup&&(window._dynarch_popupCalendar=this,Calendar.addEvent(document,"keydown",Calendar._keyEvent),Calendar.addEvent(document,"keypress",Calendar._keyEvent),Calendar.addEvent(document,"mousedown",Calendar._checkCalendar)),this.hideShowCovered()},Calendar.prototype.hide=function(){this.isPopup&&(Calendar.removeEvent(document,"keydown",Calendar._keyEvent),Calendar.removeEvent(document,"keypress",Calendar._keyEvent),Calendar.removeEvent(document,"mousedown",Calendar._checkCalendar)),this.element.style.display="none",this.hidden=!0,this.hideShowCovered()},Calendar.prototype.showAt=function(a,b){var c=this.element.style;c.left=a+"px",c.top=b+"px",this.show()},Calendar.prototype.showAtElement=function(a,b){function c(a){a.x<0&&(a.x=0),a.y<0&&(a.y=0);var b=document.createElement("div"),c=b.style;c.position="absolute",c.right=c.bottom=c.width=c.height="0px",document.body.appendChild(b);var d=Calendar.getAbsolutePos(b);document.body.removeChild(b),Calendar.is_ie?(d.y+=document.body.scrollTop,d.x+=document.body.scrollLeft):(d.y+=window.scrollY,d.x+=window.scrollX);var e=a.x+a.width-d.x;e>0&&(a.x-=e),e=a.y+a.height-d.y,e>0&&(a.y-=e)}var d=this,e=Calendar.getAbsolutePos(a);return b&&"string"==typeof b?(this.element.style.display="block",Calendar.continuation_for_the_fucking_khtml_browser=function(){var f=d.element.offsetWidth,g=d.element.offsetHeight;d.element.style.display="none";var h=b.substr(0,1),i="l";switch(b.length>1&&(i=b.substr(1,1)),h){case"T":e.y-=g;break;case"B":e.y+=a.offsetHeight;break;case"C":e.y+=(a.offsetHeight-g)/2;break;case"t":e.y+=a.offsetHeight-g;break;case"b":}switch(i){case"L":e.x-=f;break;case"R":e.x+=a.offsetWidth;break;case"C":e.x+=(a.offsetWidth-f)/2;break;case"l":e.x+=a.offsetWidth-f;break;case"r":}e.width=f,e.height=g+40,d.monthsCombo.style.display="none",c(e),d.showAt(e.x,e.y)},void(Calendar.is_khtml?setTimeout("Calendar.continuation_for_the_fucking_khtml_browser()",10):Calendar.continuation_for_the_fucking_khtml_browser())):(this.showAt(e.x,e.y+a.offsetHeight),!0)},Calendar.prototype.setDateFormat=function(a){this.dateFormat=a},Calendar.prototype.setTtDateFormat=function(a){this.ttDateFormat=a},Calendar.prototype.parseDate=function(a,b){b||(b=this.dateFormat),this.setDate(Date.parseDate(a,b))},Calendar.prototype.hideShowCovered=function(){function a(a){var b=a.style.visibility;return b||(b=document.defaultView&&"function"==typeof document.defaultView.getComputedStyle?Calendar.is_khtml?"":document.defaultView.getComputedStyle(a,"").getPropertyValue("visibility"):a.currentStyle?a.currentStyle.visibility:""),b}if(Calendar.is_ie||Calendar.is_opera)for(var b=new Array("applet","iframe","select"),c=this.element,d=Calendar.getAbsolutePos(c),e=d.x,f=c.offsetWidth+e,g=d.y,h=c.offsetHeight+g,i=b.length;i>0;)for(var j=document.getElementsByTagName(b[--i]),k=null,l=j.length;l>0;){k=j[--l],d=Calendar.getAbsolutePos(k);var m=d.x,n=k.offsetWidth+m,o=d.y,p=k.offsetHeight+o;this.hidden||m>f||e>n||o>h||g>p?(k.__msh_save_visibility||(k.__msh_save_visibility=a(k)),k.style.visibility=k.__msh_save_visibility):(k.__msh_save_visibility||(k.__msh_save_visibility=a(k)),k.style.visibility="hidden")}},Calendar.prototype._displayWeekdays=function(){for(var a=this.firstDayOfWeek,b=this.firstdayname,c=Calendar._TT.WEEKEND,d=0;7>d;++d){b.className="day name";var e=(d+a)%7;d&&(b.ttip=Calendar._TT.DAY_FIRST.replace("%s",Calendar._DN[e]),b.navtype=100,b.calendar=this,b.fdow=e,Calendar._add_evs(b)),-1!=c.indexOf(e.toString())&&Calendar.addClass(b,"weekend"),b.innerHTML=Calendar._SDN[(d+a)%7],b=b.nextSibling}},Calendar.prototype._hideCombos=function(){this.monthsCombo.style.display="none",this.yearsCombo.style.display="none"},Calendar.prototype._dragStart=function(ev){if(!this.dragging){this.dragging=!0;var posX,posY;Calendar.is_ie?(posY=window.event.clientY+document.body.scrollTop,posX=window.event.clientX+document.body.scrollLeft):(posY=ev.clientY+window.scrollY,posX=ev.clientX+window.scrollX);var st=this.element.style;with(this.xOffs=posX-parseInt(st.left),this.yOffs=posY-parseInt(st.top),Calendar)addEvent(document,"mousemove",calDragIt),addEvent(document,"mouseup",calDragEnd)}},Date._MD=new Array(31,28,31,30,31,30,31,31,30,31,30,31),Date.SECOND=1e3,Date.MINUTE=60*Date.SECOND,Date.HOUR=60*Date.MINUTE,Date.DAY=24*Date.HOUR,Date.WEEK=7*Date.DAY,Date.parseDate=function(a,b){var c=new Date,d=0,e=-1,f=0,g=a.split(/\W+/),h=b.match(/%./g),i=0,j=0,k=0,l=0;for(i=0;i<g.length;++i)if(g[i])switch(h[i]){case"%d":case"%e":f=parseInt(g[i],10);break;case"%m":e=parseInt(g[i],10)-1;break;case"%Y":case"%y":d=parseInt(g[i],10),100>d&&(d+=d>29?1900:2e3);break;case"%b":case"%B":for(j=0;12>j;++j)if(Calendar._MN[j].substr(0,g[i].length).toLowerCase()==g[i].toLowerCase()){e=j;break}break;case"%H":case"%I":case"%k":case"%l":k=parseInt(g[i],10);break;case"%P":case"%p":/pm/i.test(g[i])&&12>k?k+=12:/am/i.test(g[i])&&k>=12&&(k-=12);break;case"%M":l=parseInt(g[i],10)}if(isNaN(d)&&(d=c.getFullYear()),isNaN(e)&&(e=c.getMonth()),isNaN(f)&&(f=c.getDate()),isNaN(k)&&(k=c.getHours()),isNaN(l)&&(l=c.getMinutes()),0!=d&&-1!=e&&0!=f)return new Date(d,e,f,k,l,0);for(d=0,e=-1,f=0,i=0;i<g.length;++i)if(-1!=g[i].search(/[a-zA-Z]+/)){var m=-1;for(j=0;12>j;++j)if(Calendar._MN[j].substr(0,g[i].length).toLowerCase()==g[i].toLowerCase()){m=j;break}-1!=m&&(-1!=e&&(f=e+1),e=m)}else parseInt(g[i],10)<=12&&-1==e?e=g[i]-1:parseInt(g[i],10)>31&&0==d?(d=parseInt(g[i],10),100>d&&(d+=d>29?1900:2e3)):0==f&&(f=g[i]);return 0==d&&(d=c.getFullYear()),-1!=e&&0!=f?new Date(d,e,f,k,l,0):c},Date.prototype.getMonthDays=function(a){var b=this.getFullYear();return"undefined"==typeof a&&(a=this.getMonth()),0!=b%4||0==b%100&&0!=b%400||1!=a?Date._MD[a]:29},Date.prototype.getDayOfYear=function(){var a=new Date(this.getFullYear(),this.getMonth(),this.getDate(),0,0,0),b=new Date(this.getFullYear(),0,0,0,0,0),c=a-b;return Math.floor(c/Date.DAY)},Date.prototype.getWeekNumber=function(){var a=new Date(this.getFullYear(),this.getMonth(),this.getDate(),0,0,0),b=a.getDay();a.setDate(a.getDate()-(b+6)%7+3);var c=a.valueOf();return a.setMonth(0),a.setDate(4),Math.round((c-a.valueOf())/6048e5)+1},Date.prototype.equalsTo=function(a){return this.getFullYear()==a.getFullYear()&&this.getMonth()==a.getMonth()&&this.getDate()==a.getDate()&&this.getHours()==a.getHours()&&this.getMinutes()==a.getMinutes()},Date.prototype.setDateOnly=function(a){var b=new Date(a);this.setDate(1),this.setFullYear(b.getFullYear()),this.setMonth(b.getMonth()),this.setDate(b.getDate())},Date.prototype.print=function(a){var b=this.getMonth(),c=this.getDate(),d=this.getFullYear(),e=this.getWeekNumber(),f=this.getDay(),g={},h=this.getHours(),i=h>=12,j=i?h-12:h,k=this.getDayOfYear();0==j&&(j=12);var l=this.getMinutes(),m=this.getSeconds();g["%a"]=Calendar._SDN[f],g["%A"]=Calendar._DN[f],g["%b"]=Calendar._SMN[b],g["%B"]=Calendar._MN[b],g["%C"]=1+Math.floor(d/100),g["%d"]=10>c?"0"+c:c,g["%e"]=c,g["%H"]=10>h?"0"+h:h,g["%I"]=10>j?"0"+j:j,g["%j"]=100>k?10>k?"00"+k:"0"+k:k,g["%k"]=h,g["%l"]=j,g["%m"]=9>b?"0"+(1+b):1+b,g["%M"]=10>l?"0"+l:l,g["%n"]="\n",g["%p"]=i?"PM":"AM",g["%P"]=i?"pm":"am",g["%s"]=Math.floor(this.getTime()/1e3),g["%S"]=10>m?"0"+m:m,g["%t"]="	",g["%U"]=g["%W"]=g["%V"]=10>e?"0"+e:e,g["%u"]=f+1,g["%w"]=f,g["%y"]=(""+d).substr(2,2),g["%Y"]=d,g["%%"]="%";var n=/%./g;if(!Calendar.is_ie5&&!Calendar.is_khtml)return a.replace(n,function(a){return g[a]||a});for(var o=a.match(n),p=0;p<o.length;p++){var q=g[o[p]];q&&(n=new RegExp(o[p],"g"),a=a.replace(n,q))}return a},Date.prototype.__msh_oldSetFullYear=Date.prototype.setFullYear,Date.prototype.setFullYear=function(a){var b=new Date(this);b.__msh_oldSetFullYear(a),b.getMonth()!=this.getMonth()&&this.setDate(28),this.__msh_oldSetFullYear(a)},window._dynarch_popupCalendar=null,Calendar._DN=new Array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"),Calendar._SDN=new Array("Sun","Mon","Tue","Wed","Thu","Fri","Sat","Sun"),Calendar._FD=0,Calendar._MN=new Array("January","February","March","April","May","June","July","August","September","October","November","December"),Calendar._SMN=new Array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"),Calendar._TT={},Calendar._TT.INFO="About the calendar",Calendar._TT.ABOUT="DHTML Date/Time Selector\n(c) dynarch.com 2002-2005 / Author: Mihai Bazon\nFor latest version visit: http://www.dynarch.com/projects/calendar/\nDistributed under GNU LGPL.  See http://gnu.org/licenses/lgpl.html for details.\n\nDate selection:\n- Use the «, » buttons to select year\n- Use the "+String.fromCharCode(8249)+", "+String.fromCharCode(8250)+" buttons to select month\n- Hold mouse button on any of the above buttons for faster selection.",Calendar._TT.ABOUT_TIME="\n\nTime selection:\n- Click on any of the time parts to increase it\n- or Shift-click to decrease it\n- or click and drag for faster selection.",Calendar._TT.PREV_YEAR="Prev. year (hold for menu)",Calendar._TT.PREV_MONTH="Prev. month (hold for menu)",Calendar._TT.GO_TODAY="Go Today",Calendar._TT.NEXT_MONTH="Next month (hold for menu)",Calendar._TT.NEXT_YEAR="Next year (hold for menu)",Calendar._TT.SEL_DATE="Select date",Calendar._TT.DRAG_TO_MOVE="Drag to move",Calendar._TT.PART_TODAY=" (today)",Calendar._TT.DAY_FIRST="Display %s first",Calendar._TT.WEEKEND="0,6",Calendar._TT.CLOSE="Close",Calendar._TT.TODAY="Today",Calendar._TT.TIME_PART="(Shift-)Click or drag to change value",Calendar._TT.DEF_DATE_FORMAT="%Y-%m-%d",Calendar._TT.TT_DATE_FORMAT="%a, %b %e",Calendar._TT.WK="wk",Calendar._TT.TIME="Time:";