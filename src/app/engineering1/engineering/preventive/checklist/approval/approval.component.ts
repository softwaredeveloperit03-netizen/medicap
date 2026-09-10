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
  preventives;
  selectedResult = [];
  constructor(private service:DataAccessService){ }

  ngOnInit() {
    this.getPendingPreventives();
  }
  
  getPendingPreventives(){
    this.service.get('engineering/preventive.php?type=getPendingPreventives').subscribe(response =>{
      this.preventives = response;
    });
  }

  view(index){
    this.selectedResult = this.preventives[index];
    this.isView = true;
  }

  update(status){
    this.service.get('engineering/preventive.php?type=updatePreventive&status=' + status + '&equipment_code=' + this.selectedResult['equipment_code']).subscribe(response => {
      if(response['status']=='success'){
      alertify.success('Record Updated Successfully');      
      this.isView = false;
      this.getPendingPreventives();
      }
      else{
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
}
