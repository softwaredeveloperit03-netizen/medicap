import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isViewForm = false;
  pendinginward;
  nodes;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingInwords();
  }

  getPendingInwords() {
    this.service.get('engineering/inword.php?type=getPendingInwords').subscribe(response =>{
      this.pendinginward = response;
    });
  }

  view(index){
    this.nodes = this.pendinginward[index];
    this.isViewForm = true;
  }

  updateapprovel(status){
    this.service.get('engineering/inword.php?type=updateInword&status='+status+'&id='+this.nodes['id']).subscribe(response => {
      if(response['status']=='success'){
      alertify.success('Record Updated Successfully');      
      this.isViewForm = false;
      this.getPendingInwords();
      }
      else{
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  }
