import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {

  results;
  dosages;
  dosage_form = '';
  from_date = '';
  to_date = '';
  selectedResult=[];
  isView=false;

  constructor(private service: DataAccessService, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getDosages();
    this.getFinishControlSamples();
  }

  getDosages(){
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getFinishControlSamples(){
    this.service.get('qa/controlsample.php?type=getFinishControlSamples&dosage_form=' + this.dosage_form + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  download() {
    this.service.open('qa/controlsample.php?type=downloadFinishControlSamples&dosage_form=' + this.dosage_form + '&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

}
