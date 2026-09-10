import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-charge-acceptance',
  templateUrl: './charge-acceptance.component.html',
  styleUrls: ['./charge-acceptance.component.css']
})
export class ChargeAcceptanceComponent implements OnInit {

  selectedResult= [];
  isView = false;
  results;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getApprovedLeave();
  }

  getApprovedLeave() {
    this.service.get('hr/leaveForm.php?type=chargeacceptanceLog').subscribe(response => {
      this.results = response;
    });
  }

  pending
 
  update(val){
  
    this.service.post('hr/leaveForm.php?type=update_charge&status='+val+'&lid='+this.selectedResult["id"], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        this.ismodified=false;
        this.getApprovedLeave();                          
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });

 
   }

    ismodified=false;
    ismodified1=false;
   
    errorCorrection(index) {
      this.selectedResult = this.results[index];
      this.pending = JSON.parse(this.selectedResult['pendingList']);
       this.ismodified = true;
    }
    errorCorrection1(index) {
      this.selectedResult = this.results[index];
      this.pending = JSON.parse(this.selectedResult['pendingList']);
       this.ismodified1 = true;
    }
}
