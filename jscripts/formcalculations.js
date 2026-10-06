 
var filling_prices= new Array();
filling_prices["None"]=0;
filling_prices["singlephase"]=0.23;
filling_prices["threephase"]=0.692;

var filling_prices2= new Array();
filling_prices2["kWh"]=0.00029307107017;
filling_prices2["BTU"]=3412.14163312794;

var opps= new Array();
opps["kWh"]="BTU";
opps["BTU"]="kWh";

function getFillingPrice()
{
    var cakeFillingPrice=0;
    var theForm = document.forms["cakeform"];
    var selectedFilling = theForm.elements["filling"];
    cakeFillingPrice = filling_prices[selectedFilling.value];
    return cakeFillingPrice;
}

function getQuantity()
{
    var theForm = document.forms["cakeform"];
    var quantity = theForm.elements["quantity"];
    var howmany =0;
    if(quantity.value!="")
    {
        howmany = parseInt(quantity.value);
    }
return howmany;
}

function calculateTotal()
{
    var cakePrice = (getQuantity() / getFillingPrice()).toFixed(3);
    var divobj4 = document.getElementById('totalPrice4');
    var divobj = document.getElementById('totalPrice');
    divobj.style.display='block';
    divobj.innerHTML = cakePrice+' Amps';
    var meters = (cakePrice/55).toFixed(3);
    divobj4.innerHTML = meters+' Meters';
}

function getFillingPrice2()
{
    var cakeFillingPrice2=0;
    var theForm2 = document.forms["cakeform2"];
    var selectedFilling2 = theForm2.elements["filling2"];
    cakeFillingPrice2 = filling_prices2[selectedFilling2.value];
    return cakeFillingPrice2;
}

function getQuantity2()
{
    var theForm2 = document.forms["cakeform2"];
    var quantity2 = theForm2.elements["quantity2"];
    var howmany2 = 0;
    if(quantity2.value!="")
    {
        howmany2 = parseInt(quantity2.value);
    }
return howmany2;
}

function calculateTotal2()
{
    var cakePrice2 =  (getQuantity2() / getFillingPrice2()).toFixed(3);
    var divobj2 = document.getElementById('totalPrice2');
    var divobj3 = document.getElementById('totalPrice3');
    var theForm2 = document.forms["cakeform2"];
    var selectedFilling2 = theForm2.elements["filling2"];
    divobj2.style.display='block';
    divobj2.innerHTML = cakePrice2+' '+opps[selectedFilling2.value];
    var meters2 = (cakePrice2/55).toFixed(3);
    divobj3.innerHTML = meters2+' Meters';
}


function hideTotal()
{
    var divobj = document.getElementById('totalPrice');
    divobj.style.display='none';
    var divobj2 = document.getElementById('totalPrice2');
    divobj2.style.display='none';
    var divobj3 = document.getElementById('totalPrice3');
    divobj3.style.display='none';
}
