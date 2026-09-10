import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-status',
  templateUrl: './status.component.html',
  styleUrls: ['./status.component.css'],
  providers:[DatePipe]
})
export class StatusComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  stages=[];
  dosages;
  dosage_form='';
  from_date='';
  to_date='';
  plant_type;
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {  
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');     
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   
    this.plant_type = this.service.getPlantConfigFields('plant_type');

  }
  plant_id;
  ngOnInit() {
    this.getDispensingLog();
    this.getDosages();
    this.plant_id = this.service.getPlantConfigFields("plant_id")
  }
  getDispensingLog(){
    this.service.get('production/dispensing.php?type=getDispensingLog&from_date='+this.from_date+'&to_date='+this.to_date +'&dosage_form='+this.dosage_form).subscribe(response=>{
      this.results=response;
    });
  }
  label_claim;
  checkpoints;  
  clearances;  
  view(index){
    this.selectedResult=this.results[index];
    this.label_claim=JSON.parse(this.selectedResult['label_claim']);

    this.checkpoints=JSON.parse(this.selectedResult['instruction'][0]['checkpoints']);
    this.clearances=JSON.parse(this.selectedResult['instruction'][0]['qc_checkpoints']);
    this.stages=this.selectedResult['stage'];
    this.isView=true;
  }
  getDosages(){
    this.service.get('common.php?type=getDosages').subscribe(response=>{
      this.dosages=response;
    });
  }
  download(){
    this.service.open('production/dispensing.php?type=downloadRecordDispensingLog&from_date='+this.from_date+'&to_date='+this.to_date +'&dosage_form='+this.dosage_form);
  }
  downloadPdf(){
    this.service.open('production/dispensing.php?type=downloadDispensingLog&id='+ this.selectedResult['id']);
  }
}
