import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  tests;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getTests();
  }

  getTests() {
    this.service.get('master/test.php?type=getPendingTests')
    .subscribe(response => {
      this.tests = response;
    });
  }
  update(status,id){
    this.service.get('master/test.php?type=updateTest&status='+status+'&id='+id).subscribe(response=>{
      if(response['status']=='success'){
        this.getTests();
        alertify.success("update successfully")
      }else{
        alertify.error("Failed:error occured");
      }
    });
  
  }

}
