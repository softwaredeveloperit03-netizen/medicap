import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-batch-log',
  templateUrl: './batch-log.component.html',
  styleUrls: ['./batch-log.component.css']
})
export class DashboardComponent implements OnInit {

  isView = false;
  results;
  selectedResult=[];
  raw_materials=[];
  packing_materials=[];
  plant_type='';
  from_date='';
  to_date='';
  today='';
  product_name='';
  showTailingBatches;
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.plant_type = this.service.getPlantConfigFields('plant_type');
  }

  ngOnInit() {
    this.getPlans();
  }

  getPlans(){
    this.service.get('production/plan.php?type=getPlans&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.packing_materials = this.selectedResult['packing_material'];
    this.raw_materials = this.selectedResult['raw_materials'];
    this.isView=true;
  }
  
  download(){
    this.service.open('production/bmr/plan.php?type=downloadPlans&from_date='+this.from_date+'&to_date='+this.to_date);
  }

}
