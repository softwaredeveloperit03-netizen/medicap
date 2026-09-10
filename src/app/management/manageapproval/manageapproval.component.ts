import {Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-manageapproval',
  templateUrl: './manageapproval.component.html',
  styleUrls: ['./manageapproval.component.css']
})
export class ManageapprovalComponent implements OnInit {
  emp_id: string;
  isDIGI=false
  status: any;
    Status: any;
    ID: any;

  constructor(private service: DataAccessService,private router: Router, ) {}

  ngOnInit(): void {
    this.getCandidate();
  }



  results;
  selectedCandidate=[];
  isView = false;


  getCandidate() {
    this.service.get('hr/candidate.php?type=getCandidateForManagement').subscribe(response => {
      this.results = response;
    });
  }


  view(index){
    this.selectedCandidate =this.results[index];
    this.isView = true;
  }


  ChangeManagementStatus(id,status){
    
    let temp ={};
 
    this.service.post('hr/shift.php?type=changeStatusByManagement&cid='+id+'&status1='+status, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.getCandidate()
        this.isView = false;
         } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  openDigiSign(Id,status){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.ID=Id;
    this.Status=status
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
        this.loginPassward ='';
        this.ChangeManagementStatus(this.ID,this.Status)
       

      
        
      }
      else
      {
        alertify.error('Digi-Sign Not Verified');

      }
    });
  }
  






}
