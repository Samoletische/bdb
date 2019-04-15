var animD = 1000;
var dAnim = 10;
var rings;
var timer;

addEventListener('load', function(e) {

	VK.init({apiId: 6770995});
	timer = setTimeout(checkLoginStatus, 100);

});
//-----------------------------------------------

function checkLoginStatus() {
	
	VK.Auth.getLoginStatus(authVK);
	
}
//-----------------------------------------------

function authVK(response) {
	
	if (response.status == "connected") {
		console.log("connected!!!");
		$("#vk_auth").hide();
		VK.Widgets.CommunityMessages("vk_community_messages", 174086337, {tooltipButtonText: "Сообщения здесь ->"});
	    rings = new Rings($("#rings")
	    	.show()
	    	.get(0));
	    startPosition();
	    timer = setTimeout(update, 100);
	    $(".amount").click(update);
	    $(window).resize(startPosition);
	}
	else {
		console.log(response.status);
		//clearTimeout(timer);
		VK.Widgets.Auth("vk_auth", {"authUrl":"/bdb/daf.html"});
		//timer = setTimeout(checkLoginStatus, 1000);
	}
}
//-----------------------------------------------

function Rings(div) {

	this.div = div;
	this.pers = new Array(3);
	this.currPers = new Array(3);
	var c;

	for (c = 0; c < 3; c++)
		this.pers[c] = this.currPers[c] = 0;

	var padding = 3;
	var mnozh = 16;
	var divWidth = $(this.div).width();
	var strokeWidth = divWidth / mnozh;

	var radius = new Array(3);
	radius[0] = parseInt((divWidth - 2 * padding - strokeWidth) / 2);
	radius[1] = radius[0] - strokeWidth - padding;
	radius[2] = radius[1] - strokeWidth - padding;

	var colors = new Array(6);
	colors[0] = "#FA114F";
	colors[1] = "#9AFF01";
	colors[2] = "#00D9FD";
	colors[3] = "#200002";
    colors[4] = "#002200";
    colors[5] = "#002020";

	var x = parseInt(divWidth / 2);

	var ys = new Array(3);
	ys[0] = padding + parseInt(strokeWidth / 2);
	ys[1] = ys[0] + padding + strokeWidth;
	ys[2] = ys[1] + padding + strokeWidth;

    // adding svg & amount
    $(this.div).html("<svg id='svgRings'></svg>");
    this.svg = $("#svgRings");

    // adding paths
	var html = "";
	for (c = 3; c < 6; c++)
   	    html += "<path num='" + c + " class='iRing' d='M"+x+" "+ys[c-3]+" A"+radius[c-3]+" "+radius[c-3]+" 0 1 1 "+x+" "+(ys[c-3]+2*radius[c-3])+" A"+radius[c-3]+" "+radius[c-3]+" 0 0 1 "+x+" "+ys[c-3]+"' style='stroke: "+colors[c]+"; stroke-width: "+strokeWidth+"; stroke-linecap: round;'/>";
	for (c = 0; c < 3; c++)
		html += "<path num='" + c + " class='iRing' d='M"+x+" "+ys[c]+" A"+radius[c]+" "+radius[c]+" 0 1 1 "+x+" "+(ys[c]+2*radius[c])+" A"+radius[c]+" "+radius[c]+" 0 0 1 "+x+" "+ys[c]+"' style='stroke: "+colors[c]+"; stroke-width: "+strokeWidth+"; stroke-linecap: round;'/>";

	//console.log("html = "+html);
	$("#svgRings")
		.css("fill", "none")
		.html(html);

	this.pathMass = new Array(3);
	var pm = $("#svgRings").find("path");

	for (c = 0; c < 3; c++) {
        this.pathMass[c] = pm.get(c+3);
        this.pathMass[c].style.strokeDasharray = parseInt(this.pathMass[c].getTotalLength());
        this.pathMass[c].style.strokeDashoffset = this.pathMass[c].style.strokeDasharray;
    }

	this.update = function(periodPercent, incomePercent, profitPercent) {
	    this.pers[0] = periodPercent;
	    this.pers[1] = incomePercent;
	    this.pers[2] = profitPercent;

        setTimeout(loading, dAnim);
	}

	this.loading = function() {

		var c, mayExit = true;

		for (c = 0; c < 3; c++) {
			//console.log(c + " - " + this.currPers[c] + " - " + this.pers[c] + " - " + (this.currPers[c] == this.pers[c]));
			if (this.currPers[c] == this.pers[c])
				continue;
			if (this.currPers[c] == 100) {
				this.currPers[c] = 99;
                continue;
			}

            //console.log(c + " - " + this.currPers[c] + " - " + this.pers[c]);
			if (this.currPers[c] < this.pers[c])
				this.currPers[c] += 1;
			if (this.currPers[c] > this.pers[c])
				this.currPers[c] -= 1;
			if (this.currPers[c] === 0)
				this.pathMass[c].style.strokeDashoffset = this.pathMass[c].style.strokeDasharray;
			else
				this.pathMass[c].style.strokeDashoffset = parseInt((1.0 - this.currPers[c] / 100.0) * parseInt(this.pathMass[c].style.strokeDasharray));
			//console.log(this.pathMass[c].style.strokeDashoffset + " --- " + this.pathMass[c].style.strokeDasharray);

			mayExit = false;
        }

		if (!mayExit)
		    setTimeout(loading, dAnim);

	}

}
//-----------------------------------------------

function loading() {

	rings.loading();

}
//-----------------------------------------------

function startPosition() {

    var os = $("#rings").offset();
    var w = $("#rings").width();
    var h = $("#rings").height();

    $("div.period")
        .css("left", os.left)
        .css("top", os.top);
    $("div.income")
        .css("left", os.left + w - $("div.income").width() - 2 * parseInt($("div.income").css("padding-right")))
        .css("top", os.top);
    $("div.profit")
            .css("left", os.left + w - $("div.profit").width() - 2 * parseInt($("div.income").css("padding-right")))
            .css("top", os.top + h - $("div.profit").height());

}
//-----------------------------------------------

function update() {

	//console.log(new Date() + " - update");
	clearTimeout(timer);
	
    $.post("bdb.php", "command=getPurseSummaryData&userID=" + $("#rings").attr("uid"), function(data) {
        console.log(data);
        var dat = data.split("-=-");

        if (dat[0] == ".") {
            alert(dat[1]);
        }
        else {
            if ($("#wrap > div.amount").text() != dat[1])
                $("#wrap > div.amount")
                    .fadeOut("fast")
                    .fadeIn("fast")
                    .text(dat[1]);
            if ($("#wrap > div.period").text() != dat[2])
                $("#wrap > div.period")
                    .fadeOut("fast")
                    .fadeIn("fast")
                    .text(dat[2]);
            if ($("#wrap > div.income").text() != dat[3])
                $("#wrap > div.income")
                    .fadeOut("fast")
                    .fadeIn("fast")
                    .text(dat[3]);
            if ($("#wrap > div.profit").text() != dat[4])
                $("#wrap > div.profit")
                    .fadeOut("fast")
                    .fadeIn("fast")
                    .text(dat[4]);
            if (dat[5])
            	rings.update(dat[5], dat[6], dat[7]);
            timer = setTimeout(update, 10000);
        }
    });

}
//-----------------------------------------------
