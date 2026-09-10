import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers : [DatePipe]
})
export class LogComponent implements OnInit {

  isNew = false;
  results;
  material_type = '';
  from_date = '';
  to_date = '';
  status = '';

  selectedSampling = [];
  constructor(private service: DataAccessService, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getSamplings();
  }

  getSamplings() {
    this.service.get('qc/sampling/packing.php?type=getSamplings&material_type=' + this.material_type + '&from_date=' + this.from_date + '&to_date=' + this.to_date + '&status=' + this.status).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    this.isNew = true;
  }

  downloadReport(){
    this.service.open('gmptotal/qc/sampling/packing.php?type=getsampling');
  }
  downloadPDF(type){
    if(type == 'manual'){
      this.service.open('pdf1/sampling.php?type=samplingPacking&id=' +this.selectedSampling['id']);
    }else{
      this.service.open('pdf1/sampling.php?type=samplingdigitalPacking&id=' +this.selectedSampling['id']);
    }
  }

}
