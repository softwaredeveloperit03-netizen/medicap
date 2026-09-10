import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-send',
  templateUrl: './send.component.html',
  styleUrls: ['./send.component.css']
})
export class SendComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingBatchPlan();
  }

  getPendingBatchPlan(){
    this.service.get('production/plan.php?type=getPendingBatchPlan').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  save(){
    this.service.post('production/plan.php?type=checkBMRPlan&id='+this.selectedResult['id'],JSON.stringify(this.selectedResult)).subscribe(response=>{
      if(response['status'] == 'success'){
        alert('Data send to QA Approval');
        this.isView=false;
        this.getPendingBatchPlan();
      }else{
        alert('Some error Occured');
      }
    });
  }

}
