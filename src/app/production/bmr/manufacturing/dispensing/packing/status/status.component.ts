import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-status',
  templateUrl: './status.component.html',
  styleUrls: ['./status.component.css'],
  providers: [DatePipe]
})
export class StatusComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];
  selectedMaterial=[];
  isShow=false;
  from_date='';
  product_type='';
  to_date='';
  units;
  company_unit='';

  constructor(private service: DataAccessService ,private datePipe: DatePipe) {
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getDispensingLog();
  }

  getDispensingLog(){
    this.service.get('production/plant9/dispensing.php?type=getPackingDispensingLog&company_unit='+this.company_unit+'&product_type='+this.product_type+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  download(){
    this.service.open('production/plant9/dispensing.php?type=downloadDispensingLog&company_unit='+this.company_unit+'&product_type='+this.product_type+'&to_date='+this.to_date+'&from_date='+this.from_date);
  }

  downloadRecord(){
    this.service.open('production/plant9/dispensing.php?type=downloadPackingDispensingReport&id='+this.selectedResult['id']);
  }

}
