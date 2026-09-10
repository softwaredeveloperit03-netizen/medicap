import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe]
})
export class LogComponent implements OnInit {

  isView = false;
  entries;

  selectedResult = [];

  material_type = 'Finished Product';
  from_date = '';
  to_date = '';
  constructor(private datePipe: DatePipe,private service: DataAccessService, private router: Router) {
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit() {
    this.getControlsamples();
  }

  getControlsamples() {
    this.service.get('qa/controlsample.php?type=getControlSamples&material_type=' + this.material_type + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.entries = response;
    });
  }

  view(index){
    this.selectedResult = this.entries[index];
    this.isView = true;
  }

  close() {
    this.router.navigate(['/controlsample']);
  }

  getprint(){
    this.service.open('pdf1/controlsample.php?type=controlsamplelog&fromdate='+this.from_date+'&todate='+this.to_date);
  }

}
