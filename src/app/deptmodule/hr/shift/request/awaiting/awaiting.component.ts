import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  results = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getShiftChangeRequests();
  }

  getShiftChangeRequests() {
    this.service.get('hr/shift.php?type=shift_chnge_Approval&approval_from=HR').subscribe((response: any) => {
      this.results = response;
    });
  }


  update_status(id,status){
    
    
    



    let temp = {};
    temp['id']=id;
    temp['status']=status;
    temp['app_from']='HR';

    // temp['result']=this.result;
    this.service.post('hr/shift.php?type=update_chnge_shift_status', JSON.stringify(temp)) 
    .subscribe(response => {
      if (response['status'] === 'success') {       
        this.getShiftChangeRequests();
        alertify.success("save successfully");
      } else {
        alertify.error('Please Try Again');
      }
      })
  }


}
