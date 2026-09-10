import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;


@Component({
  selector: 'app-inhouse',
  templateUrl: './inhouse.component.html',
  styleUrls: ['./inhouse.component.css']
})
export class InhouseComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit() {
     this.getDepartments();
  }

  Trainers_data;
  getDepartments() {
    this.service.get('training.php?type=getExternalTrainersforApproval')
      .subscribe(response => {
        this.Trainers_data = response;
      });
  }
 
  isQuatation = false;
  selectedResult=[];
  view(index){

    this.selectedResult= [];

    this.selectedResult = this.Trainers_data[index];
    this.isQuatation = true;
  }



  ViewCertificate() {
   
    window.open(this.service.url+'../../upload/training/' + this.selectedResult['certificate']);
    
  }





  

   emp_id = '';
  isDIGI = false;
  isbutton = true;
  status='';
  openDigiSign(value){
    this.emp_id = localStorage.getItem('emp_id');
    this.status = value;
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
         this.save(this.status);
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }

 
  save(status) {
 

    let temp = {};

    this.service.post('training.php?type=ApproveTrainer&status='+status+'&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Saved Successfully');
        this.getDepartments();
        this.isQuatation = false;
        this.isbutton = true;
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }







 
}
