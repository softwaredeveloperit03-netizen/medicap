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
    this.getRequests();
  }

  getRequests(){
    this.service.get('hr/password.php?type=getRequests').subscribe(response => {
      this.results = response;
    });
  }

  updatePassword(status,id){
    this.service.get('hr/password.php?type=updateRequest&status=' + status + '&id=' + id).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Updated Successfully!');
        this.getRequests();
      }else{
        alertify.error('Failed an error occured,Please try again!');
      }
    });
  }

}