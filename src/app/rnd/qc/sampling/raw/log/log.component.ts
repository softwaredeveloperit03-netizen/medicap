import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results;

  selectedSampling = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getSamplings();
  }

  getSamplings() {
    this.service.get('qc/sampling/raw.php?type=getSamplings').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    this.isView = true;
  }
  downloadReport(){
    this.service.open('pdf1/sampling.php?type=samplinglog');
  }
  downloadPDF(type){
    if(type == 'manual'){
      this.service.open('pdf1/sampling/raw.php?type=sampling&id=' + this.selectedSampling['id']);
    }else{
      this.service.open('pdf1/sampling/raw.php?type=samplingdigital&id=' + this.selectedSampling['id']);
    }
  }
}
