import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  subtests;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getSubtests();
  }

  getSubtests() {
    this.service.get('master/test.php?type=getPendingSubTests')
    .subscribe(response => {
      this.subtests = response;
    });
  }
  
  update(status,id){
    this.service.get('master/test.php?type=updateSubTest&status='+status+'&id='+id).subscribe(response=>{
      if(response['status']=='success'){
        this.getSubtests();
        alertify.success("update successfully")
      }else{
        alertify.error("Failed:error occured");
      }
    });
  
  }

}
