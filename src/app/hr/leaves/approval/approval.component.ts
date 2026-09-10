import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  results;

  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingLeaves();
  }

  getPendingLeaves(){
    this.service.get('hr/leave.php?type=getPendingLeaves').subscribe(response => {
      this.results = response;
    });
  }

  update(status,id){
    this.service.get('hr/leave.php?type=updateLeave&status=' + status + '&id=' + id).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Updated Successfully!');
        this.getPendingLeaves();
      }else{
        alertify.error('Failed an error occured,Please try again!');
      }
    });
  }

}
