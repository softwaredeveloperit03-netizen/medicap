import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-start',
  templateUrl: './start.component.html',
  styleUrls: ['./start.component.css'],
  providers:[DatePipe]
})
export class StartComponent implements OnInit {

  results;
  batch_no='';
  selectedResult=[];
  isView=false;
  from_date: string;
  to_date: string;
  today: string;
  constructor(private service:DataAccessService, private datePipe: DatePipe) { 
      
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getPendingBatches();
  }

 
  getPendingBatches(){
    // this.service.get('production/plan.php?type=getPlans&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
    this.service.get('production/plan.php?type=getPlansForStartProduction&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }
  // getPendingBatches(){
  //   this.service.get('production/manufacturing2.php?type=getPendingBatches').subscribe(response=>{
  //     this.results=response;
  //   });
  // }

  viewBatch(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  start(){
    let temp = this.selectedResult;
    temp['batch_no'] = this.batch_no;
    this.service.post('production/manufacturing2.php?type=startProduction&id='+this.selectedResult['id']+ '&batch_no='+this.batch_no,JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data save successfuly');
        this.isView=false;
        this.getPendingBatches();
      }else{
        alertify.error('some error occured!');
      }
    });
  }

  calculate() {
    let materials = this.selectedResult['raw_materials'];
    for (let i = 0; i< materials.length; i++) {
      let material = materials[i];
      if (+material['recovery_stock'] < +material['recovery_qty']) {
        material['recovery_qty'] = material['recovery_stock'];
      }
      material['dispensing_qty'] = +material['qty'] - +material['recovery_qty'];
      materials[i] = material;
    }
    this.selectedResult['raw_materials'] = materials;
  }

}
