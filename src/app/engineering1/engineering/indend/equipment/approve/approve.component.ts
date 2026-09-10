import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  declare let alertify;
@Component({
  selector: 'app-approve',
  templateUrl: './approve.component.html',
  styleUrls: ['./approve.component.css']
})
export class ApproveComponent implements OnInit {
  
  
    isView = false;
    results;
    selectedResult: [];
    constructor(private service: DataAccessService) { }
  
    ngOnInit() {
      this.getPendingIndends();
    }
  
    getPendingIndends() {
      this.service.get('purchase/indend/equipment.php?type=getPendingIndends').subscribe(response => {
        this.results = response;
      });
    }
  
    updateIndend(status,id){
      this.service.get('purchase/indend/equipment.php?type=updateIndend&status=' + status + '&id=' + id).subscribe(response => {
        if (response['status']) {
          alertify.success('indend Updated Successfully');
          this.getPendingIndends();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
    }
  
  
   
  
  
  }
  