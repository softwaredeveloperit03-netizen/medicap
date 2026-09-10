import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  results;

  selectedTesting = [];
    emp_id: string;
    isDIGI: boolean=false
    status: any;
    isbutton: boolean=true
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingStabilityTestings();
  }

  getPendingStabilityTestings() {
    this.service.get('stability.php?type=getPendingApprovalStabilityTestings').subscribe(response => {
      this.results = response;
      this.isDIGI=false
    });
  }

  view(index) {
    this.selectedTesting = this.results[index];
    this.isView = true;
  }

  openDigiSign(value){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.status=value
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
        this.updateStabilityTesting(this.status)
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }
  


  updateStabilityTesting(action) {
    this.service.post('stability.php?type=updateStabilityTesting&action=' + action, JSON.stringify(this.selectedTesting)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Stability Testing Updated Successfully');
        this.isbutton=true
        
        this.isView = false;
        this.getPendingStabilityTestings();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
