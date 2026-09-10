import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {

  isView = false;
  results;
  material;
  selectedSampling = [];
  material_code='';
  from_date='';
  to_date='';

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
     this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');     
     this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }
  ngOnInit() {
    this.getSamplings();
    this.getMaterials();
  }

  getSamplings() {
    this.service.get('qc/sampling/raw.php?type=getSamplings&material_code='+this.material_code+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    this.isView = true;
  }
  downloadReport(){
    // this.service.open('pdf1/sampling.php?type=samplinglog');
    this.service.open('qc/sampling/raw.php?type=downloadSamplingsLog&material_code='+this.material_code+'&from_date='+this.from_date+'&to_date='+this.to_date);

  }

  getMaterials(){
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.material = response;
    });
  }

  downloadPDF(type){
    // if(type == 'manual'){
    //   this.service.open('pdf1/sampling/raw.php?type=sampling&id=' + this.selectedSampling['id']);
    // }else{
    //   this.service.open('pdf1/sampling/raw.php?type=samplingdigital&id=' + this.selectedSampling['id']);
    // }
     if(type == 'manual'){
      this.service.open('qc/sampling/raw.php?type=downloadSamplingsRecord&id=' + this.selectedSampling['id']);
    }else{
      this.service.open('qc/sampling/raw.php?type=downloadSamplingRecordDigital&id=' + this.selectedSampling['id']);
    }
  }
}
