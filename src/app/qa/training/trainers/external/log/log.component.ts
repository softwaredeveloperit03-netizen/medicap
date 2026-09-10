import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  isView=false;
  results;
  resume:File;
  selectedResult=[];
  constructor( private service:DataAccessService) { }

  ngOnInit(): void {
    this.getExternalTrainersLog();
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  getExternalTrainersLog(){
    this.service.get('qa/trainer.php?type=getExternalTrainersLog').subscribe(response=>{
      this.results=response;
    });
  }

  pdf(value) {
    if (value == 'certificate') {
      window.open(this.service.url + this.selectedResult['certificate']);
    }
  }
  pdfResume(value) {
    if (value == 'resume') {
      window.open(this.service.url + this.selectedResult['resume']);
    }
  }

}
