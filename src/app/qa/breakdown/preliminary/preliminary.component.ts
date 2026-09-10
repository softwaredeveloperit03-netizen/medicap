import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-preliminary',
  templateUrl: './preliminary.component.html',
  styleUrls: ['./preliminary.component.css']
})
export class PreliminaryComponent implements OnInit {

   
  isView = false;
   results;
 
  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
     this.getbreakdown_data();
  }

  
  getbreakdown_data(){
    this.service.get('engineering/maintenance.php?type=getbreakdownForPreliminary').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  prili_req='';



   emp_id = '';
  isDIGI = false;
  isbutton = true;
  formData;
  openDigiSign(value){
    if(!value.valid){
      alertify.error("All fields are required");
      return;
    }
    this.emp_id = localStorage.getItem('emp_id');
    this.formData = value;
    this.isDIGI = true;
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }

  
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.isbutton = false;
        this.loginPassward ='';
         this.updateBreakdown(this.formData);
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }

 

  updateBreakdown(data) {

 
  
    let temp =data.value; 
 
     this.service.post('engineering/maintenance.php?type=approvepreliminary&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getbreakdown_data();
        this.isbutton = true;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


 



 
 

}
