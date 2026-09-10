import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  start_date='';
  stop_date='';
  // products;
  // batches = [];

  isView=false;
  selectedResult = [];
  results;
  batches=[];
  selectProduct=[];
  number=[];
  constructor(private service: DataAccessService, private router: Router) {
   
   }

  ngOnInit() {
    this.getPendingPlans();
  }

  getPendingPlans() {
    this.service.get('production/planning.php?type=getPendingPlans').subscribe(response => {
      this.results = response;
    });
  } 
  view(index){
    this.selectedResult = this.results[index];
    let batches = [];
    for (let  i = 0; i < +this.selectedResult['batches']; i++) {
      let temp = {};
      let j = i + 1;
      temp['batch_no'] = "B00"+ j;
      temp['start_date'] = '';
      temp['complete_date'] = '';
      batches[batches.length] = temp;
    }
    this.selectedResult['batchList'] = batches;
    this.isView=true;
  }
 
  

  save(data){
    let temp={};
    temp['plan_no']=this.selectedResult['plan_no'];
    temp['id']=this.selectedResult['id'];
    temp['batch_size']=this.selectedResult['batch_size'];
    temp['product_code']=this.selectedResult['product_code'];
    temp['dosage_form']=this.selectedResult['dosage_form'];
    temp['batches']=this.selectedResult['batches'];
    temp['batchList']=this.selectedResult['batchList'];
    this.service.post('production/planning.php?type=saveBMRPlan',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alert('save Batches successfuly');
        this.getPendingPlans();
        this.isView=false;
        this.selectedResult['batchList']=[];
      }else{
        alert('some error occured');
      }
    });
  }



}
