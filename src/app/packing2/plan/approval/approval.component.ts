import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  primary=[];
  secondary=[];
  tertiary=[];


  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingPlans();
  }
  getPendingPlans(){
    this.service.get('packing/plan.php?type=getPendingPlans').subscribe(response=>{
      this.results=response;
    })
  }
  view(index){
    this.selectedResult=this.results[index];
    this.primary=this.selectedResult['primarys'];
      this.secondary=this.selectedResult['secondary']
   this.tertiary=this.selectedResult['tertiary']
    this.isView=true;
  }

  update(status) {
    this.service.get('packing/plan.php?type=updatePlan&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status']=='success') {
        alert('data Updated Successfully');
        this.isView = false;
        this.getPendingPlans();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  

}
