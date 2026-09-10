import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-label',
  templateUrl: './label.component.html',
  styleUrls: ['./label.component.css'],
  providers:[DatePipe]
})
export class LabelComponent implements OnInit {

  from_date='';
  to_date='';
  today='';
  labels;
  constructor(private service:DataAccessService, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getLabels();
  }
  getLabels(){
    this.service.get('store/label.php?type=getAwaitingGRNRawLabels&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.labels=response;
    })
  }
  download(){
    this.service.open('store/label.php?type=downloadLabels&from_date='+this.from_date+'&to_date='+this.to_date);
  }


}
