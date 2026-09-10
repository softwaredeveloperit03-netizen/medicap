import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-capa',
  templateUrl: './capa.component.html',
  styleUrls: ['./capa.component.css'],
  providers:[DatePipe]
})
export class CapaComponent implements OnInit {

  isView = false;
  results;

  fromdate;
  todate;

  selectedDev = [];
  constructor(private datePipe: DatePipe,private service: DataAccessService) {
    this.fromdate = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getDeviations();
  }

  getDeviations() {
    this.service.get('deviation.php?type=getCapaDeviationsDept').subscribe(response => {
      this.results = response;
    });
  }

  viewDeviation(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

}
