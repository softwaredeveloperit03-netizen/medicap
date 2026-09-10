import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe]
})
export class LogComponent implements OnInit {
  results;
  selectedReport=[];
  isView=false;
  material;
  material_type='';
  material_name='';
  to_date='';
  from_date='';
  constructor(private service:DataAccessService, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getRawSpillagesLog();
  }

  getRawSpillagesLog(){
    this.service.get('store/spillage.php?type=getRawSpillagesLog&material_type='+this.material_type+'&material_name=' +this.material_name +'&from_date='+this.from_date +'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedReport=this.results[index];
    this.isView=true;
  }
  
  getMaterialsByType(value) {
    this.service.get('store/spillage.php?type=getMaterialsByType&material_type='+value).subscribe(response=>{
      this.material=response;
    });
  }

}
