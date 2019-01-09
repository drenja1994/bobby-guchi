function register(){

	var greske=new Array();

	var reIme=/^[A-Z]{1}[a-z]{2,20}$/;
	var rePrez=/^[A-Z]{1}[a-z]{2,20}$/;
	var reEmail=/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/;
	var rekorime=/^[A-z0-9'_''-''/''!''+''\''@''^']{5,15}$/;
	var repassword=/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{6,}$/;

	var ime=document.getElementById('tbIme1');
	var prezime=document.getElementById('tbPrezime1');
	var korime=document.getElementById('tbUsername');
	var email=document.getElementById('tbEmail1');
	var password=document.getElementById('tbPass');
	

	if(!ime.value.match(reIme)){
		document.getElementById('tbIme1').style.border = '2px solid red';
		greske.push("Ime nije u dobrom formatu!");
	}
	else {
		document.getElementById('tbIme1').style.border = '2px solid green';
	}

	if(!prezime.value.match(rePrez)){
		document.getElementById('tbPrezime1').style.border = '2px solid red';
		greske.push("Prezime nije u dobrom formatu!");
	}
	else {
		document.getElementById('tbPrezime1').style.border = '2px solid green';
	}
    if(!korime.value.match(rekorime)){
		document.getElementById('tbUsername').style.border = '2px solid red';
		greske.push("Korisničko ime nije u dobrom formatu!");
	}
	else {
		document.getElementById('tbUsername').style.border = '2px solid green';
	}
	 if(!password.value.match(repassword)){
		document.getElementById('tbPass').style.border = '2px solid red';
		greske.push("Šifra nije u dobrom formatu!");
	}
	else {
		document.getElementById('tbPass').style.border = '2px solid green';
	}
	if(!email.value.match(reEmail)){
		document.getElementById('tbEmail1').style.border = '2px solid red';
		greske.push("Email nije u dobrom formatu!");
	}
	else {
		document.getElementById('tbEmail1').style.border = '2px solid green';
	}

	

	
	if(greske.length!=0){
		var obavestenje="";
		for(var i=0;i<greske.length;i++){
			obavestenje+=greske[i]+"\n";
		}
		alert(obavestenje);
	}
	

}

